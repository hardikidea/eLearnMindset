#!/usr/bin/env bash
set -euo pipefail
MOODLE_ROOT=${MOODLE_ROOT:-/path/to/moodle}
CSV_DIR=${CSV_DIR:-/path/to/extracted/csv-pack}
php "$MOODLE_ROOT/admin/cli/cfg.php" --name=enablecompletion --set=1
php "$MOODLE_ROOT/admin/cli/cfg.php" --name=enableavailability --set=1
php "$MOODLE_ROOT/admin/cli/cli_create_universal_master_course_template.php" --dir="$CSV_DIR" --dry-run=1
php "$MOODLE_ROOT/admin/cli/cli_create_universal_master_course_template.php" --dir="$CSV_DIR" --dry-run=0 --activity-mode=native
# To replace old activities in an existing hidden master template, run:
# php "$MOODLE_ROOT/admin/cli/cli_create_universal_master_course_template.php" --dir="$CSV_DIR" --dry-run=0 --activity-mode=native --reset-template-activities=1
