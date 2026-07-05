# Setup Runbook

1. Review `config/school_setup.yaml`.
2. Review master files under `master/`.
3. Review registration files under `registration/`.
4. Review year structure under `years/2026-2027/`.
5. Assemble Moodle-ready CSV files.

```bash
./scripts/assemble.py --year 2026-2027
```

6. Validate source and assembled output.

```bash
./scripts/validate.py --year 2026-2027
php -d memory_limit=512M scripts/moodle_cli/cli_validate_school_baseline.php --dir=build/assembled_csv/2026-2027
php -d memory_limit=512M scripts/moodle_cli/cli_validate_course_template_csv.php --dir=build/assembled_csv/2026-2027
```

7. Run Moodle dry-run.

```bash
./scripts/import.sh 2026-2027 dry-run
```

8. Take backup, then run live import.

```bash
./scripts/import.sh 2026-2027 live
```
