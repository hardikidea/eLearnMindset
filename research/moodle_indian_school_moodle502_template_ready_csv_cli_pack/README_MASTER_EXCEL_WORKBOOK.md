# Master Excel Workbook for School Data Import

This CLI pack includes a generated master Excel workbook for maintaining the Moodle Indian school import data in one place.

Workbook path:

```text
outputs/master-import-workbook/eLearnMindset_school_import_master.xlsx
```

Generator script:

```text
build_master_import_workbook.mjs
```

## Purpose

Use the workbook when a school administrator, data-entry team, or implementation partner wants one structured file for:

- School master data
- Academic years
- Boards, mediums, grades, streams, divisions, and subjects
- Category, course, cohort, group, user, parent, role, and enrolment CSVs
- Course template CSVs
- Academic-year promotion and rollover CSVs
- CSV dependency/reference checks
- Import sequence guidance

The workbook is an editing and review tool. The Moodle CLI scripts still import CSV files, so any edited workbook sheet must be exported back to the matching CSV filename before running the import.

## Workbook Structure

| Sheet | Purpose |
|---|---|
| `00_README` | Workbook usage summary and safe editing rules. |
| `01_IMPORT_SEQUENCE` | CSV import sequence, phase, purpose, dependency summary, row counts, and sheet mapping. |
| `02_REFERENCE_RULES` | Cross-sheet reference rule catalog and reusable formula patterns. |
| `03_VALIDATION_SUMMARY` | Generated invalid-reference counts from the current CSV data. |
| `04_REF_LISTS` | Dynamic reference list ranges used by dropdowns in CSV sheets. |
| `05_*` onward | One worksheet per CSV file, ordered by the recommended import sequence. |

CSV sheets keep the original CSV headers in row 1. Do not insert note rows above the header. Do not rename headers unless the CLI scripts and CSV documentation are updated.

## Dynamic Reference Rules

The workbook supports reference-driven data entry in two ways.

### 1. Dropdown lists

Common code columns use Excel data validation lists that point to `04_REF_LISTS`.

Examples:

| Field pattern | Reference list |
|---|---|
| `board_code` | Board codes from `boards.csv` |
| `medium_code` | Medium codes from `mediums.csv` |
| `grade_code` | Grade codes from `grades.csv` |
| `stream_code` | Stream codes from `streams.csv` |
| `division_code` | Division codes from `divisions.csv` |
| `subject_code` | Subject codes from `subjects.csv` |
| `category_code`, `parent_category_code`, `context_category_code` | Category codes from `categories.csv` |
| `course_code` | Course codes from `courses.csv` |
| `course_shortname` | Course shortnames from `courses.csv` |
| `cohort_code`, `cohort1` | Cohort codes from `cohorts.csv` |
| `role_shortname` | Role shortnames from `custom_roles.csv` |
| `template_code`, `course_template_code` | Template codes from `master_course_template.csv` |
| `academic_year` | Academic years from `academic_years.csv` |

The reference list cells point back to the source CSV sheets, so updating a source sheet updates the dropdown list range after workbook recalculation.

### 2. Reference-rule catalog

`02_REFERENCE_RULES` documents the dependency rules between sheets.

Examples:

| Target | Must exist in |
|---|---|
| `courses.csv.category_code` | `categories.csv.category_code` |
| `groups.csv.course_code` | `courses.csv.course_code` |
| `users_students.csv.cohort1` | `cohorts.csv.cohort_code` |
| `cohort_members.csv.username` | `users_students.csv.username` |
| `parent_links.csv.parent_username` | `users_parents.csv.username` |
| `enrolments.csv.cohort_code` | `cohorts.csv.cohort_code` |
| `course_template_activities.csv.template_code` | `master_course_template.csv.template_code` |
| `promotion_actions.csv.to_cohort_code` | `next_year_cohorts_2027_2028.csv.cohort_code` |

`03_VALIDATION_SUMMARY` is generated from the current CSV data at workbook build time. After large edits, export CSVs and run the CLI validators for the authoritative check.

## Safe Editing Workflow

1. Open `outputs/master-import-workbook/eLearnMindset_school_import_master.xlsx`.
2. Read `00_README`.
3. Update master data sheets first:

```text
school_master
academic_years
boards
mediums
grades
streams
divisions
subjects
grade_subject_matrix
```

4. Update structure sheets:

```text
categories
courses
cohorts
groups
```

5. Update user/access sheets:

```text
user_profile_fields
custom_roles
users_staff
users_students
users_parents
cohort_members
role_assignments
parent_links
enrolments
```

6. Update course-template and rollover sheets only after the baseline is stable.
7. Check `03_VALIDATION_SUMMARY`.
8. Export the edited sheet back to CSV using the original filename.
9. Run CLI validation and dry-run before real import.

## Exporting Edited Sheets Back to CSV

When exporting from Excel:

1. Open the workbook.
2. Select the sheet you edited.
3. Use `Save As` or `Export`.
4. Choose CSV.
5. Save using the exact original CSV filename, for example:

```text
courses.csv
users_students.csv
course_template_activities.csv
```

6. Replace the matching CSV file in this pack.
7. Repeat only for sheets you changed.

Do not export the control sheets (`00_README`, `01_IMPORT_SEQUENCE`, `02_REFERENCE_RULES`, `03_VALIDATION_SUMMARY`, `04_REF_LISTS`) as Moodle import CSV files.

## Validation Commands After Export

From the project root:

```bash
PACK_HOST="$PWD/research/moodle_indian_school_moodle502_template_ready_csv_cli_pack"

php "$PACK_HOST/cli_validate_school_baseline.php" --dir="$PACK_HOST"
php "$PACK_HOST/cli_validate_course_template_csv.php" --dir="$PACK_HOST"
```

For Docker Moodle dry-run:

```bash
PACK_HOST="$PWD/research/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
PACK_CONTAINER="/tmp/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
APP_CONTAINER="$(docker compose ps -q moodle)"

docker compose exec -u root -T moodle rm -rf "$PACK_CONTAINER"
docker cp "$PACK_HOST" "$APP_CONTAINER:$PACK_CONTAINER"

docker compose exec -T moodle php admin/cli/cli_import_indian_school_baseline.php \
  --dir="$PACK_CONTAINER" \
  --dry-run=1
```

## Regenerating the Workbook

Regenerate the workbook after CSV schema changes or major data updates.

This project currently uses the bundled Codex spreadsheet runtime to generate the workbook.

```bash
PACK="$PWD/research/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
NODE="/Users/hardik.chauhan/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node"
NODE_MODULES="/Users/hardik.chauhan/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules"

rm -f "$PACK/node_modules"
ln -s "$NODE_MODULES" "$PACK/node_modules"
"$NODE" "$PACK/build_master_import_workbook.mjs"
rm -f "$PACK/node_modules"
```

Output:

```text
outputs/master-import-workbook/eLearnMindset_school_import_master.xlsx
```

Do not commit or keep a `node_modules` directory or symlink in this pack.

## Practical Rules for School Data

- Keep identifiers stable after import: usernames, course shortnames, category codes, cohort codes, group idnumbers, and role shortnames.
- Avoid changing imported usernames after enrolments and parent links are created.
- Do not store full Aadhaar numbers in the workbook.
- Keep sensitive profile data limited to protected profile fields.
- Do not put parent phone numbers, medical details, addresses, or Aadhaar data into course content/template activity instructions.
- Always run dry-run before real import.

## Limitations

- Excel dropdowns help with data entry, but Moodle CLI validation remains the source of truth.
- `03_VALIDATION_SUMMARY` is generated at workbook build time. Regenerate the workbook or run CLI validators after major edits.
- If a school adds new CSV files to the pack, rerun `build_master_import_workbook.mjs` so the workbook includes the new sheet and import sequence mapping.
