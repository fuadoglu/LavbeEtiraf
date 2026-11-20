import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

const workerSource = await readFile(new URL('../../public/sw.js', import.meta.url), 'utf8');
const appSource = await readFile(new URL('../../resources/js/app.js', import.meta.url), 'utf8');

function setupWorker({ fetchImpl = async () => ({ ok: true, type: 'basic', clone() { return this; } }) } = {}) {
    const listeners = new Map();
    const cached = new Map();
    const writes = [];
    const cache = {
        async add(key) { cached.set(key, { offline: true }); },
        async match(key) { return cached.get(typeof key === 'string' ? key : key.url); },
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
        async match(key) { return cache.match(key); },
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

    return { listeners, cached, writes };
}

function dispatchFetch(worker, request) {
    let responsePromise;
    worker.listeners.get('fetch')({ request, respondWith(promise) { responsePromise = promise; } });
    return responsePromise;
}

test('navigation uses the network and returns only the generic offline page when offline', async () => {
    const worker = setupWorker({ fetchImpl: async () => { throw new Error('offline'); } });
    const installEvent = { waitUntil(promise) { this.promise = promise; } };
    worker.listeners.get('install')(installEvent);
    await installEvent.promise;

    const response = await dispatchFetch(worker, {
        url: 'https://lavbe.example/az/my', method: 'GET', mode: 'navigate',
    });
    assert.deepEqual(response, { offline: true });
    assert.equal(worker.writes.length, 0);
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
