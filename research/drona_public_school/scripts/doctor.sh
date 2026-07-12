#!/usr/bin/env bash
set -euo pipefail

YEAR="${1:-2026-2027}"
PACK_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PROJECT_ROOT="${PROJECT_ROOT:-$(git -C "$PACK_ROOT" rev-parse --show-toplevel 2>/dev/null || pwd)}"
PYTHON_BIN="${PYTHON_BIN:-python3}"
MOODLE_SERVICE="${MOODLE_SERVICE:-moodle}"

failures=0
warnings=0

ok() {
  printf '[OK] %s\n' "$1"
}

warn() {
  warnings=$((warnings + 1))
  printf '[WARN] %s\n' "$1"
}

fail() {
  failures=$((failures + 1))
  printf '[FAIL] %s\n' "$1"
}

check_command() {
  if command -v "$1" >/dev/null 2>&1; then
    ok "$1 found: $(command -v "$1")"
  else
    fail "$1 is required but was not found"
  fi
}

echo "School master-pack doctor"
echo "-------------------------"
echo "Pack: $PACK_ROOT"
echo "Year: $YEAR"
echo

check_command "$PYTHON_BIN"
check_command php

if command -v docker >/dev/null 2>&1; then
  ok "docker found: $(command -v docker)"
  if docker info >/dev/null 2>&1; then
    ok "Docker daemon is reachable"
  else
    warn "Docker daemon is not reachable; Moodle dry-run/live import will not run"
  fi
else
  warn "docker not found; offline validation still works, container import will not"
fi

if command -v soffice >/dev/null 2>&1; then
  ok "LibreOffice found: $(command -v soffice)"
elif [ -x /Applications/LibreOffice.app/Contents/MacOS/soffice ]; then
  ok "LibreOffice found: /Applications/LibreOffice.app/Contents/MacOS/soffice"
else
  warn "LibreOffice not found; macro workbook smoke conversion will be skipped or fail"
fi

if "$PYTHON_BIN" - <<'PY' >/dev/null 2>&1
import openpyxl
PY
then
  ok "Python openpyxl module is available"
else
  warn "Python openpyxl module is missing; install with: python3 -m pip install -r master_import_process/requirements.txt"
fi

for required in \
  "$PACK_ROOT/master" \
  "$PACK_ROOT/registration" \
  "$PACK_ROOT/templates" \
  "$PACK_ROOT/operations" \
  "$PACK_ROOT/years/$YEAR" \
  "$PACK_ROOT/scripts/moodle_cli" \
  "$PACK_ROOT/master_import_process/scripts"; do
  if [ -d "$required" ]; then
    ok "Directory exists: ${required#$PACK_ROOT/}"
  else
    fail "Missing directory: ${required#$PACK_ROOT/}"
  fi
done

for required in \
  "$PACK_ROOT/scripts/assemble.py" \
  "$PACK_ROOT/scripts/validate.py" \
  "$PACK_ROOT/scripts/preflight_report.py" \
  "$PACK_ROOT/scripts/moodle_cli/cli_validate_school_baseline.php" \
  "$PACK_ROOT/scripts/moodle_cli/cli_validate_course_template_csv.php"; do
  if [ -f "$required" ]; then
    ok "File exists: ${required#$PACK_ROOT/}"
  else
    fail "Missing file: ${required#$PACK_ROOT/}"
  fi
done

if [ -d "$PACK_ROOT/build/assembled_csv/$YEAR" ]; then
  ok "Assembled CSV exists: build/assembled_csv/$YEAR"
else
  warn "Assembled CSV missing; generate with: python3 scripts/assemble.py --year $YEAR"
fi

if [ -f "$PROJECT_ROOT/docker-compose.yml" ]; then
  ok "Project docker-compose.yml found"
  if command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
    container_id="$(cd "$PROJECT_ROOT" && docker compose ps -q "$MOODLE_SERVICE" 2>/dev/null || true)"
    if [ -n "$container_id" ]; then
      ok "Moodle service is running: $MOODLE_SERVICE"
    else
      warn "Moodle service is not running; start with: docker compose up -d"
    fi
  fi
else
  warn "Project docker-compose.yml not found; set PROJECT_ROOT=/path/to/repo for container import"
fi

echo
echo "Summary"
echo "-------"
echo "Failures: $failures"
echo "Warnings: $warnings"

if [ "$failures" -gt 0 ]; then
  exit 1
fi
