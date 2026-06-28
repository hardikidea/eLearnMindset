#!/usr/bin/env bash
set -euo pipefail

MOODLE_DIR="${MOODLE_DIR:-/var/www/moodle}"
MOODLE_DATA="${MOODLE_DATAROOT:-/var/www/moodledata}"

prepare_moodle() {
    mkdir -p "${MOODLE_DATA}"
    chown -R www-data:www-data "${MOODLE_DATA}" || true
    chmod -R ug+rwX "${MOODLE_DATA}" || true

    if [ ! -f "${MOODLE_DIR}/public/index.php" ]; then
        echo "Moodle checkout not found at ${MOODLE_DIR}."
        echo "Run ./scripts/bootstrap-moodle.sh on the host before starting containers."
        exit 1
    fi

    if [ ! -f "${MOODLE_DIR}/config.php" ]; then
        cp /usr/local/share/moodle/config.php "${MOODLE_DIR}/config.php"
        chown www-data:www-data "${MOODLE_DIR}/config.php" || true
    fi

    if [ "${MOODLE_COMPOSER_INSTALL:-true}" = "true" ] && [ -f "${MOODLE_DIR}/composer.json" ]; then
        cd "${MOODLE_DIR}"
        if [ ! -f vendor/autoload.php ] || [ composer.lock -nt vendor/composer/installed.php ]; then
            composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
            chown -R www-data:www-data vendor || true
        fi
    fi
}

prepare_moodle

case "${1:-moodle-web}" in
    moodle-web)
        /usr/local/sbin/php-fpm -D
        exec /usr/sbin/nginx -g "daemon off;"
        ;;
    moodle-cron-loop)
        exec moodle-cron-loop
        ;;
    *)
        exec "$@"
        ;;
esac
