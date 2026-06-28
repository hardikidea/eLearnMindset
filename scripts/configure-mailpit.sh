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
MOODLE_SMTP_HOSTS="${MOODLE_SMTP_HOSTS:-mailpit:1025}"
MOODLE_NOREPLY_ADDRESS="${MOODLE_NOREPLY_ADDRESS:-noreply@example.local}"

docker compose up -d db mailpit moodle

CONFIG_TABLE="${MOODLE_DB_PREFIX}config"
if ! docker compose exec -T db psql -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" -tAc "select to_regclass('${CONFIG_TABLE}')" | grep -q "${CONFIG_TABLE}"; then
    echo "Moodle database is not installed yet. Run ./scripts/install-site.sh first."
    exit 1
fi

docker compose exec -T moodle php admin/cli/cfg.php --name=smtphosts --set="${MOODLE_SMTP_HOSTS}"
docker compose exec -T moodle php admin/cli/cfg.php --name=smtpsecure --set=''
docker compose exec -T moodle php admin/cli/cfg.php --name=smtpauthtype --set=''
docker compose exec -T moodle php admin/cli/cfg.php --name=smtpuser --set=''
docker compose exec -T moodle php admin/cli/cfg.php --name=smtppass --set=''
docker compose exec -T moodle php admin/cli/cfg.php --name=noreplyaddress --set="${MOODLE_NOREPLY_ADDRESS}"
docker compose exec -T moodle php admin/cli/purge_caches.php

echo "Moodle SMTP is configured for MailPit at ${MOODLE_SMTP_HOSTS}"
echo "MailPit UI: http://localhost:${MAILPIT_UI_PORT:-8025}"
