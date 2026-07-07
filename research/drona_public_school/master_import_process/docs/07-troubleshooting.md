# Troubleshooting

## Workbook Not Found

Error:

```text
Workbook not found
```

Fix:

```bash
cp templates/school_master_import_template.xlsx input/school_master_import.xlsx
```

Then fill data and rerun the command.

## Missing Sheet

The workbook must keep the original sheet names. Regenerate the template if a sheet was deleted.

```bash
python3 scripts/create_master_workbooks.py --year 2026-2027
```

## openpyxl Not Found

Error:

```text
ModuleNotFoundError: No module named 'openpyxl'
```

Fix by using a Python environment that has `openpyxl` installed:

```bash
/path/to/python3 scripts/create_master_workbooks.py --year 2026-2027
PYTHON_BIN=/path/to/python3 ./scripts/run_master_import.sh 2026-2027 validate-only
```

## Header Mismatch

Do not rename row 5 columns in the new-format workbook. Restore the sheet from the template or regenerate the workbook.

## Invalid Course ID

Check these fields in the course row:

```text
school_code
board_code
medium_code
grade_code
stream_code
subject_code
academic_year
```

The course ID must match:

```text
<school>-<board>-<medium>-<grade>-<stream>-<subject>-<startYear>
```

## Cohort Not Found

Check:

- The cohort exists in `14_cohorts`.
- Student `cohort1` matches the cohort code.
- Enrolment rows reference the same cohort code.

## Group Mismatch

Group ID must be:

```text
<course_idnumber>-<division>
```

## Parent Link Failed

Check:

- Parent exists in `21_users_parents`.
- Student exists in `20_users_students`.
- Link exists in `24_parent_links`.

## Teacher Assignment Failed

Check:

- Teacher exists in `19_users_staff`.
- Course exists in `12_courses`.
- Role assignment uses `context_type=course`.
- `context_identifier` is the course idnumber.

## Certificate Mapping Failed

Check:

- Course exists in `12_courses`.
- `course_shortname` matches the course row.
- `certificate_activity_key` is populated.
- Custom Certificate plugin is installed before live import.
