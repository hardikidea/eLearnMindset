# Master Excel Notes

`school_master_pack_2026_2027_sample_master.xlsx` is a capped review workbook generated from the validated 2026-2027 assembled CSV files.

`school_master_pack_2026_2027_full_predefined_master.xlsx` is the full predefined-data workbook generated from every validated 2026-2027 assembled CSV file. It contains all rows from all sheets and is intended for complete offline review.

`school_master_pack_2026_2027_full_predefined_master_macros.ods` is the LibreOffice macro-enabled version of the full predefined workbook. It includes workbook-local `Standard.MatrixTools` macros and a `00_MACRO_GUIDE` sheet.

The full predefined workbook and macro workbook include a `status` sheet for workbook health checks.

The `.xlsx` review workbook keeps row 6 as an example/reference row. The `.ods` macro workbook is operational: during generation it is seeded from `build/assembled_csv/<academic-year>/` so row 6 starts real import data. This keeps macro-generated counts aligned with the exact CSV rows used by Moodle imports and prevents duplicate example rows during macro rebuilds.

Do not run macro actions from the `.xlsx` review workbooks. Regular `.xlsx` files do not contain the document-level LibreOffice Basic library, so LibreOffice cannot resolve `Standard.MatrixTools`. Run macro actions only from `school_master_pack_2026_2027_full_predefined_master_macros.ods`.

The full local-testing dataset is intentionally kept in CSV form because it contains thousands of rows:

- 5,220 students
- 4,698 parents
- 19,332 groups per academic year
- 19,332 enrolment mappings per academic year
- 3,222 certificate mappings per academic year

Use the capped workbook for quick structure, header and example review. Use the full predefined workbook when you need to inspect all generated data in one Excel file. Use `build/assembled_csv/<academic-year>/` for real Moodle validation and import.

## LibreOffice Macro Workbook

Open `school_master_pack_2026_2027_full_predefined_master_macros.ods` in LibreOffice Calc and enable macros when prompted.

Use the `status` sheet first:

- `type` shows whether a sheet is manually maintained or macro-generated.
- `filename` shows the source CSV name plus the ID-number formula or row contract.
- `count` is a live row-count formula for the target sheet.
- `status` is `PASSED` or `FAILED` for automatic sheets and `MANUAL` for operator-managed sheets.
- `action` contains LibreOffice macro links for refresh, clear, reset/regenerate, or a single-sheet rebuild.

Top actions on the `status` sheet:

- `Run all automatic macros` rebuilds every automatic sheet.
- `Refresh status` recalculates workbook formulas only.
- `Clear automatic data` clears generated rows without touching manual registration or reference sheets.
- `Reset and regenerate` clears automatic rows and immediately rebuilds them.

Run the full derived-data rebuild:

`Tools > Macros > Run Macro > current document > Standard > MatrixTools > GenerateAllDerivedSheets`

This clears and rebuilds only derived data sheets. It does not overwrite staff, student, parent, parent-link, or role-assignment registration sheets.

The macros use a hybrid model:

- Macros create the required row structure for matrix-style data.
- Generated cells use LibreOffice formulas wherever they depend on master data.
- Existing derived rows recalculate automatically when referenced school, board, medium, grade, stream, subject, division, academic-year, course or student values change.
- Rerun the relevant macro when row counts change, for example after adding a new subject, division, grade, stream, course, academic year or student.
- CSV export must use calculated cell values, not raw formula text.

For stream scope, use `|` in `06_streams.applies_to`: `STD01-STD10`, `STD11_SCI|STD12_SCI`, or `ALL`. Do not use comma-separated values for new data.

For subject matrix scope, use `08_subjects.applies_to` when the column is present. Older workbooks may still use `08_subjects.notes` for this value, but new edits should keep machine-readable scope in `applies_to` and human notes in `notes`.

Subject matrix output values are also source-driven from `08_subjects`:

- `matrix_is_compulsory` maps to `09_subject_matrix.is_compulsory`.
- `matrix_is_elective` maps to `09_subject_matrix.is_elective`.
- `matrix_display_order` maps to `09_subject_matrix.display_order`.
- `matrix_source_note` maps to `09_subject_matrix.source_note`.

Do not hardcode these values in macros or export scripts. Change them in `08_subjects`, then rerun `GenerateGradeSubjectMatrix` or `ResetAutomaticData`.

Supported subject scope tokens:

- Exact grade: `STD05`, `ITI_ELEC`, `UNI_UG_BCA_Y1`.
- Grade range: `PRE01-STD10`, `STD11-STD12`.
- Grade plus stream: `STD11_SCI|STD12_SCI`, `STD11_COM|STD12_COM`.
- Compact grade prefix: `ITI`, `POLY`, `UNI_UG`, `NUR_GNM`, `PRO_CA`, `LMS_CERT`.

The generated subject matrix ignores descriptive phrases that are not valid scope tokens. Use real grade codes, stream-specific grade codes, ranges, or compact program prefixes when a subject should generate rows.

The pack supports three academic models from `05_grades.moodle_label`:

- `School`: two term exams, `TERM1` and `TERM2`.
- `University` or `Diploma`: two semester exams, `SEM1` and `SEM2`.
- `Certification`, `Vocational`, `Charter`, or `Professional`: one module checkpoint, `MODULE`.

`course_term_exams` uses this grade label dynamically. Do not hardcode term counts in the macro or status formulas.

`34_grade_band_adjust` is the central policy sheet for generated course behavior. The macros read this sheet to set:

- Course format, section count, visibility, completion, grade display and group mode defaults.
- Course template code and reusable template course shortname.
- Template application flags for sections, gradebook and completion defaults.
- Assessment and gradebook weights.
- Attendance minimum percentage and generated attendance notes.
- Course certificate policy, custom certificate activity settings, unlock rule, template branding and certificate text.

Use `grade_codes` as the scope column. Put stream-specific rows before broader fallback rows, for example `STD11_SCI|STD12_SCI` before `STD11|STD12`. Keep the final `ALL` row as the default fallback for grades that do not have a dedicated policy row.

Available individual macros:

- `RefreshStatus` recalculates formulas and updates the `status` sheet checks.
- `ClearAutomaticData` clears only macro-generated data rows.
- `ResetAutomaticData` clears macro-generated rows and then runs a full rebuild.
- `GenerateGradeSubjectMatrix` rebuilds `09_subject_matrix` from boards, mediums, grades, streams, subject `applies_to` values and subject matrix source columns.
- `GenerateCategories` rebuilds `10_categories` from school, board, current academic year, medium, grade and stream setup.
- `GenerateOptionalYearCategoryModel` rebuilds `11_optional_categories` as the optional category-model export from generated categories.
- `GenerateCourses` rebuilds `12_courses` from the grade-subject matrix and current academic year.
- `GenerateCoursesWithTemplateUpload` rebuilds `13_courses_upload` from generated courses and category paths.
- `GenerateCohorts` rebuilds `14_cohorts` from current academic year, medium, grade, stream and division setup.
- `GenerateGroups` rebuilds `15_groups` from courses and divisions.
- `GenerateCohortMembers` rebuilds `22_cohort_members` from `20_users_students.cohort1`.
- `GenerateEnrolments` rebuilds `25_enrolments` from courses and divisions using cohort-sync enrolment.
- `GenerateSummary` rebuilds `29_summary` from workbook counts and the school master row.
- `GenerateCourseTemplateApplication` rebuilds `37_template_application`.
- `GenerateRolloverChecklist` rebuilds `46_rollover_checklist` from the standard academic-year rollover workflow.
- `GenerateStudentAcademicHistory` rebuilds `51_academic_history` from student registrations and current academic-year context.
- `GenerateStudentPromotionPlan` rebuilds `52_promotion_plan` from student registrations and next-year grade progression rules.
- `GenerateAssessmentPlan` rebuilds `assessment_plan` from generated courses and standard assessment weights.
- `GenerateAttendancePolicy` rebuilds `attendance_policy` from grade setup, current academic year and `34_grade_band_adjust` attendance defaults.
- `GenerateCourseCertificates` rebuilds `course_certificates`.
- `GenerateCourseFinalExams` rebuilds `course_final_exams`.
- `GenerateCourseTermExams` rebuilds `course_term_exams`.
- `GenerateGradebookWeights` rebuilds `gradebook_weights`.
- `GenerateNextYearCourses` rebuilds `54_next_year_courses` from current generated courses and the next academic year.
- `GenerateNextYearCohorts` rebuilds `55_next_year_cohorts` from current generated cohorts and the next academic year.
- `GenerateNextYearGroups` rebuilds `56_next_year_groups` from next-year courses and divisions.
- `GenerateNextYearEnrolments` rebuilds `57_next_year_enrolments` from next-year courses and divisions using cohort-sync enrolment.
- `GenerateAlumniCohorts` rebuilds `58_alumni_cohorts` from current-year cohorts matched by `45_promotion_rules` rows whose `to_grade_code` or `promotion_decision` is `ALUMNI`.
- `GenerateArchivePolicy` rebuilds `59_archive_policy` from the standard archive checklist.
- `GenerateCompatibilityMatrix` rebuilds `61_compatibility` from the standard Moodle component compatibility map.

Primary ID formulas used by the macros:

- Course code: `<SCHOOL_CODE>-<BOARD_CODE>-<MEDIUM_CODE>-<GRADE_CODE>-<STREAM_CODE>-<SUBJECT_CODE>-<START_YEAR>`
- Course shortname: `<SCHOOL_CODE>-<BOARD_CODE>-<MEDIUM_CODE>-<GRADE_CODE>-<STREAM_CODE>-<SUBJECT_CODE>-<YY>`
- Category code: `<TRUST_CODE>_<BOARD_CODE>_<SCHOOL_CODE>_<YYYY_YYYY>_<MEDIUM_CODE>_<GRADE_CODE>_<STREAM_CODE>`
- Cohort code: `<SCHOOL_CODE>-<START_YEAR>-<BOARD_CODE>-<MEDIUM_CODE>-<GRADE_CODE>-<STREAM_CODE>-<DIVISION_CODE>`
- Alumni cohort code: `<SCHOOL_CODE>-ALUMNI-<NEXT_START_YEAR>-<BOARD_CODE>-<MEDIUM_CODE>-<GRADE_CODE>-<STREAM_CODE>-<DIVISION_CODE>`
- Group ID number: `<COURSE_CODE>-<DIVISION_CODE>`
- Enrolment mapping: `course_code + cohort_code + group_idnumber + cohort_sync`

Keep a backup copy before running macros against manually edited data.

Most static/reference sheets are intentionally not rebuilt by macros. Manual registration sheets, lookup sheets, template definition sheets and promotion action sheets remain operator-managed until the school confirms exact content and policy. `43_content_template` is generated from the master subject matrix so every course has a default content placeholder, but operators should still update real URLs, descriptions and source links before production import. The safe generated operational-reference sheets are `29_summary`, `46_rollover_checklist`, `58_alumni_cohorts`, `59_archive_policy`, and `61_compatibility`.

Macro source is maintained in `research/drona_public_school/master_import_process/scripts/libreoffice_master_tools.bas`. Regenerate the ODS with:

`python3 research/drona_public_school/master_import_process/scripts/create_libreoffice_macro_workbook.py --year 2026-2027 --source-root research/drona_public_school`

Run the workbook and macro smoke validation with:

`python3 research/drona_public_school/master_import_process/scripts/validate_master_workbook.py`

This checks required sheets, row-count relationships, macro-guide alignment, embedded ODS macro files, Basic sheet references, and a LibreOffice headless open/convert pass.

When generating CSV files from the operational ODS, row 6 is treated as real data. Use `--skip-example-row` only for a pure template/review workbook where row 6 is intentionally an example row:

`python3 research/drona_public_school/master_import_process/scripts/excel_to_source_csv.py --workbook <workbook.xlsx> --output <output-dir> --year 2026-2027 --skip-example-row`

`run_master_import.sh` uses `SKIP_EXAMPLE_ROW=1` by default for the review/template `.xlsx` flow. Override with `SKIP_EXAMPLE_ROW=0` only for a workbook where row 6 is operational data.
