# Course Template Runbook

Grade-wise templates are defined under `templates/courses/`.

The Moodle legacy importer currently applies the universal legacy template from `templates/legacy/`, which contains:

- Course Home
- Syllabus
- Chapter 1 to Chapter 10
- Term 1 Exam
- Term 2 Exam
- Final Examination
- Certificate & Completion, including `course_completion_certificate`

Each course receives a template assignment in `years/<year>/course_template_application.csv`.

The normal import script creates course sections and then applies the verified PDF certificate through `cli_apply_course_certificates.php`. The separate universal-template script may still create placeholder activities for demos, but certificates are managed by the certificate import step so every real course receives a proper `customcert` activity.
