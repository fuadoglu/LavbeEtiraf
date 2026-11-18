#!/usr/bin/env python3
"""Run Lavbe's static atlas in Chromium. Requires Playwright in development."""

import argparse
from pathlib import Path

from playwright.sync_api import sync_playwright

ATLAS = Path(__file__).resolve().parents[1] / "docs/ui/wireframes.html"
CHECK_STATES = """() => {
  const choose = (id, value) => {
    const el = document.getElementById(id);
    el.value = value;
    el.dispatchEvent(new Event("change"));
  };
  let tested = 0;
  for (const theme of ["day", "night"]) {
    choose("theme", theme);
    for (const {value: screen} of document.querySelectorAll("#screen option")) {
      choose("screen", screen);
      for (const {value: state} of document.querySelectorAll("#state option")) {
        choose("state", state);
        const mobile = document.querySelector("#mobile .content");
        const desktop = document.querySelector("#desktop .content");
        if (!mobile.innerText.trim() || !desktop.innerText.trim() ||
            document.body.scrollWidth > innerWidth) {
          throw new Error([theme, screen, state, innerWidth, document.body.scrollWidth].join(" / "));
        }
        for (const section of [mobile, desktop]) {
          const ad = section.querySelector(".slot");
          if (Boolean(ad) !== (screen === "read" && state === "ad")) {
            throw new Error("Unexpected ad slot for " + screen + "/" + state);
          }
          if (screen === "home" && state === "loading") {
            const story = section.querySelector("article.story-frame");
            const skeleton = section.querySelector(".skeleton.story-frame");
            if (Math.abs(story.offsetHeight - skeleton.offsetHeight) > 1) {
              throw new Error("Skeleton geometry differs from card at " + innerWidth);
            }
          }
        }
        tested += 1;
      }
    }
  }
  return tested;
}"""


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--chromium", help="Path to a local Chromium or Chromium headless shell.")
    options = parser.parse_args()
    with sync_playwright() as playwright:
        browser = playwright.chromium.launch(executable_path=options.chromium)
        page = browser.new_page()
        errors = []
        page.on("pageerror", lambda error: errors.append(str(error)))
        page.goto(ATLAS.as_uri())
        checked = 0
        for width in (320, 390, 1366):
            page.set_viewport_size({"width": width, "height": 900})
            checked += page.evaluate(CHECK_STATES)
        assert not errors, errors
        print(f"Chromium: {checked} state/theme/viewport combinations passed at 320, 390 and 1366 px.")
        browser.close()


if __name__ == "__main__":
    main()
