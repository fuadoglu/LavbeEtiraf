#!/usr/bin/env python3
"""Render and inspect all locale package drafts in a local Chromium browser."""

import argparse
import html
import json
import re
import subprocess
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
WIDTHS = (320, 390, 1366)


def display(value):
    if isinstance(value, dict):
        return " · ".join(f"{key}: {item}" for key, item in value.items())
    return str(value)


def render_preview(packages_directory, catalog, output):
    sections = []
    locales = {item["code"]: item for item in catalog["locales"]}
    for path in sorted(packages_directory.glob("*.json")):
        package = json.loads(path.read_text(encoding="utf-8"))
        locale = locales[package["locale"]]
        section = [
            f'<section class="locale" id="locale-{html.escape(locale["segment"])}" lang="{html.escape(locale["code"])}" dir="{html.escape(locale["dir"])}" data-locale="{html.escape(locale["code"])}">',
            f'<header><p class="eyebrow">{html.escape(package["version"])} · {html.escape(package["legal_status"])}</p><h1>{html.escape(locale["native"])}</h1></header>',
        ]
        for group, messages in package["messages"].items():
            section.append(f'<article class="group"><h2>{html.escape(group)}</h2><dl>')
            for key, value in messages.items():
                section.append(
                    f'<div class="message-row"><dt>{html.escape(key)}</dt><dd>{html.escape(display(value))}</dd></div>'
                )
            section.append("</dl></article>")
        section.append('<article class="group"><h2>categories</h2><dl>')
        for key, category in package["categories"].items():
            section.append(
                '<div class="message-row"><dt>'
                f'{html.escape(key)} · {html.escape(category["slug"])}'
                '</dt><dd><strong>'
                f'{html.escape(category["label"])}'
                '</strong><br>'
                f'{html.escape(category["description"])}'
                '</dd></div>'
            )
        section.append("</dl></article>")
        section.append('<article class="group"><h2>legal · draft</h2><dl>')
        for key, page_data in package["legal_pages"].items():
            section.append(
                f'<div class="message-row"><dt>{html.escape(key)}</dt><dd><strong>'
                f'{html.escape(page_data["title"])}</strong><br>{html.escape(page_data["body"])}'
                '</dd></div>'
            )
        section.append("</dl></article></section>")
        sections.append("".join(section))
    document = """<!doctype html>
<html lang="en" data-locale-layout="pending"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>Lavbe locale review</title>
<style>
*{box-sizing:border-box}html{font:16px/1.5 system-ui,sans-serif;color:#201e1b;background:#f7f4ef}
body{margin:0;padding:1rem}.locale{width:min(100%,72rem);margin:0 auto 2rem;padding:clamp(.75rem,3vw,2rem);background:white;border:1px solid #ddd6ca;border-radius:1rem;overflow-wrap:anywhere}
header{border-bottom:1px solid #ded8cf;margin-bottom:1rem;padding-bottom:.5rem}.eyebrow{color:#6c6258;font-size:.8rem;text-transform:uppercase;letter-spacing:.06em}
h1{font-size:clamp(1.5rem,5vw,2.25rem);margin:.25rem 0}h2{font-size:1.1rem;margin:0 0 .5rem;text-transform:capitalize}
.group{margin:1rem 0;padding:1rem;border:1px solid #e6e0d7;border-radius:.75rem}dl{margin:0}.message-row{display:grid;grid-template-columns:minmax(7rem,13rem) minmax(0,1fr);gap:.75rem;padding:.5rem 0;border-top:1px solid #eee9e2}
dt{font-weight:650;overflow-wrap:anywhere}dd{margin:0;min-width:0;overflow-wrap:anywhere;word-break:normal}
@media(max-width:420px){body{padding:.4rem}.locale{padding:.6rem;border-radius:.5rem}.group{padding:.6rem}.message-row{grid-template-columns:minmax(5.5rem,35%) minmax(0,1fr);gap:.5rem;font-size:.9rem}}
</style></head><body>""" + "".join(sections) + """
<script>
const sections=[...document.querySelectorAll('.locale')];
const failures=sections.filter(section=>section.scrollWidth>section.clientWidth);
const messageCount=sections.reduce((sum,section)=>sum+section.querySelectorAll('.message-row').length,0);
document.documentElement.dataset.localeLayout=failures.length?'failed':'passed';
document.documentElement.dataset.localeCount=String(sections.length);
document.documentElement.dataset.messageCount=String(messageCount);
document.documentElement.dataset.failedLocales=failures.map(item=>item.dataset.locale).join(',');
</script></body></html>"""
    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(document, encoding="utf-8")
    return len(sections)


def inspect(chromium, preview, locale_count):
    uri = preview.resolve().as_uri()
    for width in WIDTHS:
        with tempfile.TemporaryDirectory(prefix="lavbe-locale-chrome-") as profile:
            result = subprocess.run(
                [
                    str(chromium), "--headless=new", "--no-sandbox", "--disable-gpu",
                    "--disable-dev-shm-usage", "--hide-scrollbars", "--virtual-time-budget=1500",
                    f"--window-size={width},1000", f"--user-data-dir={profile}", "--dump-dom", uri,
                ],
                check=True,
                capture_output=True,
                text=True,
                timeout=90,
            )
        document = result.stdout
        report = re.search(r'<html[^>]*data-locale-layout="([^"]+)"[^>]*data-locale-count="(\d+)"[^>]*data-message-count="(\d+)"[^>]*data-failed-locales="([^"]*)"', document)
        if not report:
            raise RuntimeError(f"Chromium did not return a locale layout report at {width}px.")
        status, count, messages, failures = report.groups()
        if status != "passed" or int(count) != locale_count or failures:
            raise RuntimeError(f"Locale layout failed at {width}px: status={status}, locales={count}, failures={failures}.")
        print(f"Chromium {width}px: {count} locales, {messages} visible rows, no horizontal overflow.")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--chromium", type=Path, required=True, help="Local Chrome/Chromium executable.")
    parser.add_argument("--packages", type=Path, default=ROOT / "resources/lavbe/locales")
    parser.add_argument("--output", type=Path, default=ROOT / "storage/app/locale-review/preview.html")
    options = parser.parse_args()
    packages = options.packages if options.packages.is_absolute() else ROOT / options.packages
    output = options.output if options.output.is_absolute() else ROOT / options.output
    catalog = json.loads((ROOT / "spec/catalog.json").read_text(encoding="utf-8"))
    count = render_preview(packages, catalog, output)
    if count != len(catalog["locales"]):
        raise SystemExit(f"Expected {len(catalog['locales'])} locale previews, found {count}.")
    inspect(options.chromium, output, count)


if __name__ == "__main__":
    main()
