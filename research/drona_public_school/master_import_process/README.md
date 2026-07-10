# Master Import Process

This folder contains the reusable Excel-to-CSV workflow for the school master pack.

The complete developer documentation is maintained at:

```text
../docs/developer-guide.md
```

## Quick Start

Run from the pack root:

```bash
cd /Users/hardik.chauhan/Documents/learning/eLearnMindset/research/drona_public_school
```

Install dependencies:

```bash
python3 -m pip install -r master_import_process/requirements.txt
```

Create or refresh workbook templates:

```bash
python3 master_import_process/scripts/create_master_workbooks.py --year 2026-2027
```

Open the workbook and start from the `status` sheet. It shows manual versus automatic sheets, live record counts, pass/fail checks for generated data, and LibreOffice macro links for refresh, clear, reset, and regenerate actions.

Create an editable input workbook:

```bash
cp master_import_process/templates/school_master_import_template.xlsx \
  master_import_process/input/school_master_import.xlsx
```

Validate-only workflow:

```bash
master_import_process/scripts/run_master_import.sh 2026-2027 validate-only
```

Dry-run workflow:

```bash
master_import_process/scripts/run_master_import.sh 2026-2027 dry-run
```

Live workflow after backup and dry-run pass:

```bash
master_import_process/scripts/run_master_import.sh 2026-2027 live
```

## Workbook Smoke Validation

```bash
python3 master_import_process/scripts/validate_master_workbook.py
```

Use `--skip-libreoffice` if LibreOffice is not installed:

```bash
python3 master_import_process/scripts/validate_master_workbook.py --skip-libreoffice
```

## Safety Rules

- Do not commit filled workbooks containing real student or parent data.
- Do not edit `output/source_csv/` by hand; regenerate it from the workbook.
- Do not edit `../build/assembled_csv/<year>/` by hand; regenerate it through the wrapper.
- Always run `validate-only` and `dry-run` before `live`.
