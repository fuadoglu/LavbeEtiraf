<?php

namespace App\Http\Controllers;

use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Http\JsonResponse;

class PwaManifestController extends Controller
{
    public function show(string $locale, LocaleRegistry $locales): JsonResponse
    {
        $entry = $locales->bySegment($locale);
        abort_unless($entry, 404);

        return response()->json([
            'id' => route('home', ['locale' => $entry['segment']], false),
            'name' => __('lavbe.feed_title').' · Lavbe',
            'short_name' => 'Lavbe',
            'description' => __('lavbe.feed_title'),
            'lang' => $entry['code'],
            'start_url' => route('home', ['locale' => $entry['segment']], false),
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#f8f6f3',
            'theme_color' => '#f8f6f3',
            'icons' => [
                ['src' => asset('icons/lavbe-feather-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('icons/lavbe-feather-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('icons/lavbe-feather.svg'), 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any'],
            ],
        ], 200, ['Cache-Control' => 'public, max-age=3600']);
    }
}
