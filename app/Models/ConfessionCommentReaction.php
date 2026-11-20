<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfessionCommentReaction extends Model
{
    protected $fillable = ['comment_id', 'participant_hash', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(ConfessionComment::class, 'comment_id');
    }
}
