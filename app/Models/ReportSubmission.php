<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSubmission extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'report_group_id', 'reporter_hash', 'idempotency_hash', 'payload_fingerprint', 'reason_code', 'details', 'created_at',
    ];

    protected function casts(): array
    {
        return ['details' => 'encrypted', 'created_at' => 'immutable_datetime'];
    }

    public function reportGroup(): BelongsTo
    {
        return $this->belongsTo(ReportGroup::class);
    }
}
