import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../../resources/js/app.js', import.meta.url), 'utf8');

function element(dataset = {}) {
    const listeners = {};
    return {
        dataset,
        hidden: false,
        disabled: false,
        textContent: '',
        attributes: {},
        addEventListener(name, handler) { listeners[name] = handler; },
        setAttribute(name, value) { this.attributes[name] = value; },
        async click() { await listeners.click(); },
    };
}

function setup(fetchResponse) {
    const more = element({ endpoint: '/az/feed-fragment?order=weekly&category=family' });
    const feed = element({ nextCursor: 'synthetic-cursor' });
    const status = element({ retryLabel: 'Retry', refreshLabel: 'Refresh' });
    const elements = {
        '[data-feed-more]': more,
        '[data-feed]': feed,
        '[data-feed-status]': status,
    };
    const requests = [];
    let reloads = 0;
    const document = {
        documentElement: {},
        querySelector: selector => elements[selector] ?? null,
        querySelectorAll: () => [],
    };
    runInNewContext(source, {
        document,
        URL,
        window: { location: { href: 'http://localhost/az/?category=family&sort=weekly', reload: () => reloads++ } },
        fetch: async (url, options) => {
            requests.push({ url: String(url), options });
            return fetchResponse();
        },
    });
    return { more, feed, status, requests, reloads: () => reloads };
}

test('an expired cursor refreshes the current page instead of retrying the expired request', async () => {
    const ui = setup(() => ({ status: 410 }));
    await ui.more.click();
    assert.equal(ui.status.textContent, 'Refresh');
    assert.equal(ui.status.hidden, false);
    assert.equal(ui.more.hidden, true);
    assert.equal(ui.feed.attributes['aria-busy'], 'false');
    await ui.status.click();
    assert.equal(ui.reloads(), 1);
    assert.equal(ui.requests.length, 1);
});

test('a network failure retries the same cursor and keeps the selected filters', async () => {
    const ui = setup(() => { throw new Error('Synthetic network failure'); });
    await ui.more.click();
    assert.equal(ui.status.textContent, 'Retry');
    assert.equal(ui.more.disabled, false);
    await ui.status.click();
    // The click handler starts an async fetch. Allow its rejection to settle.
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(ui.requests.length, 2);
    assert.equal(ui.requests[0].url, ui.requests[1].url);
    const url = new URL(ui.requests[0].url);
    assert.equal(url.searchParams.get('cursor'), 'synthetic-cursor');
    assert.equal(url.searchParams.get('category'), 'family');
    assert.equal(url.searchParams.get('order'), 'weekly');
    assert.equal(ui.requests[0].options.credentials, 'same-origin');
    assert.equal(ui.reloads(), 0);
    assert.equal(ui.status.hidden, false);
});
