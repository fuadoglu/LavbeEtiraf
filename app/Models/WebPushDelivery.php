<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebPushDelivery extends Model
{
    protected $fillable = [
        'user_notification_id', 'push_subscription_id', 'attempts', 'next_attempt_at', 'claimed_until',
        'sent_at', 'failed_at', 'response_code', 'last_error_code',
    ];

    protected function casts(): array
    {
        return [
            'next_attempt_at' => 'immutable_datetime',
            'claimed_until' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(PushSubscription::class, 'push_subscription_id');
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(UserNotification::class, 'user_notification_id');
    }
}
