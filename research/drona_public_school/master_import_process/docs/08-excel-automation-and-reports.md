# Excel Automation and Reports

## Workbook Navigation

The workbook includes these operator helper sheets:

| Sheet | Purpose |
|---|---|
| `_dashboard` | High-level import counts for students, parents, staff, courses, cohorts, enrolments and certificates. |
| `_color_guide` | Color legend for mandatory, optional, school-specific, parent, student, staff and course fields. |
| `_sheet_index` | Full list of import sheets with required/optional status, target CSV path and tab color. |
| `_version` | Template version, generated date, supported row contract and compatible script names. |
| `_lookups` | Hidden lookup values used by dropdown validations. Do not edit manually. |

## Sheet Tab Colors

Excel supports sheet tab background colors. It does not provide a reliable cross-application foreground/text color setting for sheet tabs, so foreground text is controlled by Excel, Google Sheets or LibreOffice.

| Tab color | Meaning |
|---|---|
| Blue | System/helper sheet. |
| Orange | Required import sheet. |
| Grey | Optional import sheet. |
| Green | Generated or matrix-style sheet. |

## Dropdown Validations

Dropdowns are added for configured reference columns, including:

- `academic_year`
- `board_code`
- `medium_code`
- `grade_code`
- `stream_code`
- `division_code`
- `subject_code`
- `course_code`
- `course_shortname`
- `cohort_code`
- `group_idnumber`
- `role_shortname`
- `parent_username`
- `student_username`
- boolean fields such as `visible`, `enabled`, `is_current`

If a dropdown list is missing a value, update the related master sheet and regenerate the workbook template.

## Standard Prefilled Sheets

The generated blank template already includes reusable standard data for common school setup:

- board, medium, grade, stream, division and subject masters
- Moodle profile fields and custom roles
- validation and lookup references
- course master template
- course template sections, activities, gradebook categories and completion defaults
- certificate, report access, promotion, archive and compatibility policy rows
- default attendance policy and exam term rows

Do not delete these rows unless the school intentionally follows a different standard. School-specific rows are still entered separately in the school identity, users, courses, cohorts, groups, parent links, enrolments, certificate mappings and course assessment sheets.

## Conditional Error Highlighting

The workbook highlights likely mistakes:

| Highlight | Meaning |
|---|---|
| Red | Missing mandatory or required-if-used value on a row that has data. |
| Yellow | Suspicious format such as an invalid email-like value or academic year format. |

Conditional highlighting is a first-pass operator aid. The CLI validation remains the source of truth before import.

## Formula Helper Columns

Some sheets include helper columns to the right of the CSV columns. These helper columns are not exported because the CSV header row is intentionally blank in that helper area.

Examples:

- `helper_row_status`
- `helper_expected_course_code`
- `helper_expected_shortname`
- `helper_expected_cohort_code`
- `helper_expected_group_idnumber`
- `helper_expected_parent_idnumber`

Use helper values to compare against the actual CSV columns before running the import.

## Auto-Generated Matrix Data

The converter can auto-generate `09_subject_matrix` when the sheet has no real data rows.

Generation source:

- `03_boards`
- `04_mediums`
- `05_grades`
- `06_streams`
- `08_subjects`

Generated rows are written to:

```text
output/source_csv/years/<academic-year>/grade_subject_matrix.csv
```

If a school needs board-specific or grade-specific subject rules, fill `09_subject_matrix` manually. Manual rows always win; auto-generation only runs when the matrix sheet is blank.

## Relationship Report

The wrapper command generates:

```text
output/reports/relationship_validation_report.csv
```

This report checks:

- students without parent links
- parents without linked students
- parent links referencing missing users
- teachers without course assignments
- courses without teacher role assignments
- courses without cohort-sync enrolments
- cohorts without members
- enrolments referencing missing courses, cohorts or groups

## Import Preview

The wrapper command also generates:

```text
output/reports/import_preview.html
```

Open this file in a browser to review counts, warnings, errors and a course preview before running a live import.
