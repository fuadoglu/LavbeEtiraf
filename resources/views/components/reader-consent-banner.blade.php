@php
    $readerConsent = request()->cookie(config('analytics.reader_consent_cookie_name'));
@endphp
@if(config('analytics.unique_read_tracking_enabled') && ! in_array($readerConsent, ['accepted', 'declined'], true))
    <aside class="reader-consent-banner" role="region" aria-labelledby="reader-consent-banner-title">
        <div class="reader-consent-copy">
            <h2 id="reader-consent-banner-title">{{ __('consent.banner_title') }}</h2>
            <p>{{ __('consent.banner_body', ['retention_days' => config('analytics.unique_read_retention_days')]) }}</p>
        </div>
        <form method="post" action="{{ route('reader-consent.update', ['locale' => $lavbeLocale['segment'] ?? app(App\Support\Lavbe\LocaleRegistry::class)->fallback()['segment']]) }}" class="reader-consent-actions">
            @csrf
            <input type="hidden" name="return_to" value="{{ request()->getPathInfo() }}">
            <button class="button button-primary" type="submit" name="choice" value="accept">{{ __('consent.allow') }}</button>
            <button class="button button-secondary" type="submit" name="choice" value="decline">{{ __('consent.decline') }}</button>
        </form>
    </aside>
@endif
