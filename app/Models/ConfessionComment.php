<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConfessionComment extends Model
{
    protected $fillable = [
        'confession_id', 'parent_comment_id', 'public_id', 'status', 'author_alias', 'body', 'public_revision_id', 'lock_version',
        'visibility_version', 'reaction_count', 'published_at', 'idempotency_hash', 'idempotency_fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'immutable_datetime',
            'lock_version' => 'integer',
            'visibility_version' => 'integer',
            'reaction_count' => 'integer',
        ];
    }

    /** @param Builder<ConfessionComment> $query */
    public function scopePublicApproved(Builder $query): Builder
    {
        return $query->where('status', 'public')->whereNotNull('public_revision_id')
            ->whereHas('publicRevision', fn (Builder $revision) => $revision->where('status', 'approved')
                ->whereColumn('confession_comment_revisions.comment_id', 'confession_comments.id'));
    }

    public function confession(): BelongsTo
    {
        return $this->belongsTo(Confession::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_comment_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_comment_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ConfessionCommentReaction::class, 'comment_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ConfessionCommentRevision::class, 'comment_id');
    }

    public function publicRevision(): BelongsTo
    {
        return $this->belongsTo(ConfessionCommentRevision::class, 'public_revision_id');
    }

    public function ownership(): HasOne
    {
        return $this->hasOne(CommentOwnership::class, 'comment_id');
    }

    public function response(): HasOne
    {
        return $this->hasOne(CommentResponse::class, 'comment_id');
    }
}
