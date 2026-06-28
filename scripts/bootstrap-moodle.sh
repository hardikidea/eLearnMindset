#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

if [ ! -f .env ]; then
    cp .env.example .env
    echo "Created .env from .env.example"
fi

set -a
# shellcheck disable=SC1091
source .env
set +a

MOODLE_VERSION="${MOODLE_VERSION:-v5.2.1}"
MOODLE_REPO="${MOODLE_REPO:-https://github.com/moodle/moodle.git}"

mkdir -p moodledata

if [ ! -d moodle/.git ]; then
    git clone -b "${MOODLE_VERSION}" "${MOODLE_REPO}" moodle
else
    git -C moodle fetch origin --tags --prune
    git -C moodle checkout "${MOODLE_VERSION}"
fi

echo "Moodle source is ready at ${ROOT_DIR}/moodle"
git -C moodle log --oneline -1 public/version.php

