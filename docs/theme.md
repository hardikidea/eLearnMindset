# Theme Setup

The project uses `theme_almondb` with project-level SCSS overrides for source-controlled layout and Bootstrap styling.

## Goals

- Keep Almondb's Boost-based layout files and JavaScript.
- Make `#page.drawers .main-inner` full width without breaking drawer or mobile layouts.
- Preserve responsive behavior on mobile and drawer layouts.
- Apply project colors through Bootstrap Sass variables and component rules.
- Style Bootstrap 5 components, Moodle navigation, admin forms, and the site footer consistently.

## Logo Palette

The theme palette is based on the eLearn Mindset logo:

- Navy: `#0d3f5c`
- Red: `#ed0017`
- Gold: `#ffd30a`
- Orange: `#f29900`
- White: `#ffffff`

## Files

- `moodle/public/theme/almondb/scss/almondb/_default_variables.scss`
- `moodle/public/theme/almondb/scss/almondb/_elearn-mindset.scss`
- `moodle/public/theme/almondb/scss/almondb-main.scss`

## Modern UI Patterns

- Use `theme/almondb/scss/almondb/_elearn-mindset.scss` as the single project design layer.
- Keep `main-inner` full-width, but align page header, secondary navigation, content, and footer content on one centered `1280px` rail.
- Use white cards on the light school surface with navy text, red structural accents, and gold/orange active states.
- Use standard Bootstrap 5 components as the base: nav tabs, dropdowns, cards, list groups, accordions, input groups, tables, alerts, badges, progress bars, and pagination.
- Use Font Awesome icons for primary navigation, page headers, and functional link affordances.
- Prefer card grids, clear hover/focus states, rounded touch targets, and visible keyboard focus rings.
- Keep admin pages dense and scannable: grouped settings should look like functional cards, not marketing blocks.
- Keep course cards, dashboard blocks, forms, tables, drawers, secondary navigation, and footer styling consistent.
- Add only subtle entrance and hover animation; the layout must remain usable without motion.
- Respect reduced-motion preferences for users who disable animation.

## Apply Theme

```bash
docker compose exec moodle php admin/cli/upgrade.php --non-interactive
docker compose exec moodle php admin/cli/cfg.php --name=theme --set=almondb
docker compose exec moodle php admin/cli/cfg.php --component=theme_almondb --name=brandcolor --set="#0d3f5c"
docker compose exec moodle php admin/cli/cfg.php --component=theme_almondb --name=sitecolor --set="#0d3f5c"
docker compose exec moodle php admin/cli/cfg.php --component=theme_almondb --name=navbarcolor --set="#ffffff"
docker compose exec moodle php admin/cli/cfg.php --component=theme_almondb --name=backcolor --set="#ffffff"
docker compose exec moodle php admin/cli/build_theme_css.php --themes=almondb --direction=ltr --verbose
docker compose exec moodle php admin/cli/purge_caches.php
```

## Verify

```bash
docker compose exec moodle php admin/cli/cfg.php --name=theme
curl -fsS http://localhost:8080/login/index.php | grep -E "theme/styles.php/almondb"
```

Recommended visual checks after theme changes:

- `http://localhost:8080/admin/search.php#linkappearance`
- `http://localhost:8080/my/courses.php`
- `http://localhost:8080/course/index.php?categoryid=all`
