#!/usr/bin/env python3
"""Copy the small Lucide SVG subset used by the Blade UI into public assets."""

from pathlib import Path
import shutil


ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "node_modules" / "lucide-static"
DESTINATION = ROOT / "public" / "icons" / "lucide"
ICONS = (
    "arrow-left",
    "book-open",
    "chevron-down",
    "circle-check",
    "circle-x",
    "feather",
    "heart",
    "info",
    "languages",
    "list-filter",
    "menu",
    "message-circle",
    "moon",
    "pen-line",
    "search",
    "settings-2",
    "shield-check",
    "sun",
    "triangle-alert",
    "user-round",
    "x",
)


def main() -> None:
    if not SOURCE.is_dir():
        raise SystemExit("Lucide static assets are missing; run npm ci first.")

    DESTINATION.mkdir(parents=True, exist_ok=True)
    for icon in ICONS:
        source = SOURCE / "icons" / f"{icon}.svg"
        if not source.is_file():
            raise SystemExit(f"Lucide icon is missing from the installed package: {icon}")
        shutil.copyfile(source, DESTINATION / source.name)

    shutil.copyfile(SOURCE / "LICENSE", DESTINATION / "LICENSE")
    print(f"Synced {len(ICONS)} Lucide SVG icons and license.")


if __name__ == "__main__":
    main()
