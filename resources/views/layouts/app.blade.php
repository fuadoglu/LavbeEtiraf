<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $lavbeLocale['dir'] ?? 'ltr' }}" data-theme="day">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#faf7f2">
    <title>@yield('title', 'Lavbe · '.__('lavbe.feed_title'))</title>
    @stack('head')
    <script>
        (() => {
            try {
                const saved = localStorage.getItem('lavbe-theme');
                const system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'night' : 'day';
                const theme = saved === 'day' || saved === 'night' ? saved : system;
                document.documentElement.dataset.theme = theme;
                document.querySelector('meta[name="theme-color"]').content = theme === 'night' ? '#181521' : '#faf7f2';
            } catch {
                document.documentElement.dataset.theme = 'day';
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main">{{ __('ui.skip') }}</a>
    <div class="app-shell">
        <x-site-header />
        <main id="main" class="page-content" tabindex="-1">
            @yield('content')
        </main>
        <footer class="site-footer">
            <p>Lavbe · {{ __('lavbe.feed_title') }}</p>
        </footer>
    </div>
    @stack('dialogs')
</body>
</html>
