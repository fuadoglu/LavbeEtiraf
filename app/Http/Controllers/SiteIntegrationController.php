<?php

namespace App\Http\Controllers;

use App\Models\Confession;
use App\Models\ModerationAudit;
use App\Models\SiteIntegrationSetting;
use App\Support\Http\ResponseHeaders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SiteIntegrationController extends Controller
{
    public function edit(): Response|View
    {
        return $this->privateView('moderation.site-settings', ['settings' => SiteIntegrationSetting::current()]);
    }

    public function update(Request $request, string $locale): Response
    {
        $data = Validator::make($request->all(), [
            'ga4_measurement_id' => ['nullable', 'string', 'regex:/^G-[A-Z0-9]{6,20}$/D'],
            'adsense_client_id' => ['nullable', 'string', 'regex:/^ca-pub-[0-9]{16}$/D'],
            'story_slot_id' => ['nullable', 'string', 'regex:/^[0-9]{6,20}$/D'],
            'ga4_enabled' => ['sometimes', 'accepted'],
            'ads_enabled' => ['sometimes', 'accepted'],
            'story_slot_enabled' => ['sometimes', 'accepted'],
            'expected_lock_version' => ['required', 'integer', 'min:0'],
        ])->validate();
        $values = [
            'ga4_enabled' => $request->boolean('ga4_enabled'),
            'ga4_measurement_id' => $data['ga4_measurement_id'] ?? null,
            'ads_enabled' => $request->boolean('ads_enabled'),
            'adsense_client_id' => $data['adsense_client_id'] ?? null,
            'story_slot_enabled' => $request->boolean('story_slot_enabled'),
            'story_slot_id' => $data['story_slot_id'] ?? null,
        ];
        abort_if($values['ga4_enabled'] && ! $values['ga4_measurement_id'], 422);
        abort_if(($values['ads_enabled'] || $values['story_slot_enabled'])
            && (! $values['adsense_client_id'] || ! $values['story_slot_id']), 422);

        DB::transaction(function () use ($request, $values, $data): void {
            $settings = SiteIntegrationSetting::query()->whereKey(1)->lockForUpdate()->firstOrFail();
            abort_if($settings->lock_version !== (int) $data['expected_lock_version'], 409);
            $before = $settings->only(array_keys($values));
            $settings->forceFill($values + [
                'updated_by_user_id' => $request->user()->id,
                'lock_version' => $settings->lock_version + 1,
            ])->save();
            ModerationAudit::query()->create([
                'actor_user_id' => $request->user()->id,
                'grant_snapshot' => ['role' => 'admin', 'locale' => '*'],
                'target_type' => 'site_setting', 'target_key' => 'integrations',
                'decision' => 'update_settings', 'reason_code' => 'administrative_configuration',
                'reason' => implode(', ', array_keys(array_diff_assoc($values, $before))),
                'previous_state' => 'version_'.(int) ($settings->lock_version - 1),
                'new_state' => 'version_'.$settings->lock_version,
                'request_id' => (string) Str::uuid(), 'created_at' => now(),
            ]);
        }, (int) config('database.transaction_retries'));

        return redirect()->route('moderation.settings', ['locale' => $locale])->with('status', __('moderation.settings_saved'));
    }

    public function reviewAdSuitability(Request $request, string $locale, int $confessionId): Response
    {
        $data = $request->validate([
            'eligible' => ['required', 'in:0,1'],
            'expected_visibility_version' => ['required', 'integer', 'min:1'],
        ]);
        DB::transaction(function () use ($request, $confessionId, $data): void {
            $confession = Confession::query()->whereKey($confessionId)->lockForUpdate()->firstOrFail();
            abort_unless($confession->status === 'public' && $confession->public_revision_id, 409);
            abort_unless($confession->visibility_version === (int) $data['expected_visibility_version'], 409);
            $confession->ad_review_visibility_version = $data['eligible'] === '1' ? $confession->visibility_version : null;
            $confession->save();
            ModerationAudit::query()->create([
                'actor_user_id' => $request->user()->id,
                'grant_snapshot' => ['role' => 'admin', 'locale' => '*'],
                'target_type' => 'confession', 'target_key' => (string) $confession->id,
                'target_public_id' => $confession->public_id,
                'revision_id' => $confession->public_revision_id,
                'decision' => $data['eligible'] === '1' ? 'ads_eligible' : 'ads_ineligible',
                'reason_code' => 'advertising_review',
                'previous_state' => 'ads_review', 'new_state' => $data['eligible'] === '1' ? 'eligible' : 'ineligible',
                'request_id' => (string) Str::uuid(), 'created_at' => now(),
            ]);
        }, (int) config('database.transaction_retries'));

        return redirect()->route('moderation.show', ['locale' => $locale, 'confessionId' => $confessionId])
            ->with('status', __('moderation.settings_saved'));
    }

    private function privateView(string $view, array $data): Response
    {
        return response()->view($view, $data)
            ->header('Cache-Control', ResponseHeaders::CACHE_PRIVATE_NO_STORE)
            ->header('X-Robots-Tag', ResponseHeaders::ROBOTS_PRIVATE)
            ->header('Referrer-Policy', ResponseHeaders::REFERRER_NO_REFERRER);
    }
}
