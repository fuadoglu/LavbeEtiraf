#!/usr/bin/env python3
"""Validate documentation and, when supplied, generated language packages."""

import argparse
import json
import re
import subprocess
from dataclasses import dataclass
from pathlib import Path
from urllib.parse import unquote, urlsplit

ROOT = Path(__file__).resolve().parents[1]
EXPECTED_LOCALES = tuple("en de fr nl sv nb da fi ja ko es it pt-BR pl tr az ru".split())
EXPECTED_CATEGORIES = tuple(
    "relationships family friends money work identity grief education wellbeing everyday values life-changes".split()
)
EXPECTED_WORK_IDS = {f"LAV-{number:03d}" for number in range(17)} | {f"LAV-{number:03d}" for number in range(30, 73)}
LINK = re.compile(r"!?\[[^\]\n]+\]\(([^)\s]+)\)")
HEADING = re.compile(r"^#{1,6}\s+(.+?)\s*#*\s*$", re.MULTILINE)
ACCEPTANCE_ID = re.compile(r"\b(?:DOC|BAS|UI|LOC|OWN|MOD|SEO|ADV|PWA|OPS|CFG|DISC|PAG|FEED|SEARCH|DEMO|BRAND|TAX|CAT|READ|FAV|SHARE)-\d{2}\b")
ACCEPTANCE_ROW = re.compile(r"^\| (" + ACCEPTANCE_ID.pattern + r") \|", re.MULTILINE)
ISSUE_HEADING = re.compile(r"^## (LAV-\d{3}) · .+$", re.MULTILINE)
PLACEHOLDER = re.compile(r"\{([a-z][a-z0-9_]*)\}")
SLUG = re.compile(r"[a-z0-9]+(?:-[a-z0-9]+)*")
LANGUAGE_ROW = re.compile(
    r"^\| ([a-z]{2}(?:-[A-Z]{2})?) \| ([a-z-]+) \| ([^|]+) \| ([^|]+) \| ([A-Z]{2}) \|$",
    re.MULTILINE,
)
CATEGORY_ROW = re.compile(
    r"^\| ([a-z-]+) \| ([^|]+) \| ([^|]+) \| ([^|]+) \| ([a-z0-9-]+) \|$", re.MULTILINE
)


def read_json(path):
    def unique_pairs(pairs):
        result = {}
        for key, value in pairs:
            if key in result:
                raise ValueError(f"duplicate JSON key {key!r}")
            result[key] = value
        return result

    return json.loads(path.read_text(encoding="utf-8"), object_pairs_hook=unique_pairs)


def anchors(text):
    return {
        re.sub(r"[^\w\s-]", "", heading.lower()).replace(" ", "-")
        for heading in HEADING.findall(text)
    }


@dataclass
class Report:
    errors: list

    def check(self, condition, message):
        if not condition:
            self.errors.append(message)


def check_links(root, documents, report):
    for document in documents:
        for link in LINK.findall(document.read_text(encoding="utf-8")):
            target = urlsplit(link)
            if target.scheme or target.netloc:
                continue
            path = document.parent / unquote(target.path) if target.path else document
            report.check(path.is_file(), f"{document.relative_to(root)}: invalid link {link}")
            if path.is_file() and target.fragment and path.suffix == ".md":
                report.check(
                    unquote(target.fragment) in anchors(path.read_text(encoding="utf-8")),
                    f"{document.relative_to(root)}: invalid anchor {link}",
                )


def check_catalog(root, catalog, contract, report):
    local = (root / "docs/localization.md").read_text(encoding="utf-8")
    locales = catalog["locales"]
    categories = catalog["categories"]
    report.check(tuple(item["code"] for item in locales) == EXPECTED_LOCALES, "Unexpected or unordered locale codes in catalog.")
    report.check(tuple(item["key"] for item in categories) == EXPECTED_CATEGORIES, "Unexpected or unordered category keys in catalog.")
    category_icons = tuple(item.get("icon") for item in categories)
    report.check(len(category_icons) == len(EXPECTED_CATEGORIES) and all(category_icons) and len(set(category_icons)) == len(category_icons), "Each category must have a distinct icon.")

    for item in locales:
        code = item["code"]
        report.check(item["segment"] == code.lower(), f"{code}: invalid URL segment.")
        report.check(item["dir"] in {"ltr", "rtl"}, f"{code}: invalid direction.")
    locale_rows = [tuple(cell.strip() for cell in row) for row in LANGUAGE_ROW.findall(local)]
    report.check(
        locale_rows == [tuple(item[k] for k in ("code", "segment", "native", "english", "flag")) for item in locales],
        "Localized language table differs from spec/catalog.json.",
    )
    category_rows = [tuple(cell.strip() for cell in row) for row in CATEGORY_ROW.findall(local)]
    report.check(
        category_rows == [(item["key"], item["labels"].get("az"), item["labels"].get("en"), item["descriptions"].get("az"), item["icon"]) for item in categories],
        "Localized category table differs from spec/catalog.json.",
    )
    for item in categories:
        labels = item["labels"]
        report.check(set(labels) == set(EXPECTED_LOCALES), f"{item['key']}: category label locales differ.")
        report.check(all(isinstance(value, str) and value.strip() for value in labels.values()), f"{item['key']}: empty category label.")
        descriptions = item["descriptions"]
        report.check(set(descriptions) == set(EXPECTED_LOCALES), f"{item['key']}: category description locales differ.")
        report.check(all(isinstance(value, str) and value.strip() for value in descriptions.values()), f"{item['key']}: empty category description.")

    detection = catalog["first_visit"]
    report.check(detection["fallback"] in EXPECTED_LOCALES, "Unknown locale fallback.")
    country_defaults = detection["country_defaults"]
    report.check(
        all(re.fullmatch(r"[A-Z]{2}", code) and lang in EXPECTED_LOCALES for code, lang in country_defaults.items()),
        "Invalid country locale fallback.",
    )
    report.check(not set(country_defaults).intersection(detection["multilingual_country_fallback"]), "A country has conflicting locale fallbacks.")
    report.check(set(detection["language_aliases"].values()) <= set(EXPECTED_LOCALES), "Unknown locale alias target.")

    groups = contract["groups"]
    for group, keys in groups.items():
        report.check(len(keys) == len(set(keys)) and bool(keys), f"{group}: invalid key list.")
    message_paths = {f"{group}.{key}" for group, keys in groups.items() for key in keys}
    report.check(set(contract["placeholders"]) <= message_paths, "Unknown placeholder path in contract.")
    report.check(set(contract["plural_keys"]) <= message_paths, "Unknown plural path in contract.")
    report.check(set(contract["cardinal_forms"]) == set(EXPECTED_LOCALES), "Cardinal forms do not cover the exact locale set.")
    for code, forms in contract["cardinal_forms"].items():
        report.check("other" in forms and len(forms) == len(set(forms)), f"{code}: invalid cardinal forms.")


def check_backlog(root, report):
    backlog = (root / "docs/delivery/backlog.md").read_text(encoding="utf-8")
    acceptance = (root / "docs/delivery/acceptance.md").read_text(encoding="utf-8")
    acceptance_ids = ACCEPTANCE_ROW.findall(acceptance)
    acceptance_set = set(acceptance_ids)
    report.check(len(acceptance_set) == len(acceptance_ids), "Duplicate acceptance matrix ID.")
    headers = list(ISSUE_HEADING.finditer(backlog))
    work_ids = [header.group(1) for header in headers]
    report.check(set(work_ids) == EXPECTED_WORK_IDS and len(work_ids) == len(EXPECTED_WORK_IDS), "Backlog must have one section for each expected LAV work item.")
    used = set()
    ordered_dependencies = set()
    for index, header in enumerate(headers):
        end = headers[index + 1].start() if index + 1 < len(headers) else len(backlog)
        section = backlog[header.end():end]
        declared = re.search(r"\*\*Qəbul:\*\* ([^\n]+)", section)
        ids = set(ACCEPTANCE_ID.findall(declared.group(1))) if declared else set()
        used.update(ids)
        report.check(bool(ids) and ids <= acceptance_set, f"{header.group(1)}: missing or unknown acceptance ID.")
        dependency = re.search(r"\*\*Asılılıq:\*\* (.+?) \*\*Qəbul:", section)
        dependencies = set(re.findall(r"LAV-\d{3}", dependency.group(1))) if dependency else set()
        report.check(dependency is not None, f"{header.group(1)}: missing dependency declaration.")
        report.check(dependencies <= ordered_dependencies, f"{header.group(1)}: unknown or forward dependency.")
        ordered_dependencies.add(header.group(1))
    report.check(used == acceptance_set, "Backlog does not reference every acceptance matrix ID.")
    return len(acceptance_set)


def check_message(path, value, names, report):
    if not isinstance(value, str) or not value.strip():
        report.errors.append(f"{path}: expected a nonempty string.")
        return
    remaining = PLACEHOLDER.sub("", value)
    report.check(set(PLACEHOLDER.findall(value)) == set(names) and not re.search(r"[{}]", remaining), f"{path}: invalid placeholder set.")
    report.check(not re.search(r"<\s*/?\s*[A-Za-z][^>]*>", value), f"{path}: raw HTML is outside the message contract.")


def check_package(path, locale, contract, report):
    package = read_json(path)
    code = locale["code"]
    prefix = f"{path.name}"
    fields = {"locale", "version", "messages", "categories", "legal_pages", "legal_status"}
    report.check(set(package) == fields, f"{prefix}: unexpected package fields.")
    report.check(package.get("locale") == code and isinstance(package.get("version"), str) and bool(package["version"].strip()), f"{prefix}: invalid locale/version.")
    messages = package.get("messages", {})
    report.check(set(messages) == set(contract["groups"]), f"{prefix}: unexpected message groups.")
    for group, keys in contract["groups"].items():
        actual = messages.get(group, {})
        report.check(set(actual) == set(keys), f"{prefix}:{group}: missing or unexpected keys.")
        for key in keys:
            message = f"{group}.{key}"
            value = actual.get(key)
            names = contract["placeholders"].get(message, [])
            if message in contract["plural_keys"]:
                forms = set(contract["cardinal_forms"][code])
                if not isinstance(value, dict):
                    report.errors.append(f"{prefix}:{message}: expected plural object.")
                    continue
                report.check(set(value) == forms, f"{prefix}:{message}: invalid plural forms.")
                for form in forms:
                    check_message(f"{prefix}:{message}.{form}", value.get(form), names, report)
            else:
                check_message(f"{prefix}:{message}", value, names, report)

    categories = package.get("categories", {})
    report.check(set(categories) == set(EXPECTED_CATEGORIES), f"{prefix}: incorrect category keys.")
    slugs = []
    for key, value in categories.items():
        report.check(set(value) == set(contract["category_fields"]), f"{prefix}:{key}: invalid category fields.")
        for field in ("label", "description"):
            check_message(f"{prefix}:{key}.{field}", value.get(field), [], report)
        slug = value.get("slug")
        report.check(isinstance(slug, str) and bool(SLUG.fullmatch(slug)), f"{prefix}:{key}: invalid slug.")
        if isinstance(slug, str):
            slugs.append(slug)
    report.check(len(slugs) == len(set(slugs)), f"{prefix}: duplicate category slug.")

    status = package.get("legal_status")
    report.check(status in contract["legal_status_values"], f"{prefix}: invalid legal status.")
    legal_pages = package.get("legal_pages", {})
    report.check(set(legal_pages) == set(contract["groups"]["legal"]), f"{prefix}: incorrect legal page keys.")
    for key, value in legal_pages.items():
        report.check(set(value) == set(contract["legal_page_fields"]), f"{prefix}:{key}: invalid legal page fields.")
        for field in contract["legal_page_fields"]:
            check_message(f"{prefix}:legal.{key}.{field}", value.get(field), [], report)


def check_packages(directory, catalog, contract, report):
    expected = {f"{item['segment']}.json" for item in catalog["locales"]}
    actual = {path.name for path in directory.glob("*.json")}
    report.check(actual == expected, f"{directory}: locale package set differs, missing {sorted(expected - actual)}, unexpected {sorted(actual - expected)}.")
    for item in catalog["locales"]:
        path = directory / f"{item['segment']}.json"
        if path.name not in actual:
            continue
        try:
            check_package(path, item, contract, report)
        except (KeyError, TypeError, AttributeError, ValueError) as exc:
            report.errors.append(f"{path}: invalid package shape: {exc}")


def check_feed_translations(directory, catalog, report):
    expected_keys = {
        "feed_title", "feed_subtitle", "sort_label", "topics_button", "sort_new", "sort_weekly", "sort_popular", "sort_popular_short", "sort_updates",
        "categories", "all_categories", "category_empty", "feed_empty", "feed_empty_description",
        "pagination", "previous", "next", "search_title", "search_label", "search_submit",
        "search_empty", "retry", "refresh",
        "untitled_confession", "breadcrumb", "offline_title", "offline_body",
    }
    for locale in catalog["locales"]:
        folder = locale["code"]
        path = directory / folder / "lavbe.php"
        report.check(path.is_file(), f"{path}: feed translation is missing.")
        if not path.is_file():
            continue
        try:
            result = subprocess.run(
                ["php", "-r", '$messages = require $argv[1]; echo json_encode($messages, JSON_THROW_ON_ERROR);', str(path)],
                check=True, capture_output=True, text=True,
            )
            messages = json.loads(result.stdout)
            actual_keys = set(messages)
            report.check(actual_keys == expected_keys, f"{path}: feed translation keys differ; missing {sorted(expected_keys - actual_keys)}, extra {sorted(actual_keys - expected_keys)}.")
            report.check(all(isinstance(value, str) and value.strip() for value in messages.values()), f"{path}: feed translation contains an empty or non-string value.")
        except (OSError, subprocess.CalledProcessError, json.JSONDecodeError) as exc:
            report.errors.append(f"{path}: could not validate feed translations: {exc}")


def check_guest_translations(directory, catalog, report):
    expected_keys = {
        "write_title", "write_intro", "write_anonymous", "write_options", "write_subject", "write_body", "write_body_hint", "write_topic", "topic_guide",
        "write_expectation", "write_share", "write_similar", "write_advice", "write_age", "write_submit",
        "write_pending_title", "write_pending_body", "write_recovery_title", "write_recovery_once",
        "write_owner_link", "write_recover_title", "write_recovery_code", "write_recover_submit",
        "write_status_title", "write_recovery_success", "write_duplicate_title", "write_duplicate_body",
        "validation_body_required", "validation_body_max", "validation_age_required", "validation_title_max",
        "write_remember_device", "write_conflict_body", "write_not_found", "write_unavailable",
        "validation_topic", "validation_expectation", "validation_refresh", "write_too_many",
        "write_withdraw", "write_confirm_withdraw", "write_republish", "write_confirm_republish",
        "write_withdrawn", "write_republish_pending", "author_name_label", "author_name_hint",
        "validation_title_required", "device_title", "device_intro", "device_empty", "device_status", "device_forget", "device_forget_hint", "device_forgotten", "write_public_search_notice", "characters_remaining",
    }
    for locale in catalog["locales"]:
        folder = locale["code"]
        path = directory / folder / "guest.php"
        report.check(path.is_file(), f"{path}: guest translation is missing.")
        if not path.is_file():
            continue
        try:
            result = subprocess.run(
                ["php", "-r", '$messages = require $argv[1]; echo json_encode($messages, JSON_THROW_ON_ERROR);', str(path)],
                check=True, capture_output=True, text=True,
            )
            messages = json.loads(result.stdout)
            actual_keys = set(messages)
            report.check(actual_keys == expected_keys, f"{path}: guest translation keys differ; missing {sorted(expected_keys - actual_keys)}, extra {sorted(actual_keys - expected_keys)}.")
            report.check(all(isinstance(value, str) and value.strip() for value in messages.values()), f"{path}: guest translation contains an empty or non-string value.")
        except (OSError, subprocess.CalledProcessError, json.JSONDecodeError) as exc:
            report.errors.append(f"{path}: could not validate guest translations: {exc}")


def verify(root, packages=None):
    report = Report([])
    documents = sorted([root / "README.md", root / "AGENTS.md", *root.glob("docs/**/*.md")])
    check_links(root, documents, report)
    catalog = read_json(root / "spec/catalog.json")
    contract = read_json(root / "spec/locale-contract.json")
    check_catalog(root, catalog, contract, report)
    acceptance_count = check_backlog(root, report)
    if packages is not None:
        check_packages(packages, catalog, contract, report)
    translations = root / "resources/lang"
    if translations.is_dir():
        check_feed_translations(translations, catalog, report)
        check_guest_translations(translations, catalog, report)
    return report, len(documents), acceptance_count


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--packages", type=Path, help="Check generated application locale JSON files too.")
    options = parser.parse_args()
    try:
        report, documents, acceptance_count = verify(ROOT, options.packages)
    except (OSError, KeyError, TypeError, AttributeError, ValueError) as exc:
        raise SystemExit(f"Invalid specification: {exc}") from exc
    if report.errors:
        raise SystemExit("\n".join(report.errors))
    print(f"Verified {documents} documents, {acceptance_count} acceptance IDs, {len(EXPECTED_WORK_IDS)} work items, 17 locales and 408 category label/description strings.")
    if options.packages is not None:
        print("Verified 17 locale package shapes.")


if __name__ == "__main__":
    main()
