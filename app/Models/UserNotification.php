<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'event_key', 'event_type', 'in_app', 'read_at', 'email_sent_at', 'email_skipped_at',
        'email_claimed_at', 'email_attempts', 'last_error_code', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'in_app' => 'boolean', 'read_at' => 'immutable_datetime', 'email_sent_at' => 'immutable_datetime',
            'email_skipped_at' => 'immutable_datetime', 'email_claimed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime', 'email_attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
