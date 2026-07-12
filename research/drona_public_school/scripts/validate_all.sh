#!/usr/bin/env bash
set -euo pipefail

YEAR="${1:-2026-2027}"
PACK_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PYTHON_BIN="${PYTHON_BIN:-python3}"

step() {
  printf '\n==> %s\n' "$1"
}

python_has_module() {
  "$PYTHON_BIN" - "$1" <<'PY' >/dev/null 2>&1
import importlib.util
import sys
raise SystemExit(0 if importlib.util.find_spec(sys.argv[1]) else 1)
PY
}

step "Assemble ordered CSV files"
"$PYTHON_BIN" "$PACK_ROOT/scripts/assemble.py" --year "$YEAR"

step "Validate structured source CSV relationships"
"$PYTHON_BIN" "$PACK_ROOT/scripts/validate.py" --year "$YEAR"

step "Validate ID-number formulas and reference patterns"
"$PYTHON_BIN" "$PACK_ROOT/master_import_process/scripts/validate_idnumber_patterns.py" \
  --source "$PACK_ROOT" \
  --year "$YEAR"

if [ "$YEAR" = "2026-2027" ]; then
  step "Validate maintained master workbook artifacts"
  if python_has_module openpyxl; then
    "$PYTHON_BIN" "$PACK_ROOT/master_import_process/scripts/validate_master_workbook.py"
  else
    echo "Skipped workbook artifact validation because openpyxl is not installed for $PYTHON_BIN."
    echo "Install dependencies or run with PYTHON_BIN=/path/to/python-with-openpyxl."
  fi
else
  step "Skip workbook artifact validation"
  echo "Workbook artifacts are maintained for 2026-2027; current year is $YEAR."
fi

step "Validate Moodle baseline CSV shape"
php -d memory_limit=512M "$PACK_ROOT/scripts/moodle_cli/cli_validate_school_baseline.php" \
  --dir="$PACK_ROOT/build/assembled_csv/$YEAR"

step "Validate Moodle course-template CSV shape"
php -d memory_limit=512M "$PACK_ROOT/scripts/moodle_cli/cli_validate_course_template_csv.php" \
  --dir="$PACK_ROOT/build/assembled_csv/$YEAR"

step "Generate preflight report and manifest"
"$PYTHON_BIN" "$PACK_ROOT/scripts/preflight_report.py" --year "$YEAR"

step "Validation complete"
echo "Use scripts/import.sh $YEAR dry-run next, then live only after backup and approval."
