# DPS CLI Pack Data Model Reference

This guide documents the Drona Public School Moodle CSV pack in the order a school setup should be understood and executed.

It is written for two jobs:

1. Understand the matrix formulas used to generate school data.
2. Know exactly which CSV file controls each Moodle object.

## Chapter 0: File Locations and Execution Sequence

### Source Folders

| Folder | Purpose | Edit Directly? |
|---|---|---:|
| `config/` | School-level YAML configuration and naming rules | Yes |
| `master/` | Stable school master data | Yes |
| `registration/` | Staff, student, parent and relationship data | Yes |
| `years/<academic-year>/` | Year-specific courses, cohorts, groups, enrolments, exams and promotion data | Yes |
| `templates/` | Course, activity, exam and certificate templates | Yes |
| `operations/` | Validation, rollover, archive and compatibility reference data | Yes |
| `build/assembled_csv/<academic-year>/` | Moodle-ready generated import files | No, regenerate instead |

### Build and Import Commands

Run from:

```bash
cd /Users/hardik.chauhan/Documents/learning/eLearnMindset/research/drona_public_school
```

Assemble and validate:

```bash
./scripts/assemble.py --year 2026-2027
./scripts/validate.py --year 2026-2027
./scripts/import.sh 2026-2027 validate-only
```

Dry-run import:

```bash
./scripts/import.sh 2026-2027 dry-run
```

Live import:

```bash
./scripts/import.sh 2026-2027 live
```

For future years, `scripts/import.sh` automatically passes `--skip-users=1` so existing students, parents and staff are reused.

### CLI Execution Sequence

| Step | Script / Operation | Main CSV Files |
|---:|---|---|
| 1 | Assemble source CSV into ordered import files | `master/*`, `registration/*`, `years/<year>/*`, `templates/*`, `operations/*` |
| 2 | Validate school baseline CSV | `01_school_master.csv` to `29_summary.csv` |
| 3 | Validate course template CSV | `30_master_course_template.csv` to `43_diksha_content_template.csv`, plus certificate/exam files |
| 4 | Copy generated CSV into Moodle container | `build/assembled_csv/<year>/` |
| 5 | Create profile fields and custom roles | `16_user_profile_fields.csv`, `17_custom_roles.csv` |
| 6 | Create categories, courses, cohorts and groups | `10_categories.csv`, `12_courses.csv`, `14_cohorts.csv`, `15_groups.csv` |
| 7 | Create or update users | `19_users_staff.csv`, `20_users_students.csv`, `21_users_parents.csv` |
| 8 | Add students to cohorts | `22_cohort_members.csv` |
| 9 | Assign staff, teacher, principal and trustee roles | `23_role_assignments.csv` |
| 10 | Link parents to students | `24_parent_links.csv` |
| 11 | Create cohort-sync enrolments | `25_enrolments.csv` |
| 12 | Apply course section/template settings | `31_course_template_sections.csv`, `32_course_template_activities.csv`, `37_course_template_application.csv` |
| 13 | Apply gradebook template | `33_course_template_gradebook.csv`, `gradebook_weights.csv`, `assessment_plan.csv` |
| 14 | Apply course certificates | `course_certificates.csv` |

The Moodle CLI helper resolves both logical filenames and ordered filenames. For example, a script can ask for `courses.csv`, and the helper will find `12_courses.csv`.

## Chapter 1: Core School Master Data

These files define the school identity and allowed lookup values.

| Ordered CSV | Source CSV | Purpose |
|---|---|---|
| `01_school_master.csv` | `master/school.csv` | Trust, school and principal identity |
| `02_academic_years.csv` | `master/academic_years.csv` | Academic year list |
| `03_boards.csv` | `master/boards.csv` | Education board values |
| `04_mediums.csv` | `master/mediums.csv` | Medium/language values |
| `05_grades.csv` | `master/grades.csv` | Standard 1 to Standard 12 |
| `06_streams.csv` | `master/streams.csv` | General, Science, Commerce and Arts streams |
| `07_divisions.csv` | `master/divisions.csv` | Division A to F |
| `08_subjects.csv` | `master/subjects.csv` | Subject code master |

### Core Dimension Counts

| Dimension | Values | Count |
|---|---|---:|
| School | `DPS` | 1 |
| Board | `GSEB` | 1 |
| Medium | `GUJ`, `ENG`, `HIN` | 3 |
| General grades | `STD01` to `STD10` | 10 |
| Higher secondary grades | `STD11`, `STD12` | 2 |
| General stream | `GEN` | 1 |
| Higher secondary streams | `SCI`, `COM`, `ARTS` | 3 |
| Divisions | `A`, `B`, `C`, `D`, `E`, `F` | 6 |

### CSV Templates

```csv
trust_code,school_code,school_name,principal_username,academic_year
DRONA_TRUST,DPS,Drona Public School,principal.sharma,2026-2027
```

```csv
academic_year,start_date,end_date,is_current
2026-2027,2026-04-01,2027-03-31,1
```

```csv
board_code,board_name,board_type,country,state
GSEB,Gujarat Board Education,State Board,India,Gujarat
```

```csv
medium_code,medium_name,language_code
GUJ,Gujarati Medium,gu
ENG,English Medium,en
HIN,Hindi Medium,hi
```

```csv
grade_code,grade_name,display_order,stage
STD05,Standard 5,5,Primary
```

```csv
stream_code,stream_name,applies_to
GEN,General,STD01-STD10
SCI,Science,STD11-STD12
COM,Commerce,STD11-STD12
ARTS,Arts Humanities,STD11-STD12
```

```csv
division_code,division_name,display_order
A,Division A,1
```

```csv
subject_code,subject_name,subject_category
MATH,Mathematics,Core
```

## Chapter 2: Grade Subject Matrix

The subject matrix is the source for course generation.

| Ordered CSV | Source CSV | Purpose |
|---|---|---|
| `09_grade_subject_matrix.csv` | `years/<year>/grade_subject_matrix.csv` | Defines which subjects exist for each board, medium, grade and stream |

### Matrix Formula

Grades 1 to 10 use `GEN`:

```text
10 grades x 1 stream = 10 grade-stream combinations
```

Grades 11 and 12 use `SCI`, `COM` and `ARTS`:

```text
2 grades x 3 streams = 6 grade-stream combinations
```

Per medium:

```text
10 + 6 = 16 grade-stream combinations
```

Across all mediums:

```text
16 x 3 mediums = 48 grade-stream-medium combinations
```

### Subject Count Formula

| Grade / Stream | Subjects per Medium | Formula |
|---|---:|---:|
| `STD01` to `STD05`, `GEN` | 7 | `5 x 7 = 35` |
| `STD06` to `STD08`, `GEN` | 9 | `3 x 9 = 27` |
| `STD09` to `STD10`, `GEN` | 8 | `2 x 8 = 16` |
| `STD11` to `STD12`, `SCI` | 7 | `2 x 7 = 14` |
| `STD11` to `STD12`, `COM` | 7 | `2 x 7 = 14` |
| `STD11` to `STD12`, `ARTS` | 7 | `2 x 7 = 14` |

Courses per medium:

```text
35 + 27 + 16 + 14 + 14 + 14 = 120
```

Courses per academic year:

```text
120 courses x 3 mediums = 360 courses
```

### CSV Template

```csv
board_code,medium_code,grade_code,stream_code,subject_code,subject_name,subject_category,is_compulsory,is_elective,display_order,source_note
GSEB,GUJ,STD05,GEN,MATH,Mathematics,Core,1,0,4,Generated Gujarat board school local testing subject matrix
```

## Chapter 3: Moodle Structure

This chapter creates the Moodle container structure: categories, courses, cohorts and groups.

| Ordered CSV | Source CSV | Purpose |
|---|---|---|
| `10_categories.csv` | `years/<year>/categories.csv` | Moodle category tree |
| `11_optional_year_category_model_categories.csv` | generated optional reference | Alternative year-based category structure |
| `12_courses.csv` | `years/<year>/courses.csv` | Course source data |
| `13_courses_with_templatecourse_for_moodle_upload.csv` | `years/<year>/courses_with_templatecourse_for_moodle_upload.csv` | Moodle upload-ready course file |
| `14_cohorts.csv` | `years/<year>/cohorts.csv` | Student class/division batches |
| `15_groups.csv` | `years/<year>/groups.csv` | Course-level division groups |

### Category Formula

One leaf category is created per academic year, medium, grade and stream.

```text
16 grade-stream combinations x 3 mediums = 48 leaf academic categories
```

Category ID formula:

```text
<trust_code>_<board_code>_<school_code>_<academic_year_underscore>_<medium_code>_<grade_code>_<stream_code>
```

Example:

```text
DRONA_TRUST_GSEB_DPS_2026_2027_GUJ_STD05_GEN
```

CSV template:

```csv
category_code,parent_category_code,category_type,name,idnumber,path,visible,description
DRONA_TRUST_GSEB_DPS_2026_2027_GUJ_STD05_GEN,DRONA_TRUST_GSEB_DPS_2026_2027_GUJ,stream,GEN,DRONA_TRUST_GSEB_DPS_2026_2027_GUJ_STD05_GEN,Drona Education Trust / Gujarat Board Education / Drona Public School / 2026-2027 / Gujarati Medium / Standard 5 / GEN,1,Standard 5 Gujarati Medium GEN category
```

### Course Formula

One Moodle course is created per matrix row.

Course ID formula:

```text
<school_code>-<board_code>-<medium_code>-<grade_code>-<stream_code>-<subject_code>-<start_year>
```

Course shortname formula:

```text
<school_code>-<board_code>-<medium_code>-<grade_code>-<stream_code>-<subject_code>-<short_year>
```

Examples:

```text
course idnumber: DPS-GSEB-GUJ-STD05-GEN-MATH-2026
course shortname: DPS-GSEB-GUJ-STD05-GEN-MATH-26
```

Source CSV template:

```csv
course_code,fullname,shortname,idnumber,category_code,board_code,school_code,medium_code,grade_code,stream_code,subject_code,subject_name,academic_year,format,numsections,visible,groupmode,groupmodeforce,templatecourse,enablecompletion,course_template_code
DPS-GSEB-GUJ-STD05-GEN-MATH-2026,Drona Public School - Gujarat Board Education - Gujarati Medium - Standard 5 - GEN - Mathematics - 2026-2027,DPS-GSEB-GUJ-STD05-GEN-MATH-26,DPS-GSEB-GUJ-STD05-GEN-MATH-2026,DRONA_TRUST_GSEB_DPS_2026_2027_GUJ_STD05_GEN,GSEB,DPS,GUJ,STD05,GEN,MATH,Mathematics,2026-2027,topics,16,1,1,1,MASTER-ALL-GRADES-ALL-SUBJECTS-STD-TEMPLATE,1,TPL_STD03_05_PRIMARY
```

Moodle upload CSV template:

```csv
shortname,fullname,idnumber,category_idnumber,visible,format,numsections,enablecompletion,groupmode,groupmodeforce,templatecourse
DPS-GSEB-GUJ-STD05-GEN-MATH-26,Drona Public School - Gujarat Board Education - Gujarati Medium - Standard 5 - GEN - Mathematics - 2026-2027,DPS-GSEB-GUJ-STD05-GEN-MATH-2026,DRONA_TRUST_GSEB_DPS_2026_2027_GUJ_STD05_GEN,1,topics,16,1,1,1,MASTER-ALL-GRADES-ALL-SUBJECTS-STD-TEMPLATE
```

### Cohort Formula

One cohort is created per academic year, medium, grade, stream and division.

```text
48 grade-stream-medium combinations x 6 divisions = 288 cohorts per year
```

Cohort ID formula:

```text
<school_code>-<start_year>-<board_code>-<medium_code>-<grade_code>-<stream_code>-<division_code>
```

Example:

```text
DPS-2026-GSEB-GUJ-STD05-GEN-A
```

CSV template:

```csv
cohort_code,name,idnumber,context_category_code,board_code,school_code,medium_code,grade_code,stream_code,division_code,academic_year,visible,description
DPS-2026-GSEB-GUJ-STD05-GEN-A,DPS 2026-2027 Gujarati Medium Standard 5 GEN Division A,DPS-2026-GSEB-GUJ-STD05-GEN-A,DRONA_TRUST_GSEB_DPS_2026_2027_GUJ_STD05_GEN,GSEB,DPS,GUJ,STD05,GEN,A,2026-2027,1,2026-2027 cohort for STD05 GEN GUJ Division A
```

### Group Formula

Each course gets one group per division.

```text
360 courses x 6 divisions = 2160 groups per year
```

Group ID formula:

```text
<course_idnumber>-<division_code>
```

Example:

```text
DPS-GSEB-GUJ-STD05-GEN-MATH-2026-A
```

CSV template:

```csv
course_code,course_shortname,group_name,group_idnumber,board_code,school_code,medium_code,grade_code,stream_code,division_code,description
DPS-GSEB-GUJ-STD05-GEN-MATH-2026,DPS-GSEB-GUJ-STD05-GEN-MATH-26,Division A,DPS-GSEB-GUJ-STD05-GEN-MATH-2026-A,GSEB,DPS,GUJ,STD05,GEN,A,Division A group for Standard 5 Mathematics
```

## Chapter 4: Users, Profile Fields and Roles

These files create Moodle users and the school-specific role model.

| Ordered CSV | Source CSV | Purpose |
|---|---|---|
| `16_user_profile_fields.csv` | `master/profile_fields.csv` | Student, parent and staff custom fields |
| `17_custom_roles.csv` | `master/roles.csv` | Principal, trustee and parent custom roles |
| `18_role_guidelines.csv` | `operations/role_guidelines.csv` | Human role assignment rules |
| `19_users_staff.csv` | `registration/combined/19_users_staff.csv` | Staff, teachers, principal, trustee and IT users |
| `20_users_students.csv` | `registration/combined/20_users_students.csv` | Student users |
| `21_users_parents.csv` | `registration/combined/21_users_parents.csv` | Parent users |

### User ID Formulas

| User Type | Formula | Example |
|---|---|---|
| Student username | `dps.stu.<5_digit_sequence>` | `dps.stu.01441` |
| Student idnumber / admission no | `<school_code><short_year>-<5_digit_sequence>` | `DPS26-01441` |
| Student GR number | `GR-<school_code>-<start_year>-<5_digit_sequence>` | `GR-DPS-2026-01441` |
| Student roll no | `<3_digit_sequence_inside_division>` | `001` |
| Parent username | `dps.par.<5_digit_sequence>` | `dps.par.00001` |
| Parent idnumber | `<school_code>-PAR-<5_digit_sequence>` | `DPS-PAR-00001` |
| Teacher username | `dps.tch.<medium_lowercase>.<subject_lowercase>` | `dps.tch.guj.math` |
| Teacher employee code | `<school_code>-TCH-<3_digit_sequence>` | `DPS-TCH-001` |
| Principal idnumber | fixed | `DPS-PRN-001` |
| Trustee idnumber | fixed | `DPS-TRU-001` |
| IT idnumber | fixed | `DPS-IT-001` |

### Profile Field CSV Template

```csv
shortname,name,category,datatype,required,locked,visible,forceunique,signup,sensitive,recommended_visibility
current_grade_code,Current Grade Code,Academic,text,0,0,2,0,0,0,staff-only
```

### Custom Role CSV Template

```csv
role_shortname,role_name,based_on_role,context_levels,description
principal,School Principal,manager,category,Principal-level school administration role
```

### Staff CSV Template

```csv
username,password,firstname,lastname,email,auth,institution,department,idnumber,profile_field_employee_code,profile_field_staff_designation,profile_field_staff_department,profile_field_staff_type
dps.tch.guj.math,DronaTeacher2026!,Mathematics,Teacher,dps.tch.guj.math@dronapublicschool.example,manual,Drona Public School,Teaching,DPS-TCH-001,DPS-TCH-001,Subject Teacher,Mathematics,Teaching
```

### Student CSV Template

```csv
username,password,firstname,lastname,email,auth,institution,department,idnumber,cohort1,board_code,school_code,medium_code,grade_code,stream_code,division_code,profile_field_admission_no,profile_field_roll_no,profile_field_student_gr_no,profile_field_current_academic_year,profile_field_current_board_code,profile_field_current_school_code,profile_field_current_medium_code,profile_field_current_grade_code,profile_field_current_stream_code,profile_field_current_division_code,profile_field_student_status
dps.stu.01441,DronaStudent2026!,Darsh,Patel,dps.stu.01441@students.dronapublicschool.example,manual,Drona Public School,Primary,DPS26-01441,DPS-2026-GSEB-GUJ-STD05-GEN-A,GSEB,DPS,GUJ,STD05,GEN,A,DPS26-01441,001,GR-DPS-2026-01441,2026-2027,GSEB,DPS,GUJ,STD05,GEN,A,ACTIVE
```

### Parent CSV Template

```csv
username,password,firstname,lastname,email,auth,institution,department,idnumber,phone1,profile_field_parent_type,profile_field_parent_occupation,profile_field_parent_qualification,profile_field_preferred_language,profile_field_consent_student_data
dps.par.00001,DronaParent2026!,Patel,Guardian,dps.par.00001@parents.dronapublicschool.example,manual,Drona Public School,Parents,DPS-PAR-00001,+91-9500000001,Guardian,Service,Graduate,Gujarati,1
```

## Chapter 5: Relationships, Role Assignments and Enrolments

These files connect users to cohorts, courses and each other.

| Ordered CSV | Source CSV | Purpose |
|---|---|---|
| `22_cohort_members.csv` | `years/<year>/cohort_members.csv` | Adds students to division cohorts |
| `23_role_assignments.csv` | `years/<year>/role_assignments.csv` | Assigns teachers, principal, trustee and managers |
| `24_parent_links.csv` | `registration/parent_links.csv` | Links parent accounts to student accounts |
| `25_enrolments.csv` | `years/<year>/enrolments.csv` | Creates cohort-sync enrolment into courses |

### Student to Cohort

Formula:

```text
student.username -> cohort.idnumber
```

CSV template:

```csv
username,cohort_code,role
dps.stu.01441,DPS-2026-GSEB-GUJ-STD05-GEN-A,student
```

### Cohort to Course

Formula:

```text
cohort.idnumber + course.idnumber + group.idnumber + role=student + enrolment_method=cohort_sync
```

CSV template:

```csv
course_code,course_shortname,cohort_code,role_shortname,group_name,group_idnumber,enrolment_method,status
DPS-GSEB-GUJ-STD05-GEN-MATH-2026,DPS-GSEB-GUJ-STD05-GEN-MATH-26,DPS-2026-GSEB-GUJ-STD05-GEN-A,student,Division A,DPS-GSEB-GUJ-STD05-GEN-MATH-2026-A,cohort_sync,active
```

### Teacher to Course

Formula:

```text
teacher.username + role=editingteacher + context_type=course + context_identifier=course.idnumber
```

CSV template:

```csv
username,role_shortname,context_type,context_identifier,notes
dps.tch.guj.math,editingteacher,course,DPS-GSEB-GUJ-STD05-GEN-MATH-2026,Subject teacher for Standard 5 Mathematics
```

### Principal and Trustee Access

Principal and trustee access is category-level, not cohort-based.

```csv
username,role_shortname,context_type,context_identifier,notes
principal.sharma,principal,category,DRONA_TRUST_GSEB_DPS,Principal can review school-level users reports and courses
trustee.patel,trustee_manager,category,DRONA_TRUST,Trustee manager can review trust-level school data
```

### Parent to Student

Formula:

```text
parent.username + student.username + relationship + role=parent
```

CSV template:

```csv
parent_username,student_username,relationship,role_shortname,allow_grade_view,allow_activity_report_view,notes
dps.par.00001,dps.stu.00001,Guardian,parent,1,1,Shared sibling parent for local testing
```

## Chapter 6: Course Template, Sections and Activities

This chapter defines the standard learning structure applied to every course.

| Ordered CSV | Source CSV | Purpose |
|---|---|---|
| `30_master_course_template.csv` | `templates/legacy/30_master_course_template.csv` | Hidden master course template |
| `31_course_template_sections.csv` | `templates/legacy/31_course_template_sections.csv` | Course home, syllabus, 10 chapters, exams and certificate section |
| `32_course_template_activities.csv` | `templates/legacy/32_course_template_activities.csv` | Default activities per section |
| `33_course_template_gradebook.csv` | `templates/legacy/33_course_template_gradebook.csv` | Gradebook categories |
| `34_grade_band_template_adjustments.csv` | `templates/legacy/34_grade_band_template_adjustments.csv` | Primary, secondary and higher-secondary adjustments |
| `35_subject_template_adjustments.csv` | `templates/legacy/35_subject_template_adjustments.csv` | Subject-specific additions |
| `36_completion_tracking_defaults.csv` | `templates/legacy/36_completion_tracking_defaults.csv` | Completion defaults by activity type |
| `37_course_template_application.csv` | `years/<year>/course_template_application.csv` | Applies the template to every course |
| `38_course_template_custom_fields.csv` | `templates/legacy/38_course_template_custom_fields.csv` | Course custom field definitions |
| `39_course_template_review_checklist.csv` | `templates/legacy/39_course_template_review_checklist.csv` | QA checklist |
| `40_certificate_badge_policy.csv` | `templates/legacy/40_certificate_badge_policy.csv` | Certificate and badge policy reference |
| `41_template_report_access_matrix.csv` | `templates/legacy/41_template_report_access_matrix.csv` | Report access by role |
| `42_behat_course_template_coverage_mapping.csv` | `templates/legacy/42_behat_course_template_coverage_mapping.csv` | Test coverage mapping |
| `43_diksha_content_template.csv` | `years/<year>/diksha_content_template.csv` | DIKSHA content mapping placeholder |

### Template Formula

```text
360 courses x 1 template application = 360 course template applications
```

The current universal template has:

```text
16 sections
65 default activity definitions
```

### CSV Templates

```csv
template_code,shortname,fullname,idnumber,category_code,format,numsections,enablecompletion
MASTER_ALL_GRADES_ALL_SUBJECTS_STANDARD,MASTER-ALL-GRADES-ALL-SUBJECTS-STD-TEMPLATE,MASTER - Drona All Grades - 10 Chapter Term and Final Exam Template,MASTER_ALL_GRADES_ALL_SUBJECTS_STANDARD,COURSE_TEMPLATES,topics,16,1
```

```csv
template_code,section_number,section_name,purpose,default_visibility,completion_relevant
MASTER_ALL_GRADES_ALL_SUBJECTS_STANDARD,1,Chapter 1,Chapter learning sequence,1,1
```

```csv
template_code,section_number,item_order,activity_key,recommended_activity_type,safe_placeholder_module,default_name,completion_mode,completion_required,unlock_next,grade_to_pass
MASTER_ALL_GRADES_ALL_SUBJECTS_STANDARD,1,4,chapter_01_gate_quiz,quiz,quiz,Chapter 1 Gate Quiz,2,1,1,40
```

```csv
course_shortname,course_code,template_code,templatecourse_shortname,academic_year,grade_code,grade_band,subject_code,stream_code,enablecompletion,apply_sections,apply_gradebook,certificate_policy_code
DPS-GSEB-GUJ-STD05-GEN-MATH-26,DPS-GSEB-GUJ-STD05-GEN-MATH-2026,MASTER_ALL_GRADES_ALL_SUBJECTS_STANDARD,MASTER-ALL-GRADES-ALL-SUBJECTS-STD-TEMPLATE,2026-2027,STD05,G3-G5,MATH,GEN,1,1,1,PRIMARY_COURSE_COMPLETION_BADGE
```

## Chapter 7: Exams, Gradebook and Certificates

These files define assessment and certificate behavior per course.

| CSV | Source CSV | Purpose |
|---|---|---|
| `exam_terms.csv` | `years/<year>/exam_terms.csv` | Term definitions |
| `assessment_plan.csv` | `years/<year>/assessment_plan.csv` | Course assessment weights |
| `gradebook_weights.csv` | `years/<year>/gradebook_weights.csv` | Moodle gradebook weights |
| `course_term_exams.csv` | `years/<year>/course_term_exams.csv` | Course to term-exam template mapping |
| `course_final_exams.csv` | `years/<year>/course_final_exams.csv` | Course to final-exam template mapping |
| `course_certificates.csv` | `years/<year>/course_certificates.csv` | Course certificate activity mapping |
| `attendance_policy.csv` | `years/<year>/attendance_policy.csv` | Minimum attendance reference |

### Exam Formula

```text
360 courses x 2 term exams = 720 course term exam mappings
360 courses x 1 final exam = 360 final exam mappings
```

CSV templates:

```csv
academic_year,term_code,name,weight_percent,notes
2026-2027,TERM1,Term 1 Examination,20,First term school examination
```

```csv
academic_year,course_code,course_shortname,term_code,term_exam_template_code,enabled,required_for_completion,notes
2026-2027,DPS-GSEB-GUJ-STD05-GEN-MATH-2026,DPS-GSEB-GUJ-STD05-GEN-MATH-26,TERM1,TERM_EXAM_STD05_TERM1,1,1,Generated grade-wise term exam assignment
```

```csv
academic_year,course_code,course_shortname,final_exam_template_code,enabled,required_for_completion,notes
2026-2027,DPS-GSEB-GUJ-STD05-GEN-MATH-2026,DPS-GSEB-GUJ-STD05-GEN-MATH-26,FINAL_EXAM_STD05,1,1,Generated grade-wise final exam assignment
```

### Certificate Formula

```text
360 courses x 1 certificate = 360 certificate mappings per academic year
```

CSV template:

```csv
academic_year,course_code,course_shortname,certificate_enabled,credential_type,certificate_policy_code,requires_plugin,issue_condition,min_completion_percent,min_grade_percent,visible_to_student,certificate_template_code,certificate_activity_type,certificate_activity_key,certificate_activity_name,certificate_section_number,certificate_section_name,certificate_verification_enabled,school_name,board_name,principal_name,medium_code,grade_code,stream_code,subject_code
2026-2027,DPS-GSEB-GUJ-STD05-GEN-MATH-2026,DPS-GSEB-GUJ-STD05-GEN-MATH-26,1,certificate,PRIMARY_COURSE_COMPLETION_CERT,1,course_completion,100,40,1,DRONA_MODERN_COURSE_COMPLETION,customcert,course_completion_certificate,Download Course Completion Certificate,15,Certificate & Completion,1,Drona Public School,Gujarat Board Education,Anita Sharma,GUJ,STD05,GEN,MATH
```

## Chapter 8: Academic Year Rollover and Promotion

These files support year change without recreating user accounts.

| Ordered CSV | Source CSV | Purpose |
|---|---|---|
| `44_academic_year_transition_models.csv` | `operations/academic_year_transition_models.csv` | Available rollover models |
| `45_academic_year_promotion_rules.csv` | `operations/academic_year_promotion_rules.csv` | Grade to grade promotion rules |
| `46_academic_year_rollover_checklist.csv` | `operations/academic_year_rollover_checklist.csv` | Operational checklist |
| `47_promotion_policy.csv` | `operations/promotion_policy.csv` | Promotion policy guidance |
| `48_promotion_status_codes.csv` | `operations/promotion_status_codes.csv` | Status values |
| `49_promotion_validation_rules.csv` | `operations/promotion_validation_rules.csv` | Validation rules |
| `50_student_status_codes.csv` | `operations/student_status_codes.csv` | Student lifecycle statuses |
| `51_student_academic_history_template.csv` | `years/<year>/academic_history.csv` | Student academic history shape |
| `52_student_promotion_plan_2027_2028.csv` | `years/<year>/promotion_plan_to_<next-year>.csv` | Reviewable promotion plan |
| `53_promotion_actions.csv` | `years/<year>/promotion_actions.csv` | Executable promotion actions |
| `54_next_year_courses_2027_2028.csv` | generated from next year `courses.csv` | Next-year course structure |
| `55_next_year_cohorts_2027_2028.csv` | generated from next year `cohorts.csv` | Next-year cohorts |
| `56_next_year_groups_2027_2028.csv` | generated from next year `groups.csv` | Next-year groups |
| `57_next_year_enrolments_2027_2028.csv` | generated from next year `enrolments.csv` | Next-year enrolments |
| `58_alumni_cohorts_2027.csv` | `operations/alumni_cohorts_2027.csv` | Alumni cohort reference |
| `59_archive_policy.csv` | `operations/archive_policy.csv` | Archive rules |

### Rollover Rule

Do not recreate students, parents or teachers for the next year.

Change these instead:

```text
courses
cohorts
groups
cohort memberships
cohort-sync enrolments
teacher role assignments
student academic history
student current profile fields
```

Promotion action CSV template:

```csv
action,username,student_idnumber,from_academic_year,to_academic_year,from_cohort_code,to_cohort_code,from_grade_code,to_grade_code,result_status,remove_from_old_cohort,update_profile_fields,effective_date,approved_by
PROMOTE,dps.stu.01441,DPS26-01441,2026-2027,2027-2028,DPS-2026-GSEB-GUJ-STD05-GEN-A,DPS-2027-GSEB-GUJ-STD06-GEN-A,STD05,STD06,PASS,1,1,2027-04-01,principal.sharma
```

## Chapter 9: Validation, References and Operations

These files are not all directly imported into Moodle, but they document and validate the pack.

| Ordered CSV | Source CSV | Purpose |
|---|---|---|
| `26_lookup_values.csv` | `master/lookup_values.csv` | Accepted lookup values |
| `27_validation_rules.csv` | `operations/validation_rules.csv` | CSV validation rules |
| `28_source_references.csv` | `operations/source_references.csv` | Data/reference sources |
| `29_summary.csv` | `operations/summary.csv` | Generated pack counts |
| `60_improvement_backlog.csv` | `operations/improvement_backlog.csv` | Future improvements |
| `61_compatibility_matrix.csv` | `operations/compatibility_matrix.csv` | Moodle compatibility notes |

Validation commands:

```bash
./scripts/validate.py --year 2026-2027
./scripts/import.sh 2026-2027 validate-only
```

## Chapter 10: End-to-End Dependency Flow

Use this sequence when explaining or troubleshooting the pack:

```text
school master
-> academic years
-> board, medium, grade, stream, division, subject
-> grade subject matrix
-> categories
-> courses
-> cohorts
-> groups
-> profile fields and roles
-> staff, students, parents
-> cohort members
-> role assignments
-> parent links
-> enrolments
-> course sections and activities
-> gradebook and assessment setup
-> certificates
-> promotion and rollover data
```

## Chapter 11: One Complete Standard 5 Example

For Gujarati Medium, Standard 5, General stream, Division A, Mathematics:

| Item | Value |
|---|---|
| School | `DPS` |
| Board | `GSEB` |
| Medium | `GUJ` |
| Grade | `STD05` |
| Stream | `GEN` |
| Division | `A` |
| Subject | `MATH` |
| Category | `DRONA_TRUST_GSEB_DPS_2026_2027_GUJ_STD05_GEN` |
| Course idnumber | `DPS-GSEB-GUJ-STD05-GEN-MATH-2026` |
| Course shortname | `DPS-GSEB-GUJ-STD05-GEN-MATH-26` |
| Cohort idnumber | `DPS-2026-GSEB-GUJ-STD05-GEN-A` |
| Group idnumber | `DPS-GSEB-GUJ-STD05-GEN-MATH-2026-A` |
| Student username | `dps.stu.01441` |
| Student idnumber | `DPS26-01441` |
| Parent username | `dps.par.00001` |
| Teacher username | `dps.tch.guj.math` |

Relationship chain:

```text
dps.stu.01441
-> DPS-2026-GSEB-GUJ-STD05-GEN-A
-> DPS-GSEB-GUJ-STD05-GEN-MATH-2026
-> DPS-GSEB-GUJ-STD05-GEN-MATH-2026-A
```

Teacher chain:

```text
dps.tch.guj.math
-> editingteacher
-> DPS-GSEB-GUJ-STD05-GEN-MATH-2026
```

Parent chain:

```text
dps.par.00001
-> parent
-> dps.stu.00001
```
