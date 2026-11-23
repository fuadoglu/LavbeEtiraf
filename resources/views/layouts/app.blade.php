<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $lavbeLocale['dir'] ?? 'ltr' }}" data-theme="day" style="--theme-day-page:{{ config('theme.day.page') }};--theme-day-surface:{{ config('theme.day.surface') }};--theme-day-surface-raised:{{ config('theme.day.surface_raised') }};--theme-day-text:{{ config('theme.day.text') }};--theme-day-muted:{{ config('theme.day.muted') }};--theme-day-action:{{ config('theme.day.action') }};--theme-day-action-contrast:{{ config('theme.day.action_contrast') }};--theme-day-edge:{{ config('theme.day.edge') }};--theme-day-danger:{{ config('theme.day.danger') }};--theme-day-success:{{ config('theme.day.success') }};--theme-day-warning:{{ config('theme.day.warning') }};--theme-day-shadow:{{ config('theme.day.shadow') }};--theme-day-brand-logo:{{ config('theme.day.brand_logo') }};--theme-day-brand-accent:{{ config('theme.day.brand_accent') }};--theme-night-page:{{ config('theme.night.page') }};--theme-night-surface:{{ config('theme.night.surface') }};--theme-night-surface-raised:{{ config('theme.night.surface_raised') }};--theme-night-text:{{ config('theme.night.text') }};--theme-night-muted:{{ config('theme.night.muted') }};--theme-night-action:{{ config('theme.night.action') }};--theme-night-action-contrast:{{ config('theme.night.action_contrast') }};--theme-night-edge:{{ config('theme.night.edge') }};--theme-night-danger:{{ config('theme.night.danger') }};--theme-night-success:{{ config('theme.night.success') }};--theme-night-warning:{{ config('theme.night.warning') }};--theme-night-shadow:{{ config('theme.night.shadow') }};--theme-night-brand-logo:{{ config('theme.night.brand_logo') }};--theme-night-brand-accent:{{ config('theme.night.brand_accent') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="{{ config('theme.day.page') }}" data-day="{{ config('theme.day.page') }}" data-night="{{ config('theme.night.page') }}">
    <link rel="manifest" href="{{ route('pwa.manifest', ['locale' => $lavbeLocale['segment'] ?? request()->route('locale', 'en')]) }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/lavbe-feather-192.png') }}">
    @hasSection('category-icon')
        @php($categoryIcon = trim($__env->yieldContent('category-icon')))
        <link rel="icon" type="image/png" href="{{ asset('icons/category-favicons/'.$categoryIcon.'.png') }}" sizes="32x32">
        <link rel="icon" type="image/svg+xml" href="{{ asset('icons/lucide/'.$categoryIcon.'.svg') }}" sizes="any">
    @else
        <link rel="icon" type="image/svg+xml" href="{{ asset('icons/lavbe-feather.svg') }}" sizes="any">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    @endif
    <title>@yield('title', config('app.name').' · '.__('lavbe.feed_title'))</title>
    @stack('head')
    <script>
        (() => {
            try {
                const saved = localStorage.getItem('lavbe-theme');
                const system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'night' : 'day';
                const theme = saved === 'day' || saved === 'night' ? saved : system;
                document.documentElement.dataset.theme = theme;
                document.querySelector('meta[name="theme-color"]').content = theme === 'night' ? '{{ config('theme.night.page') }}' : '{{ config('theme.day.page') }}';
            } catch {
                document.documentElement.dataset.theme = 'day';
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/css/plum-ink.css', 'resources/js/app.js'])
</head>
<body @if(request()->routeIs('moderation.*')) class="moderation-body" @endif>
    <a class="skip-link" href="#main">{{ __('navigation.skip') }}</a>
    <div class="app-shell">
        <x-site-header />
        @unless(request()->routeIs('moderation.*'))
            @include('components.reader-consent-banner')
        @endunless
        <main id="main" class="page-content @if(request()->routeIs('moderation.*')) page-content--moderation @endif" tabindex="-1">
            @yield('content')
        </main>
        <footer class="site-footer">
            <div class="site-footer-inner">
                <a class="site-footer-brand-link" href="{{ route('home', ['locale' => $lavbeLocale['segment'] ?? request()->route('locale', 'en')]) }}" aria-label="{{ config('app.name') }}">
                    <span class="brand-wordmark" aria-hidden="true" style="--brand-logo-mask: url('{{ asset('icons/lavbe-logo.svg') }}'); --brand-heart-mask: url('{{ asset('icons/lavbe-heart.svg') }}')"></span>
                </a>
                <nav class="site-footer-navigation" aria-label="{{ __('navigation.footer') }}">
                    <ul>
                        @foreach(['terms', 'community', 'privacy', 'cookies', 'illegal_content', 'data_request'] as $legalPage)
                            <li><a href="{{ route('legal.page', ['locale' => $lavbeLocale['segment'] ?? request()->route('locale', 'en'), 'page' => $legalPage]) }}" @if(request()->route('page') === $legalPage) aria-current="page" @endif>{{ __('legal.'.$legalPage.'.title') }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            </div>
        </footer>
    </div>
    @stack('dialogs')
</body>
</html>
