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
 * Role-aware access helpers for Custom LMS bundle pages.
 *
 * @package    theme_custom_lms
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_custom_lms\local;

use stdClass;

/**
 * Centralises role checks used by custom role login and role-specific pages.
 */
class role_access {

    /** @var string[] Custom LMS roles that map to Moodle role data. */
    private const MANAGED_ROLES = [
        'admin',
        'teacher',
        'student',
        'parent',
        'participant',
    ];

    /**
     * Return whether a role is managed by the custom role-login flow.
     *
     * @param string $role Role key.
     * @return bool
     */
    public static function is_managed_role(string $role): bool {
        return in_array($role, self::MANAGED_ROLES, true);
    }

    /**
     * Return the login role for a bundle login page.
     *
     * @param string $page Page slug.
     * @return string|null
     */
    public static function role_for_login_page(string $page): ?string {
        $map = [
            'login-admin' => 'admin',
            'login-teacher' => 'teacher',
            'login-student' => 'student',
            'login-parent' => 'parent',
            'login-participant' => 'participant',
        ];

        return $map[$page] ?? null;
    }

    /**
     * Return the login page for a custom role.
     *
     * @param string $role Role key.
     * @return string
     */
    public static function login_page_for_role(string $role): string {
        $map = [
            'admin' => 'login-admin',
            'teacher' => 'login-teacher',
            'student' => 'login-student',
            'parent' => 'login-parent',
            'participant' => 'login-participant',
        ];

        return $map[$role] ?? 'role-login';
    }

    /**
     * Return the target page after a successful role login.
     *
     * @param string $role Role key.
     * @return string
     */
    public static function target_page_for_role(string $role): string {
        $map = [
            'admin' => 'admin',
            'teacher' => 'teacher-dashboard',
            'student' => 'my-courses',
            'parent' => 'index',
            'participant' => 'index',
        ];

        return $map[$role] ?? 'role-login';
    }

    /**
     * Find a Moodle user record using the same username/email rules as Moodle login.
     *
     * @param string $username Submitted username or email.
     * @return stdClass|null
     */
    public static function find_login_user(string $username): ?stdClass {
        global $CFG, $DB;

        $username = trim(\core_text::strtolower($username));
        if ($username === '') {
            return null;
        }

        $user = get_complete_user_data('username', $username, $CFG->mnet_localhost_id);
        if ($user) {
            return $user;
        }

        if (empty($CFG->authloginviaemail)) {
            return null;
        }

        $email = clean_param($username, PARAM_EMAIL);
        if ($email === '') {
            return null;
        }

        $select = 'mnethostid = :mnethostid AND LOWER(email) = LOWER(:email) AND deleted = 0';
        $params = [
            'mnethostid' => $CFG->mnet_localhost_id,
            'email' => $email,
        ];
        $users = $DB->get_records_select('user', $select, $params, 'id', 'id', 0, 2);
        if (count($users) !== 1) {
            return null;
        }

        $matcheduser = reset($users);
        return get_complete_user_data('id', $matcheduser->id) ?: null;
    }

    /**
     * Return whether a Moodle user matches a Custom LMS role.
     *
     * @param stdClass $user User record.
     * @param string $role Role key.
     * @return bool
     */
    public static function user_matches_role(stdClass $user, string $role): bool {
        if (empty($user->id) || !self::is_managed_role($role)) {
            return false;
        }

        if ($role === 'admin') {
            return is_siteadmin($user);
        }

        $roles = self::assigned_role_shortnames((int)$user->id);
        $allowedroles = [
            'teacher' => ['editingteacher', 'teacher'],
            'student' => ['student'],
            'parent' => ['parent'],
            'participant' => ['user', 'frontpage'],
        ];

        foreach ($allowedroles[$role] ?? [] as $shortname) {
            if (isset($roles[$shortname])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return whether the user can view a role-specific bundle page.
     *
     * @param stdClass $user User record.
     * @param string $role Page role key.
     * @param string $page Page slug.
     * @return bool
     */
    public static function user_can_access_page(stdClass $user, string $role, string $page): bool {
        if (!self::is_managed_role($role)) {
            return true;
        }

        if (self::user_matches_role($user, $role)) {
            return true;
        }

        // The generated bundle currently reuses the dashboard page as a parent/participant landing page.
        if ($page === 'index' && $role === 'student') {
            return self::user_matches_role($user, 'parent') || self::user_matches_role($user, 'participant');
        }

        return false;
    }

    /**
     * Return the primary Custom LMS colour role for a Moodle user.
     *
     * @param stdClass|null $user User record.
     * @return string
     */
    public static function primary_role_for_user(?stdClass $user): string {
        if (empty($user->id) || isguestuser($user)) {
            return 'public';
        }

        if (is_siteadmin($user)) {
            return 'admin';
        }

        $roles = self::assigned_role_shortnames((int)$user->id);
        $priority = [
            'teacher' => ['editingteacher', 'teacher'],
            'student' => ['student'],
            'parent' => ['parent'],
            'participant' => ['user', 'frontpage'],
        ];

        foreach ($priority as $customrole => $shortnames) {
            foreach ($shortnames as $shortname) {
                if (isset($roles[$shortname])) {
                    return $customrole;
                }
            }
        }

        return 'participant';
    }

    /**
     * Return all explicitly assigned role shortnames for a user.
     *
     * @param int $userid User ID.
     * @return array
     */
    private static function assigned_role_shortnames(int $userid): array {
        global $DB;

        if ($userid <= 0) {
            return [];
        }

        $shortnames = $DB->get_fieldset_sql(
            "SELECT DISTINCT r.shortname
               FROM {role_assignments} ra
               JOIN {role} r ON r.id = ra.roleid
              WHERE ra.userid = :userid",
            ['userid' => $userid]
        );

        return array_fill_keys($shortnames, true);
    }
}
