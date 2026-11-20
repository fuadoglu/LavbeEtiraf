<?php

namespace App\Http\Controllers;

use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Support\Moderation\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function create(Request $request, string $locale, string $type, string $targetPublicId): Response
    {
        [$confession, $comment] = $this->target($locale, $type, $targetPublicId);

        return response()->view('reports.create', [
            'channel' => 'community', 'confession' => $confession, 'comment' => $comment,
            'targetType' => $type, 'targetPublicId' => $targetPublicId,
            'idempotencyKey' => bin2hex(random_bytes(16)),
        ])->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function legalNotice(Request $request, string $locale, string $type, string $targetPublicId): Response
    {
        [$confession, $comment] = $this->target($locale, $type, $targetPublicId);

        return response()->view('reports.create', [
            'channel' => 'legal', 'confession' => $confession, 'comment' => $comment,
            'targetType' => $type, 'targetPublicId' => $targetPublicId,
            'idempotencyKey' => bin2hex(random_bytes(16)),
        ])->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function store(Request $request, string $locale, string $channel, string $type, string $targetPublicId, ReportService $reports): Response
    {
        abort_unless(in_array($channel, ['community', 'legal'], true), 404);
        if ($limited = $this->rateLimited($request, $channel === 'legal' ? 'legal-notice' : 'report')) {
            return $limited;
        }
        $reasonRule = $channel === 'legal'
            ? ['required', 'in:privacy,copyright,court_order,other']
            : ['required', 'in:spam,privacy,harassment,threat,explicit,other'];
        $data = Validator::make($request->all(), [
            'reason_code' => $reasonRule,
            'details' => ['nullable', 'string', 'max:'.config('lavbe.limits.report_details')],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
        ])->validate();
        abort_unless(trim((string) ($data['details'] ?? '')) !== '' || $channel !== 'legal', 422);

        [$confession, $comment] = $this->target($locale, $type, $targetPublicId);
        $reports->submit(
            $request, $type, (int) ($comment?->id ?? $confession->id), $channel,
            $data['reason_code'], $data['details'] ?? null, $data['idempotency_key'],
        );

        return response()->view('reports.submitted', ['channel' => $channel], 202)
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer');
    }

    /** @return array{0: Confession, 1: ?ConfessionComment} */
    private function target(string $locale, string $type, string $publicId): array
    {
        abort_unless(in_array($type, ['confession', 'comment'], true), 404);
        if ($type === 'confession') {
            $confession = Confession::query()->where('public_id', $publicId)->where('locale', $locale)->public()->firstOrFail();

            return [$confession, null];
        }
        $comment = ConfessionComment::query()->with('confession')
            ->where('public_id', $publicId)->where('status', 'public')
            ->whereHas('confession', fn ($query) => $query->where('locale', $locale)->public())
            ->whereHas('publicRevision', fn ($query) => $query->where('status', 'approved'))
            ->firstOrFail();

        return [$comment->confession, $comment];
    }

    private function rateLimited(Request $request, string $action): ?Response
    {
        $limit = config('lavbe.rate_limits.actions.'.($action === 'legal-notice' ? 'legal_notice' : 'report'));
        $key = 'moderation-report:'.hash_hmac('sha256', $action.'|'.(string) $request->ip(), (string) config('app.key'));
        if (RateLimiter::tooManyAttempts($key, $limit['attempts'])) {
            return response()->view('confessions.limited', [], 429)
                ->header('Retry-After', (string) RateLimiter::availableIn($key))
                ->header('Cache-Control', 'private, no-store, max-age=0')
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }
        RateLimiter::hit($key, $limit['decay_seconds']);

        return null;
    }
}
