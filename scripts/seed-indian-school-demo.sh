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

DOCKER_BIN="${DOCKER_BIN:-docker}"
if ! command -v "${DOCKER_BIN}" >/dev/null 2>&1; then
    if [ -x /usr/local/bin/docker ]; then
        DOCKER_BIN="/usr/local/bin/docker"
    else
        echo "Docker CLI was not found. Install Docker or set DOCKER_BIN=/path/to/docker."
        exit 1
    fi
fi

POSTGRES_DB="${POSTGRES_DB:-moodle}"
POSTGRES_USER="${POSTGRES_USER:-moodle}"
MOODLE_DB_PREFIX="${MOODLE_DB_PREFIX:-mdl_}"

"${DOCKER_BIN}" compose up -d db redis mailpit moodle

CONFIG_TABLE="${MOODLE_DB_PREFIX}config"
if ! "${DOCKER_BIN}" compose exec -T db psql -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" -tAc "select to_regclass('${CONFIG_TABLE}')" | grep -q "${CONFIG_TABLE}"; then
    echo "Moodle database is not installed yet. Run ./scripts/install-site.sh first."
    exit 1
fi

"${DOCKER_BIN}" compose exec -T moodle php scripts/seed_indian_school_demo.php
