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
 * Controlled renderer for the Custom LMS HTML bundle.
 *
 * @package    theme_custom_lms
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$requestedpage = optional_param('page', 'public-home', PARAM_ALPHANUMEXT);
$requestedpage = preg_replace('/\.html$/', '', $requestedpage);

$repository = new \theme_custom_lms\local\bundle_page_repository();
if (!$repository->exists($requestedpage)) {
    throw new moodle_exception('bundlepagenotfound', 'theme_custom_lms', new moodle_url('/'));
}

$metadata = $repository->get_metadata($requestedpage);
$context = context_system::instance();
$url = new moodle_url('/theme/custom_lms/page.php', ['page' => $requestedpage]);
$loginredirecttargets = [
    'login-admin' => 'admin',
    'login-teacher' => 'teacher-dashboard',
    'login-student' => 'index',
    'login-parent' => 'index',
    'login-participant' => 'index',
];

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('embedded');
$PAGE->set_title($metadata['title']);
$PAGE->set_heading($SITE->fullname);
$PAGE->requires->css(new moodle_url('/theme/custom_lms/style/bundle_pages.css'));
$PAGE->requires->css(new moodle_url('/theme/custom_lms/style/role_tokens.css'));
$isrolelogin = $requestedpage === 'role-login';
if ($isrolelogin) {
    $PAGE->requires->css(new moodle_url('/theme/custom_lms/style/role_login.css'));
}
$PAGE->requires->js_call_amd('theme_custom_lms/bundle_pages', 'init');
$isstudentshell = in_array($requestedpage, ['index', 'my-courses'], true);
if ($isstudentshell) {
    $PAGE->requires->css(new moodle_url('/theme/custom_lms/style/student_dashboard.css'));
    $PAGE->requires->css(new moodle_url('/theme/custom_lms/style/student_dialogs.css'));
}

if ($metadata['access'] === 'admin') {
    require_login();
    if (!is_siteadmin()) {
        throw new required_capability_exception($context, 'moodle/site:config', 'nopermissions', '');
    }
} else if ($metadata['access'] === 'login') {
    require_login();
}

$pagerole = $metadata['role'] ?? 'public';
if ($pagerole === '' || !preg_match('/^[a-z0-9_-]+$/', $pagerole)) {
    $pagerole = 'public';
}
if ($metadata['access'] !== 'public' &&
        \theme_custom_lms\local\role_access::is_managed_role($pagerole) &&
        !\theme_custom_lms\local\role_access::user_can_access_page($USER, $pagerole, $requestedpage)) {
    $loginpage = \theme_custom_lms\local\role_access::login_page_for_role($pagerole);
    redirect(new moodle_url('/theme/custom_lms/page.php', [
        'page' => $loginpage,
        'roleerror' => 1,
    ]));
}

if (isset($loginredirecttargets[$requestedpage])) {
    $SESSION->wantsurl = $repository->page_url($loginredirecttargets[$requestedpage]);
}

$bundlepage = new \theme_custom_lms\output\bundle_page($requestedpage);
$templatecontext = $bundlepage->export_for_template($OUTPUT);
$templatecontext['output'] = $OUTPUT;
$templatecontext['bodyattributes'] = $OUTPUT->body_attributes([
    'theme-custom-lms-bundle',
    'theme-custom-lms-bundle-page-' . $requestedpage,
    'custom-lms-role-' . $pagerole,
    $isstudentshell ? 'custom-lms-student-shell-page' : '',
]);
$templatecontext['isstudentshell'] = $isstudentshell;
$templatecontext['pagecontent'] = $OUTPUT->render_from_template($metadata['template'], $templatecontext);
$templatecontext['standardafterhtml'] = $OUTPUT->standard_after_main_region_html();
$templatecontext['standardendhtml'] = $PAGE->requires->get_end_code();

echo $OUTPUT->render_from_template('theme_custom_lms/bundle_page', $templatecontext);
