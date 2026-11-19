<?php

namespace App\Support\Push;

use App\Models\WebPushDelivery;
use App\Support\Lavbe\LocalePackageMessages;
use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class WebPushDeliveryDispatcher
{
    public function __construct(
        private readonly WebPushConfiguration $configuration,
        private readonly WebPushGateway $gateway,
        private readonly LocaleRegistry $locales,
        private readonly LocalePackageMessages $messages,
    ) {}

    public function dispatch(int $limit = 10): array
    {
        if (! $this->configuration->isReady()) {
            return ['sent' => 0, 'retried' => 0, 'failed' => 0, 'skipped' => 'configuration'];
        }

        $sent = $retried = $failed = 0;
        $items = [];
        foreach ($this->candidates(max(1, min(50, $limit))) as $candidate) {
            $delivery = $this->claim($candidate);
            if (! $delivery) {
                continue;
            }
            $subscription = $delivery->subscription;
            $user = $subscription?->user;
            $notification = $delivery->notification;
            if (! $subscription || ! $subscription->active || ! $user || ! $user->hasVerifiedEmail()
                || ! $notification || (int) $notification->user_id !== (int) $subscription->user_id
                || ! $subscription->activated_at || $subscription->activated_at->isAfter($notification->created_at)) {
                $this->fail($delivery, 'subscription_inactive');
                $failed++;

                continue;
            }

            $locale = $this->locales->resolveTag((string) $user->preferred_locale);
            if (! $locale) {
                $this->fail($delivery, 'invalid_locale');
                $failed++;

                continue;
            }

            try {
                $body = $this->messages->text($locale['segment'], 'notifications', 'generic');
            } catch (Throwable) {
                $this->fail($delivery, 'locale_unavailable');
                $failed++;

                continue;
            }

            $items[] = [
                'delivery' => $delivery,
                'subscription' => $subscription,
                'payload' => ['title' => 'Lavbe', 'body' => $body, 'locale' => $locale['segment']],
            ];
        }

        if ($items === []) {
            return compact('sent', 'retried', 'failed');
        }

        try {
            $results = $this->gateway->send(array_map(fn (array $item): array => [
                'delivery_id' => $item['delivery']->id,
                'subscription' => $item['subscription'],
                'payload' => $item['payload'],
            ], $items));
            foreach ($items as $item) {
                /** @var WebPushDelivery $delivery */
                $delivery = $item['delivery'];
                $result = $results[$delivery->id] ?? null;
                if (($result['success'] ?? false) === true) {
                    $delivery->forceFill([
                        'sent_at' => now(), 'claimed_until' => null, 'next_attempt_at' => null,
                        'response_code' => $result['status'], 'last_error_code' => null,
                    ])->save();
                    $item['subscription']->forceFill(['last_used_at' => now()])->save();
                    $sent++;
                } elseif (($result['expired'] ?? false) === true) {
                    $item['subscription']->forceFill(['active' => false])->save();
                    $this->fail($delivery, 'subscription_expired', $result['status'] ?? null);
                    $failed++;
                } elseif (($result['status'] ?? null) === 429 || ($result['status'] ?? 0) >= 500 || $result === null) {
                    $this->retry($delivery, $result === null ? 'provider_no_result' : 'http_'.($result['status'] ?? 'unknown'), $result['status'] ?? null);
                    if ($delivery->fresh()->failed_at) {
                        $failed++;
                    } else {
                        $retried++;
                    }
                } else {
                    $this->fail($delivery, 'http_'.($result['status'] ?? 'unknown'), $result['status'] ?? null);
                    $failed++;
                }
            }
        } catch (Throwable) {
            foreach ($items as $item) {
                $delivery = $item['delivery'];
                $this->retry($delivery, 'provider_transport');
                if ($delivery->fresh()->failed_at) {
                    $failed++;
                } else {
                    $retried++;
                }
            }
        }

        return compact('sent', 'retried', 'failed');
    }

    /** @return Collection<int, WebPushDelivery> */
    private function candidates(int $limit)
    {
        return WebPushDelivery::query()->with(['subscription.user', 'notification'])
            ->whereNull('sent_at')->whereNull('failed_at')
            ->where(function (Builder $query): void {
                $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('claimed_until')->orWhere('claimed_until', '<=', now());
            })->orderBy('id')->limit($limit)->get();
    }

    private function claim(WebPushDelivery $candidate): ?WebPushDelivery
    {
        return DB::transaction(function () use ($candidate): ?WebPushDelivery {
            $delivery = WebPushDelivery::query()->whereKey($candidate->id)->lockForUpdate()->first();
            if (! $delivery || $delivery->sent_at || $delivery->failed_at
                || ($delivery->next_attempt_at && $delivery->next_attempt_at->isFuture())
                || ($delivery->claimed_until && $delivery->claimed_until->isFuture())) {
                return null;
            }
            $delivery->forceFill([
                'attempts' => $delivery->attempts + 1,
                'claimed_until' => now()->addMinutes(2),
                'next_attempt_at' => null,
            ])->save();

            return $delivery->fresh(['subscription.user', 'notification']);
        }, 3);
    }

    private function retry(WebPushDelivery $delivery, string $errorCode, ?int $responseCode = null): void
    {
        $attempts = (int) $delivery->attempts;
        if ($attempts >= (int) config('services.webpush.max_attempts', 8)) {
            $this->fail($delivery, $errorCode, $responseCode);

            return;
        }
        $delay = min(21600, 60 * (2 ** min(8, max(0, $attempts - 1))));
        $delivery->forceFill([
            'claimed_until' => null, 'next_attempt_at' => now()->addSeconds($delay),
            'response_code' => $responseCode, 'last_error_code' => $errorCode,
        ])->save();
    }

    private function fail(WebPushDelivery $delivery, string $errorCode, ?int $responseCode = null): void
    {
        $delivery->forceFill([
            'claimed_until' => null, 'next_attempt_at' => null, 'failed_at' => now(),
            'response_code' => $responseCode, 'last_error_code' => $errorCode,
        ])->save();
    }
}
