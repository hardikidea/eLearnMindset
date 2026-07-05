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

For live import, take DB and moodledata backup first.
