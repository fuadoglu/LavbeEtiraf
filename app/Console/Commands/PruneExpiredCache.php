<?php

namespace App\Console\Commands;

use Illuminate\Cache\DatabaseStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PruneExpiredCache extends Command
{
    protected $signature = 'lavbe:prune-expired-cache';

    protected $description = 'Remove expired entries from the configured database cache.';

    public function handle(): int
    {
        if (! Cache::getStore() instanceof DatabaseStore) {
            $this->info('The active cache store is not a database store.');

            return self::SUCCESS;
        }
        $settings = config('cache.stores.'.config('cache.default'));
        $deleted = DB::connection($settings['connection'] ?? null)
            ->table($settings['table'])->where('expiration', '<=', now()->timestamp)->delete();
        $this->info("Removed {$deleted} expired cache entries.");

        return self::SUCCESS;
    }
}
