#!/usr/bin/env bash
set -euo pipefail
PACK_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FROM_YEAR="${1:-}"
TO_YEAR="${2:-}"
if [ -z "$FROM_YEAR" ] || [ -z "$TO_YEAR" ]; then
  echo "Usage: ./scripts/generate-next-year.sh <from-year> <to-year>" >&2
  echo "Example: ./scripts/generate-next-year.sh 2029-2030 2030-2031" >&2
  exit 1
fi
python3 "$PACK_ROOT/scripts/generate_academic_year.py" --from-year "$FROM_YEAR" --to-year "$TO_YEAR"
"$PACK_ROOT/scripts/assemble.py" --year "$TO_YEAR"
"$PACK_ROOT/scripts/validate.py" --year "$TO_YEAR"
