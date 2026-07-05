# Chapter 02: Setup and Prerequisites

## Required Local Services

Start from the repository root:

```bash
cd /Users/hardik.chauhan/Documents/learning/eLearnMindset
docker compose up -d
docker compose ps
```

Expected services:

| Service | Purpose |
|---|---|
| Moodle application | Runs Moodle web and CLI commands. |
| PostgreSQL | Stores Moodle data. |
| Redis | Moodle cache/session support when configured. |
| Mailpit | Local mail capture for testing. |

## Required Tools

| Tool | Used For |
|---|---|
| `docker compose` | Running local Moodle stack. |
| `php` on host | Running CSV validators. |
| Moodle CLI inside container | Applying actual Moodle changes. |
| Git | Tracking ordered CSV renames and documentation changes. |

## Common Variables

```bash
cd /Users/hardik.chauhan/Documents/learning/eLearnMindset

PACK_HOST="$PWD/research/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
RUNNER="$PACK_HOST/run_school_setup_master.sh"
PACK_CONTAINER="/tmp/moodle_indian_school_moodle502_template_ready_csv_cli_pack"
MOODLE_SERVICE="moodle"
```

If the Moodle service name changes:

```bash
MOODLE_SERVICE=<your-service-name> "$RUNNER" help
```

## Preflight Sequence

Run:

```bash
"$RUNNER" validate
"$RUNNER" preflight
```

`validate` checks the CSV pack from the host. `preflight` copies CLI scripts and the pack into the Moodle container, then checks Moodle runtime compatibility.

## Manual Copy Rule

If you are not using `run_school_setup_master.sh`, copy all CLI scripts into Moodle:

```bash
cp "$PACK_HOST"/cli_*.php moodle/admin/cli/
```

Do not copy only one script. Several scripts depend on `cli_csv_helpers.php`.

## Moodle Feature Requirements

| Moodle Feature | Needed For |
|---|---|
| Manual authentication | Local demo users. |
| Manual enrolment | Staff course role assignments. |
| Cohort enrolment | Student batch enrolments. |
| Completion tracking | Activity completion and chapter gates. |
| Restricted access | Sequential chapter release. |

The template commands enable completion and availability before template creation:

```bash
"$RUNNER" template-reset-import
```

