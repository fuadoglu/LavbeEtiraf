<?php

namespace App\Models;

use Database\Factories\ConfessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Confession extends Model
{
    use HasFactory;

    protected static function newFactory(): ConfessionFactory
    {
        return ConfessionFactory::new();
    }

    protected $fillable = [
        'locale', 'category_key', 'status', 'title', 'body', 'excerpt', 'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'immutable_datetime'];
    }

    /** @param Builder<Confession> $query */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('status', 'public')->whereNotNull('published_at');
    }
}
