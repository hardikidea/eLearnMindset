<?php
// Moodle Indian school baseline CSV importer.
// Copy this file to /path/to/moodle/admin/cli/ and run as the web-server user.
// Example:
// php admin/cli/cli_import_indian_school_baseline.php --dir=/path/to/csv-pack --dry-run=1

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->libdir . '/enrollib.php');

list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'dir' => null,
    'dry-run' => true,
    'skip-users' => false,
    'skip-courses' => false,
    'skip-enrolments' => false,
], [
    'h' => 'help',
]);

if ($options['help'] || empty($options['dir'])) {
    $help = "Indian school baseline CSV importer\n\n" .
        "Options:\n" .
        "  --dir=/absolute/path/to/csv-pack      Required CSV directory\n" .
        "  --dry-run=1                          Preview only, no writes\n" .
        "  --dry-run=0                          Execute writes\n" .
        "  --skip-users=1                       Skip user/profile/cohort member import\n" .
        "  --skip-courses=1                     Skip category/course/group import\n" .
        "  --skip-enrolments=1                  Skip cohort enrolment mappings\n\n";
    echo $help;
    exit(0);
}

$csvdir = rtrim($options['dir'], DIRECTORY_SEPARATOR);
$dryrun = !empty($options['dry-run']) && $options['dry-run'] !== '0';

function msg($text) {
    cli_writeln($text);
}

function read_csv_file($dir, $filename) {
    $path = $dir . DIRECTORY_SEPARATOR . $filename;
    if (!file_exists($path)) {
        msg("Missing optional file: $filename");
        return [];
    }
    $rows = [];
    $handle = fopen($path, 'r');
    if ($handle === false) {
        cli_error("Cannot open CSV: $path");
    }
    $headers = fgetcsv($handle);
    if (!$headers) {
        fclose($handle);
        return [];
    }
    $headers = array_map('trim', $headers);
    while (($data = fgetcsv($handle)) !== false) {
        $row = [];
        foreach ($headers as $i => $h) {
            $row[$h] = isset($data[$i]) ? trim($data[$i]) : '';
        }
        $rows[] = $row;
    }
    fclose($handle);
    return $rows;
}

function as_bool_int($value, $default = 0) {
    if ($value === '' || $value === null) return $default;
    $v = strtolower((string)$value);
    return in_array($v, ['1', 'true', 'yes', 'y'], true) ? 1 : 0;
}

function ensure_profile_category($name, $dryrun) {
    global $DB;
    if ($cat = $DB->get_record('user_info_category', ['name' => $name])) {
        return $cat->id;
    }
    if ($dryrun) {
        msg("[dry-run] Create user profile field category: $name");
        return 0;
    }
    $sortorder = (int)$DB->get_field_sql('SELECT COALESCE(MAX(sortorder),0)+1 FROM {user_info_category}');
    $rec = (object)['name' => $name, 'sortorder' => $sortorder];
    $id = $DB->insert_record('user_info_category', $rec);
    msg("Created user profile field category: $name");
    return $id;
}

function ensure_profile_field($row, $dryrun) {
    global $DB;
    $shortname = $row['shortname'];
    if ($DB->record_exists('user_info_field', ['shortname' => $shortname])) {
        msg("Profile field exists: $shortname");
        return;
    }
    $catid = ensure_profile_category($row['category'] ?: 'Other fields', $dryrun);
    if ($dryrun) {
        msg("[dry-run] Create profile field: $shortname ({$row['datatype']})");
        return;
    }
    $sortorder = (int)$DB->get_field_sql('SELECT COALESCE(MAX(sortorder),0)+1 FROM {user_info_field} WHERE categoryid = ?', [$catid]);
    $datatype = $row['datatype'] ?: 'text';
    $rec = new stdClass();
    $rec->shortname = $shortname;
    $rec->name = $row['name'] ?: $shortname;
    $rec->datatype = $datatype;
    $rec->description = $row['notes'] ?? '';
    $rec->descriptionformat = FORMAT_HTML;
    $rec->categoryid = $catid;
    $rec->sortorder = $sortorder;
    $rec->required = as_bool_int($row['required'] ?? '0');
    $rec->locked = as_bool_int($row['locked'] ?? '1');
    $rec->visible = (int)($row['visible'] ?? 0);
    $rec->forceunique = as_bool_int($row['forceunique'] ?? '0');
    $rec->signup = as_bool_int($row['signup'] ?? '0');
    $rec->defaultdata = $row['defaultdata'] ?? '';
    $rec->defaultdataformat = FORMAT_HTML;
    $rec->param1 = '';
    $rec->param2 = '';
    $rec->param3 = '';
    $rec->param4 = '';
    $rec->param5 = '';
    if ($datatype === 'text') {
        $rec->param1 = 30;
        $rec->param2 = 2048;
        $rec->param3 = 0;
    } else if ($datatype === 'datetime') {
        $rec->param1 = 1900;
        $rec->param2 = 2100;
        $rec->param3 = 0;
        $rec->defaultdata = '0';
    } else if ($datatype === 'menu' && !empty($row['options'])) {
        $rec->param1 = str_replace('|', "\n", $row['options']);
    }
    $DB->insert_record('user_info_field', $rec);
    msg("Created profile field: $shortname");
}

function ensure_role_from_row($row, $dryrun) {
    global $DB;
    $shortname = $row['role_shortname'];
    if ($role = $DB->get_record('role', ['shortname' => $shortname])) {
        msg("Role exists: $shortname");
        return $role->id;
    }
    if ($dryrun) {
        msg("[dry-run] Create role: $shortname based on {$row['based_on_role']}");
        return 0;
    }
    $roleid = create_role($row['role_name'], $shortname, $row['description'] ?? '', '');
    if (!empty($row['based_on_role']) && ($base = $DB->get_record('role', ['shortname' => $row['based_on_role']]))) {
        $caps = $DB->get_records('role_capabilities', ['roleid' => $base->id]);
        foreach ($caps as $cap) {
            assign_capability($cap->capability, $cap->permission, $roleid, $cap->contextid, true);
        }
    }
    if (!empty($row['capabilities_allow'])) {
        $sysctx = context_system::instance();
        foreach (explode('|', $row['capabilities_allow']) as $capability) {
            $capability = trim($capability);
            if ($capability !== '') {
                assign_capability($capability, CAP_ALLOW, $roleid, $sysctx->id, true);
            }
        }
    }
    $levels = [];
    foreach (explode('|', $row['context_levels'] ?? '') as $level) {
        $level = trim(strtolower($level));
        if ($level === 'system') $levels[] = CONTEXT_SYSTEM;
        if ($level === 'category') $levels[] = CONTEXT_COURSECAT;
        if ($level === 'course') $levels[] = CONTEXT_COURSE;
        if ($level === 'user') $levels[] = CONTEXT_USER;
    }
    if ($levels) {
        set_role_contextlevels($roleid, $levels);
    }
    msg("Created role: $shortname");
    return $roleid;
}

function ensure_category($row, $dryrun) {
    global $DB;
    $idnumber = $row['idnumber'] ?: $row['category_code'];
    if ($cat = $DB->get_record('course_categories', ['idnumber' => $idnumber])) {
        msg("Category exists: $idnumber");
        return $cat->id;
    }
    if ($dryrun) {
        $parent = $row['parent_category_code'] ?? '';
        msg("[dry-run] Create category: $idnumber under parent $parent");
        return 0;
    }
    $parentid = 0;
    if (!empty($row['parent_category_code'])) {
        $parent = $DB->get_record('course_categories', ['idnumber' => $row['parent_category_code']], '*', MUST_EXIST);
        $parentid = $parent->id;
    }
    $data = [
        'name' => $row['name'],
        'parent' => $parentid,
        'idnumber' => $idnumber,
        'description' => $row['description'] ?? '',
        'descriptionformat' => FORMAT_HTML,
        'visible' => as_bool_int($row['visible'] ?? '1', 1),
    ];
    $cat = core_course_category::create($data);
    msg("Created category: $idnumber");
    return $cat->id;
}

function ensure_course($row, $dryrun) {
    global $DB;
    if ($course = $DB->get_record('course', ['shortname' => $row['shortname']])) {
        msg("Course exists: {$row['shortname']}");
        return $course->id;
    }
    if ($dryrun) {
        msg("[dry-run] Create course: {$row['shortname']} in category {$row['category_code']}");
        return 0;
    }
    $cat = $DB->get_record('course_categories', ['idnumber' => $row['category_code']], '*', MUST_EXIST);
    $course = new stdClass();
    $course->fullname = $row['fullname'];
    $course->shortname = $row['shortname'];
    $course->idnumber = $row['idnumber'] ?: $row['course_code'];
    $course->category = $cat->id;
    $course->summary = $row['summary'] ?? '';
    $course->summaryformat = FORMAT_HTML;
    $course->format = $row['format'] ?: 'topics';
    $course->numsections = (int)($row['numsections'] ?: 12);
    $course->visible = as_bool_int($row['visible'] ?? '1', 1);
    $course->groupmode = (int)($row['groupmode'] ?: 1);
    $course->groupmodeforce = (int)($row['groupmodeforce'] ?: 1);
    $course->enablecompletion = as_bool_int($row['enablecompletion'] ?? '1', 1);
    $course->showgrades = as_bool_int($row['showgrades'] ?? '1', 1);
    $course->showreports = as_bool_int($row['showreports'] ?? '1', 1);
    if (!empty($row['startdate'])) {
        $course->startdate = strtotime($row['startdate']);
    }
    if (!empty($row['enddate'])) {
        $course->enddate = strtotime($row['enddate']);
    }
    $course = create_course($course);
    msg("Created course: {$row['shortname']}");
    return $course->id;
}

function ensure_cohort($row, $dryrun) {
    global $DB;
    $idnumber = $row['idnumber'] ?: $row['cohort_code'];
    if ($cohort = $DB->get_record('cohort', ['idnumber' => $idnumber])) {
        msg("Cohort exists: $idnumber");
        return $cohort->id;
    }
    if ($dryrun) {
        msg("[dry-run] Create cohort: $idnumber in context {$row['context_category_code']}");
        return 0;
    }
    $context = context_system::instance();
    if (!empty($row['context_category_code'])) {
        if ($cat = $DB->get_record('course_categories', ['idnumber' => $row['context_category_code']])) {
            $context = context_coursecat::instance($cat->id);
        }
    }
    $cohort = new stdClass();
    $cohort->contextid = $context->id;
    $cohort->name = $row['name'];
    $cohort->idnumber = $idnumber;
    $cohort->description = $row['description'] ?? '';
    $cohort->descriptionformat = FORMAT_HTML;
    $cohort->visible = as_bool_int($row['visible'] ?? '1', 1);
    $id = cohort_add_cohort($cohort);
    msg("Created cohort: $idnumber");
    return $id;
}

function ensure_group($row, $dryrun) {
    global $DB;
    if ($dryrun) {
        msg("[dry-run] Create group: {$row['group_idnumber']} in course {$row['course_shortname']}");
        return 0;
    }
    $course = $DB->get_record('course', ['shortname' => $row['course_shortname']], '*', IGNORE_MISSING);
    if (!$course && !empty($row['course_code'])) {
        $course = $DB->get_record('course', ['idnumber' => $row['course_code']], '*', MUST_EXIST);
    }
    $idnumber = $row['group_idnumber'];
    if ($group = $DB->get_record('groups', ['courseid' => $course->id, 'idnumber' => $idnumber])) {
        msg("Group exists: $idnumber");
        return $group->id;
    }
    $group = new stdClass();
    $group->courseid = $course->id;
    $group->name = $row['group_name'];
    $group->idnumber = $idnumber;
    $group->description = $row['description'] ?? '';
    $group->descriptionformat = FORMAT_HTML;
    $id = groups_create_group($group);
    msg("Created group: $idnumber");
    return $id;
}

function ensure_user_from_row($row, $dryrun) {
    global $DB, $CFG;
    $username = core_text::strtolower($row['username']);
    $existing = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0]);
    $user = new stdClass();
    $user->username = $username;
    $user->firstname = $row['firstname'];
    $user->lastname = $row['lastname'];
    $user->email = $row['email'];
    $user->auth = $row['auth'] ?: 'manual';
    $user->city = $row['city'] ?? '';
    $user->country = $row['country'] ?? 'IN';
    $user->timezone = $row['timezone'] ?? 'Asia/Kolkata';
    $user->lang = $row['lang'] ?? 'en';
    $user->institution = $row['institution'] ?? '';
    $user->department = $row['department'] ?? '';
    $user->idnumber = $row['idnumber'] ?? '';
    $user->phone1 = $row['phone1'] ?? '';
    $user->phone2 = $row['phone2'] ?? '';
    $user->address = $row['address'] ?? '';
    foreach ($row as $k => $v) {
        if (strpos($k, 'profile_field_') === 0) {
            $short = substr($k, strlen('profile_field_'));
            $field = $DB->get_record('user_info_field', ['shortname' => $short]);
            if ($field && $field->datatype === 'datetime' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
                $v = strtotime($v . ' 00:00:00');
            }
            $user->$k = $v;
        }
    }
    if ($dryrun) {
        msg($existing ? "[dry-run] Update user: $username" : "[dry-run] Create user: $username");
        return 0;
    }
    if ($existing) {
        $user->id = $existing->id;
        user_update_user($user, false, false);
        profile_save_data($user);
        msg("Updated user: $username");
        return $existing->id;
    }
    $user->confirmed = 1;
    $user->mnethostid = $CFG->mnet_localhost_id;
    $user->password = hash_internal_user_password($row['password'] ?: 'ChangeMe@123');
    $userid = user_create_user($user, false, false);
    $user->id = $userid;
    profile_save_data($user);
    msg("Created user: $username");
    return $userid;
}

function ensure_cohort_member($row, $dryrun) {
    global $DB, $CFG;
    if ($dryrun) {
        msg("[dry-run] Add cohort member: {$row['username']} -> {$row['cohort_code']}");
        return;
    }
    $user = $DB->get_record('user', ['username' => core_text::strtolower($row['username']), 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0], '*', MUST_EXIST);
    $cohort = $DB->get_record('cohort', ['idnumber' => $row['cohort_code']], '*', MUST_EXIST);
    if ($DB->record_exists('cohort_members', ['cohortid' => $cohort->id, 'userid' => $user->id])) {
        msg("Cohort member exists: {$row['username']} -> {$row['cohort_code']}");
        return;
    }
    cohort_add_member($cohort->id, $user->id);
    msg("Added cohort member: {$row['username']} -> {$row['cohort_code']}");
}

function ensure_cohort_enrolment($row, $dryrun) {
    global $DB;
    if ($dryrun) {
        msg("[dry-run] Create cohort enrolment: {$row['cohort_code']} -> {$row['course_shortname']}");
        return;
    }
    $plugin = enrol_get_plugin('cohort');
    if (!$plugin) {
        cli_error('Cohort enrolment plugin is not available/enabled. Enable enrol_cohort first.');
    }
    $course = $DB->get_record('course', ['idnumber' => $row['course_code']], '*', IGNORE_MISSING);
    if (!$course) {
        $course = $DB->get_record('course', ['shortname' => $row['course_shortname']], '*', MUST_EXIST);
    }
    $cohort = $DB->get_record('cohort', ['idnumber' => $row['cohort_code']], '*', MUST_EXIST);
    $role = $DB->get_record('role', ['shortname' => $row['role_shortname'] ?: 'student'], '*', MUST_EXIST);
    $groupid = 0;
    if (!empty($row['group_idnumber'])) {
        if ($group = $DB->get_record('groups', ['courseid' => $course->id, 'idnumber' => $row['group_idnumber']])) {
            $groupid = $group->id;
        }
    }
    $exists = $DB->record_exists('enrol', ['courseid' => $course->id, 'enrol' => 'cohort', 'customint1' => $cohort->id]);
    if ($exists) {
        msg("Cohort enrolment exists: {$row['cohort_code']} -> {$course->shortname}");
        return;
    }
    $fields = [
        'name' => $row['cohort_code'],
        'status' => ENROL_INSTANCE_ENABLED,
        'customint1' => $cohort->id,
        'customint2' => $groupid,
        'roleid' => $role->id,
    ];
    $plugin->add_instance($course, $fields);
    msg("Created cohort enrolment: {$row['cohort_code']} -> {$course->shortname}");
}

function resolve_context_from_assignment($row) {
    global $DB, $CFG;
    $type = strtolower($row['context_type']);
    $ident = $row['context_identifier'];
    if ($type === 'system') {
        return context_system::instance();
    }
    if ($type === 'category') {
        $cat = $DB->get_record('course_categories', ['idnumber' => $ident], '*', MUST_EXIST);
        return context_coursecat::instance($cat->id);
    }
    if ($type === 'course') {
        $course = $DB->get_record('course', ['idnumber' => $ident], '*', IGNORE_MISSING);
        if (!$course) $course = $DB->get_record('course', ['shortname' => $ident], '*', MUST_EXIST);
        return context_course::instance($course->id);
    }
    if ($type === 'user') {
        $user = $DB->get_record('user', ['username' => core_text::strtolower($ident), 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0], '*', MUST_EXIST);
        return context_user::instance($user->id);
    }
    cli_error("Invalid context_type in role assignment: $type");
}

function resolve_course_from_assignment($row) {
    global $DB;
    $ident = $row['context_identifier'];
    $course = $DB->get_record('course', ['idnumber' => $ident], '*', IGNORE_MISSING);
    if (!$course) {
        $course = $DB->get_record('course', ['shortname' => $ident], '*', MUST_EXIST);
    }
    return $course;
}

function ensure_manual_course_enrolment($user, $course, $roleid, $dryrun) {
    global $DB;
    if ($dryrun) {
        msg("[dry-run] Manually enrol {$user->username} in {$course->shortname}");
        return;
    }
    $plugin = enrol_get_plugin('manual');
    if (!$plugin) {
        cli_error('Manual enrolment plugin is not available/enabled. Enable enrol_manual first.');
    }
    $instance = null;
    foreach (enrol_get_instances($course->id, false) as $candidate) {
        if ($candidate->enrol === 'manual') {
            $instance = $candidate;
            break;
        }
    }
    if (!$instance) {
        $plugin->add_default_instance($course);
        foreach (enrol_get_instances($course->id, false) as $candidate) {
            if ($candidate->enrol === 'manual') {
                $instance = $candidate;
                break;
            }
        }
    }
    if (!$instance) {
        cli_error("Unable to create manual enrolment instance for {$course->shortname}");
    }
    if ($DB->record_exists('user_enrolments', ['enrolid' => $instance->id, 'userid' => $user->id])) {
        msg("Manual enrolment exists: {$user->username} -> {$course->shortname}");
        return;
    }
    $plugin->enrol_user($instance, $user->id, $roleid, 0, 0, ENROL_USER_ACTIVE);
    msg("Manually enrolled {$user->username} in {$course->shortname}");
}

function ensure_role_assignment($row, $dryrun) {
    global $DB, $CFG;
    if ($dryrun) {
        msg("[dry-run] Assign role {$row['role_shortname']} to {$row['username']} in {$row['context_type']}:{$row['context_identifier']}");
        return;
    }
    $user = $DB->get_record('user', ['username' => core_text::strtolower($row['username']), 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0], '*', MUST_EXIST);
    $role = $DB->get_record('role', ['shortname' => $row['role_shortname']], '*', MUST_EXIST);
    $course = null;
    if (strtolower($row['context_type']) === 'course') {
        $course = resolve_course_from_assignment($row);
        $context = context_course::instance($course->id);
    } else {
        $context = resolve_context_from_assignment($row);
    }
    if ($DB->record_exists('role_assignments', ['userid' => $user->id, 'roleid' => $role->id, 'contextid' => $context->id])) {
        msg("Role assignment exists: {$row['username']} {$row['role_shortname']}");
        if ($course) {
            ensure_manual_course_enrolment($user, $course, $role->id, $dryrun);
        }
        return;
    }
    role_assign($role->id, $user->id, $context->id);
    msg("Assigned role {$row['role_shortname']} to {$row['username']}");
    if ($course) {
        ensure_manual_course_enrolment($user, $course, $role->id, $dryrun);
    }
}

function ensure_parent_link($row, $dryrun) {
    global $DB, $CFG;
    if ($dryrun) {
        msg("[dry-run] Link parent {$row['parent_username']} to student {$row['student_username']}");
        return;
    }
    $parent = $DB->get_record('user', ['username' => core_text::strtolower($row['parent_username']), 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0], '*', MUST_EXIST);
    $student = $DB->get_record('user', ['username' => core_text::strtolower($row['student_username']), 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0], '*', MUST_EXIST);
    $role = $DB->get_record('role', ['shortname' => $row['role_shortname'] ?: 'parent'], '*', MUST_EXIST);
    $context = context_user::instance($student->id);
    if ($DB->record_exists('role_assignments', ['userid' => $parent->id, 'roleid' => $role->id, 'contextid' => $context->id])) {
        msg("Parent link exists: {$row['parent_username']} -> {$row['student_username']}");
        return;
    }
    role_assign($role->id, $parent->id, $context->id);
    msg("Linked parent {$row['parent_username']} to student {$row['student_username']}");
}

msg('Indian school baseline importer starting.');
msg($dryrun ? 'Mode: DRY RUN' : 'Mode: EXECUTE');

foreach (read_csv_file($csvdir, 'user_profile_fields.csv') as $row) {
    ensure_profile_field($row, $dryrun);
}
foreach (read_csv_file($csvdir, 'custom_roles.csv') as $row) {
    ensure_role_from_row($row, $dryrun);
}

if (empty($options['skip-courses'])) {
    foreach (read_csv_file($csvdir, 'categories.csv') as $row) {
        ensure_category($row, $dryrun);
    }
    foreach (read_csv_file($csvdir, 'courses.csv') as $row) {
        ensure_course($row, $dryrun);
    }
    foreach (read_csv_file($csvdir, 'cohorts.csv') as $row) {
        ensure_cohort($row, $dryrun);
    }
    foreach (read_csv_file($csvdir, 'groups.csv') as $row) {
        ensure_group($row, $dryrun);
    }
}

if (empty($options['skip-users'])) {
    foreach (['users_staff.csv', 'users_students.csv', 'users_parents.csv'] as $file) {
        foreach (read_csv_file($csvdir, $file) as $row) {
            ensure_user_from_row($row, $dryrun);
        }
    }
    foreach (read_csv_file($csvdir, 'cohort_members.csv') as $row) {
        ensure_cohort_member($row, $dryrun);
    }
}

foreach (read_csv_file($csvdir, 'role_assignments.csv') as $row) {
    ensure_role_assignment($row, $dryrun);
}
foreach (read_csv_file($csvdir, 'parent_links.csv') as $row) {
    ensure_parent_link($row, $dryrun);
}

if (empty($options['skip-enrolments'])) {
    foreach (read_csv_file($csvdir, 'enrolments.csv') as $row) {
        ensure_cohort_enrolment($row, $dryrun);
    }
}

msg('Import completed. Review Moodle logs and spot-check users/courses/roles.');
