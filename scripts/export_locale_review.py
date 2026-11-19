#!/usr/bin/env python3
"""Export a locale package diff for the product owner's language review."""

import argparse
import csv
import json
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def load_json(path):
    return json.loads(path.read_text(encoding="utf-8"))


def previous_package(base, relative_path):
    result = subprocess.run(
        ["git", "show", f"{base}:{relative_path.as_posix()}"],
        cwd=ROOT,
        capture_output=True,
        text=True,
        check=False,
    )
    if result.returncode:
        return {}
    try:
        return json.loads(result.stdout)
    except json.JSONDecodeError:
        return {}


def flatten(package):
    rows = {}
    for group, messages in package.get("messages", {}).items():
        if isinstance(messages, dict):
            for key, value in messages.items():
                rows[f"{group}.{key}"] = value
    for key, category in package.get("categories", {}).items():
        for field in ("label", "description", "slug"):
            if field in category:
                rows[f"categories.{key}.{field}"] = category[field]
    for key, page in package.get("legal_pages", {}).items():
        for field, value in page.items():
            rows[f"legal_pages.{key}.{field}"] = value
    rows["legal_status"] = package.get("legal_status", "")
    return rows


def export(directory, base, output):
    packages = sorted(directory.glob("*.json"))
    if not packages:
        raise SystemExit(f"No locale packages found in {directory}.")
    output.parent.mkdir(parents=True, exist_ok=True)
    review_records = []
    git_ref = subprocess.check_output(
        ["git", "rev-parse", "--short", "HEAD"], cwd=ROOT, text=True
    ).strip()
    with output.open("w", encoding="utf-8", newline="") as stream:
        writer = csv.DictWriter(
            stream,
            fieldnames=["locale", "group", "key", "current_text", "previous_text"],
        )
        writer.writeheader()
        for path in packages:
            package = load_json(path)
            relative = path.relative_to(ROOT)
            previous = previous_package(base, relative) if base else {}
            current_values = flatten(package)
            previous_values = flatten(previous)
            locale = package.get("locale", path.stem)
            locale_changed = False
            for dotted_key, current in sorted(current_values.items()):
                previous_value = previous_values.get(dotted_key, "")
                if base and current == previous_value:
                    continue
                group, _, key = dotted_key.partition(".")
                writer.writerow(
                    {
                        "locale": locale,
                        "group": group,
                        "key": key,
                        "current_text": json.dumps(current, ensure_ascii=False, sort_keys=True),
                        "previous_text": json.dumps(previous_value, ensure_ascii=False, sort_keys=True),
                    }
                )
                locale_changed = True
            if not locale_changed:
                continue
            review_records.append(
                {
                    "locale": locale,
                    "package_version": package.get("version", ""),
                    "git_ref": git_ref,
                    "status": "pending",
                }
            )
    manifest = output.with_suffix(".review.json")
    manifest.write_text(
        json.dumps(review_records, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    print(f"Exported {len(packages)} locale packages to {output} and {manifest}.")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--packages", type=Path, default=ROOT / "resources/lavbe/locales")
    parser.add_argument("--base", help="Git ref used to show the previous package version.")
    parser.add_argument("--output", type=Path, default=ROOT / "storage/app/locale-review/current.csv")
    options = parser.parse_args()
    directory = options.packages if options.packages.is_absolute() else ROOT / options.packages
    output = options.output if options.output.is_absolute() else ROOT / options.output
    export(directory, options.base, output)


if __name__ == "__main__":
    main()
