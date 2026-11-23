<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Support\Lavbe\AuthorAlias;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_detail_uses_approved_revision_and_configured_canonical(): void
    {
        $confession = $this->publishedConfession('az', 'privacy <script>alert(1)</script>');
        $confession->revisions()->create([
            'revision' => 2,
            'title' => 'Unapproved edit',
            'body' => 'Pending private body',
            'category_key' => 'family',
            'status' => 'pending',
        ]);
        $response = $this->get('/az/confession/'.$confession->public_id)->assertOk();

        $response->assertSee('<link rel="canonical" href="http://localhost/az/confession/'.$confession->public_id.'">', false);
        $response->assertSee('privacy &lt;script&gt;alert(1)&lt;/script&gt;', false);
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertDontSee('Pending private body');
        $response->assertSee('og:locale" content="az"', false);
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_public_detail_status_and_sitemap_follow_publication_lifecycle(): void
    {
        $published = $this->publishedConfession('az', 'published');
        $this->publishedConfession('en', 'eligible English category fixture');
        $pending = $this->pendingConfession('az');
        $hidden = $this->publishedConfession('az', 'hidden');
        $hidden->forceFill(['status' => 'hidden'])->save();
        $withdrawn = $this->publishedConfession('az', 'withdrawn');
        $withdrawn->forceFill(['status' => 'withdrawn'])->save();
        $deleted = $this->publishedConfession('az', 'terminal');
        $deleted->forceFill(['status' => 'deleted', 'ever_public' => true, 'body' => '', 'title' => '', 'public_revision_id' => null])->save();

        $this->get('/az/confession/'.$published->public_id)->assertOk();
        $this->get('/az/confession/'.$pending->public_id)->assertNotFound();
        $this->get('/az/confession/'.$hidden->public_id)->assertNotFound();
        $this->get('/az/confession/'.$withdrawn->public_id)->assertNotFound();
        $this->get('/az/confession/'.$deleted->public_id)->assertStatus(410);

        config(['seo.category_min_public_items' => 1]);
        $index = $this->get('/sitemap.xml')->assertOk()->streamedContent();
        $this->assertStringContainsString('/az/sitemap-1.xml', $index);
        $xml = $this->get('/az/sitemap-1.xml')->assertOk()->streamedContent();
        $this->assertStringContainsString('/az/confession/'.$published->public_id, $xml);
        $this->assertStringNotContainsString($pending->public_id, $xml);
        $this->assertStringNotContainsString($hidden->public_id, $xml);
        $this->assertStringNotContainsString($withdrawn->public_id, $xml);
        $this->assertStringNotContainsString($deleted->public_id, $xml);
        $this->assertStringContainsString('<lastmod>'.$published->public_updated_at->toAtomString().'</lastmod>', $xml);
        $this->assertStringContainsString('hreflang="x-default"', $xml);

        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: http://localhost/sitemap.xml');
    }

    public function test_public_lastmod_changes_only_when_an_approved_revision_changes(): void
    {
        $confession = $this->publishedConfession('en', 'original');
        $publishedAt = $confession->public_updated_at;
        $confession->forceFill(['updated_at' => now()->addDay(), 'status' => 'hidden'])->save();
        $this->assertTrue($confession->fresh()->public_updated_at->equalTo($publishedAt));

        $confession->forceFill(['status' => 'public', 'lock_version' => 3])->save();
        $confession->revisions()->create([
            'revision' => 2,
            'title' => 'Edited title',
            'body' => 'Edited approved body',
            'category_key' => 'family',
            'status' => 'approved',
        ]);
        $newLastmod = now()->addMinutes(1)->startOfSecond();
        $confession->forceFill(['public_revision_id' => $confession->revisions()->where('revision', 2)->value('id'), 'public_updated_at' => $newLastmod])->save();
        $this->assertTrue($confession->fresh()->public_updated_at->equalTo($newLastmod));
    }

    public function test_numbered_feed_page_has_a_canonical_html_link_and_pagination_state(): void
    {
        for ($index = 0; $index < 13; $index++) {
            $this->publishedConfession('en', 'pagination '.$index);
        }

        $response = $this->get('/en/?page=2')->assertOk();
        $response->assertSee('<link rel="canonical" href="http://localhost/en?page=2">', false);
        $response->assertSee('page=1');
        $response->assertSee('name="robots" content="noindex,follow"', false);
    }

    private function publishedConfession(string $locale, string $body): Confession
    {
        $publishedAt = Carbon::parse('2026-10-10 12:00:00 UTC');
        $confession = Confession::query()->create([
            'locale' => $locale,
            'category_key' => 'family',
            'status' => 'public',
            'title' => 'Synthetic public title',
            'body' => $body,
            'excerpt' => $body,
            'public_id' => (string) Str::uuid(),
            'author_alias' => AuthorAlias::generate(),
            'published_at' => $publishedAt,
            'public_updated_at' => $publishedAt,
            'ever_public' => true,
            'visibility_version' => 2,
            'lock_version' => 2,
        ]);
        $revision = $confession->revisions()->create([
            'revision' => 1,
            'title' => 'Synthetic public title',
            'body' => $body,
            'category_key' => 'family',
            'status' => 'approved',
        ]);
        $confession->forceFill(['public_revision_id' => $revision->id])->save();

        return $confession->fresh();
    }

    private function pendingConfession(string $locale): Confession
    {
        $confession = Confession::query()->create([
            'locale' => $locale,
            'status' => 'unpublished',
            'title' => '',
            'body' => '',
            'public_id' => (string) Str::uuid(),
            'author_alias' => 'Lavbe #AA11BB22',
        ]);
        $confession->revisions()->create([
            'revision' => 1,
            'title' => 'Synthetic pending title',
            'body' => 'Synthetic pending body',
            'category_key' => 'family',
            'status' => 'pending',
        ]);

        return $confession;
    }
}
