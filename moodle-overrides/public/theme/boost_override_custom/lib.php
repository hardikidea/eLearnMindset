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
 * Boost Override Custom theme callbacks.
 *
 * @package    theme_boost_override_custom
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns Boost's main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_boost_override_custom_get_main_scss_content($theme): string {
    global $CFG;

    require_once($CFG->dirroot . '/theme/boost/lib.php');
    return theme_boost_get_main_scss_content($theme);
}

/**
 * Get SCSS to prepend.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_boost_override_custom_get_pre_scss($theme): string {
    global $CFG;

    require_once($CFG->dirroot . '/theme/boost/lib.php');
    return theme_boost_get_pre_scss($theme);
}

/**
 * Inject additional SCSS.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_boost_override_custom_get_extra_scss($theme): string {
    global $CFG;

    require_once($CFG->dirroot . '/theme/boost/lib.php');
    return theme_boost_get_extra_scss($theme);
}

/**
 * Get compiled Boost CSS as the base stylesheet.
 *
 * @return string
 */
function theme_boost_override_custom_get_precompiled_css(): string {
    global $CFG;

    return file_get_contents($CFG->dirroot . '/theme/boost/style/moodle.css');
}
