# Docker Architecture

This setup intentionally follows the same broad local-development pattern as the referenced CourseCloud Docker setup: separate infrastructure services, health checks, mounted source/data directories, and a PHP-FPM + Nginx app container.

It does not reuse CourseCloud application code or database assumptions.

## Services

| Service | Purpose | Notes |
| --- | --- | --- |
| `moodle` | PHP-FPM + Nginx web runtime | Serves `/var/www/moodle/public`, as required for Moodle 5.1+. |
| `cron` | Moodle cron loop | Runs `admin/cli/cron.php` every `MOODLE_CRON_INTERVAL` seconds. |
| `db` | PostgreSQL 16 | Moodle 5.2 requires PostgreSQL 16 minimum. It is available to containers as `db:5432` and to the host as `127.0.0.1:5440`. |
| `redis` | Optional cache backend | Available at `redis:6379` if you configure Moodle cache stores. |
| `mailpit` | Local email catcher | SMTP at `mailpit:1025`, host SMTP at `127.0.0.1:1025`, UI at `http://localhost:8025`. |

## Persistent Data

| Path or Volume | Purpose |
| --- | --- |
| `./moodle` | Local Moodle Git checkout and `config.php`. It is ignored by the root repository. |
| `./moodle-overrides` | Tracked Moodle themes, plugins, scripts, and demo data copied into `./moodle`. |
| `./moodledata` | Moodle data directory. Back this up before upgrades. |
| `postgres_data` | PostgreSQL Docker volume. Back this up before upgrades. |

## Web Root

Moodle 5.1 and later should not expose the full Git checkout as the web root. Nginx is configured with:

```nginx
root /var/www/moodle/public;
```

This follows the Moodle Git administrator guidance for newer Moodle versions.

## PHP Runtime

The image uses PHP 8.3 because Moodle 5.2 requires PHP 8.3 or PHP 8.4. Installed extensions include the PostgreSQL driver, GD, Intl, Mbstring, SOAP, Zip, Opcache, Exif, and Redis.

Important PHP settings are in [docker/moodle/php.ini](docker/moodle/php.ini).

## Config Generation

On first container start, [docker/moodle/entrypoint.sh](docker/moodle/entrypoint.sh) copies a local `config.php` into `moodle/config.php` if one does not already exist. The config keeps `$CFG->routerconfigured = true`, and Nginx falls back missing paths to `r.php` so Moodle's clean router endpoints work.

The generated config reads database and URL values from environment variables, so `.env` remains the main local configuration file.

`MOODLE_REVERSEPROXY=false` is used for this plain HTTP local stack. Nginx listens on container ports `80` and `8080` so Moodle CLI health checks can reach the same `MOODLE_WWWROOT` URL from inside the container.

## Moodle Overrides

The root repository does not commit the local `moodle/` checkout. Project-specific Moodle code lives in `moodle-overrides/` and is copied into `moodle/` by:

```bash
./scripts/sync-moodle-overrides.sh
```

`./scripts/bootstrap-moodle.sh` and `./scripts/update-moodle.sh` run this sync automatically. Production Docker builds also copy `moodle-overrides/` into the image after cloning the official Moodle tag.

## Local Ports

| Service | Host URL or Address | Container Address |
| --- | --- | --- |
| Moodle web | `http://localhost:8080` | `moodle:80`, `moodle:8080` |
| PostgreSQL | `127.0.0.1:5440` | `db:5432` |
| Redis | `127.0.0.1:6379` | `redis:6379` |
| MailPit UI | `http://localhost:8025` | `mailpit:8025` |
| MailPit SMTP | `127.0.0.1:1025` | `mailpit:1025` |

## MailPit SMTP

MailPit settings are controlled by `.env`:

```bash
MAILPIT_SMTP_PORT=1025
MAILPIT_UI_PORT=8025
MOODLE_SMTP_HOSTS=mailpit:1025
MOODLE_NOREPLY_ADDRESS=noreply@example.local
```

Apply or re-apply Moodle SMTP settings with:

```bash
make configure-mailpit
```
