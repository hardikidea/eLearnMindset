<?php
// Moodle 5.x / docs branch 502 preflight checker for the Indian school CSV pack.
// Copy to /path/to/moodle/admin/cli/ and run:
// php admin/cli/cli_moodle502_preflight.php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'strict' => false,
], [
    'h' => 'help',
]);

if (!empty($options['help'])) {
    echo "Moodle 5.x / 502 preflight checker\n\n" .
        "Options:\n" .
        "  --strict=1    Treat warnings as failures\n";
    exit(0);
}

$fails = 0;
$warnings = 0;
$strict = !empty($options['strict']) && $options['strict'] !== '0';

function check_line($status, $message) {
    global $fails, $warnings, $strict;
    cli_writeln(str_pad($status, 6) . ' ' . $message);
    if ($status === 'FAIL') {
        $fails++;
    }
    if ($status === 'WARN') {
        $warnings++;
        if ($strict) {
            $fails++;
        }
    }
}

cli_writeln('Moodle 5.x / docs branch 502 preflight checker');
cli_writeln('---------------------------------------------');
check_line('INFO', 'Moodle release: ' . ($GLOBALS['CFG']->release ?? 'unknown'));
check_line('INFO', 'Moodle branch: ' . ($GLOBALS['CFG']->branch ?? 'unknown'));

$branch = isset($CFG->branch) ? (int)$CFG->branch : 0;
if ($branch >= 500 && $branch <= 502) {
    check_line('PASS', 'Moodle branch is in the expected 5.0 to 5.2 range.');
} else if ($branch > 502) {
    check_line('WARN', 'Moodle branch is newer than 5.2. Core features should still work, but test on staging first.');
} else {
    check_line('FAIL', 'Moodle branch is older than 5.0. This pack is prepared for Moodle 5.x.');
}

$phpmin = ($branch >= 502) ? '8.3.0' : '8.2.0';
if (version_compare(PHP_VERSION, $phpmin, '>=')) {
    check_line('PASS', 'PHP version ' . PHP_VERSION . ' meets expected minimum ' . $phpmin . '.');
} else {
    check_line('FAIL', 'PHP version ' . PHP_VERSION . ' is below expected minimum ' . $phpmin . '.');
}

foreach (['sodium','mbstring','curl','openssl','ctype','zip','zlib','simplexml','spl','pcre','dom','xml','xmlreader','intl','json','hash','fileinfo'] as $ext) {
    check_line(extension_loaded($ext) ? 'PASS' : 'FAIL', 'PHP extension ' . $ext);
}

$maxinput = (int)ini_get('max_input_vars');
if ($maxinput >= 5000) {
    check_line('PASS', 'max_input_vars is ' . $maxinput . '.');
} else {
    check_line('WARN', 'max_input_vars is ' . $maxinput . '; Moodle 5.x release notes recommend at least 5000.');
}

$prefix = $CFG->prefix ?? '';
if (strlen($prefix) <= 10) {
    check_line('PASS', 'Database prefix length is <= 10.');
} else {
    check_line('FAIL', 'Database prefix length is greater than 10.');
}

if (method_exists($DB, 'get_dbfamily')) {
    check_line('INFO', 'Database family: ' . $DB->get_dbfamily());
}
if (method_exists($DB, 'get_server_info')) {
    $info = $DB->get_server_info();
    if (is_array($info) && !empty($info['description'])) {
        check_line('INFO', 'Database server: ' . $info['description']);
    }
}

$functionchecks = [
    'create_course',
    'cohort_add_cohort',
    'cohort_add_member',
    'groups_create_group',
    'profile_save_data',
    'create_role',
    'role_assign',
    'set_role_contextlevels',
];
foreach ($functionchecks as $fn) {
    check_line(function_exists($fn) ? 'PASS' : 'FAIL', 'Function available: ' . $fn);
}

$classchecks = [
    'core_course_category',
    'context_system',
    'context_coursecat',
    'context_course',
    'context_user',
];
foreach ($classchecks as $class) {
    check_line(class_exists($class) ? 'PASS' : 'FAIL', 'Class available: ' . $class);
}

$cohortplugin = enrol_get_plugin('cohort');
check_line($cohortplugin ? 'PASS' : 'FAIL', 'Cohort enrolment plugin enrol_cohort is available.');

$topicsdir = $CFG->dirroot . '/course/format/topics';
check_line(is_dir($topicsdir) ? 'PASS' : 'WARN', 'Topics course format directory exists.');

cli_writeln('---------------------------------------------');
cli_writeln('Warnings: ' . $warnings . ', Failures: ' . $fails);
if ($fails > 0) {
    cli_writeln('Preflight completed with failures. Fix these before import.');
    exit(2);
}
cli_writeln('Preflight completed successfully. Proceed with CSV validation and dry-run import.');
exit(0);
