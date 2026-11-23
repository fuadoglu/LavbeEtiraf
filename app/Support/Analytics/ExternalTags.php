<?php

namespace App\Support\Analytics;

use App\Models\Confession;
use App\Models\SiteIntegrationSetting;
use Illuminate\Http\Request;

class ExternalTags
{
    public const ANALYTICS_COOKIE = 'lavbe_analytics_consent';

    public const ADS_COOKIE = 'lavbe_ads_consent';

    public function analytics(Request $request): ?string
    {
        if (! config('external_tags.ready')) {
            return null;
        }
        $settings = SiteIntegrationSetting::current();

        return $settings->ga4_enabled && $request->cookie(self::ANALYTICS_COOKIE) === 'accepted'
            ? $settings->ga4_measurement_id : null;
    }

    public function adSlot(Request $request, Confession $confession, bool $ownerCanParticipate): ?array
    {
        if (! config('external_tags.ready')) {
            return null;
        }
        $settings = SiteIntegrationSetting::current();
        if (! $settings->ads_enabled || ! $settings->story_slot_enabled
            || ! $settings->adsense_client_id || ! $settings->story_slot_id
            || $request->cookie(self::ADS_COOKIE) !== 'accepted'
            || $confession->locale === 'az' || $confession->locale === 'ru'
            || $confession->status !== 'public'
            || $confession->reviews_open
            || $ownerCanParticipate
            || $confession->ad_review_visibility_version === null
            || (int) $confession->ad_review_visibility_version !== (int) $confession->visibility_version) {
            return null;
        }

        return ['client' => $settings->adsense_client_id, 'slot' => $settings->story_slot_id];
    }
}
