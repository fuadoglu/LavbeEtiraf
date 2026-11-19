<?php

namespace App\Console\Commands;

use App\Support\Accounts\ParticipationNotificationDelivery;
use Illuminate\Console\Command;

class DeliverParticipationNotifications extends Command
{
    protected $signature = 'lavbe:deliver-notifications {--limit=100}';

    protected $description = 'Deliver neutral account notifications from the participation outbox.';

    public function handle(ParticipationNotificationDelivery $delivery): int
    {
        $result = $delivery->dispatch(max(1, min(500, (int) $this->option('limit'))));
        $this->info("Created {$result['created']} account notifications; sent {$result['emails_sent']} opted-in emails.");

        return self::SUCCESS;
    }
}
