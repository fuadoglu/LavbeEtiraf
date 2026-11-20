<?php

namespace App\Console\Commands;

use App\Support\Push\WebPushDeliveryDispatcher;
use Illuminate\Console\Command;

class DispatchWebPushNotifications extends Command
{
    protected $signature = 'lavbe:deliver-web-push {--limit=10}';

    protected $description = 'Deliver neutral account notifications to opted-in web push devices.';

    public function handle(WebPushDeliveryDispatcher $dispatcher): int
    {
        $result = $dispatcher->dispatch(max(1, min(50, (int) $this->option('limit'))));
        $this->info("Web Push: {$result['sent']} sent, {$result['retried']} retrying, {$result['failed']} failed.");

        return self::SUCCESS;
    }
}
