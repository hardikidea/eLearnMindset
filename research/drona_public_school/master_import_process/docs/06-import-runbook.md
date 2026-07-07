# Import Runbook

## 1. Prepare Workbook

```bash
cd /Users/hardik.chauhan/Documents/learning/eLearnMindset/research/drona_public_school/master_import_process
python3 -m pip install -r requirements.txt
python3 scripts/create_master_workbooks.py --year 2026-2027
cp templates/school_master_import_template.xlsx input/school_master_import.xlsx
```

If `python3` cannot import `openpyxl`, run the same command with a Python environment that has `openpyxl` installed:

```bash
/path/to/python3 scripts/create_master_workbooks.py --year 2026-2027
```

Fill data from row 7 onward in each sheet.

The blank template includes row 2 requirement/owner guidance, row 3 column summaries, row 4 generic ID-number formulas and row 6 concrete filled examples. Because row 1 marks `example_row=6`, the converter skips row 6. Enter real import data from row 7 onward.

## 2. Validate Workbook Only

```bash
./scripts/validate_master_excel.py \
  --workbook input/school_master_import.xlsx \
  --year 2026-2027
```

## 3. Generate Source CSV

```bash
./scripts/excel_to_source_csv.py \
  --workbook input/school_master_import.xlsx \
  --output output/source_csv \
  --year 2026-2027
```

If `09_subject_matrix` has no real data rows, the converter generates it from the board, medium, grade, stream and subject master sheets.

## 4. Validate Generated CSV

```bash
./scripts/validate_generated_structure.py \
  --source output/source_csv \
  --year 2026-2027

./scripts/validate_idnumber_patterns.py \
  --source output/source_csv \
  --year 2026-2027
```

## 5. Generate Preview Reports

```bash
./scripts/generate_import_reports.py \
  --source output/source_csv \
  --year 2026-2027 \
  --output-dir output/reports
```

Review:

```text
output/reports/relationship_validation_report.csv
output/reports/import_preview.html
```

## 6. Assemble Moodle-Ready CSV

```bash
python3 ../scripts/assemble.py \
  --year 2026-2027 \
  --source-root output/source_csv
```

## 7. Dry Run

```bash
./scripts/run_master_import.sh 2026-2027 dry-run
```

The wrapper runs validation, report generation and assembly before calling the Moodle import process.

## 8. Live Import

```bash
./scripts/run_master_import.sh 2026-2027 live
```

## 9. Spot Check in Moodle

Check:

- One student can log in.
- The student appears in the correct cohort.
- The student sees expected courses.
- One teacher sees assigned courses.
- The principal can access school-level reports.
- One course has sections, gradebook and certificate activity.
