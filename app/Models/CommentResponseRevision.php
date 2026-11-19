<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CommentResponseRevision extends Model
{
    protected $fillable = ['response_id', 'revision', 'body', 'status', 'reviewed_by_user_id', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'immutable_datetime', 'revision' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $revision): void {
            foreach (['response_id', 'revision', 'body'] as $field) {
                if ($revision->isDirty($field)) {
                    throw new LogicException('Comment response revision content is immutable.');
                }
            }
        });
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(CommentResponse::class, 'response_id');
    }
}
