# Chapter 08: Master Excel Workbook

| Previous | Documentation Home | Next |
|---|---|---|
| [Chapter 07: Academic Year Rollover](07-academic-year-rollover.md) | [CLI Pack README](../README.md) | [Chapter 09: Validation and Troubleshooting](09-validation-and-troubleshooting.md) |


The master workbook is an Excel-first maintenance layer for the ordered CSV pack.

Workbook:

```text
outputs/master-import-workbook/eLearnMindset_school_import_master.xlsx
```

Generator:

```text
build_master_import_workbook.mjs
```

## When to Use

Use the workbook when:

- School staff prefer spreadsheet entry.
- A reviewer needs dropdown/reference checks.
- Multiple CSV sheets must be edited together.
- The implementation team wants a single handoff file.

## Workbook Workflow

1. Open the workbook.
2. Review `00_README`.
3. Use `01_IMPORT_SEQUENCE` for ordered CSV names.
4. Edit master sheets first.
5. Edit structure sheets.
6. Edit users and enrolment sheets.
7. Check `03_VALIDATION_SUMMARY`.
8. Export changed sheets back to ordered CSV filenames.
9. Run CLI validation and dry-run.

## Export Rule

Export with the ordered repository filename, not the logical script name.

Correct:

```text
12_courses.csv
20_users_students.csv
53_promotion_actions.csv
```

Incorrect:

```text
courses.csv
users_students.csv
promotion_actions.csv
```

## Regenerate Workbook

```bash
PACK="$PWD/research/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
NODE="/Users/hardik.chauhan/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/bin/node"
NODE_MODULES="/Users/hardik.chauhan/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules"

rm -f "$PACK/node_modules"
ln -s "$NODE_MODULES" "$PACK/node_modules"
"$NODE" "$PACK/build_master_import_workbook.mjs"
rm -f "$PACK/node_modules"
```

Do not commit a `node_modules` directory or symlink.

## Workbook Sheets

| Sheet | Purpose |
|---|---|
| `00_README` | Workbook usage summary and rules. |
| `01_IMPORT_SEQUENCE` | Ordered CSV file, logical file, purpose, dependencies, and row counts. |
| `02_REFERENCE_RULES` | Relationship rules between sheets. |
| `03_VALIDATION_SUMMARY` | Generated invalid-reference count from current data. |
| `04_REF_LISTS` | Dropdown list source ranges. |
| `05_*` onward | One sheet per ordered CSV file. |

The workbook is a data-entry aid. The CLI validators remain authoritative before import.

---

| Previous | Documentation Home | Next |
|---|---|---|
| [Chapter 07: Academic Year Rollover](07-academic-year-rollover.md) | [CLI Pack README](../README.md) | [Chapter 09: Validation and Troubleshooting](09-validation-and-troubleshooting.md) |
