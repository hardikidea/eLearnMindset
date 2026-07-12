#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
import hashlib
import json
from collections import Counter, defaultdict
from datetime import datetime, timezone
from pathlib import Path


PACK = Path(__file__).resolve().parents[1]


def read_rows(path: Path) -> list[dict[str, str]]:
    if not path.exists():
        return []
    with path.open(newline="") as handle:
        return list(csv.DictReader(handle))


def count_rows(path: Path) -> int:
    if not path.exists():
        return 0
    with path.open(newline="") as handle:
        reader = csv.reader(handle)
        next(reader, None)
        return sum(1 for _ in reader)


def csv_files(path: Path) -> list[Path]:
    if not path.exists():
        return []
    return sorted(file for file in path.iterdir() if file.suffix.lower() == ".csv")


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def code_set(rows: list[dict[str, str]], field: str) -> set[str]:
    return {row.get(field, "") for row in rows if row.get(field, "")}


def row_map(rows: list[dict[str, str]], field: str) -> dict[str, dict[str, str]]:
    return {row.get(field, ""): row for row in rows if row.get(field, "")}


def add_issue(issues: list[dict[str, str]], severity: str, area: str, message: str) -> None:
    issues.append({"severity": severity, "area": area, "message": message})


def required_files(year: str) -> list[Path]:
    year_dir = PACK / "years" / year
    return [
        PACK / "master" / "school.csv",
        PACK / "master" / "divisions.csv",
        PACK / "master" / "roles.csv",
        PACK / "registration" / "combined" / "19_users_staff.csv",
        PACK / "registration" / "combined" / "20_users_students.csv",
        PACK / "registration" / "combined" / "21_users_parents.csv",
        PACK / "registration" / "parent_links.csv",
        year_dir / "categories.csv",
        year_dir / "courses.csv",
        year_dir / "cohorts.csv",
        year_dir / "groups.csv",
        year_dir / "cohort_members.csv",
        year_dir / "role_assignments.csv",
        year_dir / "enrolments.csv",
        year_dir / "course_certificates.csv",
        year_dir / "course_term_exams.csv",
        year_dir / "course_final_exams.csv",
        year_dir / "exam_terms.csv",
        year_dir / "gradebook_weights.csv",
        year_dir / "attendance_policy.csv",
    ]


def build_manifest(year: str, assembled: Path) -> dict[str, object]:
    files = []
    for path in csv_files(assembled):
        files.append(
            {
                "file": str(path.relative_to(PACK)),
                "rows": count_rows(path),
                "sha256": sha256(path),
            }
        )
    return {
        "pack": "school_master_pack",
        "academic_year": year,
        "generated_at": datetime.now(timezone.utc).isoformat(timespec="seconds"),
        "assembled_dir": str(assembled.relative_to(PACK)) if assembled.exists() else str(assembled),
        "files": files,
    }


def check_relationships(year: str) -> tuple[list[dict[str, str]], dict[str, int], dict[str, object]]:
    issues: list[dict[str, str]] = []
    metrics: dict[str, int] = {}
    facts: dict[str, object] = {}
    year_dir = PACK / "years" / year
    assembled = PACK / "build" / "assembled_csv" / year

    for path in required_files(year):
        if not path.exists():
            add_issue(issues, "ERROR", "files", f"Missing required file: {path.relative_to(PACK)}")

    students = read_rows(PACK / "registration" / "combined" / "20_users_students.csv")
    parents = read_rows(PACK / "registration" / "combined" / "21_users_parents.csv")
    staff = read_rows(PACK / "registration" / "combined" / "19_users_staff.csv")
    parent_links = read_rows(PACK / "registration" / "parent_links.csv")
    roles = read_rows(PACK / "master" / "roles.csv")
    divisions = read_rows(PACK / "master" / "divisions.csv")

    categories = read_rows(year_dir / "categories.csv")
    courses = read_rows(year_dir / "courses.csv")
    cohorts = read_rows(year_dir / "cohorts.csv")
    groups = read_rows(year_dir / "groups.csv")
    cohort_members = read_rows(year_dir / "cohort_members.csv")
    role_assignments = read_rows(year_dir / "role_assignments.csv")
    enrolments = read_rows(year_dir / "enrolments.csv")
    certs = read_rows(year_dir / "course_certificates.csv")
    term_exams = read_rows(year_dir / "course_term_exams.csv")
    final_exams = read_rows(year_dir / "course_final_exams.csv")
    exam_terms = read_rows(year_dir / "exam_terms.csv")
    gradebook = read_rows(year_dir / "gradebook_weights.csv")
    attendance = read_rows(year_dir / "attendance_policy.csv")

    metrics.update(
        {
            "students": len(students),
            "parents": len(parents),
            "staff": len(staff),
            "parent_links": len(parent_links),
            "categories": len(categories),
            "courses": len(courses),
            "cohorts": len(cohorts),
            "groups": len(groups),
            "cohort_members": len(cohort_members),
            "role_assignments": len(role_assignments),
            "enrolments": len(enrolments),
            "certificates": len(certs),
            "term_exam_rows": len(term_exams),
            "final_exam_rows": len(final_exams),
            "gradebook_rows": len(gradebook),
            "attendance_rows": len(attendance),
            "assembled_csv_files": len(csv_files(assembled)),
        }
    )

    student_users = code_set(students, "username")
    parent_users = code_set(parents, "username")
    staff_users = code_set(staff, "username")
    all_users = student_users | parent_users | staff_users
    roles_defined = code_set(roles, "role_shortname") | {
        "student",
        "parent",
        "editingteacher",
        "teacher",
        "manager",
        "principal",
        "trustee_manager",
    }
    courses_by_code = row_map(courses, "course_code")
    course_shortnames = code_set(courses, "shortname")
    course_codes = set(courses_by_code)
    category_codes = code_set(categories, "category_code") | code_set(categories, "idnumber")
    cohort_codes = code_set(cohorts, "cohort_code")
    group_ids = code_set(groups, "group_idnumber")
    division_codes = code_set(divisions, "division_code")
    grades_used = code_set(courses, "grade_code")

    all_cohort_codes = set(cohort_codes)
    for cohort_file in (PACK / "years").glob("*/cohorts.csv"):
        all_cohort_codes.update(code_set(read_rows(cohort_file), "cohort_code"))

    for row in parent_links:
        if row.get("parent_username") not in parent_users:
            add_issue(issues, "ERROR", "parent links", f"Missing parent user: {row.get('parent_username')}")
        if row.get("student_username") not in student_users:
            add_issue(issues, "ERROR", "parent links", f"Missing student user: {row.get('student_username')}")
        if row.get("role_shortname") and row.get("role_shortname") not in roles_defined:
            add_issue(issues, "ERROR", "parent links", f"Unknown parent role: {row.get('role_shortname')}")

    for row in students:
        cohort = row.get("cohort1", "")
        if cohort and cohort not in all_cohort_codes:
            add_issue(issues, "ERROR", "students", f"Student {row.get('username')} references missing cohort1={cohort}")

    for row in cohort_members:
        if row.get("username") not in all_users:
            add_issue(issues, "ERROR", "cohort members", f"Missing user: {row.get('username')}")
        if row.get("cohort_code") not in cohort_codes:
            add_issue(issues, "ERROR", "cohort members", f"Missing cohort: {row.get('cohort_code')}")

    for row in categories:
        parent = row.get("parent_category_code", "")
        if parent and parent not in category_codes:
            add_issue(issues, "ERROR", "categories", f"Category {row.get('category_code')} references missing parent {parent}")

    for row in groups:
        course_code = row.get("course_code", "")
        group_id = row.get("group_idnumber", "")
        division = row.get("division_code", "")
        course = courses_by_code.get(course_code)
        if not course:
            add_issue(issues, "ERROR", "groups", f"Group {group_id} references missing course {course_code}")
        elif row.get("course_shortname") != course.get("shortname"):
            add_issue(issues, "ERROR", "groups", f"Group {group_id} has wrong course_shortname")
        if division not in division_codes:
            add_issue(issues, "ERROR", "groups", f"Group {group_id} references missing division {division}")

    for row in enrolments:
        if row.get("course_code") not in course_codes:
            add_issue(issues, "ERROR", "enrolments", f"Missing course: {row.get('course_code')}")
        if row.get("cohort_code") not in cohort_codes:
            add_issue(issues, "ERROR", "enrolments", f"Missing cohort: {row.get('cohort_code')}")
        if row.get("group_idnumber") not in group_ids:
            add_issue(issues, "ERROR", "enrolments", f"Missing group: {row.get('group_idnumber')}")
        if row.get("role_shortname") not in roles_defined:
            add_issue(issues, "ERROR", "enrolments", f"Unknown role: {row.get('role_shortname')}")

    if division_codes and groups:
        expected_groups = len(course_codes) * len(division_codes)
        facts["expected_groups"] = expected_groups
        if len(groups) != expected_groups:
            add_issue(issues, "WARN", "groups", f"Expected {expected_groups} groups from course x division matrix; found {len(groups)}")

    if groups and len(enrolments) != len(groups):
        add_issue(issues, "WARN", "enrolments", f"Expected one cohort enrolment per group; groups={len(groups)} enrolments={len(enrolments)}")

    cert_course_codes = {row.get("course_code", "") for row in certs if row.get("certificate_enabled") == "1"}
    if cert_course_codes != course_codes:
        add_issue(
            issues,
            "ERROR",
            "certificates",
            f"Enabled certificate course coverage mismatch. missing={len(course_codes - cert_course_codes)} extra={len(cert_course_codes - course_codes)}",
        )

    active_terms = {row.get("term_code", "") for row in exam_terms if row.get("term_code") and row.get("term_code") != "FINAL"}
    if not active_terms:
        active_terms = {"TERM1", "TERM2"}
    term_by_course: dict[str, set[str]] = defaultdict(set)
    for row in term_exams:
        if row.get("enabled") == "1":
            term_by_course[row.get("course_code", "")].add(row.get("term_code", ""))
    missing_term_courses = [course for course in course_codes if term_by_course.get(course) != active_terms]
    if missing_term_courses:
        add_issue(issues, "ERROR", "term exams", f"{len(missing_term_courses)} course(s) do not have exactly {sorted(active_terms)}")

    final_course_codes = {row.get("course_code", "") for row in final_exams if row.get("enabled") == "1"}
    if final_course_codes != course_codes:
        add_issue(
            issues,
            "ERROR",
            "final exams",
            f"Final exam course coverage mismatch. missing={len(course_codes - final_course_codes)} extra={len(final_course_codes - course_codes)}",
        )

    gradebook_course_codes = code_set(gradebook, "course_code")
    if gradebook_course_codes != course_codes:
        add_issue(
            issues,
            "ERROR",
            "gradebook",
            f"Gradebook course coverage mismatch. missing={len(course_codes - gradebook_course_codes)} extra={len(gradebook_course_codes - course_codes)}",
        )

    attendance_grades = code_set(attendance, "grade_code")
    if grades_used - attendance_grades:
        add_issue(issues, "WARN", "attendance", f"Missing attendance policy for grades: {', '.join(sorted(grades_used - attendance_grades))}")

    for row in role_assignments:
        username = row.get("username", "")
        role = row.get("role_shortname", "")
        context_type = row.get("context_type", "")
        context_identifier = row.get("context_identifier", "")
        if username not in all_users:
            add_issue(issues, "ERROR", "role assignments", f"Missing user: {username}")
        if role not in roles_defined:
            add_issue(issues, "ERROR", "role assignments", f"Unknown role: {role}")
        if context_type == "course" and context_identifier not in course_codes and context_identifier not in course_shortnames:
            add_issue(issues, "ERROR", "role assignments", f"Missing course context: {context_identifier}")
        if context_type == "category" and context_identifier not in category_codes:
            add_issue(issues, "ERROR", "role assignments", f"Missing category context: {context_identifier}")

    severities = Counter(issue["severity"] for issue in issues)
    facts["errors"] = severities.get("ERROR", 0)
    facts["warnings"] = severities.get("WARN", 0)
    facts["active_terms"] = sorted(active_terms)
    return issues, metrics, facts


def render_report(year: str, metrics: dict[str, int], facts: dict[str, object], issues: list[dict[str, str]], manifest: dict[str, object]) -> str:
    result = "PASSED" if not any(issue["severity"] == "ERROR" for issue in issues) else "FAILED"
    lines = [
        f"# School Master Pack Preflight Report - {year}",
        "",
        f"Result: **{result}**",
        f"Generated at: `{manifest['generated_at']}`",
        "",
        "## Import Scope",
        "",
        "| Item | Count |",
        "|---|---:|",
    ]
    for key in sorted(metrics):
        lines.append(f"| {key.replace('_', ' ').title()} | {metrics[key]} |")

    lines.extend(
        [
            "",
            "## Relationship Checks",
            "",
            f"- Expected groups from course x division matrix: `{facts.get('expected_groups', 'n/a')}`",
            f"- Active term exam codes: `{', '.join(facts.get('active_terms', []))}`",
            f"- Blocking errors: `{facts.get('errors', 0)}`",
            f"- Warnings: `{facts.get('warnings', 0)}`",
            "",
            "## Issues",
            "",
        ]
    )
    if issues:
        lines.extend(["| Severity | Area | Message |", "|---|---|---|"])
        for issue in issues:
            message = issue["message"].replace("|", "\\|")
            lines.append(f"| {issue['severity']} | {issue['area']} | {message} |")
    else:
        lines.append("No relationship issues found.")

    lines.extend(
        [
            "",
            "## Import Manifest",
            "",
            "| File | Rows | SHA-256 |",
            "|---|---:|---|",
        ]
    )
    for fileinfo in manifest["files"]:
        lines.append(f"| `{fileinfo['file']}` | {fileinfo['rows']} | `{fileinfo['sha256']}` |")
    lines.append("")
    return "\n".join(lines)


def main() -> None:
    parser = argparse.ArgumentParser(description="Generate a preflight report and manifest for a school master-pack import.")
    parser.add_argument("--year", default="2026-2027")
    parser.add_argument("--output-dir", default=None, help="Defaults to build/reports/<year>.")
    parser.add_argument("--no-write", action="store_true", help="Validate and print summary without writing report files.")
    args = parser.parse_args()

    assembled = PACK / "build" / "assembled_csv" / args.year
    if not assembled.exists():
        raise SystemExit(f"Missing assembled CSV directory. Run: python3 scripts/assemble.py --year {args.year}")

    issues, metrics, facts = check_relationships(args.year)
    manifest = build_manifest(args.year, assembled)
    report = render_report(args.year, metrics, facts, issues, manifest)

    output_dir = Path(args.output_dir).resolve() if args.output_dir else PACK / "build" / "reports" / args.year
    if not args.no_write:
        output_dir.mkdir(parents=True, exist_ok=True)
        (output_dir / "import_manifest.json").write_text(json.dumps(manifest, indent=2) + "\n")
        (output_dir / "preflight_report.md").write_text(report)

    errors = facts.get("errors", 0)
    warnings = facts.get("warnings", 0)
    print(f"Preflight report {'passed' if errors == 0 else 'failed'} for {args.year}.")
    print(f"Errors: {errors}")
    print(f"Warnings: {warnings}")
    if not args.no_write:
        print(f"Report: {output_dir / 'preflight_report.md'}")
        print(f"Manifest: {output_dir / 'import_manifest.json'}")
    if errors:
        raise SystemExit(1)


if __name__ == "__main__":
    main()
