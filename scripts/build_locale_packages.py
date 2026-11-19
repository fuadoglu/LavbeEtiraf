#!/usr/bin/env python3
"""Build locale JSON packages from explicit translation drafts; never fall back silently."""

import argparse
import json
from pathlib import Path

from check_docs import Report, check_package, read_json

ROOT = Path(__file__).resolve().parents[1]
LEGAL_DRAFT = "Draft. Confirm local requirements, the actual service operator, and data practices before publication."


def build_package(locale, catalog, contract, messages, draft):
    code = locale["code"]
    descriptions = draft.get("category_descriptions", {})
    categories = {}
    for category in catalog["categories"]:
        key = category["key"]
        description = descriptions.get(key)
        if not isinstance(description, str) or not description.strip():
            raise ValueError(f"{code}: category description missing for {key}; translations are required.")
        categories[key] = {
            "label": category["labels"][code],
            "description": description,
            "slug": key,
        }
    legal_body = draft.get("legal_body")
    if not isinstance(legal_body, str) or not legal_body.strip():
        raise ValueError(f"{code}: legal_body translation draft is required.")
    legal_pages = {
        key: {
            "title": messages["legal"][key],
            "body": legal_body,
            "version": draft["version"],
        }
        for key in contract["groups"]["legal"]
    }
    package = {
        "locale": code,
        "version": draft["version"],
        "messages": messages,
        "categories": categories,
        "legal_pages": legal_pages,
        "legal_status": "draft",
    }
    report = Report([])
    # Run validation against the in-memory package without weakening the package contract.
    from tempfile import NamedTemporaryFile

    with NamedTemporaryFile(mode="w", encoding="utf-8", suffix=".json") as stream:
        json.dump(package, stream, ensure_ascii=False)
        stream.flush()
        check_package(Path(stream.name), locale, contract, report)
    if report.errors:
        raise ValueError("\n".join(report.errors))
    return package


def load_draft(locale, source, draft_directory):
    code = locale["code"]
    if code == "en":
        messages = source
        draft = {
            "version": "draft-1",
            "category_descriptions": {},
            "legal_body": LEGAL_DRAFT,
        }
        return messages, draft
    path = draft_directory / f"{locale['segment']}.json"
    if not path.is_file():
        display_path = path.relative_to(ROOT) if path.is_relative_to(ROOT) else path
        raise FileNotFoundError(f"Missing explicit translation draft: {display_path}")
    draft = read_json(path)
    if set(draft) != {"locale", "version", "messages", "category_descriptions", "legal_body"}:
        raise ValueError(f"{path}: draft fields must be locale, version, messages, category_descriptions, legal_body.")
    if draft["locale"] != code or not isinstance(draft["version"], str) or not draft["version"].strip():
        raise ValueError(f"{path}: locale or version is invalid.")
    return draft["messages"], draft


def build_all(catalog, contract, source, draft_directory, selected=None):
    locales = [item for item in catalog["locales"] if selected is None or item["segment"] == selected]
    packages = {}
    errors = []
    for locale in locales:
        try:
            messages, draft = load_draft(locale, source, draft_directory)
            # English category descriptions are derived from the English catalog labels.
            if locale["code"] == "en":
                draft["category_descriptions"] = {
                    item["key"]: f"Confessions about {item['labels']['en']}."
                    for item in catalog["categories"]
                }
            packages[locale["segment"]] = build_package(locale, catalog, contract, messages, draft)
        except (OSError, KeyError, TypeError, ValueError) as exc:
            errors.append(str(exc))
    if errors:
        raise ValueError("\n".join(errors))
    if selected is None and len(packages) != len(catalog["locales"]):
        raise ValueError(f"Built {len(packages)} packages; expected {len(catalog['locales'])}.")
    return packages


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--drafts", type=Path, default=ROOT / "spec/locale-drafts")
    parser.add_argument("--output", type=Path, default=ROOT / "resources/lavbe/locales")
    parser.add_argument("--only", help="Build one locale segment while drafting, e.g. az or pt-br.")
    options = parser.parse_args()
    drafts = options.drafts if options.drafts.is_absolute() else ROOT / options.drafts
    output = options.output if options.output.is_absolute() else ROOT / options.output
    catalog = read_json(ROOT / "spec/catalog.json")
    contract = read_json(ROOT / "spec/locale-contract.json")
    source = read_json(ROOT / "spec/locale-messages.en.json")
    if options.only and options.only not in {item["segment"] for item in catalog["locales"]}:
        raise SystemExit(f"Unknown locale segment: {options.only}")
    try:
        packages = build_all(catalog, contract, source, drafts, options.only)
    except (OSError, KeyError, TypeError, ValueError) as exc:
        raise SystemExit(str(exc)) from exc
    output.mkdir(parents=True, exist_ok=True)
    for segment, package in packages.items():
        (output / f"{segment}.json").write_text(
            json.dumps(package, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
        )
    print(f"Built {len(packages)} explicit locale translation draft package(s).")


if __name__ == "__main__":
    main()
