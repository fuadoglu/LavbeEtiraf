<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModeratorMfa extends Model
{
    protected $fillable = ['user_id', 'secret_ciphertext', 'pending_secret_ciphertext', 'confirmed_at', 'last_counter'];

    protected function casts(): array
    {
        return ['confirmed_at' => 'immutable_datetime', 'last_counter' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
