# eLearn Mindset Docker Setup

Fresh local Moodle setup using Docker, PostgreSQL 16, and an official Moodle Git tag. The project keeps Moodle source in `moodle/` as a normal Git checkout so future upgrades follow the Moodle administrator workflow.

## What This Creates

- `moodle/`: official Moodle source checked out from `https://github.com/moodle/moodle.git`.
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

The install script enables the `almondb` theme with the eLearn Mindset logo palette and the My courses route at `http://localhost:8080/my/courses.php`.

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

- [Bulk users CSV](moodle/demo-data/indian-school/users.csv)
- [Bulk categories CSV](moodle/demo-data/indian-school/categories.csv)
- [Course and activity blueprint](moodle/demo-data/indian-school/course-activity-blueprint.md)

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

## Updating Moodle

This setup follows Moodle's Git administrator guidance: use official release tags and avoid `main` for production-style installs.

```bash
./scripts/update-moodle.sh v5.2.1
```

For a future patch release, replace `v5.2.1` with the new official tag, for example:

```bash
./scripts/update-moodle.sh v5.2.2
```

The update script takes a backup first, fetches tags, checks out the requested Moodle tag, runs Composer, runs Moodle's CLI upgrade, and purges caches.

Read the detailed update process in [docs/update.md](docs/update.md).

## Documentation

- [Operator runbook](docs/runbook.md)
- [Local setup](docs/setup.md)
- [Docker architecture](docs/docker.md)
- [CI/CD pipeline](docs/ci-cd.md)
- [Theme setup](docs/theme.md)
- [Moodle updates](docs/update.md)
- [Terraform infrastructure](terraform/README.md)

## CI Security And Renovate

The GitHub Actions pipeline now includes Renovate config validation, shell/YAML/Dockerfile/Terraform linting, Composer audit, Trivy filesystem/IaC scanning, and Trivy production image scanning.

Renovate runs on a weekday schedule from [.github/workflows/renovate.yml](.github/workflows/renovate.yml). Configure the `RENOVATE_TOKEN` repository secret so Renovate PRs can trigger the normal CI checks.

## Official References

- Moodle Git for Administrators: https://docs.moodle.org/502/en/Git_for_Administrators
- Moodle 5.2 release requirements: https://moodledev.io/general/releases/5.2
- Moodle PostgreSQL documentation: https://docs.moodle.org/en/PostgreSQL
