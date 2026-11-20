<?php

namespace App\Http\Controllers;

use App\Models\Confession;
use App\Models\ConfessionOwnership;
use App\Models\ConfessionRevision;
use App\Models\ModerationAudit;
use App\Models\ModerationOutbox;
use App\Support\Lavbe\LocaleRegistry;
use App\Support\Lavbe\RoutePatterns;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class GuestConfessionController extends Controller
{
    public function create(string $locale, LocaleRegistry $locales): Response|View
    {
        abort_unless($locales->bySegment($locale), 404);

        return $this->privateView('confessions.create', [
            'categories' => collect($locales->catalog()['categories'])->map(fn (array $item): array => [
                'key' => $item['key'],
                'label' => $item['labels'][app()->getLocale()] ?? $item['labels']['en'],
                'description' => $item['descriptions'][app()->getLocale()] ?? $item['descriptions']['en'],
                'icon' => $item['icon'],
            ]),
            'idempotencyKey' => $this->randomToken(),
        ]);
    }

    public function deviceIndex(Request $request, string $locale, LocaleRegistry $locales): Response|View
    {
        abort_unless($locales->bySegment($locale), 404);
        $token = $request->cookie('lavbe_device');
        $deviceHash = is_string($token) && preg_match('/^[A-Za-z0-9_-]{40,48}$/', $token) ? $this->keyedHash($token) : null;
        $legacyCookies = $this->legacyOwnerCookieHashes($request);
        $query = Confession::query()->whereRaw('1 = 0');
        if ($deviceHash !== null) {
            $query->orWhereHas('ownership', fn ($ownership) => $ownership
                ->whereNull('user_id')->where('device_token_hash', $deviceHash));
        }
        foreach ($legacyCookies as $publicId => $ownerHash) {
            $query->orWhere(fn ($confessions) => $confessions->where('public_id', $publicId)
                ->whereHas('ownership', fn ($ownership) => $ownership->whereNull('user_id')->where('owner_token_hash', $ownerHash)));
        }
        $confessions = $query->with(['ownership', 'revisions'])->orderByDesc('created_at')
            ->paginate((int) config('feed.page_size'));

        return $this->privateView('confessions.device-index', [
            'confessions' => $confessions,
            'canForgetDevice' => $deviceHash !== null || $legacyCookies !== [],
        ]);
    }

    public function forgetDevice(Request $request, string $locale, LocaleRegistry $locales): Response
    {
        abort_unless($locales->bySegment($locale), 404);
        $token = $request->cookie('lavbe_device');
        $deviceHash = is_string($token) && preg_match('/^[A-Za-z0-9_-]{40,48}$/', $token) ? $this->keyedHash($token) : null;
        $legacyCookies = $this->legacyOwnerCookieHashes($request);
        $revokedCookieNames = DB::transaction(function () use ($deviceHash, $legacyCookies): array {
            if ($deviceHash === null && $legacyCookies === []) {
                return [];
            }
            $ownerships = ConfessionOwnership::query()->with('confession:id,public_id')->whereNull('user_id')
                ->where(function ($query) use ($deviceHash, $legacyCookies): void {
                    if ($deviceHash !== null) {
                        $query->where('device_token_hash', $deviceHash);
                    }
                    foreach ($legacyCookies as $publicId => $ownerHash) {
                        $query->orWhere(fn ($legacy) => $legacy->whereHas('confession', fn ($confession) => $confession->where('public_id', $publicId))
                            ->where('owner_token_hash', $ownerHash));
                    }
                })->orderBy('confession_id')->lockForUpdate()->get();
            $cookieNames = [];
            foreach ($ownerships as $ownership) {
                $ownership->forceFill([
                    'session_token_hash' => $this->keyedHash('revoked-device-session|'.random_bytes(32)),
                    'owner_token_hash' => $this->keyedHash('revoked-device-owner|'.random_bytes(32)),
                    'device_token_hash' => null,
                    'credential_version' => $ownership->credential_version + 1,
                ])->save();
                if ($ownership->confession) {
                    $cookieNames[] = $this->ownerCookieName($ownership->confession);
                }
            }

            return $cookieNames;
        }, 3);
        $revokedCookieNames = array_values(array_unique(array_merge(
            $revokedCookieNames,
            array_map(fn (string $publicId): string => 'lavbe_owner_'.$publicId, array_keys($legacyCookies)),
        )));

        $response = redirect()->route('confessions.device', ['locale' => $locale])
            ->with('status', __('guest.device_forgotten'))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer');
        $response->headers->clearCookie('lavbe_device');
        foreach ($revokedCookieNames as $cookieName) {
            $response->headers->clearCookie($cookieName);
        }

        return $response;
    }

    public function store(Request $request, string $locale, LocaleRegistry $locales): Response|View
    {
        abort_unless($locales->bySegment($locale), 404);
        if ($limited = $this->rateLimited($request, 'submission', 'guest_submission')) {
            return $limited;
        }

        $categoryKeys = array_column($locales->catalog()['categories'], 'key');
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:'.config('lavbe.limits.confession_title')],
            'author_display_name' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if (mb_strlen(trim((string) $value), 'UTF-8') > (int) config('lavbe.limits.confession_body')) {
                    $fail(__('guest.validation_body_max', ['limit' => config('lavbe.limits.confession_body')]));
                }
            }],
            'category_key' => ['nullable', 'string', Rule::in($categoryKeys)],
            'response_expectation' => ['nullable', Rule::in(['share', 'similar', 'advice'])],
            'age_confirmed' => ['accepted'],
            'remember_device' => ['sometimes', 'accepted'],
            'idempotency_key' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{'.config('lavbe.limits.guest_idempotency_min').','.config('lavbe.limits.guest_idempotency').'}$/'],
        ], [
            'body.required' => __('guest.validation_body_required'),
            'age_confirmed.accepted' => __('guest.validation_age_required'),
            'title.required' => __('guest.validation_title_required'),
            'title.max' => __('guest.validation_title_max'),
            'category_key.in' => __('guest.validation_topic'),
            'response_expectation.in' => __('guest.validation_expectation'),
            'idempotency_key.required' => __('guest.validation_refresh'),
            'idempotency_key.regex' => __('guest.validation_refresh'),
            'idempotency_key.string' => __('guest.validation_refresh'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('confessions.create', ['locale' => $locale])
                ->withErrors($validator)
                ->withInput($request->only(['title', 'author_display_name', 'body', 'category_key', 'response_expectation', 'age_confirmed', 'remember_device', 'idempotency_key']));
        }
        $data = $validator->validated();
        $data['title'] = trim($data['title']);
        $data['author_display_name'] = isset($data['author_display_name']) ? trim($data['author_display_name']) : null;
        $data['body'] = trim($data['body']);
        $idempotencyHash = $this->keyedHash($data['idempotency_key']);
        $rememberDevice = $request->boolean('remember_device');
        $deviceToken = $rememberDevice ? ($request->cookie('lavbe_device') ?: $this->deviceTokenForSubmission($data['idempotency_key'])) : null;
        $sessionTokenHash = $this->keyedHash($request->session()->getId());
        $fingerprint = $this->keyedHash(json_encode([
            'locale' => app()->getLocale(),
            'title' => $data['title'],
            'body' => $data['body'],
            'category_key' => $data['category_key'] ?? null,
            'response_expectation' => $data['response_expectation'] ?? null,
            'author_display_name' => trim((string) ($request->input('author_display_name') ?? '')),
            'age_confirmed' => true,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        try {
            [$confession, $recoveryCode] = DB::transaction(function () use ($data, $idempotencyHash, $fingerprint, $deviceToken, $sessionTokenHash): array {
                $publicId = (string) Str::uuid();
                $recoveryCode = $this->randomToken();

                $confession = Confession::query()->create([
                    'locale' => app()->getLocale(),
                    'status' => 'unpublished',
                    'title' => '',
                    'body' => '',
                    'public_id' => $publicId,
                    'author_alias' => 'Lavbe #'.strtoupper(bin2hex(random_bytes(4))),
                    'submission_key_hash' => $idempotencyHash,
                    'payload_fingerprint' => $fingerprint,
                    'age_confirmed_at' => now(),
                ]);

                $confession->revisions()->create([
                    'revision' => 1,
                    'title' => $data['title'],
                    'author_display_name' => $data['author_display_name'] ?: null,
                    'body' => $data['body'],
                    'category_key' => $data['category_key'] ?? null,
                    'response_expectation' => $data['response_expectation'] ?? null,
                    'status' => 'pending',
                ]);

                $confession->ownership()->create([
                    'session_token_hash' => $sessionTokenHash,
                    'owner_token_hash' => $sessionTokenHash,
                    'recovery_token_hash' => $this->keyedHash($recoveryCode),
                    'device_token_hash' => $deviceToken ? $this->keyedHash($deviceToken) : null,
                ]);

                return [$confession, $recoveryCode];
            });
        } catch (QueryException $exception) {
            try {
                $confession = Confession::query()->where('submission_key_hash', $idempotencyHash)->first();
            } catch (QueryException $lookupException) {
                return $this->submissionFailure($lookupException);
            }
            if (! $confession) {
                return $this->submissionFailure($exception);
            }
            if (! hash_equals((string) $confession->payload_fingerprint, $fingerprint)) {
                return $this->privateView('confessions.conflict', [], 409);
            }

            $response = $this->privateView('confessions.duplicate', ['confession' => $confession]);
            $savedDeviceHash = $confession->ownership?->device_token_hash;
            if ($deviceToken !== null && is_string($savedDeviceHash) && hash_equals($savedDeviceHash, $this->keyedHash($deviceToken))
                && $request->cookie('lavbe_device') !== $deviceToken) {
                $secure = $request->isSecure() || app()->environment('production');
                $response->headers->setCookie(cookie('lavbe_device', $deviceToken, (int) config('lavbe.cookies.owner_minutes'), '/', null, $secure, true, false, 'strict'));
            }

            return $response;
        }

        $response = $this->privateView('confessions.submitted', [
            'confession' => $confession,
            'recoveryCode' => $recoveryCode,
        ], 201);
        if ($deviceToken !== null && $request->cookie('lavbe_device') !== $deviceToken) {
            $secure = $request->isSecure() || app()->environment('production');
            $response->headers->setCookie(cookie('lavbe_device', $deviceToken, (int) config('lavbe.cookies.owner_minutes'), '/', null, $secure, true, false, 'strict'));
        }

        return $response;
    }

    public function status(Request $request, string $locale, string $publicId): Response|View
    {
        $confession = Confession::query()->where('public_id', $publicId)->first();
        if (! $confession || ! $this->owns($request, $confession)) {
            return $this->privateView('confessions.not-found', [], 404);
        }

        return $this->privateView('confessions.status', [
            'confession' => $confession->load(['revisions' => fn ($query) => $query->orderByDesc('revision')->limit(1)]),
        ]);
    }

    public function canManage(Request $request, Confession $confession): bool
    {
        return $this->owns($request, $confession);
    }

    public function withdraw(Request $request, string $locale, string $publicId): Response
    {
        return $this->ownerTransition($request, $publicId, 'withdraw');
    }

    public function requestRepublish(Request $request, string $locale, string $publicId): Response
    {
        return $this->ownerTransition($request, $publicId, 'request_republish');
    }

    private function ownerTransition(Request $request, string $publicId, string $action): Response
    {
        if ($limited = $this->rateLimited($request, 'owner-transition', 'guest_owner_transition')) {
            return $limited;
        }
        $field = $action === 'withdraw' ? 'confirm_withdraw' : 'confirm_republish';
        $validator = Validator::make($request->all(), [
            'expected_lock_version' => ['required', 'integer', 'min:1'],
            'idempotency_key' => ['required', 'regex:/^[a-f0-9]{'.config('lavbe.limits.idempotency_key').'}$/'],
            $field => ['accepted'],
        ]);
        if ($validator->fails()) {
            return $this->privateView('confessions.conflict', [], 422);
        }
        $data = $validator->validated();
        $idempotencyHash = hash_hmac('sha256', 'owner|'.$publicId.'|'.$action.'|'.$data['idempotency_key'], (string) config('app.key'));
        $fingerprint = hash_hmac('sha256', json_encode([
            'public_id' => $publicId,
            'action' => $action,
            'expected_lock_version' => (int) $data['expected_lock_version'],
        ], JSON_THROW_ON_ERROR), (string) config('app.key'));

        $confession = DB::transaction(function () use ($request, $publicId, $action, $data, $idempotencyHash, $fingerprint): ?Confession {
            $confession = Confession::query()->where('public_id', $publicId)->lockForUpdate()->first();
            if (! $confession || ! $this->owns($request, $confession)) {
                return null;
            }
            $existing = ModerationAudit::query()->where('idempotency_hash', $idempotencyHash)->first();
            if ($existing) {
                abort_unless(hash_equals((string) $existing->idempotency_fingerprint, $fingerprint), 409);

                return $confession;
            }
            $expectedVersion = (int) $data['expected_lock_version'];
            $mayCloseFromOlderView = $action === 'withdraw' && $expectedVersion < $confession->lock_version;
            abort_unless($confession->lock_version === $expectedVersion || $mayCloseFromOlderView, 409);
            $previousState = $confession->status;
            $revisionId = null;

            if ($action === 'withdraw') {
                abort_unless(in_array($confession->status, ['unpublished', 'public'], true), 409);
                $revisionId = $confession->public_revision_id
                    ?: $confession->revisions()->where('status', 'pending')->orderByDesc('revision')->value('id');
                $confession->revisions()->where('status', 'pending')->update(['status' => 'cancelled', 'updated_at' => now()]);
                $confession->forceFill(['status' => 'withdrawn', 'republish_requested_at' => null]);
                $eventType = 'withdraw';
                $decision = 'owner_withdraw';
            } else {
                abort_unless($confession->status === 'withdrawn' && $confession->public_revision_id, 409);
                $publicRevision = ConfessionRevision::query()->whereKey($confession->public_revision_id)->firstOrFail();
                $nextRevision = (int) $confession->revisions()->max('revision') + 1;
                $revision = $confession->revisions()->create([
                    'revision' => $nextRevision,
                    'title' => $publicRevision->title,
                    'body' => $publicRevision->body,
                    'category_key' => $publicRevision->category_key,
                    'response_expectation' => $publicRevision->response_expectation,
                    'status' => 'pending',
                ]);
                $revisionId = $revision->id;
                $confession->republish_requested_at = now();
                $eventType = null;
                $decision = 'owner_republish_request';
            }

            $confession->lock_version++;
            if ($eventType !== null) {
                $confession->visibility_version++;
            }
            $confession->save();

            $publicRevision = $confession->public_revision_id
                ? ConfessionRevision::query()->whereKey($confession->public_revision_id)->first()
                : null;
            $publicHash = $publicRevision
                ? hash_hmac('sha256', json_encode([$publicRevision->title, $publicRevision->body, $publicRevision->category_key], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), (string) config('app.key'))
                : null;
            ModerationAudit::query()->create([
                'actor_user_id' => null,
                'grant_snapshot' => ['source' => 'verified_guest_ownership'],
                'target_type' => 'confession',
                'target_key' => (string) $confession->id,
                'target_public_id' => $confession->public_id,
                'revision_id' => $revisionId,
                'decision' => $decision,
                'reason_code' => $action === 'withdraw' ? 'owner_withdrawal' : 'owner_requested_republication',
                'previous_state' => $previousState,
                'new_state' => $confession->status,
                'expected_lock_version' => (int) $data['expected_lock_version'],
                'resulting_lock_version' => $confession->lock_version,
                'previous_public_revision_hash' => $publicHash,
                'new_public_revision_hash' => $publicHash,
                'idempotency_hash' => $idempotencyHash,
                'idempotency_fingerprint' => $fingerprint,
                'request_id' => (string) ($request->header('X-Request-Id') ?: Str::uuid()),
                'created_at' => now(),
            ]);
            if ($eventType !== null) {
                ModerationOutbox::query()->create([
                    'event_key' => hash('sha256', $confession->public_id.'|'.$confession->visibility_version.'|'.$eventType),
                    'target_public_id' => $confession->public_id,
                    'event_type' => $eventType,
                    'visibility_version' => $confession->visibility_version,
                    'revision_id' => $revisionId,
                    'revision_hash' => $publicHash,
                    'locale' => $confession->locale,
                    'category_key' => $confession->category_key,
                    'occurred_at' => now(),
                ]);
            }

            return $confession;
        }, 3);

        if (! $confession) {
            return $this->privateView('confessions.not-found', [], 404);
        }

        return redirect()->route('confessions.status', ['locale' => app(LocaleRegistry::class)->urlSegment(app()->getLocale()), 'publicId' => $publicId])
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }

    public function recoveryForm(string $locale, string $publicId): Response|View
    {
        return $this->privateView('confessions.recover', compact('publicId'));
    }

    public function recover(Request $request, string $locale, string $publicId): Response|View
    {
        if ($limited = $this->rateLimited($request, 'recovery', 'guest_recovery')) {
            return $limited;
        }

        $validator = Validator::make($request->all(), [
            'recovery_code' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{'.config('lavbe.limits.recovery_code_min').','.config('lavbe.limits.recovery_code').'}$/'],
        ], ['recovery_code.regex' => __('guest.write_not_found'), 'recovery_code.required' => __('guest.write_not_found')]);
        if ($validator->fails()) {
            return redirect()->route('confessions.recovery.form', [
                'locale' => $locale,
                'publicId' => $publicId,
            ])->withErrors($validator);
        }
        $data = $validator->validated();

        $rememberDevice = $request->boolean('remember_device');
        $deviceToken = $rememberDevice ? ($request->cookie('lavbe_device') ?: $this->deviceTokenForRecovery($data['recovery_code'])) : null;
        $rotatedRecoveryCode = $this->randomToken();
        $confession = DB::transaction(function () use ($publicId, $data, $deviceToken, $rotatedRecoveryCode, $request): ?Confession {
            $confession = Confession::query()->where('public_id', $publicId)->lockForUpdate()->first();
            if (! $confession) {
                return null;
            }
            $ownership = ConfessionOwnership::query()->where('confession_id', $confession->id)->lockForUpdate()->first();
            if (! $ownership || ! hash_equals($ownership->recovery_token_hash, $this->keyedHash($data['recovery_code']))) {
                return null;
            }

            $request->session()->regenerate(true);
            $newSessionHash = $this->keyedHash($request->session()->getId());
            $ownership->forceFill([
                'session_token_hash' => $newSessionHash,
                'owner_token_hash' => $newSessionHash,
                'recovery_token_hash' => $this->keyedHash($rotatedRecoveryCode),
                'device_token_hash' => $deviceToken ? $this->keyedHash($deviceToken) : null,
                'credential_version' => $ownership->credential_version + 1,
            ])->save();

            return $confession;
        });
        if (! $confession) {
            return $this->privateView('confessions.not-found', [], 404);
        }

        $response = $this->privateView('confessions.recovered', [
            'confession' => $confession,
            'recoveryCode' => $rotatedRecoveryCode,
        ]);
        $response->headers->clearCookie($this->ownerCookieName($confession));
        if ($deviceToken !== null && $request->cookie('lavbe_device') !== $deviceToken) {
            $secure = $request->isSecure() || app()->environment('production');
            $response->headers->setCookie(cookie('lavbe_device', $deviceToken, (int) config('lavbe.cookies.owner_minutes'), '/', null, $secure, true, false, 'strict'));
        }

        return $response;
    }

    private function owns(Request $request, Confession $confession): bool
    {
        $token = $request->cookie($this->ownerCookieName($confession));
        $ownership = $confession->ownership;
        if ($ownership?->user_id !== null) {
            return (int) $request->user()?->getAuthIdentifier() === (int) $ownership->user_id;
        }
        $sessionHash = $ownership?->session_token_hash;
        $ownerCookieHash = $ownership?->owner_token_hash;
        $sessionMatches = is_string($sessionHash) && hash_equals($sessionHash, $this->keyedHash($request->session()->getId()));
        $cookieMatches = is_string($token) && is_string($ownerCookieHash) && hash_equals($ownerCookieHash, $this->keyedHash($token));
        $deviceToken = $request->cookie('lavbe_device');
        $deviceMatches = is_string($deviceToken) && is_string($ownership?->device_token_hash)
            && hash_equals($ownership->device_token_hash, $this->keyedHash($deviceToken));

        return $sessionMatches || $cookieMatches || $deviceMatches;
    }

    private function ownerCookieName(Confession $confession): string
    {
        return 'lavbe_owner_'.$confession->public_id;
    }

    private function randomToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function deviceTokenForSubmission(string $idempotencyKey): string
    {
        return rtrim(strtr(base64_encode(hash_hmac('sha256', 'remembered-device|'.$idempotencyKey, (string) config('app.key'), true)), '+/', '-_'), '=');
    }

    private function deviceTokenForRecovery(string $recoveryCode): string
    {
        return rtrim(strtr(base64_encode(hash_hmac('sha256', 'remembered-device-recovery|'.$recoveryCode, (string) config('app.key'), true)), '+/', '-_'), '=');
    }

    /** @return array<string, string> UUID => owner-token HMAC */
    private function legacyOwnerCookieHashes(Request $request): array
    {
        $legacy = [];
        foreach ($request->cookies->all() as $name => $token) {
            if (is_string($token) && preg_match('/^[A-Za-z0-9_-]{40,48}$/', $token)
                && preg_match('/^lavbe_owner_('.RoutePatterns::UUID_V4.')$/i', (string) $name, $matches) === 1) {
                $legacy[strtolower($matches[1])] = $this->keyedHash($token);
            }
        }

        return $legacy;
    }

    private function keyedHash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    private function rateLimited(Request $request, string $action, string $limitKey): ?Response
    {
        $limit = config('lavbe.rate_limits.actions.'.$limitKey);
        $identity = $action.'|'.(string) $request->ip();
        $key = 'lavbe:guest-rate:'.hash_hmac('sha256', $identity, (string) config('app.key'));
        if (RateLimiter::tooManyAttempts($key, $limit['attempts'])) {
            return $this->privateView('confessions.limited', [], 429)
                ->header('Retry-After', (string) RateLimiter::availableIn($key));
        }

        RateLimiter::hit($key, $limit['decay_seconds']);

        return null;
    }

    private function submissionFailure(QueryException $exception): Response
    {
        Log::warning('Guest confession submission could not be stored.', [
            'sqlstate' => $exception->errorInfo[0] ?? 'unknown',
        ]);

        return $this->privateView('confessions.unavailable', [], 503);
    }

    private function privateView(string $view, array $data = [], int $status = 200): Response
    {
        return response()->view($view, $data, $status)
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
