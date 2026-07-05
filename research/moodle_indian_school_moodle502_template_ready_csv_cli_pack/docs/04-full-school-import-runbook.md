# Chapter 04: Full School Import Runbook

Use this chapter when setting up a complete school from the full CSV pack.

## Step 1: Start Moodle

```bash
cd /Users/hardik.chauhan/Documents/learning/eLearnMindset
docker compose up -d
docker compose ps
```

## Step 2: Define Variables

```bash
PACK_HOST="$PWD/research/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
RUNNER="$PACK_HOST/run_school_setup_master.sh"
```

## Step 3: Validate CSVs

```bash
"$RUNNER" validate
```

Expected result:

```text
Validation completed without blocking errors.
Template CSV validation passed.
```

## Step 4: Check Moodle Runtime

```bash
"$RUNNER" preflight
```

This copies `cli_*.php` to `moodle/admin/cli/`, copies the CSV pack into the Moodle container, and checks Moodle compatibility.

## Step 5: Dry-Run Baseline Import

```bash
"$RUNNER" baseline-dry-run
```

Review output before continuing. Fix missing categories, cohorts, users, role shortnames, or duplicate identifiers before running the real import.

## Step 6: Execute Baseline Import

```bash
"$RUNNER" baseline-import
```

This creates or updates:

- Custom user profile fields.
- Custom roles.
- Categories.
- Courses.
- Cohorts.
- Groups.
- Staff, student, and parent users.
- Cohort memberships.
- Staff role assignments.
- Parent-child links.
- Cohort enrolment mappings.

## Step 7: Create Standard Course Template

```bash
"$RUNNER" template-reset-import
```

This creates or resets the hidden master course template from:

```text
30_master_course_template.csv
31_course_template_sections.csv
32_course_template_activities.csv
```

## Step 8: Apply Template and Gradebook

```bash
"$RUNNER" apply-template-import
"$RUNNER" gradebook-import
"$RUNNER" purge-cache
```

## One-Command Full Setup

Use only after CSV review:

```bash
"$RUNNER" full-baseline
```

This runs validation, preflight, baseline dry-run, baseline import, template reset import, template application, gradebook import, and cache purge.

## Post-Import Checks

Check users:

```bash
docker compose exec -T db psql -U moodle -d moodle -c \
"select username, firstname, lastname, email from mdl_user order by id desc limit 20;"
```

Check cohort enrolments:

```bash
docker compose exec -T db psql -U moodle -d moodle -c \
"select e.enrol, e.name, c.shortname from mdl_enrol e join mdl_course c on c.id=e.courseid where e.enrol='cohort' order by c.shortname, e.name limit 20;"
```

