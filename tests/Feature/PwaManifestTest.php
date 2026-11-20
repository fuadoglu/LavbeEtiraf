<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaManifestTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_is_localized_and_referenced_by_the_app_shell(): void
    {
        $this->get('/pt-br/manifest.webmanifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('lang', 'pt-BR')
            ->assertJsonPath('start_url', '/pt-br')
            ->assertJsonPath('scope', '/')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('icons.0.sizes', '192x192')
            ->assertJsonPath('icons.1.sizes', '512x512');

        $this->get('/az/')
            ->assertOk()
            ->assertSee('/az/manifest.webmanifest', false)
            ->assertSee('apple-touch-icon', false);
        $this->get('/pt-BR/manifest.webmanifest')->assertNotFound();
    }

    public function test_offline_shell_is_static_and_worker_never_handles_html_or_private_routes_as_cached_assets(): void
    {
        $offline = file_get_contents(public_path('offline.html'));
        $worker = file_get_contents(public_path('sw.js'));

        $this->assertIsString($offline);
        $this->assertStringContainsString('You’re offline.', $offline);
        $this->assertStringNotContainsString('confession', strtolower($offline));
        $this->assertSame([192, 192], array_slice(getimagesize(public_path('icons/lavbe-feather-192.png')), 0, 2));
        $this->assertSame([512, 512], array_slice(getimagesize(public_path('icons/lavbe-feather-512.png')), 0, 2));
        $this->assertIsString($worker);
        $this->assertStringContainsString("request.mode === 'navigate'", $worker);
        $this->assertStringContainsString('if (!HASHED_ASSET.test(url.pathname)) return;', $worker);
        $this->assertStringContainsString('url.origin !== self.location.origin', $worker);
        $this->assertStringContainsString("if (response.ok && response.type === 'basic') await cache.put(request, response.clone());", $worker);
    }
}
