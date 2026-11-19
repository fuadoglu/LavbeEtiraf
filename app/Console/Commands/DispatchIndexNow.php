<?php

namespace App\Console\Commands;

use App\Support\Search\IndexNowDispatcher;
use Illuminate\Console\Command;

class DispatchIndexNow extends Command
{
    protected $signature = 'lavbe:indexnow {--limit=100}';

    protected $description = 'Submit current public confession changes to IndexNow.';

    public function handle(IndexNowDispatcher $dispatcher): int
    {
        $result = $dispatcher->dispatch(max(1, min(500, (int) $this->option('limit'))));
        $this->info("IndexNow: {$result['delivered']} accepted, {$result['retried']} retrying, {$result['failed']} failed.");

        return self::SUCCESS;
    }
}
