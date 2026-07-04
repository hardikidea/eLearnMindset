#!/usr/bin/env bash
set -euo pipefail
CSV_DIR=${CSV_DIR:-/path/to/extracted/csv-pack}
php cli_validate_course_template_csv.php --dir="$CSV_DIR"
