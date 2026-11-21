<?php

namespace App\Http\Controllers;

use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Support\Lavbe\LocaleRegistry;
use App\Support\Moderation\AppealService;
use App\Support\Participation\ParticipationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class AppealController extends Controller
{
    public function create(Request $request, string $locale, string $type, string $targetPublicId): Response
    {
        [$confession, $comment] = $this->target($locale, $type, $targetPublicId);
        $owned = $type === 'confession'
            ? app(GuestConfessionController::class)->canManage($request, $confession)
            : app(ParticipationService::class)->ownsComment($request, $comment);
        abort_unless($owned, 404);

        return response()->view('appeals.create', [
            'confession' => $confession, 'comment' => $comment, 'type' => $type,
            'publicId' => $targetPublicId, 'idempotencyKey' => \App\Support\Security\IdempotencyKey::generate(),
        ])->header('Cache-Control', \App\Support\Http\ResponseHeaders::CACHE_PRIVATE_NO_STORE)
            ->header('X-Robots-Tag', \App\Support\Http\ResponseHeaders::ROBOTS_PRIVATE)
            ->header('Referrer-Policy', \App\Support\Http\ResponseHeaders::REFERRER_NO_REFERRER);
    }

    public function store(Request $request, string $locale, string $type, string $targetPublicId, AppealService $appeals): Response
    {
        $data = Validator::make($request->all(), [
            'details' => ['required', 'string', 'min:'.config('lavbe.limits.appeal_details_min'), 'max:'.config('lavbe.limits.appeal_details')],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
        ])->validate();
        $appeals->submit($request, $locale, $type, $targetPublicId, $data['details'], $data['idempotency_key']);

        return response()->view('appeals.submitted', [], 202)
            ->header('Cache-Control', \App\Support\Http\ResponseHeaders::CACHE_PRIVATE_NO_STORE)
            ->header('X-Robots-Tag', \App\Support\Http\ResponseHeaders::ROBOTS_PRIVATE)
            ->header('Referrer-Policy', \App\Support\Http\ResponseHeaders::REFERRER_NO_REFERRER);
    }

    private function target(string $locale, string $type, string $publicId): array
    {
        abort_unless(in_array($type, ['confession', 'comment'], true), 404);
        if ($type === 'confession') {
            return [Confession::query()->where('public_id', $publicId)->where('locale', app(LocaleRegistry::class)->contentCode($locale))->where('status', 'hidden')->firstOrFail(), null];
        }
        $comment = ConfessionComment::query()->with('confession')->where('public_id', $publicId)
            ->where('status', 'hidden')->whereHas('confession', fn ($query) => $query->where('locale', app(LocaleRegistry::class)->contentCode($locale))->where('status', 'public'))->firstOrFail();

        return [$comment->confession, $comment];
    }
}
