<?php

namespace App\Http\Controllers;

use App\Support\Analytics\ExternalTags;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExternalConsentController extends Controller
{
    public function update(Request $request, string $locale): RedirectResponse
    {
        $data = $request->validate([
            'purpose' => ['required', 'in:analytics,ads'],
            'choice' => ['required', 'in:accept,decline'],
        ]);
        $name = $data['purpose'] === 'analytics' ? ExternalTags::ANALYTICS_COOKIE : ExternalTags::ADS_COOKIE;

        return redirect()->route('legal.page', ['locale' => $locale, 'page' => 'cookies'])
            ->withCookie(cookie($name, $data['choice'] === 'accept' ? 'accepted' : 'declined',
                (int) config('analytics.reader_consent_days') * 1440, '/', null,
                $request->isSecure() || (bool) config('session.secure'), true, false, 'lax'));
    }
}
