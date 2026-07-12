#!/usr/bin/env bash
set -euo pipefail

YEAR="${1:-2026-2027}"
PACK_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PYTHON_BIN="${PYTHON_BIN:-python3}"

if [ ! -d "$PACK_ROOT/build/assembled_csv/$YEAR" ]; then
  "$PYTHON_BIN" "$PACK_ROOT/scripts/assemble.py" --year "$YEAR"
fi

"$PYTHON_BIN" "$PACK_ROOT/scripts/preflight_report.py" --year "$YEAR"
