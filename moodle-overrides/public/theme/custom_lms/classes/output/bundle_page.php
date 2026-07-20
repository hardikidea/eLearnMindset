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
 * Bundle page template data.
 *
 * @package    theme_custom_lms
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_custom_lms\output;

use context_course;
use context_system;
use core_course_category;
use moodle_url;
use renderable;
use stdClass;
use templatable;
use theme_custom_lms\local\bundle_page_repository;
use theme_custom_lms\local\role_access;
use theme_custom_lms\local\student_user_menu;

/**
 * Exports Moodle-backed data for generated Custom LMS pages.
 */
class bundle_page implements renderable, templatable {

    /** @var string Page slug. */
    private string $page;

    /**
     * Constructor.
     *
     * @param string $page Page slug.
     */
    public function __construct(string $page) {
        $this->page = $page;
    }

    /**
     * Export data for the page template.
     *
     * @param object $output Renderer.
     * @return array
     */
    public function export_for_template(object $output): array {
        global $CFG, $DB, $PAGE, $SITE, $USER, $release;

        require_once($CFG->libdir . '/enrollib.php');
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/calendar/lib.php');
        require_once($CFG->libdir . '/completionlib.php');

        $repository = new bundle_page_repository();
        $metadata = $repository->get_metadata($this->page);
        $systemcontext = context_system::instance();
        $isloggedin = isloggedin() && !isguestuser();

        $fullname = $isloggedin ? fullname($USER) : get_string('guestuser');
        $rolelabel = $this->role_label($metadata['role'], $systemcontext);
        $sitefullname = format_string($SITE->fullname, true, ['context' => $systemcontext]);

        $context = [
            'title' => $metadata['title'],
            'role' => $metadata['role'],
            'access' => $metadata['access'],
            'template' => $metadata['template'],
            'sitename' => $sitefullname,
            'schoolname' => $sitefullname,
            'platformname' => get_config('core', 'sitename') ?: $sitefullname,
            'moodleversionlabel' => 'Moodle ' . ($release ?? ''),
            'userfullname' => $fullname,
            'userfirstname' => $isloggedin ? format_string($USER->firstname) : get_string('guestuser'),
            'userinitials' => $this->initials($fullname),
            'userpicture' => $isloggedin ? $output->user_picture($USER, [
                'size' => 96,
                'class' => 'student-dashboard-user-picture',
                'alttext' => true,
            ]) : '',
            'rolelabel' => $rolelabel,
            'isloggedin' => $isloggedin,
            'coursecount' => max(0, $DB->count_records_select('course', 'id <> ?', [$SITE->id])),
            'usercount' => $DB->count_records_select('user', 'deleted = 0 AND suspended = 0 AND id > 1'),
            'copyrightyear' => userdate(time(), '%Y'),
            'studentnavdashboard' => $this->page === 'index',
            'studentnavcourses' => $this->page === 'my-courses',
            'studentnavhome' => false,
            'studentnavcontentbank' => false,
        ];

        if ($this->page === 'role-login') {
            $context['studentcount'] = $this->role_user_count(['student']);
            $context['teachercount'] = $this->role_user_count(['editingteacher', 'teacher']);
        }

        $context += $repository->get_page_url_context();
        if ($isloggedin) {
            $context['url_moodle_profile'] = (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false);

            // Keep account, notification, and messaging permissions and behaviour owned by Moodle core.
            $primary = new \core\navigation\output\primary($PAGE);
            $primarymenu = $primary->export_for_template($PAGE->get_renderer('core'));

            student_user_menu::prepare($primarymenu['user']['items'], (int) $USER->id);
            $context['usermenu'] = $primarymenu['user'];
            $context['navbarpluginoutput'] = $output->navbar_plugin_output();
        }
        if ($metadata['access'] === 'admin') {
            $context = array_replace($context, $this->admin_moodle_url_context());
        }
        $context += $this->image_context($output);
        $context += $this->login_context($repository);

        $courses = $this->course_cards($output);
        $context['courses'] = $courses;
        $context['hascourses'] = !empty($courses);
        $context['currentcourse_fullname'] = $courses[0]['fullname'] ?? get_string('course');

        $dashboardcourses = array_slice($courses, 0, 3);
        $completedcoursecount = 0;
        $inprogresscoursecount = 0;
        $notstartedcoursecount = 0;
        $totalprogress = 0;
        foreach ($courses as $course) {
            $progress = (int)$course['progress'];
            $totalprogress += $progress;
            if ($progress >= 100) {
                $completedcoursecount++;
            } else if ($progress > 0) {
                $inprogresscoursecount++;
            } else {
                $notstartedcoursecount++;
            }
        }

        $overallprogress = empty($courses) ? 0 : (int)round($totalprogress / count($courses));
        $context['dashboardcourses'] = $dashboardcourses;
        $context['hasdashboardcourses'] = !empty($dashboardcourses);
        $context['enrolledcoursecount'] = count($courses);
        $context['completedcoursecount'] = $completedcoursecount;
        $context['inprogresscoursecount'] = $inprogresscoursecount;
        $context['notstartedcoursecount'] = $notstartedcoursecount;
        $context['overallprogress'] = $overallprogress;
        $context['overallprogressstyle'] = 'width:' . $overallprogress . '%';
        $context['firstcourseurl'] = $dashboardcourses[0]['courseurl'] ?? $repository->page_url('my-courses');
        $context['firstcourse'] = $dashboardcourses[0] ?? null;

        $activitysummary = $this->activity_completion_summary($courses);
        $context['completedactivitycount'] = $activitysummary['completed'];
        $context['pendingactivitycount'] = $activitysummary['pending'];

        $upcomingevents = $this->upcoming_events();
        $context['upcomingevents'] = $upcomingevents;
        $context['hasupcomingevents'] = !empty($upcomingevents);

        $announcement = $this->latest_announcement($courses);
        $context['latestannouncement'] = $announcement;
        $context['haslatestannouncement'] = $announcement !== null;

        $context['unreadnotificationcount'] = $isloggedin
            ? (int)\message_popup\api::count_unread_popup_notifications($USER->id)
            : 0;
        $context['hasunreadnotifications'] = $context['unreadnotificationcount'] > 0;

        $managedcourses = $this->managed_course_cards($output, $systemcontext);
        $context['managedcourses'] = $managedcourses;
        $context['hasmanagedcourses'] = !empty($managedcourses);

        return $context;
    }

    /**
     * Count distinct active users assigned to any of the supplied role shortnames.
     *
     * @param string[] $shortnames Moodle role shortnames.
     * @return int
     */
    private function role_user_count(array $shortnames): int {
        global $DB;

        [$rolesql, $params] = $DB->get_in_or_equal($shortnames, SQL_PARAMS_NAMED, 'countrole');
        $sql = "SELECT COUNT(DISTINCT u.id)
                  FROM {user} u
                  JOIN {role_assignments} ra ON ra.userid = u.id
                  JOIN {role} r ON r.id = ra.roleid
                 WHERE r.shortname {$rolesql}
                   AND u.deleted = 0
                   AND u.suspended = 0
                   AND u.confirmed = 1";

        return (int)$DB->count_records_sql($sql, $params);
    }

    /**
     * Return visible completion-tracked activity totals for the current learner.
     *
     * @param array $courses Exported enrolled course cards.
     * @return array
     */
    private function activity_completion_summary(array $courses): array {
        global $USER;

        $summary = ['completed' => 0, 'pending' => 0];
        foreach ($courses as $coursecard) {
            try {
                $course = get_course((int)$coursecard['id']);
                $completion = new \completion_info($course);
                $modinfo = get_fast_modinfo($course, $USER->id);
                foreach ($modinfo->cms as $cm) {
                    if (!$cm->uservisible || $cm->completion === COMPLETION_TRACKING_NONE) {
                        continue;
                    }

                    $data = $completion->get_data($cm, false, $USER->id);
                    if (in_array((int)$data->completionstate, [
                            COMPLETION_COMPLETE,
                            COMPLETION_COMPLETE_PASS,
                            COMPLETION_COMPLETE_FAIL,
                        ], true)) {
                        $summary['completed']++;
                    } else {
                        $summary['pending']++;
                    }
                }
            } catch (\Throwable $error) {
                // A damaged course must not prevent the learner dashboard from loading.
                continue;
            }
        }

        return $summary;
    }

    /**
     * Return upcoming events already filtered by Moodle calendar visibility rules.
     *
     * @return array
     */
    private function upcoming_events(): array {
        global $USER;

        if (!isloggedin() || isguestuser()) {
            return [];
        }

        $now = time();
        try {
            $events = calendar_get_legacy_events($now, $now + (45 * DAYSECS), $USER->id, true, true, true, true, [], 3);
        } catch (\Throwable $error) {
            return [];
        }

        $items = [];
        foreach ($events as $event) {
            $items[] = [
                'name' => format_string($event->name),
                'day' => userdate((int)$event->timestart, '%d'),
                'month' => \core_text::strtoupper(userdate((int)$event->timestart, '%b')),
                'course' => !empty($event->courseid) ? $this->course_name((int)$event->courseid) : get_string('site'),
                'timelabel' => get_string('duein', 'theme_custom_lms', format_time(max(0, (int)$event->timestart - $now))),
                'url' => (new moodle_url('/calendar/view.php', [
                    'view' => 'day',
                    'time' => (int)$event->timestart,
                ]))->out(false),
            ];
        }

        return $items;
    }

    /**
     * Return the latest announcement visible to the current user.
     *
     * @param array $courses Exported enrolled course cards.
     * @return array|null
     */
    private function latest_announcement(array $courses): ?array {
        global $DB, $USER;

        $latest = null;
        foreach ($courses as $coursecard) {
            try {
                $modinfo = get_fast_modinfo((int)$coursecard['id'], $USER->id);
                foreach ($modinfo->get_instances_of('forum') as $cm) {
                    if (!$cm->uservisible) {
                        continue;
                    }

                    $forum = $DB->get_record('forum', ['id' => $cm->instance], 'id, type', IGNORE_MISSING);
                    if (!$forum || $forum->type !== 'news') {
                        continue;
                    }

                    $records = $DB->get_records('forum_discussions', ['forum' => $forum->id], 'timemodified DESC',
                        'id, name, timemodified', 0, 1);
                    $discussion = $records ? reset($records) : null;
                    if (!$discussion || ($latest !== null && $discussion->timemodified <= $latest['timestamp'])) {
                        continue;
                    }

                    $latest = [
                        'title' => format_string($discussion->name, true, [
                            'context' => context_course::instance((int)$coursecard['id']),
                        ]),
                        'course' => $coursecard['fullname'],
                        'date' => userdate((int)$discussion->timemodified, get_string('strftimedatetimeshort')),
                        'url' => (new moodle_url('/mod/forum/discuss.php', ['d' => $discussion->id]))->out(false),
                        'timestamp' => (int)$discussion->timemodified,
                    ];
                }
            } catch (\Throwable $error) {
                continue;
            }
        }

        if ($latest !== null) {
            unset($latest['timestamp']);
        }
        return $latest;
    }

    /**
     * Return a formatted course name without exposing unavailable records.
     *
     * @param int $courseid Course id.
     * @return string
     */
    private function course_name(int $courseid): string {
        try {
            return format_string(get_course($courseid)->fullname);
        } catch (\Throwable $error) {
            return get_string('course');
        }
    }

    /**
     * Return real Moodle administration URLs for admin bundle templates.
     *
     * @return array
     */
    private function admin_moodle_url_context(): array {
        return [
            'url_admin' => (new moodle_url('/admin/search.php'))->out(false),
            'url_users' => (new moodle_url('/admin/user.php'))->out(false),
            'url_courses_admin' => (new moodle_url('/course/management.php'))->out(false),
            'url_plugins' => (new moodle_url('/admin/plugins.php'))->out(false),
            'url_logs' => (new moodle_url('/report/log/index.php', ['id' => 0]))->out(false),
            'url_settings' => (new moodle_url('/admin/settings.php'))->out(false),
            'url_course_edit' => (new moodle_url('/course/edit.php', ['category' => 0]))->out(false),
            'url_participants' => (new moodle_url('/admin/user.php'))->out(false),
            'url_reports' => (new moodle_url('/admin/search.php', [], 'linkreports'))->out(false),
        ];
    }

    /**
     * Return login form data for role-specific login pages.
     *
     * @param bundle_page_repository $repository Bundle page registry.
     * @return array
     */
    private function login_context(bundle_page_repository $repository): array {
        $rolelabels = [
            'admin' => 'Site administrator',
            'teacher' => 'Teacher',
            'student' => 'Student',
            'parent' => 'Parent / guardian',
            'participant' => 'Other participant',
        ];

        $loginrole = role_access::role_for_login_page($this->page) ?? 'user';
        $rolelabel = $rolelabels[$loginrole] ?? 'User';
        $loginerror = optional_param('loginerror', 0, PARAM_BOOL);
        $roleerror = optional_param('roleerror', 0, PARAM_BOOL);

        $loginmessage = '';
        if ($roleerror) {
            $loginmessage = get_string('roleloginunauthorised', 'theme_custom_lms');
        } else if ($loginerror) {
            $loginmessage = get_string('rolelogininvalid', 'theme_custom_lms');
        }

        return [
            'loginurl' => (new moodle_url('/theme/custom_lms/role_login.php'))->out(false),
            'forgotpasswordurl' => (new moodle_url('/login/forgot_password.php'))->out(false),
            'logintoken' => \core\session\manager::get_login_token(),
            'loginrole' => $loginrole,
            'rolekey' => bundle_page_repository::slug_to_key($this->page),
            'loginrolename' => $rolelabel,
            'loginsubmitlabel' => 'Log in as ' . $rolelabel,
            'loginreturnurl' => $this->login_target_url($repository),
            'loginmessage' => $loginmessage,
        ] + $this->demo_login_context($loginrole);
    }

    /**
     * Return local-only demo login values for role login forms and cards.
     *
     * Passwords are not recoverable from Moodle's database because they are hashed. These values
     * are the local demo import defaults, paired with live usernames selected from the database.
     *
     * @param string $loginrole Current login role.
     * @return array
     */
    private function demo_login_context(string $loginrole): array {
        $context = [
            'loginusername' => '',
            'loginpassword' => '',
            'hasdemologin' => false,
            'showdemocredentials' => false,
        ];

        foreach (['admin', 'teacher', 'student', 'parent', 'participant'] as $role) {
            $context['demo_' . $role . '_username'] = '';
            $context['demo_' . $role . '_password'] = '';
            $context['has_demo_' . $role] = false;
        }

        if (!$this->allow_demo_credentials()) {
            return $context;
        }

        $context['showdemocredentials'] = true;
        foreach (['admin', 'teacher', 'student', 'parent', 'participant'] as $role) {
            $credentials = $this->demo_credentials_for_role($role);
            if ($credentials === null) {
                continue;
            }

            $context['demo_' . $role . '_username'] = $credentials['username'];
            $context['demo_' . $role . '_password'] = $credentials['password'];
            $context['has_demo_' . $role] = true;

            if ($role === $loginrole) {
                $context['loginusername'] = $credentials['username'];
                $context['loginpassword'] = $credentials['password'];
                $context['hasdemologin'] = true;
            }
        }

        return $context;
    }

    /**
     * Whether demo credentials may be rendered.
     *
     * @return bool
     */
    private function allow_demo_credentials(): bool {
        global $CFG;

        $host = parse_url($CFG->wwwroot, PHP_URL_HOST);
        $localhosts = ['localhost', '127.0.0.1', '::1', '[::1]'];

        return in_array($host, $localhosts, true);
    }

    /**
     * Return one database-backed demo username and its known local demo password for a role.
     *
     * @param string $role Custom LMS role.
     * @return array|null
     */
    private function demo_credentials_for_role(string $role): ?array {
        $username = $this->demo_username_for_role($role);
        if ($username === '') {
            return null;
        }

        $passwords = [
            'admin' => getenv('MOODLE_ADMIN_PASSWORD') ?: 'admin',
            'teacher' => 'DronaTeacher2026!',
            'student' => 'DronaStudent2026!',
            'parent' => 'DronaParent2026!',
            'participant' => 'DronaTeacher2026!',
        ];

        return [
            'username' => $username,
            'password' => $passwords[$role] ?? '',
        ];
    }

    /**
     * Select one active database user for a Custom LMS role.
     *
     * @param string $role Custom LMS role.
     * @return string
     */
    private function demo_username_for_role(string $role): string {
        global $DB;

        if ($role === 'admin') {
            foreach (get_admins() as $admin) {
                if (empty($admin->deleted) && empty($admin->suspended) && !empty($admin->confirmed)) {
                    return $admin->username;
                }
            }

            return '';
        }

        $rolemap = [
            'teacher' => ['editingteacher', 'teacher'],
            'student' => ['student'],
            'parent' => ['parent'],
        ];

        if (isset($rolemap[$role])) {
            [$rolesql, $params] = $DB->get_in_or_equal($rolemap[$role], SQL_PARAMS_NAMED, 'role');
            $sql = "SELECT u.username
                      FROM {user} u
                      JOIN {role_assignments} ra ON ra.userid = u.id
                      JOIN {role} r ON r.id = ra.roleid
                     WHERE r.shortname {$rolesql}
                       AND u.deleted = 0
                       AND u.suspended = 0
                       AND u.confirmed = 1
                  ORDER BY u.id";

            return (string)($DB->get_field_sql($sql, $params, IGNORE_MISSING) ?: '');
        }

        if ($role === 'participant') {
            $sql = "SELECT u.username
                      FROM {user} u
                 LEFT JOIN {role_assignments} ra ON ra.userid = u.id
                     WHERE u.deleted = 0
                       AND u.suspended = 0
                       AND u.confirmed = 1
                       AND u.id > 2
                       AND ra.id IS NULL
                  ORDER BY u.id";

            return (string)($DB->get_field_sql($sql, [], IGNORE_MISSING) ?: '');
        }

        return '';
    }

    /**
     * Return the intended post-login page for the current role login page.
     *
     * @param bundle_page_repository $repository Bundle page registry.
     * @return string
     */
    private function login_target_url(bundle_page_repository $repository): string {
        $loginrole = role_access::role_for_login_page($this->page);
        if ($loginrole === null) {
            return $repository->page_url('role-login');
        }

        return $repository->page_url(role_access::target_page_for_role($loginrole));
    }

    /**
     * Return image URLs used by the generated bundle templates.
     *
     * @param object $output Renderer.
     * @return array
     */
    private function image_context(object $output): array {
        $images = [
            'school_logo' => 'school-logo',
            'showcase_course_player' => 'showcase/course-player',
            'showcase_page_system' => 'showcase/page-system',
            'showcase_role_experience' => 'showcase/role-experience',
            'showcase_student_dashboard' => 'showcase/student-dashboard',
            'courses_business' => 'courses/business',
            'courses_chemistry' => 'courses/chemistry',
            'courses_marketing' => 'courses/marketing',
            'courses_product' => 'courses/product',
        ];

        $context = [];
        foreach ($images as $key => $image) {
            $context['pix_' . $key] = $output->image_url($image, 'theme_custom_lms')->out(false);
        }

        return $context;
    }

    /**
     * Return current user's enrolled courses for course-card templates.
     *
     * @param object $output Renderer.
     * @return array
     */
    private function course_cards(object $output): array {
        global $USER;

        if (!isloggedin() || isguestuser()) {
            return [];
        }

        $cards = [];
        $covers = ['courses/business', 'courses/product', 'courses/marketing', 'courses/chemistry'];
        $courses = enrol_get_my_courses('id, category, shortname, fullname, visible', 'fullname ASC', 12);
        foreach ($courses as $index => $course) {
            $progress = 0;
            if (class_exists('\core_completion\progress')) {
                $percentage = \core_completion\progress::get_course_progress_percentage($course, $USER->id);
                if ($percentage !== null) {
                    $progress = (int)round($percentage);
                }
            }

            $cards[] = [
                'id' => $course->id,
                'fullname' => format_string($course->fullname),
                'shortname' => format_string($course->shortname),
                'categoryname' => $this->category_name((int)$course->category),
                'teachername' => $this->teacher_name($course),
                'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                'coverurl' => $output->image_url($covers[$index % count($covers)], 'theme_custom_lms')->out(false),
                'progress' => $progress,
                'progressstyle' => 'width:' . $progress . '%',
                'visible' => (bool)$course->visible,
            ];
        }

        return $cards;
    }

    /**
     * Return admin/manager course cards for catalogue management templates.
     *
     * @param object $output Renderer.
     * @param context_system $systemcontext System context.
     * @return array
     */
    private function managed_course_cards(object $output, context_system $systemcontext): array {
        global $DB, $SITE;

        if (!has_capability('moodle/site:config', $systemcontext) &&
                !has_capability('moodle/course:create', $systemcontext) &&
                !has_capability('moodle/category:manage', $systemcontext)) {
            return [];
        }

        $cards = [];
        $covers = ['courses/business', 'courses/product', 'courses/marketing', 'courses/chemistry'];
        $courses = $DB->get_records_select('course', 'id <> ?', [$SITE->id], 'fullname ASC', 'id, category, shortname, fullname, visible', 0, 12);
        foreach (array_values($courses) as $index => $course) {
            $coursecontext = context_course::instance($course->id, IGNORE_MISSING);
            $cards[] = [
                'id' => $course->id,
                'fullname' => format_string($course->fullname),
                'shortname' => format_string($course->shortname),
                'categoryname' => $this->category_name((int)$course->category),
                'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                'editurl' => (new moodle_url('/course/edit.php', ['id' => $course->id]))->out(false),
                'participantsurl' => (new moodle_url('/user/index.php', ['id' => $course->id]))->out(false),
                'coverurl' => $output->image_url($covers[$index % count($covers)], 'theme_custom_lms')->out(false),
                'visible' => (bool)$course->visible,
                'canedit' => $coursecontext && has_capability('moodle/course:update', $coursecontext),
            ];
        }

        return $cards;
    }

    /**
     * Return a formatted category name.
     *
     * @param int $categoryid Course category id.
     * @return string
     */
    private function category_name(int $categoryid): string {
        try {
            return core_course_category::get($categoryid)->get_formatted_name();
        } catch (\Throwable $error) {
            return 'Category';
        }
    }

    /**
     * Return a concise teacher label for a course.
     *
     * @param stdClass $course Course record.
     * @return string
     */
    private function teacher_name(stdClass $course): string {
        $coursecontext = context_course::instance($course->id, IGNORE_MISSING);
        if (!$coursecontext) {
            return 'Teaching team';
        }

        $teachers = get_enrolled_users($coursecontext, 'moodle/course:update', 0, 'u.id, u.firstname, u.lastname', 'u.lastname ASC', 0, 1);
        if (!$teachers) {
            return 'Teaching team';
        }

        return fullname(reset($teachers));
    }

    /**
     * Infer a user-facing role label without using role styling as authorization.
     *
     * @param string $designrole Role from the design page metadata.
     * @param context_system $systemcontext System context.
     * @return string
     */
    private function role_label(string $designrole, context_system $systemcontext): string {
        if (has_capability('moodle/site:config', $systemcontext)) {
            return 'Site administrator';
        }
        if (has_capability('moodle/course:create', $systemcontext) || has_capability('moodle/category:manage', $systemcontext)) {
            return 'Manager';
        }

        return ucfirst($designrole);
    }

    /**
     * Return initials for avatar chips.
     *
     * @param string $fullname User full name.
     * @return string
     */
    private function initials(string $fullname): string {
        $parts = preg_split('/\s+/', trim($fullname));
        $initials = '';
        foreach ($parts as $part) {
            if ($part !== '') {
                $initials .= \core_text::strtoupper(\core_text::substr($part, 0, 1));
            }
            if (\core_text::strlen($initials) >= 2) {
                break;
            }
        }

        return $initials !== '' ? $initials : 'U';
    }
}
