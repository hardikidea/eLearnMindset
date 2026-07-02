# Theme Setup

This project uses `theme_eduboost`, a Boost-based Moodle theme stored in `moodle-overrides/public/theme/eduboost`.

## Design Direction

- Base: Moodle Boost layout, navigation, templates, and JavaScript.
- Theme goal: keep Moodle familiar while applying a modern school management visual system.
- Upgrade approach: EduBoost inherits from Boost instead of editing Moodle core files.
- Palette: academic green-blue primary, teal success/accent, royal blue links, and warm gold highlights.

## Palette

- Primary: `#0f4c5c`
- Primary dark: `#073b4c`
- Accent blue: `#2563eb`
- Teal: `#0f766e`
- Gold: `#f59e0b`
- Surface: `#f6f8fb`
- Text: `#152536`
- Border: `#d7e2ea`

## Files

- `moodle-overrides/public/theme/eduboost/config.php`
- `moodle-overrides/public/theme/eduboost/lib.php`
- `moodle-overrides/public/theme/eduboost/lang/en/theme_eduboost.php`
- `moodle-overrides/public/theme/eduboost/version.php`

## Active Theme

The active theme is controlled by `MOODLE_THEME` in `.env`.

```bash
MOODLE_THEME=eduboost
```

## Apply Theme

```bash
make theme-install
```

Manual equivalent:

```bash
make sync-overrides
docker compose exec moodle php admin/cli/upgrade.php --non-interactive
docker compose exec moodle php admin/cli/cfg.php --name=theme --set=eduboost
docker compose exec moodle php admin/cli/build_theme_css.php --themes=eduboost --direction=ltr --verbose
docker compose exec moodle php admin/cli/purge_caches.php
```

## Verify

```bash
docker compose exec moodle php admin/cli/cfg.php --name=theme
curl -fsS http://localhost:8080/login/index.php | grep -E "theme/styles.php/eduboost"
```

Expected local Moodle themes:

```text
moodle/public/theme/boost
moodle/public/theme/classic
moodle/public/theme/eduboost
```
