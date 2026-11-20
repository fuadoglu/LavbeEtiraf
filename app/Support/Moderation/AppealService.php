<?php

namespace App\Support\Moderation;

use App\Http\Controllers\GuestConfessionController;
use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\ModerationAppeal;
use App\Models\ModerationAudit;
use App\Models\ModeratorGrant;
use App\Models\User;
use App\Support\Lavbe\LocaleRegistry;
use App\Support\Participation\ParticipationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppealService
{
    public function submit(Request $request, string $locale, string $type, string $publicId, string $details, string $idempotencyKey): ModerationAppeal
    {
        abort_unless(in_array($type, ['confession', 'comment'], true), 404);
        $meta = $this->targetMeta($locale, $type, $publicId);
        $ownerHash = $this->assertOwnerAndHash($request, $type, $meta['target']);
        $idempotencyHash = $this->keyedHash('appeal-submit|'.$ownerHash.'|'.$idempotencyKey);
        $details = mb_substr(trim(strip_tags($details)), 0, (int) config('lavbe.limits.appeal_details'), 'UTF-8');
        abort_unless($details !== '', 422);
        $fingerprint = $this->keyedHash(json_encode([
            $type, $meta['target']->id, $meta['confession']->id, $details,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        try {
            return DB::transaction(function () use ($request, $locale, $type, $meta, $ownerHash, $idempotencyHash, $fingerprint, $details): ModerationAppeal {
                $confession = Confession::query()->whereKey($meta['confession']->id)->lockForUpdate()->firstOrFail();
                if ($type === 'confession') {
                    $target = Confession::query()->whereKey($meta['target']->id)->lockForUpdate()->firstOrFail();
                    abort_unless($target->locale === app(LocaleRegistry::class)->contentCode($locale) && $target->status === 'hidden', 404);
                    abort_unless(app(GuestConfessionController::class)->canManage($request, $target), 404);
                    $targetType = 'confession';
                } else {
                    $target = ConfessionComment::query()->whereKey($meta['target']->id)->where('confession_id', $confession->id)->lockForUpdate()->firstOrFail();
                    abort_unless($confession->status === 'public' && $target->status === 'hidden', 404);
                    abort_unless(app(ParticipationService::class)->ownsComment($request, $target), 404);
                    $targetType = 'comment';
                }

                $existing = ModerationAppeal::query()->where('idempotency_hash', $idempotencyHash)->first();
                if ($existing) {
                    abort_unless(hash_equals((string) $existing->payload_fingerprint, $fingerprint), 409);

                    return $existing;
                }
                $hideDecision = $targetType === 'confession' ? 'hide' : 'hide_comment';
                $hideAudit = ModerationAudit::query()->where('target_type', $targetType)
                    ->where('target_key', (string) $target->id)->where('decision', $hideDecision)
                    ->where('new_state', 'hidden')->orderByDesc('id')->lockForUpdate()->first();
                abort_unless($hideAudit && (int) $hideAudit->resulting_lock_version <= (int) $target->lock_version, 404);
                abort_unless(! ModerationAppeal::query()->where('hide_audit_id', $hideAudit->id)->exists(), 409);

                return ModerationAppeal::query()->create([
                    'target_type' => $targetType,
                    'target_id' => $target->id,
                    'confession_id' => $confession->id,
                    'hide_audit_id' => $hideAudit->id,
                    'owner_hash' => $ownerHash,
                    'idempotency_hash' => $idempotencyHash,
                    'payload_fingerprint' => $fingerprint,
                    'details' => $details,
                    'status' => 'pending',
                    'lock_version' => 1,
                ]);
            }, 3);
        } catch (QueryException $exception) {
            $existing = ModerationAppeal::query()->where('idempotency_hash', $idempotencyHash)->first();
            if ($existing) {
                abort_unless(hash_equals((string) $existing->payload_fingerprint, $fingerprint), 409);

                return $existing;
            }
            if (ModerationAppeal::query()->where('target_type', $type)->where('target_id', $meta['target']->id)
                ->whereHas('hideAudit', fn ($query) => $query->where('decision', $type === 'confession' ? 'hide' : 'hide_comment'))
                ->exists()) {
                abort(409);
            }
            throw $exception;
        }
    }

    public function resolve(
        User $actor,
        int $appealId,
        int $expectedAppealLockVersion,
        int $expectedParentLockVersion,
        int $expectedTargetLockVersion,
        int $revisionId,
        string $decision,
        string $reasonCode,
        string $idempotencyKey,
        string $requestId,
    ): void {
        abort_unless(in_array($decision, ['uphold', 'restore'], true), 422);
        abort_unless(in_array($reasonCode, ['privacy', 'harassment', 'threat', 'spam', 'explicit', 'legal', 'other'], true), 422);
        $meta = ModerationAppeal::query()->findOrFail($appealId);
        $idempotencyHash = hash_hmac('sha256', 'appeal-resolution|'.$actor->id.'|'.$idempotencyKey, (string) config('app.key'));
        $fingerprint = $this->keyedHash(json_encode([
            $appealId, $expectedAppealLockVersion, $expectedParentLockVersion, $expectedTargetLockVersion,
            $revisionId, $decision, $reasonCode,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        DB::transaction(function () use ($actor, $appealId, $expectedAppealLockVersion, $expectedParentLockVersion, $expectedTargetLockVersion, $revisionId, $decision, $reasonCode, $idempotencyKey, $requestId, $idempotencyHash, $fingerprint, $meta): void {
            $confession = Confession::query()->whereKey($meta->confession_id)->lockForUpdate()->firstOrFail();
            $appeal = ModerationAppeal::query()->whereKey($appealId)->where('confession_id', $confession->id)->lockForUpdate()->firstOrFail();
            $existing = ModerationAudit::query()->where('idempotency_hash', $idempotencyHash)->first();
            if ($existing) {
                abort_unless(hash_equals((string) $existing->idempotency_fingerprint, $fingerprint), 409);

                return;
            }
            $grant = ModeratorGrant::query()->where('user_id', $actor->id)->where('role', 'moderator')
                ->where('locale', $confession->locale)->where('is_active', true)->whereNull('revoked_at')->lockForUpdate()->first();
            abort_if(! $grant, 404);
            abort_unless($appeal->status === 'pending' && $appeal->lock_version === $expectedAppealLockVersion, 409);
            abort_unless($confession->lock_version === $expectedParentLockVersion, 409);
            abort_unless($appeal->target_type === 'confession' || $confession->status === 'public', 409);
            $this->assertDifferentReviewerWhenAvailable($actor, $appeal);

            if ($appeal->target_type === 'confession') {
                $target = Confession::query()->whereKey($appeal->target_id)->lockForUpdate()->firstOrFail();
                abort_unless($target->status === 'hidden' && (int) $target->public_revision_id === $revisionId, 409);
                abort_unless($target->lock_version === $expectedTargetLockVersion && $expectedTargetLockVersion === $expectedParentLockVersion, 409);
                if ($decision === 'restore') {
                    app(ModerationService::class)->decide(
                        $actor, $target->id, $revisionId, $expectedParentLockVersion, 'restore', $reasonCode, null, null,
                        $idempotencyKey, true, $requestId,
                    );
                }
                $newState = $decision === 'restore' ? 'public' : 'hidden';
                $targetKey = (string) $target->id;
            } else {
                $target = ConfessionComment::query()->whereKey($appeal->target_id)->where('confession_id', $confession->id)->lockForUpdate()->firstOrFail();
                abort_unless($confession->status === 'public' && $target->status === 'hidden' && $target->lock_version === $expectedTargetLockVersion, 409);
                abort_unless((int) $target->public_revision_id === $revisionId, 409);
                if ($decision === 'restore') {
                    app(ModerationService::class)->decideParticipation(
                        $actor, 'comment', $target->id, $revisionId, $expectedTargetLockVersion, $expectedParentLockVersion,
                        'restore', $reasonCode, $idempotencyKey, $requestId,
                    );
                }
                $newState = $decision === 'restore' ? 'public' : 'hidden';
                $targetKey = (string) $target->id;
            }

            $audit = ModerationAudit::query()->create([
                'actor_user_id' => $actor->id,
                'grant_snapshot' => ['grant_id' => $grant->id, 'role' => $grant->role, 'locale' => $grant->locale],
                'target_type' => 'appeal',
                'target_key' => (string) $appeal->id,
                'target_public_id' => $confession->public_id,
                'revision_id' => $revisionId,
                'decision' => $decision.'_appeal',
                'reason_code' => $reasonCode,
                'reason' => $reasonCode,
                'previous_state' => 'pending',
                'new_state' => $newState,
                'expected_lock_version' => $expectedAppealLockVersion,
                'resulting_lock_version' => $expectedAppealLockVersion + 1,
                'idempotency_hash' => $idempotencyHash,
                'idempotency_fingerprint' => $fingerprint,
                'request_id' => $requestId,
                'created_at' => now(),
            ]);
            $appeal->forceFill([
                'status' => 'resolved',
                'resolution' => $decision,
                'reviewed_by_user_id' => $actor->id,
                'resolution_audit_id' => $audit->id,
                'reviewed_at' => now(),
                'lock_version' => $appeal->lock_version + 1,
            ])->save();
        }, 3);
    }

    /** @return array{confession: Confession, target: Confession|ConfessionComment} */
    private function targetMeta(string $locale, string $type, string $publicId): array
    {
        if ($type === 'confession') {
            $confession = Confession::query()->where('public_id', $publicId)->where('locale', app(LocaleRegistry::class)->contentCode($locale))->firstOrFail();

            return ['confession' => $confession, 'target' => $confession];
        }
        $target = ConfessionComment::query()->with('confession')
            ->where('public_id', $publicId)->whereHas('confession', fn ($query) => $query->where('locale', app(LocaleRegistry::class)->contentCode($locale)))
            ->firstOrFail();

        return ['confession' => $target->confession, 'target' => $target];
    }

    private function assertOwnerAndHash(Request $request, string $type, Confession|ConfessionComment $target): string
    {
        if ($type === 'confession') {
            $confession = $target;
            abort_unless(app(GuestConfessionController::class)->canManage($request, $confession), 404);
            $ownership = $confession->ownership;
            $sessionId = $request->session()->getId();
            if (is_string($ownership?->session_token_hash) && hash_equals($ownership->session_token_hash, $this->keyedHash($sessionId))) {
                return $this->keyedHash('appeal-owner|confession|'.$confession->id.'|session|'.$sessionId);
            }
            $cookie = $request->cookie('lavbe_owner_'.$confession->public_id);
            abort_unless(is_string($cookie), 404);

            return $this->keyedHash('appeal-owner|confession|'.$confession->id.'|cookie|'.$cookie);
        }

        $comment = $target;
        abort_unless(app(ParticipationService::class)->ownsComment($request, $comment), 404);
        $ownership = $comment->ownership;
        $sessionId = $request->session()->getId();
        if (is_string($ownership?->session_token_hash) && hash_equals($ownership->session_token_hash, $this->keyedHash($sessionId))) {
            return $this->keyedHash('appeal-owner|comment|'.$comment->id.'|session|'.$sessionId);
        }
        $cookie = $request->cookie('lavbe_comment_'.$comment->public_id);
        abort_unless(is_string($cookie), 404);

        return $this->keyedHash('appeal-owner|comment|'.$comment->id.'|cookie|'.$cookie);
    }

    private function assertDifferentReviewerWhenAvailable(User $actor, ModerationAppeal $appeal): void
    {
        $originalActorId = (int) $appeal->hideAudit?->actor_user_id;
        if ($originalActorId !== (int) $actor->id) {
            return;
        }
        $otherQualifiedReviewerExists = ModeratorGrant::query()->where('role', 'moderator')
            ->where('locale', $appeal->confession->locale)->where('is_active', true)->whereNull('revoked_at')
            ->where('user_id', '<>', $actor->id)
            ->whereHas('user.moderatorMfa', fn ($query) => $query->whereNotNull('confirmed_at'))
            ->exists();
        abort_if($otherQualifiedReviewerExists, 409);
    }

    private function keyedHash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
