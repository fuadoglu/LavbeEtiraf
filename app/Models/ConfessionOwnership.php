<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfessionOwnership extends Model
{
    protected $fillable = ['confession_id', 'session_token_hash', 'owner_token_hash', 'recovery_token_hash', 'device_token_hash', 'credential_version'];

    public function confession(): BelongsTo
    {
        return $this->belongsTo(Confession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
