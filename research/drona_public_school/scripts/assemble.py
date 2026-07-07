#!/usr/bin/env python3
from pathlib import Path
import argparse, csv, json, shutil

PACK = Path(__file__).resolve().parents[1]
HEADERS = json.loads((PACK / 'config' / 'ordered_csv_headers.json').read_text())

def available_years(source_root):
    years_dir = source_root / 'years'
    if not years_dir.exists():
        return []
    return [p.name for p in sorted(years_dir.iterdir()) if p.is_dir()]

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

def next_year(year, years):
    try:
        return years[years.index(year) + 1]
    except Exception:
        return None

def assemble_year(year, source_root):
    ydir = source_root / 'years' / year
    out = PACK / 'build' / 'assembled_csv' / year
    if out.exists():
        shutil.rmtree(out)
    out.mkdir(parents=True)
    mapping = {
        '01_school_master.csv': source_root / 'master' / 'school.csv',
        '02_academic_years.csv': source_root / 'master' / 'academic_years.csv',
        '03_boards.csv': source_root / 'master' / 'boards.csv',
        '04_mediums.csv': source_root / 'master' / 'mediums.csv',
        '05_grades.csv': source_root / 'master' / 'grades.csv',
        '06_streams.csv': source_root / 'master' / 'streams.csv',
        '07_divisions.csv': source_root / 'master' / 'divisions.csv',
        '08_subjects.csv': source_root / 'master' / 'subjects.csv',
        '09_grade_subject_matrix.csv': ydir / 'grade_subject_matrix.csv',
        '10_categories.csv': ydir / 'categories.csv',
        '11_optional_year_category_model_categories.csv': ydir / 'categories.csv',
        '12_courses.csv': ydir / 'courses.csv',
        '13_courses_with_templatecourse_for_moodle_upload.csv': ydir / 'courses_with_templatecourse_for_moodle_upload.csv',
        '14_cohorts.csv': ydir / 'cohorts.csv',
        '15_groups.csv': ydir / 'groups.csv',
        '16_user_profile_fields.csv': source_root / 'master' / 'profile_fields.csv',
        '17_custom_roles.csv': source_root / 'master' / 'roles.csv',
        '18_role_guidelines.csv': source_root / 'operations' / 'role_guidelines.csv',
        '19_users_staff.csv': source_root / 'registration' / 'combined' / '19_users_staff.csv',
        '20_users_students.csv': source_root / 'registration' / 'combined' / '20_users_students.csv',
        '21_users_parents.csv': source_root / 'registration' / 'combined' / '21_users_parents.csv',
        '22_cohort_members.csv': ydir / 'cohort_members.csv',
        '23_role_assignments.csv': ydir / 'role_assignments.csv',
        '24_parent_links.csv': source_root / 'registration' / 'parent_links.csv',
        '25_enrolments.csv': ydir / 'enrolments.csv',
        '26_lookup_values.csv': source_root / 'master' / 'lookup_values.csv',
        '27_validation_rules.csv': source_root / 'operations' / 'validation_rules.csv',
        '28_source_references.csv': source_root / 'operations' / 'source_references.csv',
        '29_summary.csv': source_root / 'operations' / 'summary.csv',
        '30_master_course_template.csv': source_root / 'templates' / 'legacy' / '30_master_course_template.csv',
        '31_course_template_sections.csv': source_root / 'templates' / 'legacy' / '31_course_template_sections.csv',
        '32_course_template_activities.csv': source_root / 'templates' / 'legacy' / '32_course_template_activities.csv',
        '33_course_template_gradebook.csv': source_root / 'templates' / 'legacy' / '33_course_template_gradebook.csv',
        '34_grade_band_template_adjustments.csv': source_root / 'templates' / 'legacy' / '34_grade_band_template_adjustments.csv',
        '35_subject_template_adjustments.csv': source_root / 'templates' / 'legacy' / '35_subject_template_adjustments.csv',
        '36_completion_tracking_defaults.csv': source_root / 'templates' / 'legacy' / '36_completion_tracking_defaults.csv',
        '37_course_template_application.csv': ydir / 'course_template_application.csv',
        '38_course_template_custom_fields.csv': source_root / 'templates' / 'legacy' / '38_course_template_custom_fields.csv',
        '39_course_template_review_checklist.csv': source_root / 'templates' / 'legacy' / '39_course_template_review_checklist.csv',
        '40_certificate_badge_policy.csv': source_root / 'templates' / 'legacy' / '40_certificate_badge_policy.csv',
        '41_template_report_access_matrix.csv': source_root / 'templates' / 'legacy' / '41_template_report_access_matrix.csv',
        '42_behat_course_template_coverage_mapping.csv': source_root / 'templates' / 'legacy' / '42_behat_course_template_coverage_mapping.csv',
        '43_diksha_content_template.csv': ydir / 'diksha_content_template.csv',
        '44_academic_year_transition_models.csv': source_root / 'operations' / 'academic_year_transition_models.csv',
        '45_academic_year_promotion_rules.csv': source_root / 'operations' / 'academic_year_promotion_rules.csv',
        '46_academic_year_rollover_checklist.csv': source_root / 'operations' / 'academic_year_rollover_checklist.csv',
        '47_promotion_policy.csv': source_root / 'operations' / 'promotion_policy.csv',
        '48_promotion_status_codes.csv': source_root / 'operations' / 'promotion_status_codes.csv',
        '49_promotion_validation_rules.csv': source_root / 'operations' / 'promotion_validation_rules.csv',
        '50_student_status_codes.csv': source_root / 'operations' / 'student_status_codes.csv',
        '51_student_academic_history_template.csv': ydir / 'academic_history.csv',
        '53_promotion_actions.csv': ydir / 'promotion_actions.csv',
        '58_alumni_cohorts_2027.csv': source_root / 'operations' / 'alumni_cohorts_2027.csv',
        '59_archive_policy.csv': source_root / 'operations' / 'archive_policy.csv',
        '60_improvement_backlog.csv': source_root / 'operations' / 'improvement_backlog.csv',
        '61_compatibility_matrix.csv': source_root / 'operations' / 'compatibility_matrix.csv',
    }
    # Promotion plan has dynamic source name.
    years = available_years(source_root)
    ny = next_year(year, years)
    if ny:
        mapping['52_student_promotion_plan_2027_2028.csv'] = ydir / f'promotion_plan_to_{ny}.csv'
        mapping['54_next_year_courses_2027_2028.csv'] = source_root / 'years' / ny / 'courses.csv'
        mapping['55_next_year_cohorts_2027_2028.csv'] = source_root / 'years' / ny / 'cohorts.csv'
        mapping['56_next_year_groups_2027_2028.csv'] = source_root / 'years' / ny / 'groups.csv'
        mapping['57_next_year_enrolments_2027_2028.csv'] = source_root / 'years' / ny / 'enrolments.csv'
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
    parser.add_argument('--source-root', default=str(PACK), help='Source CSV root. Defaults to this pack root.')
    args = parser.parse_args()
    source_root = Path(args.source_root).resolve()
    years_available = available_years(source_root)
    years = years_available if args.year == 'all' else [args.year]
    for year in years:
        if year not in years_available:
            raise SystemExit(f'Unknown year: {year}')
        out = assemble_year(year, source_root)
        print(f'Assembled {year}: {out}')
if __name__ == '__main__':
    main()
