<?php

namespace App\Http\Controllers;

use App\Models\ModerationAudit;
use App\Models\ModeratorGrant;
use App\Models\User;
use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ModerationAdminController extends Controller
{
    public function index(Request $request): Response|View
    {
        abort_if($request->isMethod('GET') && $request->query->has('user_search'), 404);
        $grantPage = $this->requestedPage($request, 'grants_page');
        $usersPage = $this->requestedPage($request, 'users_page');
        $grants = ModeratorGrant::query()->with('user')->orderBy('role')->orderBy('locale')->orderBy('id')
            ->paginate((int) config('lavbe.pagination.moderation'), ['*'], 'grants_page', $grantPage);
        $userSearch = $request->input('user_search', '');
        abort_unless(is_string($userSearch), 422);
        $userSearch = trim($userSearch);
        abort_if(mb_strlen($userSearch, 'UTF-8') > (int) config('lavbe.limits.account_email'), 422);
        $users = User::query()->whereNotNull('email_verified_at')->whereKeyNot($request->user()->id)
            ->when($userSearch === '', fn ($query) => $query->whereRaw('1 = 0'))
            ->when($userSearch !== '', fn ($query) => $query->where(fn ($users) => $users
                ->where('email', 'like', '%'.$userSearch.'%')->orWhere('name', 'like', '%'.$userSearch.'%')))
            ->orderBy('email')->orderBy('id')->paginate((int) config('lavbe.pagination.user_lookup'), ['id', 'name', 'email'], 'users_page', $usersPage);
        abort_if($grants->currentPage() > $grants->lastPage() || $users->currentPage() > $users->lastPage(), 404);

        return $this->privateView('moderation.grants', compact('grants', 'users', 'userSearch'));
    }

    private function requestedPage(Request $request, string $name): int
    {
        $page = $request->input($name, 1);
        abort_unless((is_string($page) || is_int($page))
            && preg_match('/^[1-9][0-9]*$/D', (string) $page) === 1
            && filter_var($page, FILTER_VALIDATE_INT) !== false, 404);

        return (int) $page;
    }

    public function store(Request $request, string $locale, LocaleRegistry $locales): Response
    {
        $data = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', 'in:moderator,admin'],
            'locale' => ['required', 'string', 'max:'.config('lavbe.limits.locale_segment')],
            'reason' => ['required', 'string', 'min:'.config('lavbe.limits.moderation_reason_min'), 'max:'.config('lavbe.limits.moderation_reason')],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
        ])->validate();
        $target = User::query()->whereKey($data['user_id'])->whereNotNull('email_verified_at')->firstOrFail();
        abort_if($target->id === $request->user()->id, 403);
        if ($data['role'] === 'admin') {
            abort_unless($data['locale'] === '*', 422);
        } else {
            abort_unless($locales->bySegment($data['locale']), 422);
        }

        $reason = $this->sanitize((string) $data['reason']);
        $idempotencyHash = hash_hmac('sha256', 'grant|'.$request->user()->id.'|'.$data['idempotency_key'], (string) config('app.key'));
        $fingerprint = hash_hmac('sha256', json_encode([$target->id, $data['role'], $data['locale'], $reason], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), (string) config('app.key'));
        DB::transaction(function () use ($request, $target, $data, $reason, $idempotencyHash, $fingerprint): void {
            $existing = ModerationAudit::query()->where('idempotency_hash', $idempotencyHash)->first();
            if ($existing) {
                abort_unless(hash_equals((string) $existing->idempotency_fingerprint, $fingerprint), 409);

                return;
            }
            $grant = ModeratorGrant::query()->where([
                'user_id' => $target->id,
                'role' => $data['role'],
                'locale' => $data['locale'],
            ])->lockForUpdate()->first();
            $previousState = $grant?->is_active ? 'active' : 'inactive';
            if ($grant) {
                $grant->forceFill([
                    'is_active' => true,
                    'granted_by_user_id' => $grant->granted_by_user_id ?: $request->user()->id,
                    'changed_by_user_id' => $request->user()->id,
                    'reason' => $reason,
                    'revoked_at' => null,
                ])->save();
            } else {
                $grant = ModeratorGrant::query()->create([
                    'user_id' => $target->id,
                    'role' => $data['role'],
                    'locale' => $data['locale'],
                    'is_active' => true,
                    'granted_by_user_id' => $request->user()->id,
                    'changed_by_user_id' => $request->user()->id,
                    'reason' => $reason,
                ]);
            }
            $this->auditGrantChange($request, $grant, 'grant', $previousState, 'active', $reason, $idempotencyHash, $fingerprint);
        }, 3);

        return redirect()->route('moderation.grants', ['locale' => $locale])->with('status', __('moderation.grant_saved'));
    }

    public function revoke(Request $request, string $locale, int $grantId): Response
    {
        $data = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'min:'.config('lavbe.limits.moderation_reason_min'), 'max:'.config('lavbe.limits.moderation_reason')],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
        ])->validate();
        $reason = $this->sanitize((string) $data['reason']);
        $idempotencyHash = hash_hmac('sha256', 'revoke|'.$request->user()->id.'|'.$data['idempotency_key'], (string) config('app.key'));
        $fingerprint = hash_hmac('sha256', json_encode([$grantId, $reason], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), (string) config('app.key'));
        DB::transaction(function () use ($request, $grantId, $reason, $idempotencyHash, $fingerprint): void {
            $existing = ModerationAudit::query()->where('idempotency_hash', $idempotencyHash)->first();
            if ($existing) {
                abort_unless(hash_equals((string) $existing->idempotency_fingerprint, $fingerprint), 409);

                return;
            }
            $grant = ModeratorGrant::query()->whereKey($grantId)->lockForUpdate()->firstOrFail();
            abort_if($grant->user_id === $request->user()->id, 403);
            $previousState = $grant->is_active ? 'active' : 'inactive';
            $grant->forceFill([
                'is_active' => false,
                'changed_by_user_id' => $request->user()->id,
                'reason' => $reason,
                'revoked_at' => now(),
            ])->save();
            $this->auditGrantChange($request, $grant, 'revoke', $previousState, 'inactive', $reason, $idempotencyHash, $fingerprint);
        }, 3);

        return redirect()->route('moderation.grants', ['locale' => $locale])->with('status', __('moderation.grant_saved'));
    }

    private function auditGrantChange(Request $request, ModeratorGrant $grant, string $decision, string $previous, string $next, string $reason, string $idempotencyHash, string $fingerprint): void
    {
        ModerationAudit::query()->create([
            'actor_user_id' => $request->user()->id,
            'grant_snapshot' => ['role' => 'admin', 'locale' => '*'],
            'target_type' => 'moderator_grant',
            'target_key' => (string) $grant->user_id,
            'decision' => $decision,
            'reason_code' => 'administrative_access_change',
            'reason' => $reason,
            'previous_state' => $previous,
            'new_state' => $next,
            'idempotency_hash' => $idempotencyHash,
            'idempotency_fingerprint' => $fingerprint,
            'request_id' => (string) ($request->header('X-Request-Id') ?: Str::uuid()),
            'created_at' => now(),
        ]);
    }

    private function sanitize(string $value): string
    {
        $value = strip_tags(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '');

        return mb_substr(trim($value), 0, (int) config('lavbe.limits.sanitized_message'), 'UTF-8');
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
