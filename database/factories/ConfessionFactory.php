<?php

namespace Database\Factories;

use App\Models\Confession;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Test-only fixture factory; never registered with a production seeder. */
class ConfessionFactory extends Factory
{
    protected $model = Confession::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Confession $confession): void {
            if ($confession->status !== 'public' || $confession->public_revision_id) {
                return;
            }

            $revision = $confession->revisions()->create([
                'revision' => 1,
                'title' => $confession->title,
                'body' => $confession->body,
                'category_key' => $confession->category_key,
                'status' => 'approved',
            ]);
            $confession->forceFill([
                'public_revision_id' => $revision->id,
                'public_updated_at' => $confession->public_updated_at ?: $confession->published_at,
                'ever_public' => true,
            ])->saveQuietly();
        });
    }

    public function definition(): array
    {
        return [
            'locale' => 'az',
            'category_key' => 'family',
            'status' => 'public',
            'title' => 'Sınaq hekayəsi',
            'body' => 'Bu, yalnız avtomatlaşdırılmış sınaq üçün uydurulmuş mətndir.',
            'excerpt' => 'Yalnız sınaq mətni.',
            'public_id' => fake()->uuid(),
            'author_alias' => 'Lavbe #A1B2C3D4',
            'published_at' => now(),
        ];
    }
}
