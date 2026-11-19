<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfessionParticipant extends Model
{
    protected $fillable = ['confession_id', 'participant_hash', 'author_alias'];

    public function confession(): BelongsTo
    {
        return $this->belongsTo(Confession::class);
    }
}
