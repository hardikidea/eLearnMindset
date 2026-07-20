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
 * A drawer based layout for the custom_lms theme.
 *
 * @package   theme_custom_lms
 * @copyright 2021 Bas Brands
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers'];
$customlmsrole = \theme_custom_lms\local\role_access::primary_role_for_user($USER ?? null);
$pageurlpath = parse_url($PAGE->url->out(false), PHP_URL_PATH) ?: '';
$iscustomlmsadmin = theme_custom_lms_uses_admin_shell();
$iscustomlmsadmindashboard = $pageurlpath === '/theme/custom_lms/admin_dashboard.php';
$iscustomlmsstudentcourse = theme_custom_lms_is_student_course_view();
$iscustomlmscoursemanagement = $pageurlpath === '/course/management.php';
$iscustomlmsstudent = !$iscustomlmsadmin && $customlmsrole === 'student' && isloggedin() && !isguestuser();
$studentcoursenavigationroutes = [
    '/course/view.php',
    '/course/section.php',
    '/course/overview.php',
    '/user/index.php',
    '/admin/tool/lp/coursecompetencies.php',
];
$isstudentcoursenavigationroute = in_array($pageurlpath, $studentcoursenavigationroutes, true) ||
    strpos($pageurlpath, '/grade/report/') === 0;
$studentcoursecontext = false;
if (isset($PAGE->context) && $PAGE->context->contextlevel === CONTEXT_COURSE) {
    $studentcoursecontext = $PAGE->context;
} elseif (!empty($PAGE->course->id) && $PAGE->course->id != SITEID) {
    $studentcoursecontext = context_course::instance($PAGE->course->id, IGNORE_MISSING);
}
$iscustomlmsstudentcoursenavigation = $iscustomlmsstudent &&
    $isstudentcoursenavigationroute &&
    $studentcoursecontext &&
    is_enrolled($studentcoursecontext, $USER, '', true);
if ($iscustomlmsadmin) {
    if (isloggedin() && !isguestuser() && !is_siteadmin()) {
        throw new required_capability_exception(context_system::instance(), 'moodle/site:config', 'nopermissions', '');
    }
    $customlmsrole = 'admin';
    $extraclasses[] = 'custom-lms-admin-page';
    $extraclasses[] = 'custom-lms-role-admin';
    if ($iscustomlmsadmindashboard) {
        $extraclasses[] = 'custom-lms-admin-dashboard-page';
    }
    if ($iscustomlmscoursemanagement) {
        $extraclasses[] = 'custom-lms-course-management-page';
    }
    $PAGE->requires->css(new moodle_url('/theme/custom_lms/style/role_tokens.css'));
    $PAGE->requires->css(new moodle_url('/theme/custom_lms/style/form_guidance.css'));
    $PAGE->requires->css(new moodle_url('/theme/custom_lms/style/navigation_tabs.css'));
    $PAGE->requires->css(new moodle_url('/theme/custom_lms/style/admin_pages.css'));
    if ($iscustomlmscoursemanagement) {
        $PAGE->requires->css(new moodle_url('/theme/custom_lms/style/course_management.css'));
    }
} else {
    $extraclasses[] = 'custom-lms-role-' . $customlmsrole;
    $PAGE->requires->css(new moodle_url('/theme/custom_lms/style/role_tokens.css'));
    $PAGE->requires->css(new moodle_url('/theme/custom_lms/style/form_guidance.css'));
    $PAGE->requires->css(new moodle_url('/theme/custom_lms/style/navigation_tabs.css'));
    if ($iscustomlmsstudentcourse) {
        $extraclasses[] = 'custom-lms-student-course-view';
        $PAGE->requires->css(new moodle_url('/theme/custom_lms/style/student_course.css'));
    }
    if ($iscustomlmsstudent) {
        $extraclasses[] = 'custom-lms-student-shell-page';
        $PAGE->requires->css(new moodle_url('/theme/custom_lms/style/student_dashboard.css'));
        $PAGE->requires->css(new moodle_url('/theme/custom_lms/style/student_native_pages.css'));
        $PAGE->requires->css(new moodle_url('/theme/custom_lms/style/student_dialogs.css'));
        $PAGE->requires->js_call_amd('theme_custom_lms/bundle_pages', 'init');
    }
}
if ($courseindexopen && !$iscustomlmsstudent) {
    $extraclasses[] = 'drawer-open-index';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}
if ($iscustomlmsstudentcourse) {
    $blockdraweropen = false;
}
if ($iscustomlmsstudent) {
    $courseindexopen = false;
    $blockdraweropen = false;
}
$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$studentcoursenavigation = [];
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    if ($iscustomlmsstudentcoursenavigation) {
        foreach ($PAGE->secondarynav->children as $navigationnode) {
            if (!$navigationnode->display || empty($navigationnode->action)) {
                continue;
            }

            $navigationaction = $navigationnode->action;
            if ($navigationaction instanceof \core\output\action_link) {
                $navigationaction = $navigationaction->url;
            }

            if (is_object($navigationaction) && method_exists($navigationaction, 'out')) {
                $navigationurl = $navigationaction->out(false);
            } elseif (is_string($navigationaction)) {
                $navigationurl = $navigationaction;
            } else {
                continue;
            }

            $navigationkey = (string) $navigationnode->key;
            $studentcoursenavigation[] = [
                'key' => $navigationkey,
                'text' => format_string($navigationnode->text, true, ['context' => $PAGE->context]),
                'url' => $navigationurl,
                'isactive' => $navigationnode->isactive,
                'iconclass' => match ($navigationkey) {
                    'coursehome' => 'fa fa-book',
                    'participants' => 'fa fa-users',
                    'grades' => 'fa fa-bar-chart',
                    'courseoverview' => 'fa fa-tasks',
                    'competencies' => 'fa fa-bullseye',
                    default => 'fa fa-link',
                },
            ];
        }
    }
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $selectmenu = new \core\output\select_menu(
            'tertiarynavigation',
            $overflowdata->urls,
            $overflowdata->selected,
        );
        $selectmenu->set_label($overflowdata->label, $overflowdata->labelattributes);
        $overflow = $selectmenu->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
if ($iscustomlmsstudent) {
    \theme_custom_lms\local\student_user_menu::prepare($primarymenu['user']['items'], (int) $USER->id);
}
$studentmobilenav = $primarymenu['mobileprimarynav'];
foreach ($studentmobilenav as &$studentnavitem) {
    $studentnavitem['iconclass'] = match ($studentnavitem['key'] ?? '') {
        'home' => 'fa fa-home',
        'myhome' => 'fa fa-tachometer',
        'mycourses' => 'fa fa-book',
        default => 'fa fa-compass',
    };
}
unset($studentnavitem);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

$coursefullname = ($PAGE->course?->fullname) ? format_string(
    $PAGE->course->fullname,
    true,
    ['context' => context_course::instance($PAGE->course->id), 'escape' => false],
) : '';
$courseurl = $PAGE->course ? new \core\url('/course/view.php', ['id' => $PAGE->course->id]) : null;
$pixschoollogo = (new moodle_url('/theme/custom_lms/pix/school-logo.jpg'))->out(false);
$admindashboardurl = (new moodle_url('/theme/custom_lms/admin_dashboard.php'))->out(false);
$studentdashboardurl = (new moodle_url('/theme/custom_lms/page.php', ['page' => 'index']))->out(false);
$studentcoursesurl = (new moodle_url('/theme/custom_lms/page.php', ['page' => 'my-courses']))->out(false);
$studenthomeurl = (new moodle_url('/'))->out(false);
$studentcontentbankurl = (new moodle_url('/contentbank/index.php'))->out(false);
$studentcoursesearchurl = (new moodle_url('/course/search.php'))->out(false);
$studentnavdashboard = $pageurlpath === '/my/index.php';
$studentnavhome = $pageurlpath === '/' || $pageurlpath === '/index.php';
$studentnavcontentbank = strpos($pageurlpath, '/contentbank/') === 0;
$studentnavcourses = !$iscustomlmsstudentcoursenavigation &&
    (strpos($pageurlpath, '/course/') === 0 || $pageurlpath === '/my/courses.php');
$userfullname = isloggedin() && !isguestuser() ? fullname($USER) : '';
$userinitials = 'AD';
if (isloggedin() && !isguestuser()) {
    $firstnameinitial = core_text::substr(trim($USER->firstname ?? ''), 0, 1);
    $lastnameinitial = core_text::substr(trim($USER->lastname ?? ''), 0, 1);
    $userinitials = core_text::strtoupper($firstnameinitial . $lastnameinitial) ?: 'AD';
}

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'coursefullname' => $coursefullname,
    'courseurl' => $courseurl ? $courseurl->out(false) : null,
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'studentcoursenavigation' => $studentcoursenavigation,
    'hasstudentcoursenavigation' => !empty($studentcoursenavigation),
    'mobileprimarynav' => $studentmobilenav,
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
    'iscustomlmsadmin' => $iscustomlmsadmin,
    'iscustomlmsadmindashboard' => $iscustomlmsadmindashboard,
    'iscustomlmsstudent' => $iscustomlmsstudent,
    'pixschoollogo' => $pixschoollogo,
    'pix_school_logo' => $pixschoollogo,
    'admindashboardurl' => $admindashboardurl,
    'schoolname' => format_string($SITE->fullname, true, ['context' => context_course::instance(SITEID)]),
    'url_index' => $studentdashboardurl,
    'url_my_courses' => $studentcoursesurl,
    'url_moodle_home' => $studenthomeurl,
    'url_moodle_content_bank' => $studentcontentbankurl,
    'url_moodle_course_search' => $studentcoursesearchurl,
    'studentnavdashboard' => $studentnavdashboard,
    'studentnavhome' => $studentnavhome,
    'studentnavcourses' => $studentnavcourses,
    'studentnavcontentbank' => $studentnavcontentbank,
    'navbarpluginoutput' => $OUTPUT->navbar_plugin_output(),
    'userfullname' => $userfullname,
    'userfirstname' => isloggedin() && !isguestuser() ? format_string($USER->firstname) : '',
    'userinitials' => $userinitials,
    'rolelabel' => get_string('student', 'grades'),
] + theme_custom_lms_student_course_view_context();

echo $OUTPUT->render_from_template('theme_custom_lms/drawers', $templatecontext);
