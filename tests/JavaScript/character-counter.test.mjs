import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

const source = await readFile(new URL('../../resources/js/app.js', import.meta.url), 'utf8');

function renderCounter(value, maximum = 6000) {
    const listeners = {};
    const input = {
        value,
        addEventListener(name, callback) { listeners[name] = callback; },
    };
    const output = {
        value: String(maximum),
        dataset: { max: String(maximum) },
    };
    const document = {
        documentElement: {},
        querySelector(selector) {
            return {
                '[data-character-count-input]': input,
                '[data-character-count]': output,
            }[selector] ?? null;
        },
        querySelectorAll: () => [],
        addEventListener() {},
    };
    vm.runInNewContext(source, { document, navigator: {}, window: {} });

    return { input, output, listeners };
}

test('remaining count starts at the shared limit and counts Unicode code points', () => {
    const ui = renderCounter('');
    assert.equal(ui.output.value, '6000');
    ui.input.value = 'A🙂é';
    ui.listeners.input();
    assert.equal(ui.output.value, '5997');
});

test('remaining count never becomes negative', () => {
    const ui = renderCounter('four', 3);
    assert.equal(ui.output.value, '0');
});
