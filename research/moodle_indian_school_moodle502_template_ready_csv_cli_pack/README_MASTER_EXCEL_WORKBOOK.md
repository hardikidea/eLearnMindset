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
The matching CSV filename is the ordered repository filename, for example
`12_courses.csv`, not the old unprefixed logical name.

## Workbook Structure

| Sheet | Purpose |
|---|---|
| `00_README` | Workbook usage summary and safe editing rules. |
| `01_IMPORT_SEQUENCE` | CSV import sequence, phase, purpose, dependency summary, row counts, and sheet mapping. |
| `02_REFERENCE_RULES` | Cross-sheet reference rule catalog and reusable formula patterns. |
| `03_VALIDATION_SUMMARY` | Generated invalid-reference counts from the current CSV data. |
| `04_REF_LISTS` | Dynamic reference list ranges used by dropdowns in CSV sheets. |
| `05_*` onward | One worksheet per CSV file, ordered by the recommended import sequence. |

CSV sheets keep the original CSV headers in row 1. Do not insert note rows above the header. Do not rename headers unless the CLI scripts and CSV documentation are updated. The `01_IMPORT_SEQUENCE` sheet shows both the ordered CSV filename and the logical script filename.

## Dynamic Reference Rules

The workbook supports reference-driven data entry in two ways.

### 1. Dropdown lists

Common code columns use Excel data validation lists that point to `04_REF_LISTS`.

Examples:

| Field pattern | Reference list |
|---|---|
| `board_code` | Board codes from `03_boards.csv` |
| `medium_code` | Medium codes from `04_mediums.csv` |
| `grade_code` | Grade codes from `05_grades.csv` |
| `stream_code` | Stream codes from `06_streams.csv` |
| `division_code` | Division codes from `07_divisions.csv` |
| `subject_code` | Subject codes from `08_subjects.csv` |
| `category_code`, `parent_category_code`, `context_category_code` | Category codes from `10_categories.csv` |
| `course_code` | Course codes from `12_courses.csv` |
| `course_shortname` | Course shortnames from `12_courses.csv` |
| `cohort_code`, `cohort1` | Cohort codes from `14_cohorts.csv` |
| `role_shortname` | Role shortnames from `17_custom_roles.csv` |
| `template_code`, `course_template_code` | Template codes from `30_master_course_template.csv` |
| `academic_year` | Academic years from `02_academic_years.csv` |

The reference list cells point back to the source CSV sheets, so updating a source sheet updates the dropdown list range after workbook recalculation.

### 2. Reference-rule catalog

`02_REFERENCE_RULES` documents the dependency rules between sheets.

Examples:

| Target | Must exist in |
|---|---|
| `12_courses.csv.category_code` | `10_categories.csv.category_code` |
| `15_groups.csv.course_code` | `12_courses.csv.course_code` |
| `20_users_students.csv.cohort1` | `14_cohorts.csv.cohort_code` |
| `22_cohort_members.csv.username` | `20_users_students.csv.username` |
| `24_parent_links.csv.parent_username` | `21_users_parents.csv.username` |
| `25_enrolments.csv.cohort_code` | `14_cohorts.csv.cohort_code` |
| `32_course_template_activities.csv.template_code` | `30_master_course_template.csv.template_code` |
| `53_promotion_actions.csv.to_cohort_code` | `55_next_year_cohorts_2027_2028.csv.cohort_code` |

`03_VALIDATION_SUMMARY` is generated from the current CSV data at workbook build time. After large edits, export CSVs and run the CLI validators for the authoritative check.

## Safe Editing Workflow

1. Open `outputs/master-import-workbook/eLearnMindset_school_import_master.xlsx`.
2. Read `00_README`.
3. Update master data sheets first:

```text
01_school_master.csv
02_academic_years.csv
03_boards.csv
04_mediums.csv
05_grades.csv
06_streams.csv
07_divisions.csv
08_subjects.csv
09_grade_subject_matrix.csv
```

4. Update structure sheets:

```text
10_categories.csv
12_courses.csv
14_cohorts.csv
15_groups.csv
```

5. Update user/access sheets:

```text
16_user_profile_fields.csv
17_custom_roles.csv
19_users_staff.csv
20_users_students.csv
21_users_parents.csv
22_cohort_members.csv
23_role_assignments.csv
24_parent_links.csv
25_enrolments.csv
```

6. Update course-template and rollover sheets only after the baseline is stable.
7. Check `03_VALIDATION_SUMMARY`.
8. Export the edited sheet back to CSV using the ordered filename shown in `01_IMPORT_SEQUENCE`.
9. Run CLI validation and dry-run before real import.

## Exporting Edited Sheets Back to CSV

When exporting from Excel:

1. Open the workbook.
2. Select the sheet you edited.
3. Use `Save As` or `Export`.
4. Choose CSV.
5. Save using the exact ordered CSV filename, for example:

```text
12_courses.csv
20_users_students.csv
32_course_template_activities.csv
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
