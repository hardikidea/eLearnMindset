#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
import re
from collections import defaultdict
from pathlib import Path

from common import PACK_ROOT, applies_token_matches, stream_applies_to_grade


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


def year_start(year: str) -> str:
    return year.split("-", 1)[0]


def year_short(year: str) -> str:
    return year_start(year)[-2:]


def year_token(year: str) -> str:
    return year.replace("-", "_")


def language_for_medium(medium_code: str, mediums: dict[str, dict[str, str]]) -> str:
    medium = mediums.get(medium_code, {})
    name = medium.get("medium_name", "")
    return name.replace(" Medium", "") or medium_code


def education_system(grade: dict[str, str]) -> str:
    label = grade.get("moodle_label", "").upper()
    stage = grade.get("stage", "").upper()
    if label == "SCHOOL":
        return "SCHOOL"
    if label == "UNIVERSITY":
        return "UNIVERSITY"
    if label == "VOCATIONAL":
        return "VOCATIONAL"
    if label == "CHARTER":
        return "PROFESSIONAL"
    if label == "CERTIFICATION":
        return "ONLINE"
    if "HIGHER ED" in stage:
        return "UNIVERSITY"
    return label or "GENERAL"


def term_model_for_grade(grade: dict[str, str]) -> str:
    system = education_system(grade)
    if system == "SCHOOL":
        return "TWO_TERM"
    if system in {"UNIVERSITY", "DIPLOMA"}:
        return "SEMESTER"
    if system in {"ONLINE", "VOCATIONAL", "PROFESSIONAL"}:
        return "MODULE"
    return "ANNUAL"


def term_codes_for_grade(grade: dict[str, str]) -> list[str]:
    model = term_model_for_grade(grade)
    if model == "SEMESTER":
        return ["SEM1", "SEM2"]
    if model == "MODULE":
        return ["MODULE"]
    return ["TERM1", "TERM2"]


def matching_stream(grade_code: str, streams: list[dict[str, str]], preferred: str = "") -> str:
    if preferred:
        for stream in streams:
            if stream.get("stream_code") == preferred and stream_applies_to_grade(
                grade_code, preferred, stream.get("applies_to", "")
            ):
                return preferred
    for stream in streams:
        stream_code = stream.get("stream_code", "")
        if stream_applies_to_grade(grade_code, stream_code, stream.get("applies_to", "")):
            return stream_code
    return preferred or "GEN"


def clean_legacy_scope_note(value: str) -> str:
    note = re.sub(r"\s*Former scope labels? moved from applies_to: [^.]+\.?", "", value or "")
    return re.sub(r"\s+", " ", note).strip()


def patch_subject_scopes(subjects: list[dict[str, str]]) -> list[dict[str, str]]:
    scope_overrides = {
        "CLOUD_ARCH": "LMS_CERT_CLOUD",
        "PROMPT_ENG": "UNI_DIP_AI|UNI_PG_AI|LMS_CERT_MLOPS|LMS_CERT_DATA|LMS_CERT_WEB3",
        "FSD_WEB": "UNI_DIP_CS|UNI_UG_BCA|UNI_PG_MCA|LMS_CERT_FSD",
        "CYBER_SEC": "UNI_DIP_CS|LMS_CERT_CYBER",
        "DATA_SCI": "UNI_UG_DS|UNI_PG_AI|LMS_CERT_DATA|LMS_CERT_MLOPS",
        "BLK_CHAIN": "LMS_CERT_WEB3",
        "PROD_MGT": "UNI_UG_BBA|UNI_PG_MBA|MGT_PGDM|LMS_CERT_PM|LMS_CERT_AGILE",
        "JEE_PHY": "STD11_SCI|STD12_SCI",
        "JEE_MATH": "STD11_SCI|STD12_SCI",
        "NEET_BIO": "STD11_SCI|STD12_SCI",
        "UPSC_POL": "LMS_SWAYAM_01|UNI_UG_BSW|UNI_PG_MSW",
        "APT_REAS": "PRO_CA|PRO_CS_EXE|LMS_CERT_PM|LMS_NSDC_L4|LMS_NSDC_L6",
    }
    patched = []
    for row in subjects:
        updated = dict(row)
        updated["notes"] = clean_legacy_scope_note(updated.get("notes", ""))
        code = updated.get("subject_code", "")
        if code in scope_overrides:
            updated["applies_to"] = scope_overrides[code]
        patched.append(updated)
    return patched


def build_lookup_values(
    years: list[dict[str, str]],
    boards: list[dict[str, str]],
    mediums: list[dict[str, str]],
    grades: list[dict[str, str]],
    streams: list[dict[str, str]],
    divisions: list[dict[str, str]],
    subjects: list[dict[str, str]],
    statuses: list[dict[str, str]],
) -> list[dict[str, str]]:
    rows: list[dict[str, str]] = []

    def add(lookup_type: str, code: str, label: str) -> None:
        if code:
            rows.append({"lookup_type": lookup_type, "code": code, "label": label or code})

    for row in years:
        add("academic_year", row.get("academic_year", ""), row.get("academic_year", ""))
    for row in boards:
        add("board", row.get("board_code", ""), row.get("board_name", ""))
    for row in mediums:
        add("medium", row.get("medium_code", ""), row.get("medium_name", ""))
    for row in grades:
        add("grade", row.get("grade_code", ""), row.get("grade_name", ""))
        add("grade_stage", row.get("stage", ""), row.get("stage", ""))
        add("academic_system", education_system(row), education_system(row).title())
        add("term_model", term_model_for_grade(row), term_model_for_grade(row).replace("_", " ").title())
    for row in streams:
        add("stream", row.get("stream_code", ""), row.get("stream_name", ""))
    for row in divisions:
        add("division", row.get("division_code", ""), row.get("division_name", ""))
    for row in subjects:
        add("subject", row.get("subject_code", ""), row.get("subject_name", ""))
        add("subject_category", row.get("subject_category", ""), row.get("subject_category", ""))
    for row in statuses:
        add("result_status", row.get("code", ""), row.get("name", ""))
    for code, label in [
        ("PROMOTED", "Promoted"),
        ("STREAM_SELECTED", "Stream selected"),
        ("MANUAL_CONTINUATION", "Manual continuation"),
        ("ALUMNI", "Alumni"),
        ("REPEATED", "Repeated"),
        ("TRANSFER_OUT", "Transfer out"),
    ]:
        add("promotion_decision", code, label)
    for code, label in [
        ("TERM1", "Term 1"),
        ("TERM2", "Term 2"),
        ("FINAL", "Final exam"),
        ("SEM1", "Semester 1"),
        ("SEM2", "Semester 2"),
        ("MODULE", "Module based"),
    ]:
        add("assessment_period", code, label)
    for code, label in [
        ("SCHOOL_YEARLY", "School yearly setup"),
        ("UNIVERSITY_SEMESTER", "University semester setup"),
        ("VOCATIONAL_MODULE", "Vocational module setup"),
        ("ONLINE_CERTIFICATION", "Online certification setup"),
    ]:
        add("setup_model", code, label)

    deduped: dict[tuple[str, str], dict[str, str]] = {}
    for row in rows:
        deduped.setdefault((row["lookup_type"], row["code"]), row)
    return [deduped[key] for key in sorted(deduped)]


SUBJECT_POLICIES = {
    "Language": (
        "Reading passages, grammar tasks, writing assignments, speaking/listening tasks",
        "Use chapter sections for reading, grammar, writing and oral evidence.",
    ),
    "Core": (
        "Concept overview, practice worksheet, quiz, assignment and mastery gate",
        "Keep chapter gates short and align assignments to board/unit outcomes.",
    ),
    "Science": (
        "Lab safety, experiments, diagrams, observations and practical reports",
        "Use assignments for lab/practical evidence and quizzes for conceptual checks.",
    ),
    "Commerce": (
        "Accounting practice sets, case studies, worksheets and project work",
        "Use practice activities plus teacher-reviewed business/accounting assignments.",
    ),
    "Humanities": (
        "Maps, timelines, case studies, source analysis and essay assignments",
        "Use document/source analysis and reflective written submissions.",
    ),
    "Engineering": (
        "Lab work, code/practical tasks, design problems and project checkpoints",
        "Use assignments for lab/project evidence and quizzes for theory checkpoints.",
    ),
    "Medical": (
        "Clinical concepts, diagrams, case scenarios and practical competency checks",
        "Use competency-style assignments and keep sensitive patient data out.",
    ),
    "Nursing": (
        "Clinical procedure checklist, case notes, skill demonstration and viva prep",
        "Use rubrics for practical skill evidence and reflective clinical logs.",
    ),
    "Law": (
        "Bare act reading, case briefs, argument drafting and moot tasks",
        "Use assignments for case analysis and discussion activities for argumentation.",
    ),
    "Management": (
        "Case studies, simulations, presentations and applied project work",
        "Use group work, reflective journals and short concept quizzes.",
    ),
    "Professional": (
        "Exam-style practice, standards reference, mock tests and revision plans",
        "Use timed quizzes and structured practice assignments.",
    ),
    "Design": (
        "Portfolio tasks, critique boards, design briefs and usability review",
        "Use assignments for portfolio evidence and discussion for critique.",
    ),
    "Architecture": (
        "Studio briefs, drawings, model reviews and site-analysis tasks",
        "Use portfolio submissions and staged design review checkpoints.",
    ),
    "Emerging Tech": (
        "Tool labs, guided demos, project tasks and applied reflection",
        "Use practical assignments and short quizzes for tool/concept validation.",
    ),
    "Competitive Prep": (
        "Timed quizzes, previous-year questions, explanations and performance review",
        "Use quiz banks, analytics and retake rules for exam preparation.",
    ),
    "Vocational": (
        "Safety checklist, practical demonstration, workplace evidence and portfolio",
        "Use rubrics and evidence uploads for skill demonstration.",
    ),
    "Co-curricular": (
        "Portfolio/performance evidence, activity logs and reflection",
        "Use lighter completion gates and teacher-reviewed participation evidence.",
    ),
    "Elective": (
        "Exploratory activities, short projects and optional extension tasks",
        "Use flexible completion with teacher-reviewed project evidence.",
    ),
}


def build_subject_adjustments(subjects: list[dict[str, str]]) -> list[dict[str, str]]:
    grouped: dict[str, list[str]] = defaultdict(list)
    for row in subjects:
        grouped[row.get("subject_category", "General")].append(row.get("subject_code", ""))

    rows = []
    for category in sorted(grouped):
        additions, sections = SUBJECT_POLICIES.get(
            category,
            (
                "Overview, practice activity, quiz, assignment and completion gate",
                "Use existing chapter sections and adapt activity mix to the subject.",
            ),
        )
        rows.append(
            {
                "subject_area": category,
                "subject_codes": "|".join(code for code in grouped[category] if code),
                "recommended_template_additions": additions,
                "default_extra_sections": sections,
            }
        )
    return rows


def build_content_template(
    year: str,
    school: dict[str, str],
    boards: list[dict[str, str]],
    mediums: list[dict[str, str]],
    grades: list[dict[str, str]],
    matrix: list[dict[str, str]],
) -> list[dict[str, str]]:
    start = year_start(year)
    school_code = school.get("school_code", "")
    board_code = boards[0].get("board_code", "") if boards else ""
    medium_map = {row.get("medium_code", ""): row for row in mediums}
    grade_map = {row.get("grade_code", ""): row for row in grades}
    rows = []
    for row in matrix:
        grade = grade_map.get(row.get("grade_code", ""), {})
        system = education_system(grade)
        term_model = term_model_for_grade(grade)
        if term_model == "SEMESTER":
            chapter = "SEM1-M01"
            title = f"Semester 1 - {row.get('subject_name', row.get('subject_code', ''))} reference module"
        elif term_model == "MODULE":
            chapter = "MOD01"
            title = f"Module 1 - {row.get('subject_name', row.get('subject_code', ''))} applied resource"
        else:
            chapter = "CH01"
            title = f"Chapter 1 - {row.get('subject_name', row.get('subject_code', ''))} readiness resource"
        course_code = (
            f"{school_code}-{board_code}-{row.get('medium_code', '')}-{row.get('grade_code', '')}-"
            f"{row.get('stream_code', '')}-{row.get('subject_code', '')}-{start}"
        )
        rows.append(
            {
                "board_code": row.get("board_code", ""),
                "medium_code": row.get("medium_code", ""),
                "grade_code": row.get("grade_code", ""),
                "stream_code": row.get("stream_code", ""),
                "subject_code": row.get("subject_code", ""),
                "chapter": chapter,
                "title": title,
                "diksha_identifier": "",
                "content_type": "Reference",
                "resource_type": "URL",
                "language": language_for_medium(row.get("medium_code", ""), medium_map),
                "license": "Verify before use",
                "attribution": f"{system.title()} academic team to verify official resource",
                "source_url": "",
                "artifact_url": "",
                "download_url": "",
                "moodle_course_code": course_code,
                "moodle_section": "2",
                "import_mode": "manual_review",
                "status": "placeholder",
            }
        )
    return rows


def grade_family(code: str) -> tuple[str, int | None]:
    match = re.fullmatch(r"(.+)_Y(\d+)", code)
    if match:
        return match.group(1), int(match.group(2))
    return code, None


def display_order(row: dict[str, str]) -> int:
    try:
        return int(row.get("display_order", "0"))
    except ValueError:
        return 0


def school_stream_for_grade(code: str) -> str:
    if "_" not in code:
        return "GEN"
    suffix = code.rsplit("_", 1)[1]
    return suffix or "GEN"


def normalize_stream_code(stream_code: str) -> str:
    return "ART" if stream_code == "ARTS" else stream_code


def normalize_grade_stream(grade_code: str, stream_code: str) -> tuple[str, str]:
    stream = normalize_stream_code(stream_code)
    if grade_code in {"STD11", "STD12"} and stream in {"SCI", "COM", "ART"}:
        return f"{grade_code}_{stream}", stream
    return grade_code, stream


def normalize_cohort_code(value: str) -> str:
    value = (value or "").replace("-STD11-ARTS-", "-STD11_ART-ART-").replace("-STD12-ARTS-", "-STD12_ART-ART-")
    value = value.replace("-STD11-SCI-", "-STD11_SCI-SCI-").replace("-STD12-SCI-", "-STD12_SCI-SCI-")
    value = value.replace("-STD11-COM-", "-STD11_COM-COM-").replace("-STD12-COM-", "-STD12_COM-COM-")
    return value


def normalize_student_rows(students: list[dict[str, str]]) -> list[dict[str, str]]:
    rows = []
    for row in students:
        updated = dict(row)
        grade, stream = normalize_grade_stream(updated.get("grade_code", ""), updated.get("stream_code", ""))
        updated["grade_code"] = grade
        updated["stream_code"] = stream
        updated["cohort1"] = normalize_cohort_code(updated.get("cohort1", ""))
        profile_grade, profile_stream = normalize_grade_stream(
            updated.get("profile_field_current_grade_code", ""),
            updated.get("profile_field_current_stream_code", ""),
        )
        updated["profile_field_current_grade_code"] = profile_grade
        updated["profile_field_current_stream_code"] = profile_stream
        previous_grade, previous_stream = normalize_grade_stream(
            updated.get("profile_field_previous_grade_code", ""),
            updated.get("profile_field_previous_stream_code", ""),
        )
        updated["profile_field_previous_grade_code"] = previous_grade
        updated["profile_field_previous_stream_code"] = previous_stream
        rows.append(updated)
    return rows


def build_cohort_members(students: list[dict[str, str]]) -> list[dict[str, str]]:
    return [
        {"username": row.get("username", ""), "cohort_code": row.get("cohort1", ""), "role": "student"}
        for row in students
        if row.get("username") and row.get("cohort1")
    ]


def build_promotion_rules(grades: list[dict[str, str]], streams: list[dict[str, str]]) -> list[dict[str, str]]:
    grade_by_code = {row.get("grade_code", ""): row for row in grades}
    school_grades = [
        row
        for row in sorted(grades, key=display_order)
        if education_system(row) == "SCHOOL" and row.get("grade_code")
    ]
    general_school_grades = [row for row in school_grades if school_stream_for_grade(row["grade_code"]) == "GEN"]
    rows: list[dict[str, str]] = []

    def add(
        rule_code: str,
        from_grade: str,
        from_stream: str,
        result: str,
        decision: str,
        to_grade: str,
        to_stream: str,
        review: str,
        notes: str,
    ) -> None:
        rows.append(
            {
                "rule_code": rule_code,
                "from_grade_code": from_grade,
                "from_stream_code": from_stream,
                "result_status": result,
                "promotion_decision": decision,
                "to_grade_code": to_grade,
                "to_stream_code": to_stream,
                "requires_manual_review": review,
                "notes": notes,
            }
        )

    for current_row, target_row in zip(general_school_grades, general_school_grades[1:]):
        current = current_row["grade_code"]
        target = target_row["grade_code"]
        add(
            f"PROMOTE_{current}_TO_{target}",
            current,
            "GEN",
            "PASS",
            "PROMOTED",
            target,
            "GEN",
            "0",
            "Default school annual promotion rule derived from grade display order.",
        )

    stream_entry_grades = [
        row
        for row in school_grades
        if re.fullmatch(r"STD11_[A-Z][A-Z0-9_]*", row.get("grade_code", ""))
    ]
    if "STD10" in grade_by_code:
        for target_row in stream_entry_grades:
            target = target_row["grade_code"]
            stream = school_stream_for_grade(target)
            add(
                f"STD10_TO_{target}",
                "STD10",
                "GEN",
                "PASS",
                "STREAM_SELECTED",
                target,
                stream,
                "1",
                "Std 10 to Std 11 requires manual stream selection and subject validation.",
            )

    for current_row in stream_entry_grades:
        current = current_row["grade_code"]
        stream = school_stream_for_grade(current)
        target = current.replace("STD11_", "STD12_", 1)
        if target in grade_by_code:
            add(
                f"{current}_TO_{target}",
                current,
                stream,
                "PASS",
                "PROMOTED",
                target,
                stream,
                "0",
                "Continue the same higher-secondary stream to Standard 12 unless school approves a stream change.",
            )

    for current_row in school_grades:
        current = current_row["grade_code"]
        if not re.fullmatch(r"STD12_[A-Z][A-Z0-9_]*", current):
            continue
        stream = school_stream_for_grade(current)
        if current in grade_by_code:
            add(
                f"{current}_TO_ALUMNI",
                current,
                stream,
                "COMPLETED",
                "ALUMNI",
                "ALUMNI",
                "ALUMNI",
                "1",
                "Mark completed school students as alumni; keep records and avoid deleting users.",
            )

    grouped: dict[str, list[tuple[int | None, str]]] = defaultdict(list)
    for grade in grades:
        code = grade.get("grade_code", "")
        if grade.get("moodle_label") == "School":
            continue
        family, year_no = grade_family(code)
        grouped[family].append((year_no, code))

    for family, members in grouped.items():
        ordered = sorted(members, key=lambda item: (999 if item[0] is None else item[0], item[1]))
        if len(ordered) == 1:
            code = ordered[0][1]
            stream = matching_stream(code, streams)
            add(
                f"{code}_TO_ALUMNI",
                code,
                stream,
                "COMPLETED",
                "ALUMNI",
                "ALUMNI",
                "ALUMNI",
                "1",
                "One-stage program or certification completion; archive the learner as alumni/completed.",
            )
            continue
        for index, (year_no, code) in enumerate(ordered):
            stream = matching_stream(code, streams)
            if index + 1 < len(ordered):
                next_year_no, next_code = ordered[index + 1]
                next_stream = matching_stream(next_code, streams, stream)
                consecutive = year_no is not None and next_year_no == year_no + 1
                add(
                    f"{code}_TO_{next_code}",
                    code,
                    stream,
                    "PASS",
                    "PROMOTED" if consecutive else "MANUAL_CONTINUATION",
                    next_code,
                    next_stream,
                    "0" if consecutive else "1",
                    "University/diploma semester-year progression rule."
                    if consecutive
                    else "Missing intermediate years in the master grade list; review before promotion.",
                )
            else:
                add(
                    f"{code}_TO_ALUMNI",
                    code,
                    stream,
                    "COMPLETED",
                    "ALUMNI",
                    "ALUMNI",
                    "ALUMNI",
                    "1",
                    "Final listed program year or certification completion; archive as alumni/completed.",
                )

    add(
        "REPEAT_SAME_GRADE",
        "ANY",
        "ANY",
        "REPEAT",
        "REPEATED",
        "SAME",
        "SAME",
        "1",
        "Create next-year same grade/stream cohort and enrol student there. Do not keep them only in old cohort.",
    )
    add(
        "TRANSFER_OUT",
        "ANY",
        "ANY",
        "LEFT",
        "TRANSFER_OUT",
        "",
        "",
        "1",
        "Remove future cohort membership, optionally suspend user after export. Do not delete records.",
    )

    deduped: dict[str, dict[str, str]] = {}
    for row in rows:
        deduped.setdefault(row["rule_code"], row)
    return list(deduped.values())


def make_course_code(school_code: str, board: str, medium: str, grade: str, stream: str, subject: str, year: str) -> str:
    return f"{school_code}-{board}-{medium}-{grade}-{stream}-{subject}-{year_start(year)}"


def make_course_shortname(school_code: str, board: str, medium: str, grade: str, stream: str, subject: str, year: str) -> str:
    return f"{school_code}-{board}-{medium}-{grade}-{stream}-{subject}-{year_short(year)}"


def make_stream_category(trust: str, board: str, school: str, year: str, medium: str, grade: str, stream: str) -> str:
    return f"{trust}_{board}_{school}_{year_token(year)}_{medium}_{grade}_{stream}"


def make_cohort_code(school: str, year: str, board: str, medium: str, grade: str, stream: str, division: str) -> str:
    return f"{school}-{year_start(year)}-{board}-{medium}-{grade}-{stream}-{division}"


def find_grade_band(grade_code: str, stream_code: str, grade_bands: list[dict[str, str]]) -> dict[str, str]:
    fallback: dict[str, str] = {}
    for row in grade_bands:
        scope = row.get("grade_codes", "")
        if scope.upper() == "ALL":
            fallback = row
            continue
        if stream_applies_to_grade(grade_code, stream_code, scope):
            return row
    return fallback


def build_exam_terms(year: str) -> list[dict[str, str]]:
    terms = [
        ("TERM1", "Term 1 Exam", "20", "School term 1 exam definition."),
        ("TERM2", "Term 2 Exam", "20", "School term 2 exam definition."),
        ("SEM1", "Semester 1 Exam", "40", "University semester 1 exam definition."),
        ("SEM2", "Semester 2 Exam", "40", "University semester 2 exam definition."),
        ("MODULE", "Module Checkpoint", "40", "Online/vocational/professional module checkpoint."),
        ("FINAL", "Final Exam", "30", "Final exam definition."),
    ]
    return [
        {
            "academic_year": year,
            "term_code": code,
            "name": name,
            "weight_percent": weight,
            "notes": notes,
        }
        for code, name, weight, notes in terms
    ]


def build_term_calendar(year: str) -> list[dict[str, str]]:
    start = int(year_start(year))
    end = start + 1
    return [
        {"academic_year": year, "term_code": "TERM1", "start_date": f"{start}-04-01", "end_date": f"{start}-09-30", "notes": "School term 1 local testing calendar."},
        {"academic_year": year, "term_code": "TERM2", "start_date": f"{start}-10-01", "end_date": f"{end}-03-31", "notes": "School term 2 local testing calendar."},
        {"academic_year": year, "term_code": "SEM1", "start_date": f"{start}-07-01", "end_date": f"{start}-12-31", "notes": "University semester 1 planning calendar."},
        {"academic_year": year, "term_code": "SEM2", "start_date": f"{end}-01-01", "end_date": f"{end}-06-30", "notes": "University semester 2 planning calendar."},
        {"academic_year": year, "term_code": "MODULE", "start_date": f"{start}-04-01", "end_date": f"{end}-03-31", "notes": "Module-based program calendar."},
        {"academic_year": year, "term_code": "FINAL", "start_date": f"{end}-03-01", "end_date": f"{end}-03-31", "notes": "Final assessment window."},
    ]


def build_categories(
    year: str,
    school: dict[str, str],
    boards: list[dict[str, str]],
    mediums: list[dict[str, str]],
    grades: list[dict[str, str]],
    streams: list[dict[str, str]],
    matrix: list[dict[str, str]],
) -> list[dict[str, str]]:
    trust = school.get("trust_code", "")
    trust_name = school.get("trust_name", trust)
    school_code = school.get("school_code", "")
    school_name = school.get("school_name", school_code)
    board_map = {row["board_code"]: row for row in boards if row.get("board_code")}
    medium_map = {row["medium_code"]: row for row in mediums if row.get("medium_code")}
    grade_map = {row["grade_code"]: row for row in grades if row.get("grade_code")}
    stream_map = {row["stream_code"]: row for row in streams if row.get("stream_code")}

    rows: list[dict[str, str]] = []
    seen: set[str] = set()

    def add(code: str, parent: str, ctype: str, name: str, path: str, description: str) -> None:
        if not code or code in seen:
            return
        seen.add(code)
        rows.append({
            "category_code": code,
            "parent_category_code": parent,
            "category_type": ctype,
            "name": name,
            "idnumber": code,
            "path": path,
            "visible": "1",
            "description": description,
        })

    add(trust, "", "trust", trust_name, trust_name, "Root trust category.")
    combinations = sorted({(r["board_code"], r["medium_code"], r["grade_code"], r["stream_code"]) for r in matrix})
    for board_code in sorted({item[0] for item in combinations}):
        board = board_map.get(board_code, {})
        board_name = board.get("board_name", board_code)
        board_cat = f"{trust}_{board_code}"
        school_cat = f"{board_cat}_{school_code}"
        year_cat = f"{school_cat}_{year_token(year)}"
        add(board_cat, trust, "board", board_name, f"{trust_name} / {board_name}", "Board category.")
        add(school_cat, board_cat, "school", school_name, f"{trust_name} / {board_name} / {school_name}", "School category.")
        add(year_cat, school_cat, "academic_year", f"Academic Year {year}", f"{trust_name} / {board_name} / {school_name} / Academic Year {year}", f"Academic year {year}.")
        for medium_code in sorted({item[1] for item in combinations if item[0] == board_code}):
            medium = medium_map.get(medium_code, {})
            medium_name = medium.get("medium_name", medium_code)
            medium_cat = f"{year_cat}_{medium_code}"
            add(medium_cat, year_cat, "medium", medium_name, f"{trust_name} / {board_name} / {school_name} / Academic Year {year} / {medium_name}", f"{medium_name} category for {year}.")
            for grade_code in sorted({item[2] for item in combinations if item[0] == board_code and item[1] == medium_code}, key=lambda code: display_order(grade_map.get(code, {}))):
                grade = grade_map.get(grade_code, {})
                grade_name = grade.get("grade_name", grade_code)
                grade_cat = f"{medium_cat}_{grade_code}"
                grade_path = f"{trust_name} / {board_name} / {school_name} / Academic Year {year} / {medium_name} / {grade_name}"
                add(grade_cat, medium_cat, "grade", grade_name, grade_path, f"{grade_name} category.")
                for stream_code in sorted({item[3] for item in combinations if item[0] == board_code and item[1] == medium_code and item[2] == grade_code}):
                    stream = stream_map.get(stream_code, {})
                    stream_name = stream.get("stream_name", stream_code)
                    stream_cat = f"{grade_cat}_{stream_code}"
                    add(stream_cat, grade_cat, "stream", stream_name, f"{grade_path} / {stream_code}", f"{stream_name} stream category.")
    add("COURSE_TEMPLATES", "", "system", "Course Templates", "Course Templates", "Hidden reusable course templates.")
    return rows


def build_courses(
    year: str,
    school: dict[str, str],
    boards: list[dict[str, str]],
    mediums: list[dict[str, str]],
    grades: list[dict[str, str]],
    grade_bands: list[dict[str, str]],
    matrix: list[dict[str, str]],
) -> list[dict[str, str]]:
    school_code = school.get("school_code", "")
    school_name = school.get("school_name", school_code)
    trust = school.get("trust_code", "")
    board_map = {row["board_code"]: row for row in boards if row.get("board_code")}
    medium_map = {row["medium_code"]: row for row in mediums if row.get("medium_code")}
    grade_map = {row["grade_code"]: row for row in grades if row.get("grade_code")}
    rows: list[dict[str, str]] = []
    for item in matrix:
        board = item.get("board_code", "")
        medium = item.get("medium_code", "")
        grade = item.get("grade_code", "")
        stream = item.get("stream_code", "")
        subject = item.get("subject_code", "")
        subject_name = item.get("subject_name", subject)
        grade_row = grade_map.get(grade, {})
        band = find_grade_band(grade, stream, grade_bands)
        course_code = make_course_code(school_code, board, medium, grade, stream, subject, year)
        shortname = make_course_shortname(school_code, board, medium, grade, stream, subject, year)
        board_name = board_map.get(board, {}).get("board_name", board)
        medium_name = medium_map.get(medium, {}).get("medium_name", medium)
        grade_name = grade_row.get("grade_name", grade)
        system = education_system(grade_row).title()
        rows.append({
            "course_code": course_code,
            "fullname": f"{school_name} - {board_name} - {medium_name} - {grade_name} - {stream} - {subject_name} - {year}",
            "shortname": shortname,
            "idnumber": course_code,
            "category_code": make_stream_category(trust, board, school_code, year, medium, grade, stream),
            "board_code": board,
            "school_code": school_code,
            "medium_code": medium,
            "grade_code": grade,
            "stream_code": stream,
            "subject_code": subject,
            "subject_name": subject_name,
            "academic_year": year,
            "format": band.get("course_format", "topics"),
            "numsections": band.get("course_numsections", "16"),
            "visible": band.get("course_visible", "1"),
            "groupmode": band.get("course_groupmode", "1"),
            "groupmodeforce": band.get("course_groupmodeforce", "1"),
            "summary": f"{subject_name} course for {grade_name} {stream} {medium_name} in {year}. {system} model: {term_model_for_grade(grade_row).replace('_', ' ').title()} with completion rules and default certification.",
            "templatecourse": band.get("course_template_shortname", "MASTER-ALL-GRADES-ALL-SUBJECTS-STD-TEMPLATE"),
            "enablecompletion": band.get("course_enablecompletion", "1"),
            "showgrades": band.get("course_showgrades", "1"),
            "showreports": band.get("course_showreports", "1"),
            "tags": ",".join([school_code, board, year, medium, grade, stream, subject]),
            "course_template_code": band.get("course_template_code", "TPL_STANDARD_FALLBACK"),
            "term": f"Academic Year {year}",
        })
    return rows


def category_path_map(categories: list[dict[str, str]]) -> dict[str, str]:
    return {row.get("category_code", ""): row.get("path", "") for row in categories}


def build_courses_upload(courses: list[dict[str, str]], categories: list[dict[str, str]]) -> list[dict[str, str]]:
    paths = category_path_map(categories)
    return [
        {
            "shortname": row["shortname"],
            "fullname": row["fullname"],
            "idnumber": row["idnumber"],
            "category_idnumber": row["category_code"],
            "category_path": paths.get(row["category_code"], ""),
            "visible": row["visible"],
            "format": row["format"],
            "numsections": row["numsections"],
            "enablecompletion": row["enablecompletion"],
            "showgrades": row["showgrades"],
            "showreports": row["showreports"],
            "groupmode": row["groupmode"],
            "groupmodeforce": row["groupmodeforce"],
            "templatecourse": row["templatecourse"],
            "summary": row["summary"],
            "tags": row["tags"],
        }
        for row in courses
    ]


def build_cohorts(
    year: str,
    school: dict[str, str],
    mediums: list[dict[str, str]],
    grades: list[dict[str, str]],
    divisions: list[dict[str, str]],
    matrix: list[dict[str, str]],
) -> list[dict[str, str]]:
    trust = school.get("trust_code", "")
    school_code = school.get("school_code", "")
    medium_map = {row["medium_code"]: row for row in mediums if row.get("medium_code")}
    grade_map = {row["grade_code"]: row for row in grades if row.get("grade_code")}
    combinations = sorted({(r["board_code"], r["medium_code"], r["grade_code"], r["stream_code"]) for r in matrix})
    rows: list[dict[str, str]] = []
    for board, medium, grade, stream in combinations:
        medium_name = medium_map.get(medium, {}).get("medium_name", medium)
        grade_name = grade_map.get(grade, {}).get("grade_name", grade)
        context = make_stream_category(trust, board, school_code, year, medium, grade, stream)
        for division in divisions:
            division_code = division.get("division_code", "")
            if not division_code:
                continue
            code = make_cohort_code(school_code, year, board, medium, grade, stream, division_code)
            division_name = division.get("division_name", division_code)
            rows.append({
                "cohort_code": code,
                "name": f"{school_code} {year} {medium_name} {grade_name} {stream} {division_name}",
                "idnumber": code,
                "context_category_code": context,
                "board_code": board,
                "school_code": school_code,
                "medium_code": medium,
                "grade_code": grade,
                "stream_code": stream,
                "division_code": division_code,
                "academic_year": year,
                "visible": "1",
                "description": f"Student cohort for {grade_name} {stream} {division_name} in {year}.",
            })
    return rows


def build_groups(courses: list[dict[str, str]], divisions: list[dict[str, str]]) -> list[dict[str, str]]:
    rows = []
    for course in courses:
        for division in divisions:
            division_code = division.get("division_code", "")
            if not division_code:
                continue
            group_name = f"Division {division_code}"
            group_id = f"{course['course_code']}-{division_code}"
            rows.append({
                "course_code": course["course_code"],
                "course_shortname": course["shortname"],
                "group_name": group_name,
                "group_idnumber": group_id,
                "board_code": course["board_code"],
                "school_code": course["school_code"],
                "medium_code": course["medium_code"],
                "grade_code": course["grade_code"],
                "stream_code": course["stream_code"],
                "division_code": division_code,
                "description": f"{group_name} group for {course['shortname']}.",
            })
    return rows


def build_enrolments(year: str, courses: list[dict[str, str]], divisions: list[dict[str, str]]) -> list[dict[str, str]]:
    rows = []
    for course in courses:
        for division in divisions:
            division_code = division.get("division_code", "")
            if not division_code:
                continue
            group_name = f"Division {division_code}"
            rows.append({
                "course_code": course["course_code"],
                "course_shortname": course["shortname"],
                "cohort_code": make_cohort_code(course["school_code"], year, course["board_code"], course["medium_code"], course["grade_code"], course["stream_code"], division_code),
                "role_shortname": "student",
                "group_name": group_name,
                "group_idnumber": f"{course['course_code']}-{division_code}",
                "enrolment_method": "cohort_sync",
                "status": "active",
            })
    return rows


TEACHER_SUBJECT_SUFFIXES = {
    "ENG": "eng", "HIN": "hin", "REG_LANG": "guj", "MATH": "math", "SCI": "sci", "SST": "ss",
    "EVS": "evs", "COMP": "cs", "ART": "art", "PE": "pe", "PHY": "phy", "CHE": "chem",
    "BIO": "bio", "MATH_ADV": "math", "APP_MATH": "math", "IP": "cs", "ACC": "acc",
    "BST": "ba", "ECO": "eco", "ENT": "ba", "HIS": "hist", "GEO": "geo", "POL": "polsci",
    "PSY": "psy", "SOC": "soc",
}


def build_role_assignments(school: dict[str, str], courses: list[dict[str, str]], staff: list[dict[str, str]]) -> list[dict[str, str]]:
    school_code = school.get("school_code", "")
    trust = school.get("trust_code", "")
    rows = [
        {"username": "trustee.patel", "role_shortname": "trustee_manager", "context_type": "category", "context_identifier": trust, "notes": "Trust-level management access."},
        {"username": school.get("principal_username", "principal.sharma"), "role_shortname": "principal", "context_type": "category", "context_identifier": f"{trust}_GSEB_{school_code}", "notes": "School principal access."},
        {"username": "it.coordinator", "role_shortname": "manager", "context_type": "category", "context_identifier": f"{trust}_GSEB_{school_code}", "notes": "IT coordinator category manager access."},
    ]
    staff_usernames = {row.get("username", "") for row in staff}
    medium_fallback: dict[str, str] = {}
    for username in sorted(staff_usernames):
        parts = username.split(".")
        if len(parts) >= 4 and parts[0] == school_code.lower() and parts[1] == "tch":
            medium_fallback.setdefault(parts[2].upper(), username)
    seen = {(row["username"], row["role_shortname"], row["context_type"], row["context_identifier"]) for row in rows}
    for course in courses:
        medium = course["medium_code"].lower()
        suffix = TEACHER_SUBJECT_SUFFIXES.get(course["subject_code"], course["subject_code"].lower())
        username = f"{school_code.lower()}.tch.{medium}.{suffix}"
        if username not in staff_usernames:
            username = medium_fallback.get(course["medium_code"], "")
        if not username:
            continue
        key = (username, "editingteacher", "course", course["course_code"])
        if key in seen:
            continue
        seen.add(key)
        rows.append({
            "username": username,
            "role_shortname": "editingteacher",
            "context_type": "course",
            "context_identifier": course["course_code"],
            "notes": f"Subject teacher for {course['shortname']}.",
        })
    return rows


def build_template_application(courses: list[dict[str, str]], grade_bands: list[dict[str, str]]) -> list[dict[str, str]]:
    rows = []
    for course in courses:
        band = find_grade_band(course["grade_code"], course["stream_code"], grade_bands)
        rows.append({
            "course_shortname": course["shortname"],
            "course_code": course["course_code"],
            "course_fullname": course["fullname"],
            "template_code": band.get("course_template_code", "TPL_STANDARD_FALLBACK"),
            "templatecourse_shortname": band.get("course_template_shortname", "MASTER-ALL-GRADES-ALL-SUBJECTS-STD-TEMPLATE"),
            "academic_year": course["academic_year"],
            "term": course["term"],
            "grade_code": course["grade_code"],
            "grade_band": band.get("grade_band", "ALL"),
            "subject_code": course["subject_code"],
            "stream_code": course["stream_code"],
            "visible_after_creation": band.get("visible_after_creation", "1"),
            "enablecompletion": band.get("course_enablecompletion", "1"),
            "apply_sections": band.get("apply_sections", "1"),
            "apply_gradebook": band.get("apply_gradebook", "1"),
            "apply_completion_defaults": band.get("apply_completion_defaults", "1"),
            "certificate_policy_code": band.get("template_policy_code", "STANDARD_COURSE_COMPLETION_CERT"),
            "notes": "Certification enabled by default. Visible by default for demo and student dashboard access.",
        })
    return rows


def build_course_certificates(year: str, school: dict[str, str], boards: list[dict[str, str]], mediums: list[dict[str, str]], grades: list[dict[str, str]], courses: list[dict[str, str]], grade_bands: list[dict[str, str]]) -> list[dict[str, str]]:
    board_map = {row["board_code"]: row for row in boards if row.get("board_code")}
    medium_map = {row["medium_code"]: row for row in mediums if row.get("medium_code")}
    grade_map = {row["grade_code"]: row for row in grades if row.get("grade_code")}
    rows = []
    for course in courses:
        band = find_grade_band(course["grade_code"], course["stream_code"], grade_bands)
        rows.append({
            "academic_year": year,
            "course_code": course["course_code"],
            "course_shortname": course["shortname"],
            "certificate_enabled": band.get("certificate_enabled", "1"),
            "credential_type": band.get("credential_type", "certificate"),
            "certificate_policy_code": band.get("certificate_policy_code", "STANDARD_COURSE_COMPLETION_CERT"),
            "requires_plugin": band.get("requires_plugin", "1"),
            "issue_condition": band.get("issue_condition", "course_completion"),
            "min_completion_percent": band.get("min_completion_percent", "100"),
            "min_grade_percent": band.get("min_grade_percent", "40"),
            "expiry_enabled": band.get("expiry_enabled", "0"),
            "expiry_months": band.get("expiry_months", ""),
            "visible_to_student": band.get("visible_to_student", "1"),
            "certificate_template_code": band.get("certificate_template_code", "DRONA_MODERN_COURSE_COMPLETION"),
            "certificate_template_name": band.get("certificate_template_name", "Drona Modern Course Completion Certificate"),
            "certificate_activity_type": band.get("certificate_activity_type", "customcert"),
            "certificate_activity_key": band.get("certificate_activity_key", "course_completion_certificate"),
            "certificate_activity_name": band.get("certificate_activity_name", "Download Course Completion Certificate"),
            "certificate_section_number": band.get("certificate_section_number", "15"),
            "certificate_section_name": band.get("certificate_section_name", "Certificate & Completion"),
            "certificate_download_mode": band.get("certificate_download_mode", "D"),
            "certificate_verification_enabled": band.get("certificate_verification_enabled", "1"),
            "certificate_email_students": band.get("certificate_email_students", "0"),
            "certificate_required_minutes": band.get("certificate_required_minutes", "0"),
            "certificate_unlock_activity_key": band.get("certificate_unlock_activity_key", "final_project_portfolio"),
            "certificate_filename_pattern": band.get("certificate_filename_pattern", "{COURSE_SHORT_NAME}-{FIRST_NAME}-{LAST_NAME}-{ISSUE_DATE}"),
            "certificate_brand_primary": band.get("certificate_brand_primary", "#0B4F71"),
            "certificate_brand_accent": band.get("certificate_brand_accent", "#F2B705"),
            "certificate_brand_highlight": band.get("certificate_brand_highlight", "#E51B23"),
            "school_name": school.get("school_name", ""),
            "board_name": board_map.get(course["board_code"], {}).get("board_name", course["board_code"]),
            "principal_name": "Anita Sharma",
            "medium_code": course["medium_code"],
            "medium_name": medium_map.get(course["medium_code"], {}).get("medium_name", course["medium_code"]),
            "grade_code": course["grade_code"],
            "grade_name": grade_map.get(course["grade_code"], {}).get("grade_name", course["grade_code"]),
            "stream_code": course["stream_code"],
            "subject_code": course["subject_code"],
            "subject_name": course["subject_name"],
            "notes": band.get("certificate_notes", ""),
        })
    return rows


def build_assessment_plan(year: str, courses: list[dict[str, str]], grade_bands: list[dict[str, str]]) -> list[dict[str, str]]:
    rows = []
    for course in courses:
        band = find_grade_band(course["grade_code"], course["stream_code"], grade_bands)
        rows.append({
            "academic_year": year,
            "grade_code": course["grade_code"],
            "stream_code": course["stream_code"],
            "subject_code": course["subject_code"],
            "course_code": course["course_code"],
            "classwork_weight": band.get("classwork_weight", "20"),
            "assignment_weight": band.get("assignment_weight", "25"),
            "chapter_gate_weight": band.get("chapter_gate_weight", "15"),
            "term_exam_weight": band.get("final_weight", "20"),
            "final_exam_weight": band.get("final_weight", "20"),
            "passing_percent": band.get("passing_grade_percent", "40"),
        })
    return rows


def build_attendance_policy(year: str, grades: list[dict[str, str]], grade_bands: list[dict[str, str]]) -> list[dict[str, str]]:
    rows = []
    for grade in grades:
        code = grade.get("grade_code", "")
        if not code:
            continue
        band = find_grade_band(code, school_stream_for_grade(code), grade_bands)
        rows.append({
            "academic_year": year,
            "grade_code": code,
            "minimum_attendance_percent": band.get("minimum_attendance_percent", "75"),
            "notes": band.get("attendance_notes", "Generated attendance policy from grade-band setup."),
        })
    return rows


def build_course_final_exams(year: str, courses: list[dict[str, str]]) -> list[dict[str, str]]:
    return [
        {
            "academic_year": year,
            "course_code": course["course_code"],
            "course_shortname": course["shortname"],
            "final_exam_template_code": f"FINAL_EXAM_{course['grade_code']}",
            "enabled": "1",
            "required_for_completion": "1",
            "notes": "Generated grade-wise final exam assignment.",
        }
        for course in courses
    ]


def build_course_term_exams(year: str, courses: list[dict[str, str]], grades: list[dict[str, str]]) -> list[dict[str, str]]:
    grade_map = {row["grade_code"]: row for row in grades if row.get("grade_code")}
    rows = []
    for course in courses:
        grade = grade_map.get(course["grade_code"], {})
        for term_code in term_codes_for_grade(grade):
            rows.append({
                "academic_year": year,
                "course_code": course["course_code"],
                "course_shortname": course["shortname"],
                "term_code": term_code,
                "term_exam_template_code": f"TERM_EXAM_{course['grade_code']}_{term_code}",
                "enabled": "1",
                "required_for_completion": "1",
                "notes": "Generated grade/program term or module exam assignment.",
            })
    return rows


def build_gradebook_weights(year: str, courses: list[dict[str, str]], grade_bands: list[dict[str, str]]) -> list[dict[str, str]]:
    rows = []
    for course in courses:
        band = find_grade_band(course["grade_code"], course["stream_code"], grade_bands)
        rows.append({
            "academic_year": year,
            "course_code": course["course_code"],
            "course_shortname": course["shortname"],
            "classwork_weight": band.get("classwork_weight", "20"),
            "practice_quiz_weight": band.get("practice_quiz_weight", "20"),
            "assignment_weight": band.get("assignment_weight", "25"),
            "chapter_gate_weight": band.get("chapter_gate_weight", "15"),
            "term_exam_weight": band.get("final_weight", "20"),
            "final_exam_weight": band.get("final_weight", "20"),
            "passing_percent": band.get("passing_grade_percent", "40"),
        })
    return rows


def main() -> None:
    parser = argparse.ArgumentParser(description="Sync standard school/university master reference CSV sheets.")
    parser.add_argument("--year", default="2026-2027")
    parser.add_argument("--root", default=str(PACK_ROOT))
    args = parser.parse_args()

    root = Path(args.root)
    master = root / "master"
    operations = root / "operations"
    year_dir = root / "years" / args.year

    school = read_rows(master / "school.csv")[0]
    years = read_rows(master / "academic_years.csv")
    boards = read_rows(master / "boards.csv")
    mediums = read_rows(master / "mediums.csv")
    grades = read_rows(master / "grades.csv")
    streams = read_rows(master / "streams.csv")
    divisions = read_rows(master / "divisions.csv")
    subjects = patch_subject_scopes(read_rows(master / "subjects.csv"))
    statuses = read_rows(operations / "promotion_status_codes.csv")
    grade_bands = read_rows(root / "templates" / "legacy" / "34_grade_band_template_adjustments.csv")
    staff = read_rows(root / "registration" / "combined" / "19_users_staff.csv")
    students = normalize_student_rows(read_rows(root / "registration" / "combined" / "20_users_students.csv"))
    matrix = read_rows(year_dir / "grade_subject_matrix.csv")

    if students:
        write_rows(root / "registration" / "combined" / "20_users_students.csv", list(students[0].keys()), students)

    write_rows(
        master / "subjects.csv",
        [
            "subject_code",
            "subject_name",
            "subject_category",
            "applies_to",
            "matrix_is_compulsory",
            "matrix_is_elective",
            "matrix_display_order",
            "matrix_source_note",
            "notes",
        ],
        subjects,
    )
    write_rows(
        master / "lookup_values.csv",
        ["lookup_type", "code", "label"],
        build_lookup_values(years, boards, mediums, grades, streams, divisions, subjects, statuses),
    )
    write_rows(
        root / "templates" / "legacy" / "35_subject_template_adjustments.csv",
        ["subject_area", "subject_codes", "recommended_template_additions", "default_extra_sections"],
        build_subject_adjustments(subjects),
    )
    write_rows(
        operations / "academic_year_promotion_rules.csv",
        [
            "rule_code",
            "from_grade_code",
            "from_stream_code",
            "result_status",
            "promotion_decision",
            "to_grade_code",
            "to_stream_code",
            "requires_manual_review",
            "notes",
        ],
        build_promotion_rules(grades, streams),
    )
    write_rows(
        year_dir / "diksha_content_template.csv",
        [
            "board_code",
            "medium_code",
            "grade_code",
            "stream_code",
            "subject_code",
            "chapter",
            "title",
            "diksha_identifier",
            "content_type",
            "resource_type",
            "language",
            "license",
            "attribution",
            "source_url",
            "artifact_url",
            "download_url",
            "moodle_course_code",
            "moodle_section",
            "import_mode",
            "status",
        ],
        build_content_template(args.year, school, boards, mediums, grades, matrix),
    )

    categories = build_categories(args.year, school, boards, mediums, grades, streams, matrix)
    courses = build_courses(args.year, school, boards, mediums, grades, grade_bands, matrix)
    write_rows(
        year_dir / "categories.csv",
        ["category_code", "parent_category_code", "category_type", "name", "idnumber", "path", "visible", "description"],
        categories,
    )
    write_rows(
        year_dir / "courses.csv",
        [
            "course_code", "fullname", "shortname", "idnumber", "category_code", "board_code", "school_code",
            "medium_code", "grade_code", "stream_code", "subject_code", "subject_name", "academic_year", "format",
            "numsections", "visible", "groupmode", "groupmodeforce", "summary", "templatecourse", "enablecompletion",
            "showgrades", "showreports", "tags", "course_template_code", "term",
        ],
        courses,
    )
    write_rows(
        year_dir / "courses_with_templatecourse_for_moodle_upload.csv",
        [
            "shortname", "fullname", "idnumber", "category_idnumber", "category_path", "visible", "format",
            "numsections", "enablecompletion", "showgrades", "showreports", "groupmode", "groupmodeforce",
            "templatecourse", "summary", "tags",
        ],
        build_courses_upload(courses, categories),
    )
    write_rows(
        year_dir / "cohorts.csv",
        [
            "cohort_code", "name", "idnumber", "context_category_code", "board_code", "school_code",
            "medium_code", "grade_code", "stream_code", "division_code", "academic_year", "visible", "description",
        ],
        build_cohorts(args.year, school, mediums, grades, divisions, matrix),
    )
    write_rows(
        year_dir / "groups.csv",
        [
            "course_code", "course_shortname", "group_name", "group_idnumber", "board_code", "school_code",
            "medium_code", "grade_code", "stream_code", "division_code", "description",
        ],
        build_groups(courses, divisions),
    )
    write_rows(
        year_dir / "enrolments.csv",
        ["course_code", "course_shortname", "cohort_code", "role_shortname", "group_name", "group_idnumber", "enrolment_method", "status"],
        build_enrolments(args.year, courses, divisions),
    )
    write_rows(
        year_dir / "role_assignments.csv",
        ["username", "role_shortname", "context_type", "context_identifier", "notes"],
        build_role_assignments(school, courses, staff),
    )
    write_rows(
        year_dir / "cohort_members.csv",
        ["username", "cohort_code", "role"],
        build_cohort_members(students),
    )
    write_rows(
        year_dir / "course_template_application.csv",
        [
            "course_shortname", "course_code", "course_fullname", "template_code", "templatecourse_shortname",
            "academic_year", "term", "grade_code", "grade_band", "subject_code", "stream_code", "visible_after_creation",
            "enablecompletion", "apply_sections", "apply_gradebook", "apply_completion_defaults", "certificate_policy_code", "notes",
        ],
        build_template_application(courses, grade_bands),
    )
    write_rows(
        year_dir / "course_certificates.csv",
        [
            "academic_year", "course_code", "course_shortname", "certificate_enabled", "credential_type",
            "certificate_policy_code", "requires_plugin", "issue_condition", "min_completion_percent", "min_grade_percent",
            "expiry_enabled", "expiry_months", "visible_to_student", "certificate_template_code", "certificate_template_name",
            "certificate_activity_type", "certificate_activity_key", "certificate_activity_name", "certificate_section_number",
            "certificate_section_name", "certificate_download_mode", "certificate_verification_enabled", "certificate_email_students",
            "certificate_required_minutes", "certificate_unlock_activity_key", "certificate_filename_pattern", "certificate_brand_primary",
            "certificate_brand_accent", "certificate_brand_highlight", "school_name", "board_name", "principal_name", "medium_code",
            "medium_name", "grade_code", "grade_name", "stream_code", "subject_code", "subject_name", "notes",
        ],
        build_course_certificates(args.year, school, boards, mediums, grades, courses, grade_bands),
    )
    write_rows(
        year_dir / "exam_terms.csv",
        ["academic_year", "term_code", "name", "weight_percent", "notes"],
        build_exam_terms(args.year),
    )
    write_rows(
        year_dir / "term_calendar.csv",
        ["academic_year", "term_code", "start_date", "end_date", "notes"],
        build_term_calendar(args.year),
    )
    write_rows(
        year_dir / "course_final_exams.csv",
        ["academic_year", "course_code", "course_shortname", "final_exam_template_code", "enabled", "required_for_completion", "notes"],
        build_course_final_exams(args.year, courses),
    )
    write_rows(
        year_dir / "course_term_exams.csv",
        ["academic_year", "course_code", "course_shortname", "term_code", "term_exam_template_code", "enabled", "required_for_completion", "notes"],
        build_course_term_exams(args.year, courses, grades),
    )
    write_rows(
        year_dir / "assessment_plan.csv",
        [
            "academic_year", "grade_code", "stream_code", "subject_code", "course_code", "classwork_weight",
            "assignment_weight", "chapter_gate_weight", "term_exam_weight", "final_exam_weight", "passing_percent",
        ],
        build_assessment_plan(args.year, courses, grade_bands),
    )
    write_rows(
        year_dir / "attendance_policy.csv",
        ["academic_year", "grade_code", "minimum_attendance_percent", "notes"],
        build_attendance_policy(args.year, grades, grade_bands),
    )
    write_rows(
        year_dir / "gradebook_weights.csv",
        [
            "academic_year", "course_code", "course_shortname", "classwork_weight", "practice_quiz_weight",
            "assignment_weight", "chapter_gate_weight", "term_exam_weight", "final_exam_weight", "passing_percent",
        ],
        build_gradebook_weights(args.year, courses, grade_bands),
    )

    print("Synced standard master reference dataset")
    print(f"- subjects: {len(subjects)}")
    print(f"- lookup values: {len(build_lookup_values(years, boards, mediums, grades, streams, divisions, subjects, statuses))}")
    print(f"- subject adjustments: {len(build_subject_adjustments(subjects))}")
    print(f"- promotion rules: {len(build_promotion_rules(grades, streams))}")
    print(f"- content template rows: {len(matrix)}")
    print(f"- courses: {len(courses)}")
    print(f"- course groups: {len(courses) * len(divisions)}")


if __name__ == "__main__":
    main()
