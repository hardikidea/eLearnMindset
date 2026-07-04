#!/usr/bin/env bash
set -euo pipefail

MOODLE_ROOT="/path/to/moodle"
CSV_DIR="/path/to/extracted/csv-pack"

cp cli_moodle502_preflight.php "$MOODLE_ROOT/admin/cli/"
cp cli_import_indian_school_baseline.php "$MOODLE_ROOT/admin/cli/"
cp cli_promote_academic_year.php "$MOODLE_ROOT/admin/cli/"

php "$MOODLE_ROOT/admin/cli/cli_moodle502_preflight.php"
php cli_validate_school_baseline.php --dir="$CSV_DIR"
php "$MOODLE_ROOT/admin/cli/cli_import_indian_school_baseline.php" --dir="$CSV_DIR" --dry-run=1
