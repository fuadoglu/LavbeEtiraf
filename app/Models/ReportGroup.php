<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportGroup extends Model
{
    protected $fillable = [
        'target_type', 'target_id', 'confession_id', 'channel', 'status', 'priority', 'report_count',
        'last_reason_code', 'lock_version', 'resolved_by_user_id', 'resolution', 'first_reported_at', 'last_reported_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'report_count' => 'integer',
            'lock_version' => 'integer',
            'first_reported_at' => 'immutable_datetime',
            'last_reported_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
        ];
    }

    public function confession(): BelongsTo
    {
        return $this->belongsTo(Confession::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ReportSubmission::class);
    }
}
