<?php

namespace Database\Factories;

use App\Models\Confession;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Test-only fixture factory; never registered with a production seeder. */
class ConfessionFactory extends Factory
{
    protected $model = Confession::class;

    public function definition(): array
    {
        return [
            'locale' => 'az',
            'category_key' => 'family',
            'status' => 'public',
            'title' => 'Sınaq hekayəsi',
            'body' => 'Bu, yalnız avtomatlaşdırılmış sınaq üçün uydurulmuş mətndir.',
            'excerpt' => 'Yalnız sınaq mətni.',
            'published_at' => now(),
        ];
    }
}
