<?php

namespace App\Support\Accounts;

use App\Models\Confession;
use App\Models\ParticipationOutbox;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\FollowedConfessionActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ParticipationNotificationDelivery
{
    public function dispatch(?int $limit = null): array
    {
        $limit = max(1, min((int) config('delivery.notifications.batch_max'), $limit ?? (int) config('delivery.notifications.batch_size')));
        $followerBatchSize = max(1, (int) config('delivery.notifications.follower_batch_size'));
        $eventIds = ParticipationOutbox::query()->whereNull('dispatched_at')->orderBy('id')->limit($limit)->pluck('id');
        $created = 0;

        foreach ($eventIds as $eventId) {
            $created += DB::transaction(
                fn (): int => $this->dispatchFollowerBatch((int) $eventId, $followerBatchSize),
                (int) config('database.transaction_retries'),
            );
        }

        return ['created' => $created, 'emails_sent' => $this->sendPendingEmails(max(1, (int) config('delivery.notifications.email_batch_size')))];
    }

    private function dispatchFollowerBatch(int $eventId, int $batchSize): int
    {
        $event = ParticipationOutbox::query()->whereKey($eventId)->whereNull('dispatched_at')->lockForUpdate()->first();
        if (! $event) {
            return 0;
        }

        $confession = Confession::query()->whereKey($event->confession_id)->first();
        if (! $confession || $confession->status !== 'public' || $confession->visibility_version < $event->visibility_version) {
            $this->completeEvent($event);

            return 0;
        }

        $followers = $this->eligibleFollowers($event)
            ->where('users.id', '>', (int) ($event->follower_cursor_id ?? 0))
            ->orderBy('users.id')->limit($batchSize)
            ->get(['users.id', 'users.in_app_notifications']);

        $eventKey = hash_hmac('sha256', 'account-notification|'.$event->event_key, (string) config('app.key'));
        $created = DB::table('user_notifications')->insertOrIgnore($followers->map(fn (User $user): array => [
            'user_id' => $user->id,
            'event_key' => $eventKey,
            'event_type' => $event->event_type,
            'in_app' => (bool) $user->in_app_notifications,
            'created_at' => $event->occurred_at,
        ])->all());

        $lastFollowerId = $followers->last()?->id;
        $hasMore = $lastFollowerId !== null && $this->eligibleFollowers($event)
            ->where('users.id', '>', $lastFollowerId)
            ->exists();

        $event->forceFill([
            'follower_cursor_id' => $lastFollowerId ?? $event->follower_cursor_id,
            'dispatched_at' => $hasMore ? null : now(),
            'attempts' => $event->attempts + 1,
            'last_error_code' => null,
        ])->save();

        return $created;
    }

    /** @return Builder<User> */
    private function eligibleFollowers(ParticipationOutbox $event): Builder
    {
        return User::query()->join('confession_follows', 'confession_follows.user_id', '=', 'users.id')
            ->where('confession_follows.confession_id', $event->confession_id)
            ->where(function ($query): void {
                $query->where('users.in_app_notifications', true)->orWhere('users.email_notifications', true);
            });
    }

    private function completeEvent(ParticipationOutbox $event): void
    {
        $event->forceFill([
            'dispatched_at' => now(),
            'attempts' => $event->attempts + 1,
            'last_error_code' => null,
        ])->save();
    }

    private function sendPendingEmails(int $limit): int
    {
        $sent = 0;
        $notifications = UserNotification::query()->whereNull('email_sent_at')->whereNull('email_skipped_at')
            ->whereNull('email_failed_at')
            ->where(function ($query): void {
                $query->whereNull('email_next_attempt_at')->orWhere('email_next_attempt_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('email_claimed_at')->orWhere('email_claimed_at', '<', now()->subMinutes((int) config('delivery.notifications.email_claim_minutes')));
            })->orderBy('id')->limit($limit)->pluck('id');
        foreach ($notifications as $notificationId) {
            $claimed = UserNotification::query()->whereKey($notificationId)->whereNull('email_sent_at')->whereNull('email_skipped_at')
                ->whereNull('email_failed_at')
                ->where(function ($query): void {
                    $query->whereNull('email_next_attempt_at')->orWhere('email_next_attempt_at', '<=', now());
                })
                ->where(function ($query): void {
                    $query->whereNull('email_claimed_at')->orWhere('email_claimed_at', '<', now()->subMinutes((int) config('delivery.notifications.email_claim_minutes')));
                })->update(['email_claimed_at' => now()]);
            if ($claimed !== 1) {
                continue;
            }
            $notification = UserNotification::query()->with('user')->find($notificationId);
            if (! $notification) {
                continue;
            }
            $user = $notification->user;
            if (! $user || ! $user->hasVerifiedEmail() || ! $user->email_notifications) {
                $notification->forceFill(['email_skipped_at' => now()])->save();

                continue;
            }
            try {
                Notification::sendNow($user, new FollowedConfessionActivity);
                $notification->forceFill([
                    'email_sent_at' => now(), 'email_claimed_at' => null,
                    'email_next_attempt_at' => null,
                    'email_attempts' => $notification->email_attempts + 1, 'last_error_code' => null,
                ])->save();
                $sent++;
            } catch (Throwable) {
                $attempts = $notification->email_attempts + 1;
                $maxAttempts = max(1, (int) config('delivery.notifications.email_max_attempts'));
                $initialDelay = max(1, (int) config('delivery.notifications.email_retry_initial_seconds'));
                $maxDelay = max($initialDelay, (int) config('delivery.notifications.email_retry_max_seconds'));
                $exponent = min(max(0, $attempts - 1), max(0, (int) config('delivery.notifications.email_retry_exponent_max')));
                $delay = min($maxDelay, $initialDelay * (2 ** $exponent));
                $notification->forceFill([
                    'email_claimed_at' => null, 'email_attempts' => $notification->email_attempts + 1,
                    'email_next_attempt_at' => $attempts >= $maxAttempts ? null : now()->addSeconds($delay),
                    'email_failed_at' => $attempts >= $maxAttempts ? now() : null,
                    'last_error_code' => 'mail_delivery_failed',
                ])->save();
            }
        }

        return $sent;
    }
}
