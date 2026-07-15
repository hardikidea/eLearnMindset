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
 * Role-aware login endpoint for the Custom LMS role selector.
 *
 * @package    theme_custom_lms
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/login/lib.php');
require_once($CFG->libdir . '/sessionlib.php');

redirect_if_major_upgrade_required();

$role = required_param('role', PARAM_ALPHANUMEXT);
$username = required_param('username', PARAM_RAW_TRIMMED);
$password = required_param('password', PARAM_RAW);
$logintoken = required_param('logintoken', PARAM_RAW_TRIMMED);

if (!\theme_custom_lms\local\role_access::is_managed_role($role)) {
    throw new moodle_exception('rolelogininvalidrole', 'theme_custom_lms', new moodle_url('/'));
}

$repository = new \theme_custom_lms\local\bundle_page_repository();
$loginpage = \theme_custom_lms\local\role_access::login_page_for_role($role);
$targetpage = \theme_custom_lms\local\role_access::target_page_for_role($role);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/theme/custom_lms/role_login.php'));
$PAGE->set_pagelayout('login');
$PAGE->set_cacheable(false);

$redirectparams = ['page' => $loginpage];
$loginpageurl = new moodle_url('/theme/custom_lms/page.php', $redirectparams);

$submitteduser = \theme_custom_lms\local\role_access::find_login_user($username);
if ($submitteduser && !\theme_custom_lms\local\role_access::user_matches_role($submitteduser, $role)) {
    $loginpageurl->param('roleerror', 1);
    redirect($loginpageurl);
}

$username = trim(core_text::strtolower($username));
$errorcode = 0;
$user = authenticate_user_login($username, $password, false, $errorcode, $logintoken, false);
if (!$user) {
    $loginpageurl->param('loginerror', 1);
    redirect($loginpageurl);
}

if (!\theme_custom_lms\local\role_access::user_matches_role($user, $role)) {
    $loginpageurl->param('roleerror', 1);
    redirect($loginpageurl);
}

if (empty($user->confirmed)) {
    $loginpageurl->param('loginerror', 1);
    redirect($loginpageurl);
}

complete_user_login($user);
\core\session\manager::apply_concurrent_login_limit($user->id, session_id());

if (!empty($CFG->nolastloggedin)) {
    // The site is configured not to remember the last login username.
} else if (empty($CFG->rememberusername)) {
    set_moodle_cookie('');
} else {
    set_moodle_cookie($USER->username);
}

$SESSION->wantsurl = $role === 'admin'
    ? (new moodle_url('/theme/custom_lms/admin_dashboard.php'))
    : $repository->page_url($targetpage);
redirect(new moodle_url('/login/index.php', ['testsession' => $USER->id]));
