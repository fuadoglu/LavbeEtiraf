<?php

namespace App\Models;

use Database\Factories\ConfessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'locale', 'category_key', 'status', 'title', 'body', 'excerpt', 'published_at', 'public_updated_at',
        'public_id', 'author_alias', 'response_expectation', 'submission_key_hash', 'payload_fingerprint',
        'age_confirmed_at', 'public_revision_id', 'lock_version', 'visibility_version', 'ever_public', 'deleted_at', 'republish_requested_at',
        'reviews_open', 'support_count', 'unique_read_count',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'immutable_datetime',
            'public_updated_at' => 'immutable_datetime',
            'age_confirmed_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
            'republish_requested_at' => 'immutable_datetime',
            'ever_public' => 'boolean',
            'lock_version' => 'integer',
            'visibility_version' => 'integer',
            'reviews_open' => 'boolean',
            'support_count' => 'integer',
            'unique_read_count' => 'integer',
        ];
    }

    /** @param Builder<Confession> $query */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('status', 'public')
            ->whereNotNull('published_at')
            ->whereNotNull('public_revision_id')
            ->whereHas('publicRevision', function (Builder $revision): void {
                $revision->whereColumn('confession_revisions.confession_id', 'confessions.id')
                    ->where('confession_revisions.status', 'approved');
            });
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ConfessionRevision::class);
    }

    public function ownership(): HasOne
    {
        return $this->hasOne(ConfessionOwnership::class);
    }

    public function publicRevision(): BelongsTo
    {
        return $this->belongsTo(ConfessionRevision::class, 'public_revision_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ConfessionComment::class);
    }

    public function continuations(): HasMany
    {
        return $this->hasMany(ConfessionContinuation::class);
    }

    public function supportReactions(): HasMany
    {
        return $this->hasMany(ConfessionSupportReaction::class);
    }
}
