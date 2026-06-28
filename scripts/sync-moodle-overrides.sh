#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MOODLE_DIR="${ROOT_DIR}/moodle"
OVERRIDES_DIR="${ROOT_DIR}/moodle-overrides"

if [ ! -d "${MOODLE_DIR}" ]; then
    echo "Moodle checkout is missing at ${MOODLE_DIR}. Run ./scripts/bootstrap-moodle.sh first."
    exit 1
fi

if [ ! -d "${OVERRIDES_DIR}" ]; then
    echo "No moodle-overrides directory found. Nothing to sync."
    exit 0
fi

if command -v rsync >/dev/null 2>&1; then
    rsync -a "${OVERRIDES_DIR}/" "${MOODLE_DIR}/"
else
    cp -a "${OVERRIDES_DIR}/." "${MOODLE_DIR}/"
fi

echo "Moodle overrides synced into ${MOODLE_DIR}"
