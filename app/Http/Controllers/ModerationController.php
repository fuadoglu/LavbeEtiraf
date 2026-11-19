<?php

namespace App\Http\Controllers;

use App\Models\Confession;
use App\Models\ConfessionRevision;
use App\Support\Lavbe\LocaleRegistry;
use App\Support\Moderation\ModerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ModerationController extends Controller
{
    public function index(Request $request, string $locale, LocaleRegistry $locales): Response|View
    {
        $allowed = $request->user()->moderatorGrants()
            ->where('role', 'moderator')->where('is_active', true)->whereNull('revoked_at')
            ->pluck('locale')->unique()->values();
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
            ->paginate(30)
            ->withQueryString();

        $isAdmin = $request->user()->moderatorGrants()
            ->where('role', 'admin')->where('locale', '*')->where('is_active', true)->whereNull('revoked_at')->exists();

        return $this->privateView('moderation.queue', compact('items', 'allowed', 'selected', 'selectedState', 'isAdmin'));
    }

    public function show(Request $request, string $locale, int $confessionId, LocaleRegistry $locales): Response|View
    {
        $confession = Confession::query()->with(['revisions' => fn ($query) => $query->orderByDesc('revision')])->findOrFail($confessionId);
        $this->assertLocaleGrant($request, $confession->locale);
        $requestedRevisionId = $request->query('revision_id');
        $revision = $requestedRevisionId
            ? $confession->revisions->firstWhere('id', (int) $requestedRevisionId)
            : ($confession->status === 'hidden'
                ? $confession->revisions->firstWhere('id', $confession->public_revision_id)
                : $confession->revisions->firstWhere('status', 'pending'));
        $revision ??= $confession->revisions->firstWhere('id', $confession->public_revision_id)
            ?? $confession->revisions->first();
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
            'author_message' => ['nullable', 'string', 'max:500'],
            'internal_note' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['required', 'string', 'regex:/^[a-f0-9]{32}$/'],
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

    private function assertLocaleGrant(Request $request, string $targetLocale): void
    {
        abort_unless($request->user()->moderatorGrants()
            ->where('role', 'moderator')
            ->where('locale', $targetLocale)
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->exists(), 404);
    }

    private function privateView(string $view, array $data = []): Response
    {
        return response()->view($view, $data)
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
