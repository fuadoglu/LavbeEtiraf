<?php

namespace App\Support\Search;

use App\Models\Confession;
use App\Models\IndexNowDelivery;
use App\Models\ModerationOutbox;
use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class IndexNowDispatcher
{
    private const EVENT_TYPES = ['publish', 'hide', 'restore', 'terminal_delete'];

    public function dispatch(?int $limit = null): array
    {
        if (! $this->configurationIsValid()) {
            return ['delivered' => 0, 'retried' => 0, 'failed' => 0, 'skipped' => 'configuration'];
        }

        $delivered = $retried = $failed = 0;
        $batch = [];
        $limit = max(1, min((int) config('delivery.indexnow.batch_max'), $limit ?? (int) config('delivery.indexnow.batch_size')));
        foreach ($this->candidates($limit) as $event) {
            $delivery = $this->claim($event);
            if (! $delivery) {
                continue;
            }

            $url = $this->eventUrl($event);
            if ($url === null) {
                $this->failPermanently($delivery, 'invalid_target');
                $failed++;

                continue;
            }

            $batch[] = ['delivery' => $delivery, 'url' => $url];
        }

        if ($batch === []) {
            return compact('delivered', 'retried', 'failed');
        }

        try {
            $response = Http::acceptJson()->asJson()->connectTimeout((int) config('delivery.indexnow.connect_timeout_seconds'))->timeout((int) config('delivery.indexnow.timeout_seconds'))->post('https://api.indexnow.org/indexnow', [
                'host' => $this->host(),
                'key' => (string) config('services.indexnow.key'),
                'urlList' => array_column($batch, 'url'),
            ]);
            $code = $response->status();

            foreach ($batch as $item) {
                /** @var IndexNowDelivery $delivery */
                $delivery = $item['delivery'];
                if (in_array($code, [200, 202], true)) {
                    $delivery->forceFill([
                        'delivered_at' => now(), 'claimed_until' => null, 'next_attempt_at' => null,
                        'response_code' => $code, 'last_error_code' => null,
                    ])->save();
                    $delivered++;
                } elseif ($code === 429 || $code >= 500) {
                    $this->retry($delivery, 'http_'.$code, $code);
                    if ($delivery->fresh()->failed_at) {
                        $failed++;
                    } else {
                        $retried++;
                    }
                } else {
                    $this->failPermanently($delivery, 'http_'.$code, $code);
                    $failed++;
                }
            }
        } catch (Throwable $exception) {
            $errorCode = str_contains(strtolower($exception::class), 'timeout') ? 'timeout' : 'connection';
            foreach ($batch as $item) {
                /** @var IndexNowDelivery $delivery */
                $delivery = $item['delivery'];
                if ((int) $delivery->attempts >= (int) config('services.indexnow.max_attempts', 8)) {
                    $this->failPermanently($delivery, $errorCode);
                    $failed++;
                } else {
                    $this->retry($delivery, $errorCode);
                    if ($delivery->fresh()->failed_at) {
                        $failed++;
                    } else {
                        $retried++;
                    }
                }
            }
        }

        return compact('delivered', 'retried', 'failed');
    }

    /** @return Collection<int, ModerationOutbox> */
    private function candidates(int $limit)
    {
        return ModerationOutbox::query()
            ->whereIn('event_type', self::EVENT_TYPES)
            ->whereHas('indexNowDelivery', function (Builder $query): void {
                $query->whereNull('delivered_at')->whereNull('failed_at')
                    ->where(function (Builder $due): void {
                        $due->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
                    })
                    ->where(function (Builder $claim): void {
                        $claim->whereNull('claimed_until')->orWhere('claimed_until', '<=', now());
                    });
            })
            ->orWhere(function (Builder $query): void {
                $query->whereIn('event_type', self::EVENT_TYPES)->whereDoesntHave('indexNowDelivery');
            })
            ->orderBy('id')->limit($limit)->get();
    }

    private function claim(ModerationOutbox $event): ?IndexNowDelivery
    {
        return DB::transaction(function () use ($event): ?IndexNowDelivery {
            $delivery = IndexNowDelivery::query()->firstOrCreate(
                ['event_key' => $event->event_key],
                ['moderation_outbox_id' => $event->id, 'next_attempt_at' => now()],
            );
            $delivery = IndexNowDelivery::query()->whereKey($delivery->id)->lockForUpdate()->first();
            if (! $delivery || $delivery->delivered_at || $delivery->failed_at || ($delivery->next_attempt_at && $delivery->next_attempt_at->isFuture()) || ($delivery->claimed_until && $delivery->claimed_until->isFuture())) {
                return null;
            }

            $delivery->forceFill([
                'attempts' => $delivery->attempts + 1,
                'claimed_until' => now()->addSeconds((int) config('delivery.indexnow.claim_seconds')),
                'next_attempt_at' => null,
            ])->save();

            return $delivery;
        }, 3);
    }

    private function eventUrl(ModerationOutbox $event): ?string
    {
        $confession = Confession::query()->where('public_id', $event->target_public_id)->first();
        $locale = app(LocaleRegistry::class)->bySegment($event->locale);
        if (! $confession || ! $locale || $confession->locale !== $locale['code'] || (! $confession->ever_public && $confession->status !== 'public')) {
            return null;
        }

        return rtrim((string) config('app.url'), '/').'/'.$locale['segment'].'/confession/'.$confession->public_id;
    }

    private function configurationIsValid(): bool
    {
        $key = (string) config('services.indexnow.key');
        $parts = parse_url((string) config('app.url'));

        return preg_match('/^[A-Za-z0-9-]{8,128}$/D', $key) === 1
            && is_array($parts)
            && ($parts['scheme'] ?? null) === 'https'
            && ! empty($parts['host'])
            && empty($parts['user'])
            && empty($parts['pass'])
            && empty($parts['query'])
            && empty($parts['fragment'])
            && (($parts['path'] ?? '') === '' || ($parts['path'] ?? '') === '/');
    }

    private function host(): string
    {
        return (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    }

    private function retry(IndexNowDelivery $delivery, string $errorCode, ?int $responseCode = null): void
    {
        $attempts = (int) $delivery->attempts;
        if ($attempts >= (int) config('services.indexnow.max_attempts', 8)) {
            $this->failPermanently($delivery, $errorCode, $responseCode);

            return;
        }

        $delay = min(
            (int) config('delivery.indexnow.retry_max_seconds'),
            (int) config('delivery.indexnow.retry_initial_seconds') * (2 ** min((int) config('delivery.indexnow.retry_exponent_max'), max(0, $attempts - 1))),
        );
        $delivery->forceFill([
            'claimed_until' => null, 'next_attempt_at' => now()->addSeconds($delay),
            'response_code' => $responseCode, 'last_error_code' => $errorCode,
        ])->save();
    }

    private function failPermanently(IndexNowDelivery $delivery, string $errorCode, ?int $responseCode = null): void
    {
        $delivery->forceFill([
            'claimed_until' => null, 'next_attempt_at' => null, 'failed_at' => now(),
            'response_code' => $responseCode, 'last_error_code' => $errorCode,
        ])->save();
    }
}
