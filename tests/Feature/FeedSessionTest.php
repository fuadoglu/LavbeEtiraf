<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Models\ConfessionContinuation;
use App\Support\Lavbe\FeedCursor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeedSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        config(['feed.page_size' => 2, 'cache.default' => 'array']);
    }

    public function test_new_session_skips_hidden_and_reentered_items_and_excludes_new_publications(): void
    {
        $stories = Confession::factory()->count(7)->create()->reverse()->values();
        $first = $this->get('/az/')->assertOk();
        $cursor = app(FeedCursor::class)->issue('az', null, 'new', 2);
        $this->assertSame($stories->take(2)->modelKeys(), $first->viewData('confessions')->getCollection()->modelKeys());

        $stories[0]->forceFill(['status' => 'hidden'])->save();
        $stories[2]->forceFill(['status' => 'hidden'])->save();
        $stories[2]->forceFill(['status' => 'public'])->save();
        $stories[3]->forceFill(['status' => 'deleted'])->save();
        $new = Confession::factory()->create();

        $page = $this->fragment($cursor);
        $this->assertStringContainsString('data-confession-id="'.$stories[4]->id.'"', $page['html']);
        $this->assertStringContainsString('data-confession-id="'.$stories[5]->id.'"', $page['html']);
        $this->assertStringNotContainsString('data-confession-id="'.$new->id.'"', $page['html']);
        $this->assertStringNotContainsString('data-confession-id="'.$stories[2]->id.'"', $page['html']);
        $last = $this->fragment($page['next_cursor']);
        $this->assertStringContainsString('data-confession-id="'.$stories[6]->id.'"', $last['html']);
        $this->assertFalse($last['has_more']);
        $this->assertNull($last['next_cursor']);
        $this->assertNull($last['next_url']);
    }

    public function test_weekly_session_keeps_its_order_when_activity_changes(): void
    {
        $stories = Confession::factory()->count(5)->create()->reverse()->values();
        $this->get('/az/?sort=weekly')->assertOk();
        $cursor = app(FeedCursor::class)->issue('az', null, 'weekly', 2);
        DB::table('confession_support_reactions')->insert([
            'confession_id' => $stories[4]->id, 'participant_hash' => hash('sha256', 'synthetic'),
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $page = $this->fragment($cursor, 'weekly');
        $this->assertStringContainsString('data-confession-id="'.$stories[2]->id.'"', $page['html']);
        $this->assertStringContainsString('data-confession-id="'.$stories[3]->id.'"', $page['html']);
        $this->assertStringNotContainsString('data-confession-id="'.$stories[4]->id.'"', $page['html']);
    }

    public function test_updates_session_rechecks_live_continuations_without_reordering_members(): void
    {
        $stories = Confession::factory()->count(5)->create()->reverse()->values();
        $continuations = [];
        foreach ($stories as $index => $story) {
            $continuation = ConfessionContinuation::query()->create([
                'confession_id' => $story->id, 'public_id' => (string) Str::uuid(),
                'body' => 'Synthetic continuation', 'status' => 'public', 'published_at' => now()->subHours($index),
            ]);
            $revision = $continuation->revisions()->create(['revision' => 1, 'body' => 'Synthetic continuation', 'status' => 'approved']);
            $continuation->forceFill(['public_revision_id' => $revision->id])->save();
            $continuations[] = $continuation;
        }
        $cursor = app(FeedCursor::class)->issue('az', null, 'updates', 2);
        $continuations[2]->forceFill(['status' => 'hidden'])->save();
        $continuations[4]->forceFill(['published_at' => now()->addHour()])->save();
        $page = $this->fragment($cursor, 'updates');
        $this->assertStringContainsString('data-confession-id="'.$stories[3]->id.'"', $page['html']);
        $this->assertStringContainsString('data-confession-id="'.$stories[4]->id.'"', $page['html']);
        $this->assertStringNotContainsString('data-confession-id="'.$stories[2]->id.'"', $page['html']);
        $this->assertLessThan(strpos($page['html'], 'data-confession-id="'.$stories[4]->id.'"'), strpos($page['html'], 'data-confession-id="'.$stories[3]->id.'"'));
        $this->assertFalse($page['has_more']);
    }

    public function test_missing_snapshot_returns_gone_and_continuation_does_not_extend_expiry(): void
    {
        Confession::factory()->count(6)->create();
        $this->get('/az/')->assertOk();
        $cursor = app(FeedCursor::class)->issue('az', null, 'new', 2);
        $state = app(FeedCursor::class)->read($cursor, 'az', null, 'new');
        $this->travel(30)->minutes();
        $next = $this->fragment($cursor)['next_cursor'];
        $nextState = app(FeedCursor::class)->read($next, 'az', null, 'new');
        $this->assertSame($state['expires'], $nextState['expires']);
        Cache::forget('lavbe:feed:'.$state['snapshot']);
        $this->getJson('/az/feed-fragment?'.http_build_query(['cursor' => $next, 'order' => 'new']))->assertGone();
    }

    public function test_snapshot_contains_only_metadata_and_excludes_future_publications(): void
    {
        Confession::factory()->create(['title' => 'Synthetic secret title', 'body' => 'Synthetic secret body']);
        Confession::factory()->create(['published_at' => now()->addDay()]);
        $cursor = app(FeedCursor::class)->issue('az', null, 'new', 2);
        $state = app(FeedCursor::class)->read($cursor, 'az', null, 'new');
        $members = Cache::get('lavbe:feed:'.$state['snapshot']);
        $this->assertCount(1, $members);
        $this->assertSame(['id', 'sequence', 'published'], array_keys($members[0]));
        $this->assertSame(now()->format('Y-m-d H:i:s'), $state['as_of']);
        $this->assertGreaterThan(0, $state['upper_sequence']);
    }

    public function test_snapshot_cache_is_bounded_while_numbered_html_reaches_the_full_archive(): void
    {
        config(['feed.snapshot_max_members' => 3, 'feed.page_size' => 2]);
        Confession::factory()->count(5)->create(['locale' => 'az']);
        $first = $this->get('/az')->assertOk();
        $cursor = app(FeedCursor::class)->issue('az', null, 'new', 2);
        $state = app(FeedCursor::class)->read($cursor, 'az', null, 'new');
        $this->assertCount(3, Cache::get('lavbe:feed:'.$state['snapshot']));
        $this->assertSame(5, $first->viewData('confessions')->total());
        $next = $this->fragment($cursor);
        $this->assertSame(1, substr_count($next['html'], 'data-confession-id='));
        $this->assertFalse($next['has_more']);
        $this->get('/az?page=3')->assertOk();
    }

    public function test_database_snapshot_cleanup_keeps_active_entries(): void
    {
        config(['cache.default' => 'database']);
        Cache::put('lavbe:feed:expired-synthetic', ['id' => 1], now()->addSecond());
        Cache::put('lavbe:feed:active-synthetic', ['id' => 2], now()->addHour());
        $this->travel(2)->seconds();

        $this->artisan('lavbe:prune-expired-cache')->assertSuccessful();
        $this->assertNull(Cache::get('lavbe:feed:expired-synthetic'));
        $this->assertSame(['id' => 2], Cache::get('lavbe:feed:active-synthetic'));
        $this->assertDatabaseCount('cache', 1);
    }

    private function fragment(string $cursor, string $order = 'new'): array
    {
        return $this->getJson('/az/feed-fragment?'.http_build_query(['cursor' => $cursor, 'order' => $order]))
            ->assertOk()->assertHeader('X-Robots-Tag', 'noindex')->json();
    }
}
