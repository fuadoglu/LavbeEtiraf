<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Models\ParticipationOutbox;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\FollowedConfessionActivity;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_batch_size_limits_pending_delivery(): void
    {
        Notification::fake();
        config(['delivery.notifications.email_batch_size' => 1]);
        $user = User::factory()->create(['email_notifications' => true]);
        foreach (['first', 'second'] as $suffix) {
            UserNotification::query()->create([
                'user_id' => $user->id,
                'event_key' => hash('sha256', 'synthetic-config-batch-'.$suffix),
                'event_type' => 'continuation',
                'in_app' => true,
                'created_at' => now(),
            ]);
        }

        $this->artisan('lavbe:deliver-notifications')->assertSuccessful();
        $this->assertSame(1, UserNotification::query()->whereNotNull('email_sent_at')->count());
        $this->artisan('lavbe:deliver-notifications')->assertSuccessful();
        $this->assertSame(2, UserNotification::query()->whereNotNull('email_sent_at')->count());
        Notification::assertSentToTimes($user, FollowedConfessionActivity::class, 2);
    }

    public function test_registration_requires_email_verification_and_private_account_is_no_store(): void
    {
        Notification::fake();
        $this->get('/az/account/register')->assertOk();
        $guestSessionId = session()->getId();
        $guestConfession = Confession::query()->create([
            'locale' => 'az', 'status' => 'unpublished', 'title' => '', 'body' => '', 'public_id' => (string) Str::uuid(),
            'author_alias' => 'Lavbe #C1D2E3F4', 'lock_version' => 1, 'visibility_version' => 1,
        ]);
        $guestConfession->revisions()->create(['revision' => 1, 'title' => 'Synthetic session-owned title', 'body' => 'Synthetic session-owned text', 'category_key' => 'family', 'status' => 'pending']);
        $guestConfession->ownership()->create([
            'session_token_hash' => hash_hmac('sha256', $guestSessionId, (string) config('app.key')),
            'owner_token_hash' => str_repeat('c', 64), 'recovery_token_hash' => str_repeat('d', 64),
        ]);
        $this->post('/az/account/register', [
            'name' => 'Synthetic Account', 'email' => 'synthetic@example.test',
            'password' => 'synthetic-long-passphrase', 'password_confirmation' => 'synthetic-long-passphrase',
        ])->assertRedirect('/az/account/email/verify');
        $user = User::query()->where('email', 'synthetic@example.test')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertFalse($user->email_notifications);
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get('/az/my')->assertRedirect('/az/account/email/verify');

        $signedUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(15), [
            'locale' => 'az', 'id' => $user->id, 'hash' => sha1($user->getEmailForVerification()),
        ]);
        $this->get($signedUrl)->assertRedirect('/az/my');
        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $response = $this->get('/az/my')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_reset_is_neutral_single_use_and_login_uses_the_new_password(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'owner@example.test', 'email_verified_at' => now()]);
        $this->post('/az/account/password/forgot', ['email' => $user->email])->assertRedirect();
        Notification::assertSentTo($user, ResetPassword::class);
        $token = app('auth.password.broker')->createToken($user);
        $this->post('/az/account/password/reset', [
            'email' => $user->email, 'token' => $token, 'password' => 'updated-long-passphrase',
            'password_confirmation' => 'updated-long-passphrase',
        ])->assertRedirect('/az/account/login');
        $this->post('/az/account/login', ['email' => $user->email, 'password' => 'updated-long-passphrase'])
            ->assertRedirect('/az/my');
        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_confession_and_comment_claims_require_exact_owner_proof_and_isolate_accounts(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);
        [$confession, $ownerToken] = $this->ownedConfession(public: true);
        $this->withCookie('lavbe_owner_'.$confession->public_id, $ownerToken)->actingAs($user)
            ->post('/az/my/'.$confession->public_id.'/claim')->assertRedirect('/az/my');
        $confessionOwnership = $confession->ownership()->firstOrFail();
        $this->assertSame($user->id, $confessionOwnership->user_id);
        $this->assertNull($confessionOwnership->session_token_hash);
        $this->assertNotSame(hash_hmac('sha256', $ownerToken, (string) config('app.key')), $confessionOwnership->owner_token_hash);
        $this->assertNotSame(hash_hmac('sha256', 'synthetic-recovery-code', (string) config('app.key')), $confessionOwnership->recovery_token_hash);

        [$comment, $commentOwnerToken] = $this->ownedComment($confession);
        $this->withCookie('lavbe_comment_'.$comment->public_id, $commentOwnerToken)->actingAs($user)
            ->post('/az/my/comments/'.$comment->public_id.'/claim')->assertRedirect('/az/my');
        $this->assertSame($user->id, $comment->ownership()->value('user_id'));
        $this->actingAs($other)->get('/az/my/'.$confession->public_id)->assertNotFound();
        $this->actingAs($other)->get('/az/my')->assertOk()->assertDontSee('Synthetic account-only confession')->assertDontSee('Synthetic account-only response');
        $this->actingAs($user)->get('/az/my/'.$confession->public_id)->assertOk()->assertSee('Synthetic account-only confession');
    }

    public function test_follow_preferences_and_neutral_outbox_notifications_are_private_and_idempotent(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'follower@example.test', 'email_verified_at' => now(), 'email_notifications' => true]);
        $quietUser = User::factory()->create(['email' => 'quiet@example.test', 'email_verified_at' => now()]);
        [$confession] = $this->ownedConfession(public: true);
        $this->actingAs($user)->post('/az/my/follows/'.$confession->public_id)->assertRedirect();
        DB::table('confession_follows')->insert(['user_id' => $quietUser->id, 'confession_id' => $confession->id, 'created_at' => now()]);
        $this->assertDatabaseCount('confession_follows', 2);
        $this->actingAs($user)->post('/az/my/follows/'.$confession->public_id)->assertRedirect();
        $this->assertDatabaseCount('confession_follows', 2);
        $event = ParticipationOutbox::query()->create([
            'event_key' => hash('sha256', 'synthetic-follow-event'), 'confession_id' => $confession->id,
            'event_type' => 'comment_activity', 'visibility_version' => $confession->visibility_version,
            'occurred_at' => now(),
        ]);
        $this->artisan('lavbe:deliver-notifications')->assertSuccessful();
        $this->artisan('lavbe:deliver-notifications')->assertSuccessful();
        $this->assertDatabaseCount('user_notifications', 2);
        $this->assertNotNull($event->fresh()->dispatched_at);
        $notification = UserNotification::query()->firstOrFail();
        $this->assertTrue($notification->in_app);
        $this->assertNotNull($notification->email_sent_at);
        $this->assertNull($notification->email_claimed_at);
        $stored = json_encode($notification->getAttributes(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('Synthetic account-only confession', $stored);
        $this->assertStringNotContainsString($confession->public_id, $stored);
        Notification::assertSentTo($user, FollowedConfessionActivity::class);
        Notification::assertNotSentTo($quietUser, FollowedConfessionActivity::class);
        $mail = (new FollowedConfessionActivity)->toMail($user);
        $mailText = implode(' ', array_merge([$mail->subject, $mail->greeting], $mail->introLines, $mail->outroLines));
        $this->assertStringNotContainsString('Synthetic account-only confession', $mailText);
        $this->assertStringNotContainsString($confession->public_id, $mail->actionUrl);
        $this->actingAs($user)->put('/az/my/preferences', ['in_app_notifications' => '0', 'email_notifications' => '0'])->assertRedirect();
        $this->assertFalse($user->fresh()->email_notifications);
        $this->actingAs($user)->get('/az/my')->assertOk()->assertDontSee('Google Analytics')->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->actingAs($user)->delete('/az/my/follows/'.$confession->public_id)->assertRedirect();
        $this->assertDatabaseCount('confession_follows', 1);
    }

    private function ownedConfession(bool $public = false): array
    {
        $confession = Confession::query()->create([
            'locale' => 'az', 'status' => $public ? 'public' : 'unpublished', 'title' => $public ? 'Synthetic account-only confession' : '',
            'body' => $public ? 'Synthetic public account body' : '', 'excerpt' => $public ? 'Synthetic public account body' : null,
            'public_id' => (string) Str::uuid(), 'author_alias' => 'Lavbe #A1B2C3D4', 'lock_version' => 1, 'visibility_version' => 1,
            'published_at' => $public ? now() : null, 'public_updated_at' => $public ? now() : null,
        ]);
        if ($public) {
            $revision = $confession->revisions()->create(['revision' => 1, 'title' => 'Synthetic account-only confession', 'body' => 'Synthetic public account body', 'category_key' => 'family', 'status' => 'approved']);
            $confession->forceFill(['public_revision_id' => $revision->id])->save();
        }
        $ownerToken = 'synthetic-confession-owner-token';
        $confession->ownership()->create([
            'session_token_hash' => hash_hmac('sha256', 'synthetic-guest-session', (string) config('app.key')),
            'owner_token_hash' => hash_hmac('sha256', $ownerToken, (string) config('app.key')),
            'recovery_token_hash' => hash_hmac('sha256', 'synthetic-recovery-code', (string) config('app.key')),
        ]);

        return [$confession, $ownerToken];
    }

    private function ownedComment(Confession $confession): array
    {
        $comment = $confession->comments()->create([
            'public_id' => (string) Str::uuid(), 'status' => 'unpublished', 'author_alias' => 'Lavbe #B1C2D3E4',
            'body' => '', 'lock_version' => 1, 'visibility_version' => 1,
        ]);
        $comment->revisions()->create(['revision' => 1, 'body' => 'Synthetic account-only response', 'status' => 'pending']);
        $ownerToken = 'synthetic-comment-owner-token';
        $comment->ownership()->create([
            'session_token_hash' => hash_hmac('sha256', 'synthetic-comment-session', (string) config('app.key')),
            'owner_token_hash' => hash_hmac('sha256', $ownerToken, (string) config('app.key')),
            'recovery_token_hash' => hash_hmac('sha256', 'synthetic-comment-recovery', (string) config('app.key')),
        ]);

        return [$comment, $ownerToken];
    }
}
