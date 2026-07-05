# Chapter 09: Validation and Troubleshooting

| Previous | Documentation Home | Next |
|---|---|---|
| [Chapter 08: Master Excel Workbook](08-master-excel-workbook.md) | [CLI Pack README](../README.md) | [Chapter 10: Reference Index](10-reference-index.md) |

## Standard Validation

```bash
cd /Users/hardik.chauhan/Documents/learning/eLearnMindset

PACK_HOST="$PWD/research/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
RUNNER="$PACK_HOST/run_school_setup_master.sh"

"$RUNNER" validate
```

Expected full-pack result:

```text
Validation completed without blocking errors.
Template CSV validation passed.
```

## PHP and Shell Syntax Checks

```bash
find "$PACK_HOST" -maxdepth 1 -name '*.php' -print0 | xargs -0 -n1 php -l
find "$PACK_HOST" -maxdepth 1 -name 'run_*.sh' -print0 | xargs -0 -n1 bash -n
```

## Selective Pack Validation

Header-only template files are valid for users-only selective packs.

Expected result:

```text
Validation completed without blocking errors.
Skipped template CSV validation because template files are header-only.
```

## Common Problems

| Problem | Likely Cause | Fix |
|---|---|---|
| Student exists but no courses appear | Cohort membership or cohort enrolment mapping is missing. | Check `22_cohort_members.csv` and `25_enrolments.csv`. |
| Cohort member import fails | Cohort code does not exist. | Import or fix `14_cohorts.csv`. |
| Group mapping fails | Group idnumber does not exist in the course. | Import or fix `15_groups.csv`. |
| Teacher role assignment fails | Course shortname/idnumber is wrong. | Fix `23_role_assignments.csv`. |
| Parent link fails | Parent user, student user, or parent role is missing. | Check `21_users_parents.csv`, `20_users_students.csv`, and `17_custom_roles.csv`. |
| CLI says helper file missing | Only one CLI script was copied. | Copy all `cli_*.php` into `moodle/admin/cli/`. |
| Template validation fails on selective pack | Template files have partial data rows. | Leave template files header-only or include the full required template rows. |

## Database Verification Examples

Check users:

```bash
docker compose exec -T db psql -U moodle -d moodle -c \
"select username, firstname, lastname, email from mdl_user order by id desc limit 20;"
```

Check cohort members:

```bash
docker compose exec -T db psql -U moodle -d moodle -c \
"select u.username, c.idnumber as cohort from mdl_cohort_members cm join mdl_user u on u.id=cm.userid join mdl_cohort c on c.id=cm.cohortid order by u.username limit 20;"
```

Check course cohort enrolments:

```bash
docker compose exec -T db psql -U moodle -d moodle -c \
"select e.enrol, e.name, c.shortname from mdl_enrol e join mdl_course c on c.id=e.courseid where e.enrol='cohort' order by c.shortname, e.name limit 20;"
```

## Recovery Rule

For production, restore from a database backup if a real import changes too much data. Do not manually delete Moodle records until you understand dependent enrolments, grade records, submissions, logs, and parent links.

---

| Previous | Documentation Home | Next |
|---|---|---|
| [Chapter 08: Master Excel Workbook](08-master-excel-workbook.md) | [CLI Pack README](../README.md) | [Chapter 10: Reference Index](10-reference-index.md) |
