<?php
// Validate course template CSV files outside Moodle.
require_once(__DIR__ . '/cli_csv_helpers.php');

$options = getopt('', ['dir:', 'help']);
if (isset($options['help']) || empty($options['dir'])) {
    echo "Validate course template CSV files\n";
    echo "Usage: php cli_validate_course_template_csv.php --dir=/path/to/csv-pack\n";
    exit(0);
}
$dir = rtrim($options['dir'], DIRECTORY_SEPARATOR);

function read_csv_file_local($dir, $filename) {
    $path = csv_pack_resolve_file($dir, $filename);
    if (!file_exists($path)) {
        throw new Exception("Missing CSV file: $filename");
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

$errors = [];
$sections = read_csv_file_local($dir, 'course_template_sections.csv');
$activities = read_csv_file_local($dir, 'course_template_activities.csv');
$apps = read_csv_file_local($dir, 'course_template_application.csv');
$courses = read_csv_file_local($dir, 'courses.csv');
$courseShortnames = [];
foreach ($courses as $c) {
    $courseShortnames[$c['shortname']] = true;
}
$sectionNumbers = [];
foreach ($sections as $s) {
    $sectionNumbers[$s['section_number']] = true;
}
for ($i = 0; $i <= 12; $i++) {
    if (!isset($sectionNumbers[(string)$i])) {
        $errors[] = "Missing template section number $i";
    }
}
foreach ($activities as $a) {
    if (!isset($sectionNumbers[$a['section_number']])) {
        $errors[] = "Activity {$a['activity_key']} references missing section {$a['section_number']}";
    }
}
$requiredActivityColumns = [
    'completion_rule',
    'unlock_next',
    'grade_to_pass',
];
foreach ($requiredActivityColumns as $column) {
    if ($activities && !array_key_exists($column, $activities[0])) {
        $errors[] = "course_template_activities.csv is missing required column $column";
    }
}
$gateSections = [];
foreach ($activities as $a) {
    if (($a['unlock_next'] ?? '') === '1') {
        $section = (int)$a['section_number'];
        $gateSections[$section] = true;
        if (($a['completion_rule'] ?? '') !== 'passgrade') {
            $errors[] = "Gate activity {$a['activity_key']} should use completion_rule=passgrade";
        }
        if (!is_numeric($a['grade_to_pass'] ?? '')) {
            $errors[] = "Gate activity {$a['activity_key']} must define numeric grade_to_pass";
        }
    }
}
for ($i = 2; $i <= 11; $i++) {
    if (empty($gateSections[$i])) {
        $chapter = $i - 1;
        $errors[] = "Missing unlock gate for Chapter $chapter in section $i";
    }
}
foreach ($apps as $a) {
    if (!isset($courseShortnames[$a['course_shortname']])) {
        $errors[] = "Template application references missing course {$a['course_shortname']}";
    }
}
if ($errors) {
    echo "Template CSV validation failed:\n";
    foreach ($errors as $e) {
        echo "- $e\n";
    }
    exit(1);
}
echo "Template CSV validation passed.\n";
echo "Sections: " . count($sections) . "\n";
echo "Activities: " . count($activities) . "\n";
echo "Applications: " . count($apps) . "\n";
