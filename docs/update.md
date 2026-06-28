# Updating Moodle

This project follows the Moodle Git for Administrators approach: track official Moodle release tags and update by fetching and checking out a newer tag.

## Rules

- Use official tags such as `v5.2.1`.
- Do not use `main`, alpha, beta, or release-candidate tags for production-style installs.
- Back up Moodle code, `moodledata`, and PostgreSQL before updating.
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
6. Runs Composer inside the Moodle container.
7. Runs Moodle CLI upgrade.
8. Purges caches.
9. Disables maintenance mode.

## Major Upgrade Notes

Before moving from Moodle 5.2 to a future major release:

1. Read that release's server requirements.
2. Confirm PHP, PostgreSQL, and required PHP extensions still match.
3. Confirm all installed plugins support the target version.
4. Run the update on a copy first.
5. Keep the backup until the upgraded site has been tested.

The current Docker setup is built for Moodle 5.2 requirements: PHP 8.3 and PostgreSQL 16.

