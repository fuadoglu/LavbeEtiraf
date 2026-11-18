import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import vm from "node:vm";

const html = readFileSync(new URL("../docs/ui/wireframes.html", import.meta.url), "utf8");
const nodes = new Map();
for (const [, id, innerHTML] of html.matchAll(/<template id="([^"]+)">([\s\S]*?)<\/template>/g)) {
  nodes.set(id, { innerHTML });
}
for (const id of ["screen", "state", "theme", "mobile", "desktop"]) {
  nodes.set(id, {
    innerHTML: "",
    value: { screen: "home", theme: "day" }[id] ?? "",
    listeners: new Map(),
    addEventListener(event, fn) {
      this.listeners.set(event, fn);
    },
    replaceChildren(...options) {
      this.options = options;
      this.value = options[0]?.value ?? "";
    },
  });
}
const context = vm.createContext({
  document: { documentElement: { dataset: {} }, getElementById: id => nodes.get(id) },
  Option: class {
    constructor(label, value) {
      this.label = label;
      this.value = value;
    }
  },
});
const script = html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(script, "atlas script missing");
vm.runInContext(script, context);
const screens = [...html.match(/<select id="screen">([\s\S]*?)<\/select>/)[1].matchAll(/value="([^"]+)"/g)]
  .map(match => match[1]);
const declared = vm.runInContext("Object.keys(variants)", context);
assert.deepEqual([...declared].sort(), [...screens].sort(), "every menu choice has declared states");
let checked = 0;
for (const theme of ["day", "night"]) {
  nodes.get("theme").value = theme;
  nodes.get("theme").listeners.get("change")();
  assert.equal(context.document.documentElement.dataset.theme, theme);
  for (const screen of screens) {
    nodes.get("screen").value = screen;
    nodes.get("screen").listeners.get("change")();
    assert.ok(nodes.get("state").options.length, screen);
    for (const { value: state } of nodes.get("state").options) {
      nodes.get("state").value = state;
      nodes.get("state").listeners.get("change")();
      for (const pane of ["mobile", "desktop"]) {
        const output = nodes.get(pane).innerHTML;
        assert.ok(output.includes('class="content stack"'), [screen, state, pane].join("/"));
        assert.ok(!output.includes("undefined"), [screen, state, pane].join("/"));
        assert.equal(output.includes('class="slot"'), screen === "read" && state === "ad");
        if (screen === "home" && state === "loading") {
          assert.ok(output.includes('class="card skeleton"') && output.includes("Bir söhbəti"));
        }
        if (screen === "write" && state === "retry") {
          assert.ok(output.includes("Yarımçıq qalan bir söhbət"));
        }
        checked += 1;
      }
    }
  }
}
console.log(`Verified ${screens.length} atlas screens and ${checked} screen/state/theme/pane combinations (DOM emulation).`);
