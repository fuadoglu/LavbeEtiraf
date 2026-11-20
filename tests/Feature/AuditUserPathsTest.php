<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditUserPathsTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_feed_links_open_in_every_language_and_match_the_sitemap(): void
    {
        config(['seo.category_min_public_items' => 1]);
        foreach (app(LocaleRegistry::class)->all() as $locale) {
            $story = Confession::factory()->create(['locale' => $locale['code'], 'public_id' => (string) Str::uuid()]);
            $feed = $this->get('/'.$locale['segment'])->assertOk();
            $document = new \DOMDocument;
            @$document->loadHTML($feed->getContent());
            $links = (new \DOMXPath($document))->query('//h2[contains(@class,"story-title")]/a');
            $this->assertCount(1, $links);
            $url = $links->item(0)->getAttribute('href');
            $path = '/'.$locale['segment'].'/confession/'.$story->public_id;
            $this->assertSame($path, parse_url($url, PHP_URL_PATH));
            $canonical = rtrim(config('app.url'), '/').$path;
            $this->get($path)->assertOk()
                ->assertSee('<html lang="'.$locale['code'].'"', false)
                ->assertSee('<link rel="canonical" href="'.$canonical.'">', false);
            $this->get('/'.$locale['segment'].'/report/confession/'.$story->public_id)->assertOk();
            $xml = $this->get('/'.$locale['segment'].'/sitemap-1.xml')->assertOk()->streamedContent();
            $this->assertStringContainsString('<loc>'.$canonical.'</loc>', $xml);
            $this->assertStringContainsString('<loc>http://localhost/'.$locale['segment'].'</loc>', $xml);
            $this->assertStringNotContainsString('<loc>http://localhost/'.$locale['segment'].'/</loc>', $xml);
        }
    }

    public function test_brazilian_portuguese_feed_uses_its_own_translation_without_fallback(): void
    {
        $this->assertTrue(app('translator')->hasForLocale('lavbe.feed_title', 'pt-BR'));
        $this->get('/pt-br')->assertOk()->assertDontSee('Confession Valley');
    }

    public function test_search_pagination_preserves_post_query_without_url_or_session_storage(): void
    {
        config(['feed.page_size' => 2]);
        Confession::factory()->count(3)->create(['body' => 'synthetic-search-needle']);
        Confession::factory()->create(['body' => 'unrelated synthetic text']);
        $first = $this->post('/az/search', ['q' => 'synthetic-search-needle'])->assertOk();
        $first->assertSee('name="page" value="2"', false);
        $first->assertSee('name="q" value="synthetic-search-needle"', false);
        $first->assertDontSee('?q=', false);
        $second = $this->post('/az/search', ['q' => 'synthetic-search-needle', 'page' => 2])->assertOk();
        $this->assertSame(1, substr_count($second->getContent(), 'data-confession-id='));
        $second->assertSee('name="page" value="1"', false);
        $this->post('/az/search', ['q' => 'synthetic-search-needle', 'page' => 1])->assertOk();
        $this->assertStringNotContainsString('synthetic-search-needle', json_encode(session()->all()));
        $this->get('/az/search?q=synthetic-search-needle')->assertOk()->assertDontSee('data-confession-id=');
        $this->assertStringContainsString('no-store', $second->headers->get('Cache-Control'));
    }

    public function test_robots_has_one_runtime_source_with_configured_sitemap(): void
    {
        $this->assertFileDoesNotExist(public_path('robots.txt'));
        config(['app.url' => 'https://synthetic.example']);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: https://synthetic.example/sitemap.xml');
    }
}
