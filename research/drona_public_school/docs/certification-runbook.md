# Certification Runbook

Certification is enabled by default for every course through Moodle `mod_customcert`.

## Default Certificate Model

- One certificate activity is created in section `15 - Certificate & Completion`.
- Activity key/idnumber: `course_completion_certificate`.
- Activity type: `customcert`.
- Default activity name: `Download Course Completion Certificate`.
- PDF format: A4 landscape.
- Verification: certificate ID plus QR code.
- Branding: Drona Public School navy, gold and red palette.
- Student completion tracking: automatic completion by viewing the certificate activity.

Each course must have exactly one enabled row in `years/<academic-year>/course_certificates.csv`.

## Required Plugin

The Moodle site must have `mod_customcert` installed and upgraded before live import.

```bash
docker compose exec -T moodle php admin/cli/upgrade.php --non-interactive
docker compose exec -T moodle php admin/cli/purge_caches.php
```

The import script stops if `customcert`, `customcert_templates`, `customcert_pages` or `customcert_elements` tables are missing.

## Files

- `templates/certification/certificate_templates.csv` defines the standard certificate template metadata.
- `templates/certification/certificate_policies.csv` defines grade-band certificate policy.
- `templates/legacy/40_certificate_badge_policy.csv` keeps the ordered import reference.
- `years/<academic-year>/course_certificates.csv` maps every actual course to the certificate activity and template.

## Validate

```bash
./scripts/assemble.py --year 2026-2027
./scripts/validate.py --year 2026-2027
php -d memory_limit=512M scripts/moodle_cli/cli_validate_course_template_csv.php --dir=build/assembled_csv/2026-2027
```

## Dry Run

```bash
./scripts/import.sh 2026-2027 dry-run
```

Expected certificate dry-run output contains lines similar to:

```text
[dry-run] Create customcert activity in DPS-GSEB-...: Download Course Completion Certificate
[dry-run] Configure modern certificate template for DPS-GSEB-...
```

## Live Apply

```bash
./scripts/import.sh 2026-2027 live
```

The certificate step is idempotent. Re-running it updates existing certificate activity settings and refreshes the generated PDF template elements.

## Manual Certificate Reapply

Use this when courses already exist and only certificates need to be refreshed.

```bash
PACK_CONTAINER=/tmp/drona_public_school/2026-2027
docker compose exec -T moodle php -d memory_limit=512M admin/cli/cli_apply_course_certificates.php \
  --dir="$PACK_CONTAINER" \
  --dry-run=1 \
  --refresh-template=1
```

Switch to `--dry-run=0` after reviewing dry-run output.

## Certificate Row Columns

| Column | Usage |
| --- | --- |
| `course_code`, `course_shortname` | Finds the target Moodle course. |
| `certificate_enabled` | `1` creates/updates certificate; `0` skips the course. |
| `credential_type` | Must be `certificate`. |
| `certificate_policy_code` | Grade-band policy reference. |
| `requires_plugin` | Must be `1` because `mod_customcert` is required. |
| `certificate_template_code` | Template reference, currently `DRONA_MODERN_COURSE_COMPLETION`. |
| `certificate_activity_key` | Moodle course module idnumber, currently `course_completion_certificate`. |
| `certificate_section_number` | Target course section, currently `15`. |
| `certificate_download_mode` | `D` forces PDF download. |
| `certificate_verification_enabled` | `1` enables verification from certificate code/QR. |
| `certificate_unlock_activity_key` | Optional gate activity idnumber, default `final_project_portfolio`. |
| `certificate_filename_pattern` | PDF filename pattern used by Custom Certificate. |
| `school_name`, `board_name`, `principal_name` | Printed in the generated PDF layout. |
