# Academic Year Change Workflow: Promote Students Without Re-Registration

This guide explains how to move students to the next academic year without creating new Moodle user accounts. The student keeps the same username, password, profile, parent links, and history. Only the student's academic-year profile fields and cohort memberships change.

## Core Principle

Do not re-register existing students every year.

Use this model:

```text
One student = one Moodle user account for the full school lifecycle.
One academic year = new courses + new cohorts + new groups + cohort enrolment mappings.
Promotion = add existing user to next-year cohort and update profile fields.
```

This preserves:

- Student login credentials.
- Parent-child links.
- Historical grade/course records.
- User profile and audit trail.
- Old-year reports, submissions, grades, and completion data.

## Moodle Object Model

| School concept | Moodle object | Year-change behavior |
|---|---|---|
| Student identity | User account | Reuse existing account. Do not create duplicate user. |
| Parent/guardian login | User account + parent role in student context | Keep existing parent user and existing parent link. |
| Current class/division batch | Cohort | Add student to next-year cohort. |
| Subject access | Cohort enrolment into Moodle course | Next-year cohort automatically enrols student into next-year subject courses. |
| Division | Group inside course | Cohort enrolment mapping places students into target division group. |
| Old-year subjects | Old Moodle courses | Keep for reports/archive; hide only after approval. |
| New-year subjects | New Moodle courses | Create before promotion. |
| Student current class metadata | Custom profile fields | Update after promotion. |

## Recommended Yearly Timeline

```text
March-April
  Freeze old-year assessments.
  Finish gradebook, attendance, completion, report exports.

April-May
  Review promotion decisions.
  Prepare next-year course/cohort/group/enrolment CSV files.

May-June
  Create next-year Moodle courses and cohorts.
  Dry-run student promotion.
  Execute student promotion after approval.

June
  Students log in with same username/password.
  My courses shows new academic-year courses.

After audit approval
  Hide old-year courses if needed.
  Remove old cohort membership only when old reports are archived.
```

## CSV Files Used

### Next-year object creation

These files create the new academic-year structure:

```text
next_year_courses_2027_2028.csv
next_year_cohorts_2027_2028.csv
next_year_groups_2027_2028.csv
next_year_enrolments_2027_2028.csv
alumni_cohorts_2027.csv
```

### Student movement

Use one of these promotion input models:

```text
promotion_actions.csv
student_promotion_plan_2027_2028.csv
```

Recommended default for this pack:

```text
promotion_actions.csv
```

It maps directly to `cli_promote_academic_year.php` and uses the actual profile fields in `user_profile_fields.csv`, such as:

```text
current_academic_year
current_board_code
current_school_code
current_medium_code
current_grade_code
current_stream_code
current_division_code
previous_academic_year
previous_grade_code
previous_stream_code
previous_division_code
student_status
last_promotion_date
last_promotion_result
```

## High-Level Workflow

```text
1. Keep existing student users.
2. Create next-year courses.
3. Create next-year cohorts.
4. Create next-year groups.
5. Create next-year cohort enrolment mappings.
6. Add existing students to target next-year cohorts.
7. Update student current academic profile fields.
8. Keep old cohort membership until old reports are archived.
9. Verify My courses, participants, grades, reports, and parent access.
```

## Step 1: Backup Before Year Change

Run a backup before changing enrolments:

```bash
cd /Users/hardik.chauhan/Documents/learning/eLearnMindset

./scripts/backup.sh
```

On production, also take:

- Database snapshot.
- `moodledata` backup or EFS backup.
- Export of old-year gradebooks.
- Export of attendance/plugin reports if used.

## Step 2: Prepare Target Academic-Year CSVs

Review these files:

```text
next_year_courses_2027_2028.csv
next_year_cohorts_2027_2028.csv
next_year_groups_2027_2028.csv
next_year_enrolments_2027_2028.csv
alumni_cohorts_2027.csv
promotion_actions.csv
```

For each promoted student, confirm:

- `username` is the existing Moodle username.
- `from_cohort_code` is the current year cohort.
- `to_cohort_code` is the next year cohort.
- `to_grade_code`, `to_stream_code`, and `to_division_code` are correct.
- `remove_from_old_cohort` is `0` for first run.
- `update_profile_fields` is `1`.

Example row:

```csv
action,username,student_idnumber,from_academic_year,to_academic_year,from_cohort_code,to_cohort_code,from_board_code,from_school_code,from_medium_code,from_grade_code,from_stream_code,from_division_code,to_board_code,to_school_code,to_medium_code,to_grade_code,to_stream_code,to_division_code,result_status,remove_from_old_cohort,update_profile_fields,effective_date,approved_by,remarks
PROMOTE,gvs.gseb.guj.std10.b.002,GVS-STU-002,2026-2027,2027-2028,GVS-2026-GSEB-GUJ-STD10-GEN-B,GVS-2027-GSEB-GUJ-STD11-SCI-A,GSEB,GVS,GUJ,STD10,GEN,B,GSEB,GVS,GUJ,STD11,SCI,A,Promoted,0,1,2027-06-01,principal.gvs,Promoted to Std 11 Science
```

## Step 3: Copy Pack and CLI Scripts Into Moodle Container

```bash
PACK_HOST="$PWD/research/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
PACK_CONTAINER="/tmp/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
APP_CONTAINER="$(docker compose ps -q moodle)"

cp "$PACK_HOST/cli_prepare_next_academic_year.php" moodle/admin/cli/
cp "$PACK_HOST/cli_promote_academic_year.php" moodle/admin/cli/

docker compose exec -u root -T moodle rm -rf "$PACK_CONTAINER"
docker cp "$PACK_HOST" "$APP_CONTAINER:$PACK_CONTAINER"
```

## Step 4: Create Next-Year Courses, Cohorts, Groups, and Enrolment Mappings

Dry run:

```bash
docker compose exec -T moodle php admin/cli/cli_prepare_next_academic_year.php \
  --dir="$PACK_CONTAINER" \
  --dry-run=1
```

Execute:

```bash
docker compose exec -T moodle php admin/cli/cli_prepare_next_academic_year.php \
  --dir="$PACK_CONTAINER" \
  --dry-run=0
```

This creates:

- Next-year subject courses.
- Next-year class/division cohorts.
- Groups inside next-year courses.
- Cohort-sync enrolments from target cohorts into target courses.
- Alumni cohorts where applicable.

## Step 5: Dry-Run Student Promotion

Dry run with the safe default:

```bash
docker compose exec -T moodle php admin/cli/cli_promote_academic_year.php \
  --dir="$PACK_CONTAINER" \
  --file=promotion_actions.csv \
  --dry-run=1 \
  --remove-old-cohort=0
```

Review the output carefully.

Check for:

- Missing users.
- Missing target cohorts.
- Wrong target grade/stream/division.
- Unexpected old-cohort removal.

## Step 6: Execute Student Promotion

Execute after approval:

```bash
docker compose exec -T moodle php admin/cli/cli_promote_academic_year.php \
  --dir="$PACK_CONTAINER" \
  --file=promotion_actions.csv \
  --dry-run=0 \
  --remove-old-cohort=0
```

The script will:

- Add each student to the target next-year cohort.
- Keep the existing user account.
- Update promotion-related profile fields.
- Write `promotion_run_log.csv` inside the pack directory.

Important: keep `--remove-old-cohort=0` until old-year reports are archived.

## Step 7: Sync Cohort Enrolments and Purge Cache

If needed, run Moodle cron or wait for scheduled cron:

```bash
docker compose exec -T moodle php admin/cli/cron.php
```

Then purge cache:

```bash
docker compose exec -T moodle php admin/cli/purge_caches.php
```

## Step 8: Verify Student Access

Login as the same existing student:

```text
username: existing student username
password: existing password
```

Expected behavior:

- Student does not create a new account.
- `My courses` shows new-year subject courses.
- Old-year courses may still be visible if old cohort membership remains.
- New course participants include the student.
- New course group shows correct division.

Useful DB checks:

```bash
docker compose exec -T db psql -U moodle -d moodle -tAc "
select u.username, ch.idnumber as cohort
from mdl_cohort_members cm
join mdl_user u on u.id = cm.userid
join mdl_cohort ch on ch.id = cm.cohortid
where u.username = 'gvs.gseb.guj.std10.b.002'
order by ch.idnumber;
"
```

Check new course enrolment:

```bash
docker compose exec -T db psql -U moodle -d moodle -tAc "
select u.username, c.shortname, e.enrol, ue.status
from mdl_user u
join mdl_user_enrolments ue on ue.userid = u.id
join mdl_enrol e on e.id = ue.enrolid
join mdl_course c on c.id = e.courseid
where u.username = 'gvs.gseb.guj.std10.b.002'
order by c.shortname;
"
```

## Step 9: Verify Teacher and Course Assignment

For every next-year course, confirm the teacher has:

- Course role assignment.
- Active manual enrolment if the teacher should see it in `My courses`.

Teacher assignment options:

1. Add/update rows in `role_assignments.csv` for next-year courses.
2. Run the baseline importer again after next-year course rows exist.
3. Or assign teachers manually in Moodle UI.

Recommended CSV row pattern:

```csv
username,role_shortname,context_type,context_identifier,notes
teacher.phy,editingteacher,course,GVS-GSEB-GUJ-12-SCI-PHY-27,Next-year Physics teacher assignment
```

After teacher assignment, verify:

```bash
docker compose exec -T db psql -U moodle -d moodle -tAc "
select u.username, c.shortname, r.shortname as role, e.enrol, ue.status
from mdl_user u
join mdl_role_assignments ra on ra.userid = u.id
join mdl_role r on r.id = ra.roleid
join mdl_context ctx on ctx.id = ra.contextid and ctx.contextlevel = 50
join mdl_course c on c.id = ctx.instanceid
left join mdl_enrol e on e.courseid = c.id and e.enrol = 'manual'
left join mdl_user_enrolments ue on ue.enrolid = e.id and ue.userid = u.id
where u.username = 'teacher.phy'
order by c.shortname;
"
```

## Step 10: Parent Access

Parents do not need re-registration.

Existing parent links remain valid because parent links are assigned to the student user context, not to one academic-year cohort.

After promotion, verify parent can still open the linked student profile/reports according to your parent role permissions.

Only create a new parent user when:

- A new guardian joins.
- Parent email/login changes and the school wants a separate account.
- A student is newly admitted.

## Step 11: Old-Year Courses and Cohorts

Do not delete old-year courses immediately.

Recommended old-year policy:

```text
Immediately after promotion:
  Keep old courses and old cohort memberships.
  This keeps old reports easy to access.

After report export and approval:
  Hide old courses or old categories.
  Keep old courses for audit retention.

Only after archive approval:
  Optionally remove old cohort memberships.
```

If you remove old cohort membership too early, Moodle cohort sync may remove old-year course enrolment and make old reports harder to access.

## Common Scenarios

### Std 1 to Std 2

Use the same student account. Add the student to the next-year Std 2 division cohort.

```text
from: GVS-2026-GSEB-GUJ-STD01-GEN-A
to:   GVS-2027-GSEB-GUJ-STD02-GEN-A
```

### Std 10 to Std 11 Science

This is a stream-selection workflow. Choose target stream and division in `promotion_actions.csv`.

```text
from_grade_code: STD10
from_stream_code: GEN
to_grade_code: STD11
to_stream_code: SCI
to_division_code: A
```

### Std 11 Commerce to Std 12 Commerce

Promote to the same stream in next grade.

```text
from_grade_code: STD11
from_stream_code: COM
to_grade_code: STD12
to_stream_code: COM
```

### Student Repeats Same Class

Use action/result that indicates retained/repeated and place the student in the next-year cohort for the same grade.

```text
from_grade_code: STD08
to_grade_code: STD08
result_status: Retained
```

### Student Changes Division

Update only target division/cohort.

```text
from_division_code: A
to_division_code: B
```

### Student Transfers Out

Do not delete the Moodle user. Mark status and optionally suspend after records are exported.

```text
action: TRANSFER_OUT
result_status: Transferred
```

### Class 12 Pass-Out

Move to alumni cohort or mark as alumni. Do not enrol into next-year subject courses.

```text
action: ALUMNI
result_status: Alumni
```

## New Admission vs Promotion

Use this rule:

| Situation | Create new user? | Action |
|---|---:|---|
| Existing student promoted | No | Add to next-year cohort and update profile fields. |
| Existing student retained | No | Add to next-year same-grade cohort and update profile fields. |
| Existing student changes stream/division | No | Add to target cohort and update profile fields. |
| Student transfers out | No | Mark transferred/suspend after archive. |
| New student joins school | Yes | Add to users CSV and cohort membership. |
| Parent account changes | Usually no | Update existing parent profile/email unless policy requires new account. |

## Rollback and Correction

If promotion was wrong:

1. Do not delete the student user.
2. Add the student to the correct cohort.
3. Remove the incorrect new cohort membership if confirmed.
4. Update profile fields with the correct grade/stream/division.
5. Re-run cohort sync/cron.
6. Purge caches.

Manual DB edits are not recommended for production. Prefer Moodle UI or corrected CSV rerun.

## Cleanup

After successful validation:

```bash
docker compose exec -u root -T moodle rm -rf "$PACK_CONTAINER"

rm -f moodle/admin/cli/cli_prepare_next_academic_year.php
rm -f moodle/admin/cli/cli_promote_academic_year.php
```

Keep the CSV plan files in source control or a secure school operations repository according to your privacy policy. Promotion files contain student identifiers and must be handled as sensitive operational data.
