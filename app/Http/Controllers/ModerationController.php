<?php

namespace App\Http\Controllers;

use App\Models\CommentResponse;
use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\ConfessionContinuation;
use App\Models\ConfessionRevision;
use App\Models\ModerationAppeal;
use App\Models\ModeratorGrant;
use App\Models\ReportGroup;
use App\Models\ReportSubmission;
use App\Support\Http\ResponseHeaders;
use App\Support\Lavbe\LocaleRegistry;
use App\Support\Moderation\AppealService;
use App\Support\Moderation\ModerationService;
use App\Support\Moderation\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ModerationController extends Controller
{
    public function createConfession(string $locale, LocaleRegistry $locales): Response|View
    {
        abort_unless($locales->bySegment($locale), 404);
        $this->assertLocaleGrant(request(), $locales->contentCode($locale));

        return $this->privateView('moderation.create-confession', [
            'categories' => collect($locales->catalog()['categories'])->map(fn (array $item): array => [
                'key' => $item['key'],
                'label' => $item['labels'][app()->getLocale()] ?? $item['labels']['en'],
                'description' => $item['descriptions'][app()->getLocale()] ?? $item['descriptions']['en'],
                'icon' => $item['icon'],
            ]),
            'idempotencyKey' => rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='),
        ]);
    }

    public function index(Request $request, string $locale, LocaleRegistry $locales): Response|View
    {
        $allowed = $this->allowedLocales($request);
        $selected = (string) $request->query('language', '');
        abort_if($selected !== '' && ! $allowed->contains($selected), 404);
        $languages = $selected === '' ? $allowed : collect([$selected]);
        $selectedState = (string) $request->query('state', 'review');
        abort_unless(in_array($selectedState, ['review', 'public', 'hidden'], true), 404);

        $query = DB::table('confessions')->whereIn('confessions.locale', $languages);
        if ($selectedState === 'review') {
            $query->join('confession_revisions', 'confession_revisions.confession_id', '=', 'confessions.id')
                ->where('confession_revisions.status', 'pending')
                ->whereIn('confessions.status', ['unpublished', 'public']);
            $revisionFields = [
                'confession_revisions.id as revision_id',
                'confession_revisions.title',
                'confession_revisions.category_key',
                'confession_revisions.created_at',
            ];
        } else {
            $query->join('confession_revisions', 'confession_revisions.id', '=', 'confessions.public_revision_id')
                ->where('confessions.status', $selectedState === 'public' ? 'public' : 'hidden');
            $revisionFields = [
                'confession_revisions.id as revision_id',
                'confession_revisions.title',
                'confession_revisions.category_key',
                'confession_revisions.created_at',
            ];
        }

        $items = $query->select(array_merge([
            'confessions.id', 'confessions.public_id', 'confessions.locale', 'confessions.status',
            'confessions.lock_version',
        ], $revisionFields))
            ->orderBy('confession_revisions.created_at')
            ->orderBy('confessions.id')->orderBy('confession_revisions.id')
            ->paginate((int) config('lavbe.pagination.moderation'), ['*'], 'queue_page')
            ->withQueryString();
        abort_if($items->currentPage() > $items->lastPage(), 404);

        $isAdmin = $request->user()->moderatorGrants()
            ->where('role', 'admin')->where('locale', '*')->where('is_active', true)->whereNull('revoked_at')->exists();

        return $this->privateView('moderation.queue', compact('items', 'allowed', 'selected', 'selectedState', 'isAdmin'));
    }

    public function show(Request $request, string $locale, int $confessionId, LocaleRegistry $locales): Response|View
    {
        $confession = Confession::query()->findOrFail($confessionId);
        $this->assertLocaleGrant($request, $confession->locale);
        $requestedRevisionId = $request->query('revision_id');
        $revision = $requestedRevisionId
            ? $confession->revisions()->whereKey((int) $requestedRevisionId)->first()
            : ($confession->status === 'hidden'
                ? $confession->revisions()->whereKey($confession->public_revision_id)->first()
                : $confession->revisions()->where('status', 'pending')->orderByDesc('revision')->first());
        $revision ??= ($confession->public_revision_id ? $confession->revisions()->whereKey($confession->public_revision_id)->first() : null)
            ?? $confession->revisions()->orderByDesc('revision')->first();
        abort_unless($revision, 404);

        $publicRevision = $confession->public_revision_id
            ? ConfessionRevision::query()->whereKey($confession->public_revision_id)->first()
            : null;
        $categories = collect($locales->catalog()['categories'])->map(fn (array $item): array => [
            'key' => $item['key'],
            'label' => $item['labels'][app()->getLocale()] ?? $item['labels']['en'],
        ]);

        return $this->privateView('moderation.show', compact('confession', 'revision', 'publicRevision', 'categories'));
    }

    public function decide(Request $request, string $locale, int $confessionId, ModerationService $moderation): Response
    {
        $confession = Confession::query()->find($confessionId);
        abort_if(! $confession, 404);
        $this->assertLocaleGrant($request, $confession->locale);

        $data = Validator::make($request->all(), [
            'revision_id' => ['required', 'integer', 'min:1'],
            'expected_lock_version' => ['required', 'integer', 'min:1'],
            'decision' => ['required', 'in:approve,request_changes,reject,hide,restore,terminal_delete'],
            'reason_code' => ['required', 'in:acceptable,privacy,harassment,threat,spam,explicit,legal,other'],
            'author_message' => ['nullable', 'string', 'max:'.config('lavbe.limits.moderation_author_message')],
            'internal_note' => ['nullable', 'string', 'max:'.config('lavbe.limits.moderation_internal_note')],
            'idempotency_key' => ['required', 'string', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
            'restore_reviewed' => ['sometimes', 'accepted'],
        ])->validate();
        if (in_array($data['decision'], ['request_changes', 'reject'], true) && trim((string) ($data['author_message'] ?? '')) === '') {
            return back()->withErrors(['author_message' => __('moderation.author_message_required')]);
        }

        $moderation->decide(
            $request->user(),
            $confessionId,
            (int) $data['revision_id'],
            (int) $data['expected_lock_version'],
            $data['decision'],
            $data['reason_code'],
            $data['author_message'] ?? null,
            $data['internal_note'] ?? null,
            $data['idempotency_key'],
            $request->boolean('restore_reviewed'),
            (string) ($request->header('X-Request-Id') ?: (string) Str::uuid()),
        );

        return redirect()->route('moderation.queue', ['locale' => $locale])->with('status', __('moderation.decision_saved'));
    }

    public function participation(Request $request): Response|View
    {
        $allowed = $this->allowedLocales($request);
        $comments = ConfessionComment::query()->with([
            'confession', 'parent.publicRevision',
            'revisions' => fn ($query) => $query->where('status', 'pending')->orderByDesc('revision')->limit(1),
        ])->where('status', 'unpublished')
            ->whereHas('revisions', fn ($query) => $query->where('status', 'pending'))
            ->whereHas('confession', fn ($query) => $query->public()->whereIn('locale', $allowed))
            ->orderBy('created_at')->orderBy('id')->paginate((int) config('lavbe.pagination.moderation_history'), ['*'], 'pending_comments_page')->withQueryString();
        $visibleComments = ConfessionComment::query()->with(['confession', 'publicRevision', 'parent.publicRevision'])
            ->where('status', 'public')->whereNotNull('public_revision_id')
            ->whereHas('confession', fn ($query) => $query->public()->whereIn('locale', $allowed))
            ->orderByDesc('published_at')->orderByDesc('id')->paginate((int) config('lavbe.pagination.moderation_history'), ['*'], 'visible_comments_page')->withQueryString();
        $continuations = ConfessionContinuation::query()->with([
            'confession',
            'revisions' => fn ($query) => $query->where('status', 'pending')->orderByDesc('revision')->limit(1),
        ])->where('status', 'unpublished')
            ->whereHas('revisions', fn ($query) => $query->where('status', 'pending'))
            ->whereHas('confession', fn ($query) => $query->public()->whereIn('locale', $allowed))
            ->orderBy('created_at')->orderBy('id')->paginate((int) config('lavbe.pagination.moderation_history'), ['*'], 'pending_continuations_page')->withQueryString();
        $visibleContinuations = ConfessionContinuation::query()->with(['confession', 'publicRevision'])
            ->where('status', 'public')->whereNotNull('public_revision_id')
            ->whereHas('confession', fn ($query) => $query->public()->whereIn('locale', $allowed))
            ->orderByDesc('published_at')->orderByDesc('id')->paginate((int) config('lavbe.pagination.moderation_history'), ['*'], 'visible_continuations_page')->withQueryString();
        $responses = CommentResponse::query()->with([
            'comment.confession',
            'comment.publicRevision',
            'revisions' => fn ($query) => $query->where('status', 'pending')->orderByDesc('revision')->limit(1),
        ])->where('status', 'unpublished')
            ->whereHas('revisions', fn ($query) => $query->where('status', 'pending'))
            ->whereHas('comment', fn ($query) => $query->where('status', 'public')->whereHas('confession', fn ($parent) => $parent->public()->whereIn('locale', $allowed)))
            ->orderBy('created_at')->orderBy('id')->paginate((int) config('lavbe.pagination.moderation_history'), ['*'], 'pending_responses_page')->withQueryString();
        $visibleResponses = CommentResponse::query()->with(['comment.confession', 'publicRevision'])
            ->where('status', 'public')->whereNotNull('public_revision_id')
            ->whereHas('comment', fn ($query) => $query->where('status', 'public')->whereHas('confession', fn ($parent) => $parent->public()->whereIn('locale', $allowed)))
            ->orderByDesc('published_at')->orderByDesc('id')->paginate((int) config('lavbe.pagination.moderation_history'), ['*'], 'visible_responses_page')->withQueryString();
        foreach ([$comments, $visibleComments, $continuations, $visibleContinuations, $responses, $visibleResponses] as $list) {
            abort_if($list->currentPage() > $list->lastPage(), 404);
        }

        return $this->privateView('moderation.participation', compact('comments', 'visibleComments', 'continuations', 'visibleContinuations', 'responses', 'visibleResponses'));
    }

    public function reports(Request $request): Response|View
    {
        $allowed = $this->allowedLocales($request);
        $items = ReportGroup::query()->with('confession')->where('status', 'pending')
            ->whereHas('confession', fn ($query) => $query->whereIn('locale', $allowed))
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'legal' THEN 1 ELSE 2 END")
            ->orderBy('first_reported_at')->orderBy('id')->paginate((int) config('lavbe.pagination.moderation'), ['*'], 'reports_page')->withQueryString();
        abort_if($items->currentPage() > $items->lastPage(), 404);

        return $this->privateView('moderation.reports', compact('items'));
    }

    public function report(Request $request, string $locale, int $groupId): Response|View
    {
        $group = ReportGroup::query()->with('confession.publicRevision')->findOrFail($groupId);
        $this->assertLocaleGrant($request, $group->confession->locale);
        $submissions = ReportSubmission::query()->where('report_group_id', $group->id)
            ->orderBy('created_at')->orderBy('id')
            ->paginate((int) config('lavbe.pagination.moderation_history'), ['*'], 'submissions_page')
            ->withQueryString();
        abort_if($submissions->currentPage() > $submissions->lastPage(), 404);
        $target = $group->target_type === 'confession'
            ? $group->confession
            : ConfessionComment::query()->with('publicRevision')->findOrFail($group->target_id);

        return $this->privateView('moderation.report', compact('group', 'target', 'submissions'));
    }

    public function decideReport(Request $request, string $locale, int $groupId, ReportService $reports): Response
    {
        $group = ReportGroup::query()->with('confession')->findOrFail($groupId);
        $this->assertLocaleGrant($request, $group->confession->locale);
        $data = Validator::make($request->all(), [
            'expected_group_lock_version' => ['required', 'integer', 'min:1'],
            'expected_parent_lock_version' => ['required', 'integer', 'min:1'],
            'expected_target_lock_version' => ['required', 'integer', 'min:1'],
            'revision_id' => ['required', 'integer', 'min:1'],
            'decision' => ['required', 'in:dismiss,hide'],
            'reason_code' => ['required', 'in:no_violation,privacy,harassment,threat,spam,explicit,legal,other'],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
        ])->validate();
        $reports->decide($request->user(), $groupId, (int) $data['expected_group_lock_version'], (int) $data['expected_parent_lock_version'],
            (int) $data['expected_target_lock_version'], (int) $data['revision_id'], $data['decision'], $data['reason_code'], $data['idempotency_key'],
            (string) ($request->header('X-Request-Id') ?: Str::uuid()));

        return redirect()->route('moderation.reports', ['locale' => $request->route('locale')])->with('status', __('moderation.decision_saved'));
    }

    public function appeals(Request $request): Response|View
    {
        $allowed = $this->allowedLocales($request);
        $items = ModerationAppeal::query()->with(['confession', 'hideAudit'])->where('status', 'pending')
            ->whereHas('confession', fn ($query) => $query->whereIn('locale', $allowed))->orderBy('created_at')->orderBy('id')
            ->paginate((int) config('lavbe.pagination.moderation'), ['*'], 'appeals_page')->withQueryString();
        abort_if($items->currentPage() > $items->lastPage(), 404);

        return $this->privateView('moderation.appeals', compact('items'));
    }

    public function appeal(Request $request, string $locale, int $appealId): Response|View
    {
        $appeal = ModerationAppeal::query()->with(['confession', 'hideAudit'])->findOrFail($appealId);
        $this->assertLocaleGrant($request, $appeal->confession->locale);
        $target = $appeal->target_type === 'confession' ? $appeal->confession : ConfessionComment::query()->with('publicRevision')->findOrFail($appeal->target_id);

        return $this->privateView('moderation.appeal', compact('appeal', 'target'));
    }

    public function decideAppeal(Request $request, string $locale, int $appealId, AppealService $appeals): Response
    {
        $appeal = ModerationAppeal::query()->with('confession')->findOrFail($appealId);
        $this->assertLocaleGrant($request, $appeal->confession->locale);
        $data = Validator::make($request->all(), [
            'expected_appeal_lock_version' => ['required', 'integer', 'min:1'], 'expected_parent_lock_version' => ['required', 'integer', 'min:1'],
            'expected_target_lock_version' => ['required', 'integer', 'min:1'], 'revision_id' => ['required', 'integer', 'min:1'],
            'decision' => ['required', 'in:uphold,restore'], 'reason_code' => ['required', 'in:privacy,harassment,threat,spam,explicit,legal,other'],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
        ])->validate();
        $appeals->resolve($request->user(), $appealId, (int) $data['expected_appeal_lock_version'], (int) $data['expected_parent_lock_version'],
            (int) $data['expected_target_lock_version'], (int) $data['revision_id'], $data['decision'], $data['reason_code'], $data['idempotency_key'],
            (string) ($request->header('X-Request-Id') ?: Str::uuid()));

        return redirect()->route('moderation.appeals', ['locale' => $request->route('locale')])->with('status', __('moderation.decision_saved'));
    }

    public function decideParticipation(Request $request, string $locale, string $type, int $targetId, ModerationService $moderation): Response
    {
        $data = Validator::make($request->all(), [
            'revision_id' => ['required', 'integer', 'min:1'],
            'expected_lock_version' => ['required', 'integer', 'min:1'],
            'expected_parent_lock_version' => ['required', 'integer', 'min:1'],
            'decision' => ['required', 'in:approve,reject,hide'],
            'reason_code' => ['required', 'in:acceptable,privacy,harassment,threat,spam,explicit,legal,other'],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
        ])->validate();
        $moderation->decideParticipation(
            $request->user(), $type, $targetId, (int) $data['revision_id'], (int) $data['expected_lock_version'],
            (int) $data['expected_parent_lock_version'], $data['decision'], $data['reason_code'],
            $data['idempotency_key'], (string) ($request->header('X-Request-Id') ?: Str::uuid()),
        );

        return redirect()->route('moderation.participation', ['locale' => $locale])->with('status', __('moderation.decision_saved'));
    }

    private function allowedLocales(Request $request): Collection
    {
        $grants = $request->user()->moderatorGrants()->where('is_active', true)->whereNull('revoked_at')->get(['role', 'locale']);
        if ($grants->contains(fn ($grant) => $grant->role === 'admin' && $grant->locale === '*')) {
            return collect(app(LocaleRegistry::class)->all())->pluck('code');
        }

        return $grants->where('role', 'moderator')->pluck('locale')->unique()->values();
    }

    private function assertLocaleGrant(Request $request, string $targetLocale): void
    {
        abort_unless(ModeratorGrant::activeForLocale($request->user(), $targetLocale)->exists(), 404);
    }

    private function privateView(string $view, array $data = []): Response
    {
        return response()->view($view, $data)
            ->header('Cache-Control', ResponseHeaders::CACHE_PRIVATE_NO_STORE)
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', ResponseHeaders::ROBOTS_PRIVATE)
            ->header('Referrer-Policy', ResponseHeaders::REFERRER_NO_REFERRER);
    }
}
