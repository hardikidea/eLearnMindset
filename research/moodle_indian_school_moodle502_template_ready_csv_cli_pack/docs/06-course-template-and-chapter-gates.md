# Chapter 06: Course Template and Chapter Gates

The universal course template creates a consistent course structure for all grades and subjects.

## Standard Pattern

Each course can follow a chapter-wise pattern:

```text
Course overview
Announcements
Chapter 1
  Overview
  Reading/resource
  Assignment
  Quiz
  Chapter completion gate
Chapter 2
  Locked until Chapter 1 gate is complete
...
Chapter 10
```

## Template CSV Files

| File | Purpose |
|---|---|
| `30_master_course_template.csv` | Defines the hidden master template course. |
| `31_course_template_sections.csv` | Defines section/chapter names and summaries. |
| `32_course_template_activities.csv` | Defines placeholder activities, completion rules, and unlock gates. |
| `33_course_template_gradebook.csv` | Defines gradebook categories and weights. |
| `34_grade_band_template_adjustments.csv` | Adjusts weights/pedagogy by grade band. |
| `35_subject_template_adjustments.csv` | Adds subject-specific guidance. |
| `36_completion_tracking_defaults.csv` | Documents recommended completion defaults. |
| `37_course_template_application.csv` | Maps real courses to the template. |

## Validate Template CSVs

```bash
PACK_HOST="$PWD/research/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
php "$PACK_HOST/cli_validate_course_template_csv.php" --dir="$PACK_HOST"
```

Preferred wrapper:

```bash
"$PACK_HOST/run_school_setup_master.sh" validate
```

## Create or Reset Template

```bash
"$RUNNER" template-dry-run
"$RUNNER" template-reset-import
```

Use `template-reset-import` when rebuilding the hidden template activities from CSV.

By default the runner creates native Moodle activities where possible. That gives quizzes and assignments real completion behavior. If a Moodle site is missing a module, use the generated placeholders as teacher-editable pages and replace them later.

## Apply Template to Existing Courses

```bash
"$RUNNER" apply-template-dry-run
"$RUNNER" apply-template-import
```

This updates section settings and template-guided course settings for courses listed in `37_course_template_application.csv`.

## Apply Gradebook

```bash
"$RUNNER" gradebook-dry-run
"$RUNNER" gradebook-import
```

## Chapter Gate Rule

Sequential chapter access depends on:

- Moodle completion tracking enabled.
- Moodle restricted access enabled.
- A completion gate activity in each chapter.
- Access restriction on the next chapter based on the previous chapter gate.

Default behavior:

| Section | Visibility Rule |
|---|---|
| Course overview | Open. |
| Chapter 1 | Open. |
| Chapter 2 | Requires Chapter 1 completion gate. |
| Chapter 3 | Requires Chapter 2 completion gate. |
| Chapter 4 to 10 | Each chapter requires the previous chapter gate. |
| Revision and final assessment | Requires Chapter 10 completion gate. |

Teachers should replace placeholder activities with real learning content after the template is applied. Do not place Aadhaar, address, medical, parent phone, or emergency-contact data inside course activities.
