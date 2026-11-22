import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

const source = await readFile(new URL('../../resources/js/app.js', import.meta.url), 'utf8');

function setup(initial = {}) {
    const stored = new Map(Object.entries(initial));
    const storage = {
        getItem: (key) => stored.get(key) ?? null,
        setItem: (key, value) => stored.set(key, value),
        removeItem: (key) => stored.delete(key),
    };
    const nodes = {};
    for (const key of ['optIn', 'clear', 'restore', 'conflict', 'body', 'title', 'form']) {
        nodes[key] = {
            value: '', checked: false, hidden: key === 'restore' || key === 'conflict',
            listeners: {}, addEventListener(name, callback) { this.listeners[name] = callback; },
            dispatchEvent(event) { this.listeners[event.type]?.(event); },
        };
    }
    nodes.form.querySelector = (selector) => ({
        '[data-draft-controls]': { dataset: { draftRetentionMs: String(7 * 86400000) }, querySelector: (name) => ({
            '[data-draft-opt-in]': nodes.optIn, '[data-draft-clear]': nodes.clear,
            '[data-draft-restore]': nodes.restore, '[data-draft-conflict]': nodes.conflict,
        })[name] },
    })[selector];
    nodes.form.elements = { namedItem: (name) => ({ title: nodes.title, idempotency_key: { value: 'test-key' } })[name] };
    nodes.body.closest = () => nodes.form;
    const document = {
        documentElement: {},
        querySelector: (selector) => ({ '[data-character-count-input]': nodes.body })[selector] ?? null,
        querySelectorAll: () => [], addEventListener() {},
    };
    vm.runInNewContext(source, { document, navigator: {}, window: {}, localStorage: storage,
        sessionStorage: { getItem: () => null, setItem() {}, removeItem() {} }, Date,
        Event: class { constructor(type) { this.type = type; } } });
    return { nodes, stored };
}

test('draft remains private until the user opts in, then expires after seven days', () => {
    const { nodes, stored } = setup();
    nodes.body.value = 'A private sentence';
    nodes.body.listeners.input({ isComposing: false });
    assert.equal(stored.size, 0);
    nodes.optIn.checked = true;
    nodes.optIn.listeners.change();
    const draft = JSON.parse(stored.get('lavbe-confession-draft'));
    assert.equal(draft.body, 'A private sentence');
    assert.ok(draft.expiresAt > Date.now() + 6 * 86400000);
    nodes.clear.listeners.click();
    assert.equal(stored.size, 0);
    assert.equal(nodes.body.value, '');
});

test('old automatically saved draft is offered once without displaying its contents', () => {
    const { nodes, stored } = setup({ 'lavbe-confession-draft': JSON.stringify({ title: 'Old title', body: 'Old body' }) });
    assert.equal(nodes.body.value, '');
    assert.equal(nodes.title.value, '');
    assert.equal(nodes.restore.hidden, false);
    assert.equal(stored.size, 0);
    nodes.restore.listeners.click();
    assert.equal(nodes.body.value, 'Old body');
    assert.equal(nodes.optIn.checked, true);
    assert.equal(JSON.parse(stored.get('lavbe-confession-draft')).version, 2);
});

test('expired opted-in draft is erased', () => {
    const { nodes, stored } = setup({ 'lavbe-confession-draft': JSON.stringify({ version: 2, title: 'Expired', body: 'Expired', expiresAt: Date.now() - 1 }) });
    assert.equal(nodes.body.value, '');
    assert.equal(stored.size, 0);
});
