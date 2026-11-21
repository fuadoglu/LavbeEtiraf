import importlib.util
import csv
import json
import tempfile
import unittest
from pathlib import Path
from build_locale_packages import build_all, category_slug
from check_locale_previews import render_preview


SCRIPT = Path(__file__).with_name("export_locale_review.py")
SPEC = importlib.util.spec_from_file_location("export_locale_review", SCRIPT)
MODULE = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(MODULE)


class LocaleReviewExportTest(unittest.TestCase):
    def test_flatten_keeps_message_plural_and_category_fields(self):
        package = {
            "messages": {"feed": {"empty": "No stories", "count": {"one": "{count} story", "other": "{count} stories"}}},
            "categories": {"family": {"label": "Family", "description": "Family life", "slug": "family"}},
            "legal_pages": {"privacy": {"title": "Privacy", "body": "Draft", "version": "v1"}},
            "legal_status": "draft",
        }
        self.assertEqual(
            {
                "feed.empty": "No stories",
                "feed.count": {"one": "{count} story", "other": "{count} stories"},
                "categories.family.label": "Family",
                "categories.family.description": "Family life",
                "categories.family.slug": "family",
                "legal_pages.privacy.title": "Privacy",
                "legal_pages.privacy.body": "Draft",
                "legal_pages.privacy.version": "v1",
                "legal_status": "draft",
            },
            MODULE.flatten(package),
        )

    def test_export_writes_grouped_rows_and_pending_review_records(self):
        with tempfile.TemporaryDirectory(dir=MODULE.ROOT) as temporary:
            directory = Path(temporary) / "packages"
            directory.mkdir()
            package = {
                "locale": "en",
                "version": "draft-test",
                "messages": {"feed": {"empty": "No stories"}},
                "categories": {},
                "legal_pages": {},
                "legal_status": "draft",
            }
            (directory / "en.json").write_text(json.dumps(package), encoding="utf-8")
            output = Path(temporary) / "review.csv"
            MODULE.export(directory, None, output)
            with output.open(encoding="utf-8", newline="") as stream:
                rows = list(csv.DictReader(stream))
            manifest = json.loads(output.with_suffix(".review.json").read_text(encoding="utf-8"))
            self.assertEqual("feed", rows[0]["group"])
            self.assertEqual("No stories", rows[0]["current_text"])
            self.assertEqual("pending", manifest[0]["status"])
            self.assertEqual("draft-test", manifest[0]["package_version"])

    def test_package_builder_uses_explicit_source_and_refuses_missing_locale_drafts(self):
        catalog = MODULE.load_json(MODULE.ROOT / "spec/catalog.json")
        contract = MODULE.load_json(MODULE.ROOT / "spec/locale-contract.json")
        source = MODULE.load_json(MODULE.ROOT / "spec/locale-messages.en.json")
        with tempfile.TemporaryDirectory() as temporary:
            directory = Path(temporary)
            packages = build_all(catalog, contract, source, directory, selected="en")
            self.assertEqual("draft", packages["en"]["legal_status"])
            self.assertEqual(12, len(packages["en"]["categories"]))
            with self.assertRaisesRegex(ValueError, "Missing explicit translation draft"):
                build_all(catalog, contract, source, directory)

    def test_category_slugs_use_ascii_readable_locale_labels(self):
        catalog = MODULE.load_json(MODULE.ROOT / "spec/catalog.json")
        locales = {item["code"]: item for item in catalog["locales"]}
        family = next(item for item in catalog["categories"] if item["key"] == "family")
        self.assertEqual("familie", category_slug(family["labels"]["de"], locales["de"], "family"))
        self.assertEqual("kazoku", category_slug(family["labels"]["ja"], locales["ja"], "family"))
        self.assertEqual("gajok", category_slug(family["labels"]["ko"], locales["ko"], "family"))
        self.assertEqual("semya", category_slug(family["labels"]["ru"], locales["ru"], "family"))

    def test_review_preview_contains_all_locales_and_translated_content(self):
        catalog = MODULE.load_json(MODULE.ROOT / "spec/catalog.json")
        with tempfile.TemporaryDirectory() as temporary:
            output = Path(temporary) / "preview.html"
            count = render_preview(MODULE.ROOT / "resources/lavbe/locales", catalog, output)
            rendered = output.read_text(encoding="utf-8")
        self.assertEqual(17, count)
        self.assertIn('data-locale="pt-BR"', rendered)
        self.assertIn("pending", rendered)
        self.assertIn("Advertisement", rendered)
        self.assertIn("家族", rendered)


if __name__ == "__main__":
    unittest.main()
