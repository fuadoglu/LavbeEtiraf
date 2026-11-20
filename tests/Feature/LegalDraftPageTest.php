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
                $response = $this->get($base.'/legal/'.$page)->assertOk()
                    ->assertSee(__('legal.'.$page.'.title', [], $locale['code']))
                    ->assertSee(__('legal.'.$page.'.body', [], $locale['code']));
                $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
                $this->assertSame('noindex, nofollow, noarchive', $response->headers->get('X-Robots-Tag'));
            }
        }
        $this->get('/az/legal/unknown')->assertNotFound();
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
