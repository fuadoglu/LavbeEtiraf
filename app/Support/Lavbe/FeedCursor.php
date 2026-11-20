<?php

namespace App\Support\Lavbe;

use App\Models\Confession;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class FeedCursor
{
    private const PURPOSE = 'lavbe-feed-cursor';

    public function issue(string $locale, ?string $category, string $order, int $offset): string
    {
        $asOf = now();
        $members = app(FeedQuery::class)->build($locale, $category, $order)
            ->where('confessions.published_at', '<=', $asOf)
            ->whereNotNull('confessions.publication_sequence')
            ->select(['confessions.id', 'confessions.publication_sequence', 'confessions.published_at'])
            ->limit(max((int) config('feed.page_size') + 1, (int) config('feed.snapshot_max_members')))
            ->get()->map(fn (Confession $item): array => [
                'id' => $item->id,
                'sequence' => $item->publication_sequence,
                'published' => $item->published_at->format('Y-m-d H:i:s'),
            ])->all();
        $snapshot = (string) Str::uuid();
        $expires = now()->addMinutes((int) config('feed.cursor_ttl_minutes'))->timestamp;
        Cache::put('lavbe:feed:'.$snapshot, $members, now()->setTimestamp($expires));

        return $this->encode([
            'purpose' => self::PURPOSE, 'v' => 2,
            'locale' => $locale, 'category' => $category, 'order' => $order,
            'snapshot' => $snapshot, 'offset' => $offset,
            'as_of' => $asOf->format('Y-m-d H:i:s'),
            'upper_sequence' => max(array_column($members, 'sequence') ?: [0]),
            'last' => $offset > 0 ? ($members[$offset - 1] ?? null) : null,
            'expires' => $expires,
        ]);
    }

    public function read(string $token, string $locale, ?string $category, string $order): array
    {
        try {
            $payload = json_decode(Crypt::decryptString(rawurldecode($token)), true, 8, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            abort(400);
        }
        abort_unless(is_array($payload), 400);
        if (($payload['purpose'] ?? null) === 'lavbe-feed-cursor-v1') {
            abort(410);
        }
        abort_unless(($payload['purpose'] ?? null) === self::PURPOSE
            && ($payload['v'] ?? null) === 2
            && ($payload['locale'] ?? null) === $locale
            && ($payload['category'] ?? null) === $category
            && ($payload['order'] ?? null) === $order
            && is_string($payload['snapshot'] ?? null)
            && Str::isUuid($payload['snapshot'])
            && is_int($payload['offset'] ?? null) && $payload['offset'] >= 0
            && is_int($payload['expires'] ?? null)
            && is_string($payload['as_of'] ?? null)
            && is_int($payload['upper_sequence'] ?? null), 400);
        abort_if($payload['expires'] <= now()->timestamp, 410);

        return $payload;
    }

    /** Return current approved content in the session's original order. */
    public function page(string $token, string $locale, ?string $category, string $order, int $size): array
    {
        $state = $this->read($token, $locale, $category, $order);
        $members = Cache::get('lavbe:feed:'.$state['snapshot']);
        abort_unless(is_array($members), 410);
        $items = new Collection;
        $position = $state['offset'];
        $last = $state['last'];
        $hasMore = false;

        // Read bounded groups and keep scanning when hidden/deleted entries leave gaps.
        while ($position < count($members)) {
            $batch = array_slice($members, $position, $size + 1);
            $current = app(FeedQuery::class)->build($locale, $category, $order)
                ->whereIn('confessions.id', array_column($batch, 'id'))->get()->keyBy('id');
            foreach ($batch as $member) {
                $item = $current->get($member['id']);
                $visible = $item && $item->publication_sequence === $member['sequence']
                    && $member['sequence'] <= $state['upper_sequence'];
                $after = $order !== 'new' || $state['last'] === null
                    || [$member['published'], $member['id']] < [$state['last']['published'], $state['last']['id']];
                if ($visible && $after) {
                    if ($items->count() === $size) {
                        $hasMore = true;
                        break 2;
                    }
                    $items->push($item);
                    $last = $member;
                }
                $position++;
            }
        }

        $state['offset'] = $position;
        $state['last'] = $last;

        return [
            'items' => $items, 'has_more' => $hasMore,
            'next_cursor' => $hasMore ? $this->encode($state) : null,
            'total' => count($members),
        ];
    }

    private function encode(array $state): string
    {
        return rawurlencode(Crypt::encryptString(json_encode($state, JSON_THROW_ON_ERROR)));
    }
}
