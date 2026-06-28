<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Wide Boost theme callbacks.
 *
 * @package    theme_wideboost
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/theme/boost/lib.php');

/**
 * Return the compiled SCSS source.
 *
 * @param theme_config $theme Theme config.
 * @return string
 */
function theme_wideboost_get_main_scss_content($theme) {
    global $CFG;

    $scss = file_get_contents($CFG->dirroot . '/theme/wideboost/scss/pre.scss');
    $scss .= file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
    $scss .= file_get_contents($CFG->dirroot . '/theme/wideboost/scss/components.scss');
    $scss .= file_get_contents($CFG->dirroot . '/theme/wideboost/scss/layout.scss');

    return $scss;
}

/**
 * Prepend dynamic theme SCSS.
 *
 * @param theme_config $theme Theme config.
 * @return string
 */
function theme_wideboost_get_pre_scss($theme) {
    $scss = '';

    if (!empty($theme->settings->brandcolor) && theme_wideboost_is_hex_colour($theme->settings->brandcolor)) {
        $scss .= '$primary: ' . $theme->settings->brandcolor . ";\n";
        $scss .= '$blue: ' . $theme->settings->brandcolor . ";\n";
    }

    if (defined('BEHAT_SITE_RUNNING')) {
        $scss .= "\$behatsite: true;\n";
    }

    if (!empty($theme->settings->scsspre)) {
        $scss .= $theme->settings->scsspre;
    }

    return $scss;
}

/**
 * Add extra SCSS after the main theme stack.
 *
 * @param theme_config $theme Theme config.
 * @return string
 */
function theme_wideboost_get_extra_scss($theme) {
    return !empty($theme->settings->scss) ? $theme->settings->scss : '';
}

/**
 * Return Boost's fallback CSS.
 *
 * @return string
 */
function theme_wideboost_get_precompiled_css() {
    return theme_boost_get_precompiled_css();
}

/**
 * Validate hex colours before interpolating dynamic SCSS.
 *
 * @param string $colour Colour value.
 * @return bool
 */
function theme_wideboost_is_hex_colour($colour) {
    return is_string($colour) && preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $colour);
}
