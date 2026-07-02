# Theme Setup

This project now uses Moodle's default core `boost` theme.

## Clean Theme Policy

- Keep Moodle core themes only: `boost` and `classic`.
- Do not store custom themes under `moodle-overrides/public/theme`.
- Do not edit `moodle/public/theme/*` as project source code.
- Use Moodle admin settings for supported theme configuration.
- If a future custom theme is needed, create it as a new Boost child theme under `moodle-overrides/public/theme/<theme_name>` and document why it is required.

## Active Theme

The active theme is controlled by `MOODLE_THEME` in `.env`.

```bash
MOODLE_THEME=boost
```

## Apply Core Boost

```bash
make theme-install
```

Manual equivalent:

```bash
make sync-overrides
docker compose exec moodle php admin/cli/upgrade.php --non-interactive
docker compose exec moodle php admin/cli/cfg.php --name=theme --set=boost
docker compose exec moodle php admin/cli/build_theme_css.php --themes=boost --direction=ltr --verbose
docker compose exec moodle php admin/cli/purge_caches.php
```

## Verify

```bash
docker compose exec moodle php admin/cli/cfg.php --name=theme
find moodle-overrides/public/theme -maxdepth 2 -type f
find moodle/public/theme -maxdepth 1 -type d
```

Expected local Moodle themes:

```text
moodle/public/theme/boost
moodle/public/theme/classic
```
