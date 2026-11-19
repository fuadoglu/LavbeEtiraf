<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ModerationAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_user_id', 'grant_snapshot', 'target_type', 'target_key', 'target_public_id', 'revision_id',
        'decision', 'reason_code', 'reason', 'internal_note_hash', 'author_message_hash', 'previous_state',
        'new_state', 'expected_lock_version', 'resulting_lock_version', 'previous_public_revision_hash',
        'new_public_revision_hash', 'idempotency_hash', 'idempotency_fingerprint', 'request_id', 'created_at',
    ];

    protected function casts(): array
    {
        return ['grant_snapshot' => 'array', 'created_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Moderation audit records are append-only.'));
        static::deleting(fn () => throw new LogicException('Moderation audit records are append-only.'));
    }
}
