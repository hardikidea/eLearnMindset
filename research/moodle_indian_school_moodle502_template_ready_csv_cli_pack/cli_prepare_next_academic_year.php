<?php
// Prepare next academic year objects from CSV files.
// Copy to /path/to/moodle/admin/cli/cli_prepare_next_academic_year.php
// Run: php admin/cli/cli_prepare_next_academic_year.php --dir=/path/to/csv-pack --dry-run=1

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once(__DIR__ . '/cli_csv_helpers.php');

list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'dir' => null,
    'dry-run' => true,
    'courses-file' => 'next_year_courses_2027_2028.csv',
    'cohorts-file' => 'next_year_cohorts_2027_2028.csv',
    'alumni-cohorts-file' => 'alumni_cohorts_2027.csv',
    'groups-file' => 'next_year_groups_2027_2028.csv',
    'enrolments-file' => 'next_year_enrolments_2027_2028.csv',
    'skip-courses' => false,
    'skip-cohorts' => false,
    'skip-groups' => false,
    'skip-enrolments' => false,
], ['h' => 'help']);

if ($options['help'] || empty($options['dir'])) {
    echo "Prepare next academic year Moodle objects\n\n";
    echo "Options:\n";
    echo "  --dir=/path/to/csv-pack\n";
    echo "  --dry-run=1|0\n";
    echo "  --courses-file=next_year_courses_2027_2028.csv\n";
    echo "  --cohorts-file=next_year_cohorts_2027_2028.csv\n";
    echo "  --groups-file=next_year_groups_2027_2028.csv\n";
    echo "  --enrolments-file=next_year_enrolments_2027_2028.csv\n";
    exit(0);
}

$csvdir = rtrim($options['dir'], DIRECTORY_SEPARATOR);
$dryrun = !empty($options['dry-run']) && $options['dry-run'] !== '0';
\core\session\manager::set_user(get_admin());
\core_php_time_limit::raise(0);
raise_memory_limit(MEMORY_EXTRA);

function pmsg($text) { cli_writeln($text); }
function csv_rows($filename) {
    global $csvdir;
    $path = csv_pack_resolve_file($csvdir, $filename);
    if (!file_exists($path)) { pmsg("Missing optional file: $filename"); return []; }
    $fh = fopen($path, 'r');
    if (!$fh) { cli_error("Cannot open CSV: $path"); }
    $headers = fgetcsv($fh);
    if (!$headers) { fclose($fh); return []; }
    $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
    $rows = [];
    while (($data = fgetcsv($fh)) !== false) {
        if (count($data) == 1 && trim($data[0]) === '') { continue; }
        $row = [];
        foreach ($headers as $i => $h) { $row[trim($h)] = isset($data[$i]) ? trim($data[$i]) : ''; }
        $rows[] = $row;
    }
    fclose($fh);
    return $rows;
}
function bool_int($v, $default = 1) {
    if ($v === '' || $v === null) { return $default; }
    return in_array(strtolower((string)$v), ['1','yes','true','y','active'], true) ? 1 : 0;
}
function category_id_by_code($code) {
    global $DB;
    if ($code === '') { return 0; }
    $cat = $DB->get_record('course_categories', ['idnumber' => $code], 'id', IGNORE_MISSING);
    return $cat ? (int)$cat->id : 0;
}
function course_by_code($code, $shortname = '') {
    global $DB;
    $course = $DB->get_record('course', ['idnumber' => $code], '*', IGNORE_MISSING);
    if (!$course && $shortname !== '') { $course = $DB->get_record('course', ['shortname' => $shortname], '*', IGNORE_MISSING); }
    if (!$course) { $course = $DB->get_record('course', ['shortname' => $code], '*', IGNORE_MISSING); }
    return $course;
}
function role_id($shortname) {
    global $DB;
    $role = $DB->get_record('role', ['shortname' => $shortname], 'id', IGNORE_MISSING);
    return $role ? (int)$role->id : 0;
}
function ensure_course_row($row, $dryrun) {
    $code = $row['course_code'] ?: ($row['idnumber'] ?? '');
    $shortname = $row['shortname'] ?? $code;
    if (course_by_code($code, $shortname)) { pmsg("Course exists: $code"); return; }
    $catid = category_id_by_code($row['category_code'] ?? '');
    if (!$catid && !$dryrun) { cli_error("Category not found for course $code: " . ($row['category_code'] ?? '')); }
    pmsg(($dryrun ? '[dry-run] ' : '') . "Create course: $code");
    if ($dryrun) { return; }
    $course = (object)[
        'fullname' => $row['fullname'],
        'shortname' => $shortname,
        'idnumber' => $code,
        'category' => $catid,
        'summary' => $row['summary'] ?? '',
        'summaryformat' => FORMAT_HTML,
        'format' => $row['format'] ?: 'topics',
        'numsections' => isset($row['numsections']) && $row['numsections'] !== '' ? (int)$row['numsections'] : 12,
        'visible' => bool_int($row['visible'] ?? '1', 1),
        'groupmode' => isset($row['groupmode']) && $row['groupmode'] !== '' ? (int)$row['groupmode'] : SEPARATEGROUPS,
        'groupmodeforce' => bool_int($row['groupmodeforce'] ?? '1', 1),
        'enablecompletion' => 1,
        'startdate' => !empty($row['startdate']) ? strtotime($row['startdate']) : strtotime('2027-06-01'),
        'enddate' => !empty($row['enddate']) ? strtotime($row['enddate']) : strtotime('2028-04-30'),
    ];
    create_course($course);
}
function ensure_cohort_row($row, $dryrun) {
    global $DB;
    $code = $row['cohort_code'] ?: ($row['idnumber'] ?? '');
    if ($DB->record_exists('cohort', ['idnumber' => $code])) { pmsg("Cohort exists: $code"); return; }
    $catid = category_id_by_code($row['context_category_code'] ?? '');
    $context = $catid ? context_coursecat::instance($catid) : context_system::instance();
    pmsg(($dryrun ? '[dry-run] ' : '') . "Create cohort: $code");
    if ($dryrun) { return; }
    $cohort = (object)[
        'contextid' => $context->id,
        'name' => $row['name'] ?: $code,
        'idnumber' => $code,
        'description' => $row['description'] ?? '',
        'descriptionformat' => FORMAT_HTML,
        'visible' => bool_int($row['visible'] ?? '1', 1),
    ];
    cohort_add_cohort($cohort);
}
function ensure_group_row($row, $dryrun) {
    global $DB;
    $course = course_by_code($row['course_code'] ?? '', $row['course_shortname'] ?? '');
    if (!$course && !$dryrun) { cli_error("Course not found for group: " . ($row['course_code'] ?? '')); }
    $idnumber = $row['group_idnumber'];
    if ($course && $DB->record_exists('groups', ['courseid' => $course->id, 'idnumber' => $idnumber])) { pmsg("Group exists: $idnumber"); return; }
    pmsg(($dryrun ? '[dry-run] ' : '') . "Create group: $idnumber");
    if ($dryrun) { return; }
    groups_create_group((object)[
        'courseid' => $course->id,
        'name' => $row['group_name'],
        'idnumber' => $idnumber,
        'description' => $row['description'] ?? '',
        'descriptionformat' => FORMAT_HTML,
    ]);
}
function ensure_cohort_enrolment_row($row, $dryrun) {
    global $DB;
    $plugin = enrol_get_plugin('cohort');
    if (!$plugin) { cli_error('Cohort sync enrolment plugin is not enabled.'); }
    if (($row['status'] ?? 'active') !== 'active') { return; }
    $course = course_by_code($row['course_code'] ?? '', $row['course_shortname'] ?? '');
    $cohort = $DB->get_record('cohort', ['idnumber' => $row['cohort_code']], '*', IGNORE_MISSING);
    $roleid = role_id($row['role_shortname'] ?: 'student');
    if ((!$course || !$cohort || !$roleid) && !$dryrun) { cli_error("Missing course/cohort/role for enrolment: {$row['course_code']} / {$row['cohort_code']}"); }
    $groupid = 0;
    if ($course && !empty($row['group_idnumber'])) {
        $group = $DB->get_record('groups', ['courseid' => $course->id, 'idnumber' => $row['group_idnumber']], '*', IGNORE_MISSING);
        if (!$group && !$dryrun) { cli_error("Group not found for enrolment: {$row['group_idnumber']}"); }
        $groupid = $group ? (int)$group->id : 0;
    }
    pmsg(($dryrun ? '[dry-run] ' : '') . "Cohort sync: {$row['cohort_code']} -> {$row['course_code']}");
    if ($dryrun) { return; }
    $instances = enrol_get_instances($course->id, false);
    foreach ($instances as $inst) {
        if ($inst->enrol === 'cohort' && (int)$inst->customint1 === (int)$cohort->id && (int)$inst->customint2 === (int)$groupid) { return; }
    }
    $plugin->add_instance($course, [
        'name' => $cohort->name,
        'status' => ENROL_INSTANCE_ENABLED,
        'customint1' => $cohort->id,
        'customint2' => $groupid,
        'roleid' => $roleid,
    ]);
}

pmsg('Prepare next academic year: ' . ($dryrun ? 'DRY RUN' : 'LIVE'));
if (empty($options['skip-courses'])) { foreach (csv_rows($options['courses-file']) as $row) { ensure_course_row($row, $dryrun); } }
if (empty($options['skip-cohorts'])) {
    foreach (csv_rows($options['cohorts-file']) as $row) { ensure_cohort_row($row, $dryrun); }
    foreach (csv_rows($options['alumni-cohorts-file']) as $row) { ensure_cohort_row($row, $dryrun); }
}
if (empty($options['skip-groups'])) { foreach (csv_rows($options['groups-file']) as $row) { ensure_group_row($row, $dryrun); } }
if (empty($options['skip-enrolments'])) { foreach (csv_rows($options['enrolments-file']) as $row) { ensure_cohort_enrolment_row($row, $dryrun); } }

if (!$dryrun && file_exists($GLOBALS['CFG']->dirroot . '/enrol/cohort/locallib.php')) {
    require_once($GLOBALS['CFG']->dirroot . '/enrol/cohort/locallib.php');
    if (function_exists('enrol_cohort_sync')) { enrol_cohort_sync(new null_progress_trace(), null); }
}
pmsg('Done. Now review and run cli_promote_students_academic_year.php.');
