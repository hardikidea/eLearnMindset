#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

if [ -f .env ]; then
    set -a
    # shellcheck disable=SC1091
    source .env
    set +a
fi

POSTGRES_DB="${POSTGRES_DB:-moodle}"
POSTGRES_USER="${POSTGRES_USER:-moodle}"

STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="${ROOT_DIR}/backups/${STAMP}"
mkdir -p "${BACKUP_DIR}"

docker compose up -d db

for attempt in {1..30}; do
    if docker compose exec -T db pg_isready -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" >/dev/null; then
        break
    fi

    if [ "${attempt}" -eq 30 ]; then
        echo "PostgreSQL did not become ready for backup."
        exit 1
    fi

    sleep 1
done

docker compose exec -T db pg_dump -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" > "${BACKUP_DIR}/postgres.sql"

if [ -d moodledata ]; then
    tar -czf "${BACKUP_DIR}/moodledata.tar.gz" moodledata
fi

if [ -d moodle/.git ]; then
    {
        git -C moodle describe --tags --always --dirty || true
        git -C moodle log --oneline -1 public/version.php || true
    } > "${BACKUP_DIR}/moodle-version.txt"
fi

echo "Backup written to ${BACKUP_DIR}"
