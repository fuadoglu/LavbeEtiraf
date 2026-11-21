<?php

namespace App\Support\Moderation;

use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\ModerationAudit;
use App\Models\ModeratorGrant;
use App\Models\ReportGroup;
use App\Models\ReportSubmission;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportService
{
    private const COMMUNITY_REASONS = ['spam', 'privacy', 'harassment', 'threat', 'explicit', 'other'];

    private const LEGAL_REASONS = ['privacy', 'copyright', 'court_order', 'other'];

    public function submit(
        Request $request,
        string $type,
        int $targetId,
        string $channel,
        string $reasonCode,
        ?string $details,
        string $idempotencyKey,
    ): ReportGroup {
        abort_unless(in_array($type, ['confession', 'comment'], true), 404);
        abort_unless(in_array($channel, ['community', 'legal'], true), 404);
        $reasons = $channel === 'legal' ? self::LEGAL_REASONS : self::COMMUNITY_REASONS;
        abort_unless(in_array($reasonCode, $reasons, true), 422);
        $confessionId = $this->confessionId($type, $targetId);
        $reporterHash = $this->reporterHash($request);
        $idempotencyHash = $this->keyedHash('report|'.$reporterHash.'|'.$idempotencyKey);
        $fingerprint = $this->keyedHash(json_encode([
            $type, $targetId, $channel, $reasonCode, $details,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        try {
            return DB::transaction(function () use ($type, $targetId, $channel, $reasonCode, $details, $reporterHash, $idempotencyHash, $fingerprint, $confessionId): ReportGroup {
                $confession = Confession::query()->whereKey($confessionId)->lockForUpdate()->firstOrFail();
                abort_unless($confession->status === 'public' && $confession->public_revision_id, 404);
                $this->assertApprovedConfessionRevision($confession);
                if ($type === 'comment') {
                    $comment = ConfessionComment::query()->whereKey($targetId)->where('confession_id', $confession->id)->lockForUpdate()->firstOrFail();
                    abort_unless($comment->status === 'public' && $comment->public_revision_id, 404);
                    abort_unless($comment->publicRevision?->status === 'approved' && (int) $comment->publicRevision?->comment_id === (int) $comment->id, 404);
                }

                $existingSubmission = ReportSubmission::query()->where('idempotency_hash', $idempotencyHash)->first();
                if ($existingSubmission) {
                    abort_unless(hash_equals((string) $existingSubmission->payload_fingerprint, $fingerprint), 409);

                    return $existingSubmission->reportGroup()->firstOrFail();
                }

                $group = ReportGroup::query()->where('target_type', $type)->where('target_id', $targetId)
                    ->where('channel', $channel)->lockForUpdate()->first();
                if (! $group) {
                    $group = ReportGroup::query()->create([
                        'target_type' => $type,
                        'target_id' => $targetId,
                        'confession_id' => $confession->id,
                        'channel' => $channel,
                        'status' => 'pending',
                        'priority' => $channel === 'legal' ? 'legal' : ($reasonCode === 'threat' ? 'urgent' : 'normal'),
                        'report_count' => 0,
                        'last_reason_code' => $reasonCode,
                        'lock_version' => 1,
                        'first_reported_at' => now(),
                        'last_reported_at' => now(),
                    ]);
                }

                if (ReportSubmission::query()->where('report_group_id', $group->id)->where('reporter_hash', $reporterHash)->exists()) {
                    return $group;
                }

                ReportSubmission::query()->create([
                    'report_group_id' => $group->id,
                    'reporter_hash' => $reporterHash,
                    'idempotency_hash' => $idempotencyHash,
                    'payload_fingerprint' => $fingerprint,
                    'reason_code' => $reasonCode,
                    'details' => $details === null ? null : mb_substr(trim(strip_tags($details)), 0, (int) config('lavbe.limits.report_details'), 'UTF-8'),
                    'created_at' => now(),
                ]);

                $priority = $channel === 'legal' ? 'legal' : ($reasonCode === 'threat' ? 'urgent' : $group->priority);
                $group->forceFill([
                    'status' => 'pending',
                    'priority' => $priority,
                    'report_count' => $group->report_count + 1,
                    'last_reason_code' => $reasonCode,
                    'last_reported_at' => now(),
                    'lock_version' => $group->lock_version + 1,
                    'resolved_by_user_id' => null,
                    'resolution' => null,
                    'resolved_at' => null,
                ])->save();

                return $group;
            }, (int) config('database.transaction_retries'));
        } catch (QueryException $exception) {
            $existing = ReportSubmission::query()->where('idempotency_hash', $idempotencyHash)->first();
            if ($existing) {
                abort_unless(hash_equals((string) $existing->payload_fingerprint, $fingerprint), 409);

                return $existing->reportGroup()->firstOrFail();
            }
            $group = ReportGroup::query()->where('target_type', $type)->where('target_id', $targetId)->where('channel', $channel)->first();
            if ($group && ReportSubmission::query()->where('report_group_id', $group->id)->where('reporter_hash', $reporterHash)->exists()) {
                return $group;
            }
            throw $exception;
        }
    }

    public function decide(
        User $actor,
        int $groupId,
        int $expectedGroupLockVersion,
        int $expectedParentLockVersion,
        int $expectedTargetLockVersion,
        int $revisionId,
        string $decision,
        string $reasonCode,
        string $idempotencyKey,
        string $requestId,
    ): void {
        abort_unless(in_array($decision, ['dismiss', 'hide'], true), 422);
        abort_unless(in_array($reasonCode, ['no_violation', 'privacy', 'harassment', 'threat', 'spam', 'explicit', 'legal', 'other'], true), 422);
        $meta = ReportGroup::query()->findOrFail($groupId);
        $idempotencyHash = hash_hmac('sha256', 'report-resolution|'.$actor->id.'|'.$idempotencyKey, (string) config('app.key'));
        $fingerprint = $this->keyedHash(json_encode([
            $groupId, $expectedGroupLockVersion, $expectedParentLockVersion, $expectedTargetLockVersion,
            $revisionId, $decision, $reasonCode,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        DB::transaction(function () use ($actor, $groupId, $expectedGroupLockVersion, $expectedParentLockVersion, $expectedTargetLockVersion, $revisionId, $decision, $reasonCode, $idempotencyKey, $requestId, $idempotencyHash, $fingerprint, $meta): void {
            $confession = Confession::query()->whereKey($meta->confession_id)->lockForUpdate()->firstOrFail();
            $group = ReportGroup::query()->whereKey($groupId)->where('confession_id', $confession->id)->lockForUpdate()->firstOrFail();
            $existing = ModerationAudit::query()->where('idempotency_hash', $idempotencyHash)->first();
            if ($existing) {
                abort_unless(hash_equals((string) $existing->idempotency_fingerprint, $fingerprint), 409);

                return;
            }
            $grant = ModeratorGrant::query()->where('user_id', $actor->id)->where('role', 'moderator')
                ->where('locale', $confession->locale)->where('is_active', true)->whereNull('revoked_at')->lockForUpdate()->first();
            abort_if(! $grant, 404);
            abort_unless($group->status === 'pending' && $group->lock_version === $expectedGroupLockVersion, 409);
            abort_unless($confession->lock_version === $expectedParentLockVersion, 409);

            if ($decision === 'hide') {
                if ($group->target_type === 'confession') {
                    abort_unless($expectedTargetLockVersion === $expectedParentLockVersion, 409);
                    app(ModerationService::class)->decide(
                        $actor, $confession->id, $revisionId, $expectedParentLockVersion, 'hide', $reasonCode, null, null,
                        $idempotencyKey, false, $requestId,
                    );
                } else {
                    app(ModerationService::class)->decideParticipation(
                        $actor, 'comment', $group->target_id, $revisionId, $expectedTargetLockVersion, $expectedParentLockVersion,
                        'hide', $reasonCode, $idempotencyKey, $requestId,
                    );
                }
            } else {
                $targetLockVersion = $expectedTargetLockVersion;
                if ($group->target_type === 'confession') {
                    abort_unless($expectedTargetLockVersion === $expectedParentLockVersion, 409);
                    abort_unless((int) $confession->public_revision_id === $revisionId, 409);
                    abort_unless($confession->status === 'public', 409);
                    $targetLockVersion = $confession->lock_version;
                } else {
                    $comment = ConfessionComment::query()->whereKey($group->target_id)->where('confession_id', $confession->id)->lockForUpdate()->firstOrFail();
                    abort_unless($comment->status === 'public' && $comment->lock_version === $expectedTargetLockVersion, 409);
                }
                $this->assertRevisionMatchesTarget($group, $revisionId, $confession->id);
            }

            $beforeState = $decision === 'dismiss' ? 'pending' : 'public';
            $afterState = $decision === 'dismiss' ? 'dismissed' : 'hidden';
            $group->forceFill([
                'status' => 'resolved',
                'resolution' => $afterState,
                'resolved_by_user_id' => $actor->id,
                'resolved_at' => now(),
                'lock_version' => $group->lock_version + 1,
            ])->save();

            ModerationAudit::query()->create([
                'actor_user_id' => $actor->id,
                'grant_snapshot' => ['grant_id' => $grant->id, 'role' => $grant->role, 'locale' => $grant->locale],
                'target_type' => 'report_group',
                'target_key' => (string) $group->id,
                'target_public_id' => $confession->public_id,
                'revision_id' => $revisionId,
                'decision' => $decision.'_report',
                'reason_code' => $reasonCode,
                'reason' => $reasonCode,
                'previous_state' => $beforeState,
                'new_state' => $afterState,
                'expected_lock_version' => $expectedGroupLockVersion,
                'resulting_lock_version' => $group->lock_version,
                'idempotency_hash' => $idempotencyHash,
                'idempotency_fingerprint' => $fingerprint,
                'request_id' => $requestId,
                'created_at' => now(),
            ]);
        }, (int) config('database.transaction_retries'));
    }

    private function confessionId(string $type, int $targetId): int
    {
        return $type === 'confession'
            ? (int) Confession::query()->whereKey($targetId)->value('id')
            : (int) ConfessionComment::query()->whereKey($targetId)->value('confession_id');
    }

    private function assertApprovedConfessionRevision(Confession $confession): void
    {
        abort_unless($confession->publicRevision?->status === 'approved' && (int) $confession->publicRevision?->confession_id === (int) $confession->id, 404);
    }

    private function assertRevisionMatchesTarget(ReportGroup $group, int $revisionId, int $confessionId): void
    {
        if ($group->target_type === 'confession') {
            abort_unless($group->confession()->where('public_revision_id', $revisionId)->exists(), 409);

            return;
        }

        $comment = ConfessionComment::query()->whereKey($group->target_id)->where('confession_id', $confessionId)->firstOrFail();
        abort_unless($comment->public_revision_id === $revisionId, 409);
    }

    private function reporterHash(Request $request): string
    {
        $identity = $request->user()
            ? 'account|'.$request->user()->getAuthIdentifier()
            : 'session|'.$request->session()->getId();

        return $this->keyedHash('reporter|'.$identity);
    }

    private function keyedHash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
