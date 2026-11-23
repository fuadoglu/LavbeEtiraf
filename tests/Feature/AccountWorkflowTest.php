<?php

namespace Tests\Feature;

use App\Http\Controllers\AccountAuthController;
use App\Models\Confession;
use App\Models\ParticipationOutbox;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\FollowedConfessionActivity;
use App\Support\Accounts\ParticipationNotificationDelivery;
use App\Support\Lavbe\AuthorAlias;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_preserves_guest_session_ownership_after_authentication_regenerates_the_session(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $request = Request::create('/az/account/login', 'POST', ['email' => $user->email, 'password' => 'password']);
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => Auth::user());
        $guestSessionId = $request->session()->getId();
        [$confession] = $this->ownedConfession();
        [$comment] = $this->ownedComment($confession);
        $guestHash = hash_hmac('sha256', $guestSessionId, (string) config('app.key'));
        $confession->ownership()->update(['session_token_hash' => $guestHash]);
        $comment->ownership()->update(['session_token_hash' => $guestHash]);
        $this->assertSame($guestHash, $confession->ownership()->first()->session_token_hash);
        $this->assertSame($guestHash, $comment->ownership()->first()->session_token_hash);

        $response = app(AccountAuthController::class)->login($request, 'az');
        $this->assertSame('/az/my', parse_url($response->getTargetUrl(), PHP_URL_PATH));

        $updatedHash = $confession->ownership->fresh()->session_token_hash;
        $this->assertNotSame($guestHash, $updatedHash, 'Guest ownership must move to the new session.');
        $this->assertSame($updatedHash, $comment->ownership->fresh()->session_token_hash);
    }

    public function test_unfollow_removes_only_the_requested_confession(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create();
        [$first] = $this->ownedConfession(public: true);
        [$second] = $this->ownedConfession(public: true);
        foreach ([[$user, $first], [$user, $second], [$otherUser, $first]] as [$follower, $confession]) {
            DB::table('confession_follows')->insert(['user_id' => $follower->id, 'confession_id' => $confession->id, 'created_at' => now()]);
        }

        $this->actingAs($user)->delete('/az/my/follows/'.$first->public_id)->assertRedirect();

        $this->assertDatabaseMissing('confession_follows', ['user_id' => $user->id, 'confession_id' => $first->id]);
        $this->assertDatabaseHas('confession_follows', ['user_id' => $user->id, 'confession_id' => $second->id]);
        $this->assertDatabaseHas('confession_follows', ['user_id' => $otherUser->id, 'confession_id' => $first->id]);
    }

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

    public function test_failed_email_uses_backoff_without_starving_later_notifications(): void
    {
        $user = User::factory()->create(['email_notifications' => true]);
        $items = collect(['poison', 'later'])->map(fn (string $key) => UserNotification::query()->create([
            'user_id' => $user->id, 'event_key' => hash('sha256', 'synthetic-retry-'.$key),
            'event_type' => 'continuation', 'in_app' => true, 'created_at' => now(),
        ]));
        config(['delivery.notifications.email_batch_size' => 1, 'delivery.notifications.email_retry_initial_seconds' => 60]);
        Notification::shouldReceive('sendNow')->times(3)->andReturnUsing(static function (): void {
            static $calls = 0;
            if (++$calls === 1) {
                throw new \RuntimeException('Synthetic provider failure');
            }
        });

        $this->artisan('lavbe:deliver-notifications')->assertSuccessful();
        $this->assertNotNull($items[0]->fresh()->email_next_attempt_at);
        $this->assertNull($items[0]->fresh()->email_failed_at);
        $this->artisan('lavbe:deliver-notifications')->assertSuccessful();
        $this->assertNotNull($items[1]->fresh()->email_sent_at);
        $this->assertNull($items[0]->fresh()->email_sent_at);

        $this->travel(60)->seconds();
        $this->artisan('lavbe:deliver-notifications')->assertSuccessful();
        $this->assertNotNull($items[0]->fresh()->email_sent_at);
        $this->assertSame(2, $items[0]->fresh()->email_attempts);
    }

    public function test_follow_link_uses_registered_pt_br_url_segment(): void
    {
        $user = User::factory()->create();
        [$confession] = $this->ownedConfession(public: true);
        $confession->forceFill(['locale' => 'pt-BR'])->save();
        DB::table('confession_follows')->insert(['user_id' => $user->id, 'confession_id' => $confession->id, 'created_at' => now()]);

        $this->actingAs($user)->get('/az/my')->assertOk()
            ->assertSee('/pt-br/confession/'.$confession->public_id, false)
            ->assertDontSee('/pt-BR/confession/'.$confession->public_id, false);
        $this->get('/pt-br/confession/'.$confession->public_id)->assertOk();
    }

    public function test_account_validation_errors_are_linked_to_their_fields(): void
    {
        config(['session.driver' => 'array']);
        $this->from('/en/account/register')->post('/en/account/register', [
            'name' => '',
            'email' => 'invalid',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['name', 'email', 'password']);

        $this->withViewErrors(['email' => 'A valid email address is required.'])
            ->view('account.register')
            ->assertSee('id="email"', false)
            ->assertSee('for="email"', false)
            ->assertSee('aria-describedby="email-description"', false)
            ->assertSee('id="email-description"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('A valid email address is required.');
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

    public function test_follower_notifications_resume_from_a_bounded_outbox_cursor(): void
    {
        config([
            'delivery.notifications.batch_size' => 1,
            'delivery.notifications.batch_max' => 1,
            'delivery.notifications.follower_batch_size' => 2,
        ]);
        [$confession] = $this->ownedConfession(public: true);
        $followers = User::factory()->count(5)->create(['in_app_notifications' => true, 'email_notifications' => false])->sortBy('id')->values();
        foreach ($followers as $follower) {
            DB::table('confession_follows')->insert([
                'user_id' => $follower->id,
                'confession_id' => $confession->id,
                'created_at' => now(),
            ]);
        }
        $event = ParticipationOutbox::query()->create([
            'event_key' => hash('sha256', 'synthetic-batched-follow-event'),
            'confession_id' => $confession->id,
            'event_type' => 'comment_activity',
            'visibility_version' => $confession->visibility_version,
            'occurred_at' => now(),
        ]);
        $delivery = app(ParticipationNotificationDelivery::class);

        $this->assertSame(2, $delivery->dispatch()['created']);
        $this->assertNull($event->fresh()->dispatched_at);
        $this->assertSame($followers[1]->id, $event->fresh()->follower_cursor_id);
        $this->assertSame(2, UserNotification::query()->count());

        $this->assertSame(2, $delivery->dispatch()['created']);
        $this->assertNull($event->fresh()->dispatched_at);
        $this->assertSame($followers[3]->id, $event->fresh()->follower_cursor_id);
        $this->assertSame(1, $delivery->dispatch()['created']);
        $this->assertNotNull($event->fresh()->dispatched_at);
        $this->assertSame(5, UserNotification::query()->count());
        $this->assertSame(5, UserNotification::query()->distinct('user_id')->count('user_id'));
        $this->assertSame(0, $delivery->dispatch()['created']);
        $this->assertSame(5, UserNotification::query()->count());
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

    public function test_brazilian_portuguese_notification_uses_content_code_and_url_segment_separately(): void
    {
        $user = User::factory()->create(['preferred_locale' => 'pt-br', 'email_verified_at' => now()]);
        $mail = (new FollowedConfessionActivity)->toMail($user);

        $this->assertSame(__('account.notification_subject', [], 'pt-BR'), $mail->subject);
        $this->assertStringContainsString('/pt-br/my', parse_url($mail->actionUrl, PHP_URL_PATH));
    }

    private function ownedConfession(bool $public = false): array
    {
        $confession = Confession::query()->create([
            'locale' => 'az', 'status' => $public ? 'public' : 'unpublished', 'title' => $public ? 'Synthetic account-only confession' : '',
            'body' => $public ? 'Synthetic public account body' : '', 'excerpt' => $public ? 'Synthetic public account body' : null,
            'public_id' => (string) Str::uuid(), 'author_alias' => AuthorAlias::generate(), 'lock_version' => 1, 'visibility_version' => 1,
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
