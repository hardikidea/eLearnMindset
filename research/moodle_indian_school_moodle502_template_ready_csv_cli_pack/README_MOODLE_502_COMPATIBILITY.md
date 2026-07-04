# Moodle 502 compatibility notes

This pack is intended for Moodle LMS 5.x school baseline setup.

The phrase "Moodle 502" is commonly used in two ways:

1. Moodle 5.0.2 release.
2. Moodle documentation branch 502, which corresponds to Moodle 5.2.

The CSV structure and CLI scripts in this pack are compatible with both meanings because they use long-standing Moodle core APIs and core features:

- Course categories
- Courses
- Cohorts
- Groups
- Cohort sync enrolment
- User accounts
- Custom user profile fields
- Custom roles and role assignments
- Parent role in student user context

## Compatibility result

| Area | Moodle 5.0.2 | Moodle 5.2 / docs branch 502 | Notes |
|---|---:|---:|---|
| Course categories | Compatible | Compatible | Used for Trust, Board, School, Medium, Class, Stream |
| Courses | Compatible | Compatible | Subject courses under Stream categories |
| Groups | Compatible | Compatible | Division groups inside each subject course |
| Cohorts | Compatible | Compatible | Batch-level student grouping for enrolment |
| Cohort sync enrolment | Compatible | Compatible | Must keep `enrol_cohort` enabled |
| User custom profile fields | Compatible | Compatible | Used for Indian school profile fields |
| Role contexts | Compatible | Compatible | Trustee/Principal at category, Teacher at course, Parent at user context |
| Parent links | Compatible | Compatible | Implemented as parent role assigned in student user context |
| Academic-year promotion | Compatible | Compatible | Adds/removes cohort membership and updates profile fields |
| Moodle Workplace tenancy | Not required | Not required | Optional only if true tenant isolation is needed |

## Moodle server version notes

For Moodle 5.0.x, Moodle 5.0 release notes list PHP 8.2.0 minimum, PHP 8.3.x and 8.4.x support, sodium extension requirement, `max_input_vars >= 5000`, and database minimums including MySQL 8.4 and MariaDB 10.11.0.

For Moodle 5.2, Moodle 5.2 release notes list PHP 8.3.0 minimum, PHP 8.4.x support, sodium extension requirement, `max_input_vars >= 5000`, and database minimums including PostgreSQL 16, MySQL 8.4, MariaDB 10.11.0, Aurora MySQL 8.0, and Microsoft SQL Server 2019.

Moodle 5.1 introduced a code directory restructure with a public web folder. For CLI usage, copy these scripts to the Moodle code root under `admin/cli/`, not to the public web folder.

## Preflight command

Copy the script into Moodle:

```bash
cp cli_moodle502_preflight.php /path/to/moodle/admin/cli/
```

Run:

```bash
php /path/to/moodle/admin/cli/cli_moodle502_preflight.php
```

This checks:

- Moodle release and branch
- PHP version
- required PHP extensions
- database prefix length
- core functions/classes used by the importer
- `enrol_cohort` availability
- topic course format directory

## CSV validator command

This validator can run before touching Moodle data:

```bash
php cli_validate_school_baseline.php --dir=/path/to/extracted/csv-pack
```

It checks duplicate IDs and cross-file references, including category parents, course categories, cohort contexts, group courses, enrolment course/cohort/group references, users, parent links, and promotion references.

## Compatibility limitations

The pack does not import:

- Attendance records
- Timetables
- Fee records
- Transport attendance
- Library records
- SMS/WhatsApp logs
- Government exam board APIs

Those should remain in SIS/ERP modules or be handled by selected Moodle plugins/integrations after baseline LMS setup.

## Recommended production rule

Use this sequence:

1. Run Moodle environment check from Site administration.
2. Run `cli_moodle502_preflight.php`.
3. Run `cli_validate_school_baseline.php`.
4. Run baseline import with `--dry-run=1`.
5. Import on a staging copy first.
6. Back up database and moodledata.
7. Import on production with `--dry-run=0`.
8. Spot-check categories, courses, cohorts, groups, roles, parent links, and student login.


## Optional next-academic-year scripts

This compatibility pack also includes:

```text
cli_prepare_next_academic_year.php
cli_promote_students_academic_year.php
next_year_courses_2027_2028.csv
next_year_cohorts_2027_2028.csv
next_year_groups_2027_2028.csv
next_year_enrolments_2027_2028.csv
alumni_cohorts_2027.csv
student_promotion_plan_2027_2028.csv
```

Use these when you want to create the next academic-year baseline before moving students.
