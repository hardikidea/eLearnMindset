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
 * Theme functions.
 *
 * @package    theme_custom_lms
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Post process the CSS tree.
 *
 * @param string $tree The CSS tree.
 * @param theme_config $theme The theme config object.
 */
function theme_custom_lms_css_tree_post_processor($tree, $theme) {
    error_log('theme_custom_lms_css_tree_post_processor() is deprecated. Required' .
        'prefixes for Bootstrap are now in theme/custom_lms/scss/moodle/prefixes.scss');
    $prefixer = new theme_custom_lms\autoprefixer($tree);
    $prefixer->prefix();
}

/**
 * Inject additional SCSS.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_custom_lms_get_extra_scss($theme) {
    $content = '';
    $imageurl = $theme->setting_file_url('backgroundimage', 'backgroundimage');

    // Sets the background image, and its settings.
    if (!empty($imageurl)) {
        $content .= '@media (min-width: 768px) {';
        $content .= 'body { ';
        $content .= "background-image: url('$imageurl'); background-size: cover;";
        $content .= ' } }';
    }

    // Sets the login background image.
    $loginbackgroundimageurl = $theme->setting_file_url('loginbackgroundimage', 'loginbackgroundimage');
    $backgroundposition = '';
    $isdefaultloginimage = empty($loginbackgroundimageurl);
    if ($isdefaultloginimage) {
        // Use the default login background image.
        $loginbackgroundimageurl = $theme->image_url(
            'login_background',
            'theme',
        );
        // Set the default background position to center.
        $backgroundposition = 'background-position: center;';
    }
    $content .= 'body.pagelayout-login #page .login-layout-left { ';
    $content .= "background-image: url('$loginbackgroundimageurl'); ";
    $content .= "background-size: cover; {$backgroundposition}";
    $content .= ' }';

    // Add a watermark to indicate the image is AI generated, but only for the default image.
    if ($isdefaultloginimage) {
        $content .= 'body.pagelayout-login #page .login-layout-left::after {';
        // Escape the label for use in a CSS string value: collapse newlines (which would break the CSS string)
        // and escape single quotes and backslashes via addcslashes.
        $ailabel = preg_replace('/[\r\n]+/', ' ', get_string('aigeneratedimage', 'theme_custom_lms'));
        $content .= " content: '" . addcslashes($ailabel, "'\\") . "';";
        $content .= ' position: absolute; bottom: 1rem; right: 1rem;';
        $content .= ' color: $white;';
        $content .= ' font-size: 0.8rem;';
        $content .= ' text-shadow: 0 1px 2px $black;';
        $content .= ' pointer-events: none;';
        $content .= ' }';
    }

    // Always return the background image with the scss when we have it.
    return !empty($theme->settings->scss) ? "{$theme->settings->scss}  \n  {$content}" : $content;
}

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function theme_custom_lms_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    if ($context->contextlevel == CONTEXT_SYSTEM && ($filearea === 'logo' || $filearea === 'backgroundimage' ||
        $filearea === 'loginbackgroundimage')) {
        $theme = theme_config::load('custom_lms');
        // By default, theme files must be cache-able by both browsers and proxies.
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    } else {
        send_file_not_found();
    }
}

/**
 * Get the current user preferences that are available
 *
 * @return array[]
 */
function theme_custom_lms_user_preferences(): array {
    return [
        'drawer-open-block' => [
            'type' => PARAM_BOOL,
            'null' => NULL_NOT_ALLOWED,
            'default' => false,
            'permissioncallback' => [core_user::class, 'is_current_user'],
        ],
        'drawer-open-index' => [
            'type' => PARAM_BOOL,
            'null' => NULL_NOT_ALLOWED,
            'default' => true,
            'permissioncallback' => [core_user::class, 'is_current_user'],
        ],
    ];
}

/**
 * Whether the current page should render in the custom LMS site-admin shell.
 *
 * This intentionally keeps normal teacher/student/manager account pages on their
 * regular layouts. The extended account routes are only promoted to the admin
 * shell for logged-in site administrators.
 *
 * @return bool
 */
function theme_custom_lms_uses_admin_shell(): bool {
    global $PAGE;

    $pageurlpath = parse_url($PAGE->url->out(false), PHP_URL_PATH) ?: '';
    $pagepagetype = $PAGE->pagetype ?? '';
    $isloggedinsiteadmin = isloggedin() && !isguestuser() && is_siteadmin();

    $siteadminrouteprefixes = [
        '/ai/',
        '/cohort/',
        '/report/',
        '/reportbuilder/',
    ];
    $siteadminroutes = [
        '/tag/manage.php',
    ];

    $issiteadminroute = in_array($pageurlpath, $siteadminroutes, true);
    foreach ($siteadminrouteprefixes as $prefix) {
        if (strpos($pageurlpath, $prefix) === 0) {
            $issiteadminroute = true;
            break;
        }
    }

    $siteadminaccountprefixes = [
        '/badges/',
        '/blog/',
        '/calendar/',
        '/grade/report/',
        '/message/',
        '/report/',
        '/reportbuilder/',
        '/user/',
    ];
    $siteadminaccountroutes = [
        '/course/switchrole.php',
        '/login/change_password.php',
    ];

    $issiteadminaccountroute = $isloggedinsiteadmin && in_array($pageurlpath, $siteadminaccountroutes, true);
    if (!$issiteadminaccountroute && $isloggedinsiteadmin) {
        foreach ($siteadminaccountprefixes as $prefix) {
            if (strpos($pageurlpath, $prefix) === 0) {
                $issiteadminaccountroute = true;
                break;
            }
        }
    }

    $issystemcontext = isset($PAGE->context) && $PAGE->context->contextlevel == CONTEXT_SYSTEM;

    return $PAGE->pagelayout === 'admin' ||
        strpos($pageurlpath, '/admin/') === 0 ||
        strpos($pagepagetype, 'admin-') === 0 ||
        ($issystemcontext && $issiteadminroute) ||
        $issiteadminaccountroute;
}

/**
 * Whether the current page should show the student course experience panel.
 *
 * @return bool
 */
function theme_custom_lms_is_student_course_view(): bool {
    global $COURSE, $PAGE, $USER;

    if (!isloggedin() || isguestuser() || empty($COURSE->id) || $COURSE->id == SITEID) {
        return false;
    }

    if (strpos($PAGE->pagetype ?? '', 'course-view') !== 0) {
        return false;
    }

    $coursecontext = context_course::instance($COURSE->id, IGNORE_MISSING);
    if (!$coursecontext) {
        return false;
    }

    if (is_siteadmin() ||
            has_capability('moodle/course:update', $coursecontext) ||
            has_capability('moodle/course:manageactivities', $coursecontext)) {
        return false;
    }

    return \theme_custom_lms\local\role_access::primary_role_for_user($USER ?? null) === 'student' ||
        is_enrolled($coursecontext, $USER, '', true);
}

/**
 * Build data for the student course experience panel from real Moodle course data.
 *
 * @return array
 */
function theme_custom_lms_student_course_view_context(): array {
    global $CFG, $COURSE, $USER;

    if (!theme_custom_lms_is_student_course_view()) {
        return ['isstudentcourseview' => false];
    }

    require_once($CFG->libdir . '/completionlib.php');
    require_once($CFG->libdir . '/enrollib.php');

    $coursecontext = context_course::instance($COURSE->id);
    $completion = new completion_info($COURSE);
    $modinfo = get_fast_modinfo($COURSE, $USER->id);
    $completionenabled = $completion->is_enabled();

    $visiblecms = [];
    $trackedcount = 0;
    $completedcount = 0;
    $activitycount = 0;
    $quizcount = 0;
    $assignmentcount = 0;
    $resourcecount = 0;
    $nextcm = null;
    $announcementurl = null;
    $certificateurl = null;

    foreach ($modinfo->get_cms() as $cm) {
        if (!$cm->uservisible || !empty($cm->deletioninprogress)) {
            continue;
        }

        $visiblecms[] = $cm;
        $activitycount++;

        if ($cm->modname === 'quiz') {
            $quizcount++;
        } else if ($cm->modname === 'assign') {
            $assignmentcount++;
        } else if (in_array($cm->modname, ['book', 'folder', 'page', 'resource', 'url'], true)) {
            $resourcecount++;
        }

        $isannouncement = $cm->modname === 'forum' && core_text::strtolower($cm->name) === 'announcements';
        $iscertificate = $cm->modname === 'customcert';

        if ($isannouncement && !$announcementurl) {
            $announcementurl = $cm->url ? $cm->url->out(false) : null;
        }

        if ($iscertificate && !$certificateurl) {
            $certificateurl = $cm->url ? $cm->url->out(false) : null;
        }

        $iscomplete = false;
        if ($completionenabled && $cm->completion != COMPLETION_TRACKING_NONE) {
            $trackedcount++;
            $data = $completion->get_data($cm, false, $USER->id);
            $iscomplete = in_array((int)$data->completionstate, [
                COMPLETION_COMPLETE,
                COMPLETION_COMPLETE_PASS,
            ], true);
            if ($iscomplete) {
                $completedcount++;
            }
        }

        if (!$nextcm && $cm->url && !$isannouncement && !$iscertificate &&
                (!$iscomplete || $cm->completion == COMPLETION_TRACKING_NONE)) {
            $nextcm = $cm;
        }
    }

    if (!$nextcm && !empty($visiblecms)) {
        foreach ($visiblecms as $cm) {
            $isannouncement = $cm->modname === 'forum' && core_text::strtolower($cm->name) === 'announcements';
            $iscertificate = $cm->modname === 'customcert';
            if ($cm->url && !$isannouncement && !$iscertificate) {
                $nextcm = $cm;
                break;
            }
        }
    }

    $progress = 0;
    if (class_exists('\core_completion\progress')) {
        $percentage = \core_completion\progress::get_course_progress_percentage($COURSE, $USER->id);
        if ($percentage !== null) {
            $progress = (int)round($percentage);
        }
    }
    if ($progress === 0 && $trackedcount > 0) {
        $progress = (int)round(($completedcount / $trackedcount) * 100);
    }

    $teacher = null;
    $teachers = get_enrolled_users(
        $coursecontext,
        'moodle/course:update',
        0,
        'u.id, u.firstname, u.lastname',
        'u.lastname ASC, u.firstname ASC',
        0,
        1
    );
    if ($teachers) {
        $teacher = reset($teachers);
    }

    $teachername = $teacher ? fullname($teacher) : get_string('teacher', 'core');
    $teacherinitials = 'T';
    if ($teacher) {
        $teacherinitials = core_text::strtoupper(
            core_text::substr(trim($teacher->firstname ?? ''), 0, 1) .
            core_text::substr(trim($teacher->lastname ?? ''), 0, 1)
        ) ?: 'T';
    }

    $firstname = trim($USER->firstname ?? '');
    $nexttitle = $nextcm ? format_string($nextcm->name, true, ['context' => $nextcm->context]) : 'Review course sections';
    $nexturl = $nextcm && $nextcm->url ? $nextcm->url->out(false) : (new moodle_url('/course/view.php', ['id' => $COURSE->id]))->out(false);
    $messageurl = $teacher ? (new moodle_url('/message/index.php', ['id' => $teacher->id]))->out(false) : null;

    return [
        'isstudentcourseview' => true,
        'studentcourse_firstname' => $firstname !== '' ? $firstname : fullname($USER),
        'studentcourse_progress' => $progress,
        'studentcourse_progress_style' => '--student-course-progress:' . max(0, min(100, $progress)) . '%',
        'studentcourse_completed_count' => $completedcount,
        'studentcourse_total_count' => $trackedcount ?: $activitycount,
        'studentcourse_activity_count' => $activitycount,
        'studentcourse_resource_count' => $resourcecount,
        'studentcourse_quiz_count' => $quizcount,
        'studentcourse_assignment_count' => $assignmentcount,
        'studentcourse_next_title' => $nexttitle,
        'studentcourse_next_url' => $nexturl,
        'studentcourse_teacher_name' => $teachername,
        'studentcourse_teacher_initials' => $teacherinitials,
        'studentcourse_has_teacher_message' => !empty($messageurl),
        'studentcourse_teacher_message_url' => $messageurl,
        'studentcourse_has_announcement' => !empty($announcementurl),
        'studentcourse_announcement_url' => $announcementurl,
        'studentcourse_has_certificate' => !empty($certificateurl),
        'studentcourse_certificate_url' => $certificateurl,
    ];
}

/**
 * Returns the main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_custom_lms_get_main_scss_content($theme) {
    global $CFG;

    $scss = '';
    $filename = !empty($theme->settings->preset) ? $theme->settings->preset : null;
    $fs = get_file_storage();

    $context = context_system::instance();
    if ($filename == 'default.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/custom_lms/scss/preset/default.scss');
    } else if ($filename == 'plain.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/custom_lms/scss/preset/plain.scss');
    } else if ($filename && ($presetfile = $fs->get_file($context->id, 'theme_custom_lms', 'preset', 0, '/', $filename))) {
        $scss .= $presetfile->get_content();
    } else {
        // Safety fallback - maybe new installs etc.
        $scss .= file_get_contents($CFG->dirroot . '/theme/custom_lms/scss/preset/default.scss');
    }

    return $scss;
}

/**
 * Get compiled css.
 *
 * @return string compiled css
 */
function theme_custom_lms_get_precompiled_css() {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/custom_lms/style/moodle.css');
}

/**
 * Get SCSS to prepend.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_custom_lms_get_pre_scss($theme) {
    global $CFG;

    $scss = '';
    $configurable = [
        // Config key => [variableName, ...].
        'brandcolor' => ['primary'],
    ];

    // Prepend variables first.
    foreach ($configurable as $configkey => $targets) {
        $value = isset($theme->settings->{$configkey}) ? $theme->settings->{$configkey} : null;
        if (empty($value)) {
            continue;
        }
        array_map(function($target) use (&$scss, $value) {
            $scss .= '$' . $target . ': ' . $value . ";\n";
        }, (array) $targets);
    }

    // Add a new variable to indicate that we are running behat.
    if (defined('BEHAT_SITE_RUNNING')) {
        $scss .= "\$behatsite: true;\n";
    }

    // Prepend pre-scss.
    if (!empty($theme->settings->scsspre)) {
        $scss .= $theme->settings->scsspre;
    }

    return $scss;
}
