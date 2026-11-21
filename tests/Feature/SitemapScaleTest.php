<?php

namespace Tests\Feature;

use App\Models\Confession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SitemapScaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_counts_are_grouped_and_story_reads_are_bounded(): void
    {
        config(['seo.category_min_public_items' => 1, 'seo.sitemap_read_batch' => 2]);
        foreach (['az', 'en', 'pt-BR'] as $locale) {
            foreach (['family', 'friends'] as $category) {
                Confession::factory()->count(3)->create(['locale' => $locale, 'category_key' => $category]);
            }
        }
        DB::enableQueryLog();
        $xml = $this->get('/az/sitemap-1.xml')->assertOk()->streamedContent();
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();
        $this->assertSame(6, substr_count($xml, '<loc>http://localhost/az/confession/'));
        $this->assertStringContainsString('hreflang="pt-BR" href="http://localhost/pt-br/topics/family"', $xml);
        $counts = $queries->filter(fn ($sql) => str_contains(strtolower($sql), 'count(*)'));
        $this->assertCount(1, $counts); // One grouped query supplies page and category counts.
        $reads = $queries->filter(fn ($sql) => str_contains($sql, 'public_id'));
        $this->assertCount(4, $reads); // Three bounded reads plus end detection.
        foreach ($reads as $sql) {
            $this->assertStringContainsString('limit 2', $sql);
        }
    }

    public function test_sitemap_index_uses_one_grouped_query_for_all_locale_page_counts(): void
    {
        Confession::factory()->count(3)->create(['locale' => 'az', 'category_key' => 'family']);
        Confession::factory()->count(2)->create(['locale' => 'en', 'category_key' => 'work']);
        DB::enableQueryLog();
        $xml = $this->get('/sitemap.xml')->assertOk()->streamedContent();
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $counts = $queries->filter(fn ($sql) => str_contains(strtolower($sql), 'count(*)'));
        $this->assertCount(1, $counts);
        $this->assertSame(17, substr_count($xml, '<sitemap>'));
    }

    public function test_sitemap_parts_do_not_repeat_or_exceed_their_story_range(): void
    {
        config(['seo.sitemap_max_urls' => 35, 'seo.sitemap_read_batch' => 2]);
        $stories = Confession::factory()->count(7)->create();
        foreach ([1 => 3, 2 => 3, 3 => 1] as $page => $count) {
            $xml = $this->get('/az/sitemap-'.$page.'.xml')->assertOk()->streamedContent();
            $this->assertSame($count, substr_count($xml, '<loc>http://localhost/az/confession/'));
            foreach ($stories as $index => $story) {
                $this->assertSame(intdiv($index, 3) + 1 === $page, str_contains($xml, $story->public_id));
            }
        }
    }
}
