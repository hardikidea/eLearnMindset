# 10-Chapter Sequential Course Template

This pack includes a reusable Moodle master course template for Indian school grade-subject courses.

The template is designed for this pattern:

```text
Course Home & Orientation
Syllabus & Assessment Plan
Chapter 1
Chapter 2
Chapter 3
Chapter 4
Chapter 5
Chapter 6
Chapter 7
Chapter 8
Chapter 9
Chapter 10
Revision & Final Assessment
```

Each chapter contains:

```text
1. Chapter overview
2. Study material
3. Discussion
4. Practice quiz
5. Assignment
6. Completion gate
```

The completion gate is the controlled unlock point. Chapter 2 unlocks after the Chapter 1 gate is completed, Chapter 3 unlocks after the Chapter 2 gate is completed, and so on. The final revision section unlocks after the Chapter 10 gate.

## Moodle Settings Required

Enable completion tracking and restricted access before using the gate workflow:

```bash
php admin/cli/cfg.php --name=enablecompletion --set=1
php admin/cli/cfg.php --name=enableavailability --set=1
```

In Docker, run the same commands inside the Moodle container:

```bash
docker compose exec -T moodle php admin/cli/cfg.php --name=enablecompletion --set=1
docker compose exec -T moodle php admin/cli/cfg.php --name=enableavailability --set=1
```

## Create the Master Template

Use the pack folder as the CSV source:

```bash
PACK_HOST="$PWD/research/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
PACK_CONTAINER="/tmp/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
APP_CONTAINER="$(docker compose ps -q moodle)"
```

Copy the CLI scripts into Moodle:

```bash
cp "$PACK_HOST"/cli_*.php moodle/admin/cli/
```

Copy the pack into the container:

```bash
docker compose exec -u root -T moodle rm -rf "$PACK_CONTAINER"
docker cp "$PACK_HOST" "$APP_CONTAINER:$PACK_CONTAINER"
```

Validate the template CSV files:

```bash
docker compose exec -T moodle php admin/cli/cli_validate_course_template_csv.php \
  --dir="$PACK_CONTAINER"
```

Dry run:

```bash
docker compose exec -T moodle php admin/cli/cli_create_universal_master_course_template.php \
  --dir="$PACK_CONTAINER" \
  --dry-run=1 \
  --activity-mode=native
```

Execute:

```bash
docker compose exec -T moodle php admin/cli/cli_create_universal_master_course_template.php \
  --dir="$PACK_CONTAINER" \
  --dry-run=0 \
  --activity-mode=native
```

If an older hidden master template already exists and you want to replace its old placeholder activities with the current 10-chapter template, run with the explicit reset flag:

```bash
docker compose exec -T moodle php admin/cli/cli_create_universal_master_course_template.php \
  --dir="$PACK_CONTAINER" \
  --dry-run=0 \
  --activity-mode=native \
  --reset-template-activities=1
```

The reset flag deletes activities only from the hidden master template course before recreating the activities from `course_template_activities.csv`. It does not delete real subject courses or enrolled student work.

Use `--activity-mode=native` when Quiz, Assignment, Forum, Feedback, Book, and Page modules are installed. This creates real Moodle activities and enables pass-grade chapter gates.

Use `--activity-mode=page` for a very safe placeholder-only setup. In page mode, non-page activities are created as Page placeholders, so chapter gates use view completion instead of real quiz pass grades.

## How Unlocking Works

The CLI reads `course_template_activities.csv`.

Rows with `unlock_next=1` become chapter gate activities.

The CLI then writes restricted-access rules to these sections:

| Section | Unlock rule |
|---|---|
| Chapter 1 | Open by default |
| Chapter 2 | Requires Chapter 1 completion gate |
| Chapter 3 | Requires Chapter 2 completion gate |
| Chapter 4 | Requires Chapter 3 completion gate |
| Chapter 5 | Requires Chapter 4 completion gate |
| Chapter 6 | Requires Chapter 5 completion gate |
| Chapter 7 | Requires Chapter 6 completion gate |
| Chapter 8 | Requires Chapter 7 completion gate |
| Chapter 9 | Requires Chapter 8 completion gate |
| Chapter 10 | Requires Chapter 9 completion gate |
| Revision & Final Assessment | Requires Chapter 10 completion gate |

For native Quiz gates, `completion_rule=passgrade` and `grade_to_pass=40` mean the student must pass the gate quiz. Teachers can change the pass mark per course after copying the template.

## Teacher Workflow

For each real course copied from the template:

1. Rename chapter sections to match the textbook or board syllabus.
2. Replace overview placeholders with learning outcomes and keywords.
3. Replace study material placeholders with textbook notes, diagrams, links, or Book resources.
4. Add questions to the practice quiz.
5. Configure the chapter assignment with due dates and rubrics.
6. Add questions to the completion gate quiz.
7. Confirm the gate quiz has a passing grade.
8. Preview the course as a student and confirm Chapter 2 is locked before completing Chapter 1.

## Grade-Level Adjustments

Use `grade_band_template_adjustments.csv` for local policy decisions:

| Grade band | Recommended gate approach |
|---|---|
| KG-G2 | Prefer view/submit completion and teacher sign-off. Avoid high-stakes gates. |
| G3-G5 | Short gates with simple quizzes or teacher checkpoints. |
| G6-G8 | Regular quiz gates and assignment evidence. |
| G9-G10 | Strict pass-grade gates, formal rubrics, and board-style checks. |
| G11-G12 | Rigorous pass-grade gates, mock tests, practicals, projects, and stream-specific assessment. |

## Verification Checklist

After creating the template:

1. Open the hidden template course as admin.
2. Confirm sections 0 to 12 exist.
3. Confirm Chapter 1 has six activities.
4. Confirm Chapter 1 has a completion gate.
5. Confirm Chapter 2 has restricted access based on the Chapter 1 gate.
6. Confirm Revision & Final Assessment is restricted by the Chapter 10 gate.
7. Purge Moodle caches if section availability does not immediately show.

```bash
docker compose exec -T moodle php admin/cli/purge_caches.php
```

## Creating Real Courses From the Template

Use Moodle's course upload with:

```text
courses_with_templatecourse_for_moodle_upload.csv
```

That file uses:

```text
templatecourse=MASTER-ALL-GRADES-ALL-SUBJECTS-STD-TEMPLATE
```

This copies the hidden master course into each real grade-subject course.

## Important Rules

- Keep the master template hidden.
- Do not enrol students in the master template.
- Do not put Aadhaar, parent phone numbers, addresses, medical details, or emergency contact data inside course content.
- Keep personal data in protected Moodle profile fields only.
- Test the template in staging before applying it to production courses.
