# School Master Pack

This pack provides a complete Moodle school setup flow using structured CSV files, a master workbook process, validators, and Moodle CLI import scripts.

The included sample data represents Drona Public School for local testing. Tooling, workbook artifacts, and temporary import paths use the reusable `school_master_pack` name.

## Start Here

For day-to-day use, follow this order:

1. Edit source data in `master/`, `registration/`, `templates/`, `operations/`, or `years/<year>/`.
2. Run `scripts/doctor.sh <year>` to check local tools and project readiness.
3. Run `scripts/validate_all.sh <year>` to assemble, validate, and generate the preflight report.
4. Run `scripts/import.sh <year> validate-only` if you want the Moodle PHP validators only.
5. Run `scripts/import.sh <year> dry-run`.
6. Run `scripts/import.sh <year> live` only after backup, preflight, and dry-run pass.

The full reference is maintained in [docs/developer-guide.md](docs/developer-guide.md).

Google Form based student/parent registration is documented in [docs/google-form-registration-guide.md](docs/google-form-registration-guide.md).

Google Form based teacher registration is documented in [docs/google-form-teacher-registration-guide.md](docs/google-form-teacher-registration-guide.md).

## Quick Commands

Run from the repository root:

```bash
cd research/drona_public_school
```

Assemble and validate the current year:

```bash
scripts/doctor.sh 2026-2027
scripts/validate_all.sh 2026-2027
```

Validate the Moodle-ready CSVs:

```bash
scripts/import.sh 2026-2027 validate-only
```

Generate only the preflight report and import manifest:

```bash
scripts/preflight_report.sh 2026-2027
```

Preflight output:

```text
build/reports/2026-2027/preflight_report.md
build/reports/2026-2027/import_manifest.json
```

Dry-run against the local Moodle container:

```bash
scripts/import.sh 2026-2027 dry-run
```

Live import after backup and dry-run pass:

```bash
scripts/import.sh 2026-2027 live
```

## Master Workbook

The workbook is an optional operator-friendly entry point. Start from its `status` sheet; it tells you which sheets are manual, which sheets are generated, and what needs attention.

Generate or refresh workbook templates:

```bash
python3 master_import_process/scripts/create_master_workbooks.py --year 2026-2027
```

Run the workbook-driven import pipeline:

```bash
master_import_process/scripts/run_master_import.sh 2026-2027 validate-only
master_import_process/scripts/run_master_import.sh 2026-2027 dry-run
```

Validate the maintained master workbook artifacts:

```bash
python3 master_import_process/scripts/validate_master_workbook.py
```

When reviewing a workbook, open the `status` sheet first. It is the health dashboard for source files, row counts, generated-sheet checks, and macro actions.

## Edit The Right Source

| Need | Edit here |
|---|---|
| School identity, board, medium, grades, streams, subjects | `master/*.csv` |
| Grade-specific division allocation | `master/grade_division_rules.csv` or workbook sheet `07_grade_division_rules` |
| Students, parents, staff, parent links | `registration/` |
| Course template, sections, activities, certificates, exams | `templates/` |
| Year-specific courses, cohorts, groups, enrolments, promotion plans | `years/<academic-year>/` |
| Workbook process or import reports | `master_import_process/` |
| Moodle import behavior | `scripts/moodle_cli/` |

Do not hand-edit `build/assembled_csv/<year>/`; regenerate it from source data.

`master/divisions.csv` is only the list of possible division labels. Actual class sections such as Standard 1 using `A|B|C|D` and Standard 3 using only `A` are controlled by `master/grade_division_rules.csv`. The generated `years/<year>/grade_division_matrix.csv` then drives cohorts, Moodle groups, and cohort-sync enrolments.

## Maintained Workbook Artifacts

```text
build/master_excel/school_master_pack_2026_2027_sample_master.xlsx
build/master_excel/school_master_pack_2026_2027_full_predefined_master.xlsx
build/master_excel/school_master_pack_2026_2027_full_predefined_master_macros.ods
```

## Default Local Test Passwords

- Students: `DronaStudent2026!`
- Parents: `DronaParent2026!`
- Teachers: `DronaTeacher2026!`
- Principal: `DronaPrincipal2026!`
- Trustee: `DronaTrustee2026!`
- IT Coordinator: `DronaIT2026!`

## Production Warning

The included data is a high-volume local-testing dataset. Before production import, verify official subjects, board rules, student data, parent consent, and certificate policy with the school.
