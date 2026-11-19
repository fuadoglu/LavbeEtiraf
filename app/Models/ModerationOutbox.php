<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModerationOutbox extends Model
{
    protected $table = 'moderation_outbox';

    protected $fillable = [
        'event_key', 'target_public_id', 'event_type', 'visibility_version', 'revision_id', 'revision_hash',
        'locale', 'category_key', 'occurred_at', 'dispatched_at', 'attempts', 'last_error_code',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'immutable_datetime', 'dispatched_at' => 'immutable_datetime'];
    }
}
