import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../../resources/js/app.js', import.meta.url), 'utf8');

function trigger(id, getOpenDialog) {
    const listeners = {};
    return {
        dataset: { dialogOpen: id },
        focused: false,
        addEventListener(name, callback) { listeners[name] = callback; },
        closest(selector) { return selector === 'dialog[open]' ? getOpenDialog() : null; },
        focus() { this.focused = true; },
        async click() {
            let prevented = false;
            await listeners.click({ preventDefault() { prevented = true; } });
            return prevented;
        },
    };
}

test('switching from report chooser to a report form closes the first dialog', async () => {
    const opened = [];
    const options = {
        open: false,
        dataset: {},
        listeners: {},
        close() { this.open = false; this.listeners.close?.(); },
        showModal() { this.open = true; opened.push('options'); },
        querySelector() { return null; },
        addEventListener(name, callback) { this.listeners[name] = callback; },
    };
    const community = {
        open: false,
        dataset: {},
        listeners: {},
        showModal() { this.open = true; opened.push('community'); },
        querySelector() { return null; },
        addEventListener(name, callback) { this.listeners[name] = callback; },
        close() { this.open = false; this.listeners.close?.(); },
    };
    const getOpenDialog = () => options.open ? options : null;
    const chooserLink = trigger('options', getOpenDialog);
    const communityChoice = trigger('community', getOpenDialog);
    const elements = { options, community };
    const document = {
        documentElement: {},
        querySelector() { return null; },
        querySelectorAll(selector) {
            return selector === '[data-dialog-open]' ? [chooserLink, communityChoice] : [];
        },
        getElementById(id) { return elements[id] ?? null; },
    };

    runInNewContext(source, { document, window: {}, navigator: {} });
    assert.equal(await chooserLink.click(), true);
    assert.equal(options.open, true);
    assert.equal(await communityChoice.click(), true);
    assert.equal(options.open, false);
    assert.equal(community.open, true);
    assert.deepEqual(opened, ['options', 'community']);
    community.close();
    assert.equal(chooserLink.focused, true);
});
