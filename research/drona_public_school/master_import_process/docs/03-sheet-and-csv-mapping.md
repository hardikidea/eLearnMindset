# Sheet and CSV Mapping

Each workbook sheet maps to a source CSV file and then to an ordered assembled CSV file.

## Core Mapping

| Sheet | Source CSV | Ordered CSV |
|---|---|---|
| `01_school_master` | `master/school.csv` | `01_school_master.csv` |
| `02_academic_years` | `master/academic_years.csv` | `02_academic_years.csv` |
| `03_boards` | `master/boards.csv` | `03_boards.csv` |
| `04_mediums` | `master/mediums.csv` | `04_mediums.csv` |
| `05_grades` | `master/grades.csv` | `05_grades.csv` |
| `06_streams` | `master/streams.csv` | `06_streams.csv` |
| `07_divisions` | `master/divisions.csv` | `07_divisions.csv` |
| `08_subjects` | `master/subjects.csv` | `08_subjects.csv` |
| `09_subject_matrix` | `years/<year>/grade_subject_matrix.csv` | `09_grade_subject_matrix.csv` |
| `10_categories` | `years/<year>/categories.csv` | `10_categories.csv` |
| `12_courses` | `years/<year>/courses.csv` | `12_courses.csv` |
| `14_cohorts` | `years/<year>/cohorts.csv` | `14_cohorts.csv` |
| `15_groups` | `years/<year>/groups.csv` | `15_groups.csv` |
| `19_users_staff` | `registration/combined/19_users_staff.csv` | `19_users_staff.csv` |
| `20_users_students` | `registration/combined/20_users_students.csv` | `20_users_students.csv` |
| `21_users_parents` | `registration/combined/21_users_parents.csv` | `21_users_parents.csv` |
| `22_cohort_members` | `years/<year>/cohort_members.csv` | `22_cohort_members.csv` |
| `23_role_assignments` | `years/<year>/role_assignments.csv` | `23_role_assignments.csv` |
| `24_parent_links` | `registration/parent_links.csv` | `24_parent_links.csv` |
| `25_enrolments` | `years/<year>/enrolments.csv` | `25_enrolments.csv` |

## Template and Assessment Mapping

| Sheet | Source CSV | Ordered CSV |
|---|---|---|
| `30_master_template` | `templates/legacy/30_master_course_template.csv` | `30_master_course_template.csv` |
| `31_template_sections` | `templates/legacy/31_course_template_sections.csv` | `31_course_template_sections.csv` |
| `32_template_activities` | `templates/legacy/32_course_template_activities.csv` | `32_course_template_activities.csv` |
| `37_template_application` | `years/<year>/course_template_application.csv` | `37_course_template_application.csv` |
| `course_term_exams` | `years/<year>/course_term_exams.csv` | `course_term_exams.csv` |
| `course_final_exams` | `years/<year>/course_final_exams.csv` | `course_final_exams.csv` |
| `course_certificates` | `years/<year>/course_certificates.csv` | `course_certificates.csv` |
| `gradebook_weights` | `years/<year>/gradebook_weights.csv` | `gradebook_weights.csv` |
| `assessment_plan` | `years/<year>/assessment_plan.csv` | `assessment_plan.csv` |

The `_manifest` sheet in the workbook contains the full mapping.
