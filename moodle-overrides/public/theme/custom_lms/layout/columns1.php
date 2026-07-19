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
 * A one column layout for the custom_lms theme.
 *
 * @package   theme_custom_lms
 * @copyright 2016 Damyon Wiese
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (theme_custom_lms_uses_admin_shell()) {
    require(__DIR__ . '/drawers.php');
    return;
}

$customlmsrole = \theme_custom_lms\local\role_access::primary_role_for_user($USER ?? null);
$PAGE->requires->css(new moodle_url('/theme/custom_lms/style/role_tokens.css'));
$PAGE->requires->css(new moodle_url('/theme/custom_lms/style/form_guidance.css'));
$PAGE->requires->css(new moodle_url('/theme/custom_lms/style/navigation_tabs.css'));

$bodyattributes = $OUTPUT->body_attributes(['custom-lms-role-' . $customlmsrole]);
$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
];

if (empty($PAGE->layout_options['noactivityheader'])) {
    $header = $PAGE->activityheader;
    $renderer = $PAGE->get_renderer('core');
    $templatecontext['headercontent'] = $header->export_for_template($renderer);
}

echo $OUTPUT->render_from_template('theme_custom_lms/columns1', $templatecontext);
