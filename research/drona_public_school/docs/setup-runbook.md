# Setup Runbook

1. Review `config/school_setup.yaml`.
2. Review `docs/data-model-reference.md` for matrix formulas and ID number templates.
3. Review master files under `master/`.
4. Review registration files under `registration/`.
5. Review year structure under `years/2026-2027/`.
6. Confirm `mod_customcert` is installed in Moodle if certificates will be imported.

```bash
docker compose exec -T moodle php admin/cli/upgrade.php --non-interactive
docker compose exec -T moodle php admin/cli/purge_caches.php
```

7. Assemble Moodle-ready CSV files.

```bash
./scripts/assemble.py --year 2026-2027
```

8. Validate source and assembled output.

```bash
./scripts/validate.py --year 2026-2027
php -d memory_limit=512M scripts/moodle_cli/cli_validate_school_baseline.php --dir=build/assembled_csv/2026-2027
php -d memory_limit=512M scripts/moodle_cli/cli_validate_course_template_csv.php --dir=build/assembled_csv/2026-2027
```

9. Run Moodle dry-run.

```bash
./scripts/import.sh 2026-2027 dry-run
```

10. Take backup, then run live import.

```bash
./scripts/import.sh 2026-2027 live
```

The live import creates/updates users, categories, courses, cohorts, groups, enrolments, course sections, gradebook categories and one verified PDF certificate activity for every course.
