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

TARGET_VERSION="${1:-${MOODLE_VERSION:-}}"
MOODLE_REPO="${MOODLE_REPO:-https://github.com/moodle/moodle.git}"

if [ -z "${TARGET_VERSION}" ]; then
    echo "Usage: ./scripts/update-moodle.sh v5.2.x"
    exit 1
fi

if [ ! -d moodle/.git ]; then
    echo "moodle/ Git checkout is missing. Run ./scripts/bootstrap-moodle.sh first."
    exit 1
fi

git ls-remote --exit-code --tags --refs "${MOODLE_REPO}" "${TARGET_VERSION}" >/dev/null

./scripts/backup.sh

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
