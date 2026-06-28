# Updating Moodle

This project follows the Moodle Git for Administrators approach: track official Moodle release tags and update by fetching and checking out a newer tag.

For the complete local and AWS server upgrade event procedure, read [Upgrade, backup, and restore](upgrade-backup-restore.md).

For AWS server environments, use the manual GitHub Actions `Moodle Version Upgrade` workflow. It accepts a Moodle Git tag, backs up the selected environment, deploys the new image with cron paused, runs Moodle CLI upgrade through ECS Exec, and restarts cron after success.

## Rules

- Use official tags such as `v5.2.1`.
- Do not use `main`, alpha, beta, or release-candidate tags for production-style installs.
- Back up Moodle code, `moodledata`, and PostgreSQL before updating.
- Keep project customizations in `moodle-overrides/`; do not edit the ignored `moodle/` checkout as the source of truth.
- Check plugin compatibility before major upgrades.
- For Moodle 5.1 and later, keep the web server root pointed at `public/`.

## Check Current Version

```bash
git -C moodle log --oneline -1 public/version.php
git -C moodle describe --tags --always --dirty
```

## Find Available 5.2 Tags

```bash
git ls-remote --tags --refs https://github.com/moodle/moodle.git 'v5.2*'
```

## Backup

```bash
./scripts/backup.sh
```

The backup is written under `backups/<timestamp>/` and includes:

- `postgres.sql`
- `moodledata.tar.gz`
- `moodle-version.txt`

## Restore From Backup

Use this for local Docker rollback after a failed or unwanted upgrade:

```bash
./scripts/restore-backup.sh backups/YYYYMMDD-HHMMSS --yes
```

The restore script:

1. Reads the recorded Moodle tag from `moodle-version.txt` when available.
2. Checks out that tag in `moodle/`.
3. Syncs `moodle-overrides/` into the checkout.
4. Stops Moodle and cron.
5. Recreates the local PostgreSQL database from `postgres.sql`.
6. Replaces `moodledata/` from `moodledata.tar.gz`.
7. Reinstalls Composer dependencies for the restored Moodle tag.
8. Purges caches, disables maintenance mode, reapplies MailPit SMTP, and restarts cron.

This is destructive for the local Docker database and `moodledata/`. It requires `--yes` intentionally.

## Update to a New Patch Release

```bash
./scripts/update-moodle.sh v5.2.2
```

The script:

1. Confirms the tag exists upstream.
2. Creates a backup.
3. Enables Moodle maintenance mode when possible.
4. Fetches tags from the official Moodle repository.
5. Checks out the requested tag in `moodle/`.
6. Syncs `moodle-overrides/` into the Moodle checkout.
7. Runs Composer inside the Moodle container.
8. Runs Moodle CLI upgrade.
9. Purges caches.
10. Disables maintenance mode.

If any step fails after the backup is created, the script prints the exact restore command:

```bash
./scripts/restore-backup.sh "backups/YYYYMMDD-HHMMSS" --yes
```

For local-only upgrade testing, you can ask the script to attempt rollback automatically:

```bash
./scripts/update-moodle.sh --restore-on-fail v5.2.2
```

Use automatic rollback only for local Docker environments. For shared/stage/prod environments, restore intentionally from verified database and file backups.

## Major Upgrade Notes

Before moving from Moodle 5.2 to a future major release:

1. Read that release's server requirements.
2. Confirm PHP, PostgreSQL, and required PHP extensions still match.
3. Confirm all installed plugins support the target version.
4. Run the update on a copy first.
5. Keep the backup until the upgraded site has been tested.

The current Docker setup is built for Moodle 5.2 requirements: PHP 8.3 and PostgreSQL 16.
