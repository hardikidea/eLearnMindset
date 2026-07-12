# Master Excel Notes

`school_master_pack_2026_2027_sample_master.xlsx` is a capped review workbook generated from the validated 2026-2027 assembled CSV files.

`school_master_pack_2026_2027_full_predefined_master.xlsx` is the full predefined-data workbook generated from every validated 2026-2027 assembled CSV file. It contains all rows from all sheets and is intended for complete offline review.

`school_master_pack_2026_2027_full_predefined_master_macros.ods` is the LibreOffice macro-enabled version of the full predefined workbook. It includes workbook-local `Standard.MatrixTools` macros and a `00_MACRO_GUIDE` sheet.

The full predefined workbook and macro workbook include a `status` sheet for workbook health checks.

Do not run macro actions from the `.xlsx` review workbooks. Regular `.xlsx` files do not contain the document-level LibreOffice Basic library, so LibreOffice cannot resolve `Standard.MatrixTools`. Run macro actions only from `school_master_pack_2026_2027_full_predefined_master_macros.ods`.

The full local-testing dataset is intentionally kept in CSV form because it contains thousands of rows:

- 5,220 students
- 4,698 parents
- 2,160 groups per academic year
- 2,160 enrolment mappings per academic year
- 360 certificate mappings per academic year

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

For stream scope, use `|` in `applies_to`: `STD01-STD10`, `STD11_SCI|STD12_SCI`, or `ALL`. Do not use comma-separated values for new data.

Available individual macros:

- `RefreshStatus` recalculates formulas and updates the `status` sheet checks.
- `ClearAutomaticData` clears only macro-generated data rows.
- `ResetAutomaticData` clears macro-generated rows and then runs a full rebuild.
- `GenerateGradeSubjectMatrix` rebuilds `13_grade_subject_matrix` from boards, mediums, grades, streams and subjects.
- `GenerateCategories` rebuilds `14_categories` from school, board, current academic year, medium, grade and stream setup.
- `GenerateOptionalYearCategoryModel` rebuilds `15_optional_year_category_model` as the optional category-model export from generated categories.
- `GenerateCourses` rebuilds `16_courses` from the grade-subject matrix and current academic year.
- `GenerateCoursesWithTemplateUpload` rebuilds `17_courses_with_templatecourse_` from generated courses and category paths.
- `GenerateCohorts` rebuilds `18_cohorts` from current academic year, medium, grade, stream and division setup.
- `GenerateGroups` rebuilds `19_groups` from courses and divisions.
- `GenerateCohortMembers` rebuilds `26_cohort_members` from `24_users_students.cohort1`.
- `GenerateEnrolments` rebuilds `29_enrolments` from courses and divisions using cohort-sync enrolment.
- `GenerateSummary` rebuilds `33_summary` from workbook counts and the school master row.
- `GenerateCourseTemplateApplication` rebuilds `41_course_template_application`.
- `GenerateRolloverChecklist` rebuilds `50_academic_year_rollover_check` from the standard academic-year rollover workflow.
- `GenerateStudentAcademicHistory` rebuilds `55_student_academic_history_tem` from student registrations and current academic-year context.
- `GenerateStudentPromotionPlan` rebuilds `56_student_promotion_plan_2027_` from student registrations and next-year grade progression rules.
- `GenerateAssessmentPlan` rebuilds `66_assessment_plan` from generated courses and standard assessment weights.
- `GenerateAttendancePolicy` rebuilds `67_attendance_policy` from grade setup and the current academic year.
- `GenerateCourseCertificates` rebuilds `68_course_certificates`.
- `GenerateCourseFinalExams` rebuilds `69_course_final_exams`.
- `GenerateCourseTermExams` rebuilds `70_course_term_exams`.
- `GenerateGradebookWeights` rebuilds `72_gradebook_weights`.
- `GenerateNextYearCourses` rebuilds `58_next_year_courses_2027_2028` from current generated courses and the next academic year.
- `GenerateNextYearCohorts` rebuilds `59_next_year_cohorts_2027_2028` from current generated cohorts and the next academic year.
- `GenerateNextYearGroups` rebuilds `60_next_year_groups_2027_2028` from next-year courses and divisions.
- `GenerateNextYearEnrolments` rebuilds `61_next_year_enrolments_2027_20` from next-year courses and divisions using cohort-sync enrolment.
- `GenerateAlumniCohorts` rebuilds `62_alumni_cohorts_2027` from current-year `STD12` cohorts for alumni/archive handling.
- `GenerateArchivePolicy` rebuilds `63_archive_policy` from the standard archive checklist.
- `GenerateCompatibilityMatrix` rebuilds `65_compatibility_matrix` from the standard Moodle component compatibility map.

Primary ID formulas used by the macros:

- Course code: `<SCHOOL_CODE>-<BOARD_CODE>-<MEDIUM_CODE>-<GRADE_CODE>-<STREAM_CODE>-<SUBJECT_CODE>-<START_YEAR>`
- Course shortname: `<SCHOOL_CODE>-<BOARD_CODE>-<MEDIUM_CODE>-<GRADE_CODE>-<STREAM_CODE>-<SUBJECT_CODE>-<YY>`
- Category code: `<TRUST_CODE>_<BOARD_CODE>_<SCHOOL_CODE>_<YYYY_YYYY>_<MEDIUM_CODE>_<GRADE_CODE>_<STREAM_CODE>`
- Cohort code: `<SCHOOL_CODE>-<START_YEAR>-<BOARD_CODE>-<MEDIUM_CODE>-<GRADE_CODE>-<STREAM_CODE>-<DIVISION_CODE>`
- Alumni cohort code: `<SCHOOL_CODE>-ALUMNI-<NEXT_START_YEAR>-<BOARD_CODE>-<MEDIUM_CODE>-<GRADE_CODE>-<STREAM_CODE>-<DIVISION_CODE>`
- Group ID number: `<COURSE_CODE>-<DIVISION_CODE>`
- Enrolment mapping: `course_code + cohort_code + group_idnumber + cohort_sync`

Keep a backup copy before running macros against manually edited data.

Most static/reference sheets are intentionally not rebuilt by macros. Manual registration sheets, lookup sheets, template definition sheets, promotion actions, and `47_diksha_content_template` remain operator-managed until the school confirms exact content and policy. The safe generated operational-reference sheets are `33_summary`, `50_academic_year_rollover_check`, `62_alumni_cohorts_2027`, `63_archive_policy`, and `65_compatibility_matrix`.

Macro source is maintained in `research/drona_public_school/master_import_process/scripts/libreoffice_master_tools.bas`. Regenerate the ODS with:

`python3 research/drona_public_school/master_import_process/scripts/create_libreoffice_macro_workbook.py`

Run the workbook and macro smoke validation with:

`python3 research/drona_public_school/master_import_process/scripts/validate_master_workbook.py`

This checks required sheets, row-count relationships, macro-guide alignment, embedded ODS macro files, Basic sheet references, and a LibreOffice headless open/convert pass.
