<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommentResponse extends Model
{
    protected $fillable = ['comment_id', 'status', 'body', 'public_revision_id', 'lock_version', 'visibility_version', 'published_at'];

    protected function casts(): array
    {
        return [
            'published_at' => 'immutable_datetime',
            'lock_version' => 'integer',
            'visibility_version' => 'integer',
        ];
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(ConfessionComment::class, 'comment_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(CommentResponseRevision::class, 'response_id');
    }

    public function publicRevision(): BelongsTo
    {
        return $this->belongsTo(CommentResponseRevision::class, 'public_revision_id');
    }
}
