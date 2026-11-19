<?php

namespace App\Support\Lavbe;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class FeedCursor
{
    private const PURPOSE = 'lavbe-feed-cursor-v1';

    public function issue(string $locale, ?string $category, string $order, int $offset): string
    {
        return rawurlencode(Crypt::encryptString(json_encode([
            'purpose' => self::PURPOSE,
            'v' => 1,
            'locale' => $locale,
            'category' => $category,
            'order' => $order,
            'offset' => $offset,
            'expires' => now()->addMinutes((int) config('lavbe.feed_cursor_ttl_minutes'))->timestamp,
        ], JSON_THROW_ON_ERROR)));
    }

    /** @return array{locale:string,category:?string,order:string,offset:int} */
    public function read(string $token, string $locale, ?string $category, string $order): array
    {
        try {
            $payload = json_decode(Crypt::decryptString(rawurldecode($token)), true, 8, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            abort(400);
        }

        abort_unless(is_array($payload)
            && ($payload['purpose'] ?? null) === self::PURPOSE
            && ($payload['v'] ?? null) === 1
            && ($payload['locale'] ?? null) === $locale
            && ($payload['category'] ?? null) === $category
            && ($payload['order'] ?? null) === $order
            && is_int($payload['offset'] ?? null)
            && $payload['offset'] >= 0
            && is_int($payload['expires'] ?? null), 400);
        abort_if($payload['expires'] < now()->timestamp, 410);

        return ['locale' => $locale, 'category' => $category, 'order' => $order, 'offset' => $payload['offset']];
    }
}
