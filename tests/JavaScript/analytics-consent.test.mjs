import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../../resources/js/app.js', import.meta.url), 'utf8');

function setup({ consent = null, context = { locale: 'en', type: 'confession', path: '/en/confession', title: 'Confession' } } = {}) {
    const scripts = [];
    const listeners = {};
    const checkbox = { checked: false };
    const form = { addEventListener(name, listener) { listeners[name] = listener; } };
    const decline = { addEventListener(name, listener) { listeners.decline = listener; } };
    const dialog = {
        querySelector: selector => ({ '[data-consent-form]': form, '[data-analytics-consent]': checkbox, '[data-consent-decline]': decline })[selector],
        showModal() {},
        close() {},
    };
    const elements = {
        '[data-consent-dialog]': dialog,
        'meta[name="lavbe-analytics-id"]': { content: 'G-SYNTHETIC123' },
        'meta[name="lavbe-analytics-context"]': { content: context ? Buffer.from(JSON.stringify(context)).toString('base64') : '' },
    };
    const storage = new Map(consent ? [['lavbe:consent:v1', JSON.stringify(consent)]] : []);
    const window = {
        location: { origin: 'https://lavbe.example', href: 'https://lavbe.example/en/confession/private-id?q=private-text' },
        addEventListener() {},
    };
    runInNewContext(source, {
        window, navigator: {}, URL, Date,
        atob: value => Buffer.from(value, 'base64').toString(),
        localStorage: { getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value) },
        document: {
            documentElement: {},
            querySelector: selector => elements[selector] ?? null,
            querySelectorAll: () => [],
            createElement: () => ({}),
            head: { append: script => scripts.push(script) },
        },
    });
    return {
        scripts, window,
        save(checked) {
            checkbox.checked = checked;
            listeners.submit({ submitter: { value: 'save' }, preventDefault() {} });
        },
        decline: () => listeners.decline(),
    };
}

test('no GA script loads before consent, and granted events use only sanitized context', () => {
    const ui = setup();
    assert.equal(ui.scripts.length, 0);
    ui.save(true);
    assert.equal(ui.scripts.length, 1);
    const config = ui.window.dataLayer.find(args => args[0] === 'config')[2];
    assert.equal(config.page_location, 'https://lavbe.example/en/confession');
    assert.equal(config.page_title, 'Confession');
    assert.equal(config.page_referrer, '');
    const payload = JSON.stringify(ui.window.dataLayer);
    assert.equal(payload.includes('private-id'), false);
    assert.equal(payload.includes('private-text'), false);
    assert.equal(ui.window['ga-disable-G-SYNTHETIC123'], false);
    ui.decline();
    assert.equal(ui.window['ga-disable-G-SYNTHETIC123'], true);
    const last = ui.window.dataLayer.at(-1);
    assert.equal(last[0], 'consent');
    assert.equal(last[2].analytics_storage, 'denied');
});

test('private surfaces and Azerbaijani context never load GA despite saved consent', () => {
    const consent = { version: 1, analytics: true, expires: Date.now() + 60000 };
    for (const context of [null, { locale: 'az', type: 'feed', path: '/az/', title: 'Feed' }, { locale: 'en', type: 'account', path: '/en/account', title: 'Account' }]) {
        const ui = setup({ context, consent });
        ui.save(true);
        assert.equal(ui.scripts.length, 0);
    }
});

test('expired consent cannot start analytics automatically', () => {
    const ui = setup({ consent: { version: 1, analytics: true, expires: Date.now() - 1 } });
    assert.equal(ui.scripts.length, 0);
    ui.decline();
    assert.equal(ui.scripts.length, 0);
});
