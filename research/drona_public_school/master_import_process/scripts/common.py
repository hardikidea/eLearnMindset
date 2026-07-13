#!/usr/bin/env python3
from __future__ import annotations

import csv
import json
import re
from pathlib import Path

PROCESS_ROOT = Path(__file__).resolve().parents[1]
PACK_ROOT = PROCESS_ROOT.parent
HEADERS_PATH = PACK_ROOT / "config" / "ordered_csv_headers.json"


def next_academic_year(year: str) -> str:
    match = re.fullmatch(r"(\d{4})-(\d{4})", year)
    if not match:
        raise ValueError(f"Invalid academic year: {year}")
    start = int(match.group(1)) + 1
    end = int(match.group(2)) + 1
    return f"{start}-{end}"


SOURCE_FILES = [
    {"sheet": "01_school_master", "source": "master/school.csv", "ordered": "01_school_master.csv", "purpose": "Trust and school identity", "required": True},
    {"sheet": "02_academic_years", "source": "master/academic_years.csv", "ordered": "02_academic_years.csv", "purpose": "Academic year list", "required": True},
    {"sheet": "03_boards", "source": "master/boards.csv", "ordered": "03_boards.csv", "purpose": "Education board master", "required": True},
    {"sheet": "04_mediums", "source": "master/mediums.csv", "ordered": "04_mediums.csv", "purpose": "Teaching medium master", "required": True},
    {"sheet": "05_grades", "source": "master/grades.csv", "ordered": "05_grades.csv", "purpose": "Grade master", "required": True},
    {"sheet": "06_streams", "source": "master/streams.csv", "ordered": "06_streams.csv", "purpose": "Stream master", "required": True},
    {"sheet": "07_divisions", "source": "master/divisions.csv", "ordered": "07_divisions.csv", "purpose": "Division master", "required": True},
    {"sheet": "08_subjects", "source": "master/subjects.csv", "ordered": "08_subjects.csv", "purpose": "Subject master", "required": True},
    {"sheet": "09_subject_matrix", "source": "years/{year}/grade_subject_matrix.csv", "ordered": "09_grade_subject_matrix.csv", "purpose": "Grade, stream and subject matrix", "required": True},
    {"sheet": "10_categories", "source": "years/{year}/categories.csv", "ordered": "10_categories.csv", "purpose": "Moodle category tree", "required": True},
    {"sheet": "11_optional_categories", "source": "years/{year}/categories.csv", "ordered": "11_optional_year_category_model_categories.csv", "purpose": "Optional year category model", "required": False},
    {"sheet": "12_courses", "source": "years/{year}/courses.csv", "ordered": "12_courses.csv", "purpose": "Course source data", "required": True},
    {"sheet": "13_courses_upload", "source": "years/{year}/courses_with_templatecourse_for_moodle_upload.csv", "ordered": "13_courses_with_templatecourse_for_moodle_upload.csv", "purpose": "Moodle course upload shape", "required": True},
    {"sheet": "14_cohorts", "source": "years/{year}/cohorts.csv", "ordered": "14_cohorts.csv", "purpose": "Student class/division cohorts", "required": True},
    {"sheet": "15_groups", "source": "years/{year}/groups.csv", "ordered": "15_groups.csv", "purpose": "Course division groups", "required": True},
    {"sheet": "16_profile_fields", "source": "master/profile_fields.csv", "ordered": "16_user_profile_fields.csv", "purpose": "Custom user profile fields", "required": True},
    {"sheet": "17_custom_roles", "source": "master/roles.csv", "ordered": "17_custom_roles.csv", "purpose": "Custom roles", "required": True},
    {"sheet": "18_role_guidelines", "source": "operations/role_guidelines.csv", "ordered": "18_role_guidelines.csv", "purpose": "Role usage guidance", "required": True},
    {"sheet": "19_users_staff", "source": "registration/combined/19_users_staff.csv", "ordered": "19_users_staff.csv", "purpose": "Staff users", "required": True},
    {"sheet": "20_users_students", "source": "registration/combined/20_users_students.csv", "ordered": "20_users_students.csv", "purpose": "Student users", "required": True},
    {"sheet": "21_users_parents", "source": "registration/combined/21_users_parents.csv", "ordered": "21_users_parents.csv", "purpose": "Parent users", "required": True},
    {"sheet": "22_cohort_members", "source": "years/{year}/cohort_members.csv", "ordered": "22_cohort_members.csv", "purpose": "Student cohort memberships", "required": True},
    {"sheet": "23_role_assignments", "source": "years/{year}/role_assignments.csv", "ordered": "23_role_assignments.csv", "purpose": "Role assignments", "required": True},
    {"sheet": "24_parent_links", "source": "registration/parent_links.csv", "ordered": "24_parent_links.csv", "purpose": "Parent to student links", "required": True},
    {"sheet": "25_enrolments", "source": "years/{year}/enrolments.csv", "ordered": "25_enrolments.csv", "purpose": "Cohort sync enrolments", "required": True},
    {"sheet": "26_lookup_values", "source": "master/lookup_values.csv", "ordered": "26_lookup_values.csv", "purpose": "Lookup values", "required": False},
    {"sheet": "27_validation_rules", "source": "operations/validation_rules.csv", "ordered": "27_validation_rules.csv", "purpose": "Validation rule reference", "required": False},
    {"sheet": "28_source_refs", "source": "operations/source_references.csv", "ordered": "28_source_references.csv", "purpose": "Source references", "required": False},
    {"sheet": "29_summary", "source": "operations/summary.csv", "ordered": "29_summary.csv", "purpose": "Pack summary", "required": False},
    {"sheet": "30_master_template", "source": "templates/legacy/30_master_course_template.csv", "ordered": "30_master_course_template.csv", "purpose": "Master course template", "required": True},
    {"sheet": "31_template_sections", "source": "templates/legacy/31_course_template_sections.csv", "ordered": "31_course_template_sections.csv", "purpose": "Course template sections", "required": True},
    {"sheet": "32_template_activities", "source": "templates/legacy/32_course_template_activities.csv", "ordered": "32_course_template_activities.csv", "purpose": "Course template activities", "required": True},
    {"sheet": "33_template_gradebook", "source": "templates/legacy/33_course_template_gradebook.csv", "ordered": "33_course_template_gradebook.csv", "purpose": "Template gradebook categories", "required": True},
    {"sheet": "34_grade_band_adjust", "source": "templates/legacy/34_grade_band_template_adjustments.csv", "ordered": "34_grade_band_template_adjustments.csv", "purpose": "Grade band template adjustments", "required": False},
    {"sheet": "35_subject_adjust", "source": "templates/legacy/35_subject_template_adjustments.csv", "ordered": "35_subject_template_adjustments.csv", "purpose": "Subject template adjustments", "required": False},
    {"sheet": "36_completion_defaults", "source": "templates/legacy/36_completion_tracking_defaults.csv", "ordered": "36_completion_tracking_defaults.csv", "purpose": "Completion defaults", "required": True},
    {"sheet": "37_template_application", "source": "years/{year}/course_template_application.csv", "ordered": "37_course_template_application.csv", "purpose": "Course template application", "required": True},
    {"sheet": "38_template_custom_fields", "source": "templates/legacy/38_course_template_custom_fields.csv", "ordered": "38_course_template_custom_fields.csv", "purpose": "Course custom fields", "required": False},
    {"sheet": "39_template_review", "source": "templates/legacy/39_course_template_review_checklist.csv", "ordered": "39_course_template_review_checklist.csv", "purpose": "Template review checklist", "required": False},
    {"sheet": "40_certificate_policy", "source": "templates/legacy/40_certificate_badge_policy.csv", "ordered": "40_certificate_badge_policy.csv", "purpose": "Certificate and badge policy", "required": True},
    {"sheet": "41_report_access", "source": "templates/legacy/41_template_report_access_matrix.csv", "ordered": "41_template_report_access_matrix.csv", "purpose": "Report access matrix", "required": False},
    {"sheet": "42_test_coverage", "source": "templates/legacy/42_behat_course_template_coverage_mapping.csv", "ordered": "42_behat_course_template_coverage_mapping.csv", "purpose": "Template test coverage", "required": False},
    {"sheet": "43_content_template", "source": "years/{year}/diksha_content_template.csv", "ordered": "43_diksha_content_template.csv", "purpose": "External content mapping template", "required": False},
    {"sheet": "44_transition_models", "source": "operations/academic_year_transition_models.csv", "ordered": "44_academic_year_transition_models.csv", "purpose": "Academic year transition models", "required": False},
    {"sheet": "45_promotion_rules", "source": "operations/academic_year_promotion_rules.csv", "ordered": "45_academic_year_promotion_rules.csv", "purpose": "Promotion rules", "required": False},
    {"sheet": "46_rollover_checklist", "source": "operations/academic_year_rollover_checklist.csv", "ordered": "46_academic_year_rollover_checklist.csv", "purpose": "Rollover checklist", "required": False},
    {"sheet": "47_promotion_policy", "source": "operations/promotion_policy.csv", "ordered": "47_promotion_policy.csv", "purpose": "Promotion policy", "required": False},
    {"sheet": "48_promotion_status", "source": "operations/promotion_status_codes.csv", "ordered": "48_promotion_status_codes.csv", "purpose": "Promotion status codes", "required": False},
    {"sheet": "49_promotion_validation", "source": "operations/promotion_validation_rules.csv", "ordered": "49_promotion_validation_rules.csv", "purpose": "Promotion validation rules", "required": False},
    {"sheet": "50_student_status", "source": "operations/student_status_codes.csv", "ordered": "50_student_status_codes.csv", "purpose": "Student status codes", "required": False},
    {"sheet": "51_academic_history", "source": "years/{year}/academic_history.csv", "ordered": "51_student_academic_history_template.csv", "purpose": "Student academic history", "required": False},
    {"sheet": "52_promotion_plan", "source": "years/{year}/promotion_plan_to_{next_year}.csv", "ordered": "52_student_promotion_plan_2027_2028.csv", "purpose": "Student promotion plan", "required": False},
    {"sheet": "53_promotion_actions", "source": "years/{year}/promotion_actions.csv", "ordered": "53_promotion_actions.csv", "purpose": "Promotion actions", "required": False},
    {"sheet": "54_next_year_courses", "source": "years/{next_year}/courses.csv", "ordered": "54_next_year_courses_2027_2028.csv", "purpose": "Next academic year courses", "required": False},
    {"sheet": "55_next_year_cohorts", "source": "years/{next_year}/cohorts.csv", "ordered": "55_next_year_cohorts_2027_2028.csv", "purpose": "Next academic year cohorts", "required": False},
    {"sheet": "56_next_year_groups", "source": "years/{next_year}/groups.csv", "ordered": "56_next_year_groups_2027_2028.csv", "purpose": "Next academic year groups", "required": False},
    {"sheet": "57_next_year_enrolments", "source": "years/{next_year}/enrolments.csv", "ordered": "57_next_year_enrolments_2027_2028.csv", "purpose": "Next academic year enrolments", "required": False},
    {"sheet": "58_alumni_cohorts", "source": "operations/alumni_cohorts_2027.csv", "ordered": "58_alumni_cohorts_2027.csv", "purpose": "Alumni cohorts", "required": False},
    {"sheet": "59_archive_policy", "source": "operations/archive_policy.csv", "ordered": "59_archive_policy.csv", "purpose": "Archive policy", "required": False},
    {"sheet": "60_improvement_backlog", "source": "operations/improvement_backlog.csv", "ordered": "60_improvement_backlog.csv", "purpose": "Improvement backlog", "required": False},
    {"sheet": "61_compatibility", "source": "operations/compatibility_matrix.csv", "ordered": "61_compatibility_matrix.csv", "purpose": "Compatibility matrix", "required": False},
    {"sheet": "assessment_plan", "source": "years/{year}/assessment_plan.csv", "ordered": "assessment_plan.csv", "purpose": "Assessment plan", "required": True},
    {"sheet": "attendance_policy", "source": "years/{year}/attendance_policy.csv", "ordered": "attendance_policy.csv", "purpose": "Attendance policy", "required": False},
    {"sheet": "course_certificates", "source": "years/{year}/course_certificates.csv", "ordered": "course_certificates.csv", "purpose": "Course certificates", "required": True},
    {"sheet": "course_final_exams", "source": "years/{year}/course_final_exams.csv", "ordered": "course_final_exams.csv", "purpose": "Course final exams", "required": True},
    {"sheet": "course_term_exams", "source": "years/{year}/course_term_exams.csv", "ordered": "course_term_exams.csv", "purpose": "Course term exams", "required": True},
    {"sheet": "exam_terms", "source": "years/{year}/exam_terms.csv", "ordered": "exam_terms.csv", "purpose": "Exam terms", "required": True},
    {"sheet": "gradebook_weights", "source": "years/{year}/gradebook_weights.csv", "ordered": "gradebook_weights.csv", "purpose": "Gradebook weights", "required": True},
]


def headers_map() -> dict[str, list[str]]:
    if not HEADERS_PATH.exists():
        return {}
    return json.loads(HEADERS_PATH.read_text())


def resolve_tokens(value: str, year: str) -> str:
    return value.format(year=year, next_year=next_academic_year(year))


def source_path(entry: dict, year: str, root: Path) -> Path:
    return root / resolve_tokens(entry["source"], year)


def read_header(path: Path) -> list[str]:
    if not path.exists():
        return []
    with path.open(newline="") as handle:
        reader = csv.reader(handle)
        return next(reader, [])


def read_rows(path: Path) -> list[dict[str, str]]:
    if not path.exists():
        return []
    with path.open(newline="") as handle:
        return list(csv.DictReader(handle))


def write_rows(path: Path, fieldnames: list[str], rows: list[dict[str, str]]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=fieldnames, extrasaction="ignore")
        writer.writeheader()
        for row in rows:
            writer.writerow({field: row.get(field, "") for field in fieldnames})


def expected_headers(entry: dict, year: str, root: Path = PACK_ROOT) -> list[str]:
    source_header = read_header(source_path(entry, year, root))
    if source_header:
        return source_header
    ordered = entry.get("ordered")
    return headers_map().get(ordered, [])


def manifest_rows(year: str) -> list[dict[str, str]]:
    return [
        {
            "sheet_name": entry["sheet"],
            "source_csv": resolve_tokens(entry["source"], year),
            "ordered_csv": entry["ordered"],
            "required": "1" if entry.get("required") else "0",
            "purpose": entry["purpose"],
        }
        for entry in SOURCE_FILES
    ]


def normalise_cell(value) -> str:
    if value is None:
        return ""
    return str(value).strip()


def split_applies_to(value: str) -> list[str]:
    """Split a scope expression.

    Pipe is the canonical delimiter. Comma is accepted only for older workbooks.
    """
    return [
        token.strip().upper()
        for token in re.split(r"[|,]", value or "")
        if token.strip()
    ]


def is_grade_scope_token(token: str) -> bool:
    token = (token or "").strip().upper()
    if token in {"ALL", "*"}:
        return True
    grade_token = token.split("_", 1)[0]
    if "-" in grade_token:
        start, end = [part.strip() for part in grade_token.split("-", 1)]
        return grade_scope_rank(start) is not None and grade_scope_rank(end) is not None
    if grade_scope_rank(grade_token) is not None:
        return True
    return bool(re.fullmatch(r"[A-Z][A-Z0-9_]{2,}", token))


def grade_scope_rank(grade_code: str) -> int | None:
    """Return a sortable rank for supported grade scope tokens."""
    code = (grade_code or "").strip().upper()
    if "_" in code:
        prefix, suffix = code.split("_", 1)
        if suffix and re.fullmatch(r"(PRE|STD)\d{2}", prefix):
            code = prefix
    match = re.fullmatch(r"(PRE|STD)(\d{2})", code)
    if not match:
        return None
    prefix, number = match.groups()
    offset = 0 if prefix == "PRE" else 100
    return offset + int(number)


def applies_token_matches(token: str, grade_code: str, stream_code: str) -> bool:
    token = (token or "").strip().upper()
    grade_code = (grade_code or "").strip().upper()
    stream_code = (stream_code or "").strip().upper()
    if not token:
        return False
    if token in {"ALL", "*"}:
        return True

    grade_token = token
    required_stream_suffix = ""
    if "_" in token:
        candidate_grade, token_stream = token.split("_", 1)
        if grade_scope_rank(candidate_grade) is not None:
            grade_token = candidate_grade
            if token_stream != stream_code:
                return False
            required_stream_suffix = token_stream

    if "-" in grade_token:
        start, end = [part.strip() for part in grade_token.split("-", 1)]
        start_rank = grade_scope_rank(start)
        end_rank = grade_scope_rank(end)
        grade_rank = grade_scope_rank(grade_code)
        if start_rank is None or end_rank is None or grade_rank is None:
            return False
        return start_rank <= grade_rank <= end_rank

    if required_stream_suffix:
        return grade_code in {grade_token, f"{grade_token}_{required_stream_suffix}"}

    if grade_token == grade_code:
        return True
    if grade_code.startswith(f"{grade_token}_"):
        return True
    return f"_{grade_token}_" in f"_{grade_code}_"


def stream_applies_to_grade(grade_code: str, stream_code: str, applies_to: str) -> bool:
    """Check whether a stream row applies to a grade.

    Supported canonical examples:
    - ALL
    - STD01-STD10
    - STD11|STD12
    - STD11_SCI|STD12_SCI

    Non-grade descriptive tokens such as SWAYAM or NPTEL are ignored by this
    matcher and should normally live in the notes column.
    """
    applies_to = (applies_to or "").strip()
    if not applies_to:
        return True
    tokens = split_applies_to(applies_to)
    return any(applies_token_matches(token, grade_code, stream_code) for token in tokens)


def subject_applies_to_grade_stream(grade_code: str, stream_code: str, applies_to: str) -> bool:
    """Check whether a subject row applies to a grade/stream combination.

    Subject rows may use an explicit applies_to column. When a workbook does
    not have that column, the notes column can carry the same scope tokens, for
    example PRE01-STD12 or STD11_SCI|STD12_SCI. Descriptive tokens that are not
    grade scopes are ignored.
    """
    tokens = split_applies_to(applies_to)
    scoped_tokens = [token for token in tokens if is_grade_scope_token(token)]
    if not scoped_tokens:
        return False
    return any(applies_token_matches(token, grade_code, stream_code) for token in scoped_tokens)
