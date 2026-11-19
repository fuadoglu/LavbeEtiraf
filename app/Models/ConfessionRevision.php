<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ConfessionRevision extends Model
{
    protected $fillable = ['confession_id', 'revision', 'title', 'body', 'category_key', 'response_expectation', 'status'];

    protected static function booted(): void
    {
        static::updating(function (self $revision): void {
            foreach (['confession_id', 'revision', 'title', 'body', 'category_key', 'response_expectation'] as $field) {
                if ($revision->isDirty($field)) {
                    throw new LogicException('Confession revision content is immutable.');
                }
            }
        });
    }

    public function confession(): BelongsTo
    {
        return $this->belongsTo(Confession::class);
    }
}
