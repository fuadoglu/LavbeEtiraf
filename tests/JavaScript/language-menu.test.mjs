import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

const source = await readFile(new URL('../../resources/js/app.js', import.meta.url), 'utf8');

function renderMenu(searchValues) {
    const listeners = {};
    const options = searchValues.map((search) => ({
        dataset: { search }, hidden: false, focused: 0, clicked: 0,
        addEventListener() {},
        focus() { this.focused += 1; },
        click() { this.clicked += 1; },
    }));
    const empty = { hidden: true };
    const summary = { focused: 0, focus() { this.focused += 1; } };
    const menuListeners = {};
    const menu = {
        open: false,
        addEventListener(name, callback) { (menuListeners[name] ??= []).push(callback); },
        querySelector(selector) { return selector === '[data-language-empty]' ? empty : summary; },
    };
    const search = {
        value: '',
        closest() { return menu; },
        addEventListener(name, callback) { listeners[name] = callback; },
        focus() { this.focused = true; },
    };
    const document = {
        documentElement: {},
        querySelector(selector) { return selector === '[data-language-search]' ? search : null; },
        querySelectorAll(selector) { return selector === '[data-language-option]' ? options : []; },
        addEventListener() {},
    };
    vm.runInNewContext(source, { document, navigator: {}, window: {} });

    return { empty, listeners, menu, menuListeners, options, search, summary };
}

function keyEvent(key) {
    return { key, isComposing: false, prevented: false, preventDefault() { this.prevented = true; } };
}

test('language search announces no matches and clears search when menu closes', () => {
    const ui = renderMenu(['English', 'Azərbaycanca']);
    ui.search.value = 'not-a-language';
    ui.listeners.input();
    assert.equal(ui.empty.hidden, false);
    assert.equal(ui.options.every((option) => option.hidden), true);

    ui.menu.open = false;
    ui.menuListeners.toggle.forEach((listener) => listener());
    assert.equal(ui.search.value, '');
    assert.equal(ui.empty.hidden, true);
    assert.equal(ui.options.every((option) => !option.hidden), true);
});

test('Enter selects a single match and focuses the first of several matches', () => {
    const ui = renderMenu(['English', 'English (US)', 'Azərbaycanca']);
    ui.search.value = 'english';
    ui.listeners.input();
    const multiple = keyEvent('Enter');
    ui.listeners.keydown(multiple);
    assert.equal(multiple.prevented, true);
    assert.equal(ui.options[0].focused, 1);
    assert.equal(ui.options[0].clicked, 0);

    ui.search.value = 'English (US)';
    ui.listeners.input();
    ui.listeners.keydown(keyEvent('Enter'));
    assert.equal(ui.options[1].clicked, 1);
});

test('Escape closes the language menu and returns focus to its trigger', () => {
    const ui = renderMenu(['English']);
    ui.menu.open = true;
    const event = keyEvent('Escape');
    ui.menuListeners.keydown[0](event);
    assert.equal(event.prevented, false);
    assert.equal(ui.menu.open, false);
    assert.equal(ui.summary.focused, 1);
});
