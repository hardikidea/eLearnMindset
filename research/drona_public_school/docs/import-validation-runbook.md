# Import and Validation Runbook

Always run:

```bash
./scripts/assemble.py --year 2026-2027
./scripts/validate.py --year 2026-2027
php -d memory_limit=512M scripts/moodle_cli/cli_validate_school_baseline.php --dir=build/assembled_csv/2026-2027
php -d memory_limit=512M scripts/moodle_cli/cli_validate_course_template_csv.php --dir=build/assembled_csv/2026-2027
```

Then dry-run Moodle import:

```bash
./scripts/import.sh 2026-2027 dry-run
```

The dry-run also validates that `mod_customcert` is installed before certificate activities are created.

For live import, take DB and moodledata backup first:

```bash
./scripts/import.sh 2026-2027 live
```

After live import, sample-check one course:

1. Open the course.
2. Confirm section `Certificate & Completion` exists.
3. Confirm `Download Course Completion Certificate` exists.
4. Open the activity as a student who has completed the required gate activity.
5. Confirm the PDF downloads and contains student name, course name, issue date, grade, certificate ID and QR code.
