<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['session.driver' => 'array']);
    }

    public function test_health_endpoint_returns_successful_response(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_homepage_is_server_rendered_with_lavbe_identity(): void
    {
        $this->get('/en/')
            ->assertOk()
            ->assertSee('Lavbe')
            ->assertSee('Confession Valley')
            ->assertSee('All')
            ->assertSee('Oxu');
    }
}
