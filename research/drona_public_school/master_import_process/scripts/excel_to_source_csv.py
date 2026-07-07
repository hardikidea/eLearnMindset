#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
import sys
from pathlib import Path

from openpyxl import load_workbook

from common import SOURCE_FILES, normalise_cell, resolve_tokens


def row_values(row, width: int) -> list[str]:
    values = [normalise_cell(cell.value) for cell in row[:width]]
    if len(values) < width:
        values.extend([""] * (width - len(values)))
    return values


def sheet_metadata(ws) -> dict[str, str]:
    cells = [normalise_cell(cell.value) for cell in ws[1]]
    metadata: dict[str, str] = {}
    for index in range(0, len(cells) - 1, 2):
        key = cells[index]
        value = cells[index + 1]
        if key:
            metadata[key] = value
    return metadata


def metadata_int(metadata: dict[str, str], key: str, default: int) -> int:
    value = metadata.get(key)
    if not value:
        return default
    try:
        return int(value)
    except ValueError:
        return default


def sheet_to_rows(ws) -> tuple[list[str], list[list[str]]]:
    metadata = sheet_metadata(ws)
    header_row = metadata_int(metadata, "header_row", 2)
    headers = [normalise_cell(cell.value) for cell in ws[header_row] if normalise_cell(cell.value)]
    width = len(headers)
    rows: list[list[str]] = []
    example_row = metadata_int(metadata, "example_row", 0)
    for row_number, row in enumerate(ws.iter_rows(min_row=header_row + 1), start=header_row + 1):
        if row_number == example_row:
            continue
        values = row_values(row, width)
        if any(values):
            rows.append(values)
    return headers, rows


def dict_rows(headers: list[str], rows: list[list[str]]) -> list[dict[str, str]]:
    return [dict(zip(headers, row)) for row in rows]


def rows_for(sheets: dict[str, tuple[list[str], list[list[str]]]], sheet: str) -> list[dict[str, str]]:
    headers, rows = sheets.get(sheet, ([], []))
    return dict_rows(headers, rows)


def auto_generate_subject_matrix(sheets: dict[str, tuple[list[str], list[list[str]]]]) -> None:
    headers, rows = sheets.get("09_subject_matrix", ([], []))
    if rows or not headers:
        return
    boards = rows_for(sheets, "03_boards")
    mediums = rows_for(sheets, "04_mediums")
    grades = rows_for(sheets, "05_grades")
    streams = rows_for(sheets, "06_streams")
    subjects = rows_for(sheets, "08_subjects")
    if not all([boards, mediums, grades, streams, subjects]):
        return

    generated: list[list[str]] = []
    order = 1
    for board in boards:
        for medium in mediums:
            for grade in grades:
                grade_code = grade.get("grade_code", "")
                for stream in streams:
                    applies_to = stream.get("applies_to", "").strip()
                    if applies_to and applies_to.lower() not in {"all", "*"} and grade_code not in applies_to:
                        continue
                    for subject in subjects:
                        row = {
                            "board_code": board.get("board_code", ""),
                            "medium_code": medium.get("medium_code", ""),
                            "grade_code": grade_code,
                            "stream_code": stream.get("stream_code", ""),
                            "subject_code": subject.get("subject_code", ""),
                            "subject_name": subject.get("subject_name", ""),
                            "subject_category": subject.get("subject_category", ""),
                            "is_compulsory": "1",
                            "is_elective": "0",
                            "display_order": str(order),
                            "source_note": "Auto-generated from board, medium, grade, stream and subject master sheets.",
                        }
                        generated.append([row.get(header, "") for header in headers])
                        order += 1
    sheets["09_subject_matrix"] = (headers, generated)


def apply_generated_matrices(sheets: dict[str, tuple[list[str], list[list[str]]]]) -> None:
    auto_generate_subject_matrix(sheets)


def convert(workbook: Path, output: Path, year: str) -> int:
    if not workbook.exists():
        raise FileNotFoundError(f"Workbook does not exist: {workbook}")
    wb = load_workbook(workbook, read_only=True, data_only=True)
    sheets: dict[str, tuple[list[str], list[list[str]]]] = {}
    for entry in SOURCE_FILES:
        sheet_name = entry["sheet"]
        if sheet_name not in wb.sheetnames:
            if entry.get("required"):
                raise RuntimeError(f"Missing required sheet: {sheet_name}")
            continue
        sheets[sheet_name] = sheet_to_rows(wb[sheet_name])

    apply_generated_matrices(sheets)

    targets: dict[Path, tuple[list[str], list[list[str]]]] = {}
    for entry in SOURCE_FILES:
        sheet_name = entry["sheet"]
        if sheet_name not in sheets:
            continue
        headers, rows = sheets[sheet_name]
        target = output / resolve_tokens(entry["source"], year)
        if target in targets:
            current_headers, current_rows = targets[target]
            if rows:
                if current_headers != headers:
                    raise RuntimeError(f"Duplicate target header mismatch for {target}")
                current_rows.extend(rows)
            continue
        targets[target] = (headers, rows)

    for target, (headers, rows) in targets.items():
        target.parent.mkdir(parents=True, exist_ok=True)
        with target.open("w", newline="") as handle:
            writer = csv.writer(handle)
            writer.writerow(headers)
            writer.writerows(rows)
    return len(targets)


def main():
    parser = argparse.ArgumentParser(description="Generate source CSV folder structure from the master import Excel workbook.")
    parser.add_argument("--workbook", required=True)
    parser.add_argument("--output", required=True)
    parser.add_argument("--year", default="2026-2027")
    args = parser.parse_args()

    try:
        written = convert(Path(args.workbook), Path(args.output), args.year)
    except Exception as exc:
        print(f"Excel to CSV conversion failed: {exc}", file=sys.stderr)
        sys.exit(1)
    print(f"Generated {written} CSV files under {args.output}")


if __name__ == "__main__":
    main()
