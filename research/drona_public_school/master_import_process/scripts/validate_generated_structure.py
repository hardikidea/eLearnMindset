#!/usr/bin/env python3
from __future__ import annotations

import argparse
import sys
from pathlib import Path

from common import PACK_ROOT, SOURCE_FILES, expected_headers, read_header, resolve_tokens


def validate(source: Path, year: str, reference_root: Path) -> list[str]:
    errors: list[str] = []
    for entry in SOURCE_FILES:
        target = source / resolve_tokens(entry["source"], year)
        if not target.exists():
            if entry.get("required"):
                errors.append(f"Missing required generated CSV: {target.relative_to(source)}")
            continue
        actual = read_header(target)
        expected = expected_headers(entry, year, reference_root)
        if not actual:
            errors.append(f"{target.relative_to(source)} has an empty header row.")
        elif expected and actual != expected:
            missing = [field for field in expected if field not in actual]
            extra = [field for field in actual if field not in expected]
            errors.append(f"{target.relative_to(source)} header mismatch. missing={missing[:8]} extra={extra[:8]}")
    return errors


def main():
    parser = argparse.ArgumentParser(description="Validate generated source CSV folder structure and headers.")
    parser.add_argument("--source", required=True)
    parser.add_argument("--year", default="2026-2027")
    parser.add_argument("--reference-root", default=str(PACK_ROOT))
    args = parser.parse_args()

    errors = validate(Path(args.source), args.year, Path(args.reference_root).resolve())
    if errors:
        print("Generated structure validation failed:")
        for error in errors[:80]:
            print(f"- {error}")
        if len(errors) > 80:
            print(f"... {len(errors) - 80} more errors")
        sys.exit(1)
    print("Generated structure validation passed.")


if __name__ == "__main__":
    main()
