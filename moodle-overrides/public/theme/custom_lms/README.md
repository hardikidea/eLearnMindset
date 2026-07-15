# Custom LMS Theme

`custom_lms` is a Boost-based Moodle theme with the Drona role-flex HTML bundle converted into Moodle-rendered Mustache pages.

## Source Layout

- Base theme clone: `moodle/public/theme/boost`
- Theme source override: `moodle-overrides/public/theme/custom_lms`
- Runtime sync target: `moodle/public/theme/custom_lms`
- HTML design source: `design-reference/drona-moodle-502-role-flex-html-bundle/*.html`
- Generated page registry: `data/bundle_pages.json`
- Generated page templates: `templates/pages/*.mustache`
- Shared bundle partials: `templates/partials/*.mustache`
- Bundle CSS: `style/bundle_pages.css`
- Bundle JavaScript: `amd/src/bundle_pages.js`

## Routes

Each converted HTML page is available through:

```text
/theme/custom_lms/page.php?page=<page-slug>
```

Examples:

```text
/theme/custom_lms/page.php?page=public-home
/theme/custom_lms/page.php?page=role-login
/theme/custom_lms/page.php?page=my-courses
/theme/custom_lms/page.php?page=courses-admin
```

When `custom_lms` is selected as the active Moodle theme, the site frontpage layout renders the `public-home` bundle template.

## Access Rules

The bundle role is a visual mode only. Authorization is enforced in PHP:

- `public`: available without login.
- `login`: requires a logged-in Moodle user.
- `admin`: requires login plus one of `moodle/site:config`, `moodle/course:create`, or `moodle/category:manage`.

This supports site administrators and manager-style users without using CSS role styling as authorization.

## Moodle Data

The generated templates receive Moodle-backed data from `classes/output/bundle_page.php`:

- Site/school name.
- Current user name, initials and inferred role label.
- Public course/user counts.
- Enrolled course cards for `my-courses`.
- Managed course cards for administrator course catalogue pages.
- Theme image URLs and page route URLs.

If Moodle has no matching records, the original static design cards remain as fallback content so the design still renders cleanly.

## Sync and Install

Run from the repository root:

```bash
make sync-overrides
docker compose exec -T moodle php admin/cli/upgrade.php --non-interactive
docker compose exec -T moodle php admin/cli/purge_caches.php
```

To activate after installation:

```bash
docker compose exec -T moodle php admin/cli/cfg.php --name=theme --set=custom_lms
docker compose exec -T moodle php admin/cli/purge_caches.php
```
