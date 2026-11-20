<?php

namespace App\Console\Commands;

use App\Support\Accounts\ParticipationNotificationDelivery;
use Illuminate\Console\Command;

class DeliverParticipationNotifications extends Command
{
    protected $signature = 'lavbe:deliver-notifications {--limit=}';

    protected $description = 'Deliver neutral account notifications from the participation outbox.';

    public function handle(ParticipationNotificationDelivery $delivery): int
    {
        $result = $delivery->dispatch($this->option('limit') === null ? null : (int) $this->option('limit'));
        $this->info("Created {$result['created']} account notifications; sent {$result['emails_sent']} opted-in emails.");

        return self::SUCCESS;
    }
}
