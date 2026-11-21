<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipationOutbox extends Model
{
    protected $table = 'participation_outbox';

    protected $fillable = ['event_key', 'confession_id', 'event_type', 'visibility_version', 'target_id', 'follower_cursor_id', 'occurred_at', 'dispatched_at', 'attempts', 'last_error_code'];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'immutable_datetime',
            'dispatched_at' => 'immutable_datetime',
            'visibility_version' => 'integer',
            'attempts' => 'integer',
            'follower_cursor_id' => 'integer',
        ];
    }

    public function confession(): BelongsTo
    {
        return $this->belongsTo(Confession::class);
    }
}
