<?php

namespace Tests\Feature;

use App\Http\Controllers\GuestConfessionController;
use App\Models\Confession;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class GuestConfessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['session.driver' => 'array']);
        Cache::flush();
    }

    public function test_title_is_required_and_optional_author_name_stays_in_pending_revision(): void
    {
        $payload = $this->payload();
        unset($payload['title']);
        $this->from('/az/write')->post('/az/write', $payload)->assertRedirect('/az/write')->assertSessionHasErrors('title');
        $payload = $this->payload('submission-key-'.str_repeat('n', 32));
        $payload['author_display_name'] = 'Nigar Məmmədova';
        $response = $this->post('/az/write', $payload)->assertCreated();
        $story = Confession::query()->firstOrFail();
        $this->assertNull($story->ownership()->firstOrFail()->user_id);
        $this->assertSame('Synthetic title', $story->revisions()->firstOrFail()->title);
        $this->assertSame('Nigar Məmmədova', $story->revisions()->firstOrFail()->author_display_name);
        $response->assertDontSee('Nigar Məmmədova');
    }

    public function test_signed_in_author_owns_their_submission_through_the_account(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user)->post('/az/write', $this->payload())->assertCreated()
            ->assertDontSee(__('guest.write_recovery_title'));
        $confession = Confession::query()->firstOrFail();
        $this->assertSame($user->id, $confession->ownership()->firstOrFail()->user_id);
        $this->get('/az/my')->assertOk()->assertSee('Synthetic title');
    }

    public function test_title_error_returns_focus_and_body_limit_is_not_restricted_by_utf16_maxlength(): void
    {
        $payload = $this->payload();
        unset($payload['title']);
        $this->from('/az/write')->post('/az/write', $payload)->assertRedirect('/az/write')->assertSessionHasErrors('title');

        $page = $this->get('/az/write')->assertOk()->assertSee('id="title"', false)->assertSee('autofocus', false)
            ->assertSee($payload['body'])->assertSee('data-max="'.config('lavbe.limits.confession_body').'"', false)
            ->assertDontSee('maxlength="'.config('lavbe.limits.confession_body').'"', false);
        $this->assertStringContainsString('no-store', (string) $page->headers->get('Cache-Control'));
    }

    public function test_write_form_is_localized_private_and_keeps_idempotency_out_of_the_url(): void
    {
        $response = $this->get('/az/write')
            ->assertOk()
            ->assertSee('Etiraf yaz')
            ->assertSee('name="idempotency_key"', false)
            ->assertSee('name="age_confirmed"', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $this->assertStringNotContainsString('idempotency_key=', (string) $response->headers->get('Location'));

        $this->get('/pt-br/write')->assertOk()->assertSee('Escreva um desabafo');
    }

    public function test_remembered_device_uses_a_secure_http_only_cookie_only_when_selected(): void
    {
        config(['session.secure' => true]);
        $payload = $this->payload('submission-key-'.str_repeat('m', 32));
        $payload['remember_device'] = '1';
        $response = $this->call('POST', 'https://localhost/az/write', $payload)->assertCreated();
        $confession = Confession::query()->firstOrFail();
        $this->assertNull(collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'lavbe_owner_'.$confession->public_id));

        $sessionCookie = collect($response->headers->getCookies())->first(fn ($candidate) => $candidate->getName() === config('session.cookie'));
        $this->assertNotNull($sessionCookie);
        $this->assertTrue($sessionCookie->isSecure());
        $this->assertTrue($sessionCookie->isHttpOnly());
        $this->assertSame('lax', $sessionCookie->getSameSite());
        $deviceCookie = collect($response->headers->getCookies())->first(fn ($candidate) => $candidate->getName() === 'lavbe_device');
        $this->assertNotNull($deviceCookie);
        $this->assertTrue($deviceCookie->isHttpOnly());
        $this->assertTrue($deviceCookie->isSecure());
        $this->assertNotSame($deviceCookie->getValue(), Confession::query()->firstOrFail()->ownership->device_token_hash);
    }

    public function test_forget_device_revokes_device_and_legacy_owner_credentials_but_preserves_recovery(): void
    {
        $payload = $this->payload('submission-key-'.str_repeat('q', 32));
        $payload['remember_device'] = '1';
        $response = $this->post('/az/write', $payload)->assertCreated();
        $confession = Confession::query()->firstOrFail()->load('ownership');
        $deviceCookie = $response->getCookie('lavbe_device');
        $this->assertNotNull($deviceCookie);
        $recoveryHash = $confession->ownership->recovery_token_hash;
        $legacyToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $confession->ownership->forceFill([
            'owner_token_hash' => hash_hmac('sha256', $legacyToken, (string) config('app.key')),
        ])->save();

        $forget = $this->withCookie('lavbe_device', $deviceCookie->getValue())
            ->withCookie('lavbe_owner_'.$confession->public_id, $legacyToken)
            ->post('/az/my/device/forget')
            ->assertRedirect('/az/my/device')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

        $this->assertNotNull(collect($forget->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'lavbe_device' && $cookie->getExpiresTime() < time()));
        $confession->refresh()->load('ownership');
        $this->assertNull($confession->ownership->device_token_hash);
        $this->assertNotSame(hash_hmac('sha256', $legacyToken, (string) config('app.key')), $confession->ownership->owner_token_hash);
        $this->assertSame($recoveryHash, $confession->ownership->recovery_token_hash);
        $this->withCookie('lavbe_device', $deviceCookie->getValue())->get('/az/my/'.$confession->public_id)->assertNotFound();
        $this->withCookie('lavbe_owner_'.$confession->public_id, $legacyToken)->get('/az/my/'.$confession->public_id)->assertNotFound();
    }

    public function test_remembered_device_lists_only_its_own_confessions_and_device_credential_can_manage_them(): void
    {
        $this->post('/az/write', $this->payload('submission-key-'.str_repeat('d', 32)) + ['remember_device' => '1'])->assertCreated();
        $first = Confession::query()->firstOrFail()->load('ownership');
        $deviceToken = rtrim(strtr(base64_encode(hash_hmac('sha256', 'remembered-device|'.'submission-key-'.str_repeat('d', 32), (string) config('app.key'), true)), '+/', '-_'), '=');
        $this->withCookie('lavbe_device', $deviceToken)->post('/az/write', $this->payload('submission-key-'.str_repeat('e', 32)) + ['remember_device' => '1'])
            ->assertCreated();
        $second = Confession::query()->whereKeyNot($first->id)->firstOrFail()->load('ownership');
        $unrelated = Confession::factory()->create(['locale' => 'az', 'title' => 'Unrelated private story']);

        $this->withCookie('lavbe_device', $deviceToken)->get('/az/my/device')
            ->assertOk()->assertSee('Synthetic title')->assertSee('Baxış gözləyir')->assertDontSee('Unrelated private story')
            ->assertSee('/az/my/'.$first->public_id, false)
            ->assertSeeInOrder(['class="device-confession-list"', '<details class="device-access">'], false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->get('/az/my/'.$first->public_id)->assertOk()->assertSee('A synthetic private confession');

        $otherDeviceToken = rtrim(strtr(base64_encode(hash_hmac('sha256', 'remembered-device|'.'another-device-key-'.str_repeat('z', 32), (string) config('app.key'), true)), '+/', '-_'), '=');
        $this->withCookie('lavbe_device', $otherDeviceToken)->get('/az/my/device')->assertDontSee('Synthetic title');
        $this->assertNull($unrelated->ownership()->value('device_token_hash'));
        $this->assertSame($first->ownership->device_token_hash, $second->ownership->device_token_hash);
    }

    public function test_empty_device_page_offers_writing_and_account_access(): void
    {
        $this->get('/az/my/device')
            ->assertOk()
            ->assertSee(__('guest.device_empty'))
            ->assertSeeInOrder(['class="empty-state device-empty"', '/az/write', 'class="device-page-actions"', '/az/account/login', '/az/account/register'], false)
            ->assertDontSee('<details class="device-access">', false);
    }

    public function test_write_form_explains_publication_and_search_visibility(): void
    {
        $this->get('/az/write')->assertOk()
            ->assertSee('Etirafın təsdiqlənsə, hamıya açıq olacaq və axtarış nəticələrində görünə bilər.')
            ->assertSee('Etirafını əvvəlcə moderator oxuyacaq.');
    }

    public function test_write_rate_limit_returns_a_localized_private_response(): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->post('/az/write', $this->payload('submission-key-'.str_pad((string) $attempt, 32, 'r')))
                ->assertCreated();
        }

        $response = $this->post('/az/write', $this->payload('submission-key-'.str_repeat('z', 32)))
            ->assertStatus(429)
            ->assertSee('Çox tez-tez sorğu göndərildi.');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertNotNull($response->headers->get('Retry-After'));
        $this->assertDatabaseCount('confessions', 10);
    }

    public function test_submission_creates_unpublished_pending_revision_and_is_absent_from_public_read_surfaces(): void
    {
        $response = $this->post('/az/write', $this->payload());
        $response->assertCreated()->assertSee('Lavbe #');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $ownerSessionId = session()->getId();

        $confession = Confession::query()->with(['revisions', 'ownership'])->firstOrFail();
        $this->assertSame('unpublished', $confession->status);
        $this->assertNull($confession->published_at);
        $this->assertSame('', $confession->title);
        $this->assertSame('', $confession->body);
        $this->assertSame('pending', $confession->revisions->first()->status);
        $this->assertSame('A synthetic private confession', $confession->revisions->first()->body);
        $this->assertSame(15, mb_strlen($confession->author_alias, 'UTF-8'));
        preg_match('/<code dir="ltr">([A-Za-z0-9_-]{43})<\/code>/', $response->getContent(), $secretMatch);
        $this->assertSame(hash_hmac('sha256', $secretMatch[1], (string) config('app.key')), $confession->ownership->recovery_token_hash);
        $this->assertSame(hash_hmac('sha256', session()->getId(), (string) config('app.key')), $confession->ownership->session_token_hash);
        $this->assertSame($confession->ownership->session_token_hash, $confession->ownership->owner_token_hash);

        $this->get('/az/')->assertOk()->assertDontSee('A synthetic private confession');
        $this->post('/az/search', ['q' => 'synthetic private'])->assertOk()->assertDontSee('A synthetic private confession');
        $ownerResponse = $this->statusForSession($confession->public_id, $ownerSessionId);
        $this->assertSame(200, $ownerResponse->getStatusCode());
        $this->assertStringContainsString('A synthetic private confession', $ownerResponse->getContent());
    }

    public function test_idempotent_replay_does_not_repeat_recovery_secret_and_changed_payload_conflicts(): void
    {
        $payload = $this->payload();
        $first = $this->post('/az/write', $payload)->assertCreated();
        preg_match('/<code dir="ltr">([A-Za-z0-9_-]{43})<\/code>/', $first->getContent(), $match);
        $this->assertNotEmpty($match[1] ?? null);
        $secret = $match[1];
        $confession = Confession::query()->with('ownership')->firstOrFail();
        $this->assertSame(hash_hmac('sha256', $secret, (string) config('app.key')), $confession->ownership->recovery_token_hash);
        $this->assertSame(hash_hmac('sha256', session()->getId(), (string) config('app.key')), $confession->ownership->session_token_hash);
        $this->assertSame($confession->ownership->session_token_hash, $confession->ownership->owner_token_hash);

        $this->post('/az/write', $payload)
            ->assertOk()
            ->assertSee('Bu göndəriş artıq qeydə alınıb')
            ->assertDontSee($secret);
        $this->assertDatabaseCount('confessions', 1);

        $payload['body'] = 'A different synthetic payload';
        $this->post('/az/write', $payload)
            ->assertStatus(409)
            ->assertSee(__('guest.write_conflict_body'));
        $this->assertDatabaseCount('confessions', 1);
    }

    public function test_recovery_rotates_both_credentials_and_rejects_old_recovery_secret(): void
    {
        $submitted = $this->post('/az/write', $this->payload())->assertCreated();
        preg_match('/<code dir="ltr">([A-Za-z0-9_-]{43})<\/code>/', $submitted->getContent(), $match);
        $oldRecoveryCode = $match[1];
        $confession = Confession::query()->with('ownership')->firstOrFail();
        $oldSessionHash = $confession->ownership->session_token_hash;
        $oldRecoveryHash = $confession->ownership->recovery_token_hash;

        $recovered = $this->post('/az/my/'.$confession->public_id.'/recover', ['recovery_code' => $oldRecoveryCode])->assertOk();
        $rotatedSessionId = session()->getId();
        $this->assertStringContainsString('no-store', (string) $recovered->headers->get('Cache-Control'));
        preg_match('/<code dir="ltr">([A-Za-z0-9_-]{43})<\/code>/', $recovered->getContent(), $newMatch);
        $this->assertNotEmpty($newMatch[1] ?? null);
        $this->assertNotSame($oldRecoveryCode, $newMatch[1]);

        $confession->refresh()->load('ownership');
        $this->assertNotSame($oldSessionHash, $confession->ownership->session_token_hash);
        $this->assertNotSame($oldRecoveryHash, $confession->ownership->recovery_token_hash);
        $this->assertSame(2, $confession->ownership->credential_version);
        $this->post('/az/my/'.$confession->public_id.'/recover', ['recovery_code' => $oldRecoveryCode])
            ->assertNotFound()
            ->assertSee('Bu etirafı tapa bilmirik və ya ona giriş mümkün deyil.');
        $ownerResponse = $this->statusForSession($confession->public_id, $rotatedSessionId);
        $this->assertSame(200, $ownerResponse->getStatusCode());
    }

    public function test_owner_access_is_isolated_between_confessions_and_missing_owner_gets_neutral_not_found(): void
    {
        $this->post('/az/write', $this->payload('submission-key-'.str_repeat('a', 32)))->assertCreated();
        $first = Confession::query()->firstOrFail();
        $ownerSessionId = session()->getId();
        $this->post('/az/write', $this->payload('submission-key-'.str_repeat('b', 32)))->assertCreated();
        $second = Confession::query()->where('id', '<>', $first->id)->firstOrFail();

        $ownerResponse = $this->statusForSession($first->public_id, $ownerSessionId);
        $this->assertSame(200, $ownerResponse->getStatusCode());
        $response = $this->statusForSession($first->public_id, str_repeat('f', 40));
        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('Bu etirafı tapa bilmirik və ya ona giriş mümkün deyil.', $response->getContent());
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_age_consent_and_unicode_code_point_limits_are_enforced(): void
    {
        $payload = $this->payload();
        unset($payload['age_confirmed']);
        $this->post('/az/write', $payload)->assertSessionHasErrors('age_confirmed');
        $this->assertDatabaseCount('confessions', 0);

        $payload = $this->payload('submission-key-'.str_repeat('c', 32));
        $payload['body'] = str_repeat('🙂', 6001);
        $this->post('/az/write', $payload)->assertSessionHasErrors('body');
        $this->assertDatabaseCount('confessions', 0);

        $payload = $this->payload('submission-key-'.str_repeat('e', 32));
        $payload['title'] = str_repeat('🙂', 121);
        $payload['category_key'] = 'untrusted-topic';
        $payload['response_expectation'] = 'publish-now';
        $this->post('/az/write', $payload)->assertSessionHasErrors(['title', 'category_key', 'response_expectation']);
        $this->assertDatabaseCount('confessions', 0);

        $payload = $this->payload('submission-key-'.str_repeat('d', 32));
        $payload['body'] = str_repeat('🙂', 6000);
        $this->post('/az/write', $payload)->assertCreated();
        $this->assertDatabaseCount('confessions', 1);
    }

    public function test_configured_confession_length_drives_the_form_and_server_validation(): void
    {
        config(['lavbe.limits.confession_body' => 5]);

        $this->get('/az/write')->assertOk()->assertSee('data-max="5"', false)->assertDontSee('maxlength="5"', false);

        $payload = $this->payload('submission-key-'.str_repeat('x', 32));
        $payload['body'] = '123456';
        $this->post('/az/write', $payload)->assertSessionHasErrors('body');
        $this->assertDatabaseCount('confessions', 0);
    }

    public function test_revision_text_cannot_be_updated_in_place(): void
    {
        $this->post('/az/write', $this->payload())->assertCreated();
        $revision = Confession::query()->firstOrFail()->revisions()->firstOrFail();

        try {
            $revision->body = 'changed';
            $revision->save();
            $this->fail('Revision text must be append-only.');
        } catch (\LogicException $exception) {
            $this->assertSame('Confession revision content is immutable.', $exception->getMessage());
        }
    }

    public function test_database_write_failure_returns_a_private_message_and_logs_no_submission_content(): void
    {
        $connection = DB::connection();
        $connection->beforeExecuting(function (string $query): void {
            if (str_starts_with(strtolower(ltrim($query)), 'insert into "confession_revisions"')
                || str_starts_with(strtolower(ltrim($query)), 'insert into `confession_revisions`')) {
                throw new QueryException(
                    DB::getDefaultConnection(),
                    $query,
                    [],
                    new \PDOException('synthetic database rejection', 23000),
                );
            }
        });
        Log::shouldReceive('warning')->once()->withArgs(fn (string $message, array $context): bool => $message === 'Guest confession submission could not be stored.'
            && array_keys($context) === ['sqlstate']
            && is_string($context['sqlstate'])
        );
        Log::shouldNotReceive('error');
        $payload = $this->payload();
        $payload['body'] = 'synthetic private text that must never enter an error log';

        $this->post('/az/write', $payload)
            ->assertStatus(503)
            ->assertSee('Göndərişi hazırda saxlamaq mümkün olmadı.')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->assertDatabaseCount('confessions', 0);
    }

    private function payload(string $key = 'submission-key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'): array
    {
        return [
            'idempotency_key' => $key,
            'title' => 'Synthetic title',
            'body' => 'A synthetic private confession',
            'category_key' => 'family',
            'response_expectation' => 'share',
            'age_confirmed' => '1',
        ];
    }

    private function statusForSession(string $publicId, string $sessionId): Response
    {
        $session = new Store('lavbe-session', new ArraySessionHandler(120));
        $session->setId($sessionId);
        $session->start();
        $request = Request::create('/az/my/'.$publicId);
        $request->setLaravelSession($session);

        return app(GuestConfessionController::class)->status($request, 'az', $publicId);
    }
}
