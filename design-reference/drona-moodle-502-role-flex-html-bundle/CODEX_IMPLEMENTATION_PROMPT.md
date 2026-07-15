# Codex implementation prompt

Copy the prompt below into Codex after placing this entire bundle inside the target Moodle repository, for example at:

`design-reference/drona-moodle-502-role-flex-html-bundle/`

---

You are implementing the supplied Drona Public School responsive HTML reference bundle as a production-ready Moodle 5.0.2 theme inside this repository.

## Reference bundle

The reference bundle is located at:

`design-reference/drona-moodle-502-role-flex-html-bundle/`

Key files include:

- `all-pages.html` — directory of all reference pages
- `public-home.html` — default public site without login
- `role-login.html` — role-selection experience
- `login-teacher.html` — teacher red identity
- `login-student.html` — student blue identity
- `login-admin.html` — administrator green identity
- `login-parent.html` — parent/guardian purple identity
- `login-participant.html` — other participant orange identity
- `course-player.html` — YouTube-style course player
- `assets/styles.css` — shared responsive flex-layout design system
- `assets/app.js` — prototype interactions
- `assets/school-logo.jpg` — Drona Public School logo

Treat all HTML, CSS, JavaScript, and imagery as a visual and interaction specification. Do not deploy these static files as the Moodle implementation and do not copy placeholder data into production.

## Primary objective

Create or complete a Moodle theme plugin named `theme_drona`, normally located at:

`theme/drona/`

Reproduce the bundle's visual system, responsive behavior, public-site experience, role-aware color identities, reusable flex layout, and course-player experience while preserving Moodle-native behavior, permissions, accessibility, and upgrade compatibility.

## First actions

Before changing production files:

1. Inspect the complete repository.
2. Read every applicable `AGENTS.md` file.
3. Identify the exact Moodle version, existing theme architecture, plugins, customizations, build commands, lint commands, tests, CI rules, and coding conventions.
4. Inspect every page and asset in this reference bundle.
5. Map each static page to the closest Moodle page type, route, layout, renderer, template, or reusable component.
6. Write the implementation plan to:

   `docs/drona-theme-implementation-plan.md`

Do not begin broad implementation until that plan exists.

## Required Moodle architecture

Use Moodle-supported extension points:

- Moodle theme plugin conventions
- Boost as parent theme unless this repository requires another supported parent
- Mustache templates
- SCSS
- Moodle layout files
- Moodle renderers only where necessary
- Moodle AMD JavaScript modules
- Moodle language strings
- Moodle settings API
- Moodle navigation and output APIs
- Moodle URL APIs
- Moodle capability checks
- Moodle forms and standard components
- Moodle image/pix APIs
- Moodle-supported Bootstrap utilities where appropriate

Do not:

- Modify Moodle core files
- Replace Moodle pages with static HTML
- Hard-code users, courses, grades, activities, dates, events, messages, notifications, or progress
- Introduce React, Vue, Angular, Tailwind, or a separate frontend application unless already required by the repository
- Depend on CDN scripts, fonts, or styles
- Use inline JavaScript
- Use unsafe inline CSS
- Use `eval` or `document.write`
- Insert unsanitized HTML
- Duplicate Moodle business logic
- Hide required teacher or administrator controls
- Break editing mode, drag-and-drop, forms, drawers, menus, tables, modals, file pickers, activity completion, or accessibility hooks

## Role-aware color identities

Implement one Moodle theme with contextual role styling, not separate insecure authentication systems.

Required identities:

- Teacher: red primary from the school logo, navy secondary
- Student: blue primary from the school logo, navy secondary
- Site administrator: green primary, navy secondary
- Parent/guardian: purple primary, navy secondary
- Other participant/custom learner role: orange primary, navy secondary
- Guest/public visitor: navy, white, gold, and restrained red accents

Important rules:

- Role color is visual identity only, never authorization.
- Moodle authentication, enrolment, context, and capabilities remain the source of truth.
- Users with multiple roles must be styled from active page context and relevant capabilities, not simply the first global role.
- Provide a safe default identity where context is ambiguous.
- Do not rely only on role shortnames; make custom role mappings configurable where practical.

Create a reusable role-theme resolver that considers:

1. Login state
2. Current context
3. Site administrator status
4. Course-level roles
5. Teacher management/editing capabilities
6. Student participation context
7. Configured parent role mappings
8. Configured participant role mappings
9. Fallback identity

## Public site without login

The default site must remain useful without authentication and must support:

- School branding
- Public navigation
- Public course discovery where Moodle allows it
- School information
- Public announcements/news
- Events
- Contact details
- Login entry points
- Role-selection entry point
- Guest access where Moodle permits it
- Responsive header and footer
- Accessible mobile navigation

Never expose private courses, grades, users, messages, enrolments, or other protected information.

## Role-specific login presentation

Preserve a single secure Moodle authentication flow.

Role cards may link to Moodle login with a visual preference parameter or equivalent session-safe presentation state, but:

- Do not create separate insecure authentication endpoints.
- The selected role changes appearance and guidance only.
- Final access and role identity are resolved after authentication.
- Preserve SSO, OAuth, LDAP, manual accounts, MFA, forgotten password, registration, and installed authentication plugins.

Implement visual layouts for:

- Role selection
- Teacher login
- Student login
- Administrator login
- Parent/guardian login
- Other participant login
- Guest/public entry
- Forgotten password
- Registration where enabled
- Authentication errors

## Shared flex-layout foundation

All pages should use reusable layout primitives for:

- Application shell
- Header
- Sidebar
- Main content
- Right rail
- Page title and breadcrumbs
- Toolbar
- Card grid
- Split panel
- Course player
- Activity list
- Form layout
- Responsive tables
- Empty, loading, and error states
- Footer

Use CSS Grid where it clearly improves layout, while maintaining the bundle's flex-based system. Avoid one-off page CSS where reusable components are possible.

## Responsive requirements

Support at minimum:

- 320px
- 375px
- 480px
- 768px
- 1024px
- 1280px
- 1440px and larger

Requirements:

- No unintended horizontal page scrolling
- Controlled scrolling for data tables where needed
- Sidebars collapse into Moodle-compatible drawers
- Right rails move below main content on smaller screens
- Forms become single-column on mobile
- Cards wrap responsively
- Toolbars wrap without overlap
- Header controls remain usable
- Editing controls remain accessible
- Touch targets are approximately 44px minimum
- Modals, menus, and file pickers fit small screens
- Moodle debugging messages remain readable
- Editing mode works on mobile and desktop
- Do not intentionally break RTL

## Course-player experience

Implement the YouTube-style Moodle course-player layout represented by `course-player.html`.

Required structure:

- Main activity/video/content region
- Course and activity titles
- Breadcrumbs
- Completion action
- Previous/next activity controls
- Collapsible course-content sidebar
- Course sections and activities
- Completion indicators
- Locked/restricted states
- Durations only when real metadata exists
- Course progress
- Overview, description, resources, notes, and discussion regions where supported
- Next-up card
- Quick actions
- Mobile course-content drawer
- Expanded/fullscreen behavior where technically suitable

Moodle integration rules:

- Use Moodle course format, section, module, completion, availability, enrolment, and navigation data.
- Never invent durations or progress values.
- Hide unavailable metadata instead of faking it.
- Preserve restrictions, hidden activities, and completion rules.
- Never expose future or restricted content.
- Do not replace Moodle modules with a custom video system.
- Support video, page, book, lesson, SCORM, H5P, quiz, assignment, forum, URL, file, and other activity types through progressive enhancement.
- Provide a normal Moodle-compatible fallback where the player layout is unsuitable.

## Page coverage

Implement and style all relevant page families represented in the bundle.

### Public and shared

- Public home
- Role selection
- Login variants
- Forgotten password
- Registration where enabled
- Dashboard
- Site home
- My courses
- Course search
- Calendar
- Messages
- Notifications
- Profile
- Preferences
- Private files
- Grades overview
- Search results
- Error, empty, and maintenance states

### Student

- Student dashboard
- Course home
- Course player
- Activities overview
- Assignment view and submission
- Submission confirmation
- Quiz start, attempt, review, and result
- Forum list, discussion, and reply
- Resource, book, and lesson pages
- Grades and feedback
- Progress and completion
- Participants
- Badges
- Competencies

### Teacher

- Teacher dashboard
- Course editing mode
- Activity chooser
- Add activity/resource
- Assignment setup and grading
- Quiz setup, question bank, and grading
- Gradebook
- Participants and enrolment
- Reports
- Course settings
- Backup, restore, and import
- Content bank
- Announcements
- Calendar event management

### Administrator

- Administration dashboard
- User and cohort management
- Course and category management
- Plugins and theme settings
- Authentication and security
- Reports and logs
- Scheduled tasks
- Language and mobile settings
- General site settings

### Parent/guardian

- Parent dashboard
- Linked learner overview
- Progress summary
- Attendance only where a supporting plugin/data source exists
- Grades summary only where permissions allow
- Messages
- Calendar
- Announcements

### Other participant

- Participant dashboard
- Enrolled courses
- Course player
- Progress
- Calendar
- Messages
- Profile

Only implement features supported by Moodle core or installed plugins. For unsupported reference pages, provide a graceful placeholder and document the dependency; do not fabricate backend behavior.

## Dynamic data

All production content must come from Moodle, including:

- User name and profile image
- Role context
- Courses, images, categories, and enrolments
- Completion and progress
- Activities and restrictions
- Assignments and quizzes
- Grades and feedback
- Messages and notifications
- Calendar events
- Participants
- Badges and competencies
- Site name, logo, contact information, footer content, and public courses

Provide accessible fallbacks for missing images, completion data, events, messages, notifications, block regions, course sections, unsupported player activities, and disabled JavaScript.

## Animation

Implement subtle animations for:

- Section entrances
- Card hover states
- Navigation drawers
- Dropdowns and modals
- Role-selection transitions
- Progress bars
- Course sidebar expansion
- Player drawer opening
- Notifications
- Loading/skeleton states

Rules:

- Respect `prefers-reduced-motion`
- Never make functionality depend on animation
- Avoid large layout shifts
- Prefer opacity and transform
- Keep durations short
- Do not interfere with keyboard navigation

## Accessibility

Target WCAG 2.1 AA where practical.

Ensure:

- Semantic landmarks
- Skip link
- Correct heading hierarchy
- Visible keyboard focus
- Sufficient contrast
- Keyboard-accessible navigation, drawers, modals, and course sidebar
- Labels for icon-only controls
- Accessible forms and validation
- No color-only status communication
- Screen-reader-readable progress and activity states
- Logical DOM order
- Appropriate live regions
- Accessible tables and completion controls
- Reduced-motion support

## Theme plugin structure

Inspect and create only necessary files, typically including:

```
theme/drona/
  version.php
  config.php
  lib.php
  settings.php
  classes/
  layout/
  templates/
  scss/
  amd/src/
  amd/build/
  pix/
  lang/en/theme_drona.php
  db/
  README.md
```

## Theme settings

Provide validated settings for:

- Main logo
- Compact logo
- Favicon
- Public hero image
- Navy brand color
- Teacher color
- Student color
- Administrator color
- Parent color
- Participant color
- Guest/public accent
- Footer text
- Contact information
- Social links where supported
- Custom SCSS
- Public promotional content
- Enable/disable role selection
- Enable/disable course player
- Parent role mapping
- Participant role mapping

## Template strategy

Reuse Moodle core templates whenever possible. Override only what is required for:

- Application shell
- Public layout
- Role-aware header
- Role selection/login presentation
- Dashboard and course cards
- Course-player shell and activity sidebar
- Responsive page-title region
- Footer and empty states

For each core override:

1. Document the reason.
2. Keep it minimal.
3. Preserve core data attributes and JavaScript hooks.
4. Preserve accessibility and plugin compatibility.
5. Record upgrade risk in the implementation plan.

## JavaScript

Use Moodle AMD modules, potentially including:

- `role_selector`
- `mobile_navigation`
- `course_player`
- `course_content_drawer`
- `responsive_sidebar`
- `progress_animation`

Requirements:

- No inline JavaScript
- No global namespace pollution
- No undocumented Moodle internals where avoidable
- No unsafe DOM insertion
- Preserve browser back behavior
- Provide no-JavaScript fallbacks

## Implementation phases

### Phase 1: Discovery and mapping

- Inspect repository and bundle
- Build page inventory
- Map pages to Moodle contexts/routes
- Identify reusable components and unsupported features
- Write `docs/drona-theme-implementation-plan.md`

### Phase 2: Foundation

- Theme metadata and parent setup
- Design tokens and typography
- Application shell, header, sidebar, footer, and drawers
- Public layout
- Role resolver and role CSS classes

### Phase 3: Authentication and public site

- Public home
- Role selection and visual login variants
- Forgotten password and registration
- Guest experience and public course discovery

### Phase 4: Shared authenticated pages

- Dashboard, site home, courses, calendar, messages, notifications, profile, preferences, files, search, and grades

### Phase 5: Student experience

- Course home/player, activities, assignment, quiz, forum, resources, grades, progress, badges, and competencies

### Phase 6: Teacher experience

- Editing mode, activity chooser, assignments, quizzes, gradebook, participants, reports, backup/restore/import, and content bank

### Phase 7: Administrator experience

- Administration navigation, users, courses, categories, plugins, reports, logs, tasks, authentication, and security

### Phase 8: Parent and participant experience

- Permission-aware parent and participant dashboards and supporting pages

### Phase 9: Verification

Run all validation commands discovered in the repository, including where available:

- PHP syntax
- Moodle PHP coding standards
- Mustache validation
- SCSS compilation
- JavaScript linting
- AMD build
- PHPUnit
- Behat
- Theme installation/upgrade
- Moodle cache purge
- Browser smoke tests

## Browser validation

Where browser automation exists, test guest, student, teacher, administrator, parent, and participant experiences at:

- 320px
- 375px
- 768px
- 1024px
- 1440px

Test navigation, login, role selection, dashboard, course player, assignment submission, quiz attempt, editing mode, gradebook, administration, long names, empty states, large tables, keyboard navigation, reduced motion, and JavaScript-disabled fallbacks where practical.

## Visual comparison priorities

Compare screenshots with the reference bundle, prioritizing:

1. Functional correctness
2. Security and permissions
3. Accessibility
4. Moodle compatibility
5. Responsive behavior
6. Visual fidelity

Never sacrifice Moodle behavior for pixel-perfect matching.

## Documentation

Create or update:

- `docs/drona-theme-implementation-plan.md`
- `theme/drona/README.md`

The README must include supported Moodle version, installation, build commands, settings, role-color behavior, role mapping, public-site behavior, course-player behavior, cache purge, upgrade notes, known limitations, and plugin-dependent pages.

## Deliverables

At completion provide:

1. Working theme implementation
2. Implementation plan
3. Theme README
4. Created and modified file lists
5. Completed page families
6. Exact tests run and results
7. Known limitations
8. Manual verification steps
9. Any reference pages not implemented and the exact reason

## Definition of done

The work is complete only when:

- Moodle recognizes and installs `theme_drona`
- No Moodle core files are modified
- Theme is selectable in Site administration
- Public pages work without login where permissions allow
- Login variants preserve one secure Moodle authentication flow
- Role identities are contextual and never bypass permissions
- Main layouts render without fatal errors
- Student, teacher, administrator, parent, and participant pages use Moodle data
- Course player respects availability and completion
- Navigation, editing mode, forms, modals, drawers, and file pickers work
- Required responsive widths work
- Reduced-motion support works
- Placeholder data is removed
- No private information is exposed publicly
- Tests pass or unavoidable failures are precisely documented
- README and implementation plan are complete
- Temporary files, caches, screenshots, and debugging code are absent from the final diff

## Working method

- Work directly in the repository.
- Begin with repository and bundle inspection.
- Write the implementation plan before broad production changes.
- Make small, reviewable changes.
- Run validation after each page family.
- Never claim a test passed unless it was run.
- Report exact errors when commands fail.
- Inspect the final git diff and remove unrelated changes.
- Continue through implementation and verification unless blocked by a material architectural decision.

Begin now by inspecting the repository, reading all `AGENTS.md` files, reviewing this complete reference bundle, and writing `docs/drona-theme-implementation-plan.md`.

---

## Recommended second-pass audit prompt

After the first implementation pass, use this prompt:

> Perform a complete production-readiness audit of the current `theme_drona` implementation against this reference bundle, the implementation plan, repository `AGENTS.md` files, and Moodle 5.0.2 conventions. Directly fix all reproducible issues involving guest data exposure, role-context resolution, multiple-role users, role colors, responsive overflow, course-player restrictions and completion, editing mode, admin navigation, forms, tables, modals, file pickers, messages, notifications, calendar, long labels, RTL, static placeholders, hard-coded URLs, language strings, capability checks, output escaping, Mustache validity, inline CSS/JS, non-AMD JavaScript, unnecessary template overrides, SCSS duplication, reduced motion, keyboard navigation, focus management, contrast, no-JavaScript fallbacks, settings validation, and Moodle 5.0.2 compatibility. Run all available validation commands and browser tests at 320, 375, 768, 1024, and 1440 pixels for guest, student, teacher, administrator, parent, and participant roles. Apply fixes directly, then report exact commands, results, files changed, defects fixed, remaining limitations, and intentional differences retained for Moodle compatibility.


## Administrator course-card controls
The bundle includes an administrator-only course management view in `courses-admin.html`. Cards reveal quick actions on hover or keyboard focus and expose a complete action menu for editing, enrolments, reports, duplication, visibility and deletion. In Moodle integration, every action must be capability-checked and sourced from Moodle APIs.


## Administrator course list compatibility
The administrator catalogue now includes responsive grid and list presentation modes, persistent local view preference, hover/focus quick actions, role-aware action menus, visibility badges, and mobile-safe controls. In Moodle, all actions must be connected to capability checks and native course-management URLs; the display switch is presentation only.


## Standard LMS footer requirement
Implement the expanded public footer and compact authenticated footer shown in the HTML bundle. Use Moodle theme settings and language strings for contact information, social links, legal links, CTA content, and institutional navigation. Preserve Moodle standard footer output, login information, debugging/performance output, accessibility links, and plugin-injected content. Links must be filtered by authentication state and capabilities. The footer must collapse responsively, remain keyboard accessible, and respect reduced motion.
