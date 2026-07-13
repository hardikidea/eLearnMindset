# School Master Pack Developer Guide

This guide is the maintained documentation for the Moodle school master-pack CLI and workbook flow.

The current sample dataset represents Drona Public School. Keep `DPS`, `Drona Public School`, generated users, and school-specific CSV values when working with that sample data. Use `school_master_pack` for reusable tooling, workbook artifacts, temporary paths, and documentation labels.

## Chapter 1: What This Pack Builds

The pack builds a complete Moodle school setup from structured CSV files and an optional master workbook.

It covers:

- School, trust, board, medium, grade, stream, division, and subject master data.
- Student, parent, teacher, principal, trustee, and IT users.
- Parent/student relationships.
- Moodle course categories, courses, cohorts, groups, cohort membership, enrolments, and role assignments.
- A reusable master course template with sections, activities, completion defaults, gradebook weights, exams, certificates, and chapter-gated learning flow.
- Academic-year rollover data for future years without recreating users.
- Validation scripts and Moodle CLI import scripts.
- Google Form registration intake for student, parent, teacher, and school-office registration workflows.

Current generated sample scope:

| Area | Count |
|---|---:|
| Academic years | 4 |
| Students | 5,220 |
| Parents | 4,698 |
| Staff users | 69 |
| Courses per academic year | 360 |
| Cohorts per academic year | 288 |
| Groups per academic year | 2,160 |
| Enrolments per academic year | 2,160 |
| Course certificates per academic year | 360 |

## Chapter 2: Directory Map

Run commands from the repository root:

```bash
cd research/drona_public_school
```

Important folders:

| Path | Purpose | Edit by hand? |
|---|---|---|
| `master/` | Core school reference data such as school, boards, mediums, grades, streams, divisions, subjects, roles, profile fields, lookup values. | Yes, for source data. |
| `registration/` | One-time user registration data for staff, students, parents, parent links, and supporting student records. | Yes, for source data. |
| `templates/` | Course, exam, certificate, and legacy Moodle template CSVs. | Yes, carefully. |
| `operations/` | Validation, promotion, rollover, archive, source-reference, and policy CSVs. | Yes, for policy/reference changes. |
| `years/<year>/` | Year-scoped courses, cohorts, groups, enrolments, academic history, promotion plans, exams, certificates, attendance, and gradebook weights. | Yes, or generate with scripts. |
| `scripts/` | Pack assembly, validation, year generation, and Moodle import wrappers. | Yes, for developer changes. |
| `scripts/moodle_cli/` | PHP scripts copied into Moodle `admin/cli` during import. | Yes, for Moodle import behavior changes. |
| `master_import_process/` | Excel-to-CSV process, workbook generation, workbook validation, macro workbook tooling, and import reports. | Yes, for workbook process changes. |
| `build/assembled_csv/<year>/` | Moodle-ready ordered CSV output. | No. Regenerate. |
| `build/master_excel/` | Review workbooks and macro workbook artifacts. | Regenerate when source data or macro tooling changes. |

Generated/cache files are not source of truth. Do not manually edit `build/assembled_csv/`, Python `__pycache__/`, `.DS_Store`, or generated zip files.

## Chapter 3: Naming Rules

Use these naming boundaries:

| Context | Naming rule |
|---|---|
| Tooling, workbook artifacts, temporary container paths, developer docs | Use `school_master_pack`. |
| Actual sample school records | Keep the school-provided values, currently `DPS`, `DRONA_TRUST`, and `Drona Public School`. |
| Moodle course/category/cohort/group IDs | Generate from CSV source values; do not manually invent IDs. |
| Academic year folders | Use `YYYY-YYYY`, for example `2026-2027`. |

Primary ID formulas:

| Object | Formula |
|---|---|
| Course code/idnumber | `<SCHOOL_CODE>-<BOARD_CODE>-<MEDIUM_CODE>-<GRADE_CODE>-<STREAM_CODE>-<SUBJECT_CODE>-<START_YEAR>` |
| Course shortname | `<SCHOOL_CODE>-<BOARD_CODE>-<MEDIUM_CODE>-<GRADE_CODE>-<STREAM_CODE>-<SUBJECT_CODE>-<YY>` |
| Category code | `<TRUST_CODE>_<BOARD_CODE>_<SCHOOL_CODE>_<YYYY_YYYY>_<MEDIUM_CODE>_<GRADE_CODE>_<STREAM_CODE>` |
| Cohort code/idnumber | `<SCHOOL_CODE>-<START_YEAR>-<BOARD_CODE>-<MEDIUM_CODE>-<GRADE_CODE>-<STREAM_CODE>-<DIVISION_CODE>` |
| Group idnumber | `<COURSE_CODE>-<DIVISION_CODE>` |
| Student username | `<school_code_lower>.stu.<00001>` |
| Parent username | `<school_code_lower>.par.<00001>` |
| Teacher username | `<school_code_lower>.tch.<medium_or_scope>.<subject>` |
| Student admission number | `<SCHOOL_CODE><YY>-<00001>` |
| Parent idnumber | `<SCHOOL_CODE>-PAR-<00001>` |
| Teacher idnumber | `<SCHOOL_CODE>-TCH-<###>` |

## Chapter 4: Prerequisites

Local developer machine:

- Python 3 with `openpyxl`.
- LibreOffice if you need ODS macro workbook generation or smoke validation with conversion.
- Docker Compose running Moodle for dry-run/live import.
- Moodle source available under the repository `moodle/` folder.
- PostgreSQL and Moodle services started through the project Docker setup.

Install workbook dependencies:

```bash
python3 -m pip install -r master_import_process/requirements.txt
```

If system Python does not include `openpyxl`, use a local virtualenv:

```bash
python3 -m venv .venv
. .venv/bin/activate
python3 -m pip install -r master_import_process/requirements.txt
```

## Chapter 5: CSV Source Model

The source model has two layers:

1. Stable master/registration data: users and reference data that should not be recreated every academic year.
2. Academic-year data: courses, cohorts, groups, enrolments, exams, certificates, history, and promotion data that changes per year.

Users are registered once:

- Staff: `registration/combined/19_users_staff.csv`
- Students: `registration/combined/20_users_students.csv`
- Parents: `registration/combined/21_users_parents.csv`
- Parent links: `registration/parent_links.csv`

Academic year folders reuse those users:

```text
years/2026-2027/
years/2027-2028/
years/2028-2029/
years/2029-2030/
```

Future-year imports use the same users and skip user recreation.

## Chapter 6: Ordered CSV Sequence

`scripts/assemble.py` creates Moodle-ready ordered CSV files under:

```text
build/assembled_csv/<academic-year>/
```

Core ordered files:

| Order | File | Source |
|---:|---|---|
| 01 | `01_school_master.csv` | `master/school.csv` |
| 02 | `02_academic_years.csv` | `master/academic_years.csv` |
| 03 | `03_boards.csv` | `master/boards.csv` |
| 04 | `04_mediums.csv` | `master/mediums.csv` |
| 05 | `05_grades.csv` | `master/grades.csv` |
| 06 | `06_streams.csv` | `master/streams.csv` |
| 07 | `07_divisions.csv` | `master/divisions.csv` |
| 08 | `08_subjects.csv` | `master/subjects.csv` |
| 09 | `09_grade_subject_matrix.csv` | `years/<year>/grade_subject_matrix.csv` |
| 10 | `10_categories.csv` | `years/<year>/categories.csv` |
| 11 | `11_optional_year_category_model_categories.csv` | `years/<year>/categories.csv` |
| 12 | `12_courses.csv` | `years/<year>/courses.csv` |
| 13 | `13_courses_with_templatecourse_for_moodle_upload.csv` | `years/<year>/courses_with_templatecourse_for_moodle_upload.csv` |
| 14 | `14_cohorts.csv` | `years/<year>/cohorts.csv` |
| 15 | `15_groups.csv` | `years/<year>/groups.csv` |
| 16 | `16_user_profile_fields.csv` | `master/profile_fields.csv` |
| 17 | `17_custom_roles.csv` | `master/roles.csv` |
| 18 | `18_role_guidelines.csv` | `operations/role_guidelines.csv` |
| 19 | `19_users_staff.csv` | `registration/combined/19_users_staff.csv` |
| 20 | `20_users_students.csv` | `registration/combined/20_users_students.csv` |
| 21 | `21_users_parents.csv` | `registration/combined/21_users_parents.csv` |
| 22 | `22_cohort_members.csv` | `years/<year>/cohort_members.csv` |
| 23 | `23_role_assignments.csv` | `years/<year>/role_assignments.csv` |
| 24 | `24_parent_links.csv` | `registration/parent_links.csv` |
| 25 | `25_enrolments.csv` | `years/<year>/enrolments.csv` |
| 26-29 | Lookup, validation, source, summary files | `master/` and `operations/` |
| 30-42 | Course template and template QA files | `templates/legacy/` and `years/<year>/course_template_application.csv` |
| 43-61 | Content, rollover, promotion, next-year, archive, compatibility files | `years/` and `operations/` |

Extra Moodle/course automation files copied to the assembled folder:

- `course_certificates.csv`
- `course_term_exams.csv`
- `course_final_exams.csv`
- `assessment_plan.csv`
- `exam_terms.csv`
- `gradebook_weights.csv`
- `attendance_policy.csv`

Stream `applies_to` rule:

- Use `|` as the delimiter for multiple scope tokens.
- Use ranges for grade bands, for example `STD01-STD10`.
- Use grade+stream tokens for higher-secondary stream specificity, for example `STD11_SCI|STD12_SCI`.
- Use `ALL` only when the stream should apply to every configured grade.
- Keep platform/program labels such as `SWAYAM`, `NPTEL`, `UG`, `PG` or `NEET Prep` in `notes` unless they are also modelled as grade codes.
- Comma-separated values are rejected by validation for new data.

Subject matrix `applies_to` rule:

- Use `08_subjects.applies_to` for machine-readable subject scope.
- Keep plain human descriptions in `08_subjects.notes`.
- Supported scope examples are `PRE01-STD10`, `STD11_SCI|STD12_SCI`, `ITI`, `POLY`, `UNI_UG`, `NUR_GNM`, `PRO_CA`, and `LMS_CERT`.
- `09_subject_matrix` is generated only from matching board, medium, grade, stream, and subject scope combinations.
- Descriptive labels such as `Online Platforms` or `Exam Prep` do not generate rows unless they are modelled as grade codes or compact grade prefixes.

Subject matrix output values:

- `09_subject_matrix.is_compulsory` comes from `08_subjects.matrix_is_compulsory`.
- `09_subject_matrix.is_elective` comes from `08_subjects.matrix_is_elective`.
- `09_subject_matrix.display_order` comes from `08_subjects.matrix_display_order`.
- `09_subject_matrix.source_note` comes from `08_subjects.matrix_source_note`.
- Keep these values in the sheet/source CSV. Macros and export scripts must not add hidden static defaults.

## Chapter 7: Master Workbook Rules

The master workbook is optional but useful when a school operator wants one spreadsheet entry point.

Main template:

```text
master_import_process/templates/school_master_import_template.xlsx
```

Input workbook expected by the import process:

```text
master_import_process/input/school_master_import.xlsx
```

Every import sheet follows this row contract:

| Row | Purpose |
|---:|---|
| 1 | Metadata: source CSV path, ordered CSV file, purpose, guide row, summary row, pattern row, header row, example row. |
| 2 | Column requirement and owner/use guide. |
| 3 | Column usage summary. |
| 4 | ID-number formula or reference condition. |
| 5 | Actual CSV headers exported to CSV. |
| 6 | Example row in review/template workbooks; real data row in the operational macro ODS. |
| 7+ | Operator-entered data rows. |

Do not delete rows 1 to 5. In pure review/template workbooks, keep row 6 as a reference example and export with `--skip-example-row`. In the operational macro `.ods`, row 6 is real source data and must not be skipped.

The macro-enabled `.ods` artifact is different from the `.xlsx` review workbook by design. The `.xlsx` keeps row 6 as a human example/reference row. The `.ods` is operational and is seeded from `build/assembled_csv/<academic-year>/`, so row 6 starts real import data. This keeps the `status` sheet counts and macro-generated rows aligned with the CSV files Moodle actually imports.

`run_master_import.sh` defaults to `SKIP_EXAMPLE_ROW=1` because its default input is the review/template `.xlsx`. Set `SKIP_EXAMPLE_ROW=0` only when the input workbook has real data in row 6.

Operational ODS CSV export:

```bash
python3 research/drona_public_school/master_import_process/scripts/excel_to_source_csv.py \
  --workbook <converted-or-edited-workbook.xlsx> \
  --output <output-dir> \
  --year 2026-2027
```

Template/review workbook CSV export:

```bash
python3 research/drona_public_school/master_import_process/scripts/excel_to_source_csv.py \
  --workbook <template-workbook.xlsx> \
  --output <output-dir> \
  --year 2026-2027 \
  --skip-example-row
```

Workbook helper sheets:

| Sheet | Purpose |
|---|---|
| `_dashboard` | High-level workbook status and operator guidance. |
| `_sheet_index` | Sheet list and source/ordered CSV mapping. |
| `_version` | Workbook version metadata. |
| `_lookups` | Dropdown validation source values. |
| `_color_guide` | Required/optional/school-specific color guide. |
| `status` | Health sheet with manual/automatic sheet type, source filename, live row count, pass/fail checks, and macro action links. |
| `00_MACRO_GUIDE` | Present in the LibreOffice macro workbook only. |

`status` sheet columns:

| Column | Purpose |
|---|---|
| `type` | `manual` for operator-managed sheets, `automatic` for macro-generated sheets. |
| `filename` | Ordered/source CSV name plus the ID-number formula or row contract. |
| `count` | Live formula count of records in the referenced sheet. |
| `status` | `PASSED`/`FAILED` for automatic sheets, `MANUAL` for operator-managed sheets. |
| `action` | LibreOffice macro link for full rebuild, refresh, clear, reset/regenerate, or single-sheet generation. |

## Chapter 8: Workbook Generation

Create or refresh workbook templates:

```bash
python3 master_import_process/scripts/create_master_workbooks.py --year 2026-2027
```

This creates or refreshes:

```text
master_import_process/templates/school_master_import_template.xlsx
master_import_process/templates/sample_minimal_school_import.xlsx
```

Prepare an input workbook:

```bash
cp master_import_process/templates/school_master_import_template.xlsx \
  master_import_process/input/school_master_import.xlsx
```

Validate the workbook structure:

```bash
python3 master_import_process/scripts/validate_master_excel.py \
  --workbook master_import_process/input/school_master_import.xlsx \
  --year 2026-2027
```

Generate source CSV from the workbook:

```bash
python3 master_import_process/scripts/excel_to_source_csv.py \
  --workbook master_import_process/input/school_master_import.xlsx \
  --output master_import_process/output/source_csv \
  --year 2026-2027
```

Validate generated workbook CSV structure:

```bash
python3 master_import_process/scripts/validate_generated_structure.py \
  --source master_import_process/output/source_csv \
  --year 2026-2027
```

Validate ID-number and relationship formulas:

```bash
python3 master_import_process/scripts/validate_idnumber_patterns.py \
  --source master_import_process/output/source_csv \
  --year 2026-2027
```

Generate HTML/CSV import reports:

```bash
python3 master_import_process/scripts/generate_import_reports.py \
  --source master_import_process/output/source_csv \
  --year 2026-2027 \
  --output-dir master_import_process/output/reports
```

Run the full workbook pipeline wrapper:

```bash
master_import_process/scripts/run_master_import.sh 2026-2027 validate-only
master_import_process/scripts/run_master_import.sh 2026-2027 dry-run
master_import_process/scripts/run_master_import.sh 2026-2027 live
```

## Chapter 9: Assembling the CLI Pack

Assemble one year:

```bash
python3 scripts/assemble.py --year 2026-2027
```

Assemble every available year:

```bash
python3 scripts/assemble.py --year all
```

Assemble from workbook-generated source CSV instead of the packaged source folder:

```bash
python3 scripts/assemble.py \
  --year 2026-2027 \
  --source-root master_import_process/output/source_csv
```

The assembled output goes to:

```text
build/assembled_csv/<academic-year>/
```

Do not edit assembled files by hand. Fix source CSV or workbook data, then reassemble.

## Chapter 10: Validation

Run the full local preflight first:

```bash
scripts/doctor.sh 2026-2027
scripts/validate_all.sh 2026-2027
```

`scripts/doctor.sh` checks the local execution environment:

- Python and PHP availability.
- Docker availability and whether the Moodle service is running.
- LibreOffice availability for macro workbook smoke tests.
- Python `openpyxl` availability for workbook validation.
- Required pack folders, source files, Moodle CLI validators, and assembled CSV output.

`scripts/validate_all.sh` is the recommended one-command validation gate before dry-run. It does not import data into Moodle. It runs:

1. Ordered CSV assembly.
2. Structured source CSV validation.
3. ID-number formula and relationship validation.
4. Maintained workbook artifact validation when dependencies are available.
5. Moodle baseline CSV shape validation.
6. Moodle course-template CSV shape validation.
7. Preflight report and import manifest generation.

If `openpyxl` is missing, workbook artifact validation is skipped with a warning. Install the workbook dependencies or provide a Python executable with `openpyxl`:

```bash
python3 -m pip install -r master_import_process/requirements.txt

PYTHON_BIN=/path/to/python-with-openpyxl scripts/validate_all.sh 2026-2027
```

Validate the structured source pack:

```bash
python3 scripts/validate.py --year 2026-2027
```

This validates required relationships, course/certificate/exam coverage, and duplicate keys using the correct contract for each file. Examples: `lookup_type + code` for lookup values, `course_code + cohort_code + group_idnumber + role_shortname` for enrolments, and `username + role + context` for role assignments.

Validate future years:

```bash
python3 scripts/validate.py --year 2027-2028
python3 scripts/validate.py --year 2028-2029
python3 scripts/validate.py --year 2029-2030
```

Validate only the assembled Moodle CLI CSVs:

```bash
scripts/import.sh 2026-2027 validate-only
```

Generate only the preflight report:

```bash
scripts/preflight_report.sh 2026-2027
```

Preflight outputs:

```text
build/reports/2026-2027/preflight_report.md
build/reports/2026-2027/import_manifest.json
```

The preflight report is a human-readable Markdown review file. It summarizes import scope, relationship checks, blocking errors, warnings, and every assembled CSV file hash.

The import manifest is a machine-readable JSON lock file. It records the academic year, generated timestamp, assembled directory, row count, and SHA-256 hash for every assembled CSV file. Use it to confirm that the exact CSV payload validated before dry-run is the payload being imported.

Run workbook/macro smoke validation:

```bash
python3 master_import_process/scripts/validate_master_workbook.py
```

This checks:

- Required workbook files exist.
- Workbook opens and has expected sheet count.
- Required sheets and headers exist.
- The `status` sheet exists, has the required columns, and exposes the expected action links.
- Course, template, certificate, exam, gradebook, student history, promotion, and next-year row counts line up.
- Term exams include active terms only; final exams are validated separately.
- Macro guide, Basic source, and README are aligned.
- All Basic `RequireSheet(...)` references exist.
- ODS macro package contains expected files.
- LibreOffice can open/convert the ODS workbook.

## Chapter 11: Moodle Import Workflow

Start Moodle before importing:

```bash
docker compose up -d
```

Validate-only:

```bash
scripts/import.sh 2026-2027 validate-only
```

Dry-run:

```bash
scripts/import.sh 2026-2027 dry-run
```

Live import:

```bash
scripts/import.sh 2026-2027 live
```

What the wrapper does:

1. Assembles CSVs if `build/assembled_csv/<year>/` does not exist.
2. Runs local PHP CSV validators.
3. Generates `build/reports/<year>/preflight_report.md` and `import_manifest.json`.
4. Copies Moodle CLI scripts into `moodle/admin/cli/`.
5. Copies the assembled pack into the Moodle container under `/tmp/school_master_pack/<year>`.
6. Runs the baseline importer.
7. Applies course template settings.
8. Applies gradebook template settings.
9. Applies course certificates.

Future-year imports automatically skip users:

```bash
scripts/import.sh 2027-2028 dry-run
scripts/import.sh 2027-2028 live
```

The wrapper passes `--skip-users=1` for any year other than `2026-2027`.

## Chapter 12: Moodle CLI Script Responsibilities

| Script | Responsibility |
|---|---|
| `cli_moodle502_preflight.php` | Moodle compatibility and preflight checks. |
| `cli_validate_school_baseline.php` | Validate baseline CSV shape before import. |
| `cli_validate_course_template_csv.php` | Validate course template CSVs. |
| `cli_import_indian_school_baseline.php` | Create/update categories, courses, users, cohorts, groups, enrolments, role assignments, and parent links. |
| `cli_create_universal_master_course_template.php` | Create/reset the Moodle master course template. |
| `cli_apply_course_template_settings.php` | Apply section/activity/template settings to courses. |
| `cli_apply_gradebook_template.php` | Apply gradebook categories and weights. |
| `cli_apply_course_certificates.php` | Create Custom Certificate activities and certificate templates. |
| `cli_prepare_next_academic_year.php` | Prepare future-year structures. |
| `cli_promote_academic_year.php` | Academic-year promotion workflow. |
| `cli_promote_students_academic_year.php` | Student-specific promotion workflow. |

## Chapter 13: Course Template, Exams, and Certificates

Course templates are defined in:

```text
templates/courses/
templates/exams/
templates/certification/
templates/legacy/
```

The current import still assembles legacy ordered files `30` to `42` because Moodle CLI scripts use those stable names.

Every generated course should receive:

- Chapter sections.
- Overview/resource placeholders.
- Assignments.
- Quizzes.
- Completion tracking.
- Sequential chapter-gate restrictions.
- Term exam mappings for `TERM1` and `TERM2`.
- Final exam mapping through `course_final_exams.csv`.
- One Custom Certificate activity in the `Certificate & Completion` section.

The final exam row is intentionally separate from term exam rows. `exam_terms.csv` may include `FINAL`, but `course_term_exams.csv` should only contain active term exams such as `TERM1` and `TERM2`.

## Chapter 14: Academic-Year Rollover

Do not recreate students, parents, or teachers for a new academic year.

Reusable data:

- Staff users.
- Student users.
- Parent users.
- Parent links.
- Static profile fields.
- Roles.
- Lookup values.

Year-specific data:

- Categories.
- Courses.
- Cohorts.
- Groups.
- Cohort memberships.
- Enrolments.
- Role assignments.
- Academic history.
- Promotion plans.
- Course certificates, exams, attendance, and gradebook weights.

Generate a next-year skeleton:

```bash
scripts/generate-next-year.sh 2027-2028
```

Then validate:

```bash
python3 scripts/assemble.py --year 2027-2028
python3 scripts/validate.py --year 2027-2028
scripts/import.sh 2027-2028 dry-run
```

## Chapter 15: Macro Workbook Artifacts

Workbook review artifacts live in:

```text
build/master_excel/
```

Current maintained artifact names:

```text
school_master_pack_2026_2027_sample_master.xlsx
school_master_pack_2026_2027_full_predefined_master.xlsx
school_master_pack_2026_2027_full_predefined_master_macros.ods
```

Use [build/master_excel/README.md](../build/master_excel/README.md) as the canonical workbook/macro reference. This developer guide keeps only the operating sequence so macro names and workbook rules are not duplicated across multiple files.

Regenerate the LibreOffice macro ODS after changing workbook scripts, macro source, status-sheet rules, or the full predefined workbook:

```bash
python3 master_import_process/scripts/create_libreoffice_macro_workbook.py --year 2026-2027 --source-root research/drona_public_school
```

Open the ODS in LibreOffice and run:

```text
Tools > Macros > Run Macro > current document > Standard > MatrixTools > GenerateAllDerivedSheets
```

Recommended health workflow:

1. Open `school_master_pack_2026_2027_full_predefined_master_macros.ods`.
2. Enable macros.
3. Go to the `status` sheet.
4. Use `Refresh status` after manual edits that do not change row structure.
5. Use `Run all automatic macros` after adding/removing boards, mediums, grades, streams, subjects, divisions, courses, academic years, or students.
6. Use `Clear automatic data` only when you want generated rows emptied but manual registration/reference sheets preserved.
7. Use `Reset and regenerate` when automatic sheets look stale or row counts changed significantly.

Manual sheets are source-of-truth data and must not be overwritten by workbook macros. Automatic sheets are derived from those source sheets. `34_grade_band_adjust` is the policy source for generated course defaults, template application, certificate settings, assessment weights, gradebook weights, and attendance defaults. Stream-specific rows must stay above broader rows, and the final `ALL` row is the fallback for any grade that has no dedicated grade-band policy. `58_alumni_cohorts` is automatic because it is generated from `14_cohorts` rows that match `45_promotion_rules` alumni rows; promotion action rows remain manual because they are the approval gate before changing live student placement.

## Chapter 16: Development Rules

Use this change flow:

1. Edit source CSV, workbook script, or Moodle CLI script.
2. Reassemble the affected academic year.
3. Run structured validation.
4. Run import `validate-only`.
5. Run dry-run in Docker.
6. Only then run live import.

Recommended command set:

```bash
scripts/doctor.sh 2026-2027
scripts/validate_all.sh 2026-2027
scripts/import.sh 2026-2027 validate-only
scripts/import.sh 2026-2027 dry-run
python3 master_import_process/scripts/validate_master_workbook.py
```

When changing workbook scripts:

```bash
python3 -m py_compile \
  master_import_process/scripts/create_master_workbooks.py \
  master_import_process/scripts/create_libreoffice_macro_workbook.py \
  master_import_process/scripts/validate_master_workbook.py
```

When changing Moodle PHP scripts:

```bash
php -l scripts/moodle_cli/cli_import_indian_school_baseline.php
php -l scripts/moodle_cli/cli_apply_course_template_settings.php
php -l scripts/moodle_cli/cli_apply_gradebook_template.php
php -l scripts/moodle_cli/cli_apply_course_certificates.php
```

## Chapter 17: Cleanup Policy

Keep:

- `master/`
- `registration/`
- `templates/`
- `operations/`
- `years/`
- `scripts/`
- `scripts/moodle_cli/`
- `master_import_process/scripts/`
- `master_import_process/templates/*.xlsx`
- `build/assembled_csv/` if the project intentionally tracks generated local-testing CSVs.
- `build/master_excel/*.xlsx` and `*.ods` when they are the maintained review artifacts.

Remove:

- `.DS_Store`
- `__pycache__/`
- `*.pyc`
- Generated zip archives unless explicitly needed for release packaging.
- Old duplicate runbooks after this guide supersedes them.
- Manually edited files under `build/assembled_csv/`.

## Chapter 18: Troubleshooting

`ModuleNotFoundError: openpyxl`

Install dependencies or use the bundled Python runtime:

```bash
python3 -m pip install -r master_import_process/requirements.txt
```

`Workbook not found`

Create the input workbook:

```bash
cp master_import_process/templates/school_master_import_template.xlsx \
  master_import_process/input/school_master_import.xlsx
```

`Unknown year`

Create or generate the year folder under `years/<year>/`, then rerun assembly.

`Missing required generated CSV`

The workbook conversion skipped a required sheet or the source folder is incomplete. Run:

```bash
python3 master_import_process/scripts/validate_master_excel.py \
  --workbook master_import_process/input/school_master_import.xlsx \
  --year 2026-2027
```

`course missing TERM1/TERM2 rows`

Check:

```text
years/<year>/course_term_exams.csv
years/<year>/exam_terms.csv
templates/exams/term_exam_templates.csv
```

`certificate rows do not match course rows`

Every course must have one enabled row in:

```text
years/<year>/course_certificates.csv
```

`Moodle service is not running`

Start Docker:

```bash
docker compose up -d
```

`Custom certificate plugin missing`

Install and enable Moodle `mod_customcert`, then rerun dry-run.

`LibreOffice convert failed`

Install LibreOffice or run the smoke check without conversion:

```bash
python3 master_import_process/scripts/validate_master_workbook.py --skip-libreoffice
```

## Chapter 19: Final Pre-Import Checklist

Before live import:

- `python3 scripts/assemble.py --year <year>` passes.
- `python3 scripts/validate.py --year <year>` passes.
- `scripts/import.sh <year> validate-only` passes.
- `scripts/import.sh <year> dry-run` passes.
- Moodle backup is complete.
- `mod_customcert` is installed if certificates are enabled.
- School has reviewed sample/generated data before production use.
