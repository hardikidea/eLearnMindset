#!/usr/bin/env python3
from __future__ import annotations

import argparse
import re
import subprocess
import sys
import tempfile
import zipfile
from pathlib import Path

from openpyxl import load_workbook


PACK_ROOT = Path(__file__).resolve().parents[2]
REPO_ROOT = PACK_ROOT.parents[1]
MASTER_DIR = PACK_ROOT / "build" / "master_excel"
DEFAULT_XLSX = MASTER_DIR / "school_master_pack_2026_2027_full_predefined_master.xlsx"
DEFAULT_ODS = MASTER_DIR / "school_master_pack_2026_2027_full_predefined_master_macros.ods"
MACRO_SOURCE = PACK_ROOT / "master_import_process" / "scripts" / "libreoffice_master_tools.bas"
GENERATOR = PACK_ROOT / "master_import_process" / "scripts" / "create_libreoffice_macro_workbook.py"
README = MASTER_DIR / "README.md"
SOFFICE = Path("/Applications/LibreOffice.app/Contents/MacOS/soffice")


REQUIRED_SHEETS = [
    "status",
    "05_school_master",
    "06_academic_years",
    "07_boards",
    "08_mediums",
    "09_grades",
    "10_streams",
    "11_divisions",
    "12_subjects",
    "13_grade_subject_matrix",
    "14_categories",
    "15_optional_year_category_model",
    "16_courses",
    "17_courses_with_templatecourse_",
    "18_cohorts",
    "19_groups",
    "23_users_staff",
    "24_users_students",
    "25_users_parents",
    "26_cohort_members",
    "29_enrolments",
    "41_course_template_application",
    "55_student_academic_history_tem",
    "56_student_promotion_plan_2027_",
    "58_next_year_courses_2027_2028",
    "59_next_year_cohorts_2027_2028",
    "60_next_year_groups_2027_2028",
    "61_next_year_enrolments_2027_20",
    "66_assessment_plan",
    "67_attendance_policy",
    "68_course_certificates",
    "69_course_final_exams",
    "70_course_term_exams",
    "71_exam_terms",
    "72_gradebook_weights",
]


ROW_COUNT_PAIRS = [
    ("courses vs matrix", "16_courses", "13_grade_subject_matrix"),
    ("template course upload vs courses", "17_courses_with_templatecourse_", "16_courses"),
    ("course template application vs courses", "41_course_template_application", "16_courses"),
    ("certificates vs courses", "68_course_certificates", "16_courses"),
    ("final exams vs courses", "69_course_final_exams", "16_courses"),
    ("gradebook weights vs courses", "72_gradebook_weights", "16_courses"),
    ("student history vs students", "55_student_academic_history_tem", "24_users_students"),
    ("student promotion vs students", "56_student_promotion_plan_2027_", "24_users_students"),
    ("next courses vs courses", "58_next_year_courses_2027_2028", "16_courses"),
    ("next cohorts vs cohorts", "59_next_year_cohorts_2027_2028", "18_cohorts"),
    ("next groups vs groups", "60_next_year_groups_2027_2028", "19_groups"),
    ("next enrolments vs enrolments", "61_next_year_enrolments_2027_20", "29_enrolments"),
]


def rel(path: Path) -> str:
    try:
        return str(path.relative_to(REPO_ROOT))
    except ValueError:
        return str(path)


def fail(errors: list[str], message: str) -> None:
    errors.append(message)


def check_file_exists(errors: list[str], path: Path) -> None:
    if not path.exists():
        fail(errors, f"missing file: {rel(path)}")
    else:
        print(f"OK {rel(path)}")


def check_required_sheets(errors: list[str], workbook) -> None:
    missing = [name for name in REQUIRED_SHEETS if name not in workbook.sheetnames]
    if missing:
        fail(errors, f"missing workbook sheets: {missing}")
        return
    print("OK required sheets exist")
    for sheet_name in REQUIRED_SHEETS:
        ws = workbook[sheet_name]
        if ws.max_row < 1 or ws.max_column < 1:
            fail(errors, f"{sheet_name}: empty sheet")
            continue
        headers = [
            ws.cell(1, col).value
            for col in range(1, ws.max_column + 1)
            if ws.cell(1, col).value not in (None, "")
        ]
        if not headers:
            fail(errors, f"{sheet_name}: missing row-1 headers")
    print("OK required sheet headers")


def check_status_sheet(errors: list[str], workbook) -> None:
    if "status" not in workbook.sheetnames:
        fail(errors, "missing workbook status sheet")
        return
    ws = workbook["status"]
    headers = [ws.cell(1, col).value for col in range(1, 6)]
    expected = ["type", "filename", "count", "status", "action"]
    if headers != expected:
        fail(errors, f"status sheet headers mismatch: {headers} != {expected}")
        return
    types = [str(ws.cell(row, 1).value or "") for row in range(7, ws.max_row + 1)]
    automatic = types.count("automatic")
    manual = types.count("manual")
    if automatic < 20:
        fail(errors, f"status sheet automatic row count too low: {automatic}")
    if manual < 20:
        fail(errors, f"status sheet manual row count too low: {manual}")
    actions = " ".join(str(ws.cell(row, 5).value or "") for row in range(2, min(ws.max_row, 12) + 1))
    if "vnd.sun.star.script" in actions or "Standard.MatrixTools" in actions:
        fail(errors, "xlsx status sheet must not contain document macro hyperlinks; use the .ods macro workbook")
    if "use the macro-enabled .ods workbook" not in actions:
        fail(errors, "xlsx status sheet missing macro-enabled .ods workbook guidance")
    print(f"OK status sheet: {automatic} automatic rows, {manual} manual rows")


def check_row_counts(errors: list[str], workbook) -> None:
    counts = {name: workbook[name].max_row - 1 for name in workbook.sheetnames}
    for label, left, right in ROW_COUNT_PAIRS:
        if counts[left] != counts[right]:
            fail(errors, f"{label}: {counts[left]} != {counts[right]}")
        else:
            print(f"OK {label}: {counts[left]}")

    terms_ws = workbook["71_exam_terms"]
    header = {terms_ws.cell(1, col).value: col for col in range(1, terms_ws.max_column + 1)}
    active_terms: list[str] = []
    for row in range(2, terms_ws.max_row + 1):
        term_code = str(terms_ws.cell(row, header["term_code"]).value or "")
        if term_code and term_code.upper() != "FINAL":
            active_terms.append(term_code)
    expected_term_rows = counts["16_courses"] * len(active_terms)
    if counts["70_course_term_exams"] != expected_term_rows:
        fail(
            errors,
            "term exams rows: "
            f"{counts['70_course_term_exams']} != courses({counts['16_courses']}) "
            f"* active_terms({len(active_terms)})",
        )
    else:
        print(f"OK term exams rows: {counts['70_course_term_exams']} for {active_terms}")


def check_macro_alignment(errors: list[str], workbook) -> None:
    macro_source = MACRO_SOURCE.read_text(encoding="utf-8")
    generator = GENERATOR.read_text(encoding="utf-8")
    readme = README.read_text(encoding="utf-8")

    macros_match = re.search(r'\["Individual macros", "([^"]+)"', generator)
    targets_match = re.search(r'\["Derived target sheets", "([^"]+)"', generator)
    if not macros_match or not targets_match:
        fail(errors, "macro guide rows missing in generator")
        return

    macros = macros_match.group(1).split(", ")
    targets = targets_match.group(1).split(", ")
    if len(macros) != len(targets):
        fail(errors, f"macro/target count mismatch: {len(macros)} != {len(targets)}")
    if len(macros) != 22:
        fail(errors, f"expected 22 individual macros, found {len(macros)}")

    missing_subs = [macro for macro in macros if f"Sub {macro}" not in macro_source]
    missing_docs = [macro for macro in macros if f"`{macro}`" not in readme]
    missing_target_docs = [target for target in targets if target not in readme]
    if missing_subs:
        fail(errors, f"missing macro Sub definitions: {missing_subs}")
    if missing_docs:
        fail(errors, f"missing README macro entries: {missing_docs}")
    if missing_target_docs:
        fail(errors, f"missing README target entries: {missing_target_docs}")

    sheet_refs = sorted(set(re.findall(r'RequireSheet\(oDoc, "([^"]+)"\)', macro_source)))
    missing_refs = [sheet for sheet in sheet_refs if sheet not in workbook.sheetnames]
    if missing_refs:
        fail(errors, f"Basic RequireSheet refs missing from workbook: {missing_refs}")
    else:
        print(f"OK macro guide/source/docs aligned; RequireSheet refs: {len(sheet_refs)}")


def check_ods_package(errors: list[str], ods_path: Path) -> None:
    required_entries = [
        "mimetype",
        "content.xml",
        "META-INF/manifest.xml",
        "Basic/Standard/MatrixTools.xba",
        "Basic/Standard/script-lb.xml",
        "Basic/script-lc.xml",
    ]
    with zipfile.ZipFile(ods_path) as archive:
        names = set(archive.namelist())
        missing_entries = [entry for entry in required_entries if entry not in names]
        if missing_entries:
            fail(errors, f"ODS missing package entries: {missing_entries}")
            return
        macro = archive.read("Basic/Standard/MatrixTools.xba").decode("utf-8")
        content = archive.read("content.xml").decode("utf-8", errors="ignore")
        for token in [
            "GenerateAllDerivedSheets",
            "RefreshStatus",
            "ClearAutomaticData",
            "ResetAutomaticData",
            "GenerateOptionalYearCategoryModel",
            "GenerateStudentAcademicHistory",
            "GenerateStudentPromotionPlan",
            "GenerateAssessmentPlan",
            "GenerateAttendancePolicy",
        ]:
            if token not in macro:
                fail(errors, f"ODS macro package missing {token}")
        if "00_MACRO_GUIDE" not in content:
            fail(errors, "ODS content missing 00_MACRO_GUIDE sheet")
        if "status" not in content:
            fail(errors, "ODS content missing status sheet")
        for token in [
            "vnd.sun.star.script",
            "GenerateAllDerivedSheets",
            "RefreshStatus",
            "ClearAutomaticData",
            "ResetAutomaticData",
        ]:
            if token not in content:
                fail(errors, f"ODS status sheet missing macro action token: {token}")
        if "00_MACRO_GUIDE" in content and "status" in content:
            print("OK ODS macro package")


def check_libreoffice_convert(errors: list[str], ods_path: Path) -> None:
    if not SOFFICE.exists():
        print(f"SKIP LibreOffice convert smoke; soffice not found at {SOFFICE}")
        return
    with tempfile.TemporaryDirectory(prefix="dps_master_smoke_") as tmp:
        tmpdir = Path(tmp)
        profile = tmpdir / "profile"
        command = [
            str(SOFFICE),
            f"-env:UserInstallation=file://{profile}",
            "--headless",
            "--convert-to",
            "xlsx",
            "--outdir",
            str(tmpdir),
            str(ods_path),
        ]
        result = subprocess.run(command, capture_output=True, text=True)
        if result.returncode != 0:
            fail(errors, f"LibreOffice convert failed: {result.stderr or result.stdout}")
            return
        output = tmpdir / f"{ods_path.stem}.xlsx"
        if not output.exists():
            fail(errors, "LibreOffice convert did not create an XLSX output")
        else:
            print(f"OK LibreOffice convert smoke: {output.stat().st_size:,} bytes")


def main() -> int:
    parser = argparse.ArgumentParser(description="Smoke validate the school master-pack workbook and macro ODS.")
    parser.add_argument("--xlsx", type=Path, default=DEFAULT_XLSX)
    parser.add_argument("--ods", type=Path, default=DEFAULT_ODS)
    parser.add_argument("--skip-libreoffice", action="store_true")
    args = parser.parse_args()

    errors: list[str] = []
    print("School master-pack workbook smoke validation")
    print("---------------------------------------")
    for path in [args.xlsx, args.ods, MACRO_SOURCE, GENERATOR, README]:
        check_file_exists(errors, path)
    if errors:
        for error in errors:
            print(f"ERROR {error}")
        return 1

    workbook = load_workbook(args.xlsx, read_only=False, data_only=False)
    print(f"OK workbook opens: {len(workbook.sheetnames)} sheets")
    if len(workbook.sheetnames) != 74:
        fail(errors, f"expected 74 sheets, found {len(workbook.sheetnames)}")
    if len(workbook.sheetnames) != len(set(workbook.sheetnames)):
        fail(errors, "duplicate sheet names found")
    if any(len(name) > 31 for name in workbook.sheetnames):
        fail(errors, "one or more sheet names exceed Excel's 31-character limit")
    else:
        print("OK sheet-name constraints")

    check_required_sheets(errors, workbook)
    check_status_sheet(errors, workbook)
    check_row_counts(errors, workbook)
    check_macro_alignment(errors, workbook)
    check_ods_package(errors, args.ods)
    if not args.skip_libreoffice:
        check_libreoffice_convert(errors, args.ods)

    if errors:
        print(f"Errors: {len(errors)}")
        for error in errors:
            print(f"ERROR {error}")
        return 1
    print("SMOKE PASS")
    return 0


if __name__ == "__main__":
    sys.exit(main())
