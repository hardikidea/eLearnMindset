# Chapter 07: Academic Year Rollover

| Previous | Documentation Home | Next |
|---|---|---|
| [Chapter 06: Course Template and Chapter Gates](06-course-template-and-chapter-gates.md) | [CLI Pack README](../README.md) | [Chapter 08: Master Excel Workbook](08-master-excel-workbook.md) |


Use this chapter when moving students to the next academic year without creating duplicate user accounts.

## Principle

Do not re-register existing students for the new academic year. Keep the same Moodle user account and add the student to next-year cohorts.

Use new courses and cohorts when old-year grades, submissions, completion, attendance, and reports must be preserved.

## Recommended Model

```text
Old year:
  Student user -> 2026-2027 Std 4 cohort -> Std 4 courses

New year:
  Same student user -> 2027-2028 Std 5 cohort -> Std 5 courses
```

## Rollover Files

| File | Purpose |
|---|---|
| `44_academic_year_transition_models.csv` | Available transition models. |
| `45_academic_year_promotion_rules.csv` | Promotion rules by grade/stream/result. |
| `46_academic_year_rollover_checklist.csv` | Operational checklist. |
| `47_promotion_policy.csv` | Policy settings. |
| `48_promotion_status_codes.csv` | Status reference. |
| `49_promotion_validation_rules.csv` | Validation rule reference. |
| `50_student_status_codes.csv` | Student lifecycle statuses. |
| `51_student_academic_history_template.csv` | External academic history template. |
| `52_student_promotion_plan_2027_2028.csv` | Planning sheet for next-year movement. |
| `53_promotion_actions.csv` | Executable promotion action input. |
| `54_` to `57_` | Next-year courses, cohorts, groups, and enrolments. |
| `58_alumni_cohorts_2027.csv` | Alumni/exit cohorts. |
| `59_archive_policy.csv` | Archive rules. |

## Rollover Sequence

1. Finalize grades and reports in the old year.
2. Back up the Moodle database and important course exports.
3. Prepare next-year courses, cohorts, groups, and enrolments.
4. Fill `53_promotion_actions.csv`.
5. Run promotion dry-run.
6. Execute promotion.
7. Validate student course access and parent access.
8. Archive or hide old-year courses after approval.

## Promotion Actions

Use `53_promotion_actions.csv` as the executable promotion file. Common actions:

| Action | Use When |
|---|---|
| `PROMOTE` | Student moves to the next grade/year. |
| `RETAIN` | Student repeats the same grade. |
| `CHANGE_DIVISION` | Student stays in grade/year but moves division. |
| `CHANGE_STREAM` | Student moves to a different stream, commonly Std 10 to Std 11. |
| `TRANSFER_OUT` | Student leaves the school. |
| `ALUMNI` | Student passes out after the final grade. |

Keep `remove_from_old_cohort=0` for the first production run unless old-year reports are fully archived. Removing old cohort membership can remove course access that is still needed for reports.

## Dry-Run Promotion

```bash
PACK_HOST="$PWD/research/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
RUNNER="$PACK_HOST/run_school_setup_master.sh"
PACK_CONTAINER="/tmp/moodle_indian_school_moodle502_template_ready_csv_cli_pack"

"$RUNNER" prepare
docker compose exec -T moodle php admin/cli/cli_promote_academic_year.php \
  --dir="$PACK_CONTAINER" \
  --file=53_promotion_actions.csv \
  --dry-run=1
```

## Execute Promotion

```bash
docker compose exec -T moodle php admin/cli/cli_promote_academic_year.php \
  --dir="$PACK_CONTAINER" \
  --file=53_promotion_actions.csv \
  --dry-run=0
```

## Verification

After promotion, verify:

- Student profile fields show the new academic year, grade, stream, and division.
- Student belongs to the target cohort.
- Target cohort is enrolled into next-year courses.
- Parent links still exist.
- Old-year reports are still accessible to administrators.

---

| Previous | Documentation Home | Next |
|---|---|---|
| [Chapter 06: Course Template and Chapter Gates](06-course-template-and-chapter-gates.md) | [CLI Pack README](../README.md) | [Chapter 08: Master Excel Workbook](08-master-excel-workbook.md) |
