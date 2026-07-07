# Master Import Process

Generic Excel-to-Moodle import process for building a school system from a master workbook.

## Quick Start

Run from this folder:

```bash
cd /Users/hardik.chauhan/Documents/learning/eLearnMindset/research/drona_public_school/master_import_process
```

Create or refresh the workbook templates:

```bash
python3 -m pip install -r requirements.txt
python3 scripts/create_master_workbooks.py --year 2026-2027
```

If your system Python does not include `openpyxl`, point `PYTHON_BIN` to a Python environment that has it:

```bash
/path/to/python3 scripts/create_master_workbooks.py --year 2026-2027
```

This creates:

```text
templates/school_master_import_template.xlsx
templates/sample_minimal_school_import.xlsx
```

The sample workbook is for structure review and conversion testing. For a real import, fill `input/school_master_import.xlsx`.

The workbook includes dropdowns, conditional highlighting, formula helper columns, tab colors, `_dashboard`, `_sheet_index`, `_version` and hidden `_lookups` sheets.

The blank template is not fully empty. It is prefilled with reusable standard data such as academic years, boards, mediums, grades, streams, divisions, subjects, Moodle profile fields, roles, lookup/reference rows and course-template definitions. School-specific sheets such as school identity, users, courses, cohorts, groups, enrolments and parent links remain blank for each setup.

Copy the blank template into `input/` and fill it:

```bash
cp templates/school_master_import_template.xlsx input/school_master_import.xlsx
```

Validate only:

```bash
./scripts/run_master_import.sh 2026-2027 validate-only
```

Or with an explicit Python runtime:

```bash
PYTHON_BIN=/path/to/python3 ./scripts/run_master_import.sh 2026-2027 validate-only
```

Dry-run import:

```bash
./scripts/run_master_import.sh 2026-2027 dry-run
```

Live import:

```bash
./scripts/run_master_import.sh 2026-2027 live
```

## Workbook Row Rules

Every workbook sheet follows this layout:

| Row | Purpose |
|---:|---|
| 1 | Template metadata row. It stores the target CSV path and ordered CSV file name. |
| 2 | Column guide row. It shows requirement plus owner/use, for example `Mandatory | School-specific setup`. |
| 3 | Column usage summary. This row explains what each CSV column controls in Moodle. |
| 4 | ID-number formula and reference-condition row. Example: `<SCHOOL_CODE>-<START_YEAR>-<BOARD_CODE>-<MEDIUM_CODE>-<GRADE_CODE>-<STREAM_CODE>-<DIVISION_CODE>`. |
| 5 | CSV header row. These names become the generated CSV header. |
| 6 | Concrete example row. The converter skips this row when row 1 says `example_row=6`. |
| 7+ | Actual data rows entered or pasted by the school operator. |

Do not delete rows 1 to 5. Keep row 6 as a visual example or replace it only after removing the `example_row=6` metadata from row 1.

## Color Guide

Open the `_color_guide` sheet in the workbook for the full legend.

Header row colors show the requirement level:

- `Mandatory`: must be filled before dry-run.
- `Required if used`: required only when the optional sheet or feature is used.
- `Optional`: useful metadata or optional Moodle setting.

Row 2 colors show who owns or uses the column:

- `School-specific setup`: school, board, medium, grade, stream, division or subject setup.
- `Parent/family data`: parent login and parent-student relationship fields.
- `Student registration`: student accounts, cohorts and class/division placement.
- `Staff registration`: teacher, principal, trustee and staff accounts.
- `Academic/course setup`: courses, groups, enrolments, exams and certificates.
- `Moodle/system reference`: Moodle roles, contexts, template and operational references.

## Process Flow

```text
master Excel workbook
-> source CSV folder structure
-> generated structure validation
-> ID number and reference validation
-> assembled Moodle-ready CSV
-> Moodle dry-run import
-> Moodle live import
```

## Documentation

- [Overview](docs/01-overview.md)
- [Master Excel Workbook](docs/02-master-excel-workbook.md)
- [Sheet and CSV Mapping](docs/03-sheet-and-csv-mapping.md)
- [ID Number Patterns](docs/04-idnumber-patterns.md)
- [Validation Rules](docs/05-validation-rules.md)
- [Import Runbook](docs/06-import-runbook.md)
- [Troubleshooting](docs/07-troubleshooting.md)
- [Excel Automation and Reports](docs/08-excel-automation-and-reports.md)

## Safety Rules

- Do not commit filled workbooks that contain real student or parent data.
- Do not edit `output/source_csv/` by hand; regenerate it from the workbook.
- Do not edit `../build/assembled_csv/<year>/` by hand; regenerate it through the wrapper.
- Always run `dry-run` before `live`.
