#!/usr/bin/env bash
set -euo pipefail

MOODLE_DIR="/var/www/html/moodle"
CSV_DIR="$(cd "$(dirname "$0")" && pwd)"

# Copy importer to Moodle CLI folder first:
# cp "$CSV_DIR/cli_import_indian_school_baseline.php" "$MOODLE_DIR/admin/cli/"

php "$MOODLE_DIR/admin/cli/cli_import_indian_school_baseline.php" --dir="$CSV_DIR" --dry-run=1

# After dry-run review:
# php "$MOODLE_DIR/admin/cli/cli_import_indian_school_baseline.php" --dir="$CSV_DIR" --dry-run=0
