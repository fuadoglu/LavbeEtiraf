"""Regression tests for the documentation and locale package validator."""

import json
import shutil
import unittest
from pathlib import Path
from tempfile import TemporaryDirectory

from check_docs import ROOT, Report, check_package, read_json, verify


def make_package(locale, contract, catalog):
    code = locale["code"]
    messages = {}
    for group, keys in contract["groups"].items():
        messages[group] = {}
        for key in keys:
            path = f"{group}.{key}"
            text = "Sintetik mətn " + " ".join(
                "{" + name + "}" for name in contract["placeholders"].get(path, [])
            )
            if path in contract["plural_keys"]:
                messages[group][key] = {form: text for form in contract["cardinal_forms"][code]}
            else:
                messages[group][key] = text
    return {
        "locale": code,
        "version": "fixture-1",
        "messages": messages,
        "categories": {
            category["key"]: {
                "label": category["labels"][code],
                "description": "Sintetik kateqoriya",
                "slug": category["key"],
            }
            for category in catalog["categories"]
        },
        "legal_pages": {
            key: {"title": "Test", "body": "Sintetik hüquqi nümunə", "version": "test-1"}
            for key in contract["groups"]["legal"]
        },
        "legal_status": "draft",
    }


def write_json(path, data):
    path.write_text(json.dumps(data, ensure_ascii=False), encoding="utf-8")


class DocsTests(unittest.TestCase):
    def setUp(self):
        self.temp = TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        for name in ("docs", "spec"):
            shutil.copytree(ROOT / name, self.root / name)
        for name in ("README.md", "AGENTS.md"):
            shutil.copy2(ROOT / name, self.root / name)
        if (ROOT / "resources/lang").is_dir():
            shutil.copytree(ROOT / "resources/lang", self.root / "resources/lang")

    def test_baseline_passes(self):
        report, _, _ = verify(self.root)
        self.assertFalse(report.errors)

    def test_wrong_locale_and_category_with_unchanged_count_fail(self):
        path = self.root / "docs/localization.md"
        content = path.read_text(encoding="utf-8")
        content = content.replace("| sv | sv | Svenska", "| xx | xx | Svenska")
        content = content.replace("| family | Ailə", "| familyx | Ailə")
        path.write_text(content, encoding="utf-8")
        report, _, _ = verify(self.root)
        self.assertIn("Localized language table differs from spec/catalog.json.", report.errors)
        self.assertIn("Localized category table differs from spec/catalog.json.", report.errors)

    def test_wrong_keys_even_when_catalog_and_table_match_fail(self):
        path = self.root / "spec/catalog.json"
        catalog = read_json(path)
        catalog["locales"][4].update(code="xx", segment="xx")
        catalog["categories"][1]["key"] = "familyx"
        for item in catalog["categories"]:
            item["labels"]["xx"] = item["labels"].pop("sv")
        write_json(path, catalog)
        page = self.root / "docs/localization.md"
        content = page.read_text(encoding="utf-8").replace("| sv | sv | Svenska", "| xx | xx | Svenska")
        page.write_text(content.replace("| family | Ailə", "| familyx | Ailə"), encoding="utf-8")
        report, _, _ = verify(self.root)
        self.assertIn("Unexpected or unordered locale codes in catalog.", report.errors)
        self.assertIn("Unexpected or unordered category keys in catalog.", report.errors)

    def test_unknown_acceptance_and_broken_link_fail(self):
        path = self.root / "docs/delivery/backlog.md"
        content = path.read_text(encoding="utf-8").replace("**Qəbul:** BAS-01", "**Qəbul:** BAS-99")
        path.write_text(content.replace("(operations.md)", "(missing.md)"), encoding="utf-8")
        report, _, _ = verify(self.root)
        self.assertIn("LAV-001: missing or unknown acceptance ID.", report.errors)
        self.assertTrue(any("invalid link missing.md" in error for error in report.errors))

    def test_forward_dependency_fails(self):
        path = self.root / "docs/delivery/backlog.md"
        content = path.read_text(encoding="utf-8").replace(
            "**Asılılıq:** LAV-004. **Qəbul:** MOD-01",
            "**Asılılıq:** LAV-015. **Qəbul:** MOD-01",
        )
        path.write_text(content, encoding="utf-8")
        report, _, _ = verify(self.root)
        self.assertIn("LAV-005: unknown or forward dependency.", report.errors)

    def test_valid_full_fixture_passes_and_missing_locale_fails(self):
        catalog = read_json(self.root / "spec/catalog.json")
        contract = read_json(self.root / "spec/locale-contract.json")
        directory = self.root / "packages"
        directory.mkdir()
        for locale in catalog["locales"]:
            write_json(directory / f"{locale['segment']}.json", make_package(locale, contract, catalog))
        report, _, _ = verify(self.root, directory)
        self.assertFalse(report.errors)
        (directory / "sv.json").unlink()
        report, _, _ = verify(self.root, directory)
        self.assertTrue(any("missing ['sv.json']" in error for error in report.errors))

    def test_invalid_placeholders_plural_slug_and_raw_html_fail(self):
        catalog = read_json(self.root / "spec/catalog.json")
        contract = read_json(self.root / "spec/locale-contract.json")
        locale = next(item for item in catalog["locales"] if item["code"] == "ru")
        fixture = make_package(locale, contract, catalog)
        fixture["messages"]["write"]["invalid"] = "Səhv {limit"
        fixture["messages"]["write"]["body"] = "<strong>Raw HTML</strong>"
        fixture["messages"]["notifications"]["new_comments"].pop("few")
        fixture["categories"]["family"]["slug"] = fixture["categories"]["work"]["slug"]
        path = self.root / "ru.json"
        write_json(path, fixture)
        report = Report([])
        check_package(path, locale, contract, report)
        self.assertIn("ru.json:write.invalid: invalid placeholder set.", report.errors)
        self.assertIn("ru.json:write.body: raw HTML is outside the message contract.", report.errors)
        self.assertIn("ru.json:notifications.new_comments: invalid plural forms.", report.errors)
        self.assertIn("ru.json: duplicate category slug.", report.errors)

    def test_feed_ui_translation_contract_fails_when_key_is_missing(self):
        path = self.root / "resources/lang/ja/lavbe.php"
        content = path.read_text(encoding="utf-8")
        content = content.replace(", 'refresh' => 'フィードを更新'", "")
        path.write_text(content, encoding="utf-8")
        report, _, _ = verify(self.root)
        self.assertTrue(any("resources/lang/ja/lavbe.php: feed translation keys differ" in error for error in report.errors))

    def test_guest_ui_translation_contract_fails_when_key_is_missing(self):
        path = self.root / "resources/lang/ja/guest.php"
        content = path.read_text(encoding="utf-8")
        content = content.replace("    'write_submit' => '確認に送信',\n", "")
        path.write_text(content, encoding="utf-8")
        report, _, _ = verify(self.root)
        self.assertTrue(any("resources/lang/ja/guest.php: guest translation keys differ" in error for error in report.errors))
