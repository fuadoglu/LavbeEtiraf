<?php

namespace App\Support\Accounts;

use App\Http\Controllers\GuestConfessionController;
use App\Models\CommentOwnership;
use App\Models\Confession;
use App\Models\ConfessionComment;
use App\Models\ConfessionOwnership;
use App\Models\User;
use App\Support\Participation\ParticipationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountOwnershipService
{
    public function claimConfession(Request $request, User $user, string $publicId): bool
    {
        abort_unless($user->hasVerifiedEmail(), 403);

        return DB::transaction(function () use ($request, $user, $publicId): bool {
            $confession = Confession::query()->where('public_id', $publicId)->lockForUpdate()->firstOrFail();
            $ownership = ConfessionOwnership::query()->where('confession_id', $confession->id)->lockForUpdate()->firstOrFail();
            if ($ownership->user_id !== null) {
                abort_unless((int) $ownership->user_id === (int) $user->id, 409);

                return false;
            }
            abort_unless(app(GuestConfessionController::class)->canManage($request, $confession), 404);
            $ownership->forceFill([
                'user_id' => $user->id,
                'session_token_hash' => null,
                'owner_token_hash' => $this->keyedHash('revoked-owner-token|'.random_bytes(32)),
                'recovery_token_hash' => $this->keyedHash('revoked-owner|'.random_bytes(32)),
                'device_token_hash' => null,
                'credential_version' => $ownership->credential_version + 1,
            ])->save();

            return true;
        }, (int) config('database.transaction_retries'));
    }

    public function claimComment(Request $request, User $user, string $publicId): bool
    {
        abort_unless($user->hasVerifiedEmail(), 403);

        return DB::transaction(function () use ($request, $user, $publicId): bool {
            $lookup = ConfessionComment::query()->where('public_id', $publicId)->first(['id', 'confession_id']);
            abort_unless($lookup, 404);
            Confession::query()->whereKey($lookup->confession_id)->lockForUpdate()->firstOrFail();
            $comment = ConfessionComment::query()->whereKey($lookup->id)->where('confession_id', $lookup->confession_id)->lockForUpdate()->firstOrFail();
            $ownership = CommentOwnership::query()->where('comment_id', $comment->id)->lockForUpdate()->firstOrFail();
            if ($ownership->user_id !== null) {
                abort_unless((int) $ownership->user_id === (int) $user->id, 409);

                return false;
            }
            abort_unless(app(ParticipationService::class)->ownsComment($request, $comment), 404);
            $ownership->forceFill([
                'user_id' => $user->id,
                'session_token_hash' => null,
                'owner_token_hash' => $this->keyedHash('revoked-owner-token|'.random_bytes(32)),
                'recovery_token_hash' => $this->keyedHash('revoked-owner|'.random_bytes(32)),
                'credential_version' => $ownership->credential_version + 1,
            ])->save();

            return true;
        }, (int) config('database.transaction_retries'));
    }

    private function keyedHash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
