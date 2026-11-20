<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Support\Push\PushEndpointPolicy;
use App\Support\Push\WebPushConfiguration;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PushSubscriptionController extends Controller
{
    public function status(Request $request, string $locale, PushEndpointPolicy $policy): JsonResponse
    {
        $data = Validator::make($request->all(), ['endpoint' => ['required', 'string', 'max:2048']])->validate();
        $endpoint = (string) $data['endpoint'];
        abort_unless($policy->allows($endpoint), 422);
        $endpointHash = hash_hmac('sha256', $endpoint, (string) config('app.key'));
        $active = PushSubscription::query()->where('user_id', $request->user()->id)
            ->where('endpoint_hash', $endpointHash)->where('active', true)->exists();

        return response()->json(['active' => $active])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request, string $locale, PushEndpointPolicy $policy, WebPushConfiguration $configuration): JsonResponse
    {
        abort_unless($configuration->isReady(), 404);

        $data = Validator::make($request->all(), [
            'endpoint' => ['required', 'string', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:128'],
            'keys.auth' => ['required', 'string', 'max:128'],
        ])->validate();

        $endpoint = (string) $data['endpoint'];
        abort_unless($policy->allows($endpoint), 422);
        abort_unless($policy->decodeKey((string) data_get($data, 'keys.p256dh'), 65) !== null
            && $policy->decodeKey((string) data_get($data, 'keys.auth'), 16) !== null, 422);

        $endpointHash = hash_hmac('sha256', $endpoint, (string) config('app.key'));
        $attributes = [
            'user_id' => $request->user()->id,
            'endpoint' => $endpoint,
            'p256dh' => (string) data_get($data, 'keys.p256dh'),
            'auth' => (string) data_get($data, 'keys.auth'),
            'active' => true,
            'expires_at' => null,
            'activated_at' => now(),
            'last_used_at' => now(),
        ];

        try {
            DB::transaction(function () use ($endpointHash, $attributes): void {
                $subscription = PushSubscription::query()->firstOrCreate(['endpoint_hash' => $endpointHash], $attributes);
                $subscription = PushSubscription::query()->whereKey($subscription->id)->lockForUpdate()->firstOrFail();
                $subscription->forceFill($attributes)->save();
            }, 3);
        } catch (QueryException $exception) {
            $subscription = PushSubscription::query()->where('endpoint_hash', $endpointHash)->first();
            if (! $subscription) {
                throw $exception;
            }
            $subscription->forceFill($attributes)->save();
        }

        return response()->json(['status' => 'subscribed'], 201)
            ->header('Cache-Control', 'no-store');
    }

    public function destroy(Request $request, string $locale, PushEndpointPolicy $policy): Response
    {
        $data = Validator::make($request->all(), [
            'endpoint' => ['required', 'string', 'max:2048'],
        ])->validate();
        $endpoint = (string) $data['endpoint'];
        abort_unless($policy->allows($endpoint), 422);
        $endpointHash = hash_hmac('sha256', $endpoint, (string) config('app.key'));

        PushSubscription::query()->where('user_id', $request->user()->id)
            ->where('endpoint_hash', $endpointHash)->update(['active' => false, 'updated_at' => now()]);

        return response()->noContent()->header('Cache-Control', 'no-store');
    }
}
