# Moodle Project Context

Last verified: 2026-07-20, Asia/Kolkata.

This document captures static onboarding context for the eLearn Mindset Moodle repository. It is based on file and Git inspection only. No services were started, no database was inspected, no package install or upgrade was run, no Moodle cache was purged, no cron job was run, and no network-dependent checks were executed.

## Snapshot

- Root repository: `/Users/hardik.chauhan/Documents/learning/eLearnMindset`
- Root branch: `main`
- Root HEAD: `e84910ab2aa2084706f35b76f987a0e1e0a67edf`
- Root remote: `origin git@github-personal:hardikidea/eLearnMindset.git`
- Root status at onboarding: clean
- Nested Moodle checkout: `moodle/`, ignored by the root repo and not a Git submodule
- Moodle checkout HEAD: `63e16b757ca8fee05b672a27c23ee27cc8f9fabb`
- Moodle tag: `v5.2.1`
- Moodle release from `moodle/public/version.php`: `5.2.1 (Build: 20260608)`
- Moodle branch from `version.php`: `502`

## Hard Boundaries

- The authoritative project Moodle customizations live in `moodle-overrides/`.
- The local `moodle/` directory is an ignored official Moodle checkout. Direct edits there are easy to lose and are not tracked by the root repository.
- `.env`, `moodledata/`, `backups/`, `plugins/`, Terraform state, dependency directories, generated assets, and runtime state are ignored or operationally sensitive.
- Secrets must stay out of docs, logs, commits, screenshots, and summaries. This includes database passwords, Moodle admin passwords, salts, WhatsApp access tokens, API keys, backup contents, and demo passwords.

## Repository Map

| Path | Role |
|------|------|
| `README.md` | Project overview and primary setup links |
| `docs/` | Setup, Docker, runbooks, CI/CD, upgrade, deployment, AWS architecture, ADRs |
| `docker-compose.yml` | Local Moodle/PostgreSQL/Redis/MailPit stack |
| `docker/moodle/` | Moodle PHP-FPM/Nginx image, config template, entrypoint, cron loop |
| `scripts/` | Bootstrap, sync, backup, restore, update, install, demo-data, validation helpers |
| `moodle/` | Ignored official Moodle source checkout, currently `v5.2.1` |
| `moodle-overrides/` | Tracked source of project Moodle plugins, themes, scripts, and demo data |
| `terraform/` | AWS bootstrap, environments, modules, and delivery infrastructure |
| `.github/` | CI/CD workflows and reusable composite actions |
| `plugins/` | Ignored local plugin ZIPs/assets; do not treat as source |
| `backups/` | Ignored local backup output |
| `moodledata/` | Ignored Moodle dataroot/runtime file storage |

## Moodle Runtime Baseline

- PHP requirement from Moodle Composer metadata: `>=8.3.0`.
- Moodle Node requirement from `moodle/package.json`: `>=22.11.0 <23`.
- `.nvmrc` in Moodle core points to `lts/jod`.
- Root project `package.json` uses `pnpm@11.7.0`.
- Local shell inspection found `php` and `node` unavailable on `PATH`; Docker is the documented path for runtime commands.
- Moodle web root follows Moodle 5.1+ layout: `/var/www/moodle/public`.
- Moodle dataroot is `/var/www/moodledata`.
- Local database type is PostgreSQL via Moodle `pgsql`.

## Local Stack

`docker-compose.yml` defines:

- `moodle`: PHP-FPM/Nginx Moodle container built from `docker/moodle/Dockerfile`.
- `cron`: same image running the Moodle cron loop.
- `db`: PostgreSQL 16 Bookworm.
- `redis`: Redis 7 Bookworm.
- `mailpit`: local SMTP testing.

Local ports are controlled by `.env` values with defaults from `.env.example`, including Moodle on localhost port `8080`, PostgreSQL on localhost port `5440`, Redis on `6379`, and MailPit SMTP/UI ports. Password-like values exist in `.env.example`; do not quote or reuse them in public output.

## Docker Image Behavior

- Base image: `php:8.3-fpm-bookworm`.
- Installs Nginx, PostgreSQL client tools, Git, Composer, and PHP extensions needed by Moodle.
- Uses build arg `MOODLE_VERSION`, currently defaulting to `v5.2.1`.
- Can clone official Moodle during image build when `INCLUDE_MOODLE_SOURCE=true`.
- Copies `moodle-overrides/` into the image after Moodle source is present.
- Entrypoint prepares dataroot, ensures `config.php`, can run Composer install when configured, then starts PHP-FPM and Nginx.
- Cron mode loops `admin/cli/cron.php` every `MOODLE_CRON_INTERVAL` seconds.

## Configuration Model

- `docker/moodle/config.php` is the config template.
- `moodle/config.php` matched the Docker template during inspection, but `moodle/` is ignored runtime state.
- Moodle database, wwwroot, dataroot, theme, reverse proxy, and mail settings are environment-driven.
- The template sets `$CFG->admin = 'admin'` and `$CFG->routerconfigured = true`.
- Production secrets are expected to come from managed secret stores, not from committed files.

## Source Flow

Typical source flow:

1. Clone or update official Moodle into ignored `moodle/`.
2. Sync `moodle-overrides/` into `moodle/` for local development.
3. Capture intentional Moodle customizations back into `moodle-overrides/`.
4. Build the deployment image from the official Moodle tag plus overrides.

Relevant scripts:

- `scripts/bootstrap-moodle.sh`: creates local env if missing, clones/checks out Moodle, syncs overrides.
- `scripts/sync-moodle-overrides.sh`: copies tracked overrides into the ignored Moodle checkout.
- `scripts/capture-moodle-override.sh`: captures a path from `moodle/` back into `moodle-overrides/`.
- `scripts/pre-commit-check.sh`: blocks accidentally staged ignored/runtime paths and runs changed-file checks.

These scripts are state-changing. Do not run them during read-only analysis.

## Safe Inspection Commands

These are read-only or validation-only in normal use:

```bash
git status --short --branch
git -C moodle describe --tags --always --dirty
git -C moodle status --porcelain=v1 -uno
git submodule status --recursive
./scripts/validate-docs.sh
bash -n scripts/*.sh docker/moodle/*.sh
docker compose config --quiet
terraform fmt -check -recursive terraform
```

Do not run install, upgrade, backup, restore, cron, cache purge, service startup, or network audits unless that is the requested task.

## State-Changing Commands

These are documented project workflows, but they change local state or external state:

- `make bootstrap`
- `make sync-overrides`
- `make capture-override RELPATH=...`
- `make build`
- `make up` / `make start`
- `make down` / `make stop`
- `make install`
- `make configure-mailpit`
- `make demo-data`
- `make theme-install`
- `make cron`
- `make backup`
- `make restore BACKUP_DIR=...`
- `make reset-local`
- `make update`
- `make update-restore-on-fail`

## Custom Moodle Inventory

The following inventory is from `moodle-overrides/public/...`, not from the installed database.

### Admin Tools

| Plugin | Path | Version / Release | Summary | Notes |
|--------|------|-------------------|---------|-------|
| `tool_courserating` | `admin/tool/courserating` | `2026041700`, release `4.5.1`, supported `[405,502]` | Student course ratings/reviews, flags, summaries, reportbuilder integration | Has DB tables, capabilities, event observers, AJAX service, privacy provider, AMD/templates, Behat/PHPUnit tests |
| `tool_datasetup` | `admin/tool/datasetup` | No manifest observed | Empty placeholder directory | Treat as incomplete, not an installable plugin |

`tool_courserating` creates rating, flag, and summary tables; adds or updates an average-rating custom course field; and exposes capabilities for rating, deleting, and reports.

### Blocks

| Plugin | Path | Version / Release | Summary | Notes |
|--------|------|-------------------|---------|-------|
| `block_dash` | `blocks/dash` | `2026032400`, release `2.6`, supported `[405,501]` | Dashboard/page builder with data sources, widgets, catalogs, groups, reports | Supported range stops before Moodle `502`; no privacy provider was found during inspection |
| `block_whatsapp_messenger` | `blocks/whatsapp_messenger` | `2026051200`, release `1.3.1` | WhatsApp Business API messaging from course context | Stores API credentials in plugin settings, logs messages, has privacy provider, no tests found |

`block_whatsapp_messenger` uses Moodle user phone fields and has a send capability whose archetypes include student. Review the permission model before enabling broadly.

### Course Formats

| Plugin | Path | Version / Release | Summary | Notes |
|--------|------|-------------------|---------|-------|
| `format_designer` | `course/format/designer` | `2026040300`, release `1.7`, supported `[404,501]` | Section and activity layout designer | Supported range stops before Moodle `502`; has DB table, cache, file areas, services, observers, privacy provider, tests |
| `format_remuiformat` | `course/format/remuiformat` | `2026042200`, release `4.1.21` | Edwiser card/list course format | Has DB tables, services, event observer, privacy provider, AMD/templates, many language packs |

### Activity Modules

| Plugin | Path | Version / Release | Summary | Notes |
|--------|------|-------------------|---------|-------|
| `mod_interactivevideo` | `mod/interactivevideo` | `2026062401`, release `1.9.1`, supported `[400,502]` | Interactive video/audio, annotations, completion, reports, media providers | Has DB tables, caches, mobile handler, privacy provider, file areas, bundled JS libraries; no tests found |
| `mod_certificatebeautiful` | `mod/certificatebeautiful` | `2026042700`, release `3.2.5` | Certificate designer/editor/export to PDF | Large vendored tree and assets; scheduled auto-issue task; privacy provider; no tests found |
| `mod_customcert` | `mod/customcert` | `2026042003`, release `5.2.2` | Custom PDF certificates | Moodle `5.2` compatible; extensive capabilities, services, scheduled task, file areas, tests, element privacy providers |
| `mod_hvp` | `mod/hvp` | `2026062500`, release `1.28.2` | H5P activity module | Many H5P DB tables, scheduled tasks, mobile handler, privacy provider, content hub/update integrations; no tests found |

### Activity Subplugins

- `mod_interactivevideo` subplugins under `mod/interactivevideo/plugins`: `chapter`, `contentbank`, `iframe`, `richtext`, `skipsegment`.
- Most InteractiveVideo subplugins declare support through Moodle `501`, while the parent declares support through `502`.
- `mod_customcert` element subplugins under `mod/customcert/element`: `border`, `studentname`, `userfield`, `coursename`, `bgimage`, `userpicture`, `date`, `digitalsignature`, `code`, `grade`, `image`, `qrcode`, `categoryname`, `teachername`, `gradeitemname`, `text`, `expiry`, `coursefield`.
- `mod_certificatebeautiful` data-info subplugins under `mod/certificatebeautiful/plugins_datainfo`: course, user, grade, site, custom fields, teachers, enrolments, issue, and related data sources.

### Themes and Local Placeholders

| Plugin | Path | Version | Summary | Notes |
|--------|------|---------|---------|-------|
| `theme_custom_lms` | `theme/custom_lms` | `2026072015` | Custom LMS theme and role-flex page bundle | Exists despite docs saying stock `boost` only; no release/maturity/supported line observed |
| `theme_drona` | `theme/drona` | No manifest observed | Placeholder directory | Not installable as-is |
| `theme_whitelabel` | `theme/whitelabel` | No manifest observed | Placeholder directory | Not installable as-is |
| `local_schoollanding` | `local/schoollanding` | No manifest observed | Placeholder directory | Not installable as-is |

`docs/theme.md` says the active policy is stock `boost` and no custom project themes under `moodle-overrides/public/theme/`. The repository currently contains `theme_custom_lms`, plus empty theme placeholders. Treat this as an unresolved documentation/source contradiction.

## Demo Data

- `moodle-overrides/scripts/seed_indian_school_demo.php` seeds Indian-school demo categories, courses, users, role assignments, enrolments, and activity shells.
- Data files live under `moodle-overrides/demo-data/indian-school/`.
- The script and CSVs contain local demo credentials and user data. Do not quote credentials in docs, logs, or summaries.
- The seeding workflow starts services and writes to the Moodle database, so it is not part of read-only onboarding.

## External Integrations

- WhatsApp Business API through `block_whatsapp_messenger`.
- H5P hub/content hub/update/usage-stat integrations through `mod_hvp`.
- InteractiveVideo media providers and oEmbed/video integrations such as YouTube, Vimeo, HLS/DASH, and related providers.
- MailPit for local SMTP testing.
- GHCR for container images.
- GitHub Actions OIDC for AWS deployment.
- AWS ECS Fargate, ALB, RDS PostgreSQL 16, EFS, ElastiCache Redis, Secrets Manager, CloudWatch, Route53/ACM optional.

## CI/CD

Primary workflow: `.github/workflows/ci_cd_pipeline.yml`.

Observed jobs and actions cover:

- Change eligibility and deployment gating.
- Moodle source integrity and exact official tag checkout.
- Static quality gates for shell, YAML, Dockerfile, GitHub Actions, Terraform, Renovate config, and Compose config.
- Documentation link validation through `./scripts/validate-docs.sh`.
- Composer audit and Trivy scans.
- Moodle application and worker image builds.
- Local integration smoke against Docker Compose.
- Release metadata and GHCR release candidate publishing.
- Terraform plan/apply for dev, stage, and prod.
- ECS stabilization and smoke checks.

The extended PHPUnit/Behat jobs are placeholders in the current workflow. They create skipped JUnit metadata rather than running the actual Moodle test suites.

## Deployment Model

The documented target is AWS:

- ECS Fargate service for Moodle web.
- Separate ECS cron service.
- Public Application Load Balancer.
- RDS PostgreSQL 16.
- EFS for `moodledata`.
- ElastiCache Redis.
- Secrets Manager for sensitive settings.
- CloudWatch logs and alarms.
- Optional Route53/ACM.
- Terraform bootstrap plus environment stacks under `terraform/`.

There is no SSH-first server management path in the docs. Production operations should use GitHub Actions, Terraform, AWS APIs, ECS Exec, RDS/EFS restore paths, and documented runbooks.

## Upgrade and Rollback Rules

- Use official Moodle tags only.
- Back up code, database, and `moodledata` before upgrades.
- Put the site into maintenance mode and stop or scale down cron during upgrade.
- Deploy with cron disabled, run Moodle CLI upgrade, purge caches, then restart cron.
- After a schema upgrade, image rollback alone is not enough. Roll back the matching database and EFS state as documented.
- `scripts/update-moodle.sh` is intentionally stateful and network-dependent; do not run it during analysis.

## Security and Privacy Notes

- `.gitignore` blocks `.env`, runtime data, backups, local plugin artifacts, Terraform state, and the ignored Moodle checkout.
- `scripts/pre-commit-check.sh` blocks staged secrets/runtime paths and runs changed-file validation.
- `docs/runbook.md` records a current Composer audit concern as of 2026-06-25, including a high-severity advisory affecting `aws/aws-sdk-php`. This onboarding pass did not re-run network-dependent audits.
- `block_whatsapp_messenger` handles phone numbers, outbound message content, API credentials, and message logs.
- `mod_customcert` services can expose certificate issue data and, depending on parameters and permissions, PDF/base64 content and user email fields.
- `mod_hvp` has external hub/update/content integrations and scheduled cleanup/update tasks.
- `block_dash` appears to work with personal, group, and course data, but no privacy provider was found during static inspection.
- `mod_certificatebeautiful` bundles a large vendor/assets tree with mixed license indicators; complete license and security review is recommended before production reliance.

## Theme Status

- Documented intended active theme: stock `boost`.
- Actual override inventory: `theme_custom_lms` exists and appears substantial.
- `theme_custom_lms` describes itself as a Boost-based custom LMS theme with Drona role-flex HTML bundle pages rendered through Moodle Mustache/PHP routes.
- Its `config.php` declares custom layouts, renderer factory behavior, course index support, and file settings for branding assets.
- This contradiction needs a maintainer decision before theme cleanup, activation, or upgrade work.

## Testing Status

Observed static test coverage:

- `tool_courserating`: PHPUnit/Behat coverage present.
- `format_designer`: PHPUnit/Behat coverage present.
- `mod_customcert`: extensive PHPUnit/Behat coverage present, including element privacy providers.
- `theme_custom_lms`: tests copied from Boost plus custom pages.
- `block_dash`: tests and Behat features present, but privacy provider not found.
- `block_whatsapp_messenger`: no tests found.
- `mod_interactivevideo`: no tests found.
- `mod_certificatebeautiful`: no tests found.
- `mod_hvp`: no tests found.
- CI placeholder extended tests do not currently execute Moodle PHPUnit/Behat suites.

No tests were run during this onboarding pass.

## Ten Important Facts

1. The root repo is the project source of truth; `moodle/` is ignored upstream Moodle source.
2. The Moodle version currently checked out is `v5.2.1` / branch `502`.
3. Project custom Moodle code is tracked under `moodle-overrides/`.
4. Local runtime is Docker Compose with Moodle, cron, PostgreSQL 16, Redis, and MailPit.
5. Production target is AWS ECS Fargate plus RDS, EFS, Redis, Secrets Manager, CloudWatch, GHCR, and Terraform.
6. The active theme docs say stock `boost`, but `theme_custom_lms` exists and is substantial.
7. Several plugins declare support only through Moodle `501` while the current Moodle branch is `502`.
8. Secrets and runtime data are intentionally ignored and must not be surfaced.
9. Composer audit concerns are already documented in the runbook and were not re-run here.
10. CI has strong static and image/deploy gates, but full PHPUnit/Behat jobs are placeholders.

## Highest-Risk Unknowns

- Whether the installed database currently enables any of the custom plugins or `theme_custom_lms`.
- Whether Moodle cache stores are configured to use Redis at runtime.
- Whether `block_dash`, `format_designer`, and InteractiveVideo subplugins are fully compatible with Moodle `5.2.1`.
- Whether `block_whatsapp_messenger:sendmessage` should really be granted to students.
- Whether `mod_certificatebeautiful` bundled assets and licenses are acceptable for production.
- Whether current production/stage GitHub and AWS secrets match the documented Terraform assumptions.
- Whether local backup files contain sensitive production-like data.
- Whether the placeholder plugin/theme directories are intentional future work or stale artifacts.

## Suggested Next Prompts

- "Validate Moodle 5.2 compatibility for the custom plugins without changing files."
- "Review `theme_custom_lms` versus `docs/theme.md` and propose a source-of-truth decision."
- "Audit privacy providers and capabilities for the custom plugins."
- "Turn the placeholder PHPUnit/Behat CI jobs into real Moodle test runs."
- "Review `mod_certificatebeautiful` third-party licenses and vendored dependencies."
