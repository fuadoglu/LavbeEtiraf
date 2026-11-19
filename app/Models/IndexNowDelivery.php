<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndexNowDelivery extends Model
{
    protected $table = 'indexnow_deliveries';

    protected $fillable = [
        'moderation_outbox_id', 'event_key', 'attempts', 'next_attempt_at', 'claimed_until',
        'delivered_at', 'failed_at', 'response_code', 'last_error_code',
    ];

    protected function casts(): array
    {
        return [
            'next_attempt_at' => 'immutable_datetime',
            'claimed_until' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
        ];
    }
}
