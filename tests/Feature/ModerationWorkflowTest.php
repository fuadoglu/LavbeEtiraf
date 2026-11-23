<?php

namespace Tests\Feature;

use App\Http\Controllers\GuestConfessionController;
use App\Models\Confession;
use App\Models\ConfessionRevision;
use App\Models\ModerationAudit;
use App\Models\ModeratorGrant;
use App\Models\ModeratorMfa;
use App\Models\ModeratorRecoveryCode;
use App\Models\User;
use App\Support\Moderation\ModeratorMfaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ModerationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['moderation.mfa_key' => base64_encode(str_repeat('k', 32))]);
    }

    public function test_totp_matches_rfc_vector_and_encrypted_secret_uses_separate_key(): void
    {
        $mfa = app(ModeratorMfaService::class);
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        $this->assertSame('287082', $mfa->codeForCounter($secret, 1));
        $ciphertext = $mfa->encryptSecret('moderator-secret');
        $this->assertNotSame('moderator-secret', $ciphertext);
        $this->assertSame('moderator-secret', $mfa->decryptSecret($ciphertext));

        config(['moderation.mfa_key' => base64_encode(str_repeat('x', 32))]);
        try {
            $mfa->decryptSecret($ciphertext);
            $this->fail('A rotated independent MFA key must not decrypt the stored secret.');
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        }
    }

    public function test_login_requires_verified_active_moderator_and_redirects_to_mfa_enrollment(): void
    {
        $ordinary = User::factory()->create(['password' => Hash::make('secret-password')]);
        $this->post('/az/moderation/login', ['email' => $ordinary->email, 'password' => 'secret-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $user = User::factory()->unverified()->create(['password' => Hash::make('secret-password')]);
        ModeratorGrant::query()->create(['user_id' => $user->id, 'role' => 'moderator', 'locale' => 'az', 'reason' => 'synthetic test grant']);
        $this->post('/az/moderation/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $user = $this->moderator('az', false);
        Auth::logout();
        $this->post('/az/moderation/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/az/moderation/mfa/enroll');
        $this->assertAuthenticatedAs($user);
        $this->get('/az/moderation/mfa/enroll')->assertOk()->assertSee('Doğrulama tətbiqi üçün quraşdırma ünvanı');
    }

    public function test_mfa_enrollment_only_stores_ciphertext_and_recovery_codes_are_one_time(): void
    {
        $user = $this->moderator('az', false);
        $response = $this->actingAs($user)->get('/az/moderation/mfa/enroll')->assertOk();
        preg_match('/<code dir="ltr">([A-Z2-7]{32})<\/code>/', $response->getContent(), $secretMatch);
        $secret = $secretMatch[1] ?? null;
        $this->assertIsString($secret);
        $mfaRow = ModeratorMfa::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertNotSame($secret, $mfaRow->pending_secret_ciphertext);
        $this->assertSame($secret, app(ModeratorMfaService::class)->decryptSecret($mfaRow->pending_secret_ciphertext));

        $counter = intdiv(time(), (int) config('moderation.totp_period_seconds')) + 1;
        $code = app(ModeratorMfaService::class)->codeForCounter($secret, $counter);
        $confirmed = $this->post('/az/moderation/mfa/confirm', ['code' => $code])->assertOk()->assertSee('Hər kodu yalnız bir dəfə');
        preg_match_all('/<code dir="ltr">([A-Za-z0-9_-]{27})<\/code>/', $confirmed->getContent(), $matches);
        $codes = $matches[1] ?? [];
        $this->assertCount(10, $codes);
        $this->assertSame(10, ModeratorRecoveryCode::query()->where('user_id', $user->id)->count());
        foreach ($codes as $recoveryCode) {
            $this->assertDatabaseHas('moderator_recovery_codes', [
                'user_id' => $user->id,
                'code_hash' => app(ModeratorMfaService::class)->recoveryCodeHash($recoveryCode),
            ]);
            $this->assertStringNotContainsString($recoveryCode, DB::table('moderator_recovery_codes')->where('user_id', $user->id)->value('code_hash'));
        }

        $this->post('/az/moderation/mfa/verify', ['code' => $codes[0]])->assertRedirect('/az/moderation/');
        $this->assertNotNull(ModeratorRecoveryCode::query()->where('user_id', $user->id)->whereNotNull('used_at')->first());
    }

    public function test_totp_step_cannot_be_replayed(): void
    {
        $service = app(ModeratorMfaService::class);
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $counter = intdiv(time(), 30);
        $code = $service->codeForCounter($secret, $counter);
        $this->assertSame($counter, $service->matchingCounter($secret, $code, $counter - 1, $counter * 30));
        $this->assertNull($service->matchingCounter($secret, $code, $counter, $counter * 30));
    }

    public function test_moderator_queue_and_private_headers_require_fresh_mfa_and_never_cross_language_grants(): void
    {
        $confession = $this->pendingConfession('az');
        $az = $this->moderator('az');
        $this->actingAs($az)->withSession($this->freshMfaSession($az))->get('/az/moderation')
            ->assertOk()
            ->assertSee('Synthetic pending title')
            ->assertSee('class="moderation-workspace"', false)
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('reader-consent-banner')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertStringContainsString('no-store', (string) $this->get('/az/moderation')->headers->get('Cache-Control'));

        $otherLanguageConfession = $this->pendingConfession('en');
        $this->actingAs($az)->withSession($this->freshMfaSession($az))
            ->get('/az/moderation/confessions/'.$otherLanguageConfession->id)->assertNotFound();
        $this->actingAs($az)->withSession(['moderator.mfa_verified_at' => time() - 301, 'moderator.mfa_user_id' => $az->id])
            ->get('/az/moderation')->assertRedirect('/az/moderation/mfa/challenge');

        $admin = User::factory()->create();
        ModeratorGrant::query()->create(['user_id' => $admin->id, 'role' => 'admin', 'locale' => '*', 'reason' => 'synthetic admin grant']);
        ModeratorMfa::query()->create([
            'user_id' => $admin->id,
            'secret_ciphertext' => app(ModeratorMfaService::class)->encryptSecret('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'),
            'confirmed_at' => now(),
            'last_used_counter' => 1,
        ]);
        $this->actingAs($admin)->withSession($this->freshMfaSession($admin))->get('/az/moderation/confessions/'.$confession->id)
            ->assertNotFound();
    }

    public function test_approval_publishes_only_the_selected_pending_revision_and_records_redacted_audit_and_outbox(): void
    {
        $user = $this->moderator('az');
        $confession = $this->pendingConfession('az');
        $revision = $confession->revisions()->firstOrFail();
        $this->actingAs($user)->withSession($this->freshMfaSession($user))
            ->post('/az/moderation/confessions/'.$confession->id.'/decision', $this->decisionPayload($confession, $revision, 'approve'))
            ->assertRedirect('/az/moderation/');

        $confession->refresh();
        $revision->refresh();
        $this->assertSame('public', $confession->status);
        $this->assertSame($revision->id, $confession->public_revision_id);
        $this->assertSame('Synthetic pending confession', $confession->body);
        $this->assertSame('approved', $revision->status);
        $this->assertTrue($confession->ever_public);
        $this->assertDatabaseHas('moderation_audits', ['decision' => 'approve', 'target_public_id' => $confession->public_id]);
        $this->assertDatabaseHas('moderation_outbox', ['event_type' => 'publish', 'visibility_version' => 2]);
        $audit = ModerationAudit::query()->where('decision', 'approve')->firstOrFail();
        $this->assertStringNotContainsString('Synthetic pending confession', json_encode($audit->getAttributes()));
        $this->get('/az/')->assertSee('Synthetic pending confession');
    }

    public function test_stale_moderator_tab_and_changed_idempotent_replay_are_rejected_without_side_effects(): void
    {
        $user = $this->moderator('az');
        $confession = $this->pendingConfession('az');
        $revision = $confession->revisions()->firstOrFail();
        $payload = $this->decisionPayload($confession, $revision, 'request_changes');
        $payload['author_message'] = 'Please remove identifying details.';
        $this->actingAs($user)->withSession($this->freshMfaSession($user))
            ->post('/az/moderation/confessions/'.$confession->id.'/decision', $payload)->assertRedirect('/az/moderation/');
        $this->assertSame('unpublished', $confession->fresh()->status);
        $this->assertSame('changes_requested', $revision->fresh()->status);
        $this->post('/az/moderation/confessions/'.$confession->id.'/decision', $payload)->assertRedirect('/az/moderation/');
        $payload['decision'] = 'reject';
        $this->post('/az/moderation/confessions/'.$confession->id.'/decision', $payload)->assertStatus(409);
        $this->assertSame(1, ModerationAudit::query()->where('target_public_id', $confession->public_id)->count());
        $this->assertSame(2, $confession->fresh()->lock_version);
    }

    public function test_owner_withdraw_cancels_pending_revision_and_makes_old_approval_stale(): void
    {
        $this->post('/az/write', [
            'idempotency_key' => 'submission-key-'.str_repeat('w', 32),
            'title' => 'Synthetic pending title',
            'body' => 'Synthetic pending confession',
            'category_key' => 'family',
            'age_confirmed' => '1',
        ])->assertCreated();
        $confession = Confession::query()->firstOrFail();
        $revision = $confession->revisions()->firstOrFail();
        $ownerSessionId = session()->getId();
        $request = Request::create('/az/my/'.$confession->public_id.'/withdraw', 'POST', [
            'expected_lock_version' => 1,
            'idempotency_key' => str_repeat('a', 32),
            'confirm_withdraw' => '1',
        ]);
        $ownerSession = new Store('test-owner-session', new ArraySessionHandler(120));
        $ownerSession->setId($ownerSessionId);
        $ownerSession->start();
        $request->setLaravelSession($ownerSession);
        $this->assertSame(302, app(GuestConfessionController::class)->withdraw($request, 'az', $confession->public_id)->getStatusCode());

        $confession->refresh();
        $this->assertSame('withdrawn', $confession->status);
        $this->assertSame('cancelled', $revision->fresh()->status);
        $this->assertSame(2, $confession->lock_version);
        $this->assertDatabaseHas('moderation_outbox', ['event_type' => 'withdraw', 'visibility_version' => 2]);

        $moderator = $this->moderator('az');
        $this->postDecision($moderator, $confession, $revision, 'approve')->assertStatus(409);
    }

    public function test_owner_withdrawal_closes_a_public_confession_even_from_an_older_status_view(): void
    {
        $this->post('/az/write', [
            'idempotency_key' => 'submission-key-'.str_repeat('q', 32),
            'title' => 'Synthetic pending title',
            'body' => 'Synthetic pending confession',
            'category_key' => 'family',
            'age_confirmed' => '1',
        ])->assertCreated();
        $confession = Confession::query()->firstOrFail();
        $revision = $confession->revisions()->firstOrFail();
        $ownerSessionId = session()->getId();
        $moderator = $this->moderator('az');
        $this->approve($moderator, $confession, $revision);
        $confession->refresh();
        $this->assertSame('public', $confession->status);
        $this->assertSame(2, $confession->lock_version);

        $request = Request::create('/az/my/'.$confession->public_id.'/withdraw', 'POST', [
            'expected_lock_version' => 1,
            'idempotency_key' => str_repeat('d', 32),
            'confirm_withdraw' => '1',
        ]);
        $ownerSession = new Store('test-owner-session', new ArraySessionHandler(120));
        $ownerSession->setId($ownerSessionId);
        $ownerSession->start();
        $request->setLaravelSession($ownerSession);

        $this->assertSame(302, app(GuestConfessionController::class)->withdraw($request, 'az', $confession->public_id)->getStatusCode());
        $this->assertSame('withdrawn', $confession->fresh()->status);
        $this->assertDatabaseHas('moderation_outbox', ['event_type' => 'withdraw', 'visibility_version' => 3]);
    }

    public function test_hide_restore_and_terminal_delete_update_visibility_atomically(): void
    {
        $user = $this->moderator('az');
        $confession = $this->pendingConfession('az');
        $revision = $confession->revisions()->firstOrFail();
        $this->approve($user, $confession, $revision);

        $confession->refresh();
        $this->postDecision($user, $confession, $revision, 'hide')->assertRedirect('/az/moderation/');
        $confession->refresh();
        $this->assertSame('hidden', $confession->status);
        $this->assertSame(3, $confession->visibility_version);

        $this->postDecision($user, $confession, $revision, 'restore', ['restore_reviewed' => '1'])->assertRedirect('/az/moderation/');
        $confession->refresh();
        $this->assertSame('public', $confession->status);
        $this->assertSame(4, $confession->visibility_version);

        $this->postDecision($user, $confession, $revision, 'terminal_delete')->assertRedirect('/az/moderation/');
        $confession->refresh();
        $this->assertSame('deleted', $confession->status);
        $this->assertSame('', $confession->body);
        $this->assertNull($confession->public_revision_id);
        $this->assertSame(0, $confession->revisions()->count());
        $this->assertDatabaseHas('moderation_outbox', ['event_type' => 'terminal_delete', 'visibility_version' => 5]);
    }

    public function test_admin_grants_are_explicitly_language_scoped_and_audited(): void
    {
        $adminUser = User::factory()->create();
        ModeratorGrant::query()->create(['user_id' => $adminUser->id, 'role' => 'admin', 'locale' => '*', 'reason' => 'synthetic admin grant']);
        ModeratorMfa::query()->create([
            'user_id' => $adminUser->id,
            'secret_ciphertext' => app(ModeratorMfaService::class)->encryptSecret('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'),
            'confirmed_at' => now(),
            'last_used_counter' => 1,
        ]);
        $this->actingAs($adminUser)->withSession($this->freshMfaSession($adminUser));
        $target = User::factory()->create();
        $this->post('/az/moderation/grants/search', ['user_search' => $target->email])
            ->assertOk()->assertSee($target->email);
        $this->assertDatabaseCount('moderator_grants', 1);
        $this->post('/az/moderation/grants', [
            'user_id' => $target->id,
            'role' => 'moderator',
            'locale' => 'az',
            'reason' => 'Synthetic language assignment',
            'idempotency_key' => str_repeat('b', 32),
        ])->assertRedirect('/az/moderation/grants');
        $this->assertDatabaseHas('moderator_grants', ['user_id' => $target->id, 'role' => 'moderator', 'locale' => 'az', 'is_active' => 1]);
        $this->assertDatabaseHas('moderation_audits', ['decision' => 'grant', 'target_key' => (string) $target->id]);
        $targetGrant = ModeratorGrant::query()->where('user_id', $target->id)->firstOrFail();
        $this->get('/az/moderation/grants')->assertOk()
            ->assertSee('class="moderation-grants-grid"', false)
            ->assertSee('for="revoke-reason-'.$targetGrant->id.'"', false)
            ->assertSee('id="revoke-reason-'.$targetGrant->id.'"', false)
            ->assertSee('id="reason"', false);
        config(['lavbe.pagination.moderation' => 1]);
        $this->get('/az/moderation/grants')->assertOk()
            ->assertSee('action="'.route('moderation.grants.search', ['locale' => 'az']).'"', false);

        $this->post('/az/moderation/grants', [
            'user_id' => $adminUser->id,
            'role' => 'moderator',
            'locale' => 'en',
            'reason' => 'Self assignment attempt',
            'idempotency_key' => str_repeat('c', 32),
        ])->assertForbidden();
    }

    private function moderator(string $locale, bool $confirmed = true): User
    {
        $user = User::factory()->create();
        ModeratorGrant::query()->create([
            'user_id' => $user->id,
            'role' => 'moderator',
            'locale' => $locale,
            'reason' => 'Synthetic test grant',
        ]);
        if ($confirmed) {
            ModeratorMfa::query()->create([
                'user_id' => $user->id,
                'secret_ciphertext' => app(ModeratorMfaService::class)->encryptSecret(app(ModeratorMfaService::class)->newSecret()),
                'confirmed_at' => now(),
                'last_counter' => intdiv(time(), 30) - 1,
            ]);
        }

        return $user;
    }

    private function freshMfaSession(User $user): array
    {
        return ['moderator.mfa_verified_at' => time(), 'moderator.mfa_user_id' => (int) $user->id];
    }

    private function pendingConfession(string $locale): Confession
    {
        $confession = Confession::query()->create([
            'locale' => $locale,
            'status' => 'unpublished',
            'title' => '',
            'body' => '',
            'public_id' => (string) Str::uuid(),
            'author_alias' => 'Lavbe #A1B2C3D4',
            'lock_version' => 1,
            'visibility_version' => 1,
        ]);
        $confession->revisions()->create([
            'revision' => 1,
            'title' => 'Synthetic pending title',
            'body' => 'Synthetic pending confession',
            'category_key' => 'family',
            'status' => 'pending',
        ]);

        return $confession;
    }

    private function decisionPayload(Confession $confession, ConfessionRevision $revision, string $decision): array
    {
        return [
            'revision_id' => $revision->id,
            'expected_lock_version' => $confession->lock_version,
            'decision' => $decision,
            'reason_code' => $decision === 'approve' ? 'acceptable' : 'privacy',
            'idempotency_key' => bin2hex(random_bytes(16)),
        ];
    }

    private function approve(User $user, Confession $confession, ConfessionRevision $revision): void
    {
        $this->actingAs($user)->withSession($this->freshMfaSession($user))
            ->post('/az/moderation/confessions/'.$confession->id.'/decision', $this->decisionPayload($confession, $revision, 'approve'))
            ->assertRedirect('/az/moderation/');
    }

    private function postDecision(User $user, Confession $confession, ConfessionRevision $revision, string $decision, array $extra = []): TestResponse
    {
        return $this->actingAs($user)->withSession($this->freshMfaSession($user))
            ->post('/az/moderation/confessions/'.$confession->id.'/decision', array_merge($this->decisionPayload($confession, $revision, $decision), $extra));
    }
}
