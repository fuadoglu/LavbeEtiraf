<!doctype html>
<html lang="{{ str_replace('_', '-', $locale['code']) }}" dir="{{ $locale['dir'] ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#faf7f2">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>{{ $labels['offline_title'] }} · Lavbe</title>
    <link rel="icon" href="/icons/lavbe-192.png" type="image/png">
    @vite(['resources/css/app.css'])
</head>
<body>
    <main class="page-content" tabindex="-1">
        <section class="empty-state" aria-labelledby="offline-title">
            <img src="/icons/lavbe-192.png" width="72" height="72" alt="" aria-hidden="true">
            <h1 id="offline-title">{{ $labels['offline_title'] }}</h1>
            <p>{{ $labels['offline_body'] }}</p>
            <a class="button button-primary" href="/{{ $locale['segment'] }}/">{{ $labels['home'] }}</a>
        </section>
    </main>
</body>
</html>
