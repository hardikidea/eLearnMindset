<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Extra SCSS for the eLearn Boost theme.
 *
 * This theme intentionally keeps Boost visuals and templates intact. It only
 * removes Boost's constrained content rail from drawer pages.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_elearnboost_get_extra_scss($theme) {
    return <<<'SCSS'
#page.drawers .main-inner,
#page.drawers .footer-popover,
.pagelayout-standard #page.drawers .main-inner,
.pagelayout-standard #page.drawers .footer-popover,
body.limitedwidth #page.drawers .main-inner,
body.limitedwidth #page.drawers .footer-popover {
    width: calc(100% - 2rem);
    max-width: none;
    margin-right: auto;
    margin-left: auto;
}

#page.drawers .header-maxwidth,
#page.drawers .secondary-navigation,
#page.drawers .tertiary-navigation,
#page.drawers .activity-header,
#page.drawers #page-content,
#page.drawers #region-main,
#page.drawers .course-content,
#page.drawers .dashboard-card-deck {
    max-width: none;
}

#page.drawers #region-main,
#page.drawers #page-content {
    min-width: 0;
}

@media (max-width: 767.98px) {
    #page.drawers .main-inner,
    #page.drawers .footer-popover,
    .pagelayout-standard #page.drawers .main-inner,
    .pagelayout-standard #page.drawers .footer-popover,
    body.limitedwidth #page.drawers .main-inner,
    body.limitedwidth #page.drawers .footer-popover {
        width: 100%;
        margin-right: 0;
        margin-left: 0;
        padding-right: 1rem;
        padding-left: 1rem;
        border-radius: 0;
    }

    body #page.drawers,
    body #page.drawers .main-inner,
    body #page.drawers .footer-popover,
    body #page.drawers #page-content,
    body #page.drawers #region-main {
        overflow-x: hidden !important;
    }
}
SCSS;
}
