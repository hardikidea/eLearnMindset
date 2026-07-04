# Theme Setup

This project uses Moodle's stock `boost` theme.

## Current Policy

- Keep Moodle bundled themes only.
- Do not maintain custom project themes in `moodle-overrides/public/theme/`.
- Do not edit Moodle core theme files directly.
- Theme defaults should stay compatible with standard Moodle upgrades.

## Bundled Moodle Themes

The local Moodle checkout should contain only Moodle-provided theme directories:

```text
moodle/public/theme/boost
moodle/public/theme/classic
```

`boost` is the default active theme for this project.

## Configuration

The active theme is controlled by `MOODLE_THEME` in `.env`.

```bash
MOODLE_THEME=boost
```

## Apply Theme

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
find moodle-overrides/public/theme -mindepth 1 -maxdepth 1 -type d
find moodle/public/theme -mindepth 1 -maxdepth 1 -type d
```

Expected:

- Active theme: `boost`
- No custom theme directories under `moodle-overrides/public/theme`
- Local Moodle theme directories are Moodle bundled themes only
