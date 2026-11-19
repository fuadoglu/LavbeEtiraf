<?php

namespace App\Http\Controllers;

use App\Models\Confession;
use App\Models\ConfessionComment;
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
            'publicId' => $targetPublicId, 'idempotencyKey' => bin2hex(random_bytes(16)),
        ])->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function store(Request $request, string $locale, string $type, string $targetPublicId, AppealService $appeals): Response
    {
        $data = Validator::make($request->all(), [
            'details' => ['required', 'string', 'min:10', 'max:1200'],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{32}$/'],
        ])->validate();
        $appeals->submit($request, $locale, $type, $targetPublicId, $data['details'], $data['idempotency_key']);

        return response()->view('appeals.submitted', [], 202)
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer');
    }

    private function target(string $locale, string $type, string $publicId): array
    {
        abort_unless(in_array($type, ['confession', 'comment'], true), 404);
        if ($type === 'confession') {
            return [Confession::query()->where('public_id', $publicId)->where('locale', $locale)->where('status', 'hidden')->firstOrFail(), null];
        }
        $comment = ConfessionComment::query()->with('confession')->where('public_id', $publicId)
            ->where('status', 'hidden')->whereHas('confession', fn ($query) => $query->where('locale', $locale)->where('status', 'public'))->firstOrFail();

        return [$comment->confession, $comment];
    }
}
