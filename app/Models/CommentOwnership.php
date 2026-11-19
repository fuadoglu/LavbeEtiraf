<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentOwnership extends Model
{
    protected $fillable = ['comment_id', 'session_token_hash', 'owner_token_hash', 'recovery_token_hash', 'credential_version'];

    protected function casts(): array
    {
        return ['credential_version' => 'integer'];
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(ConfessionComment::class, 'comment_id');
    }
}
