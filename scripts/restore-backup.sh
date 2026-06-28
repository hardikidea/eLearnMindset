#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

BACKUP_DIR="${1:-}"
CONFIRM="${2:-}"

if [ -z "${BACKUP_DIR}" ] || [ "${CONFIRM}" != "--yes" ]; then
    cat <<'USAGE'
Usage:
  ./scripts/restore-backup.sh backups/YYYYMMDD-HHMMSS --yes

This restores the local Docker Moodle stack from a backup created by scripts/backup.sh.
It stops Moodle, recreates the local PostgreSQL database, replaces moodledata/,
checks out the recorded Moodle tag when available, syncs moodle-overrides/, and purges caches.
USAGE
    exit 1
fi

if [ ! -f .env ]; then
    echo ".env is missing. Run ./scripts/bootstrap-moodle.sh first."
    exit 1
fi

if [ ! -d "${BACKUP_DIR}" ]; then
    echo "Backup directory not found: ${BACKUP_DIR}"
    exit 1
fi

if [ ! -f "${BACKUP_DIR}/postgres.sql" ]; then
    echo "Backup is missing postgres.sql: ${BACKUP_DIR}"
    exit 1
fi

set -a
# shellcheck disable=SC1091
source .env
set +a

POSTGRES_DB="${POSTGRES_DB:-moodle}"
POSTGRES_USER="${POSTGRES_USER:-moodle}"
MOODLE_REPO="${MOODLE_REPO:-https://github.com/moodle/moodle.git}"

recorded_tag=""
if [ -f "${BACKUP_DIR}/moodle-version.txt" ]; then
    recorded_tag="$(sed -n '1p' "${BACKUP_DIR}/moodle-version.txt" | tr -d '[:space:]')"
fi

if [ -n "${recorded_tag}" ] && [ "${recorded_tag#v}" != "${recorded_tag}" ]; then
    if [ ! -d moodle/.git ]; then
        ./scripts/bootstrap-moodle.sh
    fi

    git -C moodle remote set-url origin "${MOODLE_REPO}" || true
    git -C moodle fetch origin --tags --prune
    git -C moodle checkout "${recorded_tag}"

    tmpenv="$(mktemp)"
    awk -v target="${recorded_tag}" '
        BEGIN { done = 0 }
        /^MOODLE_VERSION=/ { print "MOODLE_VERSION=" target; done = 1; next }
        { print }
        END { if (done == 0) print "MOODLE_VERSION=" target }
    ' .env > "${tmpenv}"
    mv "${tmpenv}" .env
fi

./scripts/sync-moodle-overrides.sh

docker compose stop cron moodle || true
docker compose up -d db redis mailpit

for attempt in {1..30}; do
    if docker compose exec -T db pg_isready -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" >/dev/null 2>&1; then
        break
    fi

    if [ "${attempt}" -eq 30 ]; then
        echo "PostgreSQL did not become ready for restore."
        exit 1
    fi

    sleep 1
done

docker compose exec -T db psql -v ON_ERROR_STOP=1 -U "${POSTGRES_USER}" -d postgres -c \
    "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '${POSTGRES_DB}' AND pid <> pg_backend_pid();"
docker compose exec -T db dropdb --if-exists -U "${POSTGRES_USER}" --maintenance-db=postgres "${POSTGRES_DB}"
docker compose exec -T db createdb -U "${POSTGRES_USER}" --maintenance-db=postgres "${POSTGRES_DB}"
docker compose exec -T db psql -v ON_ERROR_STOP=1 -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" < "${BACKUP_DIR}/postgres.sql"

rm -rf moodledata
if [ -f "${BACKUP_DIR}/moodledata.tar.gz" ]; then
    tar -xzf "${BACKUP_DIR}/moodledata.tar.gz"
else
    mkdir -p moodledata
fi

docker compose up -d moodle
docker compose exec -T moodle composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
docker compose exec -T moodle php admin/cli/purge_caches.php || true
docker compose exec -T moodle php admin/cli/maintenance.php --disable || true
./scripts/configure-mailpit.sh || true
docker compose up -d cron

echo "Restore completed from ${BACKUP_DIR}"
