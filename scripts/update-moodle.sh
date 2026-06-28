#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

if [ ! -f .env ]; then
    echo ".env is missing. Run ./scripts/bootstrap-moodle.sh first."
    exit 1
fi

set -a
# shellcheck disable=SC1091
source .env
set +a

RESTORE_ON_FAIL=0
if [ "${1:-}" = "--restore-on-fail" ]; then
    RESTORE_ON_FAIL=1
    shift
fi

TARGET_VERSION="${1:-${MOODLE_VERSION:-}}"
MOODLE_REPO="${MOODLE_REPO:-https://github.com/moodle/moodle.git}"
BACKUP_DIR=""

on_error() {
    exit_code=$?
    echo "Moodle update failed."
    if [ -n "${BACKUP_DIR}" ]; then
        echo "Backup is available at: ${BACKUP_DIR}"
        echo "Restore manually with:"
        echo "  ./scripts/restore-backup.sh \"${BACKUP_DIR}\" --yes"

        if [ "${RESTORE_ON_FAIL}" -eq 1 ]; then
            echo "Attempting automatic restore from ${BACKUP_DIR}"
            ./scripts/restore-backup.sh "${BACKUP_DIR}" --yes || true
        fi
    else
        echo "No restore backup was created by this run. The failure happened before backup completed."
    fi
    exit "${exit_code}"
}

trap on_error ERR

if [ -z "${TARGET_VERSION}" ]; then
    echo "Usage: ./scripts/update-moodle.sh [--restore-on-fail] v5.2.x"
    exit 1
fi

if [ ! -d moodle/.git ]; then
    echo "moodle/ Git checkout is missing. Run ./scripts/bootstrap-moodle.sh first."
    exit 1
fi

git ls-remote --exit-code --tags --refs "${MOODLE_REPO}" "${TARGET_VERSION}" >/dev/null

backup_marker="$(mktemp)"
BACKUP_DIR_FILE="${backup_marker}" ./scripts/backup.sh
BACKUP_DIR="$(cat "${backup_marker}")"
rm -f "${backup_marker}"
echo "Pre-upgrade backup: ${BACKUP_DIR}"

if docker compose ps --status running --quiet moodle | grep -q .; then
    docker compose exec -T moodle php admin/cli/maintenance.php --enable || true
fi

git -C moodle fetch origin --tags --prune
git -C moodle checkout "${TARGET_VERSION}"
./scripts/sync-moodle-overrides.sh

tmpenv="$(mktemp)"
awk -v target="${TARGET_VERSION}" '
    BEGIN { done = 0 }
    /^MOODLE_VERSION=/ { print "MOODLE_VERSION=" target; done = 1; next }
    { print }
    END { if (done == 0) print "MOODLE_VERSION=" target }
' .env > "${tmpenv}"
mv "${tmpenv}" .env

docker compose up -d db redis mailpit moodle
docker compose exec -T moodle composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
docker compose exec -T moodle php admin/cli/upgrade.php --non-interactive
docker compose exec -T moodle php admin/cli/purge_caches.php
docker compose exec -T moodle php admin/cli/maintenance.php --disable || true
docker compose up -d cron

git -C moodle log --oneline -1 public/version.php
echo "Moodle updated to ${TARGET_VERSION}"
echo "Rollback backup retained at ${BACKUP_DIR}"
