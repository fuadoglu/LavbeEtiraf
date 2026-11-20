#!/usr/bin/env python3
"""Copy the small Lucide SVG subset used by the Blade UI into public assets."""

from pathlib import Path
import shutil


ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "node_modules" / "lucide-static"
DESTINATION = ROOT / "public" / "icons" / "lucide"
ICONS = (
    "baby",
    "arrow-left",
    "bell",
    "book-open",
    "briefcase-business",
    "calendar-days",
    "chevron-down",
    "circle-check",
    "circle-x",
    "coffee",
    "compass",
    "eye",
    "feather",
    "fingerprint",
    "flame",
    "graduation-cap",
    "heart",
    "heart-handshake",
    "heart-pulse",
    "house",
    "info",
    "languages",
    "list-filter",
    "menu",
    "message-circle",
    "moon",
    "pen-line",
    "route",
    "search",
    "share-2",
    "settings-2",
    "shield-check",
    "signpost",
    "sparkles",
    "sun",
    "triangle-alert",
    "user-round",
    "users-round",
    "wallet",
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
