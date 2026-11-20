import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../../resources/js/app.js', import.meta.url), 'utf8');

function element(dataset = {}) {
    const listeners = {};
    return {
        dataset,
        hidden: true,
        textContent: '',
        value: '',
        selected: false,
        focused: false,
        addEventListener(name, handler) { listeners[name] = handler; },
        async click() { await listeners.click(); },
        focus() { this.focused = true; },
        select() { this.selected = true; },
    };
}

function setup(navigator) {
    const button = element({
        shareTitle: 'A story worth sharing',
        shareUrl: 'https://lavbe.example/az/confession/synthetic-public-id',
        copiedLabel: 'Copied',
        copyHint: 'Copy the link below',
    });
    const status = element();
    const fallback = element();
    const urlField = element();
    const control = {
        querySelector(selector) {
            return {
                '[data-share-status]': status,
                '[data-share-fallback]': fallback,
                '[data-share-url-field]': urlField,
            }[selector] ?? null;
        },
    };
    button.closest = () => control;
    const document = {
        documentElement: {},
        querySelector: () => null,
        querySelectorAll: selector => selector === '[data-share-button]' ? [button] : [],
    };
    runInNewContext(source, { document, navigator, window: {} });
    return { button, status, fallback, urlField };
}

test('uses the native share sheet with the canonical URL and approved title', async () => {
    const shared = [];
    const ui = setup({ share: async payload => shared.push(payload) });
    await ui.button.click();
    assert.equal(shared.length, 1);
    assert.equal(shared[0].title, 'A story worth sharing');
    assert.equal(shared[0].url, 'https://lavbe.example/az/confession/synthetic-public-id');
    assert.equal(ui.fallback.hidden, true);
    assert.equal(ui.status.textContent, '');
});

test('copies the canonical URL when native sharing is unavailable', async () => {
    const copied = [];
    const ui = setup({ clipboard: { writeText: async value => copied.push(value) } });
    await ui.button.click();
    assert.deepEqual(copied, ['https://lavbe.example/az/confession/synthetic-public-id']);
    assert.equal(ui.status.textContent, 'Copied');
    assert.equal(ui.fallback.hidden, true);
});

test('shows and selects the URL if sharing and clipboard are unavailable', async () => {
    const ui = setup({});
    await ui.button.click();
    assert.equal(ui.fallback.hidden, false);
    assert.equal(ui.status.textContent, 'Copy the link below');
    assert.equal(ui.urlField.value, 'https://lavbe.example/az/confession/synthetic-public-id');
    assert.equal(ui.urlField.focused, true);
    assert.equal(ui.urlField.selected, true);
});

test('quietly ignores a user-cancelled native share sheet', async () => {
    const ui = setup({ share: async () => { throw Object.assign(new Error('Cancelled'), { name: 'AbortError' }); } });
    await ui.button.click();
    assert.equal(ui.fallback.hidden, true);
    assert.equal(ui.status.textContent, '');
});
