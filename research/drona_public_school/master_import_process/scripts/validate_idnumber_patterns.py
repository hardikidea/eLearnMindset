#!/usr/bin/env python3
from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path

from common import applies_token_matches, read_rows, source_path, split_applies_to


def one(rows, label):
    if not rows:
        raise RuntimeError(f"Missing rows for {label}")
    return rows[0]


def code_set(rows, field):
    return {row.get(field, "") for row in rows if row.get(field, "")}


def row_map(rows, field):
    return {row.get(field, ""): row for row in rows if row.get(field, "")}


def add(errors, message):
    errors.append(message)


def unique(errors, rows, field, label):
    seen = set()
    for idx, row in enumerate(rows, 2):
        value = row.get(field, "")
        if not value:
            add(errors, f"{label} line {idx}: missing {field}")
        elif value in seen:
            add(errors, f"{label} line {idx}: duplicate {field}={value}")
        seen.add(value)


def validate_year_format(errors, year):
    match = re.fullmatch(r"(\d{4})-(\d{4})", year)
    if not match:
        add(errors, f"Academic year must be YYYY-YYYY: {year}")
        return "0000", "00", "0000_0000"
    start = match.group(1)
    if int(match.group(2)) != int(start) + 1:
        add(errors, f"Academic year end must be start + 1: {year}")
    return start, start[-2:], year.replace("-", "_")


def year_parts_for_value(errors, year, label):
    start, short, token = validate_year_format(errors, year)
    return start, short, token


def validate(source: Path, year: str) -> list[str]:
    errors: list[str] = []
    start_year, short_year, year_token = validate_year_format(errors, year)

    school = one(read_rows(source / "master" / "school.csv"), "master/school.csv")
    boards = read_rows(source / "master" / "boards.csv")
    mediums = read_rows(source / "master" / "mediums.csv")
    grades = read_rows(source / "master" / "grades.csv")
    streams = read_rows(source / "master" / "streams.csv")
    divisions = read_rows(source / "master" / "divisions.csv")
    subjects = read_rows(source / "master" / "subjects.csv")

    trust_code = school.get("trust_code", "")
    school_code = school.get("school_code", "")
    school_prefix = school_code.lower()
    board_codes = code_set(boards, "board_code")
    if not board_codes:
        add(errors, "master/boards.csv must define at least one board_code.")
    board_code = sorted(board_codes)[0] if board_codes else ""
    medium_codes = code_set(mediums, "medium_code")
    grade_codes = code_set(grades, "grade_code")
    stream_codes = code_set(streams, "stream_code")
    division_codes = code_set(divisions, "division_code")
    subject_codes = code_set(subjects, "subject_code")

    for value in medium_codes | stream_codes | subject_codes:
        if not re.fullmatch(r"[A-Z][A-Z0-9_]*", value):
            add(errors, f"Invalid uppercase code: {value}")
    for value in grade_codes:
        if not re.fullmatch(r"[A-Z][A-Z0-9_]*", value):
            add(errors, f"Invalid grade_code, expected uppercase code such as STD05, STD11_SCI, UNI_UG_CSE_Y1 or LMS_CERT_DATA: {value}")
    for value in division_codes:
        if not re.fullmatch(r"[A-Z][A-Z0-9]*", value):
            add(errors, f"Invalid division_code: {value}")
    for idx, row in enumerate(streams, 2):
        applies_to = row.get("applies_to", "")
        stream_code = row.get("stream_code", "")
        tokens = split_applies_to(applies_to)
        if not tokens:
            continue
        if "," in applies_to:
            add(errors, f"streams.csv line {idx}: use pipe delimiter in applies_to, not comma: {applies_to}")
        if not any(applies_token_matches(token, grade, stream_code) for token in tokens for grade in grade_codes):
            add(errors, f"streams.csv line {idx}: applies_to does not match any configured grade: {applies_to}")

    ydir = source / "years" / year
    categories = read_rows(ydir / "categories.csv")
    courses = read_rows(ydir / "courses.csv")
    cohorts = read_rows(ydir / "cohorts.csv")
    groups = read_rows(ydir / "groups.csv")
    grade_division_matrix = read_rows(ydir / "grade_division_matrix.csv")
    enrolments = read_rows(ydir / "enrolments.csv")
    cohort_members = read_rows(ydir / "cohort_members.csv")
    role_assignments = read_rows(ydir / "role_assignments.csv")
    certs = read_rows(ydir / "course_certificates.csv")

    staff = read_rows(source / "registration" / "combined" / "19_users_staff.csv")
    students = read_rows(source / "registration" / "combined" / "20_users_students.csv")
    parents = read_rows(source / "registration" / "combined" / "21_users_parents.csv")
    parent_links = read_rows(source / "registration" / "parent_links.csv")
    roles = read_rows(source / "master" / "roles.csv")

    unique(errors, categories, "category_code", "categories.csv")
    unique(errors, courses, "course_code", "courses.csv")
    unique(errors, cohorts, "cohort_code", "cohorts.csv")
    unique(errors, groups, "group_idnumber", "groups.csv")
    unique(errors, students, "username", "users_students.csv")
    unique(errors, parents, "username", "users_parents.csv")
    unique(errors, staff, "username", "users_staff.csv")

    category_ids = code_set(categories, "idnumber") | code_set(categories, "category_code")
    course_by_code = row_map(courses, "course_code")
    course_shortnames = {row.get("shortname", ""): row for row in courses}
    cohort_codes = code_set(cohorts, "cohort_code")
    all_cohort_codes = set(cohort_codes)
    years_root = source / "years"
    if years_root.exists():
        for year_dir in years_root.iterdir():
            cohort_file = year_dir / "cohorts.csv"
            if cohort_file.exists():
                all_cohort_codes.update(code_set(read_rows(cohort_file), "cohort_code"))
    group_ids = code_set(groups, "group_idnumber")
    active_division_keys = {
        (
            row.get("academic_year", ""),
            row.get("board_code", ""),
            row.get("medium_code", ""),
            row.get("grade_code", ""),
            row.get("stream_code", ""),
            row.get("division_code", ""),
        )
        for row in grade_division_matrix
        if row.get("is_active", "1") in {"1", "yes", "YES", "true", "TRUE"}
    }
    student_usernames = code_set(students, "username")
    parent_usernames = code_set(parents, "username")
    staff_usernames = code_set(staff, "username")
    all_usernames = student_usernames | parent_usernames | staff_usernames
    role_names = code_set(roles, "role_shortname") | {"student", "teacher", "editingteacher", "manager", "parent", "principal", "trustee_manager"}

    for idx, row in enumerate(categories, 2):
        ctype = row.get("category_type", "")
        code = row.get("category_code", "")
        expected_prefix = f"{trust_code}_{board_code}_{school_code}"
        if ctype == "trust" and code != trust_code:
            add(errors, f"categories.csv line {idx}: trust category must be {trust_code}")
        elif ctype == "board" and code != f"{trust_code}_{board_code}":
            add(errors, f"categories.csv line {idx}: board category mismatch {code}")
        elif ctype == "school" and code != expected_prefix:
            add(errors, f"categories.csv line {idx}: school category mismatch {code}")
        elif ctype == "academic_year" and code != f"{expected_prefix}_{year_token}":
            add(errors, f"categories.csv line {idx}: academic year category mismatch {code}")
        elif ctype == "medium":
            if not any(code == f"{expected_prefix}_{year_token}_{medium}" for medium in medium_codes):
                add(errors, f"categories.csv line {idx}: medium category mismatch {code}")
        elif ctype == "grade":
            if not any(code == f"{expected_prefix}_{year_token}_{medium}_{grade}" for medium in medium_codes for grade in grade_codes):
                add(errors, f"categories.csv line {idx}: grade category mismatch {code}")
        elif ctype == "stream":
            if not any(code == f"{expected_prefix}_{year_token}_{medium}_{grade}_{stream}" for medium in medium_codes for grade in grade_codes for stream in stream_codes):
                add(errors, f"categories.csv line {idx}: stream category mismatch {code}")

    for idx, row in enumerate(courses, 2):
        medium = row.get("medium_code", "")
        grade = row.get("grade_code", "")
        stream = row.get("stream_code", "")
        subject = row.get("subject_code", "")
        expected = f"{school_code}-{board_code}-{medium}-{grade}-{stream}-{subject}-{start_year}"
        expected_short = f"{school_code}-{board_code}-{medium}-{grade}-{stream}-{subject}-{short_year}"
        expected_category = f"{trust_code}_{board_code}_{school_code}_{year_token}_{medium}_{grade}_{stream}"
        if row.get("course_code") != expected:
            add(errors, f"courses.csv line {idx}: course_code expected {expected}, got {row.get('course_code')}")
        if row.get("idnumber") != expected:
            add(errors, f"courses.csv line {idx}: idnumber expected {expected}, got {row.get('idnumber')}")
        if row.get("shortname") != expected_short:
            add(errors, f"courses.csv line {idx}: shortname expected {expected_short}, got {row.get('shortname')}")
        if row.get("category_code") != expected_category:
            add(errors, f"courses.csv line {idx}: category_code expected {expected_category}, got {row.get('category_code')}")
        if medium not in medium_codes or grade not in grade_codes or stream not in stream_codes or subject not in subject_codes:
            add(errors, f"courses.csv line {idx}: invalid medium/grade/stream/subject code.")
        if row.get("academic_year") != year:
            add(errors, f"courses.csv line {idx}: academic_year expected {year}")

    for idx, row in enumerate(cohorts, 2):
        medium = row.get("medium_code", "")
        grade = row.get("grade_code", "")
        stream = row.get("stream_code", "")
        division = row.get("division_code", "")
        expected = f"{school_code}-{start_year}-{board_code}-{medium}-{grade}-{stream}-{division}"
        expected_category = f"{trust_code}_{board_code}_{school_code}_{year_token}_{medium}_{grade}_{stream}"
        if row.get("cohort_code") != expected or row.get("idnumber") != expected:
            add(errors, f"cohorts.csv line {idx}: cohort code/idnumber expected {expected}")
        if row.get("context_category_code") != expected_category:
            add(errors, f"cohorts.csv line {idx}: context category expected {expected_category}")
        if division not in division_codes:
            add(errors, f"cohorts.csv line {idx}: invalid division_code {division}")
        if active_division_keys and (year, board_code, medium, grade, stream, division) not in active_division_keys:
            add(errors, f"cohorts.csv line {idx}: division {division} is not allowed by grade_division_matrix for {medium}/{grade}/{stream}")

    for idx, row in enumerate(groups, 2):
        course_code = row.get("course_code", "")
        division = row.get("division_code", "")
        expected = f"{course_code}-{division}"
        course = course_by_code.get(course_code, {})
        if row.get("group_idnumber") != expected:
            add(errors, f"groups.csv line {idx}: group_idnumber expected {expected}")
        if course_code not in course_by_code:
            add(errors, f"groups.csv line {idx}: missing course reference {course_code}")
        if division not in division_codes:
            add(errors, f"groups.csv line {idx}: invalid division_code {division}")
        key = (
            course.get("academic_year", ""),
            course.get("board_code", ""),
            course.get("medium_code", ""),
            course.get("grade_code", ""),
            course.get("stream_code", ""),
            division,
        )
        if active_division_keys and course and key not in active_division_keys:
            add(errors, f"groups.csv line {idx}: division {division} is not allowed by grade_division_matrix for {course_code}")

    for idx, row in enumerate(students, 2):
        username = row.get("username", "")
        match = re.fullmatch(rf"{re.escape(school_prefix)}\.stu\.(\d{{5}})", username)
        if not match:
            add(errors, f"users_students.csv line {idx}: username must match {school_prefix}.stu.00001 pattern: {username}")
            continue
        seq = match.group(1)
        registration_year = row.get("profile_field_current_academic_year") or year
        registration_start, registration_short, _ = year_parts_for_value(errors, registration_year, f"users_students.csv line {idx}")
        expected_id = f"{school_code}{registration_short}-{seq}"
        if row.get("idnumber") != expected_id or row.get("profile_field_admission_no") != expected_id:
            add(errors, f"users_students.csv line {idx}: student id/admission expected {expected_id}")
        if row.get("profile_field_student_gr_no") != f"GR-{school_code}-{registration_start}-{seq}":
            add(errors, f"users_students.csv line {idx}: invalid GR number")
        if row.get("cohort1") and row.get("cohort1") not in all_cohort_codes:
            add(errors, f"users_students.csv line {idx}: cohort1 does not exist: {row.get('cohort1')}")

    for idx, row in enumerate(parents, 2):
        username = row.get("username", "")
        match = re.fullmatch(rf"{re.escape(school_prefix)}\.par\.(\d{{5}})", username)
        if not match:
            add(errors, f"users_parents.csv line {idx}: username must match {school_prefix}.par.00001 pattern: {username}")
            continue
        if row.get("idnumber") != f"{school_code}-PAR-{match.group(1)}":
            add(errors, f"users_parents.csv line {idx}: invalid parent idnumber {row.get('idnumber')}")

    for idx, row in enumerate(staff, 2):
        username = row.get("username", "")
        idnumber = row.get("idnumber", "")
        if username.startswith(f"{school_prefix}.tch."):
            if not re.fullmatch(rf"{re.escape(school_prefix)}\.tch\.[a-z0-9_]+\.[a-z0-9_]+", username):
                add(errors, f"users_staff.csv line {idx}: invalid teacher username {username}")
            if not re.fullmatch(rf"{school_code}-TCH-\d{{3}}", idnumber):
                add(errors, f"users_staff.csv line {idx}: invalid teacher idnumber {idnumber}")
        elif not re.fullmatch(rf"{school_code}-(PRN|TRU|IT|ADM|STF)-\d{{3}}", idnumber):
            add(errors, f"users_staff.csv line {idx}: staff idnumber should follow {school_code}-ROLE-###: {idnumber}")

    for idx, row in enumerate(cohort_members, 2):
        if row.get("username") not in student_usernames:
            add(errors, f"cohort_members.csv line {idx}: student username not found {row.get('username')}")
        if row.get("cohort_code") not in cohort_codes:
            add(errors, f"cohort_members.csv line {idx}: cohort not found {row.get('cohort_code')}")

    for idx, row in enumerate(role_assignments, 2):
        username = row.get("username", "")
        role = row.get("role_shortname", "")
        context_type = row.get("context_type", "")
        context_id = row.get("context_identifier", "")
        if username not in all_usernames:
            add(errors, f"role_assignments.csv line {idx}: username not found {username}")
        if role not in role_names:
            add(errors, f"role_assignments.csv line {idx}: role not found {role}")
        if context_type == "course" and context_id not in course_by_code and context_id not in course_shortnames:
            add(errors, f"role_assignments.csv line {idx}: course context not found {context_id}")
        elif context_type == "category" and context_id not in category_ids:
            add(errors, f"role_assignments.csv line {idx}: category context not found {context_id}")

    for idx, row in enumerate(parent_links, 2):
        if row.get("parent_username") not in parent_usernames:
            add(errors, f"parent_links.csv line {idx}: parent not found {row.get('parent_username')}")
        if row.get("student_username") not in student_usernames:
            add(errors, f"parent_links.csv line {idx}: student not found {row.get('student_username')}")

    for idx, row in enumerate(enrolments, 2):
        course_code = row.get("course_code", "")
        group_id = row.get("group_idnumber", "")
        if course_code not in course_by_code:
            add(errors, f"enrolments.csv line {idx}: course not found {course_code}")
        if row.get("cohort_code") not in cohort_codes:
            add(errors, f"enrolments.csv line {idx}: cohort not found {row.get('cohort_code')}")
        if group_id not in group_ids:
            add(errors, f"enrolments.csv line {idx}: group not found {group_id}")
        if group_id and course_code and not group_id.startswith(f"{course_code}-"):
            add(errors, f"enrolments.csv line {idx}: group does not belong to course {course_code}")
        if row.get("enrolment_method") != "cohort_sync":
            add(errors, f"enrolments.csv line {idx}: enrolment_method should be cohort_sync")

    for idx, row in enumerate(certs, 2):
        course_code = row.get("course_code", "")
        course = course_by_code.get(course_code)
        if not course:
            add(errors, f"course_certificates.csv line {idx}: course not found {course_code}")
            continue
        if row.get("course_shortname") != course.get("shortname"):
            add(errors, f"course_certificates.csv line {idx}: course_shortname mismatch for {course_code}")
        if row.get("certificate_enabled") == "1" and not row.get("certificate_activity_key"):
            add(errors, f"course_certificates.csv line {idx}: missing certificate_activity_key")

    return errors


def main():
    parser = argparse.ArgumentParser(description="Validate configured ID number patterns and cross-file references.")
    parser.add_argument("--source", required=True)
    parser.add_argument("--year", default="2026-2027")
    args = parser.parse_args()

    try:
        errors = validate(Path(args.source), args.year)
    except Exception as exc:
        print(f"ID pattern validation failed: {exc}", file=sys.stderr)
        sys.exit(1)
    if errors:
        print("ID pattern validation failed:")
        for error in errors[:100]:
            print(f"- {error}")
        if len(errors) > 100:
            print(f"... {len(errors) - 100} more errors")
        sys.exit(1)
    print("ID pattern validation passed.")


if __name__ == "__main__":
    main()
