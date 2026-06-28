# Local Setup

## Requirements

- Docker Desktop or Docker Engine with Compose v2.
- Git.
- At least 5 GB free disk space for the local Moodle checkout, Docker images, PostgreSQL data, and `moodledata`.

## 1. Create Local Environment File

```bash
cp .env.example .env
```

Review `.env` before first install. At minimum, confirm:

```bash
MOODLE_VERSION=v5.2.1
MOODLE_HTTP_PORT=8080
MOODLE_WWWROOT=http://localhost:8080
POSTGRES_PORT=5440
POSTGRES_PASSWORD=moodle_dev_password
MOODLE_ADMIN_PASSWORD=Admin123!ChangeMe
```

## 2. Bootstrap Moodle Source

```bash
./scripts/bootstrap-moodle.sh
```

This clones the official Moodle repository into `moodle/` and checks out the configured release tag.

## 3. Build and Start Docker

```bash
docker compose build
docker compose up -d
```

Check service status:

```bash
docker compose ps
```

## 4. Install Moodle Database

```bash
./scripts/install-site.sh
```

The script uses the admin values in `.env` and runs Moodle's CLI database installer.

## 5. Open Moodle

Open:

```text
http://localhost:8080
```

MailPit is available at:

```text
http://localhost:8025
```

Moodle is configured to send local email through MailPit:

```text
mailpit:1025
```

To re-apply MailPit SMTP settings after a reset or config change:

```bash
make configure-mailpit
```

PostgreSQL is available from your host at:

```text
127.0.0.1:5440
```

Inside Docker, Moodle still connects to PostgreSQL at `db:5432`.

## Start And Stop Commands

```bash
docker compose up -d           # start all services
docker compose down            # stop services, keep volumes/data
docker compose down -v         # stop services and delete Docker volumes
docker compose restart         # restart all services
docker compose restart moodle  # restart only Moodle web
make start                     # start all services
make stop                      # stop all services
make status                    # show service status
```

## Reset Everything

This deletes the local database volume and Moodle files. Use only for a fresh local reset.

```bash
docker compose down -v
rm -rf moodle moodledata backups
./scripts/bootstrap-moodle.sh
docker compose build
docker compose up -d
./scripts/install-site.sh
```
