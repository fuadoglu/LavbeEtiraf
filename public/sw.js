const CACHE_PREFIX = 'lavbe-static-';
const CACHE_NAME = `${CACHE_PREFIX}v2`;
const OFFLINE_URL = '/offline.html';
const OFFLINE_COPY_URL = '/offline-copy.json';
const HASHED_ASSET = /^\/build\/assets\/[A-Za-z0-9._-]+-[A-Za-z0-9_-]{8,}\.(?:css|js|woff2?|png|svg|webp)$/;

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then(async (cache) => {
        await cache.add(OFFLINE_URL);
        await cache.add(OFFLINE_COPY_URL);
    }));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(Promise.all([
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key.startsWith(CACHE_PREFIX) && key !== CACHE_NAME).map((key) => caches.delete(key)),
        )),
        self.clients.claim(),
    ]));
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);
    if (request.method !== 'GET' || url.origin !== self.location.origin) return;

    if (request.mode === 'navigate') {
        const offlineUrl = new URL(OFFLINE_URL, self.location.origin);
        const requestedLocale = url.pathname.split('/').filter(Boolean)[0] || '';
        if (/^[a-z]{2,3}(?:-[a-z0-9]{2,8})?$/i.test(requestedLocale)) {
            offlineUrl.searchParams.set('locale', requestedLocale);
        }
        event.respondWith(fetch(request).catch(async () => (await caches.match(offlineUrl, { ignoreSearch: true }))));
        return;
    }

    if (url.pathname === OFFLINE_COPY_URL) {
        event.respondWith(caches.match(OFFLINE_COPY_URL).then((cached) => cached || fetch(request)));
        return;
    }

    if (!HASHED_ASSET.test(url.pathname)) return;
    event.respondWith(caches.open(CACHE_NAME).then(async (cache) => {
        const cached = await cache.match(request);
        if (cached) return cached;
        const response = await fetch(request);
        if (response.ok && response.type === 'basic') await cache.put(request, response.clone());
        return response;
    }));
});
