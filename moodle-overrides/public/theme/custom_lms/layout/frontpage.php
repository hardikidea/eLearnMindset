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
 * Public frontpage layout for the Custom LMS theme.
 *
 * @package    theme_custom_lms
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$requestedpage = 'public-home';
$repository = new \theme_custom_lms\local\bundle_page_repository();
$metadata = $repository->get_metadata($requestedpage);

$PAGE->requires->css(new moodle_url('/theme/custom_lms/style/bundle_pages.css'));
$PAGE->requires->css(new moodle_url('/theme/custom_lms/style/role_tokens.css'));

$bundlepage = new \theme_custom_lms\output\bundle_page($requestedpage);
$templatecontext = $bundlepage->export_for_template($OUTPUT);
$templatecontext['output'] = $OUTPUT;
$templatecontext['bodyattributes'] = $OUTPUT->body_attributes([
    'theme-custom-lms-bundle',
    'theme-custom-lms-frontpage',
    'landing-page',
    'custom-lms-role-public',
]);
$templatecontext['nativecontent'] = '<div class="custom-lms-native-frontpage" hidden>' . $OUTPUT->main_content() . '</div>';
$templatecontext['pagecontent'] = $OUTPUT->render_from_template($metadata['template'], $templatecontext);

echo $OUTPUT->render_from_template('theme_custom_lms/bundle_page', $templatecontext);
