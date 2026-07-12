#!/usr/bin/env python3
from pathlib import Path
import argparse, csv, sys
PACK = Path(__file__).resolve().parents[1]

def rows(path):
    with path.open(newline='') as f:
        return list(csv.DictReader(f))

def optional_rows(path):
    if not path.exists():
        return []
    return rows(path)

def fail(errors, msg):
    errors.append(msg)

def check_unique(errors, rows_, field, label):
    seen = {}
    for i, row in enumerate(rows_, 2):
        value = row.get(field, '')
        if not value:
            fail(errors, f'{label} line {i} has empty {field}')
        elif value in seen:
            fail(errors, f'{label} duplicate {field}: {value}')
        seen[value] = i

def check_unique_key(errors, rows_, fields, label, required=True):
    seen = {}
    for i, row in enumerate(rows_, 2):
        values = tuple(row.get(field, '') for field in fields)
        if required and any(value == '' for value in values):
            missing = ', '.join(field for field, value in zip(fields, values) if value == '')
            fail(errors, f'{label} line {i} has empty key field(s): {missing}')
            continue
        if all(value == '' for value in values):
            continue
        if values in seen:
            key = ' | '.join(values)
            fail(errors, f'{label} duplicate key [{", ".join(fields)}]: {key} duplicates line {seen[values]}')
        else:
            seen[values] = i

def check_exact_duplicates(errors, rows_, label):
    if not rows_:
        return
    fields = list(rows_[0].keys())
    seen = {}
    for i, row in enumerate(rows_, 2):
        values = tuple(row.get(field, '') for field in fields)
        if values in seen:
            fail(errors, f'{label} line {i} exactly duplicates line {seen[values]}')
        else:
            seen[values] = i

def validate_core_sources(errors):
    core_checks = [
        ('master/school.csv', ['school_code', 'academic_year']),
        ('master/academic_years.csv', ['academic_year']),
        ('master/boards.csv', ['board_code']),
        ('master/campuses.csv', ['campus_code']),
        ('master/departments.csv', ['department_code']),
        ('master/mediums.csv', ['medium_code']),
        ('master/grades.csv', ['grade_code']),
        ('master/streams.csv', ['stream_code']),
        ('master/divisions.csv', ['division_code']),
        ('master/subjects.csv', ['subject_code']),
        ('master/houses.csv', ['house_code']),
        ('master/permissions.csv', ['role_shortname', 'permission_area', 'recommended_context']),
        ('master/profile_fields.csv', ['shortname']),
        ('master/roles.csv', ['role_shortname']),
        ('master/staff_designations.csv', ['designation_code']),
        ('master/lookup_values.csv', ['lookup_type', 'code']),
        ('operations/academic_year_promotion_rules.csv', ['rule_code']),
        ('operations/academic_year_rollover_checklist.csv', ['step_no']),
        ('operations/academic_year_transition_models.csv', ['model_code']),
        ('operations/alumni_cohorts_2027.csv', ['cohort_code']),
        ('operations/archive_policy.csv', ['archive_item']),
        ('operations/backup_restore_policy.csv', ['event']),
        ('operations/compatibility_matrix.csv', ['component', 'moodle_feature']),
        ('operations/improvement_backlog.csv', ['priority', 'area', 'improvement']),
        ('operations/promotion_policy.csv', ['rule_code', 'area']),
        ('operations/promotion_status_codes.csv', ['code']),
        ('operations/promotion_validation_rules.csv', ['rule']),
        ('operations/role_guidelines.csv', ['school_role']),
        ('operations/source_references.csv', ['source_name']),
        ('operations/student_status_codes.csv', ['status_code']),
        ('operations/summary.csv', ['metric']),
        ('operations/validation_rules.csv', ['file', 'field', 'rule']),
        ('registration/parent_links.csv', ['parent_username', 'student_username', 'relationship']),
    ]
    for relative, fields in core_checks:
        path = PACK / relative
        if not path.exists():
            continue
        data = rows(path)
        check_exact_duplicates(errors, data, relative)
        check_unique_key(errors, data, fields, relative)

def validate_certificate_rows(errors, year, certs, course_by_code):
    required = [
        'certificate_template_code',
        'certificate_activity_key',
        'certificate_activity_type',
        'certificate_activity_name',
        'certificate_section_number',
        'certificate_download_mode',
        'certificate_verification_enabled',
        'certificate_filename_pattern',
    ]
    for i, row in enumerate(certs, 2):
        if row.get('certificate_enabled') != '1':
            continue
        code = row.get('course_code', '')
        course = course_by_code.get(code)
        if not course:
            continue
        for field in required:
            if not row.get(field):
                fail(errors, f'{year}/course_certificates.csv line {i} missing {field}')
        if row.get('credential_type') != 'certificate':
            fail(errors, f'{year}/course_certificates.csv line {i} must use credential_type=certificate')
        if row.get('requires_plugin') != '1':
            fail(errors, f'{year}/course_certificates.csv line {i} must use requires_plugin=1 for custom certificates')
        if row.get('certificate_activity_type') != 'customcert':
            fail(errors, f'{year}/course_certificates.csv line {i} must use certificate_activity_type=customcert')
        if row.get('certificate_activity_key') != 'course_completion_certificate':
            fail(errors, f'{year}/course_certificates.csv line {i} unexpected certificate_activity_key={row.get("certificate_activity_key")}')
        if row.get('course_shortname') and row.get('course_shortname') != course.get('shortname'):
            fail(errors, f'{year}/course_certificates.csv line {i} shortname mismatch for {code}')

def validate_year(year):
    errors = []
    ydir = PACK / 'years' / year
    out = PACK / 'build' / 'assembled_csv' / year
    next_year = f'{int(year[:4]) + 1}-{int(year[5:]) + 1}'
    promotion_plan_file = f'promotion_plan_to_{next_year}.csv'
    if not ydir.exists():
        fail(errors, f'Missing year directory: {ydir}')
        return errors
    academic_history = rows(ydir / 'academic_history.csv')
    assessment_plan = rows(ydir / 'assessment_plan.csv')
    categories = rows(ydir / 'categories.csv')
    cohort_members = rows(ydir / 'cohort_members.csv')
    courses = rows(ydir / 'courses.csv')
    courses_upload = rows(ydir / 'courses_with_templatecourse_for_moodle_upload.csv')
    cohorts = rows(ydir / 'cohorts.csv')
    groups = rows(ydir / 'groups.csv')
    enrolments = rows(ydir / 'enrolments.csv')
    certs = rows(ydir / 'course_certificates.csv')
    terms = rows(ydir / 'course_term_exams.csv')
    finals = rows(ydir / 'course_final_exams.csv')
    diksha_content = rows(ydir / 'diksha_content_template.csv')
    exam_terms = rows(ydir / 'exam_terms.csv')
    matrix = rows(ydir / 'grade_subject_matrix.csv')
    holidays = rows(ydir / 'holidays.csv')
    promotion_actions = rows(ydir / 'promotion_actions.csv')
    promotion_plan = optional_rows(ydir / promotion_plan_file)
    role_assignments = rows(ydir / 'role_assignments.csv')
    setup = rows(ydir / 'setup.csv')
    template_application = rows(ydir / 'course_template_application.csv')
    term_calendar = rows(ydir / 'term_calendar.csv')
    gradebook_weights = rows(ydir / 'gradebook_weights.csv')
    attendance = rows(ydir / 'attendance_policy.csv')
    year_tables = {
        'academic_history.csv': academic_history,
        'assessment_plan.csv': assessment_plan,
        'attendance_policy.csv': attendance,
        'categories.csv': categories,
        'cohort_members.csv': cohort_members,
        'cohorts.csv': cohorts,
        'course_certificates.csv': certs,
        'course_final_exams.csv': finals,
        'course_template_application.csv': template_application,
        'course_term_exams.csv': terms,
        'courses.csv': courses,
        'courses_with_templatecourse_for_moodle_upload.csv': courses_upload,
        'diksha_content_template.csv': diksha_content,
        'enrolments.csv': enrolments,
        'exam_terms.csv': exam_terms,
        'grade_subject_matrix.csv': matrix,
        'gradebook_weights.csv': gradebook_weights,
        'groups.csv': groups,
        'holidays.csv': holidays,
        'promotion_actions.csv': promotion_actions,
        promotion_plan_file: promotion_plan,
        'role_assignments.csv': role_assignments,
        'setup.csv': setup,
        'term_calendar.csv': term_calendar,
    }
    for filename, table_rows in year_tables.items():
        check_exact_duplicates(errors, table_rows, f'{year}/{filename}')
    check_unique_key(errors, academic_history, ['history_id'], f'{year}/academic_history.csv')
    check_unique_key(errors, assessment_plan, ['course_code'], f'{year}/assessment_plan.csv')
    check_unique_key(errors, attendance, ['academic_year', 'grade_code'], f'{year}/attendance_policy.csv')
    check_unique_key(errors, categories, ['category_code'], f'{year}/categories.csv')
    check_unique_key(errors, categories, ['idnumber'], f'{year}/categories.csv')
    check_unique_key(errors, cohort_members, ['username', 'cohort_code', 'role'], f'{year}/cohort_members.csv')
    check_unique(errors, courses, 'course_code', f'{year}/courses.csv')
    check_unique_key(errors, courses_upload, ['shortname'], f'{year}/courses_with_templatecourse_for_moodle_upload.csv')
    check_unique_key(errors, courses_upload, ['idnumber'], f'{year}/courses_with_templatecourse_for_moodle_upload.csv')
    check_unique(errors, cohorts, 'cohort_code', f'{year}/cohorts.csv')
    check_unique_key(errors, diksha_content, ['board_code', 'medium_code', 'grade_code', 'stream_code', 'subject_code', 'chapter', 'title'], f'{year}/diksha_content_template.csv', required=False)
    check_unique_key(errors, matrix, ['board_code', 'medium_code', 'grade_code', 'stream_code', 'subject_code'], f'{year}/grade_subject_matrix.csv')
    check_unique_key(errors, exam_terms, ['academic_year', 'term_code'], f'{year}/exam_terms.csv')
    check_unique_key(errors, groups, ['group_idnumber'], f'{year}/groups.csv')
    check_unique_key(errors, holidays, ['academic_year', 'date', 'name'], f'{year}/holidays.csv')
    check_unique_key(errors, enrolments, ['course_code', 'cohort_code', 'group_idnumber', 'role_shortname'], f'{year}/enrolments.csv')
    check_unique_key(errors, promotion_actions, ['username', 'to_academic_year', 'to_cohort_code'], f'{year}/promotion_actions.csv', required=False)
    check_unique_key(errors, promotion_plan, ['student_username', 'target_academic_year'], f'{year}/{promotion_plan_file}')
    check_unique_key(errors, role_assignments, ['username', 'role_shortname', 'context_type', 'context_identifier'], f'{year}/role_assignments.csv')
    check_unique_key(errors, setup, ['academic_year', 'school_code', 'board_code'], f'{year}/setup.csv')
    check_unique_key(errors, template_application, ['course_code', 'templatecourse_shortname'], f'{year}/course_template_application.csv')
    check_unique_key(errors, term_calendar, ['academic_year', 'term_code'], f'{year}/term_calendar.csv')
    check_unique_key(errors, certs, ['course_code'], f'{year}/course_certificates.csv')
    check_unique_key(errors, finals, ['course_code'], f'{year}/course_final_exams.csv')
    check_unique_key(errors, terms, ['course_code', 'term_code'], f'{year}/course_term_exams.csv')
    check_unique_key(errors, gradebook_weights, ['course_code'], f'{year}/gradebook_weights.csv')
    course_codes = {r['course_code'] for r in courses}
    course_by_code = {r['course_code']: r for r in courses}
    cohort_codes = {r['cohort_code'] for r in cohorts}
    cert_codes = {r['course_code'] for r in certs if r.get('certificate_enabled') == '1'}
    if cert_codes != course_codes:
        fail(errors, f'{year}: certificate rows do not match course rows. missing={len(course_codes-cert_codes)} extra={len(cert_codes-course_codes)}')
    validate_certificate_rows(errors, year, certs, course_by_code)
    term_counts = {}
    for r in terms:
        term_counts.setdefault(r['course_code'], set()).add(r['term_code'])
    for c in course_codes:
        if term_counts.get(c) != {'TERM1','TERM2'}:
            fail(errors, f'{year}: course missing TERM1/TERM2 rows: {c}')
            break
    final_codes = {r['course_code'] for r in finals if r.get('enabled') == '1'}
    if final_codes != course_codes:
        fail(errors, f'{year}: final exam rows do not match course rows. missing={len(course_codes-final_codes)}')
    for e in enrolments:
        if e['course_code'] not in course_codes:
            fail(errors, f'{year}: enrolment references missing course {e["course_code"]}')
        if e['cohort_code'] not in cohort_codes:
            fail(errors, f'{year}: enrolment references missing cohort {e["cohort_code"]}')
    if not out.exists():
        fail(errors, f'Missing assembled output. Run scripts/assemble.py --year {year}')
    return errors

def main():
    parser = argparse.ArgumentParser(description='Validate school master-pack structured CSV data.')
    parser.add_argument('--year', default='2026-2027')
    args = parser.parse_args()
    errors = []
    students = rows(PACK / 'registration' / 'combined' / '20_users_students.csv')
    parents = rows(PACK / 'registration' / 'combined' / '21_users_parents.csv')
    staff = rows(PACK / 'registration' / 'combined' / '19_users_staff.csv')
    validate_core_sources(errors)
    check_exact_duplicates(errors, students, 'students')
    check_exact_duplicates(errors, parents, 'parents')
    check_exact_duplicates(errors, staff, 'staff')
    check_unique(errors, students, 'username', 'students')
    check_unique(errors, parents, 'username', 'parents')
    check_unique(errors, staff, 'username', 'staff')
    errors.extend(validate_year(args.year))
    if errors:
        print('Structured pack validation failed:')
        for error in errors[:50]:
            print('-', error)
        if len(errors) > 50:
            print(f'... {len(errors)-50} more errors')
        sys.exit(1)
    print(f'Structured pack validation passed for {args.year}.')
    print(f'Students: {len(students)}')
    print(f'Parents: {len(parents)}')
    print(f'Staff: {len(staff)}')
if __name__ == '__main__':
    main()
