# Universal Grade-Subject Master Course Template

This upgrade adds a reusable hidden master course template for all Indian school grade-subject courses.

## Default model

```text
MASTER - All Grades - All Subjects - Standard Course Template
```

Use one master structure for every grade and subject. Teachers then replace placeholders with grade-level and subject-specific lessons, resources, activities, and assessments.

## Course name format

```text
Grade [Grade Number] - [Subject Name] - [Term/Academic Year]
```

Examples:

```text
Grade 1 - Mathematics - Term 1 2026
Grade 6 - Science - Academic Year 2026
Grade 10 - English - Semester 2 2026
Grade 12 - Biology - Board Exam Prep 2026
```

## Template files

```text
master_course_template.csv
course_template_sections.csv
course_template_activities.csv
course_template_gradebook.csv
grade_band_template_adjustments.csv
subject_template_adjustments.csv
completion_tracking_defaults.csv
course_template_application.csv
courses_with_templatecourse_for_moodle_upload.csv
certificate_badge_policy.csv
template_report_access_matrix.csv
behat_course_template_coverage_mapping.csv
```

## Why this model is Moodle-friendly

Moodle supports creating courses by CSV and reusing an existing course as a template with the `templatecourse` field. The master template should be hidden, reviewed, and then copied/imported into real subject courses. Moodle activity completion and course completion can then drive progress tracking, To do tasks, reports, and badges/certificates.

## PII rule

Do not put student Aadhaar, address, medical details, parent phone numbers, or emergency contact data inside a course page, assignment instruction, quiz question, forum post, feedback form, badge, certificate, or report visible to other users. Keep personal data in protected user profile fields only.

## Certificate rule

Default to Moodle core badges for completion credentials. Use the Custom certificate plugin only if it is installed, approved, and configured on your Moodle site.
