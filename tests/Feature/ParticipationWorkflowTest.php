<?php

namespace Tests\Feature;

use App\Models\CommentResponse;
use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\ModeratorGrant;
use App\Models\ModeratorMfa;
use App\Models\User;
use App\Support\Moderation\ModerationService;
use App\Support\Moderation\ModeratorMfaService;
use App\Support\Participation\ParticipationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ParticipationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_is_private_until_approved_and_owner_can_manage_it(): void
    {
        $confession = $this->publishedConfession();
        $payload = ['body' => 'A synthetic private response', 'idempotency_key' => str_repeat('a', 32)];
        $submitted = $this->post('/en/confession/'.$confession->public_id.'/comments', $payload)->assertCreated();
        preg_match('/<code dir="ltr">([A-Za-z0-9_-]{43})<\/code>/', $submitted->getContent(), $match);
        $recoveryCode = $match[1] ?? '';
        $comment = ConfessionComment::query()->with('revisions')->firstOrFail();
        $this->assertSame('unpublished', $comment->status);
        $this->assertSame('', $comment->body);
        $this->assertSame('pending', $comment->revisions->first()->status);
        $this->get('/en/confession/'.$confession->public_id)->assertOk()->assertDontSee('A synthetic private response');
        $recovered = $this->post('/en/comment/'.$comment->public_id.'/recover', ['recovery_code' => $recoveryCode, 'remember_device' => '1'])->assertRedirect('/en/comment/'.$comment->public_id);
        $ownerCookie = collect($recovered->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'lavbe_device');
        $this->assertNotNull($ownerCookie);
        $this->assertNull(collect($recovered->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'lavbe_comment_'.$comment->public_id));
        $this->withUnencryptedCookie($ownerCookie->getName(), $ownerCookie->getValue());
        $private = $this->get('/en/comment/'.$comment->public_id)->assertOk()->assertSee('A synthetic private response');
        $this->assertStringContainsString('no-store', (string) $private->headers->get('Cache-Control'));
    }

    public function test_comment_submission_replay_with_the_same_session_and_key_creates_one_record(): void
    {
        $confession = $this->publishedConfession();
        $request = Request::create('/en/confession/'.$confession->public_id.'/comments', 'POST');
        $session = new Store('comment-idempotency-test', new ArraySessionHandler(120));
        $session->setId('stable-comment-author-session');
        $session->start();
        $request->setLaravelSession($session);

        $first = app(ParticipationService::class)->submitComment($request, $confession->public_id, 'Synthetic idempotent response', str_repeat('a', 32));
        $replay = app(ParticipationService::class)->submitComment($request, $confession->public_id, 'Synthetic idempotent response', str_repeat('a', 32));

        $this->assertSame($first['comment']->id, $replay['comment']->id);
        $this->assertSame('', $replay['recovery_code']);
        $this->assertDatabaseCount('confession_comments', 1);
        $this->assertDatabaseCount('confession_comment_revisions', 1);
    }

    public function test_remembered_device_owns_comments_without_per_comment_cookies_and_forget_preserves_recovery(): void
    {
        $confession = $this->publishedConfession();
        $deviceToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $confession->ownership()->update(['device_token_hash' => hash_hmac('sha256', $deviceToken, (string) config('app.key'))]);
        $request = Request::create('/en/confession/'.$confession->public_id.'/comments', 'POST');
        $session = new Store('remembered-comment-device-test', new ArraySessionHandler(120));
        $session->setId('stable-remembered-comment-device-session');
        $session->start();
        $request->setLaravelSession($session);

        for ($index = 0; $index < 200; $index++) {
            app(ParticipationService::class)->submitComment(
                $request,
                $confession->public_id,
                'Synthetic response '.$index,
                hash('sha256', 'synthetic-idempotency-'.$index),
                null,
                $deviceToken,
            );
        }
        $this->assertDatabaseCount('confession_comments', 200);
        $this->assertSame(200, DB::table('comment_ownerships')->where('device_token_hash', hash_hmac('sha256', $deviceToken, (string) config('app.key')))->count());

        $this->withCookie('lavbe_device', $deviceToken)->post('/en/my/device/forget')->assertRedirect('/en/my/device');
        $this->assertSame(0, DB::table('comment_ownerships')->where('device_token_hash', hash_hmac('sha256', $deviceToken, (string) config('app.key')))->count());
        $this->assertNull($confession->ownership()->value('device_token_hash'));
        $this->assertSame(200, DB::table('comment_ownerships')->whereNotNull('owner_token_hash')->count());
    }

    public function test_remembered_comment_endpoint_sets_one_shared_device_cookie(): void
    {
        $confession = $this->publishedConfession();
        $response = $this->post('/en/confession/'.$confession->public_id.'/comments', [
            'body' => 'A synthetic remembered response', 'idempotency_key' => str_repeat('e', 32), 'remember_device' => '1',
        ])->assertCreated();
        $this->assertNotNull(collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'lavbe_device'));
        $this->assertCount(0, collect($response->headers->getCookies())->filter(fn ($cookie) => str_starts_with($cookie->getName(), 'lavbe_comment_')));
        $this->assertNotNull(ConfessionComment::query()->firstOrFail()->ownership->device_token_hash);
    }

    public function test_forgetting_device_revokes_comment_access_but_recovery_code_still_works(): void
    {
        $confession = $this->publishedConfession();
        $deviceToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $submitted = $this->withCookie('lavbe_device', $deviceToken)->post('/en/confession/'.$confession->public_id.'/comments', [
            'body' => 'A synthetic remembered response', 'idempotency_key' => str_repeat('f', 32), 'remember_device' => '1',
        ])->assertCreated();
        preg_match('/<code dir="ltr">([A-Za-z0-9_-]{43})<\/code>/', $submitted->getContent(), $recoveryMatch);
        $recoveryCode = $recoveryMatch[1] ?? '';
        $comment = ConfessionComment::query()->with('ownership')->firstOrFail();
        $this->assertNotNull(collect($submitted->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'lavbe_device'));
        $recoveryHash = $comment->ownership->recovery_token_hash;

        $this->withCookie('lavbe_device', $deviceToken)
            ->post('/en/my/device/forget')->assertRedirect('/en/my/device');
        $comment->refresh()->load('ownership');
        $this->assertNull($comment->ownership->device_token_hash);
        $this->assertSame($recoveryHash, $comment->ownership->recovery_token_hash);
        $this->withCookie('lavbe_device', $deviceToken)->get('/en/comment/'.$comment->public_id)->assertNotFound();

        $recoveryDeviceToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->withCookie('lavbe_device', $recoveryDeviceToken)->post('/en/comment/'.$comment->public_id.'/recover', ['recovery_code' => $recoveryCode, 'remember_device' => '1'])
            ->assertRedirect('/en/comment/'.$comment->public_id);
        $this->withCookie('lavbe_device', $recoveryDeviceToken)->get('/en/comment/'.$comment->public_id)->assertOk()->assertSee('A synthetic remembered response');
    }

    public function test_forgetting_device_revokes_a_legacy_per_comment_cookie_and_clears_it(): void
    {
        $confession = $this->publishedConfession();
        $this->post('/en/confession/'.$confession->public_id.'/comments', [
            'body' => 'A synthetic legacy-cookie response', 'idempotency_key' => str_repeat('7', 32),
        ])->assertCreated();
        $comment = ConfessionComment::query()->firstOrFail()->load('ownership');
        $legacyToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $comment->ownership->forceFill([
            'session_token_hash' => hash_hmac('sha256', 'unrelated-session', (string) config('app.key')),
            'owner_token_hash' => hash_hmac('sha256', $legacyToken, (string) config('app.key')),
        ])->save();

        $this->withCookie('lavbe_comment_'.$comment->public_id, $legacyToken)
            ->get('/en/comment/'.$comment->public_id)->assertOk();
        $this->withCookie('lavbe_comment_'.$comment->public_id, $legacyToken)
            ->post('/en/my/device/forget')->assertRedirect('/en/my/device')
            ->assertCookieExpired('lavbe_comment_'.$comment->public_id);
        $this->withCookie('lavbe_comment_'.$comment->public_id, $legacyToken)
            ->get('/en/comment/'.$comment->public_id)->assertNotFound();
    }

    public function test_reply_validation_opens_only_the_target_reply_form_and_preserves_its_text(): void
    {
        $confession = $this->publishedConfession();
        $first = $this->approvedComment($confession);
        $target = $this->approvedComment($confession);
        $body = str_repeat('x', (int) config('lavbe.limits.comment_body') + 1);
        $path = '/en/confession/'.$confession->public_id;

        $this->from($path)->post($path.'/comments', [
            'body' => $body,
            'comment_scope' => 'reply',
            'parent_public_id' => $target->public_id,
            'idempotency_key' => str_repeat('8', 32),
        ])->assertRedirect($path)->assertSessionHasErrors('body');
        $page = $this->get($path)->assertOk();
        $this->assertStringContainsString($body, $page->getContent());
        $page
            ->assertSee('id="reply-body-'.$target->id.'-description"', false)
            ->assertSee('id="reply-body-'.$target->id.'"', false)
            ->assertDontSee('id="reply-body-'.$first->id.'-description"', false);
        preg_match_all('/<details class="comment-reply-composer"\s+open(?:\s|>)/', $page->getContent(), $openDetails);
        $this->assertCount(1, $openDetails[0]);
    }

    public function test_closed_reviews_reject_new_comments_but_support_is_idempotent_and_reversible(): void
    {
        $confession = $this->publishedConfession();
        $this->post('/en/my/'.$confession->public_id.'/reviews', ['open' => '0', 'idempotency_key' => str_repeat('b', 32)])
            ->assertRedirect('/en/my/'.$confession->public_id);
        $this->post('/en/confession/'.$confession->public_id.'/comments', ['body' => 'Should be rejected', 'idempotency_key' => str_repeat('c', 32)])
            ->assertStatus(409);

        $supportRequest = Request::create('/en/confession/'.$confession->public_id.'/support', 'POST');
        $session = new Store('support-test', new ArraySessionHandler(120));
        $session->setId('stable-support-session');
        $session->start();
        $supportRequest->setLaravelSession($session);
        app(ParticipationService::class)->setSupport($supportRequest, $confession->public_id, true, str_repeat('d', 32));
        app(ParticipationService::class)->setSupport($supportRequest, $confession->public_id, true, str_repeat('d', 32));
        $this->assertSame(1, (int) $confession->fresh()->support_count);
        app(ParticipationService::class)->setSupport($supportRequest, $confession->public_id, false, str_repeat('e', 32));
        $this->assertSame(0, (int) $confession->fresh()->support_count);
    }

    public function test_author_can_submit_continuation_and_response_and_moderator_controls_visibility(): void
    {
        config(['moderation.mfa_key' => base64_encode(str_repeat('k', 32))]);
        $confession = $this->publishedConfession();
        $this->post('/en/confession/'.$confession->public_id.'/continuations', [
            'body' => 'A synthetic continuation', 'confirm_continuation' => '1', 'idempotency_key' => str_repeat('f', 32),
        ])->assertRedirect('/en/my/'.$confession->public_id);

        $comment = $this->approvedComment($confession);
        $this->post('/en/confession/'.$confession->public_id.'/comments/'.$comment->public_id.'/response', [
            'body' => 'A synthetic author response', 'idempotency_key' => str_repeat('1', 32),
        ])->assertRedirect('/en/confession/'.$confession->public_id);

        $moderator = User::factory()->create();
        ModeratorGrant::query()->create(['user_id' => $moderator->id, 'role' => 'moderator', 'locale' => 'en', 'reason' => 'synthetic test']);
        ModeratorMfa::query()->create([
            'user_id' => $moderator->id,
            'secret_ciphertext' => app(ModeratorMfaService::class)->encryptSecret(app(ModeratorMfaService::class)->newSecret()),
            'confirmed_at' => now(),
            'last_counter' => intdiv(time(), 30) - 1,
        ]);
        $this->actingAs($moderator)->withSession(['moderator.mfa_verified_at' => time(), 'moderator.mfa_user_id' => (int) $moderator->id]);
        $pendingContinuation = $confession->continuations()->with('revisions')->firstOrFail();
        $expectedParentLockVersion = $confession->fresh()->lock_version;
        $this->post('/en/moderation/participation/continuation/'.$pendingContinuation->id.'/decision', [
            'revision_id' => $pendingContinuation->revisions->first()->id,
            'expected_lock_version' => $pendingContinuation->lock_version,
            'expected_parent_lock_version' => $expectedParentLockVersion,
            'decision' => 'approve', 'reason_code' => 'acceptable', 'idempotency_key' => str_repeat('2', 32),
        ])->assertRedirect('/en/moderation/participation');
        app(ModerationService::class)->decideParticipation(
            $moderator, 'continuation', $pendingContinuation->id, $pendingContinuation->revisions->first()->id,
            $pendingContinuation->lock_version, $expectedParentLockVersion, 'approve', 'acceptable', str_repeat('2', 32), (string) Str::uuid(),
        );
        $this->assertSame(1, DB::table('participation_outbox')->where('event_type', 'publish_continuation')->count());

        $response = CommentResponse::query()->with('revisions')->firstOrFail();
        $responseRevision = $response->revisions->first();
        $parent = $confession->fresh();
        app(ModerationService::class)->decideParticipation(
            $moderator, 'response', $response->id, $responseRevision->id, $response->lock_version,
            $parent->lock_version, 'approve', 'acceptable', str_repeat('3', 32), (string) Str::uuid(),
        );
        $parent = $confession->fresh();
        app(ModerationService::class)->decideParticipation(
            $moderator, 'comment', $comment->id, $comment->public_revision_id, $comment->lock_version,
            $parent->lock_version, 'hide', 'privacy', str_repeat('4', 32), (string) Str::uuid(),
        );
        $this->get('/en/confession/'.$confession->public_id)->assertOk()->assertSee('A synthetic continuation');
        $this->assertDatabaseMissing('participation_outbox', ['event_type' => 'publish_comment']);
        $this->assertSame('hidden', $response->fresh()->status);
        $this->get('/en/confession/'.$confession->public_id)->assertDontSee('Synthetic comment')->assertDontSee('A synthetic author response');
        $this->assertDatabaseCount('participation_outbox', 3);
    }

    private function publishedConfession(): Confession
    {
        $confession = Confession::query()->create([
            'locale' => 'en', 'category_key' => 'family', 'status' => 'public', 'title' => 'Synthetic story',
            'body' => 'A synthetic public story', 'excerpt' => 'A synthetic public story', 'public_id' => (string) Str::uuid(),
            'author_alias' => 'Lavbe #A1B2C3D4', 'published_at' => now(), 'public_updated_at' => now(), 'ever_public' => true,
        ]);
        $revision = $confession->revisions()->create(['revision' => 1, 'title' => 'Synthetic story', 'body' => 'A synthetic public story', 'category_key' => 'family', 'status' => 'approved']);
        $confession->forceFill(['public_revision_id' => $revision->id])->save();
        $ownerToken = str_repeat('synthetic-owner-', 4);
        $confession->ownership()->create([
            'owner_token_hash' => hash_hmac('sha256', $ownerToken, (string) config('app.key')),
            'recovery_token_hash' => hash_hmac('sha256', 'synthetic-recovery-code-'.str_repeat('r', 20), (string) config('app.key')),
        ]);
        $this->withCookie('lavbe_owner_'.$confession->public_id, $ownerToken);

        return $confession->fresh();
    }

    private function approvedComment(Confession $confession): ConfessionComment
    {
        $comment = $confession->comments()->create(['public_id' => (string) Str::uuid(), 'status' => 'public', 'author_alias' => 'Lavbe #AABBCCDD', 'body' => 'Synthetic comment', 'published_at' => now()]);
        $revision = $comment->revisions()->create(['revision' => 1, 'body' => 'Synthetic comment', 'status' => 'approved']);
        $comment->forceFill(['public_revision_id' => $revision->id])->save();

        return $comment->fresh();
    }
}
