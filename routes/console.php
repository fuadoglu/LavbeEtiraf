<?php

use App\Models\UserNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('lavbe:deliver-notifications')->everyMinute()->withoutOverlapping();
Schedule::command('lavbe:indexnow')->everyMinute()->withoutOverlapping();
Schedule::command('lavbe:prune-expired-cache')->daily()->withoutOverlapping();
Schedule::command('lavbe:prune-confession-readers')->dailyAt(config('analytics.unique_read_cleanup_schedule'))->withoutOverlapping();

Artisan::command('lavbe:prune-confession-readers {--batches=}', function (): int {
    $retentionDays = max(1, (int) config('analytics.unique_read_retention_days'));
    $batchSize = max(1, (int) config('analytics.unique_read_cleanup_batch'));
    $batches = min((int) config('analytics.unique_read_cleanup_max_batches_per_run'), max(1, (int) ($this->option('batches') ?: config('analytics.unique_read_cleanup_batches_per_run'))));
    $cutoff = now()->subDays($retentionDays);
    $deleted = 0;

    for ($batch = 0; $batch < $batches; $batch++) {
        $ids = DB::table('confession_readers')->where(function ($query) use ($cutoff): void {
            $query->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $cutoff);
        })->orderBy('id')->limit($batchSize)->pluck('id');

        if ($ids->isEmpty()) {
            break;
        }

        $deleted += DB::table('confession_readers')->whereIn('id', $ids)->where(function ($query) use ($cutoff): void {
            $query->whereNull('last_seen_at')->orWhere('last_seen_at', '<', $cutoff);
        })->delete();

        if ($ids->count() < $batchSize) {
            break;
        }
    }

    $this->info("Pruned {$deleted} expired reader records.");

    return self::SUCCESS;
})->purpose('Remove expired per-confession unique-reader hashes in bounded batches');

Artisan::command('lavbe:retry-notification-email {notification}', function (int $notification): int {
    $row = UserNotification::query()->with('user')->find($notification);
    if (! $row || $row->email_sent_at || $row->email_skipped_at || ! $row->email_failed_at) {
        $this->error('Notification is not in a retryable failed state.');

        return self::FAILURE;
    }
    if (! $row->user || ! $row->user->hasVerifiedEmail() || ! $row->user->email_notifications) {
        $this->error('The verified recipient is not opted in for email.');

        return self::FAILURE;
    }

    $row->forceFill([
        'email_attempts' => 0,
        'email_next_attempt_at' => now(),
        'email_failed_at' => null,
        'email_claimed_at' => null,
        'last_error_code' => null,
    ])->save();
    $this->info("Notification {$row->id} is queued for retry.");

    return self::SUCCESS;
})->purpose('Requeue one exhausted, opted-in notification email without displaying its content');
