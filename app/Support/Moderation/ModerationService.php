<?php

namespace App\Support\Moderation;

use App\Models\CommentResponse;
use App\Models\CommentResponseRevision;
use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\ConfessionCommentRevision;
use App\Models\ConfessionContinuation;
use App\Models\ConfessionContinuationRevision;
use App\Models\ConfessionRevision;
use App\Models\ModerationAudit;
use App\Models\ModerationOutbox;
use App\Models\ModeratorGrant;
use App\Models\ParticipationOutbox;
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
                            'excerpt' => Str::limit(trim(preg_replace('/\s+/u', ' ', $revision->body) ?? ''), (int) config('lavbe.seo_description_excerpt'), ''),
                            'category_key' => $revision->category_key,
                            'response_expectation' => $revision->response_expectation,
                            'public_revision_id' => $revision->id,
                            'published_at' => now(),
                            'public_updated_at' => now(),
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
                        'public_updated_at' => null,
                        'unique_read_count' => 0,
                        'submission_key_hash' => null,
                        'payload_fingerprint' => null,
                        'author_alias' => null,
                        'deleted_at' => now(),
                        'republish_requested_at' => null,
                    ]);
                    DB::table('confession_ownerships')->where('confession_id', $confession->id)->delete();
                    DB::table('confession_readers')->where('confession_id', $confession->id)->delete();
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
            }, (int) config('database.transaction_retries'));
        } catch (QueryException $exception) {
            $existing = ModerationAudit::query()->where('idempotency_hash', $idempotencyHash)->first();
            if (! $existing) {
                throw $exception;
            }
            abort_unless(hash_equals((string) $existing->idempotency_fingerprint, $payloadFingerprint), 409);

            return ['confession' => Confession::query()->findOrFail($confessionId), 'replayed' => true];
        }
    }

    public function decideParticipation(
        User $actor,
        string $type,
        int $targetId,
        int $revisionId,
        int $expectedLockVersion,
        int $expectedParentLockVersion,
        string $decision,
        string $reasonCode,
        string $idempotencyKey,
        string $requestId,
    ): void {
        abort_unless(in_array($type, ['comment', 'response', 'continuation'], true), 422);
        abort_unless(in_array($decision, ['approve', 'reject', 'hide', 'restore'], true), 422);
        $targetMeta = $this->participationTarget($type, $targetId);
        $confessionId = $targetMeta['confession_id'];
        $idempotencyHash = hash_hmac('sha256', 'participation-moderation|'.$actor->id.'|'.$type.'|'.$idempotencyKey, (string) config('app.key'));
        $fingerprint = hash_hmac('sha256', json_encode([
            $type, $targetId, $revisionId, $expectedLockVersion, $expectedParentLockVersion, $decision, $reasonCode,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), (string) config('app.key'));

        DB::transaction(function () use ($actor, $type, $targetId, $revisionId, $expectedLockVersion, $expectedParentLockVersion, $decision, $reasonCode, $idempotencyHash, $fingerprint, $requestId, $confessionId): void {
            $existing = ModerationAudit::query()->where('idempotency_hash', $idempotencyHash)->first();
            if ($existing) {
                abort_unless(hash_equals((string) $existing->idempotency_fingerprint, $fingerprint), 409);

                return;
            }
            $confession = Confession::query()->whereKey($confessionId)->lockForUpdate()->firstOrFail();
            $grant = ModeratorGrant::query()->where('user_id', $actor->id)->where('role', 'moderator')
                ->where('locale', $confession->locale)->where('is_active', true)->whereNull('revoked_at')->lockForUpdate()->first();
            abort_if(! $grant, 404);
            abort_unless($confession->lock_version === $expectedParentLockVersion, 409);
            abort_unless($confession->status === 'public', 409);
            [$target, $revision] = $this->lockParticipationTarget($type, $targetId, $revisionId, $confessionId);
            abort_unless($target->lock_version === $expectedLockVersion, 409);
            if ($type === 'comment' && $target->parent_comment_id !== null && in_array($decision, ['approve', 'restore'], true)) {
                abort_unless(ConfessionComment::query()->publicApproved()->whereKey($target->parent_comment_id)
                    ->where('confession_id', $confession->id)->exists(), 409);
            }
            $previousState = (string) $target->status;
            $eventType = null;
            if ($decision === 'approve') {
                abort_unless($target->status === 'unpublished' && $revision->status === 'pending', 409);
                $revision->forceFill(['status' => 'approved', 'reviewed_by_user_id' => $actor->id, 'reviewed_at' => now()])->save();
                $target->forceFill([
                    'status' => 'public',
                    'body' => $revision->body,
                    'public_revision_id' => $revision->id,
                    'published_at' => now(),
                ]);
                $eventType = $type === 'comment' ? 'comment_activity' : 'publish_'.$type;
            } elseif ($decision === 'reject') {
                abort_unless($target->status === 'unpublished' && $revision->status === 'pending', 409);
                $revision->forceFill(['status' => 'rejected', 'reviewed_by_user_id' => $actor->id, 'reviewed_at' => now()])->save();
                $target->status = 'rejected';
            } elseif ($decision === 'hide') {
                abort_unless($target->status === 'public', 409);
                $target->status = 'hidden';
                $target->body = '';
                $eventType = 'hide_'.$type;
                if ($type === 'comment') {
                    $target->response()->where('status', 'public')->update([
                        'status' => 'hidden',
                        'body' => '',
                        'visibility_version' => DB::raw('visibility_version + 1'),
                        'updated_at' => now(),
                    ]);
                    if ($target->parent_comment_id === null) {
                        $replyIds = ConfessionComment::query()->where('parent_comment_id', $target->id)->pluck('id');
                        if ($replyIds->isNotEmpty()) {
                            ConfessionComment::query()->whereIn('id', $replyIds)->where('status', 'public')->update([
                                'status' => 'hidden', 'body' => '',
                                'lock_version' => DB::raw('lock_version + 1'),
                                'visibility_version' => DB::raw('visibility_version + 1'), 'updated_at' => now(),
                            ]);
                            ConfessionComment::query()->whereIn('id', $replyIds)->where('status', 'unpublished')->update([
                                'status' => 'rejected', 'body' => '',
                                'lock_version' => DB::raw('lock_version + 1'),
                                'visibility_version' => DB::raw('visibility_version + 1'), 'updated_at' => now(),
                            ]);
                            DB::table('confession_comment_revisions')->whereIn('comment_id', $replyIds)->where('status', 'pending')
                                ->update(['status' => 'cancelled', 'updated_at' => now()]);
                            DB::table('comment_responses')->whereIn('comment_id', $replyIds)->where('status', 'public')->update([
                                'status' => 'hidden', 'body' => '',
                                'visibility_version' => DB::raw('visibility_version + 1'), 'updated_at' => now(),
                            ]);
                        }
                    }
                }
            } else {
                abort_unless($target->status === 'hidden' && $revision->status === 'approved', 409);
                abort_unless((int) $target->public_revision_id === (int) $revision->id, 409);
                $target->forceFill(['status' => 'public', 'body' => $revision->body]);
                $eventType = $type === 'comment' ? 'comment_activity' : 'restore_'.$type;
            }
            $target->lock_version++;
            $target->visibility_version++;
            $target->save();
            $confession->lock_version++;
            if ($eventType !== null) {
                $confession->visibility_version++;
            }
            $confession->save();
            ModerationAudit::query()->create([
                'actor_user_id' => $actor->id,
                'grant_snapshot' => ['grant_id' => $grant->id, 'role' => $grant->role, 'locale' => $grant->locale],
                'target_type' => $type,
                'target_key' => (string) $target->id,
                'target_public_id' => $confession->public_id,
                'revision_id' => $revisionId,
                'decision' => $decision.'_'.$type,
                'reason_code' => $reasonCode,
                'reason' => $this->sanitizeMessage($reasonCode),
                'previous_state' => $previousState,
                'new_state' => $target->status,
                'expected_lock_version' => $expectedLockVersion,
                'resulting_lock_version' => $target->lock_version,
                'idempotency_hash' => $idempotencyHash,
                'idempotency_fingerprint' => $fingerprint,
                'request_id' => $requestId,
                'created_at' => now(),
            ]);
            if ($eventType !== null) {
                $group = $eventType === 'comment_activity' ? (string) intdiv(now()->timestamp, 900) : (string) $target->id;
                $eventKey = hash('sha256', implode('|', [$confession->id, $eventType, $group]));
                ParticipationOutbox::query()->firstOrCreate(['event_key' => $eventKey], [
                    'confession_id' => $confession->id,
                    'event_type' => $eventType,
                    'visibility_version' => $confession->visibility_version,
                    'target_id' => $eventType === 'comment_activity' ? null : $target->id,
                    'occurred_at' => now(),
                ]);
            }
        }, (int) config('database.transaction_retries'));
    }

    /** @return array{confession_id: int} */
    private function participationTarget(string $type, int $targetId): array
    {
        if ($type === 'comment') {
            $target = ConfessionComment::query()->findOrFail($targetId);

            return ['confession_id' => (int) $target->confession_id];
        }
        if ($type === 'continuation') {
            $target = ConfessionContinuation::query()->findOrFail($targetId);

            return ['confession_id' => (int) $target->confession_id];
        }
        $response = CommentResponse::query()->with('comment')->findOrFail($targetId);

        return ['confession_id' => (int) $response->comment->confession_id];
    }

    /** @return array{0: ConfessionComment|ConfessionContinuation|CommentResponse, 1: ConfessionCommentRevision|ConfessionContinuationRevision|CommentResponseRevision} */
    private function lockParticipationTarget(string $type, int $targetId, int $revisionId, int $confessionId): array
    {
        if ($type === 'comment') {
            $target = ConfessionComment::query()->whereKey($targetId)->where('confession_id', $confessionId)->lockForUpdate()->firstOrFail();
            $revision = ConfessionCommentRevision::query()->whereKey($revisionId)->where('comment_id', $target->id)->lockForUpdate()->firstOrFail();
        } elseif ($type === 'continuation') {
            $target = ConfessionContinuation::query()->whereKey($targetId)->where('confession_id', $confessionId)->lockForUpdate()->firstOrFail();
            $revision = ConfessionContinuationRevision::query()->whereKey($revisionId)->where('continuation_id', $target->id)->lockForUpdate()->firstOrFail();
        } else {
            $target = CommentResponse::query()->whereKey($targetId)->whereHas('comment', fn ($query) => $query->where('confession_id', $confessionId))->lockForUpdate()->firstOrFail();
            $revision = CommentResponseRevision::query()->whereKey($revisionId)->where('response_id', $target->id)->lockForUpdate()->firstOrFail();
        }

        return [$target, $revision];
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
            'author_display_name' => $revision->author_display_name,
            'body' => $revision->body,
            'category_key' => $revision->category_key,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    private function sanitizeMessage(string $value): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

        return mb_substr(trim($value), 0, (int) config('lavbe.limits.sanitized_message'), 'UTF-8');
    }
}
