<?php
// Create or update the universal hidden master course template from CSV.
// Copy to /path/to/moodle/admin/cli/ and run as the web-server user.

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');

list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'dir' => null,
    'dry-run' => true,
    'activity-mode' => 'page',
], [
    'h' => 'help',
]);

if ($options['help'] || empty($options['dir'])) {
    echo "Create universal master course template\n\n";
    echo "Options:\n";
    echo "  --dir=/absolute/path/to/csv-pack\n";
    echo "  --dry-run=1|0\n";
    echo "  --activity-mode=page|native\n";
    exit(0);
}

$csvdir = rtrim($options['dir'], DIRECTORY_SEPARATOR);
$dryrun = !empty($options['dry-run']) && $options['dry-run'] !== '0';
$activitymode = strtolower($options['activity-mode'] ?: 'page');

function msg($text) { cli_writeln($text); }

function read_csv_file($dir, $filename) {
    $path = $dir . DIRECTORY_SEPARATOR . $filename;
    if (!file_exists($path)) {
        cli_error("Missing CSV file: $filename");
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
    if ($value === '' || $value === null) {
        return $default;
    }
    $v = strtolower((string)$value);
    return in_array($v, ['1', 'true', 'yes', 'y'], true) ? 1 : 0;
}

function ensure_template_category($row, $dryrun) {
    global $DB;
    $idnumber = $row['category_code'] ?: 'COURSE_TEMPLATES';
    if ($cat = $DB->get_record('course_categories', ['idnumber' => $idnumber])) {
        msg("Template category exists: $idnumber");
        return $cat->id;
    }
    if ($dryrun) {
        msg("[dry-run] Create hidden template category: $idnumber");
        return 0;
    }
    $cat = core_course_category::create([
        'name' => $row['category_name'] ?: 'Course Templates',
        'idnumber' => $idnumber,
        'parent' => 0,
        'visible' => 0,
        'description' => 'Hidden category for reusable course templates.',
        'descriptionformat' => FORMAT_HTML,
    ]);
    msg("Created hidden template category: $idnumber");
    return $cat->id;
}

function ensure_template_course($row, $categoryid, $dryrun) {
    global $DB;
    if ($course = $DB->get_record('course', ['shortname' => $row['shortname']])) {
        msg("Template course exists: {$row['shortname']}");
        return $course;
    }
    if ($dryrun) {
        msg("[dry-run] Create template course: {$row['shortname']}");
        $course = new stdClass();
        $course->id = 0;
        $course->shortname = $row['shortname'];
        return $course;
    }
    $course = new stdClass();
    $course->fullname = $row['fullname'];
    $course->shortname = $row['shortname'];
    $course->idnumber = $row['idnumber'];
    $course->category = $categoryid;
    $course->summary = $row['summary'];
    $course->summaryformat = FORMAT_HTML;
    $course->format = $row['format'] ?: 'topics';
    $course->numsections = (int)($row['numsections'] ?: 12);
    $course->visible = 0;
    $course->enablecompletion = as_bool_int($row['enablecompletion'] ?? '1', 1);
    $course->showgrades = as_bool_int($row['showgrades'] ?? '1', 1);
    $course->showreports = as_bool_int($row['showreports'] ?? '1', 1);
    $course->groupmode = (int)($row['groupmode'] ?: 1);
    $course->groupmodeforce = (int)($row['groupmodeforce'] ?: 1);
    $created = create_course($course);
    msg("Created template course: {$row['shortname']}");
    return $created;
}

function update_course_settings($course, $row, $dryrun) {
    global $DB;
    if ($dryrun || empty($course->id)) {
        msg("[dry-run] Update template course settings: {$row['shortname']}");
        return;
    }
    $course->visible = 0;
    $course->enablecompletion = as_bool_int($row['enablecompletion'] ?? '1', 1);
    $course->showgrades = as_bool_int($row['showgrades'] ?? '1', 1);
    $course->showreports = as_bool_int($row['showreports'] ?? '1', 1);
    $course->numsections = (int)($row['numsections'] ?: 12);
    $DB->update_record('course', $course);
    rebuild_course_cache($course->id, true);
}

function ensure_sections($course, $sections, $dryrun) {
    global $DB;
    $maxsection = 0;
    foreach ($sections as $row) {
        $maxsection = max($maxsection, (int)$row['section_number']);
    }
    if ($dryrun || empty($course->id)) {
        msg("[dry-run] Ensure course sections 0..$maxsection and update names/summaries");
        return;
    }
    course_create_sections_if_missing($course->id, range(0, $maxsection));
    foreach ($sections as $row) {
        $sectionnum = (int)$row['section_number'];
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $sectionnum], '*', MUST_EXIST);
        $section->name = $row['section_name'];
        $section->summary = '<p>' . s($row['purpose']) . '</p><p><strong>Teacher notes:</strong> ' . s($row['teacher_notes']) . '</p>';
        $section->summaryformat = FORMAT_HTML;
        $section->visible = as_bool_int($row['default_visibility'] ?? '1', 1);
        $DB->update_record('course_sections', $section);
    }
    rebuild_course_cache($course->id, true);
    msg('Updated template course sections.');
}

function module_table_exists($modname) {
    global $DB;
    return $DB->get_manager()->table_exists($modname);
}

function create_placeholder_activity($course, $row, $activitymode, $dryrun) {
    global $DB, $CFG;
    $recommended = strtolower($row['recommended_activity_type']);
    $modname = ($activitymode === 'native' && module_table_exists($recommended)) ? $recommended : 'page';
    if (!$DB->record_exists('modules', ['name' => $modname])) {
        msg("Skipping activity {$row['default_name']}; module not installed: $modname");
        return;
    }
    $title = $row['default_name'];
    if ($modname === 'page' && $recommended !== 'page') {
        $title = '[' . strtoupper($recommended) . '] ' . $title;
    }
    if ($DB->get_manager()->table_exists($modname) && $DB->record_exists($modname, ['course' => $course->id, 'name' => $title])) {
        msg("Activity exists: $title");
        return;
    }
    if ($dryrun || empty($course->id)) {
        msg("[dry-run] Create $modname activity in section {$row['section_number']}: $title");
        return;
    }
    $module = $DB->get_record('modules', ['name' => $modname], '*', MUST_EXIST);
    $info = new stdClass();
    $info->modulename = $modname;
    $info->module = $module->id;
    $info->course = $course->id;
    $info->section = (int)$row['section_number'];
    $info->visible = as_bool_int($row['visible'] ?? '1', 1);
    $info->name = $title;
    $info->intro = '<p>' . s($row['purpose']) . '</p><p><em>Replace this placeholder with grade-level and subject-specific content.</em></p>';
    $info->introformat = FORMAT_HTML;
    $info->completion = (int)($row['completion_mode'] ?: 1);
    $info->completionview = 1;
    if ($modname === 'page') {
        $info->content = '<h3>' . s($row['default_name']) . '</h3><p>' . s($row['purpose']) . '</p><p>Recommended activity type: <strong>' . s($recommended) . '</strong>.</p><p>PII-safe rule: do not place Aadhaar, personal address, medical details, or parent contact data inside course content.</p>';
        $info->contentformat = FORMAT_HTML;
        $info->display = 5;
    } else if ($modname === 'url') {
        $info->externalurl = 'https://example.invalid/replace-this-link';
        $info->display = 0;
    } else if ($modname === 'forum') {
        $info->type = 'general';
    } else if ($modname === 'assign') {
        $info->grade = is_numeric($row['default_points']) ? (float)$row['default_points'] : 100;
        $info->allowsubmissionsfromdate = 0;
        $info->duedate = 0;
        $info->cutoffdate = 0;
    } else if ($modname === 'quiz') {
        $info->grade = is_numeric($row['default_points']) ? (float)$row['default_points'] : 10;
        $info->sumgrades = $info->grade;
        $info->timeopen = 0;
        $info->timeclose = 0;
        $info->preferredbehaviour = 'deferredfeedback';
    } else if ($modname === 'feedback') {
        $info->timeopen = 0;
        $info->timeclose = 0;
        $info->anonymous = 1;
        $info->email_notification = 0;
        $info->multiple_submit = 0;
    }
    try {
        add_moduleinfo($info, $course);
        msg("Created $modname activity: $title");
    } catch (Throwable $e) {
        msg("Could not create native activity $title: " . $e->getMessage());
    }
}

$template = read_csv_file($csvdir, 'master_course_template.csv')[0];
$sections = read_csv_file($csvdir, 'course_template_sections.csv');
$activities = read_csv_file($csvdir, 'course_template_activities.csv');

msg($dryrun ? 'Mode: DRY RUN' : 'Mode: EXECUTE');
$categoryid = ensure_template_category($template, $dryrun);
$course = ensure_template_course($template, $categoryid, $dryrun);
update_course_settings($course, $template, $dryrun);
ensure_sections($course, $sections, $dryrun);
foreach ($activities as $row) {
    create_placeholder_activity($course, $row, $activitymode, $dryrun);
}
msg('Master course template process completed.');
