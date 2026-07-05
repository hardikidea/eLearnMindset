#!/usr/bin/env python3
from pathlib import Path
import argparse, csv, sys
PACK = Path(__file__).resolve().parents[1]

def rows(path):
    with path.open(newline='') as f:
        return list(csv.DictReader(f))

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

def validate_year(year):
    errors = []
    ydir = PACK / 'years' / year
    out = PACK / 'build' / 'assembled_csv' / year
    if not ydir.exists():
        fail(errors, f'Missing year directory: {ydir}')
        return errors
    courses = rows(ydir / 'courses.csv')
    cohorts = rows(ydir / 'cohorts.csv')
    groups = rows(ydir / 'groups.csv')
    enrolments = rows(ydir / 'enrolments.csv')
    certs = rows(ydir / 'course_certificates.csv')
    terms = rows(ydir / 'course_term_exams.csv')
    finals = rows(ydir / 'course_final_exams.csv')
    check_unique(errors, courses, 'course_code', f'{year}/courses.csv')
    check_unique(errors, cohorts, 'cohort_code', f'{year}/cohorts.csv')
    course_codes = {r['course_code'] for r in courses}
    cohort_codes = {r['cohort_code'] for r in cohorts}
    cert_codes = {r['course_code'] for r in certs if r.get('certificate_enabled') == '1'}
    if cert_codes != course_codes:
        fail(errors, f'{year}: certificate rows do not match course rows. missing={len(course_codes-cert_codes)} extra={len(cert_codes-course_codes)}')
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
    parser = argparse.ArgumentParser(description='Validate Drona Public School structured pack.')
    parser.add_argument('--year', default='2026-2027')
    args = parser.parse_args()
    errors = []
    students = rows(PACK / 'registration' / 'combined' / '20_users_students.csv')
    parents = rows(PACK / 'registration' / 'combined' / '21_users_parents.csv')
    staff = rows(PACK / 'registration' / 'combined' / '19_users_staff.csv')
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
