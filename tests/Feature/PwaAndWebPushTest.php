<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Models\ParticipationOutbox;
use App\Models\PushSubscription;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WebPushDelivery;
use App\Support\Push\WebPushGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PwaAndWebPushTest extends TestCase
{
    use RefreshDatabase;

    public function test_localized_manifest_offline_document_and_scoped_worker_are_available(): void
    {
        $response = $this->get('/ja/manifest.webmanifest')->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json; charset=UTF-8')
            ->assertHeader('Cache-Control', 'max-age=300, public');
        $manifest = $response->json();

        $this->assertContains('public', array_map('trim', explode(',', $response->headers->get('Cache-Control'))));
        $this->assertContains('max-age=300', array_map('trim', explode(',', $response->headers->get('Cache-Control'))));

        $this->assertSame('/ja/', $manifest['start_url']);
        $this->assertSame('ja', $manifest['lang']);
        $this->assertContains('maskable', array_column($manifest['icons'], 'purpose'));

        $this->get('/ja/offline')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('オフライン', false)->assertSee('保存した下書き', false);

        $workerResponse = $this->get('/service-worker.js')->assertOk()->assertHeader('Service-Worker-Allowed', '/');
        $workerCache = array_map('trim', explode(',', $workerResponse->headers->get('Cache-Control')));
        $this->assertContains('no-cache', $workerCache);
        $this->assertContains('must-revalidate', $workerCache);
        $worker = $workerResponse->getContent();
        $this->assertStringContainsString('lavbe-offline-', $worker);
        $this->assertStringContainsString('"pt-br"', $worker);
        $this->assertStringNotContainsString('__LAVBE_LOCALES__', $worker);
        $this->assertStringNotContainsString('caches.put(request', $worker);
    }

    public function test_opted_in_push_subscription_is_encrypted_owned_and_can_be_disabled(): void
    {
        $this->configureVapid();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $payload = $this->subscriptionPayload();

        $this->actingAs($user)->postJson('/en/my/push-subscriptions', $payload)->assertCreated()->assertJson(['status' => 'subscribed']);
        $subscription = PushSubscription::query()->firstOrFail();
        $this->assertSame($payload['endpoint'], $subscription->endpoint);
        $this->assertNotSame($payload['endpoint'], DB::table('push_subscriptions')->value('endpoint'));
        $this->assertSame($user->id, $subscription->user_id);

        $this->actingAs($user)->postJson('/en/my/push-subscriptions/status', ['endpoint' => $payload['endpoint']])
            ->assertOk()->assertJson(['active' => true]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($otherUser)->postJson('/en/my/push-subscriptions/status', ['endpoint' => $payload['endpoint']])
            ->assertOk()->assertJson(['active' => false]);
        $this->actingAs($otherUser)->deleteJson('/en/my/push-subscriptions', ['endpoint' => $payload['endpoint']])->assertNoContent();
        $this->assertTrue($subscription->fresh()->active);
        $this->actingAs($user)->deleteJson('/en/my/push-subscriptions', ['endpoint' => $payload['endpoint']])->assertNoContent();
        $this->assertFalse($subscription->fresh()->active);
    }

    public function test_push_subscription_rejects_local_or_untrusted_endpoints(): void
    {
        $this->configureVapid();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $payload = $this->subscriptionPayload();
        $payload['endpoint'] = 'https://127.0.0.1/private';

        $this->actingAs($user)->postJson('/en/my/push-subscriptions', $payload)->assertStatus(422);
        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_logout_disables_the_account_devices_push_subscription(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $subscription = $this->pushSubscription($user);

        $this->actingAs($user)->post('/en/account/logout')->assertRedirect('/en/');

        $this->assertFalse($subscription->fresh()->active);
    }

    public function test_push_only_followers_receive_a_neutral_idempotent_outbox_delivery(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'preferred_locale' => 'en', 'in_app_notifications' => false, 'email_notifications' => false]);
        $confession = Confession::factory()->create(['locale' => 'en', 'visibility_version' => 2]);
        DB::table('confession_follows')->insert(['user_id' => $user->id, 'confession_id' => $confession->id, 'created_at' => now()]);
        $this->pushSubscription($user);
        $this->outbox($confession);

        $this->artisan('lavbe:deliver-notifications')->assertSuccessful();
        $this->artisan('lavbe:deliver-notifications')->assertSuccessful();

        $this->assertDatabaseCount('user_notifications', 1);
        $notification = UserNotification::query()->firstOrFail();
        $this->assertFalse($notification->in_app);
        $this->assertDatabaseHas('web_push_deliveries', ['user_notification_id' => $notification->id, 'sent_at' => null]);
        $this->assertDatabaseCount('web_push_deliveries', 1);
    }

    public function test_dispatcher_sends_only_neutral_localized_payload_and_retries_transient_status(): void
    {
        $this->configureVapid();
        $user = User::factory()->create(['email_verified_at' => now(), 'preferred_locale' => 'az']);
        $subscription = $this->pushSubscription($user);
        $notification = UserNotification::query()->create([
            'user_id' => $user->id, 'event_key' => hash('sha256', (string) Str::uuid()), 'event_type' => 'comment_activity',
            'in_app' => false, 'created_at' => now(),
        ]);
        $delivery = WebPushDelivery::query()->create([
            'user_notification_id' => $notification->id, 'push_subscription_id' => $subscription->id,
        ]);
        $gateway = new RecordingWebPushGateway([
            $delivery->id => ['success' => false, 'expired' => false, 'status' => 503],
        ]);
        $this->app->instance(WebPushGateway::class, $gateway);

        $this->artisan('lavbe:deliver-web-push')->assertSuccessful();
        $this->assertDatabaseHas('web_push_deliveries', [
            'id' => $delivery->id, 'attempts' => 1, 'last_error_code' => 'http_503', 'sent_at' => null,
        ]);
        $this->assertSame(['title' => 'Lavbe', 'body' => 'Hesabınızda yeni fəaliyyət var.', 'locale' => 'az'], $gateway->sent[0]['payload']);

        $delivery->forceFill(['next_attempt_at' => now()->subSecond()])->save();
        $gateway->results[$delivery->id] = ['success' => true, 'expired' => false, 'status' => 201];
        $this->artisan('lavbe:deliver-web-push')->assertSuccessful();
        $this->assertNotNull($delivery->fresh()->sent_at);
        $this->assertSame(2, $delivery->fresh()->attempts);
        $this->assertSame(2, count($gateway->sent));
    }

    private function configureVapid(): void
    {
        $encode = fn (int $bytes): string => rtrim(strtr(base64_encode(str_repeat("\x01", $bytes)), '+/', '-_'), '=');
        config([
            'services.webpush.subject' => 'mailto:push@example.test',
            'services.webpush.public_key' => $encode(65),
            'services.webpush.private_key' => $encode(32),
        ]);
    }

    private function subscriptionPayload(): array
    {
        $encode = fn (int $bytes): string => rtrim(strtr(base64_encode(str_repeat("\x01", $bytes)), '+/', '-_'), '=');

        return [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/synthetic-endpoint-token',
            'keys' => ['p256dh' => $encode(65), 'auth' => $encode(16)],
        ];
    }

    private function pushSubscription(User $user): PushSubscription
    {
        $payload = $this->subscriptionPayload();

        return PushSubscription::query()->create([
            'user_id' => $user->id,
            'endpoint_hash' => hash_hmac('sha256', $payload['endpoint'], (string) config('app.key')),
            'endpoint' => $payload['endpoint'],
            'p256dh' => $payload['keys']['p256dh'],
            'auth' => $payload['keys']['auth'],
            'active' => true,
            'activated_at' => now()->subMinute(),
            'last_used_at' => now(),
        ]);
    }

    private function outbox(Confession $confession): ParticipationOutbox
    {
        return ParticipationOutbox::query()->create([
            'event_key' => hash('sha256', (string) Str::uuid()),
            'confession_id' => $confession->id,
            'event_type' => 'publish_comment',
            'visibility_version' => $confession->visibility_version,
            'occurred_at' => now(),
        ]);
    }
}

class RecordingWebPushGateway implements WebPushGateway
{
    public array $sent = [];

    public function __construct(public array $results = []) {}

    public function send(array $items): array
    {
        $this->sent = [...$this->sent, ...$items];

        return $this->results;
    }
}
