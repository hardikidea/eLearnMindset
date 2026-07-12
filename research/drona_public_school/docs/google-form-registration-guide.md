# Google Form Registration Guide

This guide explains how to collect school registration data through Google Forms, map it into the school master workbook sheets, export the mapped data back to the CLI pack CSV files, validate it, and import it into Moodle.

The workflow is aligned to the current school master pack:

```text
Google Form
  -> Google Form response sheet
  -> Apps Script validation and mapping
  -> Master workbook tabs
  -> CSV export
  -> CLI pack validation
  -> Moodle dry-run/live import
```

Important limitation: Google Forms cannot write directly to the local `.ods` file in this repository. Use a Google Sheets copy of the workbook as the intake workbook, then export the mapped tabs to the CLI pack CSV files.

## 1. Source Files And Target Sheets

Master workbook file:

```text
build/master_excel/school_master_pack_2026_2027_full_predefined_master_macros.ods
```

Google Sheets target tabs:

| Registration data | Google Sheets tab | CLI pack CSV |
|---|---|---|
| Student Moodle user | `24_users_students` | `registration/combined/20_users_students.csv` |
| Parent Moodle user | `25_users_parents` | `registration/combined/21_users_parents.csv` |
| Parent-student link | `28_parent_links` | `registration/parent_links.csv` |
| Optional sibling links | Dedicated/manual sheet or CSV edit | `registration/sibling_links.csv` |
| Optional emergency contacts | Dedicated/manual sheet or CSV edit | `registration/emergency_contacts.csv` |
| Optional health records | Dedicated/manual sheet or CSV edit | `registration/student_health.csv` |
| Optional transport records | Dedicated/manual sheet or CSV edit | `registration/student_transport.csv` |
| Staff and teacher users | Staff intake sheet or CSV edit | `registration/combined/19_users_staff.csv` |

Rows 1 to 6 in the master workbook sheets are metadata, contract, formula, header, and example rows. Apps Script writes real records from row 7 onward.

## 2. Create The Google Sheets Intake Workbook

1. Upload the ODS file to Google Drive.
2. Open it with Google Sheets.
3. Save it as:

```text
School Master Pack - Registration Intake
```

4. Confirm these tabs exist:

```text
24_users_students
25_users_parents
28_parent_links
```

5. Create two optional audit tabs if you want to see failures and successful generated accounts:

```text
_intake_errors
_intake_audit
```

Apps Script also creates these audit tabs automatically if they do not exist.

## 3. Create The Student And Parent Google Form

Form name:

```text
Drona Public School - Student & Parent Registration
```

Form description:

```text
Use this form to register a student and parent/guardian account for Moodle. Please enter accurate details. School office will verify the information before import.
```

Recommended form settings:

| Setting | Value |
|---|---|
| Collect email addresses | Optional |
| Limit to 1 response | No |
| Allow response editing | No |
| Show progress bar | Yes |
| Shuffle question order | No |
| Confirmation message | Thank you. Your registration has been submitted for school verification. |

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

### Section 1: Student Basic Details

Section description:

```text
Enter the student identity details exactly as per school/admission records.
```

| Question title | Type | Required | Validation or hint |
|---|---|---:|---|
| Student first name | Short answer | Yes | Example: Rohan |
| Student last name | Short answer | Yes | Example: Patel |
| Date of birth | Date | Yes | Official date of birth |
| Gender | Dropdown | Yes | Male, Female, Other |
| Blood group | Dropdown | No | A+, A-, B+, B-, AB+, AB-, O+, O-, Unknown |
| Religion | Short answer | No | Optional |
| Category | Dropdown | No | General, SC, ST, OBC, EWS, Other |
| Caste | Short answer | No | Optional |
| Nationality | Short answer | Yes | Default: Indian |
| Mother tongue | Dropdown | Yes | Gujarati, Hindi, English, Other |

### Section 2: Academic Placement

Section description:

```text
Select the current academic placement. These values must match the school master data.
```

| Question title | Type | Required | Standard values |
|---|---|---:|---|
| Academic year | Dropdown | Yes | 2026-2027, 2027-2028, 2028-2029, 2029-2030 |
| Board | Dropdown | Yes | GSEB - Gujarat Board Education |
| Medium | Dropdown | Yes | GUJ - Gujarati Medium, ENG - English Medium, HIN - Hindi Medium |
| Grade | Dropdown | Yes | STD01 to STD12 |
| Stream | Dropdown | Yes | GEN - General, SCI - Science, COM - Commerce, ARTS - Arts / Humanities |
| Division | Dropdown | Yes | A - Division A through F - Division F |
| Admission date | Date | Yes | Official admission date |
| Roll number | Short answer | No | Leave blank if school will assign later |
| House | Dropdown | No | ARYA - Aryabhatta House, TAG - Tagore House, GAN - Gandhi House, RAM - Raman House |

Academic rule:

```text
STD01 to STD10 must use GEN.
STD11 and STD12 can use SCI, COM, or ARTS.
```

### Section 3: Student IDs

Section description:

```text
Government or school reference IDs. Optional IDs can be updated later by the school office.
```

| Question title | Type | Required | Validation or hint |
|---|---|---:|---|
| Student Aadhaar last 4 digits | Short answer | No | Regex: `^[0-9]{4}$` |
| Student Aadhaar consent | Multiple choice | Yes | Yes, No |
| APAAR ID | Short answer | No | Optional |
| UDISE student code | Short answer | No | Optional |
| SARAL ID | Short answer | No | Optional |

### Section 4: Address Details

Section description:

```text
Enter current and permanent address details for communication and school records.
```

| Question title | Type | Required | Validation or hint |
|---|---|---:|---|
| Current address line 1 | Short answer | Yes | House, society, street |
| Current address line 2 | Short answer | No | Area or landmark |
| Current city | Short answer | Yes | Example: Ahmedabad |
| Current taluka | Short answer | No | Optional |
| Current district | Short answer | Yes | Example: Ahmedabad |
| Current state | Short answer | Yes | Default: Gujarat |
| Current PIN code | Short answer | Yes | Regex: `^[1-9][0-9]{5}$` |
| Permanent address same as current? | Multiple choice | Yes | Yes, No |
| Permanent address line 1 | Short answer | No | Required by office only when different |
| Permanent address line 2 | Short answer | No | Optional |
| Permanent city | Short answer | No | Required by office only when different |
| Permanent taluka | Short answer | No | Optional |
| Permanent district | Short answer | No | Required by office only when different |
| Permanent state | Short answer | No | Required by office only when different |
| Permanent PIN code | Short answer | No | Regex: `^[1-9][0-9]{5}$` |

### Section 5: Primary Parent Login

Section description:

```text
This parent or guardian will receive Moodle parent access for the student.
```

| Question title | Type | Required | Validation or hint |
|---|---|---:|---|
| Parent account type | Dropdown | Yes | Father, Mother, Guardian |
| Parent first name | Short answer | Yes | Example: Amit |
| Parent last name | Short answer | Yes | Example: Patel |
| Parent mobile number | Short answer | Yes | Regex: `^[6-9][0-9]{9}$` |
| Parent email | Short answer | Yes | Google Forms email validation or regex in Apps Script |
| Parent occupation | Short answer | No | Optional |
| Parent qualification | Short answer | No | Optional |
| Parent annual income | Short answer | No | Optional |
| Preferred language | Dropdown | Yes | gu - Gujarati, en - English, hi - Hindi |
| Parent Aadhaar last 4 digits | Short answer | No | Regex: `^[0-9]{4}$` |
| Parent Aadhaar consent | Multiple choice | Yes | Yes, No |

### Section 6: Father, Mother, And Guardian Details

Section description:

```text
These details are stored in the student profile for school records.
```

| Question title | Type | Required | Validation or hint |
|---|---|---:|---|
| Father name | Short answer | No | Full name |
| Father mobile | Short answer | No | Regex: `^[6-9][0-9]{9}$` |
| Father email | Short answer | No | Email format |
| Father occupation | Short answer | No | Optional |
| Father qualification | Short answer | No | Optional |
| Mother name | Short answer | No | Full name |
| Mother mobile | Short answer | No | Regex: `^[6-9][0-9]{9}$` |
| Mother email | Short answer | No | Email format |
| Mother occupation | Short answer | No | Optional |
| Mother qualification | Short answer | No | Optional |
| Guardian name | Short answer | No | Required if parent account type is Guardian |
| Guardian mobile | Short answer | No | Regex: `^[6-9][0-9]{9}$` |
| Guardian email | Short answer | No | Email format |
| Guardian occupation | Short answer | No | Optional |
| Guardian qualification | Short answer | No | Optional |

### Section 7: Health, Emergency, And Transport

Section description:

```text
Used by school staff for safety, emergency, and transport coordination.
```

| Question title | Type | Required | Validation or hint |
|---|---|---:|---|
| Emergency contact name | Short answer | Yes | Full name |
| Emergency contact relation | Short answer | Yes | Example: Father, Mother, Uncle |
| Emergency mobile | Short answer | Yes | Regex: `^[6-9][0-9]{9}$` |
| Alternate emergency mobile | Short answer | No | Regex: `^[6-9][0-9]{9}$` |
| Medical conditions | Paragraph | No | Write None if not applicable |
| Allergies | Paragraph | No | Write None if not applicable |
| Family doctor name | Short answer | No | Optional |
| Doctor phone | Short answer | No | Regex: `^[0-9]{6,15}$` |
| Transport required? | Multiple choice | Yes | Yes, No |
| Bus route | Short answer | No | Required only if transport required |
| Pickup point | Short answer | No | Required only if transport required |

### Section 8: Additional Student Status

Section description:

```text
Used for school classification and reporting.
```

| Question title | Type | Required | Standard values |
|---|---|---:|---|
| RTE category? | Multiple choice | No | Yes, No |
| BPL category? | Multiple choice | No | Yes, No |
| Disability status | Dropdown | No | None, Learning, Physical, Visual, Hearing, Other |
| Sibling admission number | Short answer | No | Optional |

### Section 9: Consent And Declaration

Section description:

```text
Parent or guardian confirmation is required before registration can be processed.
```

| Question title | Type | Required | Validation or hint |
|---|---|---:|---|
| I consent to school storing student and parent data for LMS and school operations | Checkbox | Yes | Option: I agree |
| I confirm all details are correct | Checkbox | Yes | Option: I confirm |
| Submitted by name | Short answer | Yes | Parent or guardian name |
| Submitted by mobile | Short answer | Yes | Regex: `^[6-9][0-9]{9}$` |

## 5. Form Response Validation

Configure validation inside Google Forms for fields that must be checked before submission.

Open each question:

```text
Question -> three-dot menu -> Response validation
```

Use these rules:

| Field | Google Forms validation | Regex |
|---|---|---|
| Mobile numbers | Regular expression -> Matches | `^[6-9][0-9]{9}$` |
| PIN code | Regular expression -> Matches | `^[1-9][0-9]{5}$` |
| Aadhaar last 4 digits | Regular expression -> Matches | `^[0-9]{4}$` |
| Doctor phone | Regular expression -> Matches | `^[0-9]{6,15}$` |
| Email | Text -> Email address | Built-in |

Custom error messages:

| Field type | Message |
|---|---|
| Mobile | Enter a valid 10 digit Indian mobile number. |
| PIN code | Enter a valid 6 digit Indian PIN code. |
| Aadhaar last 4 | Enter only the last 4 digits of Aadhaar. |
| Email | Enter a valid email address. |

Apps Script repeats the same validations because form validation can be bypassed by copied sheets or manual changes.

## 6. Apps Script Project Setup

Starter project files are included in this repository:

```text
integrations/google_forms/student_parent_registration/appsscript.json
integrations/google_forms/student_parent_registration/Code.gs
```

Setup steps:

1. Open the Google Sheets intake workbook.
2. Go to:

```text
Extensions -> Apps Script
```

3. Replace the default script with the contents of:

```text
integrations/google_forms/student_parent_registration/Code.gs
```

4. In project settings, confirm:

```text
Time zone: Asia/Kolkata
Runtime: V8
```

5. Save the project.
6. Select and run:

```text
installStudentRegistrationTrigger
```

7. Approve the Google authorization prompt.

The trigger listens for form submissions and runs:

```text
onStudentRegistrationSubmit
```

## 7. Form Title To Script Key Mapping

Apps Script maps human-friendly question titles to CLI-pack field keys. Example:

```text
Student first name -> student_firstname
Parent mobile number -> parent_mobile
Current PIN code -> current_pincode
```

The mapping is implemented in:

```text
canonicalKey(title)
```

If you rename a Google Form question, either keep the same normalized meaning or update `canonicalKey()`.

Important mappings:

| Question title | Apps Script key |
|---|---|
| Student first name | `student_firstname` |
| Student last name | `student_lastname` |
| Date of birth | `student_birth_date` |
| Academic year | `academic_year` |
| Board | `board_code` |
| Medium | `medium_code` |
| Grade | `grade_code` |
| Stream | `stream_code` |
| Division | `division_code` |
| Current PIN code | `current_pincode` |
| Parent first name | `parent_firstname` |
| Parent last name | `parent_lastname` |
| Parent mobile number | `parent_mobile` |
| Parent email | `parent_email` |
| Emergency mobile | `emergency_contact_mobile` |
| I consent to school storing student and parent data for LMS and school operations | `consent_student_data` |
| I confirm all details are correct | `declaration_correct` |

Dropdown values can include labels. The script keeps only the first code:

```text
ENG - English Medium -> ENG
STD05 - Standard 5 -> STD05
GEN - General -> GEN
A - Division A -> A
```

This is implemented in:

```text
normalizeDropdownCodes(data)
firstCode(value)
```

## 8. Generated System Values

Do not ask parents or office users to enter these values. Apps Script generates them.

| Generated value | Formula |
|---|---|
| Student username | `dps.stu.<00001>` |
| Student password | `DronaStudent2026!` |
| Student email | `<student_username>@students.dronapublicschool.example` |
| Student idnumber | `DPS<YY>-<00001>` |
| Admission number | `DPS<YY>-<00001>` |
| Student GR number | `GR-DPS-<START_YEAR>-<00001>` |
| Parent username | `dps.par.<00001>` |
| Parent password | `DronaParent2026!` |
| Parent idnumber | `DPS-PAR-<00001>` |
| Cohort | `DPS-<START_YEAR>-GSEB-<MEDIUM>-<GRADE>-<STREAM>-<DIVISION>` |
| Parent role | `parent` |
| Parent grade/activity access | `1` |
| Student status | `ACTIVE` |
| Auth type | `manual` |
| Country | `IN` |
| Timezone | `Asia/Kolkata` |

## 9. Registration Use Cases

### Use Case A: New Student With New Parent

Use this when both student and parent do not exist.

Input:

```text
Student: new
Parent email/mobile: new
```

Output:

```text
24_users_students: new student row
25_users_parents: new parent row
28_parent_links: new parent-student link row
```

### Use Case B: New Student With Existing Parent

Use this when a parent already has one child and submits another child.

Input:

```text
Student: new
Parent email/mobile: already exists in 25_users_parents
```

Output:

```text
24_users_students: new student row
25_users_parents: no duplicate parent row
28_parent_links: new link to existing parent
```

The Apps Script reuses an existing parent by matching `email` or `phone1`.

### Use Case C: Add Another Parent For Existing Student

Use this for adding mother/father/guardian access later.

Recommended process:

1. Use a separate office-only Google Form.
2. Ask for existing student username or admission number.
3. Create/reuse parent in `25_users_parents`.
4. Append only `28_parent_links`.
5. Validate before import.

Do not create a duplicate student row.

### Use Case D: Staff Or Teacher Registration

Use this for teachers, office staff, principal, trustee manager, or IT coordinator.

Target CSV:

```text
registration/combined/19_users_staff.csv
```

Minimum fields:

| Field | Purpose |
|---|---|
| `username` | Moodle login |
| `password` | Initial password |
| `firstname`, `lastname` | Staff name |
| `email` | Unique Moodle email |
| `auth` | `manual` |
| `city`, `country`, `timezone`, `lang` | Profile defaults |
| `institution` | School name |
| `department` | Department or subject |
| `idnumber` | Staff ID |
| `phone1` | Staff mobile |
| `profile_field_employee_code` | Employee code |
| `profile_field_staff_designation` | Teacher, Principal, Trustee, IT Coordinator |
| `profile_field_staff_department` | Academic, Admin, IT |
| `profile_field_staff_type` | Teaching, Non-teaching, Leadership |

Teacher course access is not controlled only by this user row. Teacher access is assigned through:

```text
years/<year>/role_assignments.csv
```

For a new teacher, add the staff user first, then add role assignment rows for the courses/categories they manage.

### Use Case E: Principal, Trustee, Manager, IT Coordinator

These are staff users plus category-level role assignments.

Target files:

```text
registration/combined/19_users_staff.csv
years/<year>/role_assignments.csv
```

Examples:

| Role | Context | Purpose |
|---|---|---|
| Principal | School category | Can review school-level users/courses/reports |
| Trustee manager | Trust category | Can review multiple school structures |
| IT coordinator | School category | Can support setup and technical operations |

### Use Case F: Health, Transport, Emergency, Sibling Data

The student form captures these fields into `24_users_students` profile fields. If the school wants separate operational registers, maintain these optional CSVs:

```text
registration/emergency_contacts.csv
registration/student_health.csv
registration/student_transport.csv
registration/sibling_links.csv
```

These files are source-of-truth supporting registers. The current Moodle baseline user import primarily consumes the combined user and parent-link CSVs, so keep optional registers validated and synchronized with student usernames.

## 10. Export Back To CLI Pack

After submissions are verified in Google Sheets, export these tabs as CSV:

| Google Sheets tab | Export path |
|---|---|
| `24_users_students` | `registration/combined/20_users_students.csv` |
| `25_users_parents` | `registration/combined/21_users_parents.csv` |
| `28_parent_links` | `registration/parent_links.csv` |

Keep the header row exactly aligned with the CLI pack CSV headers. Do not export the raw Google Form response tab into the CLI pack.

## 11. Validate And Import

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

## 12. Troubleshooting

### Form response is saved but target sheets are empty

Check:

```text
Apps Script -> Executions
```

Then run:

```text
debugLatestResponseKeys
```

Review:

```text
View -> Logs
```

Common cause: Google Form question title does not match `canonicalKey()` mapping.

### Parent duplicate created

The starter script checks existing parent by:

```text
email
phone1
```

If your form allowed different email/mobile for the same person, fix the duplicate in `25_users_parents` and keep only one parent username in `28_parent_links`.

### Wrong stream for grade

Rule:

```text
STD01 to STD10 -> GEN only
STD11 to STD12 -> SCI, COM, or ARTS
```

The script blocks invalid combinations.

### CSV validation fails after export

Run:

```bash
scripts/validate_all.sh 2026-2027
```

Fix source rows in the Google Sheets target tabs, export again, and rerun validation.

## 13. Production Controls

For real school data:

- Restrict Google Form edit access to school admins only.
- Restrict response sheet access to authorized office and IT users.
- Do not collect full Aadhaar numbers in Google Forms. Store only last 4 digits and consent.
- Keep a separate approval status outside raw form responses if admissions require verification.
- Export and import only after school office approval.
- Keep backups before live Moodle import.
- Do not commit real student/parent CSV files to Git.
