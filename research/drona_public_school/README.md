# School Master Pack

This pack provides a complete Moodle school setup flow using structured CSV files, a master workbook process, validators, and Moodle CLI import scripts.

The included sample data represents Drona Public School for local testing. Tooling, workbook artifacts, and temporary import paths use the reusable `school_master_pack` name.

## Start Here

Read the maintained developer guide:

```text
docs/developer-guide.md
```

## Quick Commands

Run from this folder:

```bash
cd /Users/hardik.chauhan/Documents/learning/eLearnMindset/research/drona_public_school
```

Assemble and validate the current year:

```bash
python3 scripts/assemble.py --year 2026-2027
python3 scripts/validate.py --year 2026-2027
```

Validate the Moodle-ready CSVs:

```bash
scripts/import.sh 2026-2027 validate-only
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
