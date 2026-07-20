# Agent Instructions

## Scope
- Root repo tracks Docker, Terraform, docs, scripts, CI, and project Moodle customizations in `moodle-overrides/`.
- `moodle/` is an ignored nested checkout of official Moodle, currently `v5.2.1`; direct edits there are not source of truth unless captured into `moodle-overrides/`.
- Keep `.env`, `moodledata/`, `backups/`, `plugins/`, Terraform state, generated assets, vendored dependencies, and deployment state unmodified unless the user explicitly asks.
- Never print or commit secrets, salts, tokens, database credentials, WhatsApp credentials, backup contents, or demo passwords.
- Start with `docs/MOODLE_PROJECT_CONTEXT.md`, then `README.md`, `docs/setup.md`, `docs/docker.md`, `docs/runbook.md`, `docs/update.md`, `docs/upgrade-backup-restore.md`, `docs/ci-cd.md`, and `terraform/README.md`.

## Package Manager
- Root package manager: **pnpm** from `packageManager` in `package.json` and `pnpm-lock.yaml`.
- Moodle core toolchain: PHP `>=8.3`, Composer, and Node `>=22.11.0 <23`; Docker is the documented runtime path.

## File-Scoped Commands
| Task | Command |
|------|---------|
| Shell syntax | `bash -n path/to/script.sh` |
| PHP syntax, when PHP is available | `php -l path/to/file.php` |
| Terraform format | `terraform fmt -check path/to/file.tf` |
| Markdown links | `./scripts/validate-docs.sh` |

## Project Commands
| Task | Command |
|------|---------|
| Inspect root status | `git status --short --branch` |
| Inspect Moodle checkout | `git -C moodle describe --tags --always --dirty && git -C moodle status --porcelain=v1 -uno` |
| Validate docs | `./scripts/validate-docs.sh` |
| Validate shell scripts | `bash -n scripts/*.sh docker/moodle/*.sh` |
| Validate Compose config | `docker compose config --quiet` |
| Validate changed files | `pnpm validate` |

## Moodle Conventions
- Put project plugin/theme/code changes under `moodle-overrides/public/...`; use Moodle plugin APIs and preserve upstream Moodle core in `moodle/`.
- Plugin changes need the matching Moodle metadata: `version.php`, language strings, capabilities, privacy provider when personal data is involved, install/upgrade XML for DB changes, and focused tests where practical.
- DB schema changes require backup/upgrade/rollback planning; never apply direct database edits as a shortcut.
- Existing docs say the intended active theme is stock `boost`, but this checkout also contains `moodle-overrides/public/theme/custom_lms`; verify the desired policy before theme work.

## State-Changing Commands
- Run only when the user asks for that workflow: `make bootstrap`, `make sync-overrides`, `make install`, `make demo-data`, `make backup`, `make restore`, `make update`, `make reset-local`, `make up`, `make down`, `make build`, `make cron`.
- Upgrade work must follow `docs/update.md` and `docs/upgrade-backup-restore.md`.

## Commit Attribution
AI commits MUST include:
```text
Co-Authored-By: (the agent model's name and attribution byline)
```
