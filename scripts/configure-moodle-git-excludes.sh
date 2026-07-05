#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MOODLE_DIR="${ROOT_DIR}/moodle"
OVERRIDES_DIR="${ROOT_DIR}/moodle-overrides"
BEGIN_MARKER="# BEGIN eLearnMindset synced Moodle overrides"
END_MARKER="# END eLearnMindset synced Moodle overrides"

if [ ! -d "${MOODLE_DIR}" ] || ! git -C "${MOODLE_DIR}" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    echo "Moodle Git checkout is missing at ${MOODLE_DIR}. Skipping nested Git excludes."
    exit 0
fi

if [ ! -d "${OVERRIDES_DIR}" ]; then
    echo "No moodle-overrides directory found. Skipping nested Git excludes."
    exit 0
fi

exclude_path="$(git -C "${MOODLE_DIR}" rev-parse --git-path info/exclude)"
case "${exclude_path}" in
    /*) exclude_file="${exclude_path}" ;;
    *) exclude_file="${MOODLE_DIR}/${exclude_path}" ;;
esac
mkdir -p "$(dirname "${exclude_file}")"

entries="$(mktemp)"
clean_exclude="$(mktemp)"
trap 'rm -f "${entries}" "${clean_exclude}"' EXIT

add_dir_if_exists() {
    local relpath="$1"
    if [ -d "${OVERRIDES_DIR}/${relpath}" ]; then
        printf '/%s/\n' "${relpath}" >> "${entries}"
    fi
}

add_file_if_exists() {
    local relpath="$1"
    if [ -f "${OVERRIDES_DIR}/${relpath}" ]; then
        printf '/%s\n' "${relpath}" >> "${entries}"
    fi
}

add_plugin_dirs() {
    local relroot="$1"
    if [ -d "${OVERRIDES_DIR}/${relroot}" ]; then
        find "${OVERRIDES_DIR}/${relroot}" -mindepth 1 -maxdepth 1 -type d \
            | sed "s#^${OVERRIDES_DIR}/#/#; s#\$#/#" >> "${entries}"
    fi
}

add_dir_if_exists "demo-data"
add_plugin_dirs "public/admin/tool"
add_plugin_dirs "public/blocks"
add_plugin_dirs "public/course/format"
add_plugin_dirs "public/mod"
add_plugin_dirs "public/theme"

for file in \
    admin/cli/cli_apply_course_template_settings.php \
    admin/cli/cli_apply_gradebook_template.php \
    admin/cli/cli_create_universal_master_course_template.php \
    admin/cli/cli_csv_helpers.php \
    admin/cli/cli_import_indian_school_baseline.php \
    admin/cli/cli_moodle502_preflight.php \
    admin/cli/cli_prepare_next_academic_year.php \
    admin/cli/cli_promote_academic_year.php \
    admin/cli/cli_promote_students_academic_year.php \
    admin/cli/cli_validate_course_template_csv.php \
    admin/cli/cli_validate_school_baseline.php
do
    if [ -f "${MOODLE_DIR}/${file}" ]; then
        printf '/%s\n' "${file}" >> "${entries}"
    fi
done

if [ -d "${OVERRIDES_DIR}/scripts" ]; then
    while IFS= read -r file; do
        add_file_if_exists "${file#${OVERRIDES_DIR}/}"
    done < <(find "${OVERRIDES_DIR}/scripts" -maxdepth 1 -type f | sort)
fi

sort -u "${entries}" -o "${entries}"

if [ -f "${exclude_file}" ]; then
    awk -v begin="${BEGIN_MARKER}" -v end="${END_MARKER}" '
        $0 == begin { skip = 1; next }
        $0 == end { skip = 0; next }
        skip != 1 { print }
    ' "${exclude_file}" > "${clean_exclude}"
else
    : > "${clean_exclude}"
fi

{
    cat "${clean_exclude}"
    printf '%s\n' "${BEGIN_MARKER}"
    printf '# Managed by scripts/configure-moodle-git-excludes.sh. Do not edit this block by hand.\n'
    cat "${entries}"
    printf '%s\n' "${END_MARKER}"
} > "${exclude_file}"

echo "Configured Moodle nested Git excludes in ${exclude_file}"
