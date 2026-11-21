<?php

namespace Tests\Feature;

use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\ConfessionContinuation;
use App\Support\Lavbe\FeedQuery;
use Carbon\CarbonInterface;
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

    public function test_updates_uses_live_approved_continuations_instead_of_parent_updated_at(): void
    {
        $this->freezeTime();
        $latest = Confession::factory()->create(['published_at' => now()->subMonth()]);
        $earlier = Confession::factory()->create(['updated_at' => now()->addHour()]);
        $noUpdate = Confession::factory()->create();
        $this->continuation($latest, now());
        $this->continuation($earlier, now()->subDay());
        $this->continuation($noUpdate, now()->addMinute(), 'hidden');
        $this->continuation($noUpdate, now()->addMinute(), 'public', 'pending');
        $this->assertSame([$latest->id, $earlier->id], $this->rank('updates'));
        $this->continuation($earlier, now());
        $this->assertSame([$earlier->id, $latest->id], $this->rank('updates'));
        $latest->forceFill(['status' => 'hidden'])->save();
        $this->assertSame([$earlier->id], $this->rank('updates'));
    }

    public function test_publication_sequence_changes_only_when_reentering_the_public_feed(): void
    {
        $first = Confession::factory()->create();
        $second = Confession::factory()->create();
        $this->assertGreaterThan($first->publication_sequence, $second->publication_sequence);
        $initial = $first->publication_sequence;

        $first->forceFill(['body' => 'Synthetic approved edit', 'visibility_version' => 2])->save();
        $this->assertSame($initial, $first->fresh()->publication_sequence);
        $first->forceFill(['status' => 'hidden'])->save();
        $this->assertSame($initial, $first->fresh()->publication_sequence);
        $first->forceFill(['status' => 'public'])->save();
        $this->assertGreaterThan($second->publication_sequence, $first->fresh()->publication_sequence);
    }

    public function test_publication_sequence_and_visibility_roll_back_together(): void
    {
        $confession = Confession::factory()->create(['status' => 'hidden']);
        $this->assertNull($confession->publication_sequence);
        try {
            DB::transaction(function () use ($confession): void {
                $confession->forceFill(['status' => 'public'])->save();
                throw new \RuntimeException('Synthetic transaction failure');
            });
        } catch (\RuntimeException) {
            // Check the stored state after the interrupted publication.
        }
        $this->assertSame('hidden', $confession->fresh()->status);
        $this->assertNull($confession->fresh()->publication_sequence);
        $this->assertDatabaseCount('feed_publications', 0);
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

    private function continuation(Confession $parent, CarbonInterface $publishedAt, string $status = 'public', string $revisionStatus = 'approved'): void
    {
        $continuation = ConfessionContinuation::query()->create([
            'confession_id' => $parent->id, 'public_id' => (string) Str::uuid(),
            'status' => $status, 'body' => 'Synthetic continuation', 'published_at' => $publishedAt,
        ]);
        $revision = $continuation->revisions()->create(['revision' => 1, 'body' => 'Synthetic continuation', 'status' => $revisionStatus]);
        $continuation->forceFill(['public_revision_id' => $revision->id])->save();
    }
}
