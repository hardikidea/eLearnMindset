# Standard LMS footer update

The public homepage now includes a responsive four-column LMS footer inspired by the supplied reference. It contains contact information, social links, homepage navigation, course navigation, a portal call-to-action, utility links, legal links, staff access, back-to-top and catalogue controls.

All other prototype pages include a compact shared footer with public-site, courses, support, accessibility and role-switch links.

## Moodle implementation guidance

Populate contact details, legal links and CTA content through theme settings and language strings. Show links according to authentication state and capabilities. Preserve Moodle's standard `standard_footer_html`, performance/debug output, login info and accessibility controls. Do not hard-code private links or use footer navigation as authorization.
