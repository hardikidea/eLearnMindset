# Theme Setup

The project uses `theme_elearnboost`, a minimal Boost child theme stored in `moodle-overrides/public/theme/elearnboost`.

## Design Direction

- Base: Moodle Boost, kept visually close to the original Boost experience.
- Purpose: remove Boost's constrained `.main-inner` and `.footer-popover` rails so Moodle pages can use the available horizontal workspace.
- Scope: CSS-only theme override. No Boost layout templates are copied or modified.
- Upgrade safety: the theme remains a small child theme, so Moodle upgrades continue to come from the official `moodle/` checkout.

## Full-Width Rule

The theme removes `max-width` from drawer page containers and gives `.main-inner` plus `.footer-popover` a responsive width:

- Desktop/tablet: `width: calc(100% - 2rem)` and `max-width: none`.
- Mobile: `width: 100%`, 1rem left/right padding, and hidden horizontal overflow protection.
- Inner page regions such as `#region-main`, `#page-content`, `.course-content`, `.secondary-navigation`, and `.dashboard-card-deck` also use `max-width: none`.

## Rules

- Do not edit `moodle/public/theme/*` as the source of truth.
- Keep all project theme code under `moodle-overrides/public/theme/elearnboost`.
- Do not override Boost layout templates unless a future requirement explicitly needs a structural change.
- Keep drawer pages and the footer popover full width by overriding Boost's limited-width rail while preserving mobile padding and touch targets.
- Keep touch targets at least `44px` high.
- Keep focus rings visible.

## Files

- `moodle-overrides/public/theme/elearnboost/config.php`
- `moodle-overrides/public/theme/elearnboost/lib.php`
- `moodle-overrides/public/theme/elearnboost/lang/en/theme_elearnboost.php`
- `moodle-overrides/public/theme/elearnboost/version.php`

The active theme is controlled by `MOODLE_THEME` in `.env`. The default is:

```bash
MOODLE_THEME=elearnboost
```

## Apply Theme

```bash
make theme-install
```

Manual equivalent:

```bash
make sync-overrides
docker compose exec moodle php admin/cli/upgrade.php --non-interactive
docker compose exec moodle php admin/cli/cfg.php --name=theme --set=elearnboost
docker compose exec moodle php admin/cli/build_theme_css.php --themes=elearnboost --direction=ltr --verbose
docker compose exec moodle php admin/cli/purge_caches.php
```

## Verify

```bash
docker compose exec moodle php admin/cli/cfg.php --name=theme
curl -fsS http://localhost:8080/login/index.php | grep -E "theme/styles.php/elearnboost"
```

Recommended visual checks after theme changes:

- `http://localhost:8080/admin/search.php`
- `http://localhost:8080/admin/search.php#linkappearance`
- `http://localhost:8080/my/courses.php`
- `http://localhost:8080/course/index.php?categoryid=all`
- `http://localhost:8080/course/view.php?id=1`
