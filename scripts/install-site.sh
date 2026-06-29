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

POSTGRES_DB="${POSTGRES_DB:-moodle}"
POSTGRES_USER="${POSTGRES_USER:-moodle}"
MOODLE_DB_PREFIX="${MOODLE_DB_PREFIX:-mdl_}"
MOODLE_THEME="${MOODLE_THEME:-elearnboost}"
export MOODLE_RUNTIME_UID="${MOODLE_RUNTIME_UID:-$(id -u)}"
export MOODLE_RUNTIME_GID="${MOODLE_RUNTIME_GID:-$(id -g)}"

docker compose up -d db redis mailpit moodle

CONFIG_TABLE="${MOODLE_DB_PREFIX}config"
if docker compose exec -T db psql -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" -tAc "select to_regclass('${CONFIG_TABLE}')" | grep -q "${CONFIG_TABLE}"; then
    echo "Moodle database already appears to be installed."
    ./scripts/configure-mailpit.sh
    docker compose up -d cron
    exit 0
fi

docker compose exec -T moodle php admin/cli/install_database.php \
    --agree-license \
    --adminuser="${MOODLE_ADMIN_USER:-admin}" \
    --adminpass="${MOODLE_ADMIN_PASSWORD:-Admin123!ChangeMe}" \
    --adminemail="${MOODLE_ADMIN_EMAIL:-admin@example.local}" \
    --fullname="${MOODLE_SITE_FULLNAME:-eLearn Mindset Local}" \
    --shortname="${MOODLE_SITE_SHORTNAME:-elearnmindset}"

if [[ "${MOODLE_WWWROOT:-http://localhost:8080}" == http://* ]]; then
    docker compose exec -T moodle php admin/cli/cfg.php --name=cookiesecure --set=0
fi

docker compose exec -T moodle php admin/cli/cfg.php --name=forcelogin --set=0
docker compose exec -T moodle php admin/cli/cfg.php --name=defaulthomepage --set=0
docker compose exec -T moodle php admin/cli/cfg.php --name=enablemyhome --set=1
docker compose exec -T moodle php admin/cli/cfg.php --name=enablemycourses --set=1
docker compose exec -T moodle php admin/cli/upgrade.php --non-interactive
docker compose exec -T moodle php admin/cli/cfg.php --name=theme --set="${MOODLE_THEME}"
docker compose exec -T moodle php admin/cli/build_theme_css.php --themes="${MOODLE_THEME}" --direction=ltr --verbose
docker compose exec -T moodle php admin/cli/purge_caches.php
./scripts/configure-mailpit.sh

docker compose up -d cron

echo "Moodle is installed at ${MOODLE_WWWROOT:-http://localhost:8080}"
