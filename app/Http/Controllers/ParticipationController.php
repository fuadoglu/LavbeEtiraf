<?php

namespace App\Http\Controllers;

use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Support\Http\ResponseHeaders;
use App\Support\Lavbe\LocaleRegistry;
use App\Support\Lavbe\RoutePatterns;
use App\Support\Participation\ParticipationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ParticipationController extends Controller
{
    public function supported(Request $request, string $locale, LocaleRegistry $locales): Response
    {
        abort_unless($locales->bySegment($locale), 404);
        $ids = $request->session()->get('lavbe_supported', []);
        $ids = is_array($ids) ? array_slice(array_values(array_unique(array_filter($ids, 'is_string'))), 0, (int) config('lavbe.limits.supported_session_items')) : [];
        $confessions = Confession::query()->public()
            ->whereIn('public_id', $ids)->with('publicRevision')->orderByDesc('published_at')->orderByDesc('id')
            ->paginate((int) config('lavbe.pagination.supported'))->withQueryString();
        abort_if($confessions->currentPage() > $confessions->lastPage(), 404);

        return response()->view('participation.supported', compact('confessions'))
            ->header('Cache-Control', ResponseHeaders::CACHE_PRIVATE_NO_STORE)
            ->header('X-Robots-Tag', ResponseHeaders::ROBOTS_PRIVATE)
            ->header('Referrer-Policy', ResponseHeaders::REFERRER_NO_REFERRER);
    }

    public function commentForm(string $locale, string $publicId, LocaleRegistry $locales): Response
    {
        $story = Confession::query()->public()->where('locale', $locales->contentCode($locale))
            ->where('public_id', $publicId)->firstOrFail();

        return response()->view('participation.comment-form', ['story' => $story])
            ->header('Cache-Control', ResponseHeaders::CACHE_PRIVATE_NO_STORE)
            ->header('X-Robots-Tag', ResponseHeaders::ROBOTS_PRIVATE)
            ->header('Referrer-Policy', ResponseHeaders::REFERRER_NO_REFERRER);
    }

    public function comment(Request $request, string $locale, string $publicId, ParticipationService $participation): Response
    {
        if ($limited = $this->limit($request, 'comment')) {
            return $limited;
        }
        $data = Validator::make($request->all(), [
            'body' => ['required', 'string', 'max:'.config('lavbe.limits.comment_request_body')],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
            'parent_public_id' => ['nullable', 'regex:/^'.RoutePatterns::UUID_V4.'$/'],
            'remember_device' => ['sometimes', 'accepted'],
        ])->validate();
        $body = trim((string) $data['body']);
        abort_if($body === '' || mb_strlen($body, 'UTF-8') > (int) config('lavbe.limits.comment_body'), 422);
        $deviceToken = $request->boolean('remember_device') ? $this->deviceToken($request) : null;
        $result = $participation->submitComment($request, $publicId, $body, $data['idempotency_key'], $data['parent_public_id'] ?? null, $deviceToken);
        $response = response()->view('participation.submitted', [
            'confession' => $result['confession'],
            'comment' => $result['comment'],
            'recoveryCode' => $result['recovery_code'],
        ], 201);
        if ($deviceToken !== null) {
            $response->headers->setCookie(cookie(
                'lavbe_device',
                $deviceToken,
                (int) config('lavbe.cookies.owner_minutes'),
                '/',
                null,
                $request->isSecure() || app()->environment('production'),
                true,
                false,
                'strict',
            ));
        }

        return $response;
    }

    public function commentReaction(Request $request, string $locale, string $publicId, string $commentPublicId, ParticipationService $participation): Response
    {
        if ($limited = $this->limit($request, 'comment_reaction')) {
            return $limited;
        }
        $data = Validator::make($request->all(), [
            'action' => ['required', 'in:react,unreact'],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
        ])->validate();
        $participation->setCommentReaction($request, $publicId, $commentPublicId, $data['action'] === 'react', $data['idempotency_key']);

        return redirect()->to(route('confessions.public', ['locale' => $locale, 'publicId' => $publicId]).'#comment-'.$commentPublicId);
    }

    public function continuation(Request $request, string $locale, string $publicId, ParticipationService $participation): Response
    {
        if ($limited = $this->limit($request, 'continuation')) {
            return $limited;
        }
        $data = Validator::make($request->all(), [
            'body' => ['required', 'string', 'max:'.config('lavbe.limits.continuation_request_body')],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
            'confirm_continuation' => ['accepted'],
        ])->validate();
        $body = trim((string) $data['body']);
        abort_if($body === '' || mb_strlen($body, 'UTF-8') > (int) config('lavbe.limits.continuation_body'), 422);
        $participation->submitContinuation($request, $publicId, $body, $data['idempotency_key']);

        return redirect()->route('confessions.status', ['locale' => $locale, 'publicId' => $publicId])->with('status', __('participation.continuation_pending'));
    }

    public function respond(Request $request, string $locale, string $publicId, string $commentPublicId, ParticipationService $participation): Response
    {
        if ($limited = $this->limit($request, 'author_response')) {
            return $limited;
        }
        $data = Validator::make($request->all(), [
            'body' => ['required', 'string', 'max:'.config('lavbe.limits.comment_request_body')],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
        ])->validate();
        $body = trim((string) $data['body']);
        abort_if($body === '' || mb_strlen($body, 'UTF-8') > (int) config('lavbe.limits.response_body'), 422);
        $participation->respondToComment($request, $publicId, $commentPublicId, $body, $data['idempotency_key']);

        return redirect()->route('confessions.public', ['locale' => $locale, 'publicId' => $publicId])->with('status', __('participation.response_pending'));
    }

    public function support(Request $request, string $locale, string $publicId, ParticipationService $participation): Response
    {
        if ($limited = $this->limit($request, 'support')) {
            return $limited;
        }
        $data = Validator::make($request->all(), [
            'action' => ['required', 'in:support,unsupport'],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
        ])->validate();
        $participation->setSupport($request, $publicId, $data['action'] === 'support', $data['idempotency_key']);
        $ids = $request->session()->get('lavbe_supported', []);
        $ids = is_array($ids) ? $ids : [];
        $ids = $data['action'] === 'support' ? [...$ids, $publicId] : array_values(array_diff($ids, [$publicId]));
        $request->session()->put('lavbe_supported', array_slice(array_values(array_unique($ids)), -(int) config('lavbe.limits.supported_session_items')));

        return redirect()->route('confessions.public', ['locale' => $locale, 'publicId' => $publicId]);
    }

    public function reviews(Request $request, string $locale, string $publicId, ParticipationService $participation): Response
    {
        $data = Validator::make($request->all(), [
            'open' => ['required', 'boolean'],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
        ])->validate();
        $participation->setReviewsOpen($request, $publicId, (bool) $data['open'], $data['idempotency_key']);

        return redirect()->route('confessions.status', ['locale' => $locale, 'publicId' => $publicId])->with('status', __('participation.reviews_saved'));
    }

    public function commentStatus(Request $request, string $locale, string $commentPublicId, ParticipationService $participation): Response
    {
        $comment = ConfessionComment::query()->with(['confession', 'revisions' => fn ($query) => $query->orderByDesc('revision')->limit(1)])
            ->where('public_id', $commentPublicId)->firstOrFail();
        abort_unless($participation->ownsComment($request, $comment), 404);

        return response()->view('participation.comment-status', compact('comment'))
            ->header('Cache-Control', ResponseHeaders::CACHE_PRIVATE_NO_STORE)
            ->header('X-Robots-Tag', ResponseHeaders::ROBOTS_NO_ARCHIVE)
            ->header('Referrer-Policy', ResponseHeaders::REFERRER_NO_REFERRER);
    }

    public function recoveryForm(string $locale, string $commentPublicId): Response
    {
        abort_unless(ConfessionComment::query()->where('public_id', $commentPublicId)->exists(), 404);

        return response()->view('participation.comment-recover', compact('commentPublicId'))
            ->header('Cache-Control', ResponseHeaders::CACHE_PRIVATE_NO_STORE)
            ->header('X-Robots-Tag', ResponseHeaders::ROBOTS_NO_ARCHIVE)
            ->header('Referrer-Policy', ResponseHeaders::REFERRER_NO_REFERRER);
    }

    public function recover(Request $request, string $locale, string $commentPublicId, ParticipationService $participation): Response
    {
        if ($limited = $this->limit($request, 'comment_recovery')) {
            return $limited;
        }
        $data = Validator::make($request->all(), [
            'recovery_code' => ['required', 'string', 'min:'.config('lavbe.limits.recovery_code_min'), 'max:'.config('lavbe.limits.recovery_code'), 'regex:/^[A-Za-z0-9_-]+$/'],
            'remember_device' => ['sometimes', 'accepted'],
        ])->validate();
        $deviceToken = $request->boolean('remember_device') ? $this->deviceToken($request) : null;
        $result = $participation->recoverComment($request, $commentPublicId, $data['recovery_code'], $deviceToken);
        $response = redirect()->route('participation.comment.status', ['locale' => $locale, 'commentPublicId' => $commentPublicId]);
        if ($deviceToken !== null) {
            $response->headers->setCookie(cookie(
                'lavbe_device',
                $deviceToken,
                (int) config('lavbe.cookies.owner_minutes'),
                '/',
                null,
                $request->isSecure() || app()->environment('production'),
                true,
                false,
                'strict',
            ));
        }

        return $response;
    }

    public function withdrawComment(Request $request, string $locale, string $commentPublicId, ParticipationService $participation): Response
    {
        $data = Validator::make($request->all(), [
            'confirm_withdraw' => ['accepted'],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
        ])->validate();
        $parentPublicId = ConfessionComment::query()->where('public_id', $commentPublicId)->with('confession:id,public_id')->first()?->confession?->public_id;
        $participation->withdrawComment($request, $commentPublicId, $data['idempotency_key']);
        if ($parentPublicId) {
            return redirect()->route('confessions.public', ['locale' => $locale, 'publicId' => $parentPublicId]);
        }

        return redirect()->route('home', ['locale' => $locale]);
    }

    private function limit(Request $request, string $action): ?Response
    {
        $limit = config('lavbe.rate_limits.actions.'.$action);
        $key = 'participation:'.hash_hmac('sha256', $action.'|'.(string) $request->ip(), (string) config('app.key'));
        if (RateLimiter::tooManyAttempts($key, $limit['attempts'])) {
            return response()->view('confessions.limited', [], 429)
                ->header('Retry-After', (string) RateLimiter::availableIn($key))
                ->header('Cache-Control', ResponseHeaders::CACHE_PRIVATE_NO_STORE)
                ->header('X-Robots-Tag', ResponseHeaders::ROBOTS_NO_ARCHIVE);
        }
        RateLimiter::hit($key, $limit['decay_seconds']);

        return null;
    }

    private function deviceToken(Request $request): string
    {
        $existing = $request->cookie('lavbe_device');
        if (is_string($existing) && preg_match('/^[A-Za-z0-9_-]{40,48}$/', $existing) === 1) {
            return $existing;
        }

        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
