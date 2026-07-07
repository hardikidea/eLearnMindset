# Validation Rules

Validation happens in layers so errors are found before Moodle writes to the database.

## Layer 1: Workbook Structure

Command:

```bash
./scripts/validate_master_excel.py \
  --workbook input/school_master_import.xlsx \
  --year 2026-2027
```

Checks:

- Required sheets exist.
- Row 1 metadata exists.
- New-format workbooks include row 2 requirement/owner guidance, row 3 column summaries, row 4 formula/reference guidance and row 5 headers.
- Headers match the expected CSV headers. Legacy workbooks with row 2 headers are still supported when `header_row` metadata is absent.

## Layer 2: Generated Folder Structure

Command:

```bash
./scripts/validate_generated_structure.py \
  --source output/source_csv \
  --year 2026-2027
```

Checks:

- Required source CSV files exist.
- Headers are correct.
- Folder paths match the expected import structure.

## Layer 3: ID and Reference Validation

Command:

```bash
./scripts/validate_idnumber_patterns.py \
  --source output/source_csv \
  --year 2026-2027
```

Checks:

- Course ID numbers.
- Cohort ID numbers.
- Group ID numbers.
- Student, parent and teacher usernames.
- Student admission numbers and GR numbers.
- Parent/student links.
- Teacher/course role assignments.
- Cohort/course/group enrolment mappings.
- Course certificate mappings.

## Layer 4: Relationship and Preview Reports

Before the Moodle CLI dry run, generate operator reports:

```bash
./scripts/generate_import_reports.py \
  --source output/source_csv \
  --year 2026-2027 \
  --output-dir output/reports
```

Checks:

- Parent/student relationship gaps.
- Teacher/course assignment gaps.
- Course/cohort/group enrolment gaps.
- Human-readable import preview.

## Layer 5: Moodle CLI Dry Run

Command:

```bash
./scripts/run_master_import.sh 2026-2027 dry-run
```

Checks:

- Moodle can resolve users, roles, courses, cohorts and categories.
- The import can run without writing production changes.
