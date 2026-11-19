<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('mysql')]
class MySqlSmokeTest extends TestCase
{
    public function test_configured_mysql_database_is_reachable_after_migrations(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame(1, DB::selectOne('select 1 as connected')->connected);
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('migrations'));
    }
}
