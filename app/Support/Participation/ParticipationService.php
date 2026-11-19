<?php

namespace App\Support\Participation;

use App\Http\Controllers\GuestConfessionController;
use App\Models\CommentOwnership;
use App\Models\CommentResponse;
use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\ConfessionContinuation;
use App\Models\ConfessionParticipant;
use App\Models\ConfessionSupportReaction;
use App\Models\ParticipationOutbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ParticipationService
{
    public function ownsComment(Request $request, ConfessionComment $comment): bool
    {
        $ownership = $comment->ownership;
        if (! $ownership) {
            return false;
        }
        $sessionMatches = is_string($ownership->session_token_hash)
            && hash_equals($ownership->session_token_hash, $this->keyedHash($request->session()->getId()));
        $cookie = $request->cookie('lavbe_comment_'.$comment->public_id);
        $cookieMatches = is_string($cookie) && is_string($ownership->owner_token_hash)
            && hash_equals($ownership->owner_token_hash, $this->keyedHash($cookie));

        return $sessionMatches || $cookieMatches;
    }

    /** @return array{comment: ConfessionComment, owner_token: string, recovery_code: string} */
    public function recoverComment(Request $request, string $publicId, string $recoveryCode): array
    {
        return DB::transaction(function () use ($request, $publicId, $recoveryCode): array {
            $comment = ConfessionComment::query()->where('public_id', $publicId)->lockForUpdate()->first();
            $ownership = $comment?->ownership()->lockForUpdate()->first();
            if (! $comment || ! $ownership || ! hash_equals($ownership->recovery_token_hash, $this->keyedHash($recoveryCode))) {
                abort(404);
            }
            $ownerToken = $this->randomToken();
            $newRecoveryCode = $this->randomToken();
            $request->session()->regenerate(true);
            $ownership->forceFill([
                'session_token_hash' => $this->keyedHash($request->session()->getId()),
                'owner_token_hash' => $this->keyedHash($ownerToken),
                'recovery_token_hash' => $this->keyedHash($newRecoveryCode),
                'credential_version' => $ownership->credential_version + 1,
            ])->save();

            return ['comment' => $comment, 'owner_token' => $ownerToken, 'recovery_code' => $newRecoveryCode];
        }, 3);
    }

    public function withdrawComment(Request $request, string $publicId, string $idempotencyKey): void
    {
        $commandHash = $this->commandHash('comment-withdraw', 0, $publicId, $idempotencyKey);
        $fingerprint = $this->fingerprint([$publicId, 'withdraw']);
        $existing = DB::table('participation_idempotencies')->where('idempotency_hash', $commandHash)->first();
        if ($existing) {
            abort_unless(hash_equals((string) $existing->payload_fingerprint, $fingerprint), 409);

            return;
        }
        $target = ConfessionComment::query()->where('public_id', $publicId)->first(['id', 'confession_id']);
        abort_if(! $target, 404);
        DB::transaction(function () use ($request, $publicId, $target, $commandHash, $fingerprint): void {
            $parent = Confession::query()->whereKey($target->confession_id)->lockForUpdate()->firstOrFail();
            $existing = DB::table('participation_idempotencies')->where('idempotency_hash', $commandHash)->first();
            if ($existing) {
                abort_unless(hash_equals((string) $existing->payload_fingerprint, $fingerprint), 409);

                return;
            }
            $comment = ConfessionComment::query()->whereKey($target->id)->where('public_id', $publicId)->lockForUpdate()->first();
            abort_if(! $comment, 404);
            abort_unless($this->ownsComment($request, $comment), 404);
            abort_unless(in_array($comment->status, ['unpublished', 'public', 'hidden'], true), 409);
            $comment->delete();
            if ($comment->status === 'public') {
                $parent->visibility_version++;
                $parent->lock_version++;
                $parent->save();
            }
            DB::table('participation_idempotencies')->insert([
                'idempotency_hash' => $commandHash,
                'payload_fingerprint' => $fingerprint,
                'target_type' => 'comment',
                'target_id' => $target->id,
                'action' => 'withdraw',
                'created_at' => now(),
            ]);
        }, 3);
    }

    public function hasSupport(Request $request, Confession $confession): bool
    {
        $participantHash = $this->keyedHash('participation|'.$confession->id.'|'.$request->session()->getId());

        return ConfessionSupportReaction::query()
            ->where('confession_id', $confession->id)
            ->where('participant_hash', $participantHash)
            ->where('is_active', true)
            ->exists();
    }

    /** @return array{confession: Confession, comment: ConfessionComment, recovery_code: string, owner_token: string} */
    public function submitComment(Request $request, string $publicId, string $body, string $idempotencyKey): array
    {
        return DB::transaction(function () use ($request, $publicId, $body, $idempotencyKey): array {
            $confession = $this->lockPublicConfession($publicId);
            abort_unless($confession->reviews_open, 409);
            $participant = $this->participant($request, $confession);
            $idempotencyHash = $this->commandHash('comment', $confession->id, $participant->participant_hash, $idempotencyKey);
            $fingerprint = $this->fingerprint([$confession->id, $body]);
            $existing = ConfessionComment::query()->where('idempotency_hash', $idempotencyHash)->first();
            if ($existing) {
                abort_unless(hash_equals((string) $existing->idempotency_fingerprint, $fingerprint), 409);

                return ['confession' => $confession, 'comment' => $existing, 'recovery_code' => '', 'owner_token' => ''];
            }

            $recoveryCode = $this->randomToken();
            $ownerToken = $this->randomToken();
            $comment = ConfessionComment::query()->create([
                'confession_id' => $confession->id,
                'public_id' => (string) Str::uuid(),
                'status' => 'unpublished',
                'author_alias' => $participant->author_alias,
                'body' => '',
                'idempotency_hash' => $idempotencyHash,
                'idempotency_fingerprint' => $fingerprint,
            ]);
            $comment->revisions()->create(['revision' => 1, 'body' => $body, 'status' => 'pending']);
            CommentOwnership::query()->create([
                'comment_id' => $comment->id,
                'session_token_hash' => $this->keyedHash($request->session()->getId()),
                'owner_token_hash' => $this->keyedHash($ownerToken),
                'recovery_token_hash' => $this->keyedHash($recoveryCode),
            ]);
            $confession->lock_version++;
            $confession->save();

            return ['confession' => $confession, 'comment' => $comment, 'recovery_code' => $recoveryCode, 'owner_token' => $ownerToken];
        }, 3);
    }

    /** @return array{confession: Confession, continuation: ConfessionContinuation} */
    public function submitContinuation(Request $request, string $publicId, string $body, string $idempotencyKey): array
    {
        return DB::transaction(function () use ($request, $publicId, $body, $idempotencyKey): array {
            $confession = $this->lockPublicConfession($publicId);
            abort_unless(app(GuestConfessionController::class)->canManage($request, $confession), 404);
            $commandHash = $this->commandHash('continuation', $confession->id, (string) $confession->id, $idempotencyKey);
            $fingerprint = $this->fingerprint([$confession->id, $body]);
            $existing = DB::table('participation_idempotencies')->where('idempotency_hash', $commandHash)->first();
            if ($existing) {
                abort_unless(hash_equals((string) $existing->payload_fingerprint, $fingerprint), 409);
                $continuation = ConfessionContinuation::query()->findOrFail($existing->target_id);

                return ['confession' => $confession, 'continuation' => $continuation];
            }

            $continuation = ConfessionContinuation::query()->create([
                'confession_id' => $confession->id,
                'public_id' => (string) Str::uuid(),
                'status' => 'unpublished',
                'body' => '',
            ]);
            $continuation->revisions()->create(['revision' => 1, 'body' => $body, 'status' => 'pending']);
            DB::table('participation_idempotencies')->insert([
                'idempotency_hash' => $commandHash,
                'payload_fingerprint' => $fingerprint,
                'target_type' => 'continuation',
                'target_id' => $continuation->id,
                'action' => 'submit',
                'created_at' => now(),
            ]);
            $confession->lock_version++;
            $confession->save();

            return ['confession' => $confession, 'continuation' => $continuation];
        }, 3);
    }

    public function respondToComment(Request $request, string $publicId, string $commentPublicId, string $body, string $idempotencyKey): void
    {
        DB::transaction(function () use ($request, $publicId, $commentPublicId, $body, $idempotencyKey): void {
            $confession = $this->lockPublicConfession($publicId);
            abort_unless(app(GuestConfessionController::class)->canManage($request, $confession), 404);
            $comment = ConfessionComment::query()->where('public_id', $commentPublicId)->where('confession_id', $confession->id)->lockForUpdate()->firstOrFail();
            abort_unless($comment->status === 'public' && $comment->public_revision_id, 409);
            $commandHash = $this->commandHash('response', $confession->id, (string) $comment->id, $idempotencyKey);
            $fingerprint = $this->fingerprint([$confession->id, $comment->id, $body]);
            $existing = DB::table('participation_idempotencies')->where('idempotency_hash', $commandHash)->first();
            if ($existing) {
                abort_unless(hash_equals((string) $existing->payload_fingerprint, $fingerprint), 409);

                return;
            }

            $response = CommentResponse::query()->firstOrCreate(['comment_id' => $comment->id], ['status' => 'unpublished', 'body' => '']);
            $response->revisions()->where('status', 'pending')->update(['status' => 'cancelled', 'updated_at' => now()]);
            $revisionNumber = (int) $response->revisions()->max('revision') + 1;
            $response->revisions()->create(['revision' => $revisionNumber, 'body' => $body, 'status' => 'pending']);
            $response->lock_version++;
            $response->save();
            DB::table('participation_idempotencies')->insert([
                'idempotency_hash' => $commandHash,
                'payload_fingerprint' => $fingerprint,
                'target_type' => 'comment_response',
                'target_id' => $response->id,
                'action' => 'submit',
                'created_at' => now(),
            ]);
            $confession->lock_version++;
            $confession->save();
        }, 3);
    }

    public function setReviewsOpen(Request $request, string $publicId, bool $open, string $idempotencyKey): Confession
    {
        return DB::transaction(function () use ($request, $publicId, $open, $idempotencyKey): Confession {
            $confession = Confession::query()->where('public_id', $publicId)->lockForUpdate()->firstOrFail();
            abort_unless(app(GuestConfessionController::class)->canManage($request, $confession), 404);
            $commandHash = $this->commandHash('reviews', $confession->id, (string) $confession->id, $idempotencyKey);
            $fingerprint = $this->fingerprint([$confession->id, $open]);
            $existing = DB::table('participation_idempotencies')->where('idempotency_hash', $commandHash)->first();
            if ($existing) {
                abort_unless(hash_equals((string) $existing->payload_fingerprint, $fingerprint), 409);

                return $confession;
            }
            $confession->reviews_open = $open;
            $confession->lock_version++;
            $confession->save();
            DB::table('participation_idempotencies')->insert([
                'idempotency_hash' => $commandHash,
                'payload_fingerprint' => $fingerprint,
                'target_type' => 'confession',
                'target_id' => $confession->id,
                'action' => $open ? 'reviews_open' : 'reviews_close',
                'created_at' => now(),
            ]);

            return $confession;
        }, 3);
    }

    public function setSupport(Request $request, string $publicId, bool $active, string $idempotencyKey): Confession
    {
        return DB::transaction(function () use ($request, $publicId, $active, $idempotencyKey): Confession {
            $confession = $this->lockPublicConfession($publicId);
            $participant = $this->participant($request, $confession);
            $commandHash = $this->commandHash('support', $confession->id, $participant->participant_hash, $idempotencyKey);
            $fingerprint = $this->fingerprint([$confession->id, $active]);
            $existingCommand = DB::table('participation_idempotencies')->where('idempotency_hash', $commandHash)->first();
            if ($existingCommand) {
                abort_unless(hash_equals((string) $existingCommand->payload_fingerprint, $fingerprint), 409);

                return $confession;
            }

            $reaction = ConfessionSupportReaction::query()->where('confession_id', $confession->id)->where('participant_hash', $participant->participant_hash)->lockForUpdate()->first();
            $wasActive = (bool) $reaction?->is_active;
            if (! $reaction) {
                $reaction = ConfessionSupportReaction::query()->create([
                    'confession_id' => $confession->id,
                    'participant_hash' => $participant->participant_hash,
                    'is_active' => $active,
                ]);
            } else {
                $reaction->is_active = $active;
                $reaction->save();
            }
            if ($wasActive !== $active) {
                $confession->support_count = max(0, (int) $confession->support_count + ($active ? 1 : -1));
                DB::table('confessions')->where('id', $confession->id)->update(['support_count' => $confession->support_count]);
            }
            DB::table('participation_idempotencies')->insert([
                'idempotency_hash' => $commandHash,
                'payload_fingerprint' => $fingerprint,
                'target_type' => 'confession',
                'target_id' => $confession->id,
                'action' => $active ? 'support_on' : 'support_off',
                'created_at' => now(),
            ]);

            return $confession;
        }, 3);
    }

    private function lockPublicConfession(string $publicId): Confession
    {
        $confession = Confession::query()->public()->where('public_id', $publicId)->lockForUpdate()->firstOrFail();

        return $confession;
    }

    private function participant(Request $request, Confession $confession): ConfessionParticipant
    {
        $hash = $this->keyedHash('participation|'.$confession->id.'|'.$request->session()->getId());
        $participant = ConfessionParticipant::query()->where('confession_id', $confession->id)->where('participant_hash', $hash)->lockForUpdate()->first();
        if ($participant) {
            return $participant;
        }

        return ConfessionParticipant::query()->create([
            'confession_id' => $confession->id,
            'participant_hash' => $hash,
            'author_alias' => 'Lavbe #'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
        ]);
    }

    private function enqueue(Confession $confession, string $eventType, ?int $targetId): void
    {
        $eventKey = hash('sha256', implode('|', [$confession->id, $confession->visibility_version, $eventType, $targetId ?? 0]));
        ParticipationOutbox::query()->firstOrCreate(['event_key' => $eventKey], [
            'confession_id' => $confession->id,
            'event_type' => $eventType,
            'visibility_version' => $confession->visibility_version,
            'target_id' => $targetId,
            'occurred_at' => now(),
        ]);
    }

    private function commandHash(string $action, int $confessionId, string $actorHash, string $key): string
    {
        return $this->keyedHash('participation-command|'.$action.'|'.$confessionId.'|'.$actorHash.'|'.$key);
    }

    private function fingerprint(array $payload): string
    {
        return $this->keyedHash(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function keyedHash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    private function randomToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
