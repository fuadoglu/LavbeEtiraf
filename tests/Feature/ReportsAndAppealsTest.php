<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\ConfessionCommentRevision;
use App\Models\ModerationAppeal;
use App\Models\ModerationAudit;
use App\Models\ModeratorGrant;
use App\Models\ModeratorMfa;
use App\Models\ReportGroup;
use App\Models\ReportSubmission;
use App\Models\User;
use App\Support\Moderation\ModeratorMfaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportsAndAppealsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['moderation.mfa_key' => base64_encode(str_repeat('k', 32))]);
    }

    public function test_report_groups_are_target_and_channel_specific_deduplicated_and_do_not_hide_content(): void
    {
        [$confession, $revision] = $this->publicConfession();
        $comment = $this->publicComment($confession);
        $reporter = User::factory()->create();
        $this->actingAs($reporter);
        $response = $this->post($this->reportUrl('confession', $confession->public_id), $this->reportPayload('spam', 'Private explanation'));
        $response->assertStatus(202);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $group = ReportGroup::query()->firstOrFail();
        $this->post($this->reportUrl('confession', $confession->public_id), $this->reportPayload('threat', 'Different retry key'))->assertStatus(202);
        $this->assertSame(1, $group->fresh()->report_count, 'One session counts only once for the target/channel group.');
        $this->assertSame('normal', $group->fresh()->priority, 'A duplicate submission cannot escalate priority.');
        $this->actingAs(User::factory()->create())->post($this->reportUrl('confession', $confession->public_id), $this->reportPayload('threat', 'Threat detail'))->assertStatus(202);
        $this->assertSame(2, $group->fresh()->report_count);
        $this->assertSame('urgent', $group->fresh()->priority);
        $this->assertSame('public', $confession->fresh()->status);
        $this->post($this->reportUrl('comment', $comment->public_id), $this->reportPayload('spam'))->assertStatus(202);
        $this->assertSame(2, ReportGroup::query()->count());
        $this->post($this->reportUrl('confession', $confession->public_id, 'legal'), $this->reportPayload('privacy', 'Legal context'))->assertStatus(202);
        $this->assertSame(3, ReportGroup::query()->count());
        $legal = ReportGroup::query()->where('channel', 'legal')->firstOrFail();
        $this->assertSame('legal', $legal->priority);
        $stored = ReportSubmission::query()->whereNotNull('details')->firstOrFail();
        $this->assertNotSame('Private explanation', $stored->getRawOriginal('details'));
        $this->assertSame('Private explanation', $stored->details);
        $this->assertSame(0, ModerationAudit::query()->count());
    }

    public function test_report_queue_requires_fresh_mfa_and_only_shows_granted_languages(): void
    {
        [$confession] = $this->publicConfession();
        $this->post($this->reportUrl('confession', $confession->public_id), $this->reportPayload('spam'))->assertStatus(202);
        $moderator = $this->moderator('az');
        $this->actingAs($moderator)->get('/az/moderation/reports')->assertRedirect('/az/moderation/mfa/challenge');
        $this->actingAs($moderator)->withSession($this->freshMfa($moderator))->get('/az/moderation/reports')->assertOk()->assertSee('Şikayət qrupuna bax');
        $other = $this->moderator('en');
        $this->actingAs($other)->withSession($this->freshMfa($other))->get('/az/moderation/reports')->assertOk()->assertDontSee('Şikayət qrupuna bax');
    }

    public function test_moderator_can_dismiss_without_changing_target_and_group_hide_is_atomic_with_audit(): void
    {
        [$confession, $revision] = $this->publicConfession();
        $this->post($this->reportUrl('confession', $confession->public_id), $this->reportPayload('spam'))->assertStatus(202);
        $group = ReportGroup::query()->firstOrFail();
        $moderator = $this->moderator('az');
        $base = [
            'expected_group_lock_version' => $group->lock_version, 'expected_parent_lock_version' => $confession->lock_version,
            'expected_target_lock_version' => $confession->lock_version, 'revision_id' => $revision->id,
            'reason_code' => 'no_violation', 'idempotency_key' => bin2hex(random_bytes(16)),
        ];
        $this->actingAs($moderator)->withSession($this->freshMfa($moderator))
            ->post('/az/moderation/reports/'.$group->id.'/decision', $base + ['decision' => 'dismiss'])->assertRedirect('/az/moderation/reports');
        $this->assertSame('public', $confession->fresh()->status);
        $this->assertSame('resolved', $group->fresh()->status);
        $this->assertDatabaseHas('moderation_audits', ['target_type' => 'report_group', 'decision' => 'dismiss_report']);

        $this->actingAs(User::factory()->create())->post($this->reportUrl('confession', $confession->public_id, 'legal'), $this->reportPayload('privacy', 'Synthetic legal notice'))->assertStatus(202);
        $newGroup = ReportGroup::query()->where('channel', 'legal')->firstOrFail();
        $hide = [
            'expected_group_lock_version' => $newGroup->lock_version, 'expected_parent_lock_version' => $confession->fresh()->lock_version,
            'expected_target_lock_version' => $confession->fresh()->lock_version, 'revision_id' => $revision->id,
            'reason_code' => 'privacy', 'decision' => 'hide', 'idempotency_key' => bin2hex(random_bytes(16)),
        ];
        $this->actingAs($moderator)->withSession($this->freshMfa($moderator))->post('/az/moderation/reports/'.$newGroup->id.'/decision', $hide)->assertRedirect('/az/moderation/reports');
        $this->assertSame('hidden', $confession->fresh()->status);
        $this->assertSame('resolved', $newGroup->fresh()->status);
        $this->assertDatabaseHas('moderation_audits', ['target_type' => 'confession', 'decision' => 'hide']);
        $this->assertDatabaseHas('moderation_audits', ['target_type' => 'report_group', 'decision' => 'hide_report']);
    }

    public function test_only_owner_can_submit_one_appeal_and_moderator_can_restore_through_existing_decision_path(): void
    {
        [$confession, $revision] = $this->publicConfession();
        $ownerToken = 'synthetic-owner-token';
        $confession->ownership()->create(['owner_token_hash' => hash_hmac('sha256', $ownerToken, (string) config('app.key')), 'recovery_token_hash' => str_repeat('b', 64)]);
        $this->withCookie('lavbe_owner_'.$confession->public_id, $ownerToken);
        $moderator = $this->moderator('az');
        $this->actingAs($moderator)->withSession($this->freshMfa($moderator))->post('/az/moderation/confessions/'.$confession->id.'/decision', [
            'revision_id' => $revision->id, 'expected_lock_version' => $confession->lock_version,
            'decision' => 'hide', 'reason_code' => 'privacy', 'idempotency_key' => bin2hex(random_bytes(16)),
        ])->assertRedirect('/az/moderation/');
        $confession->refresh();
        $hideAudit = ModerationAudit::query()->where('target_type', 'confession')->where('decision', 'hide')->firstOrFail();
        $payload = ['details' => 'Please review this synthetic content again.', 'idempotency_key' => bin2hex(random_bytes(16))];
        $this->post('/az/appeal/confession/'.(string) Str::uuid(), $payload)->assertNotFound();
        $this->post('/az/appeal/confession/'.$confession->public_id, $payload)->assertStatus(202);
        $appeal = ModerationAppeal::query()->firstOrFail();
        $this->assertSame($hideAudit->id, $appeal->hide_audit_id);
        $this->assertNotSame($payload['details'], $appeal->getRawOriginal('details'));
        $this->post('/az/appeal/confession/'.$confession->public_id, ['details' => 'Second appeal should fail.', 'idempotency_key' => bin2hex(random_bytes(16))])->assertStatus(409);
        $resolve = [
            'expected_appeal_lock_version' => 1, 'expected_parent_lock_version' => $confession->lock_version,
            'expected_target_lock_version' => $confession->lock_version, 'revision_id' => $revision->id,
            'decision' => 'restore', 'reason_code' => 'privacy', 'idempotency_key' => bin2hex(random_bytes(16)),
        ];
        $this->actingAs($moderator)->withSession($this->freshMfa($moderator))
            ->post('/az/moderation/appeals/'.$appeal->id.'/decision', $resolve)->assertRedirect('/az/moderation/appeals');
        $this->assertSame('public', $confession->fresh()->status);
        $this->assertSame('resolved', $appeal->fresh()->status);
        $this->assertDatabaseHas('moderation_audits', ['target_type' => 'appeal', 'decision' => 'restore_appeal']);
    }

    private function publicConfession(): array
    {
        $confession = Confession::query()->create(['locale' => 'az', 'status' => 'unpublished', 'title' => '', 'body' => '', 'excerpt' => null, 'public_id' => (string) Str::uuid(), 'author_alias' => 'Guest', 'lock_version' => 1, 'visibility_version' => 1]);
        $revision = $confession->revisions()->create(['revision' => 1, 'title' => 'Safe title', 'body' => 'Synthetic public confession', 'category_key' => 'family', 'status' => 'approved']);
        $confession->forceFill(['status' => 'public', 'title' => $revision->title, 'body' => $revision->body, 'public_revision_id' => $revision->id, 'published_at' => now(), 'lock_version' => 2, 'visibility_version' => 2])->save();

        return [$confession, $revision];
    }

    private function publicComment(Confession $confession): ConfessionComment
    {
        $comment = ConfessionComment::query()->create(['confession_id' => $confession->id, 'public_id' => (string) Str::uuid(), 'status' => 'unpublished', 'author_alias' => 'Reader', 'body' => '', 'lock_version' => 1, 'visibility_version' => 1]);
        $revision = ConfessionCommentRevision::query()->create(['comment_id' => $comment->id, 'revision' => 1, 'body' => 'Synthetic public response', 'status' => 'approved']);
        $comment->forceFill(['status' => 'public', 'body' => $revision->body, 'public_revision_id' => $revision->id, 'published_at' => now(), 'lock_version' => 2, 'visibility_version' => 2])->save();

        return $comment;
    }

    private function reportUrl(string $type, string $publicId, string $channel = 'community'): string
    {
        return '/az/report/'.$channel.'/'.$type.'/'.$publicId;
    }

    private function reportPayload(string $reason, ?string $details = null): array
    {
        return ['reason_code' => $reason, 'details' => $details, 'idempotency_key' => bin2hex(random_bytes(16))];
    }

    private function moderator(string $locale): User
    {
        $user = User::factory()->create();
        ModeratorGrant::query()->create(['user_id' => $user->id, 'role' => 'moderator', 'locale' => $locale, 'reason' => 'test']);
        ModeratorMfa::query()->create(['user_id' => $user->id, 'secret_ciphertext' => app(ModeratorMfaService::class)->encryptSecret('secret'), 'confirmed_at' => now(), 'last_counter' => 1]);

        return $user;
    }

    private function freshMfa(User $user): array
    {
        return ['moderator.mfa_verified_at' => time(), 'moderator.mfa_user_id' => $user->id];
    }
}
