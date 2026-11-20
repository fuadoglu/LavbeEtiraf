"""Keep user-facing text in translation files, including accessible labels."""
import re
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


class UiCopyTest(unittest.TestCase):
    def test_blade_text_and_labels_come_from_locale_files(self):
        errors = []
        for path in sorted((ROOT / "resources/views").rglob("*.blade.php")):
            source = path.read_text(encoding="utf-8")
            # Blade expressions can contain HTML, quoted text and PHP object arrows.
            markup = re.sub(r"{{--.*?--}}|{{.*?}}|{!!.*?!!}", "{}", source, flags=re.S)
            markup = re.sub(r"<script\b[^>]*>.*?</script>", "", markup, flags=re.S)
            for match in re.finditer(r"<[\w:-]+(?:\s[^<>]*?)?>\s*([^<>{}@]+?)\s*<", markup):
                value = match[1].strip()
                if value not in {"L", "Lavbe"} and any(char.isalpha() for char in value):
                    errors.append(f"{path.relative_to(ROOT)}: {value}")
            for match in re.finditer(r'(?<![:\w-])(?:aria-label|placeholder|alt|title|label|hint)="([^"{}]+)"', markup):
                value = match[1]
                if value != "Lavbe" and any(char.isalpha() for char in value):
                    errors.append(f"{path.relative_to(ROOT)}: {value}")
        self.assertEqual([], errors)
