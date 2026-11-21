<?php

namespace App\Support\Lavbe;

use App\Models\Confession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ConfessionRecommendations
{
    /** @return Collection<int, Confession> */
    public function for(Confession $confession, string $currentCategory): Collection
    {
        $poolSize = max(1, (int) config('lavbe.recommendation_pool_per_category'));
        $cacheKey = 'lavbe:recommendations:v1:'.$confession->locale.':'.$poolSize;
        $candidateIds = Cache::remember(
            $cacheKey,
            max(1, (int) config('lavbe.recommendation_pool_cache_seconds')),
            fn (): array => $this->candidateIdsByCategory((string) $confession->locale, $poolSize),
        );
        $ids = collect($candidateIds)->flatten()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Confession::query()->public()
            ->where('locale', $confession->locale)
            ->whereNotNull('category_key')
            ->where('category_key', '!=', $currentCategory)
            ->whereIn('confessions.id', $ids)
            ->with('publicRevision')
            ->get()
            ->groupBy('category_key')
            ->map(fn (Collection $stories): Confession => $stories->random())
            ->shuffle()
            ->take((int) config('lavbe.recommendation_count'))
            ->values();
    }

    /** @return array<string, list<int>> */
    private function candidateIdsByCategory(string $locale, int $poolSize): array
    {
        $ranked = Confession::query()->public()
            ->where('locale', $locale)
            ->whereNotNull('category_key')
            ->select(['confessions.id', 'confessions.category_key'])
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY confessions.category_key ORDER BY confessions.support_count DESC, confessions.published_at DESC, confessions.id DESC) AS category_rank');

        return DB::query()->fromSub($ranked, 'ranked_recommendations')
            ->where('category_rank', '<=', $poolSize)
            ->get(['id', 'category_key'])
            ->groupBy('category_key')
            ->map(fn (Collection $stories): array => $stories->pluck('id')->map(fn ($id): int => (int) $id)->all())
            ->all();
    }
}
