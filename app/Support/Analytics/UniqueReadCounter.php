<?php

namespace App\Support\Analytics;

use App\Models\Confession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Cookie;

class UniqueReadCounter
{
    public const COOKIE_NAME = 'lavbe_reader_id';

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
            max(1, (int) config('analytics.unique_read_retention_days')) * 1440,
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
            $now = now();
            $inserted = DB::table('confession_readers')->insertOrIgnore([
                'confession_id' => $confession->getKey(),
                'reader_hash' => $readerHash,
                'last_seen_at' => $now,
            ]);

            if ($inserted !== 1) {
                DB::table('confession_readers')
                    ->where('confession_id', $confession->getKey())
                    ->where('reader_hash', $readerHash)
                    ->update(['last_seen_at' => $now]);

                return;
            }

            $counted = DB::table('confessions')
                ->where('id', $confession->getKey())
                ->where('status', 'public')
                ->whereNotNull('published_at')
                ->whereNotNull('public_revision_id')
                ->whereExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('confession_revisions')
                        ->whereColumn('confession_revisions.id', 'confessions.public_revision_id')
                        ->whereColumn('confession_revisions.confession_id', 'confessions.id')
                        ->where('confession_revisions.status', 'approved');
                })
                ->increment('unique_read_count');

            if ($counted !== 1) {
                DB::table('confession_readers')
                    ->where('confession_id', $confession->getKey())
                    ->where('reader_hash', $readerHash)
                    ->delete();

                return;
            }

            $confession->setAttribute('unique_read_count', (int) $confession->unique_read_count + 1);
        }, max(1, (int) config('analytics.unique_read_transaction_attempts')));

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
