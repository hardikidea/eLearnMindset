#!/usr/bin/env bash
set -euo pipefail

YEAR="${1:-2026-2027}"
MODE="${2:-dry-run}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROCESS_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
PACK_ROOT="$(cd "$PROCESS_ROOT/.." && pwd)"
PYTHON_BIN="${PYTHON_BIN:-python3}"
WORKBOOK="${WORKBOOK:-$PROCESS_ROOT/input/school_master_import.xlsx}"
SOURCE_DIR="${SOURCE_DIR:-$PROCESS_ROOT/output/source_csv}"
REPORT_DIR="${REPORT_DIR:-$PROCESS_ROOT/output/reports}"

case "$MODE" in
  validate-only|dry-run|live) ;;
  *)
    echo "Usage: $0 <academic-year> <validate-only|dry-run|live>" >&2
    exit 2
    ;;
esac

if [ ! -f "$WORKBOOK" ]; then
  echo "Workbook not found: $WORKBOOK" >&2
  echo "Copy templates/school_master_import_template.xlsx into input/school_master_import.xlsx and fill it first." >&2
  exit 1
fi

"$PYTHON_BIN" "$SCRIPT_DIR/validate_master_excel.py" --workbook "$WORKBOOK" --year "$YEAR"
rm -rf "$SOURCE_DIR"
"$PYTHON_BIN" "$SCRIPT_DIR/excel_to_source_csv.py" --workbook "$WORKBOOK" --output "$SOURCE_DIR" --year "$YEAR"
"$PYTHON_BIN" "$SCRIPT_DIR/validate_generated_structure.py" --source "$SOURCE_DIR" --year "$YEAR"
"$PYTHON_BIN" "$SCRIPT_DIR/validate_idnumber_patterns.py" --source "$SOURCE_DIR" --year "$YEAR"
"$PYTHON_BIN" "$SCRIPT_DIR/generate_import_reports.py" --source "$SOURCE_DIR" --year "$YEAR" --output-dir "$REPORT_DIR"

"$PYTHON_BIN" "$PACK_ROOT/scripts/assemble.py" --year "$YEAR" --source-root "$SOURCE_DIR"

if [ "$MODE" = "validate-only" ]; then
  "$PACK_ROOT/scripts/import.sh" "$YEAR" validate-only
else
  "$PACK_ROOT/scripts/import.sh" "$YEAR" "$MODE"
fi
