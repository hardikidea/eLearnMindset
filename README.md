# eLearn Mindset Docker Setup

Fresh local Moodle setup using Docker, PostgreSQL 16, and an official Moodle Git tag. The project keeps Moodle source in `moodle/` as a local Git checkout, while project-specific themes, plugins, scripts, and demo data are versioned in `moodle-overrides/`.

## What This Creates

- `moodle/`: official Moodle source checked out from `https://github.com/moodle/moodle.git`.
- `moodle-overrides/`: tracked project-specific Moodle files that are synced into `moodle/` after bootstrap and during production image builds.
- `moodledata/`: persistent Moodle uploaded files and cache data.
- `postgres_data`: Docker volume for PostgreSQL data.
- `moodle`: PHP 8.3 + PHP-FPM + Nginx application container.
- `db`: PostgreSQL 16 database container, available on host port `5440`.
- `redis`: optional Redis service for local cache testing.
- `mailpit`: local SMTP catcher at `http://localhost:8025`.
- `cron`: Moodle cron runner, every 60 seconds by default.

## Quick Start

```bash
cp .env.example .env
./scripts/bootstrap-moodle.sh
docker compose build
docker compose up -d
./scripts/install-site.sh
make demo-data
```

Open Moodle at `http://localhost:8080`.

PostgreSQL is available from the host at `127.0.0.1:5440` and from containers at `db:5432`.

The install script enables the `elearnboost` Boost child theme by default. It keeps Moodle's original Boost visual baseline and removes the constrained `.main-inner` and `.footer-popover` max-width so pages can use the full responsive workspace.

`./scripts/bootstrap-moodle.sh` automatically syncs `moodle-overrides/` into the local Moodle checkout. Re-apply the same sync manually with:

```bash
make sync-overrides
```

Install the local Git hooks once after cloning:

```bash
pnpm install
```

Husky requires Node.js 18+ to install. If `pnpm install` reports `env: node: No such file or directory`, install Node.js or make your existing Node binary available on `PATH`, then rerun the command.

The Husky pre-commit hook runs `sync-overrides` when the local Moodle checkout exists, blocks accidental commits of local runtime/state/secret files, and lints only files changed against `origin/main` or `main`. The pre-push hook runs dependency and vulnerability audits only for the changed files that need them.

MailPit is configured as Moodle's local SMTP server:

- SMTP from Moodle: `mailpit:1025`
- SMTP from host: `127.0.0.1:1025`
- Web UI: `http://localhost:8025`

Default local admin credentials are in `.env`:

- Username: `admin`
- Password: `Admin123!ChangeMe`

Change these values before installing if this will be shared with anyone.

## Indian School Demo Data

Seed an eLearn Mindset demo setup for Primary School and Higher Secondary School:

```bash
make demo-data
```

This creates the requested category hierarchy, Class 1/Class 3/Class 11 courses, Indian K-12 activity shells, Principal/IT/teacher/student users, and enrolments. The command is idempotent and can be rerun after local updates.

Demo package files:

- [Bulk users CSV](moodle-overrides/demo-data/indian-school/users.csv)
- [Bulk categories CSV](moodle-overrides/demo-data/indian-school/categories.csv)
- [Course and activity blueprint](moodle-overrides/demo-data/indian-school/course-activity-blueprint.md)

All seeded users use this demo password:

```text
SchoolDemo2026!
```

## Start And Stop Commands

```bash
docker compose up -d           # start all services
docker compose down            # stop services, keep volumes/data
docker compose down -v         # stop services and delete Docker volumes
docker compose restart         # restart all services
docker compose restart moodle  # restart only the web container
make start                     # same as docker compose up -d
make stop                      # same as docker compose down
make restart                   # restart Moodle web + cron
make status                    # show container status
make configure-mailpit         # apply Moodle SMTP settings for MailPit
make sync-overrides            # copy tracked Moodle customizations into moodle/
```

## Daily Commands

```bash
docker compose logs -f moodle  # app logs
docker compose exec moodle bash
docker compose exec db psql -U moodle -d moodle
make cron                      # run cron once
make backup                    # database + moodledata backup
make configure-mailpit         # re-apply MailPit SMTP config
```

## Git Hooks

Requires Node.js 18+ and pnpm.

```bash
pnpm install       # install dependencies and enable Husky hooks
pnpm precommit    # run the same checks as the pre-commit hook
pnpm prepush      # run the same checks as the pre-push hook
pnpm validate     # run pre-commit and pre-push checks
```

Commit checks:

- Sync `moodle-overrides/` into the ignored local `moodle/` checkout when `moodle/` exists.
- Block staged runtime, state, or secret files such as `.env`, `moodle/`, `moodledata/`, `backups/`, `plugins/`, and Terraform state.
- Lint only files changed against `origin/main` or `main`: shell, JSON, YAML, PHP, Terraform, Dockerfiles, Docker Compose, and Renovate config when the matching tools are installed.

Push checks:

- Audit only files changed against `origin/main` or `main`.
- Run `pnpm audit --prod` when root Node dependency files changed.
- Run `npm audit --omit=dev` for changed package-lock based packages.
- Run `composer audit` for changed Composer lockfile based packages.
- Run Trivy through local `trivy` or Docker for changed dependency, Docker, Terraform, Compose, and workflow files.

Keep project Moodle customizations in `moodle-overrides/` so upgrades can safely replace the upstream Moodle checkout. Run `git fetch origin main` before a large branch push if your local `origin/main` is stale.

## Updating Moodle

This setup follows Moodle's Git administrator guidance: use official release tags and avoid `main` for production-style installs.

```bash
./scripts/update-moodle.sh v5.2.1
```

For a future patch release, replace `v5.2.1` with the new official tag, for example:

```bash
./scripts/update-moodle.sh v5.2.2
```

The update script takes a backup first, fetches tags, checks out the requested Moodle tag, syncs `moodle-overrides/`, runs Composer, runs Moodle's CLI upgrade, and purges caches.

If an upgrade fails after the backup is created, restore the local Docker stack with:

```bash
./scripts/restore-backup.sh backups/YYYYMMDD-HHMMSS --yes
```

For local upgrade testing only, rollback can be attempted automatically:

```bash
./scripts/update-moodle.sh --restore-on-fail v5.2.2
```

Read the detailed update process in [docs/update.md](docs/update.md).

For AWS environments, use the manual GitHub Actions workflows:

- `Moodle Version Upgrade`: tag-based server upgrade with backup, deploy, Moodle CLI upgrade, and cron restart.
- `Server Backup`: RDS snapshot plus EFS AWS Backup recovery point.
- `Server Restore`: guarded ECS rollback and restore-point validation.

The server restore workflow intentionally does not replace production RDS/EFS in place. Use the full [upgrade, backup, and restore runbook](docs/upgrade-backup-restore.md) for data restore cutover steps.

Production images are published to GHCR by default:

```text
ghcr.io/hardikidea/elearnmindset
```

## Documentation

- [Operator runbook](docs/runbook.md)
- [Deployment preparation guide](docs/deployment-preparation.md)
- [AWS monthly cost estimate in INR](docs/aws-monthly-cost-estimate-inr.md)
- [Indian school product pricing model](docs/indian-school-product-pricing.md)
- [AWS architecture blueprint](docs/aws-architecture.md)
- [Local setup](docs/setup.md)
- [Docker architecture](docs/docker.md)
- [CI/CD pipeline](docs/ci-cd.md)
- [Pipeline onboarding and troubleshooting](docs/pipeline-onboarding.md)
- [Theme setup](docs/theme.md)
- [Moodle updates](docs/update.md)
- [Upgrade, backup, and restore runbook](docs/upgrade-backup-restore.md)
- [Architecture Decision Records](docs/adr/README.md)
- [Terraform infrastructure](terraform/README.md)

## CI Security And Renovate

The GitHub Actions Moodle Delivery Pipeline now uses reusable composite actions for source integrity, static quality, documentation validation, supply-chain security, image packaging, local/remote smoke checks, Terraform plan/apply, ECS web/worker stabilization, production smoke validation, GHCR publishing, and the `prod-approval` environment gate.

The `Infrastructure Drift Detection` workflow runs scheduled and manual refresh-only Terraform drift checks for dev, stage, and prod.

Renovate runs on a weekday schedule from [.github/workflows/renovate.yml](.github/workflows/renovate.yml). Configure the `RENOVATE_TOKEN` repository secret so Renovate PRs can trigger the normal CI checks.

## Official References

- Moodle Git for Administrators: https://docs.moodle.org/502/en/Git_for_Administrators
- Moodle 5.2 release requirements: https://moodledev.io/general/releases/5.2
- Moodle PostgreSQL documentation: https://docs.moodle.org/en/PostgreSQL
