<?php

namespace App\Models;

use Database\Factories\ConfessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Confession extends Model
{
    use HasFactory;

    protected static function newFactory(): ConfessionFactory
    {
        return ConfessionFactory::new();
    }

    protected $fillable = [
        'locale', 'category_key', 'status', 'title', 'body', 'excerpt', 'published_at',
        'public_id', 'author_alias', 'response_expectation', 'submission_key_hash', 'payload_fingerprint',
        'age_confirmed_at', 'public_revision_id', 'lock_version', 'visibility_version', 'ever_public', 'deleted_at', 'republish_requested_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'immutable_datetime',
            'age_confirmed_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
            'republish_requested_at' => 'immutable_datetime',
            'ever_public' => 'boolean',
            'lock_version' => 'integer',
            'visibility_version' => 'integer',
        ];
    }

    /** @param Builder<Confession> $query */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('status', 'public')->whereNotNull('published_at');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ConfessionRevision::class);
    }

    public function ownership(): HasOne
    {
        return $this->hasOne(ConfessionOwnership::class);
    }
}
