<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $lavbeLocale['dir'] ?? 'ltr' }}" data-theme="day" data-locale="{{ request()->route('locale') ?? app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <x-theme-colors />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="{{ route('pwa.manifest', ['locale' => request()->route('locale') ?? app(\App\Support\Lavbe\LocaleRegistry::class)->fallback()['segment']]) }}">
    <link rel="apple-touch-icon" href="/icons/lavbe-apple-touch.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>@yield('title', 'Lavbe · Etiraflar Vadisi')</title>
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
    <a class="skip-link" href="#main">Əsas məzmuna keç</a>
    <div class="app-shell">
        <x-site-header />
        <main id="main" class="page-content" tabindex="-1">
            @yield('content')
        </main>
        <footer class="site-footer">
            <p>Lavbe · Etiraflar Vadisi</p>
        </footer>
    </div>
    @stack('dialogs')
</body>
</html>
