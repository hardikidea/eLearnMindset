#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
import html
import sys
from pathlib import Path

from common import read_rows


def code_set(rows: list[dict[str, str]], field: str) -> set[str]:
    return {row.get(field, "") for row in rows if row.get(field, "")}


def row_map(rows: list[dict[str, str]], field: str) -> dict[str, dict[str, str]]:
    return {row.get(field, ""): row for row in rows if row.get(field, "")}


def add_issue(issues: list[dict[str, str]], severity: str, area: str, message: str, reference: str = "") -> None:
    issues.append({"severity": severity, "area": area, "message": message, "reference": reference})


def load_dataset(source: Path, year: str) -> dict[str, list[dict[str, str]]]:
    ydir = source / "years" / year
    return {
        "school": read_rows(source / "master" / "school.csv"),
        "boards": read_rows(source / "master" / "boards.csv"),
        "mediums": read_rows(source / "master" / "mediums.csv"),
        "grades": read_rows(source / "master" / "grades.csv"),
        "streams": read_rows(source / "master" / "streams.csv"),
        "subjects": read_rows(source / "master" / "subjects.csv"),
        "categories": read_rows(ydir / "categories.csv"),
        "courses": read_rows(ydir / "courses.csv"),
        "cohorts": read_rows(ydir / "cohorts.csv"),
        "groups": read_rows(ydir / "groups.csv"),
        "cohort_members": read_rows(ydir / "cohort_members.csv"),
        "role_assignments": read_rows(ydir / "role_assignments.csv"),
        "parent_links": read_rows(source / "registration" / "parent_links.csv"),
        "enrolments": read_rows(ydir / "enrolments.csv"),
        "certificates": read_rows(ydir / "course_certificates.csv"),
        "students": read_rows(source / "registration" / "combined" / "20_users_students.csv"),
        "parents": read_rows(source / "registration" / "combined" / "21_users_parents.csv"),
        "staff": read_rows(source / "registration" / "combined" / "19_users_staff.csv"),
    }


def relationship_issues(data: dict[str, list[dict[str, str]]]) -> list[dict[str, str]]:
    issues: list[dict[str, str]] = []
    students = code_set(data["students"], "username")
    parents = code_set(data["parents"], "username")
    staff = code_set(data["staff"], "username")
    teachers = {username for username in staff if ".tch." in username}
    courses = code_set(data["courses"], "course_code")
    cohorts = code_set(data["cohorts"], "cohort_code")
    groups = code_set(data["groups"], "group_idnumber")

    linked_students = code_set(data["parent_links"], "student_username")
    linked_parents = code_set(data["parent_links"], "parent_username")
    for username in sorted(students - linked_students):
        add_issue(issues, "WARN", "parent_links", "Student has no parent link.", username)
    for username in sorted(parents - linked_parents):
        add_issue(issues, "WARN", "parent_links", "Parent has no linked student.", username)
    for row in data["parent_links"]:
        if row.get("parent_username") not in parents:
            add_issue(issues, "ERROR", "parent_links", "Parent link references missing parent.", row.get("parent_username", ""))
        if row.get("student_username") not in students:
            add_issue(issues, "ERROR", "parent_links", "Parent link references missing student.", row.get("student_username", ""))

    course_role_assignments = {
        row.get("context_identifier", "")
        for row in data["role_assignments"]
        if row.get("context_type") == "course" and row.get("role_shortname") in {"teacher", "editingteacher"}
    }
    assigned_teachers = {
        row.get("username", "")
        for row in data["role_assignments"]
        if row.get("context_type") == "course" and row.get("role_shortname") in {"teacher", "editingteacher"}
    }
    for username in sorted(teachers - assigned_teachers):
        add_issue(issues, "WARN", "role_assignments", "Teacher has no course assignment.", username)
    for course in sorted(courses - course_role_assignments):
        add_issue(issues, "WARN", "role_assignments", "Course has no teacher role assignment.", course)

    enrolment_courses = code_set(data["enrolments"], "course_code")
    enrolment_cohorts = code_set(data["enrolments"], "cohort_code")
    enrolment_groups = code_set(data["enrolments"], "group_idnumber")
    member_cohorts = code_set(data["cohort_members"], "cohort_code")
    for course in sorted(courses - enrolment_courses):
        add_issue(issues, "WARN", "enrolments", "Course has no cohort-sync enrolment row.", course)
    for cohort in sorted(cohorts - member_cohorts):
        add_issue(issues, "WARN", "cohort_members", "Cohort has no student members.", cohort)
    for row in data["enrolments"]:
        if row.get("course_code") not in courses:
            add_issue(issues, "ERROR", "enrolments", "Enrolment references missing course.", row.get("course_code", ""))
        if row.get("cohort_code") not in cohorts:
            add_issue(issues, "ERROR", "enrolments", "Enrolment references missing cohort.", row.get("cohort_code", ""))
        if row.get("group_idnumber") and row.get("group_idnumber") not in groups:
            add_issue(issues, "ERROR", "enrolments", "Enrolment references missing group.", row.get("group_idnumber", ""))
    for group in sorted(groups - enrolment_groups):
        add_issue(issues, "WARN", "enrolments", "Group has no enrolment mapping.", group)
    for cohort in sorted(cohorts - enrolment_cohorts):
        add_issue(issues, "WARN", "enrolments", "Cohort has no enrolment mapping.", cohort)

    return issues


def summary_rows(data: dict[str, list[dict[str, str]]]) -> list[tuple[str, int]]:
    return [
        ("schools", len(data["school"])),
        ("boards", len(data["boards"])),
        ("mediums", len(data["mediums"])),
        ("grades", len(data["grades"])),
        ("streams", len(data["streams"])),
        ("subjects", len(data["subjects"])),
        ("categories", len(data["categories"])),
        ("courses", len(data["courses"])),
        ("cohorts", len(data["cohorts"])),
        ("groups", len(data["groups"])),
        ("students", len(data["students"])),
        ("parents", len(data["parents"])),
        ("staff", len(data["staff"])),
        ("parent_links", len(data["parent_links"])),
        ("enrolments", len(data["enrolments"])),
        ("certificates", len(data["certificates"])),
    ]


def write_relationship_csv(path: Path, issues: list[dict[str, str]]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=["severity", "area", "message", "reference"])
        writer.writeheader()
        writer.writerows(issues)


def write_preview_html(path: Path, year: str, data: dict[str, list[dict[str, str]]], issues: list[dict[str, str]]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    error_count = sum(1 for issue in issues if issue["severity"] == "ERROR")
    warning_count = sum(1 for issue in issues if issue["severity"] == "WARN")
    summary = summary_rows(data)
    course_rows = data["courses"][:25]
    issue_rows = issues[:200]
    html_text = f"""<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>School Import Preview - {html.escape(year)}</title>
  <style>
    body {{ font-family: Arial, sans-serif; margin: 32px; color: #172b3a; background: #f6f8fb; }}
    h1, h2 {{ margin-bottom: 8px; }}
    .cards {{ display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin: 20px 0; }}
    .card {{ background: #fff; border: 1px solid #dbe4ee; border-radius: 8px; padding: 14px; }}
    .metric {{ font-size: 28px; font-weight: 700; }}
    table {{ border-collapse: collapse; width: 100%; background: #fff; margin: 16px 0 28px; }}
    th, td {{ border: 1px solid #dbe4ee; padding: 8px 10px; text-align: left; vertical-align: top; }}
    th {{ background: #eaf3f8; }}
    .error {{ background: #ffc7ce; }}
    .warn {{ background: #ffeb9c; }}
  </style>
</head>
<body>
  <h1>School Import Preview</h1>
  <p>Academic year: <strong>{html.escape(year)}</strong></p>
  <div class="cards">
    <div class="card"><div>Errors</div><div class="metric">{error_count}</div></div>
    <div class="card"><div>Warnings</div><div class="metric">{warning_count}</div></div>
    <div class="card"><div>Courses</div><div class="metric">{len(data["courses"])}</div></div>
    <div class="card"><div>Students</div><div class="metric">{len(data["students"])}</div></div>
    <div class="card"><div>Parents</div><div class="metric">{len(data["parents"])}</div></div>
  </div>
  <h2>Import Counts</h2>
  <table><tr><th>Object</th><th>Count</th></tr>
    {''.join(f"<tr><td>{html.escape(name)}</td><td>{count}</td></tr>" for name, count in summary)}
  </table>
  <h2>Relationship Validation</h2>
  <table><tr><th>Severity</th><th>Area</th><th>Message</th><th>Reference</th></tr>
    {''.join(f"<tr class='{issue['severity'].lower()}'><td>{html.escape(issue['severity'])}</td><td>{html.escape(issue['area'])}</td><td>{html.escape(issue['message'])}</td><td>{html.escape(issue['reference'])}</td></tr>" for issue in issue_rows)}
  </table>
  <h2>Course Preview</h2>
  <table><tr><th>Course code</th><th>Full name</th><th>Category</th></tr>
    {''.join(f"<tr><td>{html.escape(row.get('course_code', ''))}</td><td>{html.escape(row.get('fullname', ''))}</td><td>{html.escape(row.get('category_code', ''))}</td></tr>" for row in course_rows)}
  </table>
</body>
</html>
"""
    path.write_text(html_text)


def main() -> None:
    parser = argparse.ArgumentParser(description="Generate relationship validation and HTML import preview reports.")
    parser.add_argument("--source", required=True)
    parser.add_argument("--year", default="2026-2027")
    parser.add_argument("--output-dir", required=True)
    args = parser.parse_args()

    try:
        source = Path(args.source)
        output = Path(args.output_dir)
        data = load_dataset(source, args.year)
        issues = relationship_issues(data)
        write_relationship_csv(output / "relationship_validation_report.csv", issues)
        write_preview_html(output / "import_preview.html", args.year, data, issues)
    except Exception as exc:
        print(f"Report generation failed: {exc}", file=sys.stderr)
        sys.exit(1)
    print(f"Generated import reports under {args.output_dir}")


if __name__ == "__main__":
    main()
