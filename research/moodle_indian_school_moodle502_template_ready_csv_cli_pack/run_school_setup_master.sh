#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="${PROJECT_ROOT:-$(git -C "$SCRIPT_DIR" rev-parse --show-toplevel 2>/dev/null || pwd)}"
BASE_PACK="${BASE_PACK:-$SCRIPT_DIR}"
PACK_HOST="${PACK_HOST:-$SCRIPT_DIR}"
PACK_CONTAINER="${PACK_CONTAINER:-/tmp/moodle_indian_school_moodle502_template_ready_csv_cli_pack}"
MOODLE_SERVICE="${MOODLE_SERVICE:-moodle}"
MOODLE_CLI_DIR="${MOODLE_CLI_DIR:-$PROJECT_ROOT/moodle/admin/cli}"

usage() {
    cat <<'USAGE'
Moodle Indian school setup master runner

Usage:
  ./run_school_setup_master.sh <command> [args]

Environment:
  BASE_PACK       Base CLI pack path containing cli_*.php. Defaults to this script directory.
  PACK_HOST       Host CSV pack path. Defaults to this script directory.
  PACK_CONTAINER  Container CSV pack path. Defaults to /tmp/moodle_indian_school_moodle502_template_ready_csv_cli_pack.
  PROJECT_ROOT    Repository root. Auto-detected when run inside this repo.
  MOODLE_SERVICE  Docker Compose Moodle service name. Defaults to moodle.
  MOODLE_CLI_DIR  Host Moodle admin/cli path. Defaults to $PROJECT_ROOT/moodle/admin/cli.

Commands:
  help
  prepare
      Copy cli_*.php into Moodle admin/cli and copy PACK_HOST into the Moodle container.

  new-selective-pack <destination-dir>
      Create a small editable pack with headers for selective imports.
      It copies 16_user_profile_fields.csv and 17_custom_roles.csv because they are safe and idempotent.
      New selective packs use the ordered NN_<logical_name>.csv filenames.

  validate
      Run baseline and course-template CSV validators from the host.

  preflight
      Run Moodle runtime preflight checks inside Docker.

  baseline-dry-run
  baseline-import
      Run full baseline importer with dry-run=1 or dry-run=0.

  structure-dry-run
  structure-import
      Import categories, courses, cohorts, groups, role assignments and cohort enrolments from PACK_HOST.
      Use a selective pack with empty user/parent CSV files when adding only structure.

  users-dry-run
  users-import
      Import users, cohort memberships, parent links and optional staff role assignments from PACK_HOST.
      Skips course/category/group/enrolment creation.

  enrolments-dry-run
  enrolments-import
      Import cohort enrolment mappings from PACK_HOST.
      Use a selective pack with only 25_enrolments.csv rows for the intended courses/cohorts.

  template-dry-run
  template-import
  template-reset-import
      Create or reset the hidden universal master course template.

  apply-template-dry-run
  apply-template-import
      Apply course template section/settings rows from 37_course_template_application.csv.

  gradebook-dry-run
  gradebook-import
      Apply gradebook template rows from 33_course_template_gradebook.csv.

  purge-cache
      Purge Moodle caches.

  full-baseline
      prepare, validate, preflight, baseline dry-run, baseline import, template reset import,
      apply template import, gradebook import, purge cache.

Examples:
  ./run_school_setup_master.sh full-baseline
  ./run_school_setup_master.sh new-selective-pack /tmp/gvs-std05-a-users
  PACK_HOST=/tmp/gvs-std05-a-users ./run_school_setup_master.sh users-dry-run
  PACK_HOST=/tmp/gvs-std05-a-users ./run_school_setup_master.sh users-import
USAGE
}

require_project_root() {
    if [ ! -f "$PROJECT_ROOT/docker-compose.yml" ]; then
        echo "Cannot find docker-compose.yml at PROJECT_ROOT=$PROJECT_ROOT" >&2
        exit 1
    fi
}

copy_cli_to_moodle() {
    mkdir -p "$MOODLE_CLI_DIR"
    cp "$BASE_PACK"/cli_*.php "$MOODLE_CLI_DIR"/
    echo "Copied CLI scripts to $MOODLE_CLI_DIR"
}

copy_pack_to_container() {
    require_project_root
    local app_container
    app_container="$(cd "$PROJECT_ROOT" && docker compose ps -q "$MOODLE_SERVICE")"
    if [ -z "$app_container" ]; then
        echo "Moodle service is not running. Start it with: docker compose up -d" >&2
        exit 1
    fi
    cd "$PROJECT_ROOT"
    docker compose exec -u root -T "$MOODLE_SERVICE" rm -rf "$PACK_CONTAINER"
    docker cp "$PACK_HOST" "$app_container:$PACK_CONTAINER"
    echo "Copied pack to $MOODLE_SERVICE:$PACK_CONTAINER"
}

prepare() {
    copy_cli_to_moodle
    copy_pack_to_container
}

resolve_pack_csv() {
    local dir="$1"
    local logical="$2"
    if [ -f "$dir/$logical" ]; then
        printf '%s\n' "$dir/$logical"
        return 0
    fi
    local candidate
    for candidate in "$dir"/[0-9][0-9]_"$logical" "$dir"/[0-9][0-9][0-9]_"$logical"; do
        if [ -f "$candidate" ]; then
            printf '%s\n' "$candidate"
            return 0
        fi
    done
    return 1
}

copy_csv_to_selective_pack() {
    local logical="$1"
    local dest="$2"
    local mode="${3:-header}"
    local source
    source="$(resolve_pack_csv "$BASE_PACK" "$logical")"
    local target="$dest/$(basename "$source")"
    if [ "$mode" = "full" ]; then
        cp "$source" "$target"
    elif [ ! -f "$target" ]; then
        head -n 1 "$source" > "$target"
    fi
}

csv_has_data_rows() {
    local dir="$1"
    local logical="$2"
    local source
    source="$(resolve_pack_csv "$dir" "$logical")"
    [ -f "$source" ] || return 1
    awk 'NR > 1 && $0 !~ /^[[:space:]]*$/ { found = 1; exit } END { exit found ? 0 : 1 }' "$source"
}

new_selective_pack() {
    local dest="${1:-}"
    if [ -z "$dest" ]; then
        echo "Usage: ./run_school_setup_master.sh new-selective-pack <destination-dir>" >&2
        exit 1
    fi

    mkdir -p "$dest"
    copy_csv_to_selective_pack user_profile_fields.csv "$dest" full
    copy_csv_to_selective_pack custom_roles.csv "$dest" full

    local files=(
        categories.csv
        courses.csv
        cohorts.csv
        groups.csv
        users_staff.csv
        users_students.csv
        users_parents.csv
        cohort_members.csv
        role_assignments.csv
        parent_links.csv
        enrolments.csv
        master_course_template.csv
        course_template_sections.csv
        course_template_activities.csv
        course_template_application.csv
        course_template_gradebook.csv
        grade_band_template_adjustments.csv
    )

    local file
    for file in "${files[@]}"; do
        copy_csv_to_selective_pack "$file" "$dest" header
    done

    cat > "$dest/README_SELECTIVE_PACK.md" <<EOF
# Selective Moodle Import Pack

Created from:

\`\`\`text
$BASE_PACK
\`\`\`

Fill only the CSV files needed for the operation.

Common examples:

- Students only: edit the ordered student and cohort-member files, for example \`20_users_students.csv\` and \`22_cohort_members.csv\`.
- Students with parents: also edit the ordered parent and parent-link files, for example \`21_users_parents.csv\` and \`24_parent_links.csv\`.
- Teacher assignment: edit the ordered staff and role-assignment files, for example \`19_users_staff.csv\` and \`23_role_assignments.csv\`.
- New division: edit the ordered cohort, group, and enrolment files, for example \`14_cohorts.csv\`, \`15_groups.csv\`, and \`25_enrolments.csv\`.
- New course structure: edit the ordered category, course, cohort, group, and enrolment files.

The PHP import scripts resolve logical names automatically, so \`20_users_students.csv\` is read as \`users_students.csv\`.

Run from the repository root:

\`\`\`bash
PACK_HOST="$dest" "$SCRIPT_DIR/run_school_setup_master.sh" prepare
PACK_HOST="$dest" "$SCRIPT_DIR/run_school_setup_master.sh" users-dry-run
PACK_HOST="$dest" "$SCRIPT_DIR/run_school_setup_master.sh" users-import
\`\`\`
EOF

    echo "Created selective pack: $dest"
}

validate() {
    php "$BASE_PACK/cli_validate_school_baseline.php" --dir="$PACK_HOST"
    if csv_has_data_rows "$PACK_HOST" course_template_sections.csv \
        || csv_has_data_rows "$PACK_HOST" course_template_activities.csv \
        || csv_has_data_rows "$PACK_HOST" course_template_application.csv \
        || csv_has_data_rows "$PACK_HOST" course_template_gradebook.csv; then
        php "$BASE_PACK/cli_validate_course_template_csv.php" --dir="$PACK_HOST"
    else
        echo "Skipped template CSV validation because template files are header-only."
    fi
}

moodle_php() {
    require_project_root
    cd "$PROJECT_ROOT"
    docker compose exec -T "$MOODLE_SERVICE" php "$@"
}

preflight() {
    prepare
    moodle_php admin/cli/cli_moodle502_preflight.php
}

import_baseline() {
    local dry_run="$1"
    shift
    prepare
    moodle_php admin/cli/cli_import_indian_school_baseline.php \
        --dir="$PACK_CONTAINER" \
        --dry-run="$dry_run" \
        "$@"
}

template_import() {
    local dry_run="$1"
    local reset="${2:-0}"
    prepare
    moodle_php admin/cli/cfg.php --name=enablecompletion --set=1 >/dev/null
    moodle_php admin/cli/cfg.php --name=enableavailability --set=1 >/dev/null
    moodle_php admin/cli/cli_create_universal_master_course_template.php \
        --dir="$PACK_CONTAINER" \
        --dry-run="$dry_run" \
        --activity-mode=native \
        --reset-template-activities="$reset"
}

apply_template() {
    local dry_run="$1"
    prepare
    moodle_php admin/cli/cli_apply_course_template_settings.php \
        --dir="$PACK_CONTAINER" \
        --dry-run="$dry_run"
}

apply_gradebook() {
    local dry_run="$1"
    prepare
    moodle_php admin/cli/cli_apply_gradebook_template.php \
        --dir="$PACK_CONTAINER" \
        --dry-run="$dry_run"
}

purge_cache() {
    moodle_php admin/cli/purge_caches.php
}

full_baseline() {
    prepare
    validate
    preflight
    import_baseline 1
    import_baseline 0
    template_import 0 1
    apply_template 0
    apply_gradebook 0
    purge_cache
}

command="${1:-help}"
shift || true

case "$command" in
    help|-h|--help)
        usage
        ;;
    prepare)
        prepare
        ;;
    new-selective-pack)
        new_selective_pack "$@"
        ;;
    validate)
        validate
        ;;
    preflight)
        preflight
        ;;
    baseline-dry-run)
        import_baseline 1
        ;;
    baseline-import)
        import_baseline 0
        ;;
    structure-dry-run)
        import_baseline 1 --skip-users=1
        ;;
    structure-import)
        import_baseline 0 --skip-users=1
        ;;
    users-dry-run)
        import_baseline 1 --skip-courses=1 --skip-enrolments=1
        ;;
    users-import)
        import_baseline 0 --skip-courses=1 --skip-enrolments=1
        ;;
    enrolments-dry-run)
        import_baseline 1 --skip-courses=1 --skip-users=1
        ;;
    enrolments-import)
        import_baseline 0 --skip-courses=1 --skip-users=1
        ;;
    template-dry-run)
        template_import 1 0
        ;;
    template-import)
        template_import 0 0
        ;;
    template-reset-import)
        template_import 0 1
        ;;
    apply-template-dry-run)
        apply_template 1
        ;;
    apply-template-import)
        apply_template 0
        ;;
    gradebook-dry-run)
        apply_gradebook 1
        ;;
    gradebook-import)
        apply_gradebook 0
        ;;
    purge-cache)
        purge_cache
        ;;
    full-baseline)
        full_baseline
        ;;
    *)
        echo "Unknown command: $command" >&2
        echo >&2
        usage >&2
        exit 1
        ;;
esac
