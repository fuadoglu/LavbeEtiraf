<?php

namespace App\Http\Controllers;

use App\Models\ConfessionComment;
use App\Support\Participation\ParticipationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ParticipationController extends Controller
{
    public function comment(Request $request, string $locale, string $publicId, ParticipationService $participation): Response
    {
        if ($limited = $this->limit($request, 'comment')) {
            return $limited;
        }
        $data = Validator::make($request->all(), [
            'body' => ['required', 'string', 'max:'.config('lavbe.limits.comment_request_body')],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
            'remember_device' => ['sometimes', 'accepted'],
        ])->validate();
        $body = trim((string) $data['body']);
        abort_if($body === '' || mb_strlen($body, 'UTF-8') > (int) config('lavbe.limits.comment_body'), 422);
        $result = $participation->submitComment($request, $publicId, $body, $data['idempotency_key']);
        $response = response()->view('participation.submitted', [
            'confession' => $result['confession'],
            'comment' => $result['comment'],
            'recoveryCode' => $result['recovery_code'],
        ], 201);
        if ($result['owner_token'] !== '' && $request->boolean('remember_device')) {
            $response->headers->setCookie(cookie(
                'lavbe_comment_'.$result['comment']->public_id,
                $result['owner_token'],
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
        $comment = ConfessionComment::query()->with(['confession', 'revisions' => fn ($query) => $query->orderByDesc('revision')])
            ->where('public_id', $commentPublicId)->firstOrFail();
        abort_unless($participation->ownsComment($request, $comment), 404);

        return response()->view('participation.comment-status', compact('comment'))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function recoveryForm(string $locale, string $commentPublicId): Response
    {
        abort_unless(ConfessionComment::query()->where('public_id', $commentPublicId)->exists(), 404);

        return response()->view('participation.comment-recover', compact('commentPublicId'))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
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
        $result = $participation->recoverComment($request, $commentPublicId, $data['recovery_code']);
        $response = redirect()->route('participation.comment.status', ['locale' => $locale, 'commentPublicId' => $commentPublicId]);
        if ($request->boolean('remember_device')) {
            $response->headers->setCookie(cookie(
                'lavbe_comment_'.$commentPublicId,
                $result['owner_token'],
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
                ->header('Cache-Control', 'private, no-store, max-age=0')
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }
        RateLimiter::hit($key, $limit['decay_seconds']);

        return null;
    }
}
