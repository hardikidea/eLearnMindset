#!/bin/sh
# Example usage. Run from Moodle root or adjust script paths.
CSV_DIR="/path/to/extracted/csv-pack"

php admin/cli/cli_prepare_next_academic_year.php \
  --dir="$CSV_DIR" \
  --dry-run=1

php admin/cli/cli_promote_students_academic_year.php \
  --dir="$CSV_DIR" \
  --plan=student_promotion_plan_2027_2028.csv \
  --dry-run=1

# After verification and backup:
# php admin/cli/cli_prepare_next_academic_year.php --dir="$CSV_DIR" --dry-run=0
# php admin/cli/cli_promote_students_academic_year.php --dir="$CSV_DIR" --plan=student_promotion_plan_2027_2028.csv --dry-run=0
