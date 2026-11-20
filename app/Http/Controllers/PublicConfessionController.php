<?php

namespace App\Http\Controllers;

use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\ConfessionContinuation;
use App\Support\Lavbe\LocaleRegistry;
use App\Support\Participation\ParticipationService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicConfessionController extends Controller
{
    public function show(Request $request, string $locale, string $publicId, LocaleRegistry $locales, ParticipationService $participation): Response|View
    {
        $localeRecord = $locales->bySegment($locale);
        abort_unless($localeRecord, 404);

        $confession = Confession::query()->with('publicRevision')
            ->where('public_id', $publicId)
            ->where('locale', app(LocaleRegistry::class)->contentCode($locale))
            ->first();

        if ($confession?->status === 'deleted') {
            abort_if($confession->ever_public, 410);
            abort(404);
        }

        abort_unless(
            $confession
            && $confession->status === 'public'
            && $confession->published_at
            && $confession->public_revision_id
            && $confession->publicRevision?->status === 'approved'
            && (int) $confession->publicRevision?->confession_id === (int) $confession->id,
            404,
        );

        $path = '/'.$locale.'/confession/'.$confession->public_id;
        $canonical = rtrim((string) config('app.url'), '/').$path;
        $revision = $confession->publicRevision;
        $description = $confession->excerpt ?: mb_substr(trim(preg_replace('/\s+/u', ' ', $revision->body) ?? ''), 0, (int) config('lavbe.seo_description_excerpt'), 'UTF-8');
        $category = $locales->category((string) $revision->category_key);
        $comments = ConfessionComment::query()->with([
            'publicRevision' => fn ($query) => $query->where('status', 'approved'),
            'response.publicRevision' => fn ($query) => $query->where('status', 'approved'),
            'ownership',
        ])
            ->where('confession_id', $confession->id)
            ->where('status', 'public')
            ->whereHas('publicRevision', fn ($query) => $query->where('status', 'approved')
                ->whereColumn('confession_comment_revisions.comment_id', 'confession_comments.id'))
            ->orderBy('published_at')->orderBy('id')->get();
        foreach ($comments as $comment) {
            $response = $comment->response;
            if ($response && (int) ($response->publicRevision?->response_id ?? 0) !== (int) $response->id) {
                $response->setRelation('publicRevision', null);
            }
        }
        $ownedCommentIds = $comments->filter(fn (ConfessionComment $comment): bool => $participation->ownsComment($request, $comment))
            ->pluck('id')->all();
        $continuations = ConfessionContinuation::query()->with('publicRevision')
            ->where('confession_id', $confession->id)
            ->where('status', 'public')
            ->whereNotNull('published_at')
            ->whereHas('publicRevision', fn ($query) => $query->where('status', 'approved')
                ->whereColumn('confession_continuation_revisions.continuation_id', 'confession_continuations.id'))
            ->orderBy('published_at')->orderBy('id')->get();
        $ownerCanParticipate = app(GuestConfessionController::class)->canManage($request, $confession);
        $hasSupported = $participation->hasSupport($request, $confession);

        return response()->view('confessions.public', compact('confession', 'revision', 'canonical', 'description', 'category', 'localeRecord', 'comments', 'continuations', 'ownerCanParticipate', 'hasSupported', 'ownedCommentIds'))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'index, follow')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
