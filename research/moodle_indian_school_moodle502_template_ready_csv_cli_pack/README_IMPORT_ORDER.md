# Moodle 5.x / 502 compatible import order

For per-file column usage, dependencies, and selective-import guidance, read
`README_CSV_COLUMN_GUIDE.md` before editing CSV data.

For academic-year change, student promotion, and the no re-registration workflow,
read `README_ACADEMIC_YEAR_CHANGE_WORKFLOW.md`.

Before importing, run the new compatibility and CSV checks:

```bash
php /path/to/moodle/admin/cli/cli_moodle502_preflight.php
php cli_validate_school_baseline.php --dir=/path/to/extracted/csv-pack
php /path/to/moodle/admin/cli/cli_import_indian_school_baseline.php --dir=/path/to/extracted/csv-pack --dry-run=1
```

Then run the real import only after the dry-run is clean:

```bash
php /path/to/moodle/admin/cli/cli_import_indian_school_baseline.php --dir=/path/to/extracted/csv-pack --dry-run=0
```

Important: for Moodle 5.1 and later, the web-accessible directory changed, but CLI scripts should still be copied to the Moodle code root under `admin/cli/`, not into the public web folder.

---

# Moodle Indian School Baseline CSV + CLI Pack

Generated: 2026-07-03
Academic year: 2026-2027
Sample school: Green Valley School
Sample trust: Green Valley Education Trust

## Purpose

This pack creates a Moodle baseline for a typical Indian school setup with:

- Trust / organisation
- Board
- School
- Medium
- Grade / class
- Stream
- Subject course
- Division groups
- Cohorts for student enrolment
- Student, staff and parent sample users
- Parent-child role links
- Trustee Manager, Principal, Teacher, Student and Parent role guidelines
- Indian-school profile fields such as address, admission number, roll number, parent details, medical/emergency contacts and masked Aadhaar reference fields

## Recommended structure

```text
Moodle Site
└── Trust / Organisation = Course Category
    └── Board = Course Category
        └── School = Course Category
            └── Medium = Course Category
                └── Class / Std = Course Category
                    └── Stream = Course Category
                        └── Subject = Moodle Course
                            └── Division = Moodle Group
```

If you do not need a Trust level, remove the first `TRUST` row from `categories.csv` and make board categories top-level.

## Important privacy rule for Aadhaar

This pack does **not** create a full Aadhaar-number field in Moodle. It uses:

- `profile_field_aadhaar_last4`
- `profile_field_aadhaar_masked`
- `profile_field_aadhaar_consent`
- `profile_field_aadhaar_vault_ref`

Use full Aadhaar only in a legally approved and secure system outside Moodle if your institution has a lawful basis and consent/process. UIDAI describes masked Aadhaar as showing only the last four digits, with the first eight digits replaced. UIDAI also states Aadhaar is not mandatory for school admission. See `source_references.csv`.

## Files

### Master files

- `school_master.csv`
- `academic_years.csv`
- `boards.csv`
- `mediums.csv`
- `grades.csv`
- `streams.csv`
- `divisions.csv`
- `subjects.csv`
- `grade_subject_matrix.csv`

### Moodle structure files

- `categories.csv`
- `courses.csv`
- `cohorts.csv`
- `groups.csv`
- `enrolments.csv`
- `cohort_members.csv`

### User and role files

- `user_profile_fields.csv`
- `users_staff.csv`
- `users_students.csv`
- `users_parents.csv`
- `custom_roles.csv`
- `role_guidelines.csv`
- `role_assignments.csv`
- `parent_links.csv`

### Support files

- `lookup_values.csv`
- `validation_rules.csv`
- `source_references.csv`
- `diksha_content_template.csv`
- `summary.csv`
- `cli_import_indian_school_baseline.php`
- `run_import_example.sh`

## CLI import order

The provided CLI script imports in this order:

1. User profile fields
2. Custom roles
3. Course categories
4. Courses
5. Cohorts
6. Groups
7. Staff users
8. Student users
9. Parent users
10. Cohort members
11. Staff role assignments
12. Parent-child links
13. Cohort sync enrolment mappings

## Usage

Extract the ZIP file and copy the PHP importer into Moodle's CLI folder.

```bash
unzip moodle_indian_school_full_baseline_csv_cli_pack.zip -d /tmp/moodle-school-pack
cp /tmp/moodle-school-pack/cli_import_indian_school_baseline.php /path/to/moodle/admin/cli/
```

Run dry-run first:

```bash
php /path/to/moodle/admin/cli/cli_import_indian_school_baseline.php   --dir=/tmp/moodle-school-pack   --dry-run=1
```

Execute after review:

```bash
php /path/to/moodle/admin/cli/cli_import_indian_school_baseline.php   --dir=/tmp/moodle-school-pack   --dry-run=0
```

Run as the same OS user that normally owns/runs Moodle files, often `www-data` on Ubuntu/Debian.

## Role guidelines

| School role | Moodle role | Context | Recommended use |
|---|---|---|---|
| Trustee Manager | `trustee_manager` | Trust category or system | Trust-level oversight. Avoid Site Administrator unless technical admin. |
| Principal | `principal` | School category | School-level administration and reports. |
| Teacher | `editingteacher` | Subject course | Teaching, activities, grading and course reports. |
| Student | `student` | Course via cohort sync | Student learning access. |
| Parent | `parent` | Student user context | View linked child details/reports only. |

## Stream and division rule

- Stream is a category below Class/Std.
- Division is a group inside each subject course.
- Cohort is the enrolment batch for class + stream + division.

Example:

```text
GSEB → Green Valley School → Gujarati Medium → Std 11 → Science → Physics
Groups inside Physics: Division A, Division B
Cohort: GVS-2026-GSEB-GUJ-STD11-SCI-A
```

## Moodle profile field convention

Moodle user upload custom fields use CSV headers in the form:

```text
profile_field_shortname
```

This pack uses that same convention in `users_students.csv`, `users_staff.csv` and `users_parents.csv`.

## Production checklist

1. Replace sample school/trust data.
2. Replace sample users and emails.
3. Confirm board-specific subject/elective mapping in `grade_subject_matrix.csv`.
4. Confirm profile fields and visibility with school data-protection policy.
5. Do not store full Aadhaar in Moodle baseline.
6. Enable Moodle cohort enrolment plugin before importing enrolments.
7. Run `--dry-run=1` first.
8. Backup Moodle database before `--dry-run=0`.
9. Spot-check category tree, courses, cohorts, groups, users, parent links and role assignments.

## Notes

The subject matrix is a broad baseline for Gujarat Government/GSEB/CBSE/NCERT-style Indian school setup. It contains common core subjects, stream subjects and electives. Verify school-specific subjects, official board circulars and latest curriculum before production import.


## Academic year promotion / rollover add-on

After the first baseline import, use `README_ACADEMIC_YEAR_PROMOTION.md` for moving students to the next academic year/standard.

Additional files:

```text
README_ACADEMIC_YEAR_PROMOTION.md
promotion_actions.csv
promotion_policy.csv
student_status_codes.csv
student_academic_history_template.csv
academic_year_rollover_checklist.csv
archive_policy.csv
cli_promote_academic_year.php
run_promotion_dry_run_example.sh
run_promotion_execute_example.sh
```

Promotion sequence:

```text
1. Create next academic year courses/cohorts/groups/enrolments.
2. Fill promotion_actions.csv.
3. Run cli_promote_academic_year.php with --dry-run=1.
4. Fix missing cohorts/users.
5. Run with --dry-run=0.
6. Keep remove_from_old_cohort=0 until old-year reports/backups are complete.
```


## Universal course-template upgrade

Additional recommended order when using the master course template:

```text
1. Import/create categories, including Course Templates category.
2. Run cli_create_universal_master_course_template.php to create MASTER-ALL-GRADES-ALL-SUBJECTS-STD-TEMPLATE.
3. Create real subject courses using either:
   - cli_import_indian_school_baseline.php, then cli_apply_course_template_settings.php, or
   - Moodle native upload-course using courses_with_templatecourse_for_moodle_upload.csv.
4. Import cohorts, groups, users, role assignments, and cohort enrolments.
5. Teachers replace placeholder content and principal approves courses before making them visible.
```

Read:

```text
README_UNIVERSAL_COURSE_TEMPLATE.md
README_TEMPLATE_CLI_USAGE.md
```
