<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConfessionComment extends Model
{
    protected $fillable = [
        'confession_id', 'public_id', 'status', 'author_alias', 'body', 'public_revision_id', 'lock_version',
        'visibility_version', 'published_at', 'idempotency_hash', 'idempotency_fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'immutable_datetime',
            'lock_version' => 'integer',
            'visibility_version' => 'integer',
        ];
    }

    public function confession(): BelongsTo
    {
        return $this->belongsTo(Confession::class);
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
