<?php
// Optional helper: create gradebook categories from course_template_gradebook.csv.
// Review on staging before production.

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/grade/grade_category.php');
require_once($CFG->libdir . '/grade/grade_item.php');

list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'dir' => null,
    'dry-run' => true,
    'limit' => 0,
], [
    'h' => 'help',
]);

if ($options['help'] || empty($options['dir'])) {
    echo "Apply gradebook category template\n\n";
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
    $path = $dir . DIRECTORY_SEPARATOR . $filename;
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

function ensure_grade_category($courseid, $fullname, $weight, $dryrun) {
    global $DB;
    if ($DB->record_exists('grade_categories', ['courseid' => $courseid, 'fullname' => $fullname])) {
        return;
    }
    if ($dryrun) {
        msg("[dry-run] Create grade category $fullname in courseid $courseid with weight $weight");
        return;
    }
    $parent = grade_category::fetch_course_category($courseid);
    $cat = new grade_category();
    $cat->courseid = $courseid;
    $cat->parent = $parent->id;
    $cat->fullname = $fullname;
    $cat->aggregation = GRADE_AGGREGATE_WEIGHTED_MEAN;
    $cat->aggregateonlygraded = 1;
    $cat->insert('system');
    $item = $cat->get_grade_item();
    if ($item) {
        $item->aggregationcoef2 = ((float)$weight) / 100;
        $item->update('system');
    }
}

$applications = read_csv_file($csvdir, 'course_template_application.csv');
$gradecats = read_csv_file($csvdir, 'course_template_gradebook.csv');
$count = 0;
foreach ($applications as $app) {
    if ($limit > 0 && $count >= $limit) {
        break;
    }
    $course = $DB->get_record('course', ['shortname' => $app['course_shortname']], '*', IGNORE_MISSING);
    if (!$course) {
        msg("Course not found, skipping gradebook: {$app['course_shortname']}");
        continue;
    }
    foreach ($gradecats as $row) {
        ensure_grade_category($course->id, $row['category_name'], $row['weight_percent'], $dryrun);
    }
    msg("Gradebook template checked for {$course->shortname}");
    $count++;
}
msg("Gradebook template processed for $count courses.");
