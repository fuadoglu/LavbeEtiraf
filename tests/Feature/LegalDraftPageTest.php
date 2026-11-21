<?php

namespace Tests\Feature;

use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalDraftPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_locale_drafts_are_accessible_and_not_indexable(): void
    {
        foreach (app(LocaleRegistry::class)->all() as $locale) {
            $base = '/'.$locale['segment'];
            $home = $this->get($base)->assertOk();
            foreach (['terms', 'community', 'privacy', 'cookies', 'illegal_content', 'data_request', 'operator'] as $page) {
                if ($page === 'operator') {
                    $home->assertDontSee($base.'/legal/'.$page);
                } else {
                    $home->assertSee($base.'/legal/'.$page);
                }
                $body = str_replace(
                    [':retention_days', ':site_name', ':site_domain'],
                    [(string) config('analytics.unique_read_retention_days'), (string) config('app.name'), (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: config('app.name'))],
                    __('legal.'.$page.'.body', [], $locale['code']),
                );
                $response = $this->get($base.'/legal/'.$page)->assertOk()
                    ->assertSee(__('legal.'.$page.'.title', [], $locale['code']))
                    ->assertSee($body);
                if ($page !== 'operator') {
                    $sections = __('legal.'.$page.'.sections', [], $locale['code']);
                    $this->assertGreaterThanOrEqual(2, count($sections));
                    foreach ($sections as $section) {
                        $response->assertSee('<h2>'.e($section['heading']).'</h2>', false)
                            ->assertSee($section['text']);
                    }
                }
                if ($page === 'cookies') {
                    $response->assertSee(__('consent.preference_cookie_notice', ['consent_days' => config('analytics.reader_consent_days')], $locale['code']));
                }
                $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
                $this->assertSame('noindex, nofollow, noarchive', $response->headers->get('X-Robots-Tag'));
            }
        }
        $this->get('/az/legal/unknown')->assertNotFound();
    }

    public function test_privacy_pages_show_local_rights_and_configured_contact(): void
    {
        config(['lavbe.operator_name' => 'Example Operator', 'lavbe.privacy_email' => 'privacy@example.test']);

        foreach (app(LocaleRegistry::class)->all() as $locale) {
            $this->get('/'.$locale['segment'].'/legal/privacy')->assertOk()
                ->assertSee(__('legal.rights', [], $locale['code']))
                ->assertSee('Example Operator')
                ->assertSee('mailto:privacy@example.test', false);

            $pages = require lang_path($locale['code'].'/legal.php');
            $bodies = array_column(array_filter($pages, 'is_array'), 'body');
            $this->assertCount(7, array_unique($bodies));
        }
    }

    public function test_footer_has_compact_named_navigation_and_omits_the_signature_line(): void
    {
        $response = $this->get('/az/legal/privacy')->assertOk();
        $response->assertSee('<nav class="site-footer-navigation" aria-label="Hüquqi və məlumat keçidləri">', false)
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('/az/legal/operator', false)
            ->assertSee('--brand-heart-mask: url(', false)
            ->assertDontSee('Lavbe · Etiraflar Vadisi', false);
    }
}
