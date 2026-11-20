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
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
