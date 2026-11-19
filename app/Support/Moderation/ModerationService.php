<?php

namespace App\Support\Moderation;

use App\Models\Confession;
use App\Models\ConfessionRevision;
use App\Models\ModerationAudit;
use App\Models\ModerationOutbox;
use App\Models\ModeratorGrant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModerationService
{
    private const DECISIONS = ['approve', 'request_changes', 'reject', 'hide', 'restore', 'terminal_delete'];

    /** @return array{confession: Confession, replayed: bool} */
    public function decide(
        User $actor,
        int $confessionId,
        int $revisionId,
        int $expectedLockVersion,
        string $decision,
        string $reasonCode,
        ?string $authorMessage,
        ?string $internalNote,
        string $idempotencyKey,
        bool $restoreReviewed,
        string $requestId,
    ): array {
        abort_unless(in_array($decision, self::DECISIONS, true), 422);
        abort_unless(in_array($reasonCode, ['acceptable', 'privacy', 'harassment', 'threat', 'spam', 'explicit', 'legal', 'other'], true), 422);
        $idempotencyHash = hash_hmac('sha256', 'moderation|'.$actor->id.'|'.$idempotencyKey, (string) config('app.key'));
        $payloadFingerprint = hash_hmac('sha256', json_encode([
            'confession_id' => $confessionId,
            'revision_id' => $revisionId,
            'expected_lock_version' => $expectedLockVersion,
            'decision' => $decision,
            'reason_code' => $reasonCode,
            'author_message' => $authorMessage,
            'internal_note' => $internalNote,
            'restore_reviewed' => $restoreReviewed,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), (string) config('app.key'));

        try {
            return DB::transaction(function () use ($actor, $confessionId, $revisionId, $expectedLockVersion, $decision, $reasonCode, $authorMessage, $internalNote, $idempotencyHash, $payloadFingerprint, $restoreReviewed, $requestId): array {
                $existing = ModerationAudit::query()->where('idempotency_hash', $idempotencyHash)->first();
                if ($existing) {
                    abort_unless(hash_equals((string) $existing->idempotency_fingerprint, $payloadFingerprint), 409);

                    return ['confession' => Confession::query()->findOrFail($confessionId), 'replayed' => true];
                }

                $confession = Confession::query()->whereKey($confessionId)->lockForUpdate()->first();
                abort_if(! $confession, 404);

                $grant = ModeratorGrant::query()
                    ->where('user_id', $actor->id)
                    ->where('role', 'moderator')
                    ->where('locale', $confession->locale)
                    ->where('is_active', true)
                    ->whereNull('revoked_at')
                    ->lockForUpdate()
                    ->first();
                abort_if(! $grant, 404);
                abort_if($confession->lock_version !== $expectedLockVersion, 409);

                $revision = ConfessionRevision::query()
                    ->whereKey($revisionId)
                    ->where('confession_id', $confession->id)
                    ->lockForUpdate()
                    ->first();
                abort_if(! $revision, 404);

                $previousState = (string) $confession->status;
                $previousPublicHash = $confession->public_revision_id
                    ? $this->revisionHash(ConfessionRevision::query()->find($confession->public_revision_id))
                    : null;
                $eventType = null;
                $safeReason = $this->sanitizeMessage($reasonCode);
                $safeAuthorMessage = $authorMessage === null ? null : $this->sanitizeMessage($authorMessage);

                if (in_array($decision, ['approve', 'request_changes', 'reject'], true)) {
                    abort_unless($revision->status === 'pending', 409);
                    if ($decision === 'approve') {
                        abort_unless(in_array($confession->status, ['unpublished', 'public', 'withdrawn'], true), 409);
                    }
                    if ($confession->status === 'withdrawn' && $decision === 'approve') {
                        abort_if(! $confession->republish_requested_at, 409);
                    }
                    $revision->forceFill([
                        'status' => match ($decision) {
                            'approve' => 'approved',
                            'request_changes' => 'changes_requested',
                            default => 'rejected',
                        },
                        'reviewed_by_user_id' => $actor->id,
                        'reviewed_at' => now(),
                        'moderator_message' => $safeAuthorMessage,
                    ])->save();

                    if ($decision === 'approve') {
                        $confession->forceFill([
                            'status' => 'public',
                            'title' => $revision->title ?? '',
                            'body' => $revision->body,
                            'excerpt' => Str::limit(trim(preg_replace('/\s+/u', ' ', $revision->body) ?? ''), 240, ''),
                            'category_key' => $revision->category_key,
                            'response_expectation' => $revision->response_expectation,
                            'public_revision_id' => $revision->id,
                            'published_at' => now(),
                            'ever_public' => true,
                            'republish_requested_at' => null,
                        ]);
                        $eventType = 'publish';
                    }
                } elseif ($decision === 'hide') {
                    abort_unless(in_array($confession->status, ['public', 'withdrawn'], true), 409);
                    abort_unless((int) $confession->public_revision_id === $revision->id && $revision->status === 'approved', 409);
                    $confession->status = 'hidden';
                    $eventType = 'hide';
                } elseif ($decision === 'restore') {
                    abort_unless($confession->status === 'hidden', 409);
                    abort_unless($restoreReviewed && (int) $confession->public_revision_id === $revision->id && $revision->status === 'approved', 422);
                    $confession->status = 'public';
                    $eventType = 'restore';
                } else {
                    abort_unless(in_array($confession->status, ['unpublished', 'public', 'withdrawn', 'hidden'], true), 409);
                    $confession->forceFill([
                        'status' => 'deleted',
                        'title' => '',
                        'body' => '',
                        'excerpt' => null,
                        'category_key' => null,
                        'response_expectation' => null,
                        'public_revision_id' => null,
                        'published_at' => null,
                        'submission_key_hash' => null,
                        'payload_fingerprint' => null,
                        'author_alias' => null,
                        'deleted_at' => now(),
                        'republish_requested_at' => null,
                    ]);
                    DB::table('confession_ownerships')->where('confession_id', $confession->id)->delete();
                    DB::table('confession_revisions')->where('confession_id', $confession->id)->delete();
                    $eventType = 'terminal_delete';
                }

                $confession->lock_version++;
                $confession->visibility_version++;
                $confession->save();

                ModerationAudit::query()->create([
                    'actor_user_id' => $actor->id,
                    'grant_snapshot' => ['grant_id' => $grant->id, 'role' => $grant->role, 'locale' => $grant->locale],
                    'target_type' => 'confession',
                    'target_key' => (string) $confession->id,
                    'target_public_id' => $confession->public_id,
                    'revision_id' => $revisionId,
                    'decision' => $decision,
                    'reason_code' => $reasonCode,
                    'reason' => $safeReason,
                    'internal_note_hash' => $internalNote ? hash_hmac('sha256', $this->sanitizeMessage($internalNote), (string) config('app.key')) : null,
                    'author_message_hash' => $safeAuthorMessage ? hash_hmac('sha256', $safeAuthorMessage, (string) config('app.key')) : null,
                    'previous_state' => $previousState,
                    'new_state' => $confession->status,
                    'expected_lock_version' => $expectedLockVersion,
                    'resulting_lock_version' => $confession->lock_version,
                    'previous_public_revision_hash' => $previousPublicHash,
                    'new_public_revision_hash' => $confession->status === 'public'
                        ? $this->revisionHash($decision === 'approve' ? $revision : ConfessionRevision::query()->find($confession->public_revision_id))
                        : null,
                    'idempotency_hash' => $idempotencyHash,
                    'idempotency_fingerprint' => $payloadFingerprint,
                    'request_id' => $requestId,
                    'created_at' => now(),
                ]);

                if ($eventType !== null) {
                    $this->queueOutbox($confession, $eventType, $decision === 'approve' ? $revision : null);
                }

                return ['confession' => $confession, 'replayed' => false];
            }, 3);
        } catch (QueryException $exception) {
            $existing = ModerationAudit::query()->where('idempotency_hash', $idempotencyHash)->first();
            if (! $existing) {
                throw $exception;
            }
            abort_unless(hash_equals((string) $existing->idempotency_fingerprint, $payloadFingerprint), 409);

            return ['confession' => Confession::query()->findOrFail($confessionId), 'replayed' => true];
        }
    }

    private function queueOutbox(Confession $confession, string $eventType, ?ConfessionRevision $revision): void
    {
        $revisionHash = $this->revisionHash($revision);
        $eventKey = hash('sha256', implode('|', [$confession->public_id, $confession->visibility_version, $eventType]));
        ModerationOutbox::query()->create([
            'event_key' => $eventKey,
            'target_public_id' => $confession->public_id,
            'event_type' => $eventType,
            'visibility_version' => $confession->visibility_version,
            'revision_id' => $revision?->id,
            'revision_hash' => $revisionHash,
            'locale' => $confession->locale,
            'category_key' => $confession->category_key,
            'occurred_at' => now(),
        ]);
    }

    private function revisionHash(?ConfessionRevision $revision): ?string
    {
        if (! $revision) {
            return null;
        }

        return hash_hmac('sha256', json_encode([
            'title' => $revision->title,
            'body' => $revision->body,
            'category_key' => $revision->category_key,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    private function sanitizeMessage(string $value): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

        return mb_substr(trim($value), 0, 500, 'UTF-8');
    }
}
