import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

const workerSource = await readFile(new URL('../../public/sw.js', import.meta.url), 'utf8');
const appSource = await readFile(new URL('../../resources/js/app.js', import.meta.url), 'utf8');
const offlineHtml = await readFile(new URL('../../public/offline.html', import.meta.url), 'utf8');
const offlineCatalog = JSON.parse(await readFile(new URL('../../public/offline-copy.json', import.meta.url), 'utf8'));

function setupWorker({ fetchImpl = async () => ({ ok: true, type: 'basic', clone() { return this; } }) } = {}) {
    const listeners = new Map();
    const cached = new Map();
    const writes = [];
    const matches = [];
    const cache = {
        async add(key) { cached.set(key, { offline: true }); },
        async match(key, options = {}) {
            const value = typeof key === 'string' ? key : key instanceof URL ? key.toString() : key.url;
            matches.push(value);
            if (cached.has(value)) return cached.get(value);
            if (options.ignoreSearch) return cached.get(new URL(value, 'https://lavbe.example').pathname);
            return undefined;
        },
        async put(key, response) { writes.push([typeof key === 'string' ? key : key.url, response]); },
    };
    const scope = {
        location: { origin: 'https://lavbe.example' },
        clients: { claim: async () => {} },
        skipWaiting() {},
        addEventListener(type, listener) { listeners.set(type, listener); },
    };
    const cachesApi = {
        async open() { return cache; },
        async match(key, options) { return cache.match(key, options); },
        async keys() { return ['lavbe-static-old', 'unrelated-cache']; },
        async delete() { return true; },
    };
    vm.runInNewContext(workerSource, {
        self: scope,
        caches: cachesApi,
        URL,
        Promise,
        fetch: fetchImpl,
    });

    return { listeners, cached, matches, writes };
}

function dispatchFetch(worker, request) {
    let responsePromise;
    worker.listeners.get('fetch')({ request, respondWith(promise) { responsePromise = promise; } });
    return responsePromise;
}

test('navigation preserves the requested locale in the generic offline fallback', async () => {
    const worker = setupWorker({ fetchImpl: async () => { throw new Error('offline'); } });
    const installEvent = { waitUntil(promise) { this.promise = promise; } };
    worker.listeners.get('install')(installEvent);
    await installEvent.promise;

    const response = await dispatchFetch(worker, {
        url: 'https://lavbe.example/az/my', method: 'GET', mode: 'navigate',
    });
    assert.deepEqual(response, { offline: true });
    assert.equal(worker.matches.at(-1), 'https://lavbe.example/offline.html?locale=az');
    assert.equal(worker.writes.length, 0);
});

test('offline shell uses the selected locale before browser language and updates document language', async () => {
    const script = offlineHtml.match(/<script>([\s\S]*?)<\/script>/)[1];
    const elements = {
        '[data-offline-title]': { textContent: 'You’re offline' },
        '[data-offline-body]': { textContent: 'When you reconnect, you can return to the page you wanted to read.' },
        '[data-offline-retry]': { textContent: 'Try again', addEventListener(name, listener) { this[name] = listener; } },
    };
    const document = {
        title: '',
        documentElement: { lang: 'en', dir: 'ltr', style: { setProperty() {} } },
        querySelector(selector) { return elements[selector]; },
    };
    vm.runInNewContext(script, {
        document,
        fetch: async () => ({ ok: true, json: async () => offlineCatalog }),
        location: { search: '?locale=ru', reload() {} },
        navigator: { languages: ['en-US'], language: 'en-US' },
        URLSearchParams,
    });
    await new Promise((resolve) => setTimeout(resolve, 0));
    assert.equal(document.documentElement.lang, 'ru');
    assert.equal(document.documentElement.dir, 'ltr');
    assert.equal(document.title, offlineCatalog.messages.ru.title);
    assert.equal(elements['[data-offline-retry]'].textContent, offlineCatalog.messages.ru.retry);
});

test('offline shell keeps readable fallback copy and a working retry when locale JSON fails', async () => {
    const script = offlineHtml.match(/<script>([\s\S]*?)<\/script>/)[1];
    const elements = {
        '[data-offline-title]': { textContent: 'You’re offline' },
        '[data-offline-body]': { textContent: 'When you reconnect, you can return to the page you wanted to read.' },
        '[data-offline-retry]': { textContent: 'Try again', addEventListener(name, listener) { this[name] = listener; } },
    };
    const document = {
        title: '',
        documentElement: { lang: 'en', dir: 'ltr', style: { setProperty() {} } },
        querySelector(selector) { return elements[selector]; },
    };
    let reloaded = false;
    vm.runInNewContext(script, {
        document,
        fetch: async () => { throw new Error('offline copy unavailable'); },
        location: { search: '?locale=ru', reload() { reloaded = true; } },
        navigator: { languages: ['en-US'], language: 'en-US' },
        URLSearchParams,
    });
    await new Promise((resolve) => setTimeout(resolve, 0));
    assert.equal(elements['[data-offline-title]'].textContent, 'You’re offline');
    assert.equal(elements['[data-offline-body]'].textContent.length > 0, true);
    elements['[data-offline-retry]'].click();
    assert.equal(reloaded, true);
});

test('private pages, API routes, and cross-origin requests are never stored in Cache Storage', async () => {
    const worker = setupWorker();
    for (const url of [
        'https://lavbe.example/az/my',
        'https://lavbe.example/az/moderation',
        'https://lavbe.example/az/feed-fragment?cursor=secret',
        'https://other.example/build/assets/app-12345678.js',
    ]) {
        assert.equal(await dispatchFetch(worker, { url, method: 'GET', mode: 'cors' }), undefined);
    }
    assert.equal(worker.writes.length, 0);
});

test('only successful same-origin versioned build assets are stored', async () => {
    const worker = setupWorker();
    const request = { url: 'https://lavbe.example/build/assets/app-AbCd1234.js', method: 'GET', mode: 'cors' };
    await dispatchFetch(worker, request);
    assert.equal(worker.writes.length, 1);
    assert.equal(worker.writes[0][0], request.url);

    const failed = setupWorker({ fetchImpl: async () => ({ ok: false, type: 'basic', clone() { return this; } }) });
    await dispatchFetch(failed, request);
    assert.equal(failed.writes.length, 0);
});

test('service worker registration is limited to secure contexts', async () => {
    let loadHandler;
    let registration;
    const secureWindow = { isSecureContext: true, addEventListener(type, handler) { if (type === 'load') loadHandler = handler; } };
    const secureNavigator = { serviceWorker: { register: async (...args) => { registration = args; } } };
    const document = { documentElement: {}, querySelector: () => null, querySelectorAll: () => [], addEventListener() {} };
    vm.runInNewContext(appSource, { window: secureWindow, navigator: secureNavigator, document });
    assert.equal(typeof loadHandler, 'function');
    await loadHandler();
    assert.equal(registration[0], '/sw.js');
    assert.equal(registration[1].scope, '/');

    let insecureLoadHandler;
    const insecureWindow = { isSecureContext: false, addEventListener(type, handler) { if (type === 'load') insecureLoadHandler = handler; } };
    vm.runInNewContext(appSource, {
        window: insecureWindow,
        navigator: { serviceWorker: { register: async () => { throw new Error('must not register'); } } },
        document,
    });
    assert.equal(insecureLoadHandler, undefined);
});
