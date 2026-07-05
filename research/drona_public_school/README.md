# Drona Public School Moodle Structured Pack

This pack prepares a full local-testing school setup for **Drona Public School** using Gujarat Board-style school data.

## Generated Scope

- Academic years: 2026-2027, 2027-2028, 2028-2029, 2029-2030
- Mediums: Gujarati, English, Hindi
- Grades: Standard 1 to Standard 12
- Divisions: A to F
- Higher secondary streams: Science, Commerce, Arts
- Students: 5220 generated local-testing users
- Parents: 4698 generated local-testing parent users
- Parent/student links: 5220
- Staff: 69 including trustee, principal, IT and subject teachers
- Courses per academic year: 360
- Cohorts per academic year: 288
- Groups per academic year: 2160
- Verified PDF course certificates per academic year: 360

## Important Model

Users are registered once under `registration/`. Academic years do not recreate students, parents or teachers. Every year changes courses, cohorts, groups, enrolments, role assignments, academic history and promotion plans.

Course certificates use the Moodle `mod_customcert` plugin. The importer creates one verified PDF certificate activity in the final `Certificate & Completion` section of every course.

## Quick Start

```bash
cd /Users/hardik.chauhan/Documents/learning/eLearnMindset/research/drona_public_school
./scripts/assemble.py --year 2026-2027
./scripts/validate.py --year 2026-2027
```

Moodle dry-run import:

```bash
./scripts/import.sh 2026-2027 dry-run
```

Live import after backup and dry-run pass:

```bash
./scripts/import.sh 2026-2027 live
```

The live import applies core users/courses, section names, gradebook templates and course certificates. If the Custom Certificate plugin is missing, the certificate step fails before silently creating incomplete credentials.

## Master Excel

A capped sample workbook is available at:

```text
build/master_excel/drona_public_school_2026_2027_sample_master.xlsx
```

The full local-testing dataset stays in CSV form under `build/assembled_csv/` because the generated import set is intentionally large.

For future academic years, users are reused. Import future structure with `--skip-users` automatically through `scripts/import.sh`.

## Default Test Passwords

- Students: `DronaStudent2026!`
- Parents: `DronaParent2026!`
- Teachers: `DronaTeacher2026!`
- Principal: `DronaPrincipal2026!`
- Trustee: `DronaTrustee2026!`
- IT Coordinator: `DronaIT2026!`

## Production Warning

This is a high-volume local-testing dataset. Before production, the school must verify official subjects, board rules, student data, parent consent, and certificate policy. Aadhaar fields are intentionally blank.
