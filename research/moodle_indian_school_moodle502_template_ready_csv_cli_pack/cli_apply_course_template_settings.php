<?php
// Apply universal template settings and section names to existing courses.
// Copy to /path/to/moodle/admin/cli/ and run as the web-server user.

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once(__DIR__ . '/cli_csv_helpers.php');

list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'dir' => null,
    'dry-run' => true,
    'limit' => 0,
], [
    'h' => 'help',
]);

if ($options['help'] || empty($options['dir'])) {
    echo "Apply universal course template settings to existing courses\n\n";
    echo "Options:\n";
    echo "  --dir=/absolute/path/to/csv-pack\n";
    echo "  --dry-run=1|0\n";
    echo "  --limit=10       Limit number of courses; 0 means all\n";
    exit(0);
}

$csvdir = rtrim($options['dir'], DIRECTORY_SEPARATOR);
$dryrun = !empty($options['dry-run']) && $options['dry-run'] !== '0';
$limit = (int)$options['limit'];

function msg($text) { cli_writeln($text); }

function read_csv_file($dir, $filename) {
    $path = csv_pack_resolve_file($dir, $filename);
    if (!file_exists($path)) {
        cli_error("Missing CSV file: $filename");
    }
    $rows = [];
    $handle = fopen($path, 'r');
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

function apply_course_settings_and_sections($course, $application, $sections, $dryrun) {
    global $DB;
    $maxsection = 0;
    foreach ($sections as $row) {
        $maxsection = max($maxsection, (int)$row['section_number']);
    }
    if ($dryrun) {
        msg("[dry-run] Apply settings and sections to {$course->shortname}");
        return;
    }
    $course->visible = as_bool_int($application['visible_after_creation'] ?? '0', 0);
    $course->enablecompletion = as_bool_int($application['enablecompletion'] ?? '1', 1);
    $course->showgrades = 1;
    $course->showreports = 1;
    $course->numsections = $maxsection;
    $course->format = 'topics';
    $DB->update_record('course', $course);
    course_create_sections_if_missing($course->id, range(0, $maxsection));
    foreach ($sections as $row) {
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => (int)$row['section_number']], '*', MUST_EXIST);
        $section->name = $row['section_name'];
        $section->summary = '<p>' . s($row['purpose']) . '</p>';
        $section->summaryformat = FORMAT_HTML;
        $section->visible = as_bool_int($row['default_visibility'] ?? '1', 1);
        $DB->update_record('course_sections', $section);
    }
    rebuild_course_cache($course->id, true);
    msg("Applied template settings to {$course->shortname}");
}

$applications = read_csv_file($csvdir, 'course_template_application.csv');
$sections = read_csv_file($csvdir, 'course_template_sections.csv');
$count = 0;
foreach ($applications as $row) {
    if ($limit > 0 && $count >= $limit) {
        break;
    }
    $course = $DB->get_record('course', ['shortname' => $row['course_shortname']], '*', IGNORE_MISSING);
    if (!$course && !empty($row['course_code'])) {
        $course = $DB->get_record('course', ['idnumber' => $row['course_code']], '*', IGNORE_MISSING);
    }
    if (!$course) {
        msg("Course not found, skipping: {$row['course_shortname']}");
        continue;
    }
    apply_course_settings_and_sections($course, $row, $sections, $dryrun);
    $count++;
}
msg("Template settings processed for $count courses.");
