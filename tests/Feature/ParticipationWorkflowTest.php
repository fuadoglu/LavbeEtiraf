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
        $ownerCookie = collect($recovered->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'lavbe_comment_'.$comment->public_id);
        $this->assertNotNull($ownerCookie);
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
