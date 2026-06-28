#!/usr/bin/env bash
set -euo pipefail

MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"
INTERVAL="${MOODLE_CRON_INTERVAL:-60}"

while true; do
    if [ -f "${MOODLE_DIR}/admin/cli/cron.php" ] && [ -f "${MOODLE_DIR}/config.php" ]; then
        php "${MOODLE_DIR}/admin/cli/cron.php" || true
    fi
    sleep "${INTERVAL}"
done

