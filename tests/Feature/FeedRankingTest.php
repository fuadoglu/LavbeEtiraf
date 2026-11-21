<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Support\Lavbe\FeedQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeedRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_counts_unique_live_commenters_and_recent_active_supports(): void
    {
        $this->freezeTime();
        $older = Confession::factory()->create(['published_at' => now()->subMonth()]);
        $newer = Confession::factory()->create();
        $this->comment($older, 'Lavbe #A1234567');
        $this->comment($older, 'Lavbe #A1234567');
        foreach (range(1, 3) as $index) {
            DB::table('confession_support_reactions')->insert([
                'confession_id' => $newer->id, 'participant_hash' => hash('sha256', (string) $index),
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        // One unique commenter scores two points, three supports score three.
        $this->assertSame([$newer->id, $older->id], $this->rank('weekly'));
        DB::table('confession_support_reactions')->where('confession_id', $newer->id)->update(['updated_at' => now()->subDays(8)]);
        $this->assertSame([$older->id, $newer->id], $this->rank('weekly'));
        foreach (range(4, 6) as $index) {
            DB::table('confession_support_reactions')->insert([
                'confession_id' => $newer->id, 'participant_hash' => hash('sha256', (string) $index),
                'is_active' => false, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->comment($newer, 'Lavbe #B1234567', 'hidden');
        $this->comment($newer, 'Lavbe #C1234567', 'public', 'pending');
        $this->assertSame([$older->id, $newer->id], $this->rank('weekly'));
        config(['feed.commenter_weight' => 0]);
        $this->assertSame([$newer->id, $older->id], $this->rank('weekly'));
    }

    public function test_popular_and_weekly_aggregates_are_scoped_by_locale_before_grouping(): void
    {
        foreach (['popular', 'weekly'] as $order) {
            $sql = str_replace(['`', '"'], '', strtolower(app(FeedQuery::class)->build('az', null, $order)->toSql()));
            $this->assertSame(2, substr_count($sql, 'ranked_confessions.locale = ?'), $order.' must constrain both score aggregates before grouping.');
            $this->assertSame(2, substr_count($sql, 'ranked_revisions.status = ?') + substr_count($sql, 'ranked_parent_revisions.status = ?'));
        }
    }

    private function rank(string $order): array
    {
        return app(FeedQuery::class)->build('az', null, $order)->get()->modelKeys();
    }

    private function comment(Confession $parent, string $alias, string $status = 'public', string $revisionStatus = 'approved'): void
    {
        $comment = ConfessionComment::query()->create([
            'confession_id' => $parent->id, 'public_id' => (string) Str::uuid(),
            'status' => $status, 'author_alias' => $alias, 'body' => 'Synthetic comment',
            'published_at' => now(),
        ]);
        $revision = $comment->revisions()->create(['revision' => 1, 'body' => 'Synthetic comment', 'status' => $revisionStatus]);
        $comment->forceFill(['public_revision_id' => $revision->id])->save();
    }
}
