<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConfessionContinuation extends Model
{
    protected $fillable = ['confession_id', 'public_id', 'status', 'body', 'public_revision_id', 'lock_version', 'visibility_version', 'published_at'];

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
        return $this->hasMany(ConfessionContinuationRevision::class, 'continuation_id');
    }

    public function publicRevision(): BelongsTo
    {
        return $this->belongsTo(ConfessionContinuationRevision::class, 'public_revision_id');
    }
}
