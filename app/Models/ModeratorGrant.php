<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModeratorGrant extends Model
{
    protected $fillable = ['user_id', 'role', 'locale', 'is_active', 'granted_by_user_id', 'changed_by_user_id', 'reason', 'revoked_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'revoked_at' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
