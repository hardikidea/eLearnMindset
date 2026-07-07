# Master Excel Workbook

The workbook is the single school-facing input file.

## Required Layout

Every data sheet uses the same layout:

| Row | Required | Description |
|---:|---:|---|
| 1 | Yes | Template metadata row containing the target CSV path, guide row, summary row, pattern row, header row and example row. |
| 2 | Yes | Column guide row. This shows requirement and owner/use, for example `Mandatory | Parent/family data`. |
| 3 | Yes | Column usage summary row. This tells operators what each column changes in Moodle. |
| 4 | Yes | ID-number formula and reference-condition row. This uses reusable tokens instead of only hardcoded values. |
| 5 | Yes | CSV header row. These names become the generated CSV header. |
| 6 | No | Concrete example row with filled ID-number values. The converter skips it when row 1 includes `example_row=6`. |
| 7+ | No | Real data rows. Blank rows are ignored. |

Example first rows:

```text
template_csv_file | years/{year}/cohorts.csv | ordered_csv_file | 14_cohorts.csv | purpose | Student class/division cohorts | guide_row | 2 | summary_row | 3 | pattern_row | 4 | header_row | 5 | example_row | 6
Mandatory | Academic/course setup | Mandatory | Academic/course setup | Mandatory | Academic/course setup | Mandatory | Academic/course setup | ...
Stable source...  | Display name...           | Moodle idnumber... | Category code... | ...
<SCHOOL_CODE>-<START_YEAR>-<BOARD_CODE>-<MEDIUM_CODE>-<GRADE_CODE>-<STREAM_CODE>-<DIVISION_CODE> | Free text... | Same cohort formula... | Must match 10_categories.category_code | ...
cohort_code       | name                       | idnumber          | context_category_code | ...
DPS-2026-GSEB-GUJ-STD01-GEN-A | Drona Public School - Standard 1 - A | DPS-2026-GSEB-GUJ-STD01-GEN-A | DRONA_TRUST_GSEB_DPS_2026_2027_GUJ_STD01_GEN | ...
```

The formula row should stay generic. Use tokens such as `<SCHOOL_CODE>`, `<START_YEAR>`, `<BOARD_CODE>`, `<MEDIUM_CODE>`, `<GRADE_CODE>`, `<STREAM_CODE>`, `<DIVISION_CODE>` and `<SUBJECT_CODE>`. The example row below it can show real values for the selected school.

## Manifest Sheet

The `_manifest` sheet lists:

```text
sheet_name
source_csv
ordered_csv
required
purpose
```

The converter uses the internal script mapping, and the manifest exists so operators can understand where each sheet goes.

## Color Guide

The workbook includes a `_color_guide` sheet. Use it as the authoritative legend.

The bottom sheet tabs are also color-coded:

| Tab color | Meaning |
|---|---|
| Blue | System/helper sheet such as `_dashboard`, `_color_guide`, `_version`, `_sheet_index` or hidden `_lookups`. |
| Orange | Required import sheet. |
| Grey | Optional import sheet. |
| Green | Generated or matrix-style sheet. |

Excel controls sheet-tab foreground text automatically; the template controls the tab background color only.

Header row colors show requirement:

| Requirement | Meaning |
|---|---|
| `Mandatory` | Must be filled for import or reliable Moodle setup. |
| `Required if used` | Required only when the optional sheet or feature is used. |
| `Optional` | Useful metadata or optional Moodle setting. |

Row 2 colors show owner/use:

| Owner/use | Meaning |
|---|---|
| `School-specific setup` | School identity, board, medium, grade, stream, division and subject setup. |
| `Parent/family data` | Parent login, guardian relationship and parent-student links. |
| `Student registration` | Student accounts, cohorts and class/division placement. |
| `Staff registration` | Teacher, principal, trustee and staff accounts. |
| `Academic/course setup` | Courses, cohorts, groups, enrolments, exams, certificates and templates. |
| `Moodle/system reference` | Moodle roles, contexts, template references and operational metadata. |

## Workbook Helpers

The workbook includes:

- `_dashboard` for import counts.
- `_version` for template version and row contract.
- `_sheet_index` for required/optional sheet status.
- hidden `_lookups` for dropdown validation values.
- formula helper columns to the right of some source sheets. These helper columns are not exported to CSV.

## Standard Prefilled Data

The blank template includes real standard rows starting at row 7 for reusable setup sheets. Operators do not need to recreate these values for every school.

Prefilled master/setup sheets:

- `02_academic_years`
- `03_boards`
- `04_mediums`
- `05_grades`
- `06_streams`
- `07_divisions`
- `08_subjects`
- `16_profile_fields`
- `17_custom_roles`
- `18_role_guidelines`
- `26_lookup_values`
- `27_validation_rules`
- `28_source_refs`
- `29_summary`

Prefilled course-template and policy sheets:

- `30_master_template`
- `31_template_sections`
- `32_template_activities`
- `33_template_gradebook`
- `34_grade_band_adjust`
- `35_subject_adjust`
- `36_completion_defaults`
- `38_template_custom_fields`
- `39_template_review`
- `40_certificate_policy`
- `41_report_access`
- `42_test_coverage`
- `44_transition_models`
- `45_promotion_rules`
- `46_rollover_checklist`
- `47_promotion_policy`
- `48_promotion_status`
- `49_promotion_validation`
- `50_student_status`
- `58_alumni_cohorts`
- `59_archive_policy`
- `60_improvement_backlog`
- `61_compatibility`
- `attendance_policy`
- `exam_terms`

School-specific sheets remain blank except for row 6 examples. Fill these per school: `01_school_master`, `10_categories`, `12_courses`, `14_cohorts`, `15_groups`, user registration sheets, parent links, enrolments, course certificates and course-specific assessment/gradebook rows.

## Workbook Files

| File | Purpose |
|---|---|
| `templates/school_master_import_template.xlsx` | Blank workbook with metadata rows and headers. |
| `templates/sample_minimal_school_import.xlsx` | Small structure-review workbook generated from existing pack rows. It is not intended as a complete live import dataset. |
| `input/school_master_import.xlsx` | Filled workbook used by the wrapper command. |

## Editing Rules

- Keep sheet names unchanged.
- Keep row 1 unchanged unless the CSV target path or row mapping intentionally changes.
- Keep row 2 as requirement and owner/use guidance.
- Keep row 3 as short column usage guidance.
- Keep row 4 as formula/reference guidance.
- Keep row 5 header names unchanged.
- Use row 6 as the concrete example row.
- Add real import data starting at row 7.
- Use configured codes consistently: school code, board code, medium code, grade code, stream code, division code and subject code.
