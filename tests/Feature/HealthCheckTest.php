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
            ->assertSee('Read')
            ->assertSee('Write')
            ->assertSee('My space');
    }

    public function test_consent_controls_are_present_without_loading_third_party_tags(): void
    {
        config(['services.analytics.measurement_id' => 'G-TEST123']);

        $this->get('/en/?search=private-query')
            ->assertOk()
            ->assertSee('data-consent-dialog', false)
            ->assertSee('data-analytics-consent', false)
            ->assertSee('data-ads-consent disabled', false)
            ->assertSee('name="lavbe-analytics-context"', false)
            ->assertDontSee('googletagmanager.com/gtag/js', false)
            ->assertDontSee('G-TEST123</script>', false);

        $this->get('/az/')->assertOk()->assertDontSee('name="lavbe-analytics-context"', false);
        $this->get('/en/write')->assertOk()->assertDontSee('name="lavbe-analytics-context"', false);
    }
}
