<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Models\ModeratorGrant;
use App\Models\ModeratorMfa;
use App\Models\SiteIntegrationSetting;
use App\Models\User;
use App\Support\Analytics\ExternalTags;
use App\Support\Lavbe\LocaleRegistry;
use App\Support\Moderation\ModeratorMfaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminSiteWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['moderation.mfa_key' => base64_encode(str_repeat('k', 32))]);
    }

    public function test_admin_can_publish_in_any_language_and_moderator_is_limited_to_their_grant(): void
    {
        $moderator = $this->staff('moderator', 'az');
        $this->actingAs($moderator)->withSession($this->freshMfaSession($moderator));
        $this->get('/en/moderation/write')->assertNotFound();
        $this->post('/en/moderation/write', $this->story())->assertNotFound();
        $this->get('/az/moderation/settings')->assertNotFound();

        $admin = $this->staff('admin', '*');
        $this->actingAs($admin)->withSession($this->freshMfaSession($admin));
        foreach (app(LocaleRegistry::class)->all() as $language) {
            $this->get('/'.$language['segment'].'/moderation/write')->assertOk();
        }
        $this->post('/en/moderation/write', $this->story())->assertCreated();
        $confession = Confession::query()->sole();
        $this->assertSame('public', $confession->status);
        $this->assertSame('en', $confession->locale);
        $this->assertSame($admin->id, $confession->ownership->user_id);
        $this->assertDatabaseHas('moderation_audits', ['target_type' => 'confession', 'target_key' => (string) $confession->id, 'decision' => 'approve']);
        $this->assertDatabaseHas('moderation_outbox', ['target_public_id' => $confession->public_id, 'event_type' => 'publish']);
        $this->get('/en/confession/'.$confession->public_id)->assertOk()->assertSee('Synthetic staff story');
    }

    public function test_admin_settings_are_validated_versioned_and_audited(): void
    {
        $admin = $this->staff('admin', '*');
        $this->actingAs($admin)->withSession($this->freshMfaSession($admin));
        $this->get('/az/moderation/settings')->assertOk()->assertSee(__('moderation.settings_title'))
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->post('/az/moderation/settings', ['ga4_measurement_id' => '<script>alert(1)</script>', 'expected_lock_version' => 1])
            ->assertSessionHasErrors('ga4_measurement_id');
        $this->assertDatabaseHas('site_integration_settings', ['id' => 1, 'ga4_enabled' => 0]);

        $this->post('/az/moderation/settings', $this->settings())->assertRedirect('/az/moderation/settings');
        $this->assertDatabaseHas('site_integration_settings', ['id' => 1, 'ga4_enabled' => 1, 'ads_enabled' => 1, 'lock_version' => 2]);
        $this->assertDatabaseHas('moderation_audits', ['target_type' => 'site_setting', 'decision' => 'update_settings']);
        $this->post('/az/moderation/settings', $this->settings())->assertStatus(409);
        $this->assertSame(2, SiteIntegrationSetting::current()->lock_version);
    }

    public function test_external_tags_require_separate_consent_and_current_ad_review(): void
    {
        config(['external_tags.ready' => true]);
        $admin = $this->staff('admin', '*');
        $this->actingAs($admin)->withSession($this->freshMfaSession($admin));
        $this->post('/az/moderation/settings', $this->settings())->assertRedirect();
        $english = Confession::factory()->create(['locale' => 'en', 'public_id' => (string) Str::uuid(), 'reviews_open' => false])->fresh();
        $azerbaijani = Confession::factory()->create(['locale' => 'az', 'public_id' => (string) Str::uuid()])->fresh();
        $this->get('/en')->assertDontSee('googletagmanager.com');
        $this->get('/en/confession/'.$english->public_id)->assertOk()->assertDontSee('googlesyndication.com');

        $this->post('/az/moderation/confessions/'.$english->id.'/ads', [
            'eligible' => '1', 'expected_visibility_version' => $english->visibility_version,
        ])->assertRedirect();
        $this->assertDatabaseHas('moderation_audits', ['target_type' => 'confession', 'decision' => 'ads_eligible']);
        $this->get('/en/confession/'.$english->public_id)->assertDontSee('googlesyndication.com');

        $analyticsResponse = $this->withCookie(ExternalTags::ANALYTICS_COOKIE, 'accepted')->get('/en')->assertSee('googletagmanager.com');
        $this->assertStringContainsString('no-store', (string) $analyticsResponse->headers->get('Cache-Control'));
        $this->withCookie(ExternalTags::ANALYTICS_COOKIE, 'accepted')->get('/en/write')->assertDontSee('googletagmanager.com');
        $this->withCookie(ExternalTags::ADS_COOKIE, 'accepted')->get('/en/confession/'.$english->public_id)->assertSee('googlesyndication.com');
        $this->withCookie(ExternalTags::ADS_COOKIE, 'accepted')->get('/az/confession/'.$azerbaijani->public_id)->assertDontSee('googlesyndication.com');

        $english->forceFill(['reviews_open' => true])->save();
        $this->withCookie(ExternalTags::ADS_COOKIE, 'accepted')->get('/en/confession/'.$english->public_id)->assertDontSee('googlesyndication.com');
        $english->forceFill(['reviews_open' => false])->save();

        $this->post('/en/external-consent', ['purpose' => 'ads', 'choice' => 'decline'])->assertRedirect('/en/legal/cookies');
        $this->withCookie(ExternalTags::ADS_COOKIE, 'declined')->get('/en/confession/'.$english->public_id)->assertDontSee('googlesyndication.com');

        $english->forceFill(['visibility_version' => $english->visibility_version + 1])->save();
        $this->withCookie(ExternalTags::ADS_COOKIE, 'accepted')->get('/en/confession/'.$english->public_id)->assertDontSee('googlesyndication.com');

        config(['external_tags.ready' => false]);
        $this->withCookie(ExternalTags::ANALYTICS_COOKIE, 'accepted')->get('/en')->assertDontSee('googletagmanager.com');
    }

    private function staff(string $role, string $locale): User
    {
        $user = User::factory()->create();
        ModeratorGrant::query()->create(['user_id' => $user->id, 'role' => $role, 'locale' => $locale, 'reason' => 'synthetic test access']);
        ModeratorMfa::query()->create([
            'user_id' => $user->id,
            'secret_ciphertext' => app(ModeratorMfaService::class)->encryptSecret(app(ModeratorMfaService::class)->newSecret()),
            'confirmed_at' => now(),
        ]);

        return $user;
    }

    private function freshMfaSession(User $user): array
    {
        return ['moderator.mfa_verified_at' => time(), 'moderator.mfa_user_id' => $user->id];
    }

    private function story(): array
    {
        return [
            'title' => 'Synthetic staff story', 'body' => 'Synthetic public story for tests.',
            'category_key' => 'family', 'age_confirmed' => '1',
            'idempotency_key' => str_repeat('z', 43),
        ];
    }

    private function settings(): array
    {
        return [
            'ga4_enabled' => '1', 'ga4_measurement_id' => 'G-ABCDEF12',
            'ads_enabled' => '1', 'adsense_client_id' => 'ca-pub-1234567890123456',
            'story_slot_enabled' => '1', 'story_slot_id' => '1234567890',
            'expected_lock_version' => 1,
        ];
    }
}
