<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ModerationAppeal extends Model
{
    protected $fillable = [
        'target_type', 'target_id', 'confession_id', 'hide_audit_id', 'owner_hash', 'idempotency_hash',
        'payload_fingerprint', 'details', 'status', 'resolution', 'reviewed_by_user_id', 'resolution_audit_id',
        'lock_version', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'encrypted',
            'lock_version' => 'integer',
            'reviewed_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $appeal): void {
            foreach (['target_type', 'target_id', 'confession_id', 'hide_audit_id', 'owner_hash', 'idempotency_hash', 'payload_fingerprint', 'details'] as $field) {
                if ($appeal->isDirty($field)) {
                    throw new LogicException('Moderation appeal submissions are immutable.');
                }
            }
        });
    }

    public function confession(): BelongsTo
    {
        return $this->belongsTo(Confession::class);
    }

    public function hideAudit(): BelongsTo
    {
        return $this->belongsTo(ModerationAudit::class, 'hide_audit_id');
    }
}
