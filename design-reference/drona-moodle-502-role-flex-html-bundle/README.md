# Drona Public School Moodle 5.0.2 HTML UI Bundle

Static responsive front-end prototype with **54 HTML pages**.

## Start
Open `public-home.html` for the default no-login site, or `all-pages.html` for the full page directory.

## Role colour system
- Teacher: red
- Student: blue
- Site administrator: green
- Parent / guardian: purple
- Other participant: orange

All authenticated pages use one flex-based responsive shell. The colour theme is controlled by `body[data-role]` in `assets/styles.css`.

## Important
This is a static design reference, not a working Moodle installation. Convert it to Moodle layouts, Mustache templates, SCSS, renderers and AMD modules for production use. Replace demo data with Moodle APIs and preserve Moodle permissions and accessibility.


## Glassmorphism redesign

The refreshed bundle uses translucent surfaces, layered gradients, backdrop blur, subtle reveal animations, responsive role cards and accessible reduced-motion behavior. Start at `public-home.html`, then follow `role-login.html`. See `NAVIGATION_AUDIT.md` for the tested page journeys.

## 2026 immersive UI refresh
The public experience now includes an animated LMS hero, dashboard showcase, capability metrics, programme pathways, role-experience carousel, bento feature layout, school/university community section, campus news and events, conversion call-to-action, and a full institutional footer. Shared application pages receive a compact standard footer through the common JavaScript layer.


## Moodle course overview compatibility

The bundle now includes an enhanced `my-courses.html` view with Moodle-style course filters, sorting, search, grid/list/summary controls, course actions, teacher identity, activity completion and responsive progress cards. The student dashboard also includes a four-part enrolment and activity statistics strip. All values are static prototype data and must be connected to Moodle APIs during implementation.


## Administrator course-card controls
The bundle includes an administrator-only course management view in `courses-admin.html`. Cards reveal quick actions on hover or keyboard focus and expose a complete action menu for editing, enrolments, reports, duplication, visibility and deletion. In Moodle integration, every action must be capability-checked and sourced from Moodle APIs.


## Administrator course list compatibility
The administrator catalogue now includes responsive grid and list presentation modes, persistent local view preference, hover/focus quick actions, role-aware action menus, visibility badges, and mobile-safe controls. In Moodle, all actions must be connected to capability checks and native course-management URLs; the display switch is presentation only.

## Final validated release

This package has received a complete local-reference, accessibility-baseline, CSS and JavaScript syntax audit. See `FINAL_RELEASE_NOTES.md` and `FINAL_VALIDATION.json` for details.
