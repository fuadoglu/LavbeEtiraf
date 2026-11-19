<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ConfessionContinuationRevision extends Model
{
    protected $fillable = ['continuation_id', 'revision', 'body', 'status', 'reviewed_by_user_id', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'immutable_datetime', 'revision' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $revision): void {
            foreach (['continuation_id', 'revision', 'body'] as $field) {
                if ($revision->isDirty($field)) {
                    throw new LogicException('Continuation revision content is immutable.');
                }
            }
        });
    }

    public function continuation(): BelongsTo
    {
        return $this->belongsTo(ConfessionContinuation::class, 'continuation_id');
    }
}
