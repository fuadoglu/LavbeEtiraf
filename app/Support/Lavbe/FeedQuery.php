<?php

namespace App\Support\Lavbe;

use App\Models\Confession;
use Illuminate\Database\Eloquent\Builder;

class FeedQuery
{
    /** @return Builder<Confession> */
    public function build(string $locale, ?string $category, string $order): Builder
    {
        $query = Confession::query()->public()->where('locale', $locale);

        if ($order === 'weekly') {
            $query->where('published_at', '>=', now()->subDays(7));
        }

        if ($category !== null) {
            $query->where('category_key', $category);
        }

        return match ($order) {
            'weekly' => $query->orderByDesc('published_at')->orderByDesc('id'),
            // LAV-004/005 will add approved update events and versioned weekly snapshots.
            'updates' => $query->orderByDesc('updated_at')->orderByDesc('id'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };
    }
}
