#!/usr/bin/env bash
set -euo pipefail
YEAR="${1:-2026-2027}"
MODE="${2:-dry-run}"
PACK_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PROJECT_ROOT="${PROJECT_ROOT:-$(git -C "$PACK_ROOT" rev-parse --show-toplevel 2>/dev/null || pwd)}"
MOODLE_SERVICE="${MOODLE_SERVICE:-moodle}"
BUILD_DIR="$PACK_ROOT/build/assembled_csv/$YEAR"
CONTAINER_PACK="/tmp/school_master_pack/$YEAR"
if [ ! -d "$BUILD_DIR" ]; then
  "$PACK_ROOT/scripts/assemble.py" --year "$YEAR"
fi
php -d memory_limit=512M "$PACK_ROOT/scripts/moodle_cli/cli_validate_school_baseline.php" --dir="$BUILD_DIR"
php -d memory_limit=512M "$PACK_ROOT/scripts/moodle_cli/cli_validate_course_template_csv.php" --dir="$BUILD_DIR"
if [ "$MODE" = "validate-only" ]; then
  exit 0
fi
if [ ! -f "$PROJECT_ROOT/docker-compose.yml" ]; then
  echo "Cannot find docker-compose.yml. Set PROJECT_ROOT=/path/to/repo" >&2
  exit 1
fi
cp "$PACK_ROOT/scripts/moodle_cli"/cli_*.php "$PROJECT_ROOT/moodle/admin/cli/"
APP_CONTAINER="$(cd "$PROJECT_ROOT" && docker compose ps -q "$MOODLE_SERVICE")"
if [ -z "$APP_CONTAINER" ]; then
  echo "Moodle service is not running. Start it with: docker compose up -d" >&2
  exit 1
fi
cd "$PROJECT_ROOT"
docker compose exec -u root -T "$MOODLE_SERVICE" sh -lc "rm -rf '$CONTAINER_PACK' && mkdir -p '$CONTAINER_PACK'"
docker cp "$BUILD_DIR/." "$APP_CONTAINER:$CONTAINER_PACK/"
DRY="1"
if [ "$MODE" = "live" ]; then
  DRY="0"
fi
EXTRA_SKIP=""
if [ "$YEAR" != "2026-2027" ]; then
  EXTRA_SKIP="--skip-users=1"
fi
docker compose exec -T "$MOODLE_SERVICE" php -d memory_limit=512M admin/cli/cli_import_indian_school_baseline.php --dir="$CONTAINER_PACK" --dry-run="$DRY" $EXTRA_SKIP
docker compose exec -T "$MOODLE_SERVICE" php -d memory_limit=512M admin/cli/cli_apply_course_template_settings.php --dir="$CONTAINER_PACK" --dry-run="$DRY"
docker compose exec -T "$MOODLE_SERVICE" php -d memory_limit=512M admin/cli/cli_apply_gradebook_template.php --dir="$CONTAINER_PACK" --dry-run="$DRY"
docker compose exec -T "$MOODLE_SERVICE" php -d memory_limit=512M admin/cli/cli_apply_course_certificates.php --dir="$CONTAINER_PACK" --dry-run="$DRY" --refresh-template=1
