<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $lavbeLocale['dir'] ?? 'ltr' }}" data-theme="day" data-locale="{{ request()->route('locale') ?? app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <x-theme-colors />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="lavbe-analytics-id" content="{{ config('services.analytics.measurement_id') }}">
    @if($analyticsContext ?? null)
        <meta name="lavbe-analytics-context" content="{{ base64_encode(json_encode($analyticsContext, JSON_THROW_ON_ERROR)) }}">
    @endif
    <link rel="manifest" href="{{ route('pwa.manifest', ['locale' => request()->route('locale') ?? app(\App\Support\Lavbe\LocaleRegistry::class)->fallback()['segment']]) }}">
    <link rel="apple-touch-icon" href="/icons/lavbe-apple-touch.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>@yield('title', 'Lavbe · '.__('lavbe.feed_title'))</title>
    @stack('head')
    <script>
        (() => {
            try {
                const saved = localStorage.getItem('lavbe-theme');
                const system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'night' : 'day';
                const theme = saved === 'day' || saved === 'night' ? saved : system;
                document.documentElement.dataset.theme = theme;
                const color = document.querySelector('meta[name="theme-color"]');
                color.content = color.dataset[theme];
            } catch {
                document.documentElement.dataset.theme = 'day';
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main">{{ __('navigation.skip') }}</a>
    <div class="app-shell">
        <x-site-header />
        <main id="main" class="page-content" tabindex="-1">
            @yield('content')
        </main>
        <footer class="site-footer">
            <p>Lavbe · {{ __('lavbe.feed_title') }}</p>
            <button class="footer-link" type="button" data-consent-open>{{ $consentMessages['settings'] }}</button>
        </footer>
    </div>
    @stack('dialogs')
    <dialog class="consent-dialog" data-consent-dialog aria-labelledby="consent-title">
        <form method="dialog" data-consent-form>
            <h2 id="consent-title">{{ $consentMessages['settings'] }}</h2>
            <p>{{ $consentMessages['description'] }}</p>
            <label class="consent-option"><input type="checkbox" data-analytics-consent> {{ $consentMessages['analytics'] }}</label>
            <label class="consent-option"><input type="checkbox" data-ads-consent disabled> {{ $consentMessages['ads'] }}</label>
            <p class="consent-note">{{ $consentMessages['ads_unavailable'] }}</p>
            <div class="consent-actions">
                <button class="button button-primary" type="submit" value="save" data-consent-save>{{ $consentMessages['save'] }}</button>
                <button class="button button-secondary" type="button" data-consent-decline>{{ $consentMessages['decline'] }}</button>
            </div>
        </form>
    </dialog>
</body>
</html>
