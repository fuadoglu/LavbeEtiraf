<?php

namespace App\Support\Lavbe;

use App\Models\Confession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FeedQuery
{
    /** @return Builder<Confession> */
    public function build(string $locale, ?string $category, string $order): Builder
    {
        $query = Confession::query()->public()->where('locale', $locale)->select('confessions.*');

        if ($category !== null) {
            $query->where('category_key', $category);
        }

        if ($order === 'weekly') {
            $since = now()->subDays((int) config('feed.weekly_days'));
            $supports = DB::table('confession_support_reactions')
                ->select('confession_id')->selectRaw('COUNT(*) AS supports')
                ->where('is_active', true)->where('updated_at', '>=', $since)
                ->groupBy('confession_id');
            $commenters = DB::table('confession_comments as comments')
                ->join('confession_comment_revisions as revisions', function ($join): void {
                    $join->on('revisions.id', '=', 'comments.public_revision_id')
                        ->on('revisions.comment_id', '=', 'comments.id');
                })
                ->select('comments.confession_id')->selectRaw('COUNT(DISTINCT comments.author_alias) AS commenters')
                ->where('comments.status', 'public')->where('revisions.status', 'approved')
                ->where('comments.published_at', '>=', $since)
                ->groupBy('comments.confession_id');

            $query->leftJoinSub($supports, 'weekly_supports', 'weekly_supports.confession_id', '=', 'confessions.id')
                ->leftJoinSub($commenters, 'weekly_commenters', 'weekly_commenters.confession_id', '=', 'confessions.id')
                ->orderByRaw('COALESCE(weekly_supports.supports, 0) * ? + COALESCE(weekly_commenters.commenters, 0) * ? DESC', [
                    (int) config('feed.support_weight'), (int) config('feed.commenter_weight'),
                ]);
        } elseif ($order === 'updates') {
            $continuations = DB::table('confession_continuations as continuations')
                ->join('confession_continuation_revisions as revisions', function ($join): void {
                    $join->on('revisions.id', '=', 'continuations.public_revision_id')
                        ->on('revisions.continuation_id', '=', 'continuations.id');
                })
                ->select('continuations.confession_id')->selectRaw('MAX(continuations.published_at) AS latest_continuation')
                ->where('continuations.status', 'public')->where('revisions.status', 'approved')
                ->whereNotNull('continuations.published_at')->groupBy('continuations.confession_id');
            $query->joinSub($continuations, 'live_continuations', 'live_continuations.confession_id', '=', 'confessions.id')
                ->orderByDesc('live_continuations.latest_continuation');

            return $query->orderByDesc('confessions.id');
        }

        return $query->orderByDesc('confessions.published_at')->orderByDesc('confessions.id');
    }
}
