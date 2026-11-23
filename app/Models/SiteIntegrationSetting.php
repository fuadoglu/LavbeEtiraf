<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteIntegrationSetting extends Model
{
    protected $fillable = [
        'ga4_enabled', 'ga4_measurement_id', 'ads_enabled', 'adsense_client_id',
        'story_slot_enabled', 'story_slot_id', 'updated_by_user_id', 'lock_version',
    ];

    protected function casts(): array
    {
        return [
            'ga4_enabled' => 'boolean', 'ads_enabled' => 'boolean',
            'story_slot_enabled' => 'boolean', 'lock_version' => 'integer',
        ];
    }

    public static function current(): self
    {
        return self::query()->find(1) ?? new self([
            'ga4_enabled' => false, 'ads_enabled' => false, 'story_slot_enabled' => false,
            'lock_version' => 0,
        ]);
    }
}
