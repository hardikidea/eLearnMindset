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
        global $CFG, $DB, $SITE, $USER, $release;

        require_once($CFG->libdir . '/enrollib.php');
        require_once($CFG->dirroot . '/course/lib.php');

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
            'userinitials' => $this->initials($fullname),
            'rolelabel' => $rolelabel,
            'isloggedin' => $isloggedin,
            'coursecount' => max(0, $DB->count_records_select('course', 'id <> ?', [$SITE->id])),
            'usercount' => $DB->count_records_select('user', 'deleted = 0 AND suspended = 0 AND id > 1'),
        ];

        $context += $repository->get_page_url_context();
        if ($metadata['access'] === 'admin') {
            $context = array_replace($context, $this->admin_moodle_url_context());
        }
        $context += $this->image_context($output);
        $context += $this->login_context($repository);

        $courses = $this->course_cards($output);
        $context['courses'] = $courses;
        $context['hascourses'] = !empty($courses);
        $context['currentcourse_fullname'] = $courses[0]['fullname'] ?? get_string('course');

        $managedcourses = $this->managed_course_cards($output, $systemcontext);
        $context['managedcourses'] = $managedcourses;
        $context['hasmanagedcourses'] = !empty($managedcourses);

        return $context;
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
        ];
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
