<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $lavbeLocale['dir'] ?? 'ltr' }}" data-theme="day">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#f8f6f3">
    @hasSection('favicon')
        <link rel="icon" type="image/svg+xml" href="@yield('favicon')" sizes="any">
    @else
        <link rel="icon" type="image/svg+xml" href="{{ asset('icons/lavbe-feather.svg') }}" sizes="any">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    @endif
    <title>@yield('title', 'Lavbe · '.__('lavbe.feed_title'))</title>
    @stack('head')
    <script>
        (() => {
            try {
                const saved = localStorage.getItem('lavbe-theme');
                const system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'night' : 'day';
                const theme = saved === 'day' || saved === 'night' ? saved : system;
                document.documentElement.dataset.theme = theme;
                document.querySelector('meta[name="theme-color"]').content = theme === 'night' ? '#1c191d' : '#f8f6f3';
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
            <div class="site-footer-inner">
                <a class="site-footer-brand-link" href="{{ route('home', ['locale' => $lavbeLocale['segment'] ?? request()->route('locale', 'en')]) }}" aria-label="Lavbe">
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
