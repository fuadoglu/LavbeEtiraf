<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class IndexNowKeyController extends Controller
{
    public function __invoke(string $key): Response
    {
        $configuredKey = (string) config('services.indexnow.key');

        abort_unless($configuredKey !== '' && hash_equals($configuredKey, $key), 404);

        return response($configuredKey, 200, [
            'Content-Type' => \App\Support\Http\ResponseHeaders::CONTENT_TYPE_TEXT,
            'Cache-Control' => \App\Support\Http\ResponseHeaders::CACHE_PUBLIC_MAX_AGE_PREFIX.(int) config('seo.indexnow_key_cache_seconds'),
            'X-Content-Type-Options' => \App\Support\Http\ResponseHeaders::CONTENT_TYPE_OPTIONS_NOSNIFF,
        ]);
    }
}
