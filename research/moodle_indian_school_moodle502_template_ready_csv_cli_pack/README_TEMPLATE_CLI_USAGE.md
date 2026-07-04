# Course template CLI usage

Copy the CLI scripts into your Moodle code root under `admin/cli/`.

## 1. Create the hidden master template course

Dry run:

```bash
php /path/to/moodle/admin/cli/cli_create_universal_master_course_template.php \
  --dir=/path/to/extracted/csv-pack \
  --dry-run=1
```

Execute:

```bash
php /path/to/moodle/admin/cli/cli_create_universal_master_course_template.php \
  --dir=/path/to/extracted/csv-pack \
  --dry-run=0 \
  --activity-mode=page
```

`--activity-mode=page` is the safest default. It creates Moodle Page placeholders using the recommended activity type in the title. Teachers can replace the placeholders with Quiz, Assignment, Forum, H5P, SCORM, URL, Book, or other activities later.

Use `--activity-mode=native` only on a staging site after checking installed activity modules.

## 2. Apply template settings to already-created courses

Dry run first:

```bash
php /path/to/moodle/admin/cli/cli_apply_course_template_settings.php \
  --dir=/path/to/extracted/csv-pack \
  --dry-run=1 \
  --limit=10
```

Execute for all courses:

```bash
php /path/to/moodle/admin/cli/cli_apply_course_template_settings.php \
  --dir=/path/to/extracted/csv-pack \
  --dry-run=0 \
  --limit=0
```

This applies course settings and section names/summaries. It does not duplicate activity content into every existing course by default, because creating thousands of placeholder activities can be heavy.

## 3. Create new courses from the template using Moodle native upload-course tool

Use:

```text
courses_with_templatecourse_for_moodle_upload.csv
```

This file includes `templatecourse=MASTER-ALL-GRADES-ALL-SUBJECTS-STD-TEMPLATE`. Moodle course upload supports the `templatecourse` field when using an existing course as a template. Categories must exist before using this file.

## 4. Gradebook template

Use:

```text
course_template_gradebook.csv
grade_band_template_adjustments.csv
```

The CLI pack includes `cli_apply_gradebook_template.php` as a helper for creating gradebook categories, but gradebook weighting should be reviewed in staging before production.
