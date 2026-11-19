<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_returns_successful_response(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_homepage_is_server_rendered_with_lavbe_identity(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<h1>Lavbe</h1>', false)
            ->assertSee('Etiraflar Vadisi');
    }
}
