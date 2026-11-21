<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ExpiredCachePruningTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_cache_cleanup_keeps_active_entries(): void
    {
        config(['cache.default' => 'database']);
        Cache::put('lavbe:expired-synthetic', ['id' => 1], now()->addSecond());
        Cache::put('lavbe:active-synthetic', ['id' => 2], now()->addHour());
        $this->travel(2)->seconds();

        $this->artisan('lavbe:prune-expired-cache')->assertSuccessful();
        $this->assertNull(Cache::get('lavbe:expired-synthetic'));
        $this->assertSame(['id' => 2], Cache::get('lavbe:active-synthetic'));
        $this->assertDatabaseCount('cache', 1);
    }
}
