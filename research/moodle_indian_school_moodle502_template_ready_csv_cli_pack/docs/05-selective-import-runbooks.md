# Chapter 05: Selective Import Runbooks

| Previous | Documentation Home | Next |
|---|---|---|
| [Chapter 04: Full School Import Runbook](04-full-school-import-runbook.md) | [CLI Pack README](../README.md) | [Chapter 06: Course Template and Chapter Gates](06-course-template-and-chapter-gates.md) |


Use selective imports when the school already exists in Moodle and you only need to add or update a limited dataset.

## Create a Selective Pack

```bash
cd /Users/hardik.chauhan/Documents/learning/eLearnMindset

PACK_HOST="$PWD/research/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
RUNNER="$PACK_HOST/run_school_setup_master.sh"
SELECTIVE_PACK="/tmp/ems-selective-pack"

rm -rf "$SELECTIVE_PACK"
"$RUNNER" new-selective-pack "$SELECTIVE_PACK"
```

The selective pack contains ordered CSV filenames and header-only files for optional sections.

## Validate a Selective Pack

```bash
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" validate
```

If template files are header-only, the runner skips template validation intentionally.

## Import Only Students for One Division

Use this when categories, courses, cohorts, groups, and enrolments already exist.

Edit:

```text
20_users_students.csv
22_cohort_members.csv
```

Run:

```bash
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" users-dry-run
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" users-import
```

## Import Students With Parents

Edit:

```text
20_users_students.csv
21_users_parents.csv
22_cohort_members.csv
24_parent_links.csv
```

Run:

```bash
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" users-dry-run
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" users-import
```

## Add a New Division

Use this when a grade exists but a new division must be opened.

Edit:

```text
14_cohorts.csv
15_groups.csv
25_enrolments.csv
```

Run:

```bash
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" structure-dry-run
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" structure-import
```

Then add students with:

```bash
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" users-dry-run
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" users-import
```

## Assign a Teacher to One Course

Edit:

```text
19_users_staff.csv
23_role_assignments.csv
```

Use `context_type=course` and set `context_identifier` to the Moodle course shortname or idnumber.

Run:

```bash
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" users-dry-run
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" users-import
```

## Add Only Cohort Enrolment Mappings

Use this when courses, cohorts, and groups exist but students do not see courses because cohort sync mappings are missing.

Edit:

```text
25_enrolments.csv
```

Run:

```bash
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" enrolments-dry-run
PACK_HOST="$SELECTIVE_PACK" "$RUNNER" enrolments-import
```

## Selective Import Safety

- Keep unrelated CSVs header-only.
- Never remove stable identifiers from existing Moodle records unless you are intentionally changing them.
- Always dry-run first.
- Use production backups before large user or enrolment updates.

---

| Previous | Documentation Home | Next |
|---|---|---|
| [Chapter 04: Full School Import Runbook](04-full-school-import-runbook.md) | [CLI Pack README](../README.md) | [Chapter 06: Course Template and Chapter Gates](06-course-template-and-chapter-gates.md) |
