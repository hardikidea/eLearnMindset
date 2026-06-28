#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

if [ -d "${ROOT_DIR}/moodle" ]; then
    ./scripts/sync-moodle-overrides.sh
else
    echo "Local Moodle checkout not found; skipping override sync. Run ./scripts/bootstrap-moodle.sh before local Moodle development."
fi

forbidden_files=""
while IFS= read -r staged_file; do
    [ -z "${staged_file}" ] && continue

    case "${staged_file}" in
        .env|.env.*)
            [ "${staged_file}" = ".env.example" ] && continue
            forbidden_files="${forbidden_files}${staged_file}"$'\n'
            ;;
        moodle|moodle/*|moodledata|moodledata/*|backups|backups/*|plugins|plugins/*)
            forbidden_files="${forbidden_files}${staged_file}"$'\n'
            ;;
        *.tfstate|*.tfstate.*|*.tfplan|terraform.tfvars|*/terraform.tfvars|.terraform/*|*/.terraform/*)
            forbidden_files="${forbidden_files}${staged_file}"$'\n'
            ;;
    esac
done < <(git diff --cached --name-only)

if [ -n "${forbidden_files}" ]; then
    printf 'Refusing to commit local runtime, state, or secret files:\n%s\n' "${forbidden_files}" >&2
    exit 1
fi

./scripts/lint-changed.sh commit

echo "Pre-commit checks passed."
