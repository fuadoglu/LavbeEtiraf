<?php

namespace App\Http\Controllers;

use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\ConfessionContinuation;
use App\Support\Analytics\UniqueReadCounter;
use App\Support\Lavbe\LocaleRegistry;
use App\Support\Lavbe\RoutePatterns;
use App\Support\Participation\ParticipationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicConfessionController extends Controller
{
    public function show(Request $request, string $locale, string $publicId, LocaleRegistry $locales, ParticipationService $participation, UniqueReadCounter $readCounter): Response|View
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

        $readerCookie = $readCounter->record($request, $confession);

        $path = '/'.$locale.'/confession/'.$confession->public_id;
        $canonical = rtrim((string) config('app.url'), '/').$path;
        $revision = $confession->publicRevision;
        $description = $confession->excerpt ?: mb_substr(trim(preg_replace('/\s+/u', ' ', $revision->body) ?? ''), 0, (int) config('lavbe.seo_description_excerpt'), 'UTF-8');
        $category = $locales->category((string) $revision->category_key);
        $rankedRecommendations = Confession::query()->public()
            ->where('locale', $confession->locale)
            ->whereKeyNot($confession->id)
            ->whereNotNull('category_key')
            ->select(['confessions.id', 'confessions.category_key'])
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY confessions.category_key ORDER BY confessions.support_count DESC, confessions.published_at DESC, confessions.id DESC) AS category_rank');
        $recommendationIds = DB::query()->fromSub($rankedRecommendations, 'ranked_recommendations')
            ->where('category_rank', 1)
            ->select('id');
        $recommended = Confession::query()->public()
            ->where('locale', $confession->locale)
            ->whereNotNull('category_key')
            ->where('category_key', '!=', (string) $revision->category_key)
            ->whereIn('confessions.id', $recommendationIds)
            ->with('publicRevision')
            ->orderByDesc('support_count')->orderByDesc('published_at')->orderByDesc('id')
            ->limit(3)->get();
        $threadPublicId = $request->query('thread');
        $threadRoot = is_string($threadPublicId) && preg_match('/^'.RoutePatterns::UUID_V4.'$/D', $threadPublicId)
            ? ConfessionComment::query()->publicApproved()->where('confession_id', $confession->id)
                ->whereNull('parent_comment_id')->where('public_id', $threadPublicId)->first()
            : null;
        $threadReplies = $threadRoot
            ? ConfessionComment::query()->publicApproved()->where('parent_comment_id', $threadRoot->id)->with([
                'publicRevision', 'response.publicRevision', 'ownership',
            ])->orderBy('published_at')->orderBy('id')
                ->paginate((int) config('lavbe.pagination.comment_replies'), ['*'], 'reply_page')
                ->appends($request->except('reply_page'))
            : null;
        abort_if($threadReplies && $threadReplies->currentPage() > $threadReplies->lastPage(), 404);
        $comments = ConfessionComment::query()->publicApproved()->with([
            'publicRevision', 'response.publicRevision', 'ownership',
            'replies' => fn ($query) => $query->publicApproved()->with(['publicRevision', 'response.publicRevision', 'ownership'])
                ->orderBy('published_at')->orderBy('id')->limit((int) config('lavbe.pagination.comment_reply_preview')),
        ])->where('confession_id', $confession->id)->whereNull('parent_comment_id')
            ->orderBy('published_at')->orderBy('id')
            ->paginate((int) config('lavbe.pagination.comments'), ['*'], 'comments_page')
            ->appends($request->except('reply_page'));
        abort_if($comments->currentPage() > $comments->lastPage(), 404);
        $replyCounts = DB::table('confession_comments as replies')
            ->join('confession_comment_revisions as revisions', function ($join): void {
                $join->on('revisions.id', '=', 'replies.public_revision_id')
                    ->on('revisions.comment_id', '=', 'replies.id')
                    ->where('revisions.status', 'approved');
            })
            ->whereIn('replies.parent_comment_id', $comments->pluck('id'))
            ->where('replies.status', 'public')
            ->groupBy('replies.parent_comment_id')
            ->selectRaw('replies.parent_comment_id, COUNT(*) AS aggregate')
            ->pluck('aggregate', 'parent_comment_id');
        $visibleComments = collect();
        foreach ($comments as $comment) {
            $comment->setAttribute('public_replies_count', (int) ($replyCounts[$comment->id] ?? 0));
            $visibleComments->push($comment);
            foreach ($comment->replies as $reply) {
                $visibleComments->push($reply);
            }
            $this->clearStaleResponseRevision($comment);
            foreach ($comment->replies as $reply) {
                $this->clearStaleResponseRevision($reply);
            }
        }
        if ($threadReplies) {
            foreach ($threadReplies as $reply) {
                $visibleComments->push($reply);
                $this->clearStaleResponseRevision($reply);
            }
        }
        $ownedCommentIds = $visibleComments->filter(fn (ConfessionComment $comment): bool => $participation->ownsComment($request, $comment))
            ->pluck('id')->all();
        $reactedCommentIds = $participation->activeCommentReactionIds($request, $confession, $visibleComments->pluck('id')->all());
        $continuations = ConfessionContinuation::query()->with('publicRevision')
            ->where('confession_id', $confession->id)
            ->where('status', 'public')
            ->whereNotNull('published_at')
            ->whereHas('publicRevision', fn ($query) => $query->where('status', 'approved')
                ->whereColumn('confession_continuation_revisions.continuation_id', 'confession_continuations.id'))
            ->orderBy('published_at')->orderBy('id')
            ->paginate((int) config('lavbe.pagination.continuations'), ['*'], 'continuations_page')
            ->appends($request->except('continuations_page'));
        abort_if($continuations->currentPage() > $continuations->lastPage(), 404);
        $ownerCanParticipate = app(GuestConfessionController::class)->canManage($request, $confession);
        $hasSupported = $participation->hasSupport($request, $confession);

        $response = response()->view('confessions.public', compact('confession', 'revision', 'canonical', 'description', 'category', 'localeRecord', 'comments', 'continuations', 'ownerCanParticipate', 'hasSupported', 'ownedCommentIds', 'recommended', 'threadRoot', 'threadPublicId', 'threadReplies', 'reactedCommentIds'))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'index, follow')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Content-Type-Options', 'nosniff');

        return $readerCookie ? $response->withCookie($readerCookie) : $response;
    }

    private function clearStaleResponseRevision(ConfessionComment $comment): void
    {
        $response = $comment->response;
        if ($response && (int) ($response->publicRevision?->response_id ?? 0) !== (int) $response->id) {
            $response->setRelation('publicRevision', null);
        }
    }
}
