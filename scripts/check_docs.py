#!/usr/bin/env python3
"""Validate the repository documentation entry points and cross-references."""

import re
from pathlib import Path
from urllib.parse import unquote, urlsplit

ROOT = Path(__file__).resolve().parents[1]
LINK = re.compile(r"!?\[[^\]\n]+\]\(([^)\s]+)\)")
HEADING = re.compile(r"^#{1,6}\s+(.+?)\s*#*\s*$", re.MULTILINE)
ACCEPTANCE_ID = re.compile(r"\b(?:BAS|UI|LOC|OWN|MOD|SEO|ADV|PWA|OPS)-\d{2}\b")


def anchors(text):
    return {
        re.sub(r"[^\w\s-]", "", heading.lower()).replace(" ", "-")
        for heading in HEADING.findall(text)
    }


def main():
    documents = sorted(
        [ROOT / "README.md", ROOT / "AGENTS.md", *ROOT.glob("docs/**/*.md")]
    )
    errors = []
    for document in documents:
        for link in LINK.findall(document.read_text(encoding="utf-8")):
            target = urlsplit(link)
            if target.scheme or target.netloc:
                continue
            path = document.parent / unquote(target.path) if target.path else document
            if not path.is_file():
                errors.append(f"{document.relative_to(ROOT)}: invalid link {link}")
            elif target.fragment and path.suffix == ".md":
                if unquote(target.fragment) not in anchors(path.read_text(encoding="utf-8")):
                    errors.append(f"{document.relative_to(ROOT)}: invalid anchor {link}")

    backlog = (ROOT / "docs/delivery/backlog.md").read_text(encoding="utf-8")
    acceptance = (ROOT / "docs/delivery/acceptance.md").read_text(encoding="utf-8")
    backlog_ids = set(ACCEPTANCE_ID.findall(backlog))
    acceptance_ids = ACCEPTANCE_ID.findall(acceptance)
    if backlog_ids != set(acceptance_ids) or len(acceptance_ids) != len(set(acceptance_ids)):
        errors.append("Backlog and acceptance identifiers must match without duplicate matrix rows.")

    localization = (ROOT / "docs/localization.md").read_text(encoding="utf-8")
    locale_rows = re.findall(r"^\| ([a-z]{2}(?:-[A-Z]{2})?) \| [a-z-]+ \|", localization, re.M)
    category_section = localization.split("## Kateqoriyaların sabit xəritəsi", 1)[1]
    category_rows = re.findall(r"^\| ([a-z-]+) \| .+ \| [a-z0-9-]+ \|$", category_section, re.M)
    if len(locale_rows) != 17 or len(set(locale_rows)) != 17:
        errors.append("Expected 17 unique locale rows.")
    if len(category_rows) != 12 or len(set(category_rows)) != 12:
        errors.append("Expected 12 unique category keys.")

    if errors:
        raise SystemExit("\n".join(errors))
    print(f"Verified {len(documents)} documents, {len(acceptance_ids)} acceptance IDs, 17 locales and 12 categories.")


if __name__ == "__main__":
    main()
