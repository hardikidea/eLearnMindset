#!/usr/bin/env bash
set -euo pipefail
MOODLE_ROOT=${MOODLE_ROOT:-/path/to/moodle}
CSV_DIR=${CSV_DIR:-/path/to/extracted/csv-pack}
php "$MOODLE_ROOT/admin/cli/cli_apply_course_template_settings.php" --dir="$CSV_DIR" --dry-run=1 --limit=10
php "$MOODLE_ROOT/admin/cli/cli_apply_course_template_settings.php" --dir="$CSV_DIR" --dry-run=0 --limit=0
php "$MOODLE_ROOT/admin/cli/cli_apply_gradebook_template.php" --dir="$CSV_DIR" --dry-run=1 --limit=10
