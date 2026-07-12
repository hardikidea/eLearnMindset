# Google Form Teacher Registration Guide

This guide explains how to collect teacher registration data through Google Forms, map it into the school master workbook, generate Moodle staff users, generate Moodle teacher role assignments, export CSV files, validate the CLI pack, and import into Moodle.

The workflow is aligned to the current school master pack:

```text
Google Form
  -> Google Form response sheet
  -> Apps Script validation and mapping
  -> 19_users_staff
  -> 23_role_assignments
  -> CSV export
  -> CLI pack validation
  -> Moodle dry-run/live import
```

Important limitation: Google Forms cannot write directly to the local `.ods` file in this repository. Use a Google Sheets copy of the workbook as the intake workbook, then export the mapped tabs to the CLI pack CSV files.

## 1. Target Sheets And CLI Pack Files

Google Sheets target tabs:

| Data | Google Sheets tab | CLI pack CSV |
|---|---|---|
| Teacher Moodle user | `19_users_staff` | `registration/combined/19_users_staff.csv` |
| Teacher Moodle access | `23_role_assignments` | `years/<academic-year>/role_assignments.csv` |
| Course matching source | `12_courses` | `years/<academic-year>/courses.csv` |

Rows 1 to 6 in the master workbook sheets are metadata, contract, formula, header, and example rows. Apps Script writes real records from row 7 onward.

## 2. Create The Google Sheets Intake Workbook

Use the same Google Sheets workbook prepared for registration intake:

```text
School Master Pack - Registration Intake
```

Confirm these tabs exist:

```text
12_courses
19_users_staff
23_role_assignments
```

Optional audit tabs are created automatically by the script:

```text
_teacher_intake_errors
_teacher_intake_audit
```

## 3. Create The Teacher Google Form

Form name:

```text
Drona Public School - Teacher Registration
```

Form description:

```text
Use this form to register a teacher/staff account for Moodle and assign teaching access to matching courses. School office and IT team must verify the generated user and role assignments before import.
```

Recommended form settings:

| Setting | Value |
|---|---|
| Collect email addresses | Optional |
| Limit to 1 response | No |
| Allow response editing | No |
| Show progress bar | Yes |
| Shuffle question order | No |
| Confirmation message | Thank you. Teacher registration has been submitted for verification. |

Link the form to the intake spreadsheet:

```text
Google Form -> Responses -> Link to Sheets -> Select existing spreadsheet
```

Choose:

```text
School Master Pack - Registration Intake
```

## 4. Form Sections And Fields

Use the question titles exactly as shown unless you also update the `canonicalKey()` mapping in Apps Script.

### Section 1: Teacher Basic Details

Section description:

```text
Enter the teacher identity and contact details exactly as per school employment records.
```

| Question title | Type | Required | Validation or hint |
|---|---|---:|---|
| Teacher first name | Short answer | Yes | Example: Kiran |
| Teacher last name | Short answer | Yes | Example: Patel |
| Teacher mobile number | Short answer | Yes | Regex: `^[6-9][0-9]{9}$` |
| Teacher email | Short answer | Yes | Email format |
| Alternate mobile number | Short answer | No | Regex: `^[6-9][0-9]{9}$` |
| Address line 1 | Short answer | Yes | Home or communication address |
| City | Short answer | No | Default can be Ahmedabad |

### Section 2: Staff Profile

Section description:

```text
Enter staff profile details used by Moodle custom profile fields and school reports.
```

| Question title | Type | Required | Standard values or hint |
|---|---|---:|---|
| Employee code | Short answer | No | Leave blank to use generated staff idnumber |
| Designation | Dropdown | Yes | TEACHER - Teacher |
| Department | Dropdown | Yes | ACADEMIC - Academic, PRIMARY - Primary School, UPPER_PRIMARY - Upper Primary, SECONDARY - Secondary, HIGHER_SECONDARY - Higher Secondary |
| Staff type | Dropdown | Yes | Teaching |
| Joining date | Date | Yes | Official joining date |
| Qualification | Short answer | Yes | Example: B.Ed, M.Sc Mathematics |
| Teacher Aadhaar last 4 digits | Short answer | No | Regex: `^[0-9]{4}$` |
| Teacher Aadhaar consent | Multiple choice | No | Yes, No |

For non-teaching staff, use the staff CSV directly or create a separate staff form. Teacher course access is only generated for teaching staff.

### Section 3: Teaching Assignment

Section description:

```text
Select the course scope this teacher should teach. The script creates one Moodle role assignment for each matching course.
```

| Question title | Type | Required | Standard values |
|---|---|---:|---|
| Academic year | Dropdown | Yes | 2026-2027, 2027-2028, 2028-2029, 2029-2030 |
| Medium | Dropdown | Yes | GUJ - Gujarati Medium, ENG - English Medium, HIN - Hindi Medium |
| Grades to teach | Checkboxes | Yes | STD01 to STD12 |
| Streams to teach | Checkboxes | Yes | GEN - General, SCI - Science, COM - Commerce, ARTS - Arts / Humanities |
| Subjects to teach | Checkboxes | Yes | Subject codes from `master/subjects.csv` |
| Moodle role | Dropdown | Yes | editingteacher - Editing teacher, teacher - Non-editing teacher |

Academic assignment rules:

```text
STD01 to STD10 must use GEN.
STD11 and STD12 can use SCI, COM, or ARTS.
```

Subject options:

```text
ACC - Accountancy
ART - Art Education
BA - Business Studies
BIO - Biology
CHEM - Chemistry
CS - Computer Studies
ECO - Economics
ENG - English
EVS - Environmental Studies
GEO - Geography
GUJ - Gujarati
HIN - Hindi
HIST - History
MATH - Mathematics
PE - Physical Education
PHY - Physics
POLSCI - Political Science
PSY - Psychology
SCI - Science
SOC - Sociology
SS - Social Science
STAT - Statistics
```

### Section 4: Declaration

Section description:

```text
School office confirmation is required before teacher registration can be processed.
```

| Question title | Type | Required | Validation or hint |
|---|---|---:|---|
| Declaration teacher details are correct | Checkbox | Yes | Option: I confirm |
| Submitted by name | Short answer | Yes | Office/admin user name |
| Submitted by mobile | Short answer | No | Regex: `^[6-9][0-9]{9}$` |

## 5. Form Response Validation

Configure validation inside Google Forms for fields that must be checked before submission.

Open each question:

```text
Question -> three-dot menu -> Response validation
```

Use these rules:

| Field | Google Forms validation | Regex |
|---|---|---|
| Teacher mobile number | Regular expression -> Matches | `^[6-9][0-9]{9}$` |
| Alternate mobile number | Regular expression -> Matches | `^[6-9][0-9]{9}$` |
| Submitted by mobile | Regular expression -> Matches | `^[6-9][0-9]{9}$` |
| Teacher Aadhaar last 4 digits | Regular expression -> Matches | `^[0-9]{4}$` |
| Teacher email | Text -> Email address | Built-in |

Custom error messages:

| Field type | Message |
|---|---|
| Mobile | Enter a valid 10 digit Indian mobile number. |
| Aadhaar last 4 | Enter only the last 4 digits of Aadhaar. |
| Email | Enter a valid email address. |

Apps Script repeats the same validations because form validation can be bypassed by copied sheets or manual edits.

## 6. Apps Script Project Setup

Starter project files are included in this repository:

```text
integrations/google_forms/teacher_registration/appsscript.json
integrations/google_forms/teacher_registration/Code.gs
```

Setup steps:

1. Open the Google Sheets intake workbook.
2. Go to:

```text
Extensions -> Apps Script
```

3. Create a new Apps Script project for the teacher form, or add this code to the same Apps Script project if you understand trigger separation.
4. Paste the contents of:

```text
integrations/google_forms/teacher_registration/Code.gs
```

5. In project settings, confirm:

```text
Time zone: Asia/Kolkata
Runtime: V8
```

6. Save the project.
7. Select and run:

```text
installTeacherRegistrationTrigger
```

8. Approve the Google authorization prompt.

The trigger listens for form submissions and runs:

```text
onTeacherRegistrationSubmit
```

If this teacher form shares a spreadsheet with other forms, keep each form's Apps Script handler separate and test carefully. The starter script includes a guard so it ignores non-teacher submissions. The student/parent starter script has the same guard for non-student submissions.

## 7. Form Title To Script Key Mapping

Apps Script maps human-friendly question titles to CLI-pack keys. Example:

```text
Teacher first name -> teacher_firstname
Subjects to teach -> subject_codes
Moodle role -> role_shortname
```

Important mappings:

| Question title | Apps Script key |
|---|---|
| Teacher first name | `teacher_firstname` |
| Teacher last name | `teacher_lastname` |
| Teacher mobile number | `teacher_mobile` |
| Teacher email | `teacher_email` |
| Alternate mobile number | `alternate_mobile` |
| Employee code | `employee_code` |
| Designation | `designation_code` |
| Department | `department_code` |
| Staff type | `staff_type` |
| Joining date | `joining_date` |
| Qualification | `qualification` |
| Address line 1 | `address_line1` |
| Teacher Aadhaar last 4 digits | `teacher_aadhaar_last4` |
| Teacher Aadhaar consent | `teacher_aadhaar_consent` |
| Academic year | `academic_year` |
| Medium | `medium_code` |
| Grades to teach | `grade_codes` |
| Streams to teach | `stream_codes` |
| Subjects to teach | `subject_codes` |
| Moodle role | `role_shortname` |
| Declaration teacher details are correct | `declaration_correct` |
| Submitted by name | `submitted_by_name` |
| Submitted by mobile | `submitted_by_mobile` |

Dropdown and checkbox values can include labels. The script keeps only the first code:

```text
ENG - English Medium -> ENG
STD05 - Standard 5 -> STD05
MATH - Mathematics -> MATH
editingteacher - Editing teacher -> editingteacher
```

Checkbox values are converted to pipe-delimited internal lists before matching courses:

```text
STD05, STD06 -> STD05|STD06
MATH, SCI -> MATH|SCI
```

## 8. Validated Output Mapping To Master Sheets

This mapping has been checked against the current master workbook headers and the Apps Script in:

```text
integrations/google_forms/teacher_registration/Code.gs
```

The script writes to the current workbook tabs:

| Target tab | Header row | First data row | CLI export |
|---|---:|---:|---|
| `19_users_staff` | 5 | 7 | `registration/combined/19_users_staff.csv` |
| `23_role_assignments` | 5 | 7 | `years/<academic-year>/role_assignments.csv` |
| `12_courses` | 5 | 7 | Source only; do not export from teacher form workflow |

Do not use older tab names such as `23_users_staff`, `27_role_assignments`, or `16_courses`. Those names are from an older workbook structure and will not match the current master pack.

### `19_users_staff` Mapping

| Workbook column group | Source |
|---|---|
| `username`, `password`, `auth`, `idnumber` | Generated by Apps Script |
| `firstname`, `lastname`, `email`, `phone1`, `phone2`, `address` | Teacher form fields |
| `city`, `country`, `timezone`, `lang`, `institution`, `department` | Form values plus config defaults |
| `profile_field_employee_code` | Employee code form field or generated teacher idnumber |
| `profile_field_staff_designation` | Designation |
| `profile_field_staff_department` | Department |
| `profile_field_staff_joining_date` | Joining date |
| `profile_field_staff_qualification` | Qualification |
| `profile_field_staff_type` | Staff type |
| Aadhaar profile fields | Teacher Aadhaar last 4, masked value, and consent |

Teacher-specific generated values:

| Column | Formula |
|---|---|
| `username` | `dps.tch.<medium>.<first_subject>` with suffix if already used |
| `idnumber` | `DPS-TCH-###` |
| `profile_field_employee_code` | Submitted employee code, otherwise `DPS-TCH-###` |

### `23_role_assignments` Mapping

| Workbook column | Source |
|---|---|
| `username` | Newly generated teacher username |
| `role_shortname` | Moodle role form field, usually `editingteacher` or `teacher` |
| `context_type` | Generated as `course` |
| `context_identifier` | Matching `12_courses.course_code` |
| `notes` | Generated note with matching course shortname or course code |

### Course Matching Source: `12_courses`

Apps Script reads `12_courses` and creates one `23_role_assignments` row for every course matching:

```text
academic_year
medium_code
grade_code
stream_code
subject_code
```

The teacher form does not create or edit `12_courses`. Run the workbook macros and validate course generation before using teacher registration for a new academic year.

### Required Relationship Checks

Before CSV export, verify these relationships:

| Check | Expected result |
|---|---|
| Every new `23_role_assignments.username` exists in `19_users_staff.username` | Required |
| Every `23_role_assignments.context_identifier` exists in `12_courses.course_code` | Required |
| `role_shortname` is a valid Moodle role, usually `editingteacher` or `teacher` | Required |
| `STD01` to `STD10` use stream `GEN` | Required |
| `STD11` and `STD12` use one of `SCI`, `COM`, `ARTS` | Required |
| At least one matching course exists for the selected academic year, medium, grade, stream, and subject combination | Required |

The script performs basic validation during form submission. The CLI pack validation must still be run before import because it checks cross-file relationships against generated courses and Moodle role rules.

## 9. Generated System Values

Do not ask teachers or office users to enter these values. Apps Script generates them.

| Generated value | Formula |
|---|---|
| Teacher username | `dps.tch.<medium>.<subject>` with numeric suffix if already used |
| Teacher password | `DronaTeacher2026!` |
| Teacher idnumber | `DPS-TCH-###` |
| Employee code | Uses submitted employee code, otherwise generated idnumber |
| Moodle auth | `manual` |
| Country | `IN` |
| Timezone | `Asia/Kolkata` |
| Staff designation | `TEACHER` unless changed in the form |
| Staff type | `Teaching` |
| Role assignment context | `course` |
| Role assignment context identifier | Matching `course_code` from `12_courses` |

Username examples:

```text
dps.tch.eng.math
dps.tch.guj.guj
dps.tch.hin.science
dps.tch.eng.math_070
```

The username must satisfy the CLI-pack validation rule:

```text
dps.tch.<lowercase_scope>.<lowercase_subject_or_suffix>
```

Teacher idnumber must satisfy:

```text
DPS-TCH-###
```

## 10. How Moodle Course Access Is Generated

The teacher form does not directly enrol teachers into every course by hand. Apps Script reads `12_courses` and finds matching courses using:

```text
academic_year
medium_code
grade_code
stream_code
subject_code
```

For each matching course, it appends one row to `23_role_assignments`:

```text
username,role_shortname,context_type,context_identifier,notes
dps.tch.eng.math,editingteacher,course,DPS-GSEB-ENG-STD05-GEN-MATH-2026,Google Form teacher registration for DPS-GSEB-ENG-STD05-GEN-MATH-26.
```

Moodle import then grants the teacher access to those courses.

## 11. Teacher Registration Use Cases

### Use Case A: One Teacher, One Subject, One Grade

Example:

```text
Medium: ENG
Grades to teach: STD05
Streams to teach: GEN
Subjects to teach: MATH
Moodle role: editingteacher
```

Output:

```text
19_users_staff: one teacher row
23_role_assignments: one course role row for STD05 ENG GEN MATH
```

### Use Case B: One Teacher, Same Subject, Multiple Grades

Example:

```text
Medium: GUJ
Grades to teach: STD01, STD02, STD03
Streams to teach: GEN
Subjects to teach: GUJ
```

Output:

```text
19_users_staff: one teacher row
23_role_assignments: one role row per matching course
```

### Use Case C: One Teacher, Multiple Subjects

Example:

```text
Medium: ENG
Grades to teach: STD05
Streams to teach: GEN
Subjects to teach: MATH, SCI
```

Output:

```text
19_users_staff: one teacher row
23_role_assignments: two course role rows if both courses exist
```

### Use Case D: Higher Secondary Teacher

Example:

```text
Medium: ENG
Grades to teach: STD11, STD12
Streams to teach: SCI
Subjects to teach: PHY, CHEM
```

Output:

```text
Role assignments only for matching Science stream courses.
```

### Use Case E: Non-editing Teacher

Use Moodle role:

```text
teacher - Non-editing teacher
```

This can be used for assistant teachers or observers who should not edit course content.

### Use Case F: Principal Or IT Coordinator

Do not use the teacher form for principal, trustee manager, or IT coordinator. They need category-level role assignments, not course-level subject matching.

Use:

```text
registration/combined/19_users_staff.csv
years/<year>/role_assignments.csv
```

Examples:

```text
principal.sharma,principal,category,DRONA_TRUST_GSEB_DPS,School principal access.
it.coordinator,manager,category,DRONA_TRUST_GSEB_DPS,IT coordinator category manager access.
```

## 12. Export Back To CLI Pack

After submissions are verified in Google Sheets, export these tabs as CSV:

| Google Sheets tab | Export path |
|---|---|
| `19_users_staff` | `registration/combined/19_users_staff.csv` |
| `23_role_assignments` | `years/2026-2027/role_assignments.csv` |

If the teacher is for a future year, export `23_role_assignments` to that year folder:

```text
years/2027-2028/role_assignments.csv
years/2028-2029/role_assignments.csv
years/2029-2030/role_assignments.csv
```

Do not export the raw Google Form response tab into the CLI pack.

## 13. Validate And Import

Run validation:

```bash
cd /Users/hardik.chauhan/Documents/learning/eLearnMindset/research/drona_public_school

scripts/validate_all.sh 2026-2027
```

Review:

```text
build/reports/2026-2027/preflight_report.md
```

Dry-run:

```bash
scripts/import.sh 2026-2027 dry-run
```

Live import after backup, review, and approval:

```bash
scripts/import.sh 2026-2027 live
```

## 14. Troubleshooting

### Form response is saved but no teacher row is created

Check:

```text
Apps Script -> Executions
```

Then run:

```text
debugLatestTeacherResponseKeys
```

Common cause: Google Form question title does not match `canonicalKey()` mapping.

### No matching courses found

The script matches against `12_courses`.

Check:

```text
Academic year
Medium
Grade
Stream
Subject
```

The selected combination must exist as a course.

### Teacher already exists

The script blocks duplicate staff when `email` or `phone1` already exists in `19_users_staff`.

If this is an access update for an existing teacher, add only new rows in `23_role_assignments`.

### CLI validation fails

Run:

```bash
scripts/validate_all.sh 2026-2027
```

Common failures:

- Teacher username does not match `dps.tch.<scope>.<subject>`.
- Teacher idnumber does not match `DPS-TCH-###`.
- Role assignment references a course code that does not exist.
- Role assignment username does not exist in staff users.

## 15. Production Controls

For real staff data:

- Restrict teacher form access to office/admin users only.
- Do not collect full Aadhaar numbers. Store only last 4 digits and consent.
- Require school office approval before exporting CSV.
- Keep backups before live Moodle import.
- Do not commit real staff CSV files to Git.
