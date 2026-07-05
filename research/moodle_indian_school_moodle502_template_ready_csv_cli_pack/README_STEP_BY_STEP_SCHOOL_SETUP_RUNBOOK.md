# Step-by-Step School Setup and Selective Import Runbook

Use this runbook when setting up a new Indian school Moodle structure or when importing only a specific division, student batch, teacher assignment, parent link, or enrolment mapping.

This document is command-first. For complete column definitions, read `README_CSV_COLUMN_GUIDE.md`.

## 1. Paths and Common Variables

Run commands from the repository root.

```bash
cd /Users/hardik.chauhan/Documents/learning/eLearnMindset

PACK_HOST="$PWD/research/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
RUNNER="$PACK_HOST/run_school_setup_master.sh"
PACK_CONTAINER="/tmp/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
```

The Docker Moodle service name is `moodle` by default. If it changes:

```bash
MOODLE_SERVICE=moodle
```

## 2. Required Runtime Dependencies

Before any import:

```bash
docker compose up -d
docker compose ps
```

Required local tools:

| Tool | Why it is needed |
|---|---|
| `docker compose` | Runs Moodle, PostgreSQL, Redis, and Mailpit. |
| `php` on host | Runs host CSV validators. |
| Moodle CLI inside container | Runs the actual Moodle import scripts. |
| Existing Moodle database | Import scripts create Moodle records in the running site. |

Required Moodle plugins/features:

| Moodle feature | Required for |
|---|---|
| Manual authentication | Local sample users. |
| Manual enrolment | Direct staff role assignment in course context. |
| Cohort enrolment | Student cohort-to-course enrolment. |
| Completion tracking | Chapter template completion rules. |
| Restricted access | Sequential chapter gates. |

Check Moodle readiness:

```bash
"$RUNNER" preflight
```

## 3. Master Runner Commands

The helper script is:

```text
research/moodle_indian_school_moodle502_template_ready_csv_cli_pack/run_school_setup_master.sh
```

Show available commands:

```bash
"$RUNNER" help
```

Prepare the running container:

```bash
"$RUNNER" prepare
```

This copies:

- `cli_*.php` to `moodle/admin/cli/`
- the selected CSV pack to the Moodle container at `$PACK_CONTAINER`

Validate CSVs:

```bash
"$RUNNER" validate
```

Full baseline import:

```bash
"$RUNNER" baseline-dry-run
"$RUNNER" baseline-import
```

Full one-command setup:

```bash
"$RUNNER" full-baseline
```

Use `full-baseline` only after reviewing CSV data. It imports baseline data, creates the master course template, applies template settings, applies gradebook settings, and purges caches.

## 4. Recommended Full New School Setup Sequence

### Step 1: Edit master source CSV files

Update these first:

```text
school_master.csv
academic_years.csv
boards.csv
mediums.csv
grades.csv
streams.csv
divisions.csv
subjects.csv
grade_subject_matrix.csv
```

Example: add a CBSE English medium school:

```csv
board_code,board_name,board_type,country,state,source_url
CBSE,Central Board of Secondary Education,Central Board,India,All India,https://cbseacademic.nic.in/
```

```csv
medium_code,medium_name,language_code
ENG,English Medium,en
```

```csv
trust_code,trust_name,school_code,school_name,udise_code,affiliation_no,school_type,address_line1,address_line2,city,district,state,pincode,phone,email,website,principal_username,academic_year
EDU_TRUST,EducateMe Education Trust,EMS,EducateMe School,24070000001,CBSE-SAMPLE,Co-educational,School Campus,Main Road,Ahmedabad,Ahmedabad,Gujarat,380001,07900000000,info@educateme.example.org,https://educateme.example.org,principal.ems,2026-2027
```

### Step 2: Create Moodle structure CSV rows

These files build the Moodle category/course/cohort/group/enrolment structure:

```text
categories.csv
courses.csv
cohorts.csv
groups.csv
enrolments.csv
```

Dependency order:

```text
categories.csv
  -> courses.csv
  -> cohorts.csv
  -> groups.csv
  -> enrolments.csv
```

Example category path:

```csv
category_code,parent_category_code,category_type,name,idnumber,path,visible,description
EDU_TRUST,,TRUST,EducateMe Education Trust,EDU_TRUST,EducateMe Education Trust,1,Trust category
EDU_TRUST_CBSE,EDU_TRUST,BOARD,CBSE,EDU_TRUST_CBSE,EducateMe Education Trust/CBSE,1,Board category
EDU_TRUST_CBSE_EMS,EDU_TRUST_CBSE,SCHOOL,EducateMe School,EDU_TRUST_CBSE_EMS,EducateMe Education Trust/CBSE/EducateMe School,1,School category
EDU_TRUST_CBSE_EMS_ENG,EDU_TRUST_CBSE_EMS,MEDIUM,English Medium,EDU_TRUST_CBSE_EMS_ENG,EducateMe Education Trust/CBSE/EducateMe School/English Medium,1,Medium category
EDU_TRUST_CBSE_EMS_ENG_STD05_GEN,EDU_TRUST_CBSE_EMS_ENG,STREAM,Std 5 - General,EDU_TRUST_CBSE_EMS_ENG_STD05_GEN,EducateMe Education Trust/CBSE/EducateMe School/English Medium/Std 5/General,1,Std 5 General category
```

Example course:

```csv
course_code,fullname,shortname,idnumber,category_code,board_code,school_code,medium_code,grade_code,stream_code,subject_code,subject_name,academic_year,format,numsections,visible,groupmode,groupmodeforce,summary,templatecourse,enablecompletion,showgrades,showreports,tags,course_template_code,term
EMS-CBSE-ENG-STD05-GEN-MATH-2026,EducateMe School - CBSE - English Medium - Std 5 - Mathematics - 2026-2027,EMS-CBSE-ENG-STD05-GEN-MATH-26,EMS-CBSE-ENG-STD05-GEN-MATH-2026,EDU_TRUST_CBSE_EMS_ENG_STD05_GEN,CBSE,EMS,ENG,STD05,GEN,MATH,Mathematics,2026-2027,topics,12,1,1,1,Std 5 Mathematics course.,MASTER-ALL-GRADES-ALL-SUBJECTS-STD-TEMPLATE,1,1,1,"CBSE,ENG,STD05,GEN,MATH",MASTER_ALL_GRADES_ALL_SUBJECTS_STANDARD,Academic Year 2026-2027
```

Example cohort for Division A:

```csv
cohort_code,name,idnumber,context_category_code,board_code,school_code,medium_code,grade_code,stream_code,division_code,academic_year,visible,description
EMS-2026-CBSE-ENG-STD05-GEN-A,EducateMe School 2026-2027 CBSE ENG STD05 GEN Division A,EMS-2026-CBSE-ENG-STD05-GEN-A,EDU_TRUST_CBSE_EMS_ENG_STD05_GEN,CBSE,EMS,ENG,STD05,GEN,A,2026-2027,1,Student cohort for Std 5 Division A
```

Example group inside the Mathematics course:

```csv
course_code,course_shortname,group_name,group_idnumber,board_code,school_code,medium_code,grade_code,stream_code,division_code,description
EMS-CBSE-ENG-STD05-GEN-MATH-2026,EMS-CBSE-ENG-STD05-GEN-MATH-26,Division A,EMS-CBSE-ENG-STD05-GEN-MATH-2026-A,CBSE,EMS,ENG,STD05,GEN,A,Division A group
```

Example cohort enrolment:

```csv
course_code,course_shortname,cohort_code,role_shortname,group_name,group_idnumber,enrolment_method,status
EMS-CBSE-ENG-STD05-GEN-MATH-2026,EMS-CBSE-ENG-STD05-GEN-MATH-26,EMS-2026-CBSE-ENG-STD05-GEN-A,student,Division A,EMS-CBSE-ENG-STD05-GEN-MATH-2026-A,cohort_sync,active
```

### Step 3: Validate and dry-run

```bash
"$RUNNER" validate
"$RUNNER" preflight
"$RUNNER" baseline-dry-run
```

### Step 4: Execute import

```bash
"$RUNNER" baseline-import
```

### Step 5: Create course template and gradebook

```bash
"$RUNNER" template-reset-import
"$RUNNER" apply-template-import
"$RUNNER" gradebook-import
"$RUNNER" purge-cache
```

## 5. Selective Import Pattern

For selective imports, do not run the full pack. Create a small pack with only the rows you want.

Create an empty selective pack:

```bash
SELECTIVE_PACK="/tmp/ems-std05-a-users"
"$RUNNER" new-selective-pack "$SELECTIVE_PACK"
```

Then edit only the required CSV files in `$SELECTIVE_PACK`.

Run with the selected pack:

```bash
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" prepare
```

## 6. Import Only Students for One Division

Use this when the course/cohort/group/enrolment structure already exists and you only need to register students into one division.

Required existing Moodle records:

| Record | Example |
|---|---|
| Cohort | `EMS-2026-CBSE-ENG-STD05-GEN-A` |
| Cohort enrolment mappings | Cohort mapped to each Std 5 subject course |
| Profile fields | Created by first baseline import or included in selective pack |

Required CSV files in selective pack:

```text
users_students.csv
cohort_members.csv
```

Optional CSV files:

```text
users_parents.csv
parent_links.csv
```

Example `users_students.csv`:

```csv
username,password,firstname,lastname,email,auth,city,country,timezone,lang,institution,department,idnumber,phone1,phone2,address,cohort1,board_code,school_code,medium_code,grade_code,stream_code,division_code,profile_field_admission_no,profile_field_roll_no,profile_field_current_academic_year,profile_field_current_board_code,profile_field_current_school_code,profile_field_current_medium_code,profile_field_current_grade_code,profile_field_current_stream_code,profile_field_current_division_code,profile_field_student_status
ems.cbse.eng.std05.a.001,ChangeMe@123,Aarav,Patel,ems.cbse.eng.std05.a.001@students.ems.example.org,manual,Ahmedabad,IN,Asia/Kolkata,en,EducateMe School,STD05-GEN-A,EMS.CBSE.ENG.STD05.A.001,9999900001,,Ahmedabad,EMS-2026-CBSE-ENG-STD05-GEN-A,CBSE,EMS,ENG,STD05,GEN,A,EMS-ADM-0001,1,2026-2027,CBSE,EMS,ENG,STD05,GEN,A,Active
ems.cbse.eng.std05.a.002,ChangeMe@123,Kavya,Shah,ems.cbse.eng.std05.a.002@students.ems.example.org,manual,Ahmedabad,IN,Asia/Kolkata,en,EducateMe School,STD05-GEN-A,EMS.CBSE.ENG.STD05.A.002,9999900002,,Ahmedabad,EMS-2026-CBSE-ENG-STD05-GEN-A,CBSE,EMS,ENG,STD05,GEN,A,EMS-ADM-0002,2,2026-2027,CBSE,EMS,ENG,STD05,GEN,A,Active
```

Example `cohort_members.csv`:

```csv
username,cohort_code,role
ems.cbse.eng.std05.a.001,EMS-2026-CBSE-ENG-STD05-GEN-A,student
ems.cbse.eng.std05.a.002,EMS-2026-CBSE-ENG-STD05-GEN-A,student
```

Dry-run and import:

```bash
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" users-dry-run
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" users-import
```

What this does:

- Creates or updates only users from `users_staff.csv`, `users_students.csv`, and `users_parents.csv`.
- Adds listed students to listed cohorts.
- Creates parent links if `parent_links.csv` contains rows.
- Skips category, course, cohort, group, and enrolment creation.

## 7. Import Students With Parents

Use the same student files as Section 6, plus:

```text
users_parents.csv
parent_links.csv
```

Example `users_parents.csv`:

```csv
username,password,firstname,lastname,email,auth,city,country,timezone,lang,institution,department,idnumber,phone1,phone2,address,profile_field_parent_type,profile_field_parent_occupation,profile_field_parent_qualification,profile_field_preferred_language,profile_field_consent_student_data
parent.ems.cbse.eng.std05.a.001,ChangeMe@123,Patel,Parent,parent.ems.cbse.eng.std05.a.001@parents.ems.example.org,manual,Ahmedabad,IN,Asia/Kolkata,en,EducateMe School,Parent,EMS-PAR-0001,9876500001,,Ahmedabad,Father,Service,Graduate,English,1
```

Example `parent_links.csv`:

```csv
parent_username,student_username,relationship,role_shortname,allow_grade_view,allow_activity_report_view,notes
parent.ems.cbse.eng.std05.a.001,ems.cbse.eng.std05.a.001,Father,parent,1,1,Parent can view linked child reports.
```

Run:

```bash
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" users-dry-run
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" users-import
```

## 8. Import Only a New Division

Use this when a grade already exists but a new division, for example Division C, must be added.

Update source-of-truth:

```text
divisions.csv
```

Required import CSV rows:

```text
cohorts.csv
groups.csv
enrolments.csv
```

Example `cohorts.csv`:

```csv
cohort_code,name,idnumber,context_category_code,board_code,school_code,medium_code,grade_code,stream_code,division_code,academic_year,visible,description
EMS-2026-CBSE-ENG-STD05-GEN-C,EducateMe School 2026-2027 CBSE ENG STD05 GEN Division C,EMS-2026-CBSE-ENG-STD05-GEN-C,EDU_TRUST_CBSE_EMS_ENG_STD05_GEN,CBSE,EMS,ENG,STD05,GEN,C,2026-2027,1,Student cohort for Std 5 Division C
```

Example `groups.csv` for one course:

```csv
course_code,course_shortname,group_name,group_idnumber,board_code,school_code,medium_code,grade_code,stream_code,division_code,description
EMS-CBSE-ENG-STD05-GEN-MATH-2026,EMS-CBSE-ENG-STD05-GEN-MATH-26,Division C,EMS-CBSE-ENG-STD05-GEN-MATH-2026-C,CBSE,EMS,ENG,STD05,GEN,C,Division C group
```

Example `enrolments.csv`:

```csv
course_code,course_shortname,cohort_code,role_shortname,group_name,group_idnumber,enrolment_method,status
EMS-CBSE-ENG-STD05-GEN-MATH-2026,EMS-CBSE-ENG-STD05-GEN-MATH-26,EMS-2026-CBSE-ENG-STD05-GEN-C,student,Division C,EMS-CBSE-ENG-STD05-GEN-MATH-2026-C,cohort_sync,active
```

Run:

```bash
SELECTIVE_PACK="/tmp/ems-std05-division-c"
"$RUNNER" new-selective-pack "$SELECTIVE_PACK"

# Edit cohorts.csv, groups.csv, and enrolments.csv in $SELECTIVE_PACK.

PACK_HOST="$SELECTIVE_PACK" "$RUNNER" structure-dry-run
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" structure-import
```

## 9. Import Only Enrolment Mappings

Use this when courses, cohorts, and groups already exist but the cohort sync mapping is missing.

Required CSV:

```text
enrolments.csv
```

Run:

```bash
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" enrolments-dry-run
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" enrolments-import
```

## 10. Assign One Teacher to One Course

Required existing Moodle records:

| Record | Example |
|---|---|
| Course | `EMS-CBSE-ENG-STD05-GEN-MATH-26` |
| Role | `editingteacher` |

Required CSV files:

```text
users_staff.csv
role_assignments.csv
```

Example `users_staff.csv`:

```csv
username,password,firstname,lastname,email,auth,city,country,timezone,lang,institution,department,idnumber,phone1,phone2,address,profile_field_employee_code,profile_field_staff_designation,profile_field_staff_department,profile_field_staff_joining_date,profile_field_staff_qualification,profile_field_staff_type
teacher.math05,ChangeMe@123,Meera,Joshi,teacher.math05@ems.example.org,manual,Ahmedabad,IN,Asia/Kolkata,en,EducateMe School,Mathematics,EMS-TCH-005,9999933333,,Ahmedabad,EMS-TCH-005,Teacher,Mathematics,2026-06-01,M.Sc B.Ed,Teacher
```

Example `role_assignments.csv`:

```csv
username,role_shortname,context_type,context_identifier,notes
teacher.math05,editingteacher,course,EMS-CBSE-ENG-STD05-GEN-MATH-26,Std 5 Mathematics teacher
```

Run:

```bash
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" users-dry-run
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" users-import
```

The importer assigns the role and also ensures manual course enrolment for course-context staff assignments.

## 11. Create or Reset the Universal Course Template

Dependencies:

```text
master_course_template.csv
course_template_sections.csv
course_template_activities.csv
```

Dry-run:

```bash
"$RUNNER" template-dry-run
```

Create without deleting existing template activities:

```bash
"$RUNNER" template-import
```

Reset and rebuild existing hidden template activities:

```bash
"$RUNNER" template-reset-import
```

## 12. Apply Template Sections to Existing Courses

Dependencies:

```text
course_template_application.csv
course_template_sections.csv
```

Run:

```bash
"$RUNNER" apply-template-dry-run
"$RUNNER" apply-template-import
```

## 13. Apply Gradebook Template

Dependencies:

```text
course_template_gradebook.csv
grade_band_template_adjustments.csv
```

Run:

```bash
"$RUNNER" gradebook-dry-run
"$RUNNER" gradebook-import
```

## 14. Verification Commands

Check users by username:

```bash
docker compose exec -T db psql -U moodle -d moodle -c \
"select username, firstname, lastname, email from mdl_user where username in ('ems.cbse.eng.std05.a.001','ems.cbse.eng.std05.a.002');"
```

Check cohort membership:

```bash
docker compose exec -T db psql -U moodle -d moodle -c \
"select u.username, c.idnumber as cohort from mdl_cohort_members cm join mdl_user u on u.id=cm.userid join mdl_cohort c on c.id=cm.cohortid where c.idnumber='EMS-2026-CBSE-ENG-STD05-GEN-A' order by u.username;"
```

Check course enrolment mappings:

```bash
docker compose exec -T db psql -U moodle -d moodle -c \
"select e.enrol, e.name, c.shortname from mdl_enrol e join mdl_course c on c.id=e.courseid where e.enrol='cohort' and e.name like 'EMS-2026-CBSE-ENG-STD05-GEN-%' order by c.shortname, e.name;"
```

Check teacher course role:

```bash
docker compose exec -T db psql -U moodle -d moodle -c \
"select u.username, r.shortname as role, c.shortname as course from mdl_role_assignments ra join mdl_user u on u.id=ra.userid join mdl_role r on r.id=ra.roleid join mdl_context ctx on ctx.id=ra.contextid join mdl_course c on c.id=ctx.instanceid where ctx.contextlevel=50 and u.username='teacher.math05';"
```

Purge caches after larger structure/template changes:

```bash
"$RUNNER" purge-cache
```

## 15. Common Mistakes and Fixes

| Problem | Cause | Fix |
|---|---|---|
| Student created but courses do not appear | Student is not in the cohort, or cohort enrolment mapping is missing. | Check `cohort_members.csv` and `enrolments.csv`. |
| Cohort member import fails | `cohort_code` does not exist in Moodle. | Import `cohorts.csv` first or correct the code. |
| Group mapping is empty | `group_idnumber` does not match an existing course group. | Import/fix `groups.csv`. |
| Teacher role fails | `context_identifier` does not match course shortname/idnumber. | Use course shortname, for example `EMS-CBSE-ENG-STD05-GEN-MATH-26`. |
| Parent link fails | Parent or student username does not exist, or `parent` role is missing. | Import parent/student users and `custom_roles.csv` first. |
| Duplicate user email warning | Moodle allows or blocks this depending on site config. | Use unique real emails for production. |
| Full pack imports too much data | Running full pack instead of selective pack. | Use `new-selective-pack` and keep unrelated CSV files header-only. |

## 16. Production Safety Rules

- Always run dry-run before import.
- Backup the database before large production imports.
- Keep usernames, course shortnames, category idnumbers, cohort idnumbers, and group idnumbers stable after import.
- Do not store full Aadhaar in Moodle CSVs.
- Use masked Aadhaar or external vault references only when legally approved.
- Keep medical, address, parent contact, and Aadhaar data out of course content.
- Do not reuse old-year courses for new academic-year reporting. Create new academic-year courses/cohorts and promote existing users.
