<?php

namespace App\Console\Commands;

use App\Models\ModerationAudit;
use App\Models\ModeratorGrant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BootstrapModeratorAdmin extends Command
{
    protected $signature = 'lavbe:moderator:bootstrap-admin {email} {--reason=} {--confirm}';

    protected $description = 'Grant the first moderator admin role to an existing verified user.';

    public function handle(): int
    {
        if (! config('moderation.allow_bootstrap') || ! $this->option('confirm')) {
            $this->error('Bootstrap requires LAVBE_ALLOW_ADMIN_BOOTSTRAP=true and --confirm.');

            return self::FAILURE;
        }

        $reason = trim((string) $this->option('reason'));
        if (mb_strlen($reason, 'UTF-8') < 5 || mb_strlen($reason, 'UTF-8') > 500) {
            $this->error('Provide a reason between 5 and 500 characters.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $this->argument('email'))->whereNotNull('email_verified_at')->first();
        if (! $user) {
            $this->error('A verified account with that email was not found.');

            return self::FAILURE;
        }
        if (ModeratorGrant::query()->where('role', 'admin')->exists()) {
            $this->error('An admin grant already exists. Use an authenticated admin to change grants.');

            return self::FAILURE;
        }

        $grant = ModeratorGrant::query()->create([
            'user_id' => $user->id,
            'role' => 'admin',
            'locale' => '*',
            'is_active' => true,
            'reason' => $reason,
        ]);
        ModerationAudit::query()->create([
            'actor_user_id' => null,
            'grant_snapshot' => ['source' => 'one_time_authorized_cli_bootstrap', 'grant_id' => $grant->id],
            'target_type' => 'moderator_grant',
            'target_key' => (string) $user->id,
            'decision' => 'bootstrap_admin',
            'reason_code' => 'authorized_initial_provisioning',
            'reason' => mb_substr(strip_tags($reason), 0, 500, 'UTF-8'),
            'request_id' => (string) Str::uuid(),
            'created_at' => now(),
        ]);

        $this->info('Initial admin grant created. Turn off LAVBE_ALLOW_ADMIN_BOOTSTRAP and configure MODERATOR_MFA_KEY before sign-in.');

        return self::SUCCESS;
    }
}
