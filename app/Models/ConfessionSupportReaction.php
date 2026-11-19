<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfessionSupportReaction extends Model
{
    protected $fillable = ['confession_id', 'participant_hash', 'is_active', 'last_idempotency_hash', 'last_payload_fingerprint'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function confession(): BelongsTo
    {
        return $this->belongsTo(Confession::class);
    }
}
