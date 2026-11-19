<?php

namespace App\Support\Accounts;

use App\Models\Confession;
use App\Models\ParticipationOutbox;
use App\Models\PushSubscription;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WebPushDelivery;
use App\Notifications\FollowedConfessionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ParticipationNotificationDelivery
{
    public function dispatch(int $limit = 100): array
    {
        $created = DB::transaction(function () use ($limit): int {
            $events = ParticipationOutbox::query()->whereNull('dispatched_at')->orderBy('id')->limit($limit)->lockForUpdate()->get();
            $created = 0;
            foreach ($events as $event) {
                $confession = Confession::query()->whereKey($event->confession_id)->first();
                if ($confession && $confession->status === 'public' && $confession->visibility_version >= $event->visibility_version) {
                    $followers = User::query()->join('confession_follows', 'confession_follows.user_id', '=', 'users.id')
                        ->where('confession_follows.confession_id', $confession->id)
                        ->where(function ($query): void {
                            $query->where('users.in_app_notifications', true)->orWhere('users.email_notifications', true);
                            $query->orWhereHas('pushSubscriptions', fn ($subscriptions) => $subscriptions->where('active', true));
                        })->select('users.*')->get();
                    $eventKey = hash_hmac('sha256', 'account-notification|'.$event->event_key, (string) config('app.key'));
                    foreach ($followers as $user) {
                        $notification = UserNotification::query()->firstOrCreate([
                            'user_id' => $user->id, 'event_key' => $eventKey,
                        ], [
                            'event_type' => $event->event_type,
                            'in_app' => (bool) $user->in_app_notifications,
                            'created_at' => $event->occurred_at,
                        ]);
                        $created += $notification->wasRecentlyCreated ? 1 : 0;
                        if ($notification->wasRecentlyCreated) {
                            $subscriptions = PushSubscription::query()->where('user_id', $user->id)->where('active', true)
                                ->whereNotNull('activated_at')->where('activated_at', '<=', $notification->created_at)
                                ->where(function ($query): void {
                                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                                })->get(['id']);
                            foreach ($subscriptions as $subscription) {
                                WebPushDelivery::query()->firstOrCreate([
                                    'user_notification_id' => $notification->id,
                                    'push_subscription_id' => $subscription->id,
                                ]);
                            }
                        }
                    }
                }
                $event->forceFill([
                    'dispatched_at' => now(), 'attempts' => $event->attempts + 1, 'last_error_code' => null,
                ])->save();
            }

            return $created;
        }, 3);

        return ['created' => $created, 'emails_sent' => $this->sendPendingEmails(100)];
    }

    private function sendPendingEmails(int $limit): int
    {
        $sent = 0;
        $notifications = UserNotification::query()->whereNull('email_sent_at')->whereNull('email_skipped_at')
            ->where(function ($query): void {
                $query->whereNull('email_claimed_at')->orWhere('email_claimed_at', '<', now()->subMinutes(15));
            })->orderBy('id')->limit($limit)->pluck('id');
        foreach ($notifications as $notificationId) {
            $claimed = UserNotification::query()->whereKey($notificationId)->whereNull('email_sent_at')->whereNull('email_skipped_at')
                ->where(function ($query): void {
                    $query->whereNull('email_claimed_at')->orWhere('email_claimed_at', '<', now()->subMinutes(15));
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
                    'email_attempts' => $notification->email_attempts + 1, 'last_error_code' => null,
                ])->save();
                $sent++;
            } catch (Throwable) {
                $notification->forceFill([
                    'email_claimed_at' => null, 'email_attempts' => $notification->email_attempts + 1,
                    'last_error_code' => 'mail_delivery_failed',
                ])->save();
            }
        }

        return $sent;
    }
}
