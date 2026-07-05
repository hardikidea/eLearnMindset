<?php
// Create or update the universal hidden master course template from CSV.
// Copy to /path/to/moodle/admin/cli/ and run as the web-server user.

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once(__DIR__ . '/cli_csv_helpers.php');

list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'dir' => null,
    'dry-run' => true,
    'activity-mode' => 'page',
    'reset-template-activities' => false,
], [
    'h' => 'help',
]);

if ($options['help'] || empty($options['dir'])) {
    echo "Create universal master course template\n\n";
    echo "Options:\n";
    echo "  --dir=/absolute/path/to/csv-pack\n";
    echo "  --dry-run=1|0\n";
    echo "  --activity-mode=page|native\n";
    echo "  --reset-template-activities=0|1  Delete existing activities from the hidden template course before recreating them\n";
    exit(0);
}

$csvdir = rtrim($options['dir'], DIRECTORY_SEPARATOR);
$dryrun = !empty($options['dry-run']) && $options['dry-run'] !== '0';
$activitymode = strtolower($options['activity-mode'] ?: 'page');
$resetactivities = !empty($options['reset-template-activities']) && $options['reset-template-activities'] !== '0';

function msg($text) { cli_writeln($text); }

function read_csv_file($dir, $filename) {
    $path = csv_pack_resolve_file($dir, $filename);
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

function csv_value(array $row, $key, $default = '') {
    return array_key_exists($key, $row) && $row[$key] !== '' ? $row[$key] : $default;
}

function supports_grade_completion($modname) {
    return plugin_supports('mod', $modname, FEATURE_GRADE_HAS_GRADE, false);
}

function completion_state_for_rule($rule, $modname) {
    if ($rule === 'passgrade' && supports_grade_completion($modname)) {
        return COMPLETION_COMPLETE_PASS;
    }
    return COMPLETION_COMPLETE;
}

function apply_completion_settings(stdClass $info, array $row, $modname) {
    $rule = strtolower(csv_value($row, 'completion_rule', 'view'));
    $required = as_bool_int(csv_value($row, 'completion_required', '1'), 1);

    $info->completion = COMPLETION_TRACKING_NONE;
    $info->completionview = COMPLETION_VIEW_NOT_REQUIRED;
    $info->completiongradeitemnumber = null;
    $info->completionpassgrade = 0;
    $info->completionexpected = 0;
    $info->completionunlocked = 1;

    if (!$required || $rule === 'none') {
        return;
    }

    if ($rule === 'manual') {
        $info->completion = COMPLETION_TRACKING_MANUAL;
        return;
    }

    $info->completion = COMPLETION_TRACKING_AUTOMATIC;

    if ($rule === 'submit') {
        if (in_array($modname, ['assign', 'feedback', 'choice'], true)) {
            $info->completionsubmit = 1;
        } else {
            $info->completionview = COMPLETION_VIEW_REQUIRED;
        }
        return;
    }

    if (in_array($rule, ['grade', 'passgrade'], true) && supports_grade_completion($modname)) {
        $info->completionusegrade = 1;
        $info->completiongradeitemnumber = 0;
        $info->completionpassgrade = $rule === 'passgrade' ? 1 : 0;
        return;
    }

    $info->completionview = COMPLETION_VIEW_REQUIRED;
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

function reset_template_activities($course, $dryrun) {
    global $DB;
    if ($dryrun || empty($course->id)) {
        msg('[dry-run] Delete existing activities from the hidden template course before recreating the current CSV template.');
        return;
    }
    $cms = $DB->get_records('course_modules', ['course' => $course->id], 'id ASC', 'id');
    foreach ($cms as $cm) {
        course_delete_module($cm->id);
    }
    rebuild_course_cache($course->id, true);
    msg('Deleted ' . count($cms) . ' existing template course activities.');
}

function module_table_exists($modname) {
    global $DB;
    return $DB->get_manager()->table_exists($modname);
}

function create_placeholder_activity($course, $row, $activitymode, $dryrun) {
    global $DB, $CFG;
    $recommended = strtolower($row['recommended_activity_type']);
    $safeplaceholder = strtolower(csv_value($row, 'safe_placeholder_module', 'page'));
    if (!$DB->record_exists('modules', ['name' => $safeplaceholder])) {
        $safeplaceholder = 'page';
    }
    $modname = ($activitymode === 'native' && $recommended !== 'customcert' && module_table_exists($recommended))
        ? $recommended
        : $safeplaceholder;
    if (!$DB->record_exists('modules', ['name' => $modname])) {
        msg("Skipping activity {$row['default_name']}; module not installed: $modname");
        return null;
    }
    $title = $row['default_name'];
    if ($modname === 'page' && $recommended !== 'page') {
        $title = '[' . strtoupper($recommended) . '] ' . $title;
    }
    if ($DB->get_manager()->table_exists($modname) && $instance = $DB->get_record($modname, ['course' => $course->id, 'name' => $title])) {
        msg("Activity exists: $title");
        $cm = get_coursemodule_from_instance($modname, $instance->id, $course->id, false, IGNORE_MISSING);
        return $cm ? [
            'cmid' => (int)$cm->id,
            'modname' => $modname,
            'completionstate' => completion_state_for_rule(strtolower(csv_value($row, 'completion_rule', 'view')), $modname),
        ] : null;
    }
    if ($dryrun || empty($course->id)) {
        msg("[dry-run] Create $modname activity in section {$row['section_number']}: $title");
        return null;
    }
    $module = $DB->get_record('modules', ['name' => $modname], '*', MUST_EXIST);
    $info = new stdClass();
    $info->modulename = $modname;
    $info->module = $module->id;
    $info->course = $course->id;
    $info->section = (int)$row['section_number'];
    $info->visible = as_bool_int($row['visible'] ?? '1', 1);
    $info->visibleoncoursepage = $info->visible;
    $info->name = $title;
    $info->intro = '<p>' . s($row['purpose']) . '</p><p><em>Replace this placeholder with grade-level and subject-specific content.</em></p>';
    $info->introformat = FORMAT_HTML;
    $info->cmidnumber = csv_value($row, 'activity_key', '');
    $info->groupmode = 0;
    $info->groupingid = 0;
    $info->add = $modname;
    apply_completion_settings($info, $row, $modname);
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
        if (is_numeric(csv_value($row, 'grade_to_pass', ''))) {
            $info->gradepass = $info->grade * ((float)$row['grade_to_pass'] / 100);
        }
        $info->alwaysshowdescription = 0;
        $info->nosubmissions = 0;
        $info->submissiondrafts = 0;
        $info->sendnotifications = 0;
        $info->sendlatenotifications = 0;
        $info->allowsubmissionsfromdate = 0;
        $info->duedate = 0;
        $info->cutoffdate = 0;
        $info->gradingduedate = 0;
        $info->requiresubmissionstatement = 0;
        $info->teamsubmission = 0;
        $info->requireallteammemberssubmit = 0;
        $info->teamsubmissiongroupingid = 0;
        $info->blindmarking = 0;
        $info->hidegrader = 0;
        $info->revealidentities = 0;
        $info->attemptreopenmethod = 'untilpass';
        $info->maxattempts = -1;
        $info->markingworkflow = 0;
        $info->markingallocation = 0;
        $info->markercount = 1;
        $info->sendstudentnotifications = 1;
        $info->preventsubmissionnotingroup = 0;
        $info->activity = '';
        $info->activityformat = FORMAT_HTML;
        $info->timelimit = 0;
        $info->submissionattachments = 0;
        $info->gradepenalty = 0;
    } else if ($modname === 'quiz') {
        $info->grade = is_numeric($row['default_points']) ? (float)$row['default_points'] : 10;
        if (is_numeric(csv_value($row, 'grade_to_pass', ''))) {
            $info->gradepass = $info->grade * ((float)$row['grade_to_pass'] / 100);
        }
        $info->sumgrades = $info->grade;
        $info->timeopen = 0;
        $info->timeclose = 0;
        $info->timelimit = 0;
        $info->overduehandling = 'autoabandon';
        $info->graceperiod = 0;
        $info->preferredbehaviour = 'deferredfeedback';
        $info->canredoquestions = 0;
        $info->attempts = 0;
        $info->attemptonlast = 0;
        $info->grademethod = 1;
        $info->decimalpoints = 2;
        $info->questiondecimalpoints = -1;
        $info->attemptduring = 1;
        $info->correctnessduring = 1;
        $info->maxmarksduring = 1;
        $info->marksduring = 1;
        $info->specificfeedbackduring = 1;
        $info->generalfeedbackduring = 1;
        $info->rightanswerduring = 1;
        $info->overallfeedbackduring = 0;
        $info->attemptimmediately = 1;
        $info->correctnessimmediately = 1;
        $info->maxmarksimmediately = 1;
        $info->marksimmediately = 1;
        $info->specificfeedbackimmediately = 1;
        $info->generalfeedbackimmediately = 1;
        $info->rightanswerimmediately = 1;
        $info->overallfeedbackimmediately = 1;
        $info->attemptopen = 1;
        $info->correctnessopen = 1;
        $info->maxmarksopen = 1;
        $info->marksopen = 1;
        $info->specificfeedbackopen = 1;
        $info->generalfeedbackopen = 1;
        $info->rightansweropen = 1;
        $info->overallfeedbackopen = 1;
        $info->attemptclosed = 1;
        $info->correctnessclosed = 1;
        $info->maxmarksclosed = 1;
        $info->marksclosed = 1;
        $info->specificfeedbackclosed = 1;
        $info->generalfeedbackclosed = 1;
        $info->rightanswerclosed = 1;
        $info->overallfeedbackclosed = 1;
        $info->questionsperpage = 1;
        $info->navmethod = 'free';
        $info->shuffleanswers = 1;
        $info->quizpassword = '';
        $info->subnet = '';
        $info->browsersecurity = '';
        $info->delay1 = 0;
        $info->delay2 = 0;
        $info->showuserpicture = 0;
        $info->showblocks = 0;
        $info->completionattemptsexhausted = 0;
        $info->completionminattempts = 0;
        $info->allowofflineattempts = 0;
    } else if ($modname === 'feedback') {
        $info->timeopen = 0;
        $info->timeclose = 0;
        $info->anonymous = 1;
        $info->email_notification = 0;
        $info->multiple_submit = 0;
        $info->autonumbering = 1;
        $info->site_after_submit = '';
        $info->page_after_submit = '';
        $info->page_after_submitformat = FORMAT_HTML;
        $info->publish_stats = 0;
    }
    try {
        $created = add_moduleinfo($info, $course);
        msg("Created $modname activity: $title");
        return [
            'cmid' => (int)$created->coursemodule,
            'modname' => $modname,
            'completionstate' => completion_state_for_rule(strtolower(csv_value($row, 'completion_rule', 'view')), $modname),
        ];
    } catch (Throwable $e) {
        msg("Could not create native activity $title: " . $e->getMessage());
        return null;
    }
}

function apply_sequential_chapter_availability($course, $sections, $gateactivities, $dryrun) {
    global $CFG, $DB;
    if ($dryrun || empty($course->id)) {
        msg('[dry-run] Apply restricted access: Chapter 2..10 and final review unlock from the previous chapter gate.');
        return;
    }
    if (empty($gateactivities)) {
        msg('No chapter gate activities found; sequential chapter restrictions were not applied.');
        return;
    }
    if (empty($CFG->enableavailability)) {
        msg('WARNING: Moodle restricted access is disabled. Set enableavailability=1 before relying on chapter unlock rules.');
    }

    foreach ($sections as $row) {
        $sectionnum = (int)$row['section_number'];
        if ($sectionnum < 3 || $sectionnum > 12) {
            continue;
        }
        $previoussection = $sectionnum - 1;
        if (empty($gateactivities[$previoussection]['cmid'])) {
            msg("Skipping restriction for section $sectionnum; missing gate for section $previoussection.");
            continue;
        }
        $condition = [
            'op' => '&',
            'showc' => [true],
            'c' => [[
                'type' => 'completion',
                'cm' => (int)$gateactivities[$previoussection]['cmid'],
                'e' => (int)$gateactivities[$previoussection]['completionstate'],
            ]],
        ];
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $sectionnum], '*', MUST_EXIST);
        course_update_section($course, $section, ['availability' => json_encode($condition)]);
        msg("Applied restricted access to section $sectionnum using section $previoussection gate.");
    }
    rebuild_course_cache($course->id, true);
}

$template = read_csv_file($csvdir, 'master_course_template.csv')[0];
$sections = read_csv_file($csvdir, 'course_template_sections.csv');
$activities = read_csv_file($csvdir, 'course_template_activities.csv');

msg($dryrun ? 'Mode: DRY RUN' : 'Mode: EXECUTE');
$categoryid = ensure_template_category($template, $dryrun);
$course = ensure_template_course($template, $categoryid, $dryrun);
update_course_settings($course, $template, $dryrun);
ensure_sections($course, $sections, $dryrun);
if ($resetactivities) {
    reset_template_activities($course, $dryrun);
}
$gateactivities = [];
foreach ($activities as $row) {
    $created = create_placeholder_activity($course, $row, $activitymode, $dryrun);
    if (as_bool_int(csv_value($row, 'unlock_next', '0'), 0) && $created) {
        $gateactivities[(int)$row['section_number']] = $created;
    }
}
apply_sequential_chapter_availability($course, $sections, $gateactivities, $dryrun);
msg('Master course template process completed.');
