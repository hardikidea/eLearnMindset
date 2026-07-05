# Chapter 03: Ordered CSV Data Model

## Naming Rule

Every editable CSV uses this format:

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

The numeric prefix is the setup order. The logical name remains the script-level identity. For example, scripts request `users_students.csv`, and `cli_csv_helpers.php` resolves it to `20_users_students.csv`.

## Main Dependency Chain

```text
01_school_master.csv
  -> 02_academic_years.csv
  -> 03_boards.csv / 04_mediums.csv / 05_grades.csv / 06_streams.csv / 07_divisions.csv / 08_subjects.csv
  -> 09_grade_subject_matrix.csv
  -> 10_categories.csv
  -> 12_courses.csv
  -> 14_cohorts.csv
  -> 15_groups.csv
  -> 25_enrolments.csv
```

User data depends on structure:

```text
16_user_profile_fields.csv
17_custom_roles.csv
19_users_staff.csv
20_users_students.csv
21_users_parents.csv
22_cohort_members.csv
23_role_assignments.csv
24_parent_links.csv
```

## Edit Order for a New School

1. Edit master school identity in `01_school_master.csv`.
2. Edit academic calendar in `02_academic_years.csv`.
3. Confirm boards, mediums, grades, streams, divisions, and subjects in `03_` to `08_`.
4. Edit subject eligibility in `09_grade_subject_matrix.csv`.
5. Generate or edit categories in `10_categories.csv`.
6. Generate or edit courses in `12_courses.csv`.
7. Create cohorts and groups in `14_cohorts.csv` and `15_groups.csv`.
8. Add users and assignments in `19_` to `25_`.

## Full CSV Index

Use [../README_ORDERED_CSV_FILES.md](../README_ORDERED_CSV_FILES.md) for the full `01_` to `61_` ordered file list.

## Column-Level Reference

Use [../README_CSV_COLUMN_GUIDE.md](../README_CSV_COLUMN_GUIDE.md) for detailed column definitions, dependencies, and examples.

## Validation Commands

```bash
PACK_HOST="$PWD/research/moodle_indian_school_moodle502_template_ready_csv_cli_pack"

php "$PACK_HOST/cli_validate_school_baseline.php" --dir="$PACK_HOST"
php "$PACK_HOST/cli_validate_course_template_csv.php" --dir="$PACK_HOST"
```

Preferred wrapper:

```bash
"$PACK_HOST/run_school_setup_master.sh" validate
```

