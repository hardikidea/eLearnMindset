# Master Import Process

This folder contains the reusable Excel-to-CSV workflow for the school master pack.

Canonical docs:

- Main guide: [../docs/developer-guide.md](../docs/developer-guide.md)
- Workbook/macro guide: [../build/master_excel/README.md](../build/master_excel/README.md)

Run commands from the repository root:

```bash
cd research/drona_public_school
```

Common tasks:

| Task | Command |
|---|---|
| Install workbook dependencies | `python3 -m pip install -r master_import_process/requirements.txt` |
| Refresh workbook templates | `python3 master_import_process/scripts/create_master_workbooks.py --year 2026-2027` |
| Regenerate macro ODS workbook | `python3 master_import_process/scripts/create_libreoffice_macro_workbook.py --year 2026-2027 --source-root research/drona_public_school` |
| Create editable input workbook | `cp master_import_process/templates/school_master_import_template.xlsx master_import_process/input/school_master_import.xlsx` |
| Validate workbook artifacts | `python3 master_import_process/scripts/validate_master_workbook.py` |
| Check local environment | `scripts/doctor.sh 2026-2027` |
| Run complete preflight validation | `scripts/validate_all.sh 2026-2027` |
| Generate preflight report only | `scripts/preflight_report.sh 2026-2027` |
| Validate generated source CSV | `master_import_process/scripts/run_master_import.sh 2026-2027 validate-only` |
| Dry-run import | `master_import_process/scripts/run_master_import.sh 2026-2027 dry-run` |
| Live import after backup | `master_import_process/scripts/run_master_import.sh 2026-2027 live` |

Safety rules:

- Start every workbook review from the `status` sheet.
- Use the `.xlsx` workbook for example/reference review and the `.ods` workbook for macro execution; the `.ods` is seeded from assembled import CSVs so status counts match Moodle-ready data.
- Do not commit filled workbooks containing real student or parent data.
- Do not edit `output/source_csv/` or `../build/assembled_csv/<year>/` by hand.
- Keep `build/reports/<year>/preflight_report.md` and `import_manifest.json` for operator review when validating a production import batch.
- Always run `validate-only` and `dry-run` before `live`.
