# CSV Column Guide and Dependency Reference

The chaptered documentation entry point is [README.md](README.md). Use this file as the detailed per-column reference for Chapter 03.

This chapter explains every CSV file in this pack, how each column is used by Moodle or by the helper CLI scripts, and which files must exist before another file can be imported.

Use this document when setting up a new school, trimming the pack for a smaller import, or troubleshooting missing courses, cohorts, users, sections, or reports.

If the school team prefers maintaining all CSV files from one workbook, use
[docs/08-master-excel-workbook.md](docs/08-master-excel-workbook.md) and the generated workbook at
`outputs/master-import-workbook/eLearnMindset_school_import_master.xlsx`.

## Safe Setup Order

Run imports in this order:

```text
1. Validate CSV files locally.
2. Run Moodle preflight.
3. Import baseline data with dry-run=1.
4. Import baseline data with dry-run=0.
5. Create the hidden master course template.
6. Apply section/template settings to existing courses.
7. Apply gradebook template.
8. Purge Moodle caches.
9. Verify counts and UI access.
```

Minimum command pattern:

```bash
PACK_HOST="$PWD/research/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
PACK_CONTAINER="/tmp/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
APP_CONTAINER="$(docker compose ps -q moodle)"

php "$PACK_HOST/cli_validate_school_baseline.php" --dir="$PACK_HOST"

cp "$PACK_HOST"/cli_*.php moodle/admin/cli/

docker compose exec -T moodle php admin/cli/cli_moodle502_preflight.php

docker compose exec -u root -T moodle rm -rf "$PACK_CONTAINER"
docker cp "$PACK_HOST" "$APP_CONTAINER:$PACK_CONTAINER"

docker compose exec -T moodle php admin/cli/cli_import_indian_school_baseline.php \
  --dir="$PACK_CONTAINER" \
  --dry-run=1

docker compose exec -T moodle php admin/cli/cli_import_indian_school_baseline.php \
  --dir="$PACK_CONTAINER" \
  --dry-run=0
```

## Dependency Map

Use this dependency chain when editing or importing selected CSV files:

```text
Master lookup CSVs
  01_school_master.csv
  02_academic_years.csv
  03_boards.csv
  04_mediums.csv
  05_grades.csv
  06_streams.csv
  07_divisions.csv
  08_subjects.csv
  09_grade_subject_matrix.csv

Moodle structure CSVs
  10_categories.csv depends on master lookup codes.
  12_courses.csv depends on 10_categories.csv and 09_grade_subject_matrix.csv.
  14_cohorts.csv depends on 10_categories.csv and 07_divisions.csv.
  15_groups.csv depends on 12_courses.csv and 07_divisions.csv.
  25_enrolments.csv depends on 12_courses.csv, 14_cohorts.csv, 15_groups.csv, and Moodle roles.

User and access CSVs
  16_user_profile_fields.csv must run before users_*.csv.
  17_custom_roles.csv must run before 23_role_assignments.csv and 24_parent_links.csv.
  19_users_staff.csv, 20_users_students.csv, 21_users_parents.csv depend on 16_user_profile_fields.csv.
  22_cohort_members.csv depends on 20_users_students.csv and 14_cohorts.csv.
  23_role_assignments.csv depends on 19_users_staff.csv, 17_custom_roles.csv, 10_categories.csv, and 12_courses.csv.
  24_parent_links.csv depends on 21_users_parents.csv, 20_users_students.csv, and 17_custom_roles.csv.

Course template CSVs
  30_master_course_template.csv depends on COURSE_TEMPLATES category settings.
  31_course_template_sections.csv drives section names and summaries.
  32_course_template_activities.csv depends on 31_course_template_sections.csv.
  37_course_template_application.csv depends on 12_courses.csv and 30_master_course_template.csv.
  33_course_template_gradebook.csv depends on 12_courses.csv or template application.

Academic-year rollover CSVs
  next_year_courses_*.csv depends on categories for the target academic year.
  next_year_cohorts_*.csv depends on target categories.
  next_year_groups_*.csv depends on target courses.
  next_year_enrolments_*.csv depends on target courses, target cohorts, and target groups.
  53_promotion_actions.csv depends on current users/cohorts and target cohorts.
```

For the full yearly operating procedure, including the rule that existing
students should not be re-registered, see
[docs/07-academic-year-rollover.md](docs/07-academic-year-rollover.md).

## Identifier Rules

Keep these identifiers stable. Moodle and the helper scripts use them for matching existing records and for idempotent reruns.

| Identifier | Where used | Purpose |
|---|---|---|
| `username` | user CSVs, role assignments, cohort members, parent links | Moodle login and user matching key. |
| `category_code` / `idnumber` | category, course, cohort files | Moodle course category matching key. |
| `course_code` | course, group, enrolment, template files | Business identifier for a course. |
| `shortname` / `course_shortname` | course, group, enrolment, role files | Moodle course shortname and CLI lookup key. |
| `cohort_code` / `idnumber` | cohort, enrolment, cohort member files | Moodle cohort matching key. |
| `group_idnumber` | group and enrolment files | Moodle group matching key inside a course. |
| `role_shortname` | custom roles, role assignments, enrolments | Moodle role lookup key. |
| `profile_field_*` | user CSVs | Moodle custom profile data mapped to `16_user_profile_fields.csv`. |

Boolean columns use `1` for yes/enabled/visible and `0` for no/disabled/hidden. Dates should use `YYYY-MM-DD`.

## CLI Scripts and CSV Usage

| Script | Main purpose | Primary CSV files read |
|---|---|---|
| `cli_validate_school_baseline.php` | Validates CSV structure and cross-file references before Moodle import. | Most baseline CSV files. |
| `cli_moodle502_preflight.php` | Checks Moodle features/plugins/config expected by the pack. | No data import; checks Moodle runtime. |
| `cli_import_indian_school_baseline.php` | Creates profile fields, roles, categories, courses, cohorts, groups, users, links, and enrolments. | `16_user_profile_fields.csv`, `17_custom_roles.csv`, `10_categories.csv`, `12_courses.csv`, `14_cohorts.csv`, `15_groups.csv`, `users_*.csv`, `22_cohort_members.csv`, `23_role_assignments.csv`, `24_parent_links.csv`, `25_enrolments.csv`. |
| `cli_create_universal_master_course_template.php` | Creates hidden reusable course template, chapter activities, and sequential chapter restricted-access rules. | `30_master_course_template.csv`, `31_course_template_sections.csv`, `32_course_template_activities.csv`. |
| `cli_apply_course_template_settings.php` | Applies section names, summaries, visibility, completion, and course display settings to existing courses. | `37_course_template_application.csv`, `31_course_template_sections.csv`. |
| `cli_apply_gradebook_template.php` | Applies gradebook category pattern to courses. | `33_course_template_gradebook.csv`, `34_grade_band_template_adjustments.csv`. |
| `cli_prepare_next_academic_year.php` | Creates next-year courses, cohorts, groups, and enrolment mappings. | `next_year_courses_*.csv`, `next_year_cohorts_*.csv`, `next_year_groups_*.csv`, `next_year_enrolments_*.csv`. |
| `cli_promote_students_academic_year.php` / `cli_promote_academic_year.php` | Moves or records students into next academic year cohorts based on promotion decisions. | `53_promotion_actions.csv`, `student_promotion_plan_*.csv`, promotion policy/status CSVs. |

## CSV Reference

### `01_school_master.csv`

Purpose: Defines the school/trust identity used across categories, users, reporting, and school-specific metadata.

Dependencies: none. Other files should reuse `trust_code`, `school_code`, `principal_username`, and `academic_year`.

| Column | Usage inside system |
|---|---|
| `trust_code` | Short code for the trust/organisation; used in category identifiers and reporting. |
| `trust_name` | Human-readable trust name. |
| `school_code` | School code used in course/cohort/category identifiers. |
| `school_name` | Human-readable school name shown in generated names. |
| `udise_code` | Indian school UDISE reference; metadata only unless integrated externally. |
| `affiliation_no` | Board affiliation number; metadata/reporting. |
| `school_type` | School type such as private/government/aided; metadata. |
| `address_line1` | School address. |
| `address_line2` | Additional school address. |
| `city` | School city. |
| `district` | School district. |
| `state` | School state. |
| `pincode` | Postal PIN code. |
| `phone` | School contact phone. |
| `email` | School contact email. |
| `website` | School website. |
| `principal_username` | Must match a staff user if used for principal assignments. |
| `academic_year` | Current academic year label used by course/cohort/user profile data. |

### `02_academic_years.csv`

Purpose: Defines academic-year windows and the current year.

Dependencies: none. Used by course, cohort, promotion, and rollover files.

| Column | Usage inside system |
|---|---|
| `academic_year` | Year label, for example `2026-2027`; referenced by courses/cohorts/users. |
| `start_date` | Year start date, useful for course start date and reporting. |
| `end_date` | Year end date, useful for archive/rollover decisions. |
| `is_current` | `1` marks the active school year. |

### `03_boards.csv`

Purpose: Defines boards such as GSEB, CBSE, NCERT, or school-specific government board models.

Dependencies: none. Referenced by category/course/cohort/user files.

| Column | Usage inside system |
|---|---|
| `board_code` | Short code used in identifiers and category tree. |
| `board_name` | Display name. |
| `board_type` | Type/category of board. |
| `country` | Country context. |
| `state` | State context where applicable. |
| `source_url` | Reference URL for validation or curriculum source. |

### `04_mediums.csv`

Purpose: Defines teaching mediums/languages.

Dependencies: none. Referenced by categories, courses, cohorts, and users.

| Column | Usage inside system |
|---|---|
| `medium_code` | Short code used in identifiers, for example `GUJ`, `ENG`, `HIN`. |
| `medium_name` | Display name. |
| `language_code` | Moodle/language reference where applicable. |

### `05_grades.csv`

Purpose: Defines school grades/classes.

Dependencies: none. Referenced by grade-subject matrix, categories, courses, cohorts, and users.

| Column | Usage inside system |
|---|---|
| `grade_code` | Short grade code such as `STD01`, `STD11`. |
| `grade_name` | Display name. |
| `display_order` | Sorting order in generated output and reports. |
| `stage` | Academic stage such as primary, secondary, higher secondary. |
| `moodle_label` | Friendly Moodle label used in category/course names. |

### `06_streams.csv`

Purpose: Defines streams such as General, Science, Commerce, Arts, and Vocational.

Dependencies: none. Referenced by categories, courses, cohorts, users, and promotion rules.

| Column | Usage inside system |
|---|---|
| `stream_code` | Short stream code used in identifiers. |
| `stream_name` | Display name. |
| `applies_to` | Grade range or stage where the stream is valid. |
| `notes` | Administrative guidance. |

### `07_divisions.csv`

Purpose: Defines class divisions used as Moodle groups inside courses.

Dependencies: none. Referenced by cohorts, groups, enrolments, and student users.

| Column | Usage inside system |
|---|---|
| `division_code` | Short code such as `A` or `B`. |
| `division_name` | Display name such as `Division A`. |
| `display_order` | Sorting order. |

### `08_subjects.csv`

Purpose: Defines subject master data.

Dependencies: none. Referenced by `09_grade_subject_matrix.csv` and course generation.

| Column | Usage inside system |
|---|---|
| `subject_code` | Short subject code used in course identifiers. |
| `subject_name` | Display name used in course full names. |
| `subject_category` | Groups subjects as core, language, stream, elective, activity, etc. |
| `notes` | Curriculum or implementation notes. |

### `09_grade_subject_matrix.csv`

Purpose: Defines which subjects are offered for each board, medium, grade, and stream combination.

Dependencies: `03_boards.csv`, `04_mediums.csv`, `05_grades.csv`, `06_streams.csv`, `08_subjects.csv`.

| Column | Usage inside system |
|---|---|
| `board_code` | Must match `03_boards.csv`. |
| `medium_code` | Must match `04_mediums.csv`. |
| `grade_code` | Must match `05_grades.csv`. |
| `stream_code` | Must match `06_streams.csv`. |
| `subject_code` | Must match `08_subjects.csv`; drives generated course identifiers. |
| `subject_name` | Display subject name used in generated course names. |
| `subject_category` | Subject classification for template/reporting logic. |
| `is_compulsory` | `1` means required subject. |
| `is_elective` | `1` means elective subject. |
| `display_order` | Sort order in course lists and generated CSV output. |
| `source_note` | Curriculum/source note for audit. |

### `10_categories.csv`

Purpose: Creates Moodle course categories for trust, board, school, medium, grade, stream, and optional subject/template levels.

Dependencies: master lookup codes. Parent rows must appear before child rows.

| Column | Usage inside system |
|---|---|
| `category_code` | Internal source key; often same as Moodle category idnumber. |
| `parent_category_code` | Parent category idnumber/code; blank for top-level categories. |
| `category_type` | Logical level such as trust, board, school, medium, grade, stream, template. |
| `name` | Moodle category display name. |
| `idnumber` | Moodle category matching key; used by courses/cohorts/role assignments. |
| `path` | Human-readable hierarchy path for review. |
| `visible` | `1` visible, `0` hidden. |
| `description` | Moodle category description. |

### `optional_year_category_model_10_categories.csv`

Purpose: Optional alternative category tree that includes academic-year nodes.

Dependencies: same as `10_categories.csv`.

| Column | Usage inside system |
|---|---|
| `category_code` | Internal category key. |
| `parent_category_code` | Parent category key/idnumber. |
| `category_type` | Logical category level. |
| `name` | Moodle category display name. |
| `idnumber` | Moodle category idnumber. |
| `path` | Review path. |
| `visible` | Visibility flag. |
| `description` | Category description. |

### `12_courses.csv`

Purpose: Creates Moodle subject courses.

Dependencies: `10_categories.csv` must already contain `category_code`; subject/grade/stream codes should be valid in lookup files.

| Column | Usage inside system |
|---|---|
| `course_code` | Business/source course identifier. |
| `fullname` | Moodle course full name. |
| `shortname` | Moodle course shortname; login/navigation and CLI matching key. |
| `idnumber` | Moodle course idnumber; used by integrations and role assignments. |
| `category_code` | Moodle category idnumber where course is created. |
| `board_code` | Reporting/filtering metadata. |
| `school_code` | Reporting/filtering metadata. |
| `medium_code` | Reporting/filtering metadata. |
| `grade_code` | Reporting/filtering metadata. |
| `stream_code` | Reporting/filtering metadata. |
| `subject_code` | Reporting/filtering metadata. |
| `subject_name` | Human-readable subject name. |
| `academic_year` | Academic year label. |
| `format` | Moodle course format, usually `topics`. |
| `numsections` | Number of sections created by Moodle. Controls default empty sections. |
| `visible` | `1` visible to users, `0` hidden. |
| `groupmode` | Moodle group mode; `1` separate groups is common for divisions. |
| `groupmodeforce` | Forces course group mode when `1`. |
| `summary` | Moodle course summary. |
| `templatecourse` | Optional template course shortname for Moodle upload-course flow. |
| `enablecompletion` | Enables completion tracking. |
| `showgrades` | Shows gradebook to users. |
| `showreports` | Shows activity reports when permitted. |
| `tags` | Comma-separated course tags or reporting tags. |
| `course_template_code` | Template mapping key. |
| `term` | Term/year label for reporting. |

### `13_courses_with_templatecourse_for_moodle_upload.csv`

Purpose: Alternative CSV for Moodle native course upload using an existing `templatecourse`.

Dependencies: categories and the template course must already exist.

| Column | Usage inside system |
|---|---|
| `shortname` | Moodle course shortname. |
| `fullname` | Moodle course full name. |
| `idnumber` | Moodle course idnumber. |
| `category_idnumber` | Target Moodle category idnumber. |
| `category_path` | Human-readable category path for review. |
| `visible` | Course visibility. |
| `format` | Course format. |
| `numsections` | Number of sections. |
| `enablecompletion` | Completion tracking setting. |
| `showgrades` | Gradebook visibility. |
| `showreports` | Report visibility. |
| `groupmode` | Course group mode. |
| `groupmodeforce` | Force group mode flag. |
| `templatecourse` | Existing template course shortname used by Moodle upload. |
| `summary` | Course summary. |
| `tags` | Course tags. |

### `14_cohorts.csv`

Purpose: Creates Moodle cohorts used to enrol divisions/classes into subject courses.

Dependencies: `10_categories.csv` for `context_category_code`; lookup codes should match school structure.

| Column | Usage inside system |
|---|---|
| `cohort_code` | Source key for cohort. |
| `name` | Moodle cohort display name. |
| `idnumber` | Moodle cohort matching key. |
| `context_category_code` | Category idnumber where cohort is scoped. |
| `board_code` | Reporting metadata. |
| `school_code` | Reporting metadata. |
| `medium_code` | Reporting metadata. |
| `grade_code` | Reporting metadata. |
| `stream_code` | Reporting metadata. |
| `division_code` | Division metadata. |
| `academic_year` | Academic year. |
| `visible` | Cohort visibility. |
| `description` | Cohort description. |

### `15_groups.csv`

Purpose: Creates Moodle groups inside each course, usually one group per division.

Dependencies: `12_courses.csv` must exist before groups are created.

| Column | Usage inside system |
|---|---|
| `course_code` | Source course identifier. |
| `course_shortname` | Moodle course shortname lookup. |
| `group_name` | Moodle group display name. |
| `group_idnumber` | Moodle group matching key inside course. |
| `board_code` | Reporting metadata. |
| `school_code` | Reporting metadata. |
| `medium_code` | Reporting metadata. |
| `grade_code` | Reporting metadata. |
| `stream_code` | Reporting metadata. |
| `division_code` | Division metadata. |
| `description` | Moodle group description. |

### `25_enrolments.csv`

Purpose: Creates Moodle cohort-sync enrolment mappings from cohorts into courses and optionally assigns students to groups.

Dependencies: `12_courses.csv`, `14_cohorts.csv`, `15_groups.csv`, and Moodle role `role_shortname`.

| Column | Usage inside system |
|---|---|
| `course_code` | Course source idnumber lookup. |
| `course_shortname` | Course shortname fallback lookup. |
| `cohort_code` | Cohort idnumber to sync into the course. |
| `role_shortname` | Moodle role assigned by the cohort enrolment, usually `student`. |
| `group_name` | Human-readable group name for review. |
| `group_idnumber` | Group idnumber for automatic group assignment. |
| `enrolment_method` | Expected enrolment method, usually `cohort`. |
| `status` | Desired status such as active/enabled. |

### `22_cohort_members.csv`

Purpose: Adds student users to cohorts.

Dependencies: `20_users_students.csv` and `14_cohorts.csv`.

| Column | Usage inside system |
|---|---|
| `username` | Existing Moodle username to add. |
| `cohort_code` | Existing Moodle cohort idnumber. |
| `role` | Informational role, usually `student`; cohort enrolment controls course role. |

### `16_user_profile_fields.csv`

Purpose: Creates Moodle custom user profile fields for student, parent, staff, address, consent, health, and promotion metadata.

Dependencies: must run before `19_users_staff.csv`, `20_users_students.csv`, and `21_users_parents.csv`.

| Column | Usage inside system |
|---|---|
| `shortname` | Moodle profile field shortname; referenced by `profile_field_SHORTNAME` columns. |
| `name` | Display label in Moodle user profile. |
| `category` | Moodle profile field category/group. |
| `datatype` | Moodle profile field type such as `text`, `menu`, `datetime`, `checkbox`. |
| `required` | Whether the field is required. |
| `locked` | Whether users can edit the field. |
| `visible` | Moodle visibility setting. |
| `forceunique` | Enforces uniqueness where Moodle supports it. |
| `signup` | Shows field on signup if enabled. |
| `defaultdata` | Default value. |
| `options` | Pipe-separated menu options for `menu` fields. |
| `sensitive` | Internal flag to mark PII/sensitive data for policy review. |
| `recommended_visibility` | Human-readable visibility guidance such as admin-only. |
| `notes` | Data-protection or admin notes. |

### `19_users_staff.csv`

Purpose: Creates staff users such as trustee, principal, and teachers.

Dependencies: `16_user_profile_fields.csv` must exist. Teacher course access is completed by `23_role_assignments.csv`.

| Column | Usage inside system |
|---|---|
| `username` | Moodle login and matching key. |
| `password` | Initial password for manual-auth users. |
| `firstname` | User first name. |
| `lastname` | User last name. |
| `email` | User email; should be unique in production. |
| `auth` | Moodle authentication method, usually `manual`. |
| `city` | User city. |
| `country` | Country code such as `IN`. |
| `timezone` | User timezone, for example `Asia/Kolkata`. |
| `lang` | Moodle language code. |
| `institution` | User institution field. |
| `department` | User department field. |
| `idnumber` | Staff idnumber. |
| `phone1` | Primary phone. |
| `phone2` | Secondary phone. |
| `address` | Staff address. |
| `profile_field_employee_code` | Custom profile field `employee_code`. |
| `profile_field_staff_designation` | Custom profile field `staff_designation`. |
| `profile_field_staff_department` | Custom profile field `staff_department`. |
| `profile_field_staff_joining_date` | Custom profile field `staff_joining_date`; date field. |
| `profile_field_staff_qualification` | Custom profile field `staff_qualification`. |
| `profile_field_staff_type` | Custom profile field `staff_type`. |
| `profile_field_aadhaar_last4` | Stores only last four digits, if policy allows. |
| `profile_field_aadhaar_masked` | Stores masked Aadhaar only, never full Aadhaar. |
| `profile_field_aadhaar_consent` | Consent status. |

### `20_users_students.csv`

Purpose: Creates student users and stores Indian-school academic, address, parent, health, consent, and promotion metadata.

Dependencies: `16_user_profile_fields.csv`; `22_cohort_members.csv` performs the actual cohort membership.

| Column | Usage inside system |
|---|---|
| `username` | Moodle login and matching key. |
| `password` | Initial password. |
| `firstname` | Student first name. |
| `lastname` | Student last name. |
| `email` | Student email or generated placeholder. |
| `auth` | Moodle authentication method. |
| `city` | City. |
| `country` | Country code. |
| `timezone` | User timezone. |
| `lang` | Moodle language. |
| `institution` | Institution/school. |
| `department` | Department/class label. |
| `idnumber` | Student identifier. |
| `phone1` | Primary contact. |
| `phone2` | Secondary contact. |
| `address` | Student address. |
| `cohort1` | Informational cohort reference; actual membership uses `22_cohort_members.csv`. |
| `board_code` | Current board metadata. |
| `school_code` | Current school metadata. |
| `medium_code` | Current medium metadata. |
| `grade_code` | Current grade metadata. |
| `stream_code` | Current stream metadata. |
| `division_code` | Current division metadata. |
| `profile_field_admission_no` | Admission number custom profile field. |
| `profile_field_roll_no` | Roll number custom profile field. |
| `profile_field_student_gr_no` | General Register number custom profile field. |
| `profile_field_birth_date` | Date of birth custom profile field. |
| `profile_field_gender` | Gender custom profile field. |
| `profile_field_blood_group` | Blood group custom profile field. |
| `profile_field_religion` | Religion custom profile field. |
| `profile_field_category` | Social category custom profile field. |
| `profile_field_caste` | Caste/community custom profile field. |
| `profile_field_nationality` | Nationality custom profile field. |
| `profile_field_mother_tongue` | Mother tongue custom profile field. |
| `profile_field_admission_date` | Admission date custom profile field. |
| `profile_field_apaar_id` | APAAR ID custom profile field if policy allows. |
| `profile_field_udise_student_code` | UDISE student code custom profile field. |
| `profile_field_saral_id` | SARAL/state student ID custom profile field. |
| `profile_field_aadhaar_last4` | Aadhaar last four digits only. |
| `profile_field_aadhaar_masked` | Masked Aadhaar value only. |
| `profile_field_aadhaar_consent` | Consent status. |
| `profile_field_aadhaar_vault_ref` | External secure vault reference, not the full number. |
| `profile_field_house` | School house. |
| `profile_field_transport_required` | Transport requirement flag. |
| `profile_field_bus_route` | Bus route. |
| `profile_field_pickup_point` | Pickup point. |
| `profile_field_rte_category` | RTE flag. |
| `profile_field_bpl` | BPL/EWS flag. |
| `profile_field_disability_status` | Disability status. |
| `profile_field_medical_conditions` | Medical conditions. |
| `profile_field_allergies` | Allergy notes. |
| `profile_field_doctor_name` | Family doctor name. |
| `profile_field_doctor_phone` | Family doctor phone. |
| `profile_field_sibling_admission_no` | Sibling admission number. |
| `profile_field_current_address_line1` | Current address line 1. |
| `profile_field_current_address_line2` | Current address line 2. |
| `profile_field_current_city` | Current city. |
| `profile_field_current_taluka` | Current taluka. |
| `profile_field_current_district` | Current district. |
| `profile_field_current_state` | Current state. |
| `profile_field_current_pincode` | Current PIN code. |
| `profile_field_permanent_address_line1` | Permanent address line 1. |
| `profile_field_permanent_address_line2` | Permanent address line 2. |
| `profile_field_permanent_city` | Permanent city. |
| `profile_field_permanent_taluka` | Permanent taluka. |
| `profile_field_permanent_district` | Permanent district. |
| `profile_field_permanent_state` | Permanent state. |
| `profile_field_permanent_pincode` | Permanent PIN code. |
| `profile_field_permanent_address_same` | Flag if permanent address equals current address. |
| `profile_field_father_name` | Father name. |
| `profile_field_father_mobile` | Father mobile. |
| `profile_field_father_email` | Father email. |
| `profile_field_father_occupation` | Father occupation. |
| `profile_field_father_qualification` | Father qualification. |
| `profile_field_mother_name` | Mother name. |
| `profile_field_mother_mobile` | Mother mobile. |
| `profile_field_mother_email` | Mother email. |
| `profile_field_mother_occupation` | Mother occupation. |
| `profile_field_mother_qualification` | Mother qualification. |
| `profile_field_guardian_name` | Guardian name. |
| `profile_field_guardian_mobile` | Guardian mobile. |
| `profile_field_guardian_email` | Guardian email. |
| `profile_field_guardian_occupation` | Guardian occupation. |
| `profile_field_guardian_qualification` | Guardian qualification. |
| `profile_field_emergency_contact_name` | Emergency contact name. |
| `profile_field_emergency_contact_relation` | Emergency contact relation. |
| `profile_field_emergency_contact_mobile` | Emergency mobile. |
| `profile_field_emergency_contact_alt_mobile` | Alternate emergency mobile. |
| `profile_field_current_academic_year` | Current academic year. |
| `profile_field_current_board_code` | Current board. |
| `profile_field_current_school_code` | Current school. |
| `profile_field_current_medium_code` | Current medium. |
| `profile_field_current_grade_code` | Current grade. |
| `profile_field_current_stream_code` | Current stream. |
| `profile_field_current_division_code` | Current division. |
| `profile_field_student_status` | Student lifecycle status. |
| `profile_field_previous_academic_year` | Previous academic year for promotion history. |
| `profile_field_previous_grade_code` | Previous grade. |
| `profile_field_previous_stream_code` | Previous stream. |
| `profile_field_previous_division_code` | Previous division. |
| `profile_field_last_promotion_date` | Last promotion date. |
| `profile_field_last_promotion_result` | Last promotion result. |

### `21_users_parents.csv`

Purpose: Creates parent/guardian users.

Dependencies: `16_user_profile_fields.csv`; `24_parent_links.csv` links parent users to student users.

| Column | Usage inside system |
|---|---|
| `username` | Parent Moodle login. |
| `password` | Initial password. |
| `firstname` | First name. |
| `lastname` | Last name. |
| `email` | Email. |
| `auth` | Authentication method. |
| `city` | City. |
| `country` | Country code. |
| `timezone` | Timezone. |
| `lang` | Moodle language. |
| `institution` | Institution/school. |
| `department` | Department label. |
| `idnumber` | Parent idnumber. |
| `phone1` | Primary phone. |
| `phone2` | Secondary phone. |
| `address` | Address. |
| `profile_field_parent_type` | Father/mother/guardian/other. |
| `profile_field_parent_occupation` | Occupation. |
| `profile_field_parent_qualification` | Qualification. |
| `profile_field_parent_annual_income` | Income range. |
| `profile_field_preferred_language` | Communication language. |
| `profile_field_consent_student_data` | Consent for linked student data access. |
| `profile_field_aadhaar_last4` | Aadhaar last four digits only, if collected. |
| `profile_field_aadhaar_masked` | Masked Aadhaar only. |
| `profile_field_aadhaar_consent` | Consent status. |

### `17_custom_roles.csv`

Purpose: Creates custom Moodle roles such as principal, trustee manager, and parent.

Dependencies: Moodle base roles must exist, for example `manager` or `user`.

| Column | Usage inside system |
|---|---|
| `role_shortname` | Moodle role shortname. |
| `role_name` | Human-readable role name. |
| `based_on_role` | Existing Moodle role to copy capabilities from. |
| `context_levels` | Pipe-separated allowed contexts such as `system|category|course|user`. |
| `capabilities_allow` | Pipe-separated capabilities to explicitly allow. |
| `description` | Moodle role description. |

### `23_role_assignments.csv`

Purpose: Assigns roles to staff/users in category, course, system, or user contexts.

Dependencies: users, roles, categories/courses must already exist.

| Column | Usage inside system |
|---|---|
| `username` | User receiving the role. |
| `role_shortname` | Existing Moodle role shortname. |
| `context_type` | Context type: `system`, `category`, `course`, or `user`. |
| `context_identifier` | Category idnumber, course idnumber/shortname, username, or system marker depending on context. |
| `notes` | Review notes. |

### `18_role_guidelines.csv`

Purpose: Human-readable role governance guide.

Dependencies: references `17_custom_roles.csv` and Moodle core roles.

| Column | Usage inside system |
|---|---|
| `school_role` | School-friendly role label. |
| `moodle_role_shortname` | Moodle role shortname. |
| `recommended_context` | Where role should be assigned. |
| `assign_to` | Type of user receiving the role. |
| `can_do` | Expected permissions/use cases. |
| `avoid` | Governance warning. |

### `24_parent_links.csv`

Purpose: Assigns the parent role in the student user context so parent users can view permitted child information.

Dependencies: parent users, student users, and `parent` role must already exist.

| Column | Usage inside system |
|---|---|
| `parent_username` | Existing parent Moodle username. |
| `student_username` | Existing student Moodle username. |
| `relationship` | Relationship label. |
| `role_shortname` | Moodle role, usually `parent`. |
| `allow_grade_view` | Policy flag for grade access. |
| `allow_activity_report_view` | Policy flag for activity report access. |
| `notes` | Review notes. |

### `30_master_course_template.csv`

Purpose: Defines the hidden master course template used for consistent sections and placeholder activities.

Dependencies: template category can be created by the template CLI if missing.

| Column | Usage inside system |
|---|---|
| `template_code` | Template identifier referenced by application files. |
| `shortname` | Moodle template course shortname. |
| `fullname` | Template course full name. |
| `idnumber` | Template course idnumber. |
| `category_code` | Template category idnumber. |
| `category_name` | Template category display name. |
| `format` | Course format. |
| `numsections` | Number of template sections. |
| `visible` | Usually `0` because template courses should be hidden. |
| `enablecompletion` | Completion tracking setting. |
| `showgrades` | Gradebook visibility setting. |
| `showreports` | Report visibility setting. |
| `groupmode` | Group mode. |
| `groupmodeforce` | Force group mode flag. |
| `lang` | Course language. |
| `tags` | Template tags. |
| `summary` | Template summary. |

### `31_course_template_sections.csv`

Purpose: Defines standard course section names, summaries, visibility, and teacher/student guidance.

Dependencies: used by master template script and apply-template script.

| Column | Usage inside system |
|---|---|
| `template_code` | Links section row to a template. |
| `section_number` | Moodle section number; `0` is general section. |
| `section_name` | Moodle section name shown instead of `New section`. |
| `purpose` | Section summary/purpose. |
| `default_visibility` | Section visible flag. |
| `completion_relevant` | Indicates whether section should affect completion planning. |
| `teacher_notes` | Teacher-facing implementation notes. |
| `student_instructions` | Student-facing guidance. |

### `32_course_template_activities.csv`

Purpose: Defines placeholder or native activities/resources for the master template. The current standard template uses 10 chapter sections. Each chapter includes overview, study material, discussion, practice quiz, assignment, and a completion gate.

Dependencies: `31_course_template_sections.csv`; recommended activity modules must exist if using native mode.

| Column | Usage inside system |
|---|---|
| `template_code` | Template identifier. |
| `section_number` | Section where activity belongs. |
| `item_order` | Sort order within section. |
| `activity_key` | Stable key for the activity pattern. |
| `recommended_activity_type` | Intended Moodle module type such as page, quiz, assignment, forum, H5P. |
| `safe_placeholder_module` | Safe fallback module, usually `page`. |
| `default_name` | Default activity/resource name. |
| `purpose` | Why the activity exists pedagogically. |
| `completion_mode` | Recommended completion behavior. |
| `completion_required` | Whether completion should be required. |
| `gradebook_category` | Gradebook category mapping. |
| `default_points` | Default grade points. |
| `visible` | Activity visibility. |
| `pii_safe_note` | Privacy note. |
| `completion_rule` | Logical completion rule used by the CLI. Supported values are `none`, `view`, `submit`, `grade`, `passgrade`, and `manual`. |
| `unlock_next` | `1` marks the activity as the chapter gate used to unlock the next chapter section. |
| `grade_to_pass` | Pass percentage used for pass-grade gates and final mock tests. For example `40` means 40 percent of the activity max grade. |

Sequential chapter behavior:

- Chapter 1 is open by default.
- Chapter 2 requires the Chapter 1 gate.
- Chapter 3 requires the Chapter 2 gate.
- The same rule continues through Chapter 10.
- Revision & Final Assessment requires the Chapter 10 gate.

For the operational runbook, see [docs/06-course-template-and-chapter-gates.md](docs/06-course-template-and-chapter-gates.md).

### `37_course_template_application.csv`

Purpose: Maps each generated course to the standard course template.

Dependencies: `12_courses.csv`, `30_master_course_template.csv`, `31_course_template_sections.csv`.

| Column | Usage inside system |
|---|---|
| `course_shortname` | Existing Moodle course shortname. |
| `course_code` | Existing course source code. |
| `course_fullname` | Review label. |
| `template_code` | Template code to apply. |
| `templatecourse_shortname` | Existing Moodle template course shortname. |
| `academic_year` | Course academic year. |
| `term` | Term/year label. |
| `grade_code` | Grade metadata. |
| `grade_band` | Grade band for template adjustments. |
| `subject_code` | Subject metadata. |
| `stream_code` | Stream metadata. |
| `visible_after_creation` | Whether course should be visible after applying template. |
| `enablecompletion` | Completion setting to apply. |
| `apply_sections` | Whether sections should be applied. |
| `apply_gradebook` | Whether gradebook template should be applied. |
| `apply_completion_defaults` | Whether completion defaults should be applied. |
| `certificate_policy_code` | Completion/certificate policy reference. |
| `notes` | Review notes. |

### `33_course_template_gradebook.csv`

Purpose: Defines gradebook category pattern and weights.

Dependencies: courses/template application; review weights before production.

| Column | Usage inside system |
|---|---|
| `template_code` | Template identifier. |
| `category_name` | Moodle gradebook category name. |
| `weight_percent` | Suggested category weight. |
| `default_pass_percent` | Suggested passing threshold. |
| `notes` | Review notes. |

### `34_grade_band_template_adjustments.csv`

Purpose: Adjusts gradebook weights by grade band.

Dependencies: `33_course_template_gradebook.csv`, grades/grade bands.

| Column | Usage inside system |
|---|---|
| `grade_band` | Band such as primary, secondary, higher secondary. |
| `grade_codes` | Grade codes included in the band. |
| `classwork_weight` | Suggested classwork and participation weight. |
| `practice_quiz_weight` | Suggested practice quiz weight. |
| `assignment_weight` | Suggested assignment weight. |
| `chapter_gate_weight` | Suggested chapter completion gate weight. |
| `final_weight` | Suggested final assessment weight. |
| `passing_grade_percent` | Suggested pass percentage. |
| `template_adjustment` | Notes about adjustment. |

### `35_subject_template_adjustments.csv`

Purpose: Defines subject-specific template additions.

Dependencies: `08_subjects.csv`, `31_course_template_sections.csv`.

| Column | Usage inside system |
|---|---|
| `subject_area` | Subject group. |
| `subject_codes` | Subject codes affected. |
| `recommended_template_additions` | Suggested additional sections/activities. |
| `default_extra_sections` | Suggested extra section count or names. |

### `36_completion_tracking_defaults.csv`

Purpose: Defines recommended Moodle completion settings by activity type.

Dependencies: referenced by template planning; not required for baseline import.

| Column | Usage inside system |
|---|---|
| `activity_type` | Moodle activity/resource type. |
| `completion_mode` | Logical completion mode. |
| `recommended_moodle_completion` | Moodle setting recommendation. |
| `grade_required` | Whether a grade is required. |
| `notes` | Implementation notes. |

### `40_certificate_badge_policy.csv`

Purpose: Documents certificate/badge behavior by policy code.

Dependencies: optional certificate/badge plugins if implemented.

| Column | Usage inside system |
|---|---|
| `policy_code` | Policy identifier. |
| `implementation` | How to implement certificate/badge behavior. |
| `requires_plugin` | Plugin requirement flag/name. |
| `visibility_rule` | When the certificate/badge should show. |
| `notes` | Review notes. |

### `38_course_template_custom_fields.csv`

Purpose: Defines proposed custom course fields for reporting/templates.

Dependencies: Moodle custom course fields may need manual/admin setup.

| Column | Usage inside system |
|---|---|
| `field_shortname` | Course custom field shortname. |
| `field_name` | Display label. |
| `datatype` | Field type. |
| `recommended_values` | Allowed or suggested values. |
| `notes` | Implementation notes. |

### `39_course_template_review_checklist.csv`

Purpose: Human review checklist for course templates.

Dependencies: none.

| Column | Usage inside system |
|---|---|
| `step` | Step/order number. |
| `phase` | Review phase. |
| `check_item` | Item to verify. |
| `owner` | Owner role/team. |
| `required` | Whether the check is mandatory. |

### `42_behat_course_template_coverage_mapping.csv`

Purpose: Maps template areas to Behat/test coverage.

Dependencies: Behat/testing workflow.

| Column | Usage inside system |
|---|---|
| `tested_area` | Feature area under test. |
| `template_support` | Template behavior covered. |
| `csv_file` | CSV file related to the test area. |

### `41_template_report_access_matrix.csv`

Purpose: Defines reporting/PII access expectations by role.

Dependencies: roles and Moodle reporting permissions.

| Column | Usage inside system |
|---|---|
| `role_shortname` | Moodle role. |
| `course_reports` | Expected course report access. |
| `completion_reports` | Expected completion report access. |
| `gradebook` | Expected gradebook access. |
| `pii_access` | PII access level. |
| `notes` | Governance notes. |

### `43_diksha_content_template.csv`

Purpose: Planning template for mapping DIKSHA or other external resources into Moodle courses.

Dependencies: courses and section structure.

| Column | Usage inside system |
|---|---|
| `board_code` | Board mapping. |
| `medium_code` | Medium mapping. |
| `grade_code` | Grade mapping. |
| `stream_code` | Stream mapping. |
| `subject_code` | Subject mapping. |
| `chapter` | Chapter label. |
| `title` | Resource title. |
| `diksha_identifier` | External DIKSHA identifier. |
| `content_type` | Content type. |
| `resource_type` | Moodle resource/activity target type. |
| `language` | Resource language. |
| `license` | License information. |
| `attribution` | Attribution text. |
| `source_url` | Source page URL. |
| `artifact_url` | Artifact URL. |
| `download_url` | Download URL if permitted. |
| `moodle_course_code` | Target Moodle course code. |
| `moodle_section` | Target section number/name. |
| `import_mode` | Link/upload/manual review mode. |
| `status` | Workflow status. |

### `54_next_year_courses_2027_2028.csv`

Purpose: Creates next academic year courses during rollover preparation.

Dependencies: target-year categories.

| Column | Usage inside system |
|---|---|
| `course_code` | Next-year source course identifier. |
| `fullname` | Moodle course full name. |
| `shortname` | Moodle course shortname. |
| `idnumber` | Moodle course idnumber. |
| `category_code` | Target category idnumber. |
| `board_code` | Reporting metadata. |
| `school_code` | Reporting metadata. |
| `medium_code` | Reporting metadata. |
| `grade_code` | Reporting metadata. |
| `stream_code` | Reporting metadata. |
| `subject_code` | Reporting metadata. |
| `subject_name` | Subject display name. |
| `academic_year` | Target academic year. |
| `format` | Course format. |
| `numsections` | Section count. |
| `visible` | Visibility. |
| `groupmode` | Group mode. |
| `groupmodeforce` | Force group mode flag. |
| `summary` | Course summary. |

### `55_next_year_cohorts_2027_2028.csv`

Purpose: Creates target-year cohorts.

Dependencies: target-year categories.

| Column | Usage inside system |
|---|---|
| `cohort_code` | Target cohort source key. |
| `name` | Cohort display name. |
| `idnumber` | Moodle cohort idnumber. |
| `context_category_code` | Category context idnumber. |
| `board_code` | Reporting metadata. |
| `school_code` | Reporting metadata. |
| `medium_code` | Reporting metadata. |
| `grade_code` | Reporting metadata. |
| `stream_code` | Reporting metadata. |
| `division_code` | Division metadata. |
| `academic_year` | Target academic year. |
| `visible` | Cohort visibility. |
| `description` | Cohort description. |

### `56_next_year_groups_2027_2028.csv`

Purpose: Creates groups inside target-year courses.

Dependencies: target-year courses.

| Column | Usage inside system |
|---|---|
| `course_code` | Target course code. |
| `course_shortname` | Target course shortname. |
| `group_name` | Group display name. |
| `group_idnumber` | Group idnumber. |
| `board_code` | Reporting metadata. |
| `school_code` | Reporting metadata. |
| `medium_code` | Reporting metadata. |
| `grade_code` | Reporting metadata. |
| `stream_code` | Reporting metadata. |
| `division_code` | Division metadata. |
| `description` | Group description. |

### `57_next_year_enrolments_2027_2028.csv`

Purpose: Creates target-year cohort enrolment mappings.

Dependencies: target-year courses, cohorts, and groups.

| Column | Usage inside system |
|---|---|
| `course_code` | Target course code. |
| `course_shortname` | Target course shortname. |
| `cohort_code` | Target cohort idnumber. |
| `role_shortname` | Moodle role, usually `student`. |
| `group_name` | Group display label. |
| `group_idnumber` | Group idnumber. |
| `enrolment_method` | Expected method, usually `cohort`. |
| `status` | Desired enrolment status. |

### `58_alumni_cohorts_2027.csv`

Purpose: Defines alumni/exit cohorts for students leaving the active school structure.

Dependencies: categories and promotion workflow.

| Column | Usage inside system |
|---|---|
| `cohort_code` | Alumni cohort source key. |
| `name` | Cohort display name. |
| `idnumber` | Moodle cohort idnumber. |
| `context_category_code` | Category context. |
| `board_code` | Reporting metadata. |
| `school_code` | Reporting metadata. |
| `medium_code` | Reporting metadata. |
| `grade_code` | Prior/exit grade metadata. |
| `stream_code` | Stream metadata. |
| `division_code` | Division metadata. |
| `academic_year` | Alumni transition year. |
| `visible` | Cohort visibility. |
| `description` | Cohort description. |

### `53_promotion_actions.csv`

Purpose: Action file for promoting, retaining, transferring, or marking students as alumni.

Dependencies: existing students/cohorts and target cohorts.

| Column | Usage inside system |
|---|---|
| `action` | Promotion action type. |
| `username` | Student username. |
| `student_idnumber` | Student idnumber for verification. |
| `from_academic_year` | Current year. |
| `to_academic_year` | Target year. |
| `from_cohort_code` | Current cohort idnumber. |
| `to_cohort_code` | Target cohort idnumber. |
| `from_board_code` | Current board. |
| `from_school_code` | Current school. |
| `from_medium_code` | Current medium. |
| `from_grade_code` | Current grade. |
| `from_stream_code` | Current stream. |
| `from_division_code` | Current division. |
| `to_board_code` | Target board. |
| `to_school_code` | Target school. |
| `to_medium_code` | Target medium. |
| `to_grade_code` | Target grade. |
| `to_stream_code` | Target stream. |
| `to_division_code` | Target division. |
| `result_status` | Result such as promoted, retained, left, transferred. |
| `remove_from_old_cohort` | Whether to remove old cohort membership. |
| `update_profile_fields` | Whether to update current academic profile fields. |
| `effective_date` | Effective promotion date. |
| `approved_by` | Approver username/name. |
| `remarks` | Notes. |

### `52_student_promotion_plan_2027_2028.csv`

Purpose: Detailed student-by-student promotion plan for the next academic year.

Dependencies: current students/cohorts, target cohorts, promotion policy.

| Column | Usage inside system |
|---|---|
| `student_username` | Student username. |
| `student_idnumber` | Student idnumber. |
| `firstname` | Review name. |
| `lastname` | Review name. |
| `current_academic_year` | Current year. |
| `target_academic_year` | Target year. |
| `current_board_code` | Current board. |
| `current_school_code` | Current school. |
| `current_medium_code` | Current medium. |
| `current_grade_code` | Current grade. |
| `current_stream_code` | Current stream. |
| `current_division_code` | Current division. |
| `current_cohort_code` | Current cohort. |
| `result_status` | Result status. |
| `promotion_decision` | Promote/retain/transfer/alumni decision. |
| `target_board_code` | Target board. |
| `target_school_code` | Target school. |
| `target_medium_code` | Target medium. |
| `target_grade_code` | Target grade. |
| `target_stream_code` | Target stream. |
| `target_division_code` | Target division. |
| `target_cohort_code` | Target cohort. |
| `new_roll_no` | New roll number. |
| `effective_date` | Effective date. |
| `remove_from_previous_cohort` | Whether to remove old cohort membership. |
| `update_user_profile` | Whether to update student profile fields. |
| `notify_parent` | Whether parent communication is expected. |
| `remarks` | Notes. |

### `51_student_academic_history_template.csv`

Purpose: Template for exporting or maintaining student academic history outside core Moodle tables.

Dependencies: student users, academic year, cohort data.

| Column | Usage inside system |
|---|---|
| `history_id` | External history record key. |
| `username` | Student username. |
| `academic_year` | Academic year. |
| `board_code` | Board. |
| `school_code` | School. |
| `medium_code` | Medium. |
| `grade_code` | Grade. |
| `stream_code` | Stream. |
| `division_code` | Division. |
| `cohort_code` | Cohort. |
| `status` | Student status. |
| `result` | Result/promotion result. |
| `effective_from` | Start date. |
| `effective_to` | End date. |
| `created_by` | Creator/approver. |
| `created_on` | Creation date. |
| `notes` | Notes. |

### `45_academic_year_promotion_rules.csv`

Purpose: Defines rule-based promotion decisions by grade/stream/result.

Dependencies: grades, streams, promotion status codes.

| Column | Usage inside system |
|---|---|
| `rule_code` | Promotion rule identifier. |
| `from_grade_code` | Source grade. |
| `from_stream_code` | Source stream. |
| `result_status` | Student result status. |
| `promotion_decision` | Decision output. |
| `to_grade_code` | Target grade. |
| `to_stream_code` | Target stream. |
| `requires_manual_review` | Manual review flag. |
| `notes` | Rule notes. |

### `44_academic_year_transition_models.csv`

Purpose: Documents possible academic-year rollover models.

Dependencies: none.

| Column | Usage inside system |
|---|---|
| `model_code` | Model identifier. |
| `model_name` | Model display name. |
| `category_structure` | How categories are organised. |
| `student_move_method` | How students move between cohorts. |
| `when_to_use` | Usage guidance. |
| `advantages` | Benefits. |
| `risks_or_notes` | Risks or cautions. |
| `recommended` | Whether this model is recommended. |

### `46_academic_year_rollover_checklist.csv`

Purpose: Operational checklist for rollover.

Dependencies: rollover process.

| Column | Usage inside system |
|---|---|
| `step_no` | Step number. |
| `phase` | Rollover phase. |
| `task` | Task to perform. |
| `owner_role` | Responsible role. |
| `required_before_next_step` | Whether task blocks the next step. |
| `notes` | Notes. |

### `47_promotion_policy.csv`

Purpose: Human-readable promotion policy reference.

Dependencies: promotion workflow.

| Column | Usage inside system |
|---|---|
| `rule_code` | Policy rule key. |
| `area` | Policy area. |
| `recommended_setup` | Recommended Moodle/data setup. |
| `why` | Reason. |
| `example` | Example. |

### `48_promotion_status_codes.csv`

Purpose: Defines promotion status labels and default Moodle action.

Dependencies: promotion action files.

| Column | Usage inside system |
|---|---|
| `code` | Status code. |
| `name` | Display label. |
| `moodle_action` | Recommended Moodle operation. |

### `50_student_status_codes.csv`

Purpose: Defines student lifecycle statuses.

Dependencies: student profile fields and promotion workflow.

| Column | Usage inside system |
|---|---|
| `status_code` | Student status code. |
| `meaning` | Meaning of status. |
| `default_moodle_action` | Recommended Moodle action. |
| `notes` | Notes. |

### `promotion_27_validation_rules.csv`

Purpose: Defines validation checks before promotion execution.

Dependencies: promotion files.

| Column | Usage inside system |
|---|---|
| `rule` | Validation rule key/name. |
| `severity` | Error/warning level. |
| `description` | What is checked. |
| `example_fix` | How to fix. |

### `59_archive_policy.csv`

Purpose: Defines what to archive after academic-year completion.

Dependencies: backup/rollover process.

| Column | Usage inside system |
|---|---|
| `archive_item` | Item to archive. |
| `recommended_action` | Recommended action. |
| `when_to_do` | Timing guidance. |
| `risk_if_skipped` | Risk if not archived. |

### `26_lookup_values.csv`

Purpose: Generic lookup table for values used by other CSVs or future UI/reporting.

Dependencies: none.

| Column | Usage inside system |
|---|---|
| `lookup_type` | Lookup group. |
| `code` | Lookup code. |
| `label` | Display label. |

### `27_validation_rules.csv`

Purpose: Documents CSV validation rules.

Dependencies: validation CLI and CSV quality process.

| Column | Usage inside system |
|---|---|
| `file` | CSV file being validated. |
| `field` | Field/column being validated. |
| `rule` | Validation rule. |
| `severity` | Error/warning severity. |

### `61_compatibility_matrix.csv`

Purpose: Documents Moodle version compatibility assumptions.

Dependencies: Moodle version and installed plugins.

| Column | Usage inside system |
|---|---|
| `component` | Feature/component being checked. |
| `moodle_feature` | Moodle feature required. |
| `compatible_moodle_5_0_2` | Compatibility status for Moodle 5.0.2. |
| `compatible_moodle_5_2_branch_502` | Compatibility status for Moodle 5.2/docs branch 502. |
| `dependency` | Required plugin/config/dependency. |
| `recommendation` | Recommendation before production. |

### `28_source_references.csv`

Purpose: Stores source references used to design the pack.

Dependencies: none.

| Column | Usage inside system |
|---|---|
| `source_name` | Source label. |
| `url` | Source URL. |
| `used_for` | What the source supports. |

### `29_summary.csv`

Purpose: High-level count summary for quick verification.

Dependencies: generated from/related to other CSVs.

| Column | Usage inside system |
|---|---|
| `metric` | Count or feature label. |
| `value` | Expected value/count. |

### `60_improvement_backlog.csv`

Purpose: Product/implementation backlog for improving the pack.

Dependencies: none.

| Column | Usage inside system |
|---|---|
| `priority` | Priority level. |
| `area` | Improvement area. |
| `improvement` | Proposed improvement. |
| `why_it_helps` | Reason/benefit. |
| `implementation_hint` | Suggested implementation approach. |

## Selective Import Guidance

Use the existing importer flags for large blocks:

```bash
--skip-users=1
--skip-courses=1
--skip-enrolments=1
```

For a specific item import, create a smaller copy of the pack and keep only the required rows plus their dependencies.

Example: import one teacher into one course.

Required rows/files:

```text
16_user_profile_fields.csv        keep all required fields
17_custom_roles.csv               keep roles used by role_assignments
10_categories.csv                 keep course category path
12_courses.csv                    keep target course row
19_users_staff.csv                keep teacher row
23_role_assignments.csv           keep teacher course assignment row
```

If students must also be enrolled:

```text
14_cohorts.csv                    keep target cohort row
15_groups.csv                     keep group rows for the target course
20_users_students.csv             keep student rows
22_cohort_members.csv             keep student-to-cohort rows
25_enrolments.csv                 keep cohort-to-course rows
```

Always keep CSV headers even when only one data row is imported.

## Production Data Notes

- Replace all sample names, emails, phone numbers, school identifiers, and generated users before production.
- Do not store full Aadhaar numbers in Moodle. Use only masked/last-four/reference fields if legally approved.
- Keep `shortname`, `idnumber`, `category_code`, `cohort_code`, and `group_idnumber` stable after import. Changing these breaks idempotent reruns and references.
- Run `--dry-run=1` every time after editing CSVs.
- Take a database and moodledata backup before a production `--dry-run=0`.
