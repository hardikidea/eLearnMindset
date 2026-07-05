#!/usr/bin/env python3
from pathlib import Path
import argparse, csv, json, shutil

PACK = Path(__file__).resolve().parents[1]
HEADERS = json.loads((PACK / 'config' / 'ordered_csv_headers.json').read_text())
YEARS = [p.name for p in sorted((PACK / 'years').iterdir()) if p.is_dir()]

def read_csv(path):
    if not path.exists():
        return []
    with path.open(newline='') as f:
        return list(csv.DictReader(f))

def write_csv(path, fieldnames, rows):
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open('w', newline='') as f:
        w = csv.DictWriter(f, fieldnames=fieldnames, extrasaction='ignore')
        w.writeheader()
        for row in rows:
            w.writerow({h: row.get(h, '') for h in fieldnames})

def copy_to(out, ordered, rows):
    write_csv(out / ordered, HEADERS[ordered], rows)

def next_year(year):
    try:
        return YEARS[YEARS.index(year) + 1]
    except Exception:
        return None

def assemble_year(year):
    ydir = PACK / 'years' / year
    out = PACK / 'build' / 'assembled_csv' / year
    if out.exists():
        shutil.rmtree(out)
    out.mkdir(parents=True)
    mapping = {
        '01_school_master.csv': PACK / 'master' / 'school.csv',
        '02_academic_years.csv': PACK / 'master' / 'academic_years.csv',
        '03_boards.csv': PACK / 'master' / 'boards.csv',
        '04_mediums.csv': PACK / 'master' / 'mediums.csv',
        '05_grades.csv': PACK / 'master' / 'grades.csv',
        '06_streams.csv': PACK / 'master' / 'streams.csv',
        '07_divisions.csv': PACK / 'master' / 'divisions.csv',
        '08_subjects.csv': PACK / 'master' / 'subjects.csv',
        '09_grade_subject_matrix.csv': ydir / 'grade_subject_matrix.csv',
        '10_categories.csv': ydir / 'categories.csv',
        '11_optional_year_category_model_categories.csv': ydir / 'categories.csv',
        '12_courses.csv': ydir / 'courses.csv',
        '13_courses_with_templatecourse_for_moodle_upload.csv': ydir / 'courses_with_templatecourse_for_moodle_upload.csv',
        '14_cohorts.csv': ydir / 'cohorts.csv',
        '15_groups.csv': ydir / 'groups.csv',
        '16_user_profile_fields.csv': PACK / 'master' / 'profile_fields.csv',
        '17_custom_roles.csv': PACK / 'master' / 'roles.csv',
        '18_role_guidelines.csv': PACK / 'operations' / 'role_guidelines.csv',
        '19_users_staff.csv': PACK / 'registration' / 'combined' / '19_users_staff.csv',
        '20_users_students.csv': PACK / 'registration' / 'combined' / '20_users_students.csv',
        '21_users_parents.csv': PACK / 'registration' / 'combined' / '21_users_parents.csv',
        '22_cohort_members.csv': ydir / 'cohort_members.csv',
        '23_role_assignments.csv': ydir / 'role_assignments.csv',
        '24_parent_links.csv': PACK / 'registration' / 'parent_links.csv',
        '25_enrolments.csv': ydir / 'enrolments.csv',
        '26_lookup_values.csv': PACK / 'master' / 'lookup_values.csv',
        '27_validation_rules.csv': PACK / 'operations' / 'validation_rules.csv',
        '28_source_references.csv': PACK / 'operations' / 'source_references.csv',
        '29_summary.csv': PACK / 'operations' / 'summary.csv',
        '30_master_course_template.csv': PACK / 'templates' / 'legacy' / '30_master_course_template.csv',
        '31_course_template_sections.csv': PACK / 'templates' / 'legacy' / '31_course_template_sections.csv',
        '32_course_template_activities.csv': PACK / 'templates' / 'legacy' / '32_course_template_activities.csv',
        '33_course_template_gradebook.csv': PACK / 'templates' / 'legacy' / '33_course_template_gradebook.csv',
        '34_grade_band_template_adjustments.csv': PACK / 'templates' / 'legacy' / '34_grade_band_template_adjustments.csv',
        '35_subject_template_adjustments.csv': PACK / 'templates' / 'legacy' / '35_subject_template_adjustments.csv',
        '36_completion_tracking_defaults.csv': PACK / 'templates' / 'legacy' / '36_completion_tracking_defaults.csv',
        '37_course_template_application.csv': ydir / 'course_template_application.csv',
        '38_course_template_custom_fields.csv': PACK / 'templates' / 'legacy' / '38_course_template_custom_fields.csv',
        '39_course_template_review_checklist.csv': PACK / 'templates' / 'legacy' / '39_course_template_review_checklist.csv',
        '40_certificate_badge_policy.csv': PACK / 'templates' / 'legacy' / '40_certificate_badge_policy.csv',
        '41_template_report_access_matrix.csv': PACK / 'templates' / 'legacy' / '41_template_report_access_matrix.csv',
        '42_behat_course_template_coverage_mapping.csv': PACK / 'templates' / 'legacy' / '42_behat_course_template_coverage_mapping.csv',
        '43_diksha_content_template.csv': ydir / 'diksha_content_template.csv',
        '44_academic_year_transition_models.csv': PACK / 'operations' / 'academic_year_transition_models.csv',
        '45_academic_year_promotion_rules.csv': PACK / 'operations' / 'academic_year_promotion_rules.csv',
        '46_academic_year_rollover_checklist.csv': PACK / 'operations' / 'academic_year_rollover_checklist.csv',
        '47_promotion_policy.csv': PACK / 'operations' / 'promotion_policy.csv',
        '48_promotion_status_codes.csv': PACK / 'operations' / 'promotion_status_codes.csv',
        '49_promotion_validation_rules.csv': PACK / 'operations' / 'promotion_validation_rules.csv',
        '50_student_status_codes.csv': PACK / 'operations' / 'student_status_codes.csv',
        '51_student_academic_history_template.csv': ydir / 'academic_history.csv',
        '53_promotion_actions.csv': ydir / 'promotion_actions.csv',
        '58_alumni_cohorts_2027.csv': PACK / 'operations' / 'alumni_cohorts_2027.csv',
        '59_archive_policy.csv': PACK / 'operations' / 'archive_policy.csv',
        '60_improvement_backlog.csv': PACK / 'operations' / 'improvement_backlog.csv',
        '61_compatibility_matrix.csv': PACK / 'operations' / 'compatibility_matrix.csv',
    }
    # Promotion plan has dynamic source name.
    ny = next_year(year)
    if ny:
        mapping['52_student_promotion_plan_2027_2028.csv'] = ydir / f'promotion_plan_to_{ny}.csv'
        mapping['54_next_year_courses_2027_2028.csv'] = PACK / 'years' / ny / 'courses.csv'
        mapping['55_next_year_cohorts_2027_2028.csv'] = PACK / 'years' / ny / 'cohorts.csv'
        mapping['56_next_year_groups_2027_2028.csv'] = PACK / 'years' / ny / 'groups.csv'
        mapping['57_next_year_enrolments_2027_2028.csv'] = PACK / 'years' / ny / 'enrolments.csv'
    else:
        mapping['52_student_promotion_plan_2027_2028.csv'] = ydir / 'promotion_plan_to_next_year.csv'
    for ordered in sorted(HEADERS):
        rows = read_csv(mapping[ordered]) if ordered in mapping else []
        copy_to(out, ordered, rows)
    # Extra structured files not consumed by Moodle core importer yet.
    for extra in ['course_certificates.csv','course_term_exams.csv','course_final_exams.csv','assessment_plan.csv','exam_terms.csv','gradebook_weights.csv','attendance_policy.csv']:
        src = ydir / extra
        if src.exists():
            shutil.copy2(src, out / extra)
    return out

def main():
    parser = argparse.ArgumentParser(description='Assemble structured Drona Public School CSV sources into Moodle ordered CSV directories.')
    parser.add_argument('--year', default='all', help='Academic year to assemble, or all')
    args = parser.parse_args()
    years = YEARS if args.year == 'all' else [args.year]
    for year in years:
        if year not in YEARS:
            raise SystemExit(f'Unknown year: {year}')
        out = assemble_year(year)
        print(f'Assembled {year}: {out}')
if __name__ == '__main__':
    main()
