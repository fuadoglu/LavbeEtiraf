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
        $query = Confession::query()->public()->where('locale', $locale)->select('confessions.*')
            ->withCount(['comments as public_comment_count' => fn ($comments) => $comments->publicApproved()])
            ->withCount(['continuations as public_continuation_count' => fn ($continuations) => $continuations
                ->where('status', 'public')->whereNotNull('public_revision_id')
                ->whereHas('publicRevision', fn ($revision) => $revision->where('status', 'approved'))]);

        if ($category !== null) {
            $query->where('category_key', $category);
        }

        if (in_array($order, ['weekly', 'popular'], true)) {
            $since = $order === 'weekly' ? now()->subDays((int) config('feed.weekly_days')) : null;
            $supports = DB::table('confession_support_reactions as reactions')
                ->join('confessions as ranked_confessions', 'ranked_confessions.id', '=', 'reactions.confession_id')
                ->join('confession_revisions as ranked_revisions', function ($join): void {
                    $join->on('ranked_revisions.id', '=', 'ranked_confessions.public_revision_id')
                        ->on('ranked_revisions.confession_id', '=', 'ranked_confessions.id')
                        ->where('ranked_revisions.status', 'approved');
                })
                ->select('reactions.confession_id')->selectRaw('COUNT(*) AS supports')
                ->where('ranked_confessions.locale', $locale)
                ->where('ranked_confessions.status', 'public')
                ->whereNotNull('ranked_confessions.published_at')
                ->where('reactions.is_active', true)
                ->when($since, fn ($query) => $query->where('reactions.updated_at', '>=', $since))
                ->groupBy('reactions.confession_id');
            $commenters = DB::table('confession_comments as comments')
                ->join('confessions as ranked_confessions', 'ranked_confessions.id', '=', 'comments.confession_id')
                ->join('confession_revisions as ranked_parent_revisions', function ($join): void {
                    $join->on('ranked_parent_revisions.id', '=', 'ranked_confessions.public_revision_id')
                        ->on('ranked_parent_revisions.confession_id', '=', 'ranked_confessions.id')
                        ->where('ranked_parent_revisions.status', 'approved');
                })
                ->join('confession_comment_revisions as revisions', function ($join): void {
                    $join->on('revisions.id', '=', 'comments.public_revision_id')
                        ->on('revisions.comment_id', '=', 'comments.id')
                        ->where('revisions.status', 'approved');
                })
                ->select('comments.confession_id')->selectRaw('COUNT(DISTINCT comments.author_alias) AS commenters')
                ->where('ranked_confessions.locale', $locale)
                ->where('ranked_confessions.status', 'public')
                ->whereNotNull('ranked_confessions.published_at')
                ->where('comments.status', 'public')
                ->whereNotNull('comments.published_at')
                ->when($since, fn ($query) => $query->where('comments.published_at', '>=', $since))
                ->groupBy('comments.confession_id');

            $query->leftJoinSub($supports, 'ranked_supports', 'ranked_supports.confession_id', '=', 'confessions.id')
                ->leftJoinSub($commenters, 'ranked_commenters', 'ranked_commenters.confession_id', '=', 'confessions.id');

            $query->reorder()->orderByRaw('COALESCE(ranked_supports.supports, 0) * ? + COALESCE(ranked_commenters.commenters, 0) * ? DESC', [
                (int) config('feed.support_weight'), (int) config('feed.commenter_weight'),
            ]);
        }

        return $query->orderByDesc('confessions.published_at')->orderByDesc('confessions.id');
    }
}
