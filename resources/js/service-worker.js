const STATIC_CACHE_PREFIX = 'lavbe-static-';
const OFFLINE_CACHE_PREFIX = 'lavbe-offline-';
const DEFAULT_LOCALE = __LAVBE_DEFAULT_LOCALE__;
const SUPPORTED_LOCALES = new Set(__LAVBE_LOCALES__);

function offlinePath(locale) {
    const segment = SUPPORTED_LOCALES.has(locale) ? locale : DEFAULT_LOCALE;
    return `/${segment}/offline`;
}

async function staticCacheName() {
    const response = await fetch('/build/manifest.json', { cache: 'no-store', credentials: 'same-origin' });
    if (!response.ok) throw new Error('Asset manifest is unavailable');

    const manifest = await response.json();
    const paths = Object.values(manifest)
        .flatMap((entry) => [entry.file, ...(entry.css || []), ...(entry.assets || [])])
        .filter((path) => typeof path === 'string' && /^build\/assets\/[A-Za-z0-9._-]+\.(?:css|js)$/.test(path))
        .map((path) => `/${path}`)
        .sort();
    if (paths.length === 0) throw new Error('No versioned application assets found');

    const bytes = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(paths.join('|')));
    const fingerprint = [...new Uint8Array(bytes)].map((byte) => byte.toString(16).padStart(2, '0')).join('');
    return { name: `${STATIC_CACHE_PREFIX}${fingerprint}`, paths };
}

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const { name, paths } = await staticCacheName();
        const cache = await caches.open(name);
        await cache.addAll([...paths, '/icons/lavbe-192.png', '/icons/lavbe-512.png', '/icons/lavbe-maskable-512.png']);
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const { name } = await staticCacheName();
        const names = await caches.keys();
        await Promise.all(names
            .filter((cacheName) => (cacheName.startsWith(STATIC_CACHE_PREFIX) && cacheName !== name)
                || cacheName.startsWith(OFFLINE_CACHE_PREFIX))
            .map((cacheName) => caches.delete(cacheName)));
        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    if (event.data?.type !== 'CACHE_OFFLINE_PAGE' || !SUPPORTED_LOCALES.has(event.data.locale)) return;
    event.waitUntil((async () => {
        const path = offlinePath(event.data.locale);
        const response = await fetch(path, { cache: 'no-store', credentials: 'same-origin' });
        if (!response.ok || response.headers.get('cache-control')?.includes('private')) return;
        const cache = await caches.open(`${OFFLINE_CACHE_PREFIX}${event.data.locale}`);
        await cache.put(path, response);
    })());
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);
    if (request.method !== 'GET' || url.origin !== self.location.origin || url.search !== '') return;

    if (/^\/build\/assets\/[A-Za-z0-9._-]+\.(?:css|js)$/.test(url.pathname)
        || /^\/icons\/lavbe-(?:192|512|maskable-512)\.png$/.test(url.pathname)) {
        event.respondWith((async () => {
            const cached = await caches.match(request);
            if (cached) return cached;
            return fetch(request);
        })());
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(async () => {
            const locale = url.pathname.split('/').filter(Boolean)[0] || DEFAULT_LOCALE;
            const path = offlinePath(locale);
            return (await caches.open(`${OFFLINE_CACHE_PREFIX}${SUPPORTED_LOCALES.has(locale) ? locale : DEFAULT_LOCALE}`)).match(path)
                || Response.error();
        }));
    }
});

self.addEventListener('push', (event) => {
    let payload;
    try {
        payload = event.data?.json();
    } catch {
        payload = null;
    }
    if (!payload || typeof payload.body !== 'string') return;

    const title = typeof payload.title === 'string' ? payload.title.slice(0, 80) : 'Lavbe';
    const body = payload.body.slice(0, 160);
    const locale = typeof payload.locale === 'string' && SUPPORTED_LOCALES.has(payload.locale) ? payload.locale : DEFAULT_LOCALE;
    event.waitUntil(self.registration.showNotification(title, {
        body,
        lang: locale,
        icon: '/icons/lavbe-192.png',
        badge: '/icons/lavbe-192.png',
        tag: 'lavbe-account-activity',
        data: { url: `/${locale}/my` },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = new URL(typeof event.notification.data?.url === 'string' ? event.notification.data.url : `/${DEFAULT_LOCALE}/my`, self.location.origin);
    const parts = target.pathname.split('/').filter(Boolean);
    if (target.origin !== self.location.origin || parts.length !== 2 || !SUPPORTED_LOCALES.has(parts[0]) || parts[1] !== 'my') return;
    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        const existing = windows.find((client) => new URL(client.url).origin === self.location.origin);
        if (existing) {
            await existing.navigate(target.href);
            return existing.focus();
        }
        return self.clients.openWindow(target.href);
    })());
});
