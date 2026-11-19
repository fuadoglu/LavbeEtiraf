<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Models\IndexNowDelivery;
use App\Models\ModerationOutbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class IndexNowDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_public_url_is_submitted_once_and_delivery_is_idempotent(): void
    {
        config(['app.url' => 'https://lavbe.example', 'services.indexnow.key' => 'test-key-1234']);
        Http::fake(['https://api.indexnow.org/indexnow' => Http::response('', 200)]);
        $confession = Confession::factory()->create(['locale' => 'az']);
        $event = $this->outbox($confession);
        $secondConfession = Confession::factory()->create(['locale' => 'en']);
        $secondEvent = $this->outbox($secondConfession);

        $this->artisan('lavbe:indexnow')->assertSuccessful();
        $this->artisan('lavbe:indexnow')->assertSuccessful();

        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.indexnow.org/indexnow'
            && $request['host'] === 'lavbe.example'
            && $request['key'] === 'test-key-1234'
            && $request['urlList'] === [
                'https://lavbe.example/az/confession/'.$confession->public_id,
                'https://lavbe.example/en/confession/'.$secondConfession->public_id,
            ]);
        $this->assertDatabaseHas('indexnow_deliveries', [
            'moderation_outbox_id' => $event->id,
            'attempts' => 1,
            'response_code' => 200,
            'delivered_at' => now()->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('indexnow_deliveries', [
            'moderation_outbox_id' => $secondEvent->id,
            'attempts' => 1,
            'response_code' => 200,
        ]);
    }

    public function test_transient_provider_failure_is_retried_with_backoff_and_later_accepted(): void
    {
        config(['app.url' => 'https://lavbe.example', 'services.indexnow.key' => 'test-key-1234']);
        Http::fakeSequence()->push('', 503)->push('', 202);
        $confession = Confession::factory()->create();
        $event = $this->outbox($confession);

        $this->artisan('lavbe:indexnow')->assertSuccessful();
        $this->assertDatabaseHas('indexnow_deliveries', [
            'moderation_outbox_id' => $event->id,
            'attempts' => 1,
            'last_error_code' => 'http_503',
            'delivered_at' => null,
        ]);

        $delivery = IndexNowDelivery::query()->firstOrFail();
        $delivery->forceFill(['next_attempt_at' => now()->subSecond()])->save();
        $this->artisan('lavbe:indexnow')->assertSuccessful();

        Http::assertSentCount(2);
        $this->assertNotNull($delivery->fresh()->delivered_at);
        $this->assertSame(202, $delivery->fresh()->response_code);
    }

    public function test_missing_or_invalid_production_configuration_never_submits(): void
    {
        Http::fake();
        $confession = Confession::factory()->create();
        $this->outbox($confession);

        config(['app.url' => 'http://lavbe.example', 'services.indexnow.key' => 'short']);
        $this->artisan('lavbe:indexnow')->assertSuccessful();
        Http::assertNothingSent();
        $this->assertDatabaseCount('indexnow_deliveries', 0);
    }

    public function test_key_verification_file_is_only_served_for_the_configured_key(): void
    {
        config(['services.indexnow.key' => 'test-key-1234']);

        $this->get('/test-key-1234.txt')->assertOk()->assertContent('test-key-1234')
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->get('/different-key.txt')->assertNotFound();
        config(['services.indexnow.key' => null]);
        $this->get('/test-key-1234.txt')->assertNotFound();
    }

    private function outbox(Confession $confession): ModerationOutbox
    {
        return ModerationOutbox::query()->create([
            'event_key' => hash('sha256', (string) Str::uuid()),
            'target_public_id' => $confession->public_id,
            'event_type' => 'publish',
            'visibility_version' => $confession->visibility_version ?: 1,
            'revision_id' => $confession->public_revision_id,
            'revision_hash' => hash('sha256', 'synthetic-test-revision'),
            'locale' => $confession->locale,
            'category_key' => $confession->category_key,
            'occurred_at' => now(),
        ]);
    }
}
