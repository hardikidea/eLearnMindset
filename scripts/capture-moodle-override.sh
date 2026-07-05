#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MOODLE_DIR="${ROOT_DIR}/moodle"
OVERRIDES_DIR="${ROOT_DIR}/moodle-overrides"
RELPATH="${1:-}"

usage() {
    cat <<'USAGE'
Capture one locally installed Moodle file or plugin directory into moodle-overrides/.

Usage:
  ./scripts/capture-moodle-override.sh public/mod/customcert
  make capture-override RELPATH=public/mod/customcert

This is intentionally the reverse of sync-overrides:
  capture-override: moodle/ -> moodle-overrides/
  sync-overrides:   moodle-overrides/ -> moodle/
USAGE
}

if [ -z "${RELPATH}" ] || [ "${RELPATH}" = "--help" ]; then
    usage
    exit 0
fi

case "${RELPATH}" in
    /*|*..*|"")
        echo "Invalid RELPATH: ${RELPATH}" >&2
        exit 1
        ;;
esac

SOURCE="${MOODLE_DIR}/${RELPATH}"
TARGET="${OVERRIDES_DIR}/${RELPATH}"

if [ ! -e "${SOURCE}" ]; then
    echo "Source does not exist in local Moodle checkout: ${SOURCE}" >&2
    exit 1
fi

mkdir -p "$(dirname "${TARGET}")"

if [ -d "${SOURCE}" ]; then
    mkdir -p "${TARGET}"
    if command -v rsync >/dev/null 2>&1; then
        rsync -a --delete "${SOURCE}/" "${TARGET}/"
    else
        rm -rf "${TARGET}"
        cp -a "${SOURCE}" "${TARGET}"
    fi
else
    cp -a "${SOURCE}" "${TARGET}"
fi

"${ROOT_DIR}/scripts/configure-moodle-git-excludes.sh"

echo "Captured ${RELPATH} into moodle-overrides/"
