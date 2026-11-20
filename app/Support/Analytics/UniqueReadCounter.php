<?php

namespace App\Support\Analytics;

use App\Models\Confession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Cookie;

class UniqueReadCounter
{
    public const COOKIE_NAME = 'lavbe_reader_id';

    private const COOKIE_MINUTES = 525600;

    public function record(Request $request, Confession $confession): ?Cookie
    {
        if ($this->isPrefetch($request)) {
            return null;
        }

        $readerId = $request->cookie(self::COOKIE_NAME);

        if (! is_string($readerId) || ! preg_match('/\A[a-f0-9]{64}\z/i', $readerId)) {
            $readerId = bin2hex(random_bytes(32));
        }

        $cookie = cookie(
            self::COOKIE_NAME,
            $readerId,
            self::COOKIE_MINUTES,
            '/',
            null,
            $request->isSecure() || (bool) config('session.secure'),
            true,
            false,
            'lax',
        );

        $readerHash = hash_hmac(
            'sha256',
            $confession->getKey().'|'.$readerId,
            (string) config('app.key'),
        );

        DB::transaction(function () use ($confession, $readerHash): void {
            $currentConfession = DB::table('confessions')
                ->where('id', $confession->getKey())
                ->lockForUpdate()
                ->first(['status', 'unique_read_count']);

            if (! $currentConfession) {
                return;
            }

            $currentCount = (int) $currentConfession->unique_read_count;
            $confession->setAttribute('unique_read_count', $currentCount);

            if ($currentConfession->status !== 'public') {
                return;
            }

            $inserted = DB::table('confession_readers')->insertOrIgnore([
                'confession_id' => $confession->getKey(),
                'reader_hash' => $readerHash,
            ]);

            if ($inserted === 1) {
                DB::table('confessions')->where('id', $confession->getKey())->increment('unique_read_count');
                $currentCount++;
            }

            $confession->setAttribute('unique_read_count', $currentCount);
        });

        return $cookie;
    }

    private function isPrefetch(Request $request): bool
    {
        $purpose = $request->header('Purpose', '').' '.$request->header('Sec-Purpose', '');
        $userAgent = $request->userAgent() ?? '';

        return preg_match('/prefetch|prerender/i', $purpose) === 1
            || preg_match('/bot|crawler|spider|preview|headless|lighthouse|pagespeed/i', $userAgent) === 1;
    }
}
