# Ordered CSV File Index

The CSV files in this pack are intentionally named with an execution prefix:

```text
NN_<logical_file_name>.csv
```

Use the ordered filename when editing, reviewing, or creating selective packs. The Moodle CLI scripts still resolve logical names internally, so both `20_users_students.csv` and `users_students.csv` can be read, but the ordered names are the repository standard.

## Full Import Sequence

| Order | Phase | CSV file | Purpose |
|---:|---|---|---|
| 01 | Core master data | `01_school_master.csv` | School/trust identity and current academic year. |
| 02 | Core master data | `02_academic_years.csv` | Academic-year calendar. |
| 03 | Core master data | `03_boards.csv` | Education boards. |
| 04 | Core master data | `04_mediums.csv` | Teaching mediums/languages. |
| 05 | Core master data | `05_grades.csv` | Grade/class master list. |
| 06 | Core master data | `06_streams.csv` | Streams such as General, Science, Commerce. |
| 07 | Core master data | `07_divisions.csv` | Class divisions used for Moodle groups. |
| 08 | Core master data | `08_subjects.csv` | Subject master list. |
| 09 | Core master data | `09_grade_subject_matrix.csv` | Allowed board/medium/grade/stream/subject combinations. |
| 10 | Moodle structure | `10_categories.csv` | Moodle course-category hierarchy. |
| 11 | Moodle structure | `11_optional_year_category_model_categories.csv` | Optional year-based category model. |
| 12 | Moodle structure | `12_courses.csv` | Generated Moodle courses. |
| 13 | Moodle structure | `13_courses_with_templatecourse_for_moodle_upload.csv` | Moodle native course-upload file using `templatecourse`. |
| 14 | Moodle structure | `14_cohorts.csv` | Student enrolment cohorts. |
| 15 | Moodle structure | `15_groups.csv` | Course-level division groups. |
| 16 | Users and roles | `16_user_profile_fields.csv` | Custom Moodle user-profile fields. |
| 17 | Users and roles | `17_custom_roles.csv` | Custom Moodle roles. |
| 18 | Users and roles | `18_role_guidelines.csv` | Role-assignment guidance. |
| 19 | Users and roles | `19_users_staff.csv` | Staff users. |
| 20 | Users and roles | `20_users_students.csv` | Student users and school profile fields. |
| 21 | Users and roles | `21_users_parents.csv` | Parent users. |
| 22 | Users and roles | `22_cohort_members.csv` | Student-to-cohort membership. |
| 23 | Users and roles | `23_role_assignments.csv` | Staff/admin role assignments. |
| 24 | Users and roles | `24_parent_links.csv` | Parent-child relationships. |
| 25 | Users and roles | `25_enrolments.csv` | Cohort sync enrolment mappings. |
| 26 | Support | `26_lookup_values.csv` | General lookup/reference values. |
| 27 | Support | `27_validation_rules.csv` | CSV validation rule catalog. |
| 28 | Support | `28_source_references.csv` | Source URL/reference catalog. |
| 29 | Support | `29_summary.csv` | Pack summary metrics. |
| 30 | Course template | `30_master_course_template.csv` | Hidden master course template definition. |
| 31 | Course template | `31_course_template_sections.csv` | Template course sections. |
| 32 | Course template | `32_course_template_activities.csv` | Template activities and chapter gates. |
| 33 | Course template | `33_course_template_gradebook.csv` | Template gradebook categories. |
| 34 | Course template | `34_grade_band_template_adjustments.csv` | Grade-band weight and pedagogy adjustments. |
| 35 | Course template | `35_subject_template_adjustments.csv` | Subject-specific template suggestions. |
| 36 | Course template | `36_completion_tracking_defaults.csv` | Recommended completion settings by activity type. |
| 37 | Course template | `37_course_template_application.csv` | Map real courses to the master template. |
| 38 | Course template | `38_course_template_custom_fields.csv` | Template custom field guidance. |
| 39 | Course template | `39_course_template_review_checklist.csv` | Template QA checklist. |
| 40 | Course template | `40_certificate_badge_policy.csv` | Badge/certificate policy. |
| 41 | Course template | `41_template_report_access_matrix.csv` | Report access by role. |
| 42 | Course template | `42_behat_course_template_coverage_mapping.csv` | Template test coverage mapping. |
| 43 | Course template | `43_diksha_content_template.csv` | DIKSHA/NCERT/state-board content mapping. |
| 44 | Academic year rollover | `44_academic_year_transition_models.csv` | Academic-year transition models. |
| 45 | Academic year rollover | `45_academic_year_promotion_rules.csv` | Promotion rules by grade/stream/result. |
| 46 | Academic year rollover | `46_academic_year_rollover_checklist.csv` | Rollover checklist. |
| 47 | Academic year rollover | `47_promotion_policy.csv` | Promotion policy reference. |
| 48 | Academic year rollover | `48_promotion_status_codes.csv` | Promotion status codes. |
| 49 | Academic year rollover | `49_promotion_validation_rules.csv` | Promotion validation rules. |
| 50 | Academic year rollover | `50_student_status_codes.csv` | Student status codes. |
| 51 | Academic year rollover | `51_student_academic_history_template.csv` | Student academic history records. |
| 52 | Academic year rollover | `52_student_promotion_plan_2027_2028.csv` | Next-year student promotion plan. |
| 53 | Academic year rollover | `53_promotion_actions.csv` | Executable promotion action rows. |
| 54 | Academic year rollover | `54_next_year_courses_2027_2028.csv` | Prepared next-year courses. |
| 55 | Academic year rollover | `55_next_year_cohorts_2027_2028.csv` | Prepared next-year cohorts. |
| 56 | Academic year rollover | `56_next_year_groups_2027_2028.csv` | Prepared next-year groups. |
| 57 | Academic year rollover | `57_next_year_enrolments_2027_2028.csv` | Prepared next-year enrolments. |
| 58 | Academic year rollover | `58_alumni_cohorts_2027.csv` | Alumni/exit cohorts. |
| 59 | Academic year rollover | `59_archive_policy.csv` | Archive policy. |
| 60 | Support | `60_improvement_backlog.csv` | Future improvements. |
| 61 | Support | `61_compatibility_matrix.csv` | Moodle/version compatibility reference. |

## Selective Pack Rule

For a selective import, keep only the ordered files needed for the task and leave unrelated files as header-only CSVs.

Examples:

| Task | Minimum ordered files to edit |
|---|---|
| Add students to an existing division | `20_users_students.csv`, `22_cohort_members.csv` |
| Add students with parents | `20_users_students.csv`, `21_users_parents.csv`, `22_cohort_members.csv`, `24_parent_links.csv` |
| Add one teacher to one course | `19_users_staff.csv`, `23_role_assignments.csv` |
| Add a new division | `14_cohorts.csv`, `15_groups.csv`, `25_enrolments.csv` |
| Promote students to next academic year | `52_student_promotion_plan_2027_2028.csv`, `53_promotion_actions.csv`, and the target next-year files `54_` to `57_` as needed |

