# Moodle Indian School CLI Pack

This pack imports and maintains an Indian K-12 school structure in Moodle using ordered CSV files and Moodle CLI scripts.

It supports:

- Board, medium, grade, stream, subject, division, course, cohort, group, user, parent, and enrolment setup.
- Universal course templates with chapter sections, quizzes, assignments, completion tracking, and sequential access gates.
- Academic-year rollover without re-registering existing students.
- Selective imports for only one division, one teacher assignment, one batch of students, or one enrolment mapping.
- Master Excel workbook maintenance for non-technical school operations teams.

## Read First

| Chapter | Document | Use When |
|---:|---|---|
| 01 | [Overview and Architecture](docs/01-overview-and-architecture.md) | You need to understand how the Moodle school model works. |
| 02 | [Setup and Prerequisites](docs/02-setup-and-prerequisites.md) | You are preparing Docker/Moodle before import. |
| 03 | [Ordered CSV Data Model](docs/03-ordered-csv-data-model.md) | You are editing CSV files or checking dependencies. |
| 04 | [Full School Import Runbook](docs/04-full-school-import-runbook.md) | You are setting up a new school from the full pack. |
| 05 | [Selective Import Runbooks](docs/05-selective-import-runbooks.md) | You only need one division, users, parents, teachers, or enrolments. |
| 06 | [Course Template and Chapter Gates](docs/06-course-template-and-chapter-gates.md) | You need the 10-chapter standard course pattern. |
| 07 | [Academic Year Rollover](docs/07-academic-year-rollover.md) | Students move to the next grade/year without new accounts. |
| 08 | [Master Excel Workbook](docs/08-master-excel-workbook.md) | School staff prefer Excel-first data maintenance. |
| 09 | [Validation and Troubleshooting](docs/09-validation-and-troubleshooting.md) | You are fixing import, CSV, or Moodle CLI problems. |
| 10 | [Reference Index](docs/10-reference-index.md) | You need the deep reference docs, scripts, and file map. |

## Beginner Decision Table

| I Want To... | Start Here | Main Command or File |
|---|---|---|
| Understand the school structure | [Chapter 01](docs/01-overview-and-architecture.md) | Read the Moodle mapping table. |
| Prepare local Moodle | [Chapter 02](docs/02-setup-and-prerequisites.md) | `docker compose up -d` |
| Edit school data | [Chapter 03](docs/03-ordered-csv-data-model.md) | Start with `01_school_master.csv`. |
| Import a full school | [Chapter 04](docs/04-full-school-import-runbook.md) | `"$RUNNER" baseline-dry-run` |
| Import only new students | [Chapter 05](docs/05-selective-import-runbooks.md) | `new-selective-pack`, then `users-dry-run` |
| Create standard chapters | [Chapter 06](docs/06-course-template-and-chapter-gates.md) | `"$RUNNER" template-reset-import` |
| Promote students next year | [Chapter 07](docs/07-academic-year-rollover.md) | Edit `53_promotion_actions.csv`. |
| Work from Excel | [Chapter 08](docs/08-master-excel-workbook.md) | Export ordered CSV filenames. |
| Fix an error | [Chapter 09](docs/09-validation-and-troubleshooting.md) | `"$RUNNER" validate` |

## Fast Path

Run from the repository root:

```bash
cd /Users/hardik.chauhan/Documents/learning/eLearnMindset

PACK_HOST="$PWD/research/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
RUNNER="$PACK_HOST/run_school_setup_master.sh"

docker compose up -d
"$RUNNER" validate
"$RUNNER" preflight
"$RUNNER" baseline-dry-run
```

Execute the real baseline import only after dry-run output is reviewed:

```bash
"$RUNNER" baseline-import
"$RUNNER" template-reset-import
"$RUNNER" apply-template-import
"$RUNNER" gradebook-import
"$RUNNER" purge-cache
```

## Filename Standard

All editable CSV files are ordered:

```text
NN_<logical_file_name>.csv
```

Examples:

```text
01_school_master.csv
12_courses.csv
20_users_students.csv
53_promotion_actions.csv
```

CLI scripts can still resolve logical names internally, but repository edits and selective packs should use ordered filenames.

## Safety Rules

- Run `"$RUNNER" validate` before every import.
- Run dry-run before every real import.
- Back up the Moodle database before production imports or promotions.
- Do not store full Aadhaar numbers in Moodle CSVs.
- Keep usernames, course shortnames, category codes, cohort codes, and group idnumbers stable after import.
- Create new academic-year courses and cohorts for the next year; do not overwrite old-year courses when reports must be preserved.
