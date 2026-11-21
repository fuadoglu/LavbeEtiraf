<?php

namespace App\Http\Controllers;

use App\Support\Analytics\UniqueReadCounter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReaderConsentController extends Controller
{
    public function update(Request $request, string $locale): RedirectResponse
    {
        $data = $request->validate([
            'choice' => ['required', 'in:accept,decline'],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ]);
        $prefix = '/'.$locale;
        $path = is_string($data['return_to'] ?? null) ? parse_url($data['return_to'], PHP_URL_PATH) : null;
        $target = is_string($path) && ($path === $prefix || str_starts_with($path, $prefix.'/'))
            ? $path
            : route('home', ['locale' => $locale]);
        $requestIsSecure = $request->isSecure() || (bool) config('session.secure');

        $response = redirect($target)->withCookie(cookie(
            (string) config('analytics.reader_consent_cookie_name'),
            $data['choice'] === 'accept' ? 'accepted' : 'declined',
            max(1, (int) config('analytics.reader_consent_days')) * 1440,
            '/',
            null,
            $requestIsSecure,
            true,
            false,
            'lax',
        ));

        if ($data['choice'] === 'decline') {
            $response->withCookie(cookie()->forget(UniqueReadCounter::COOKIE_NAME, '/'));
        }

        return $response;
    }
}
