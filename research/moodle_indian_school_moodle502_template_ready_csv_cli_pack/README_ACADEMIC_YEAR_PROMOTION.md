# Academic Year Promotion / Student Rollover Guide

This guide explains how to move students from one academic year to the next standard in a Moodle-based Indian school setup.

## Recommended Moodle-native model

Use this fixed structure:

```text
Moodle Site
└── Trust / Organisation
    └── Board
        └── School
            └── Medium
                └── Class / Std
                    └── Stream
                        └── Subject Course
                            └── Division Group
```

Use these rules:

| School concept | Moodle feature | Promotion handling |
|---|---|---|
| Academic year | Course/cohort idnumber + custom field | Create new-year courses/cohorts; do not overwrite old year |
| Standard / class | Course category | Student moves to next class category through new course enrolments |
| Stream | Course category under class | Required mainly for Std 11/12; use `GEN` for lower standards |
| Division | Moodle group inside each subject course | Do not create division categories unless syllabus/course is different |
| Student batch | Moodle cohort | One cohort per year + board + school + medium + grade + stream + division |
| Subject | Moodle course | Create fresh course per academic year if records must be retained |

## Best practice

Do not rename old courses from `2026-2027` to `2027-2028`. Create new-year courses and cohorts instead. Old courses contain grades, completions, submissions, attendance/plugin data, and reports.

Recommended cohort code:

```text
[School]-[YearStart]-[Board]-[Medium]-[Grade]-[Stream]-[Division]
```

Examples:

```text
GVS-2026-GSEB-GUJ-STD10-GEN-B
GVS-2027-GSEB-GUJ-STD11-SCI-A
GVS-2027-CBSE-ENG-STD10-GEN-A
```

## Standard promotion flow

1. Finalize old-year marks, attendance, and completion data.
2. Export gradebooks and back up important old-year courses.
3. Create next academic year courses, cohorts, groups, and cohort-sync enrolments.
4. Fill `promotion_actions.csv`.
5. Run promotion dry-run.
6. Execute promotion.
7. Validate student access, courses, groups, and parent links.
8. Hide/archive old-year courses after approval.

## Category or group?

Use categories for stable academic hierarchy:

```text
Board > School > Medium > Class > Stream
```

Use groups for divisions:

```text
Course: Std 11 Science Physics
Groups: Division A, Division B, Division C
```

Do not create categories like `Division A` unless each division has completely different subject courses or syllabus. Groups are easier to maintain and work with cohort sync.

## Student movement scenarios

| Scenario | What to do |
|---|---|
| Std 1 to Std 2 | Add student to next-year Std 2 cohort; update profile fields |
| Std 10 to Std 11 Science | Add selected stream/division in `promotion_actions.csv` |
| Std 11 Science to Std 12 Science | Add to next-year Std 12 Science cohort |
| Student repeats standard | Use `RETAIN`; add to next-year same-grade cohort |
| Student changes division | Move to new division cohort/group; use `CHANGE_DIVISION` |
| Student changes stream | Move to target stream cohort and courses; use `CHANGE_STREAM` |
| Student transfers out | Update status to `Transferred`; do not delete user by default |
| Class 12 pass-out | Use `ALUMNI`; no next-year subject course cohort required |

## Important safety rule

Keep `remove_from_old_cohort=0` during the first promotion run unless old-year reporting is fully archived. Removing a student from an old cohort can remove the student from courses enrolled through cohort sync.

## Files added for rollover

| File | Purpose |
|---|---|
| `promotion_actions.csv` | Main promotion input file |
| `promotion_policy.csv` | Policy decisions for category/cohort/group/year handling |
| `student_status_codes.csv` | Allowed student movement actions/statuses |
| `student_academic_history_template.csv` | External audit/history template |
| `academic_year_rollover_checklist.csv` | Operational checklist |
| `archive_policy.csv` | What to archive and when |
| `cli_promote_academic_year.php` | Moodle CLI script for promotion |
| `run_promotion_dry_run_example.sh` | Example dry-run command |
| `run_promotion_execute_example.sh` | Example execute command |

## CLI usage

Copy the script into Moodle:

```bash
cp cli_promote_academic_year.php /path/to/moodle/admin/cli/
```

Dry run:

```bash
php /path/to/moodle/admin/cli/cli_promote_academic_year.php \
  --dir=/path/to/csv-pack \
  --file=promotion_actions.csv \
  --dry-run=1
```

Execute:

```bash
php /path/to/moodle/admin/cli/cli_promote_academic_year.php \
  --dir=/path/to/csv-pack \
  --file=promotion_actions.csv \
  --dry-run=0
```

Optional old cohort removal after archive:

```bash
php /path/to/moodle/admin/cli/cli_promote_academic_year.php \
  --dir=/path/to/csv-pack \
  --file=promotion_actions.csv \
  --dry-run=0 \
  --remove-old-cohort=1
```

Use old-cohort removal only after backups and old-year reports are complete.
