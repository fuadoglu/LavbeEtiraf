<?php

namespace App\Http\Controllers;

use App\Support\Lavbe\LocalePackageMessages;
use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

class PwaController extends Controller
{
    public function manifest(string $locale, LocaleRegistry $locales, LocalePackageMessages $messages): JsonResponse
    {
        $entry = $locales->bySegment($locale);
        abort_unless($entry, 404);

        return response()->json([
            'id' => '/'.$entry['segment'].'/',
            'name' => 'Lavbe',
            'short_name' => 'Lavbe',
            'description' => $messages->text($entry['segment'], 'seo', 'home_description'),
            'lang' => $entry['code'],
            'start_url' => '/'.$entry['segment'].'/',
            'scope' => '/',
            'display' => 'standalone',
            'display_override' => ['standalone'],
            'orientation' => 'portrait-primary',
            'background_color' => config('theme.day'),
            'theme_color' => config('theme.day'),
            'icons' => [
                ['src' => '/icons/lavbe-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/lavbe-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/icons/lavbe-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ])->header('Content-Type', 'application/manifest+json; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=300')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function offline(string $locale, LocalePackageMessages $messages, LocaleRegistry $locales): Response|View
    {
        $entry = $locales->bySegment($locale);
        abort_unless($entry, 404);

        try {
            $labels = $messages->group($entry['segment'], 'pwa');
        } catch (RuntimeException) {
            abort(503);
        }

        foreach (['offline_title', 'offline_body'] as $key) {
            abort_unless(is_string($labels[$key] ?? null) && trim($labels[$key]) !== '', 503);
        }

        $labels['home'] = $messages->text($entry['segment'], 'navigation', 'read');

        return response()->view('pwa.offline', ['labels' => $labels, 'locale' => $entry])
            ->header('Cache-Control', 'public, max-age=0, must-revalidate')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function worker(LocaleRegistry $locales): Response
    {
        $path = resource_path('js/service-worker.js');
        $source = @file_get_contents($path);
        abort_unless(is_string($source), 503);
        $segments = array_column($locales->all(), 'segment');
        $source = strtr($source, [
            '__LAVBE_LOCALES__' => json_encode($segments, JSON_THROW_ON_ERROR),
            '__LAVBE_DEFAULT_LOCALE__' => json_encode($locales->fallback()['segment'], JSON_THROW_ON_ERROR),
        ]);

        return response($source, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Service-Worker-Allowed' => '/',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
