<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    protected $fillable = [
        'user_id', 'endpoint_hash', 'endpoint', 'p256dh', 'auth', 'active', 'expires_at', 'activated_at', 'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'endpoint' => 'encrypted',
            'p256dh' => 'encrypted',
            'auth' => 'encrypted',
            'active' => 'boolean',
            'expires_at' => 'immutable_datetime',
            'activated_at' => 'immutable_datetime',
            'last_used_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
