<?php

namespace Tests\Feature;

use Tests\TestCase;

class UiFoundationTest extends TestCase
{
    public function test_topic_page_uses_the_shared_feed_layout_and_selected_filter(): void
    {
        $this->get('/topic/family')
            ->assertOk()
            ->assertSee('Ailə')
            ->assertSee('aria-current="page"', false)
            ->assertSee('name="viewport"', false);
    }

    public function test_unknown_topic_uses_the_styled_not_found_screen(): void
    {
        $this->get('/topic/not-a-category')
            ->assertNotFound()
            ->assertSee('Bu səhifə tapılmadı')
            ->assertSee('Oxu lentinə qayıt');
    }

    public function test_admin_preview_is_synthetic_and_is_not_exposed_as_an_admin_route(): void
    {
        $this->get('/_ui/admin-preview')
            ->assertOk()
            ->assertSee('Bu, admin paneli deyil')
            ->assertSee('Nümunə material, real istifadəçi məlumatı deyil');

        $this->get('/admin')->assertNotFound();
    }

    public function test_admin_preview_is_not_available_in_production(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->get('/_ui/admin-preview')->assertNotFound();
    }
}
