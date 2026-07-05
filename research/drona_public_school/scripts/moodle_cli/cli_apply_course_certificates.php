<?php
// Create or update per-course Custom certificate activities from course_certificates.csv.
// Copy to /path/to/moodle/admin/cli/ and run as the web-server user.

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once(__DIR__ . '/cli_csv_helpers.php');

list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'dir' => null,
    'dry-run' => true,
    'limit' => 0,
    'refresh-template' => true,
], [
    'h' => 'help',
]);

if ($options['help'] || empty($options['dir'])) {
    echo "Apply Drona course certificate activities\n\n";
    echo "Options:\n";
    echo "  --dir=/absolute/path/to/csv-pack\n";
    echo "  --dry-run=1|0\n";
    echo "  --limit=10                 Limit courses; 0 means all\n";
    echo "  --refresh-template=1|0     Rebuild generated certificate PDF elements\n";
    exit(0);
}

$csvdir = rtrim($options['dir'], DIRECTORY_SEPARATOR);
$dryrun = !empty($options['dry-run']) && $options['dry-run'] !== '0';
$limit = (int)$options['limit'];
$refreshtemplate = !empty($options['refresh-template']) && $options['refresh-template'] !== '0';

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

function require_customcert_plugin() {
    global $DB, $CFG;
    if (!$DB->record_exists('modules', ['name' => 'customcert'])) {
        cli_error('mod_customcert is required. Install/sync moodle-overrides/public/mod/customcert before running certificates.');
    }
    foreach (['customcert', 'customcert_templates', 'customcert_pages', 'customcert_elements'] as $table) {
        if (!$DB->get_manager()->table_exists($table)) {
            cli_error("mod_customcert table is missing: $table. Complete Moodle plugin upgrade first.");
        }
    }
    require_once($CFG->dirroot . '/mod/customcert/lib.php');
}

function certificate_activity_name(array $row) {
    return csv_value($row, 'certificate_activity_name', 'Download Course Completion Certificate');
}

function certificate_activity_key(array $row) {
    return csv_value($row, 'certificate_activity_key', 'course_completion_certificate');
}

function get_course_by_certificate_row(array $row) {
    global $DB;
    $course = false;
    if (!empty($row['course_code'])) {
        $course = $DB->get_record('course', ['idnumber' => $row['course_code']], '*', IGNORE_MISSING);
    }
    if (!$course && !empty($row['course_shortname'])) {
        $course = $DB->get_record('course', ['shortname' => $row['course_shortname']], '*', IGNORE_MISSING);
    }
    return $course;
}

function ensure_course_certificate_section($course, array $row, $dryrun) {
    global $DB;
    $sectionnum = (int)csv_value($row, 'certificate_section_number', '15');
    if ($dryrun) {
        return $sectionnum;
    }
    if ((int)$course->numsections < $sectionnum) {
        $course->numsections = $sectionnum;
        $DB->update_record('course', $course);
    }
    course_create_sections_if_missing($course->id, range(0, $sectionnum));
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $sectionnum], '*', MUST_EXIST);
    if (trim((string)$section->name) === '') {
        $section->name = csv_value($row, 'certificate_section_name', 'Certificate & Completion');
    }
    if (trim((string)$section->summary) === '') {
        $section->summary = '<p>Review completion status and download the official course certificate after meeting course requirements.</p>';
        $section->summaryformat = FORMAT_HTML;
    }
    $section->visible = as_bool_int(csv_value($row, 'visible_to_student', '1'), 1);
    $DB->update_record('course_sections', $section);
    return $sectionnum;
}

function find_customcert_cm($course, $activitykey, $activityname) {
    global $DB;
    $module = $DB->get_record('modules', ['name' => 'customcert'], '*', MUST_EXIST);
    $cm = $DB->get_record('course_modules', [
        'course' => $course->id,
        'module' => $module->id,
        'idnumber' => $activitykey,
    ], '*', IGNORE_MISSING);
    if ($cm) {
        return get_coursemodule_from_id('customcert', $cm->id, $course->id, false, MUST_EXIST);
    }
    $instance = $DB->get_record('customcert', ['course' => $course->id, 'name' => $activityname], '*', IGNORE_MISSING);
    if ($instance) {
        return get_coursemodule_from_instance('customcert', $instance->id, $course->id, false, MUST_EXIST);
    }
    return null;
}

function certificate_intro($course, array $row) {
    $template = csv_value($row, 'certificate_template_name', 'Drona Modern Course Completion Certificate');
    $condition = csv_value($row, 'issue_condition', 'course_completion');
    return '<p><strong>' . s($template) . '</strong></p>'
        . '<p>Download your course completion certificate after satisfying the configured issue condition: '
        . s($condition) . '.</p>'
        . '<p>This certificate includes the learner name, course name, academic year, final grade, certificate code and verification QR.</p>';
}

function build_customcert_module_info($course, array $row, $sectionnum) {
    global $DB;
    $module = $DB->get_record('modules', ['name' => 'customcert'], '*', MUST_EXIST);
    $info = new stdClass();
    $info->modulename = 'customcert';
    $info->module = $module->id;
    $info->course = $course->id;
    $info->section = $sectionnum;
    $info->visible = as_bool_int(csv_value($row, 'visible_to_student', '1'), 1);
    $info->visibleoncoursepage = $info->visible;
    $info->name = certificate_activity_name($row);
    $info->intro = certificate_intro($course, $row);
    $info->introformat = FORMAT_HTML;
    $info->cmidnumber = certificate_activity_key($row);
    $info->groupmode = 0;
    $info->groupingid = 0;
    $info->add = 'customcert';
    $info->deliveryoption = csv_value($row, 'certificate_download_mode', 'D');
    $info->usecustomfilename = 1;
    $info->customfilenamepattern = csv_value(
        $row,
        'certificate_filename_pattern',
        '{COURSE_SHORT_NAME}-{FIRST_NAME}-{LAST_NAME}-{ISSUE_DATE}'
    );
    $info->emailstudents = as_bool_int(csv_value($row, 'certificate_email_students', '0'), 0);
    $info->emailteachers = 0;
    $info->emailothers = '';
    $info->verifyany = as_bool_int(csv_value($row, 'certificate_verification_enabled', '1'), 1);
    $info->requiredtime = (int)csv_value($row, 'certificate_required_minutes', '0');
    $info->protection_print = 0;
    $info->protection_modify = 1;
    $info->protection_copy = 1;
    $info->language = '';
    $info->completion = COMPLETION_TRACKING_AUTOMATIC;
    $info->completionview = COMPLETION_VIEW_REQUIRED;
    $info->completiongradeitemnumber = null;
    $info->completionpassgrade = 0;
    $info->completionexpected = 0;
    $info->completionunlocked = 1;
    return $info;
}

function update_existing_customcert($cm, $course, array $row, $sectionnum, $dryrun) {
    global $DB;
    if ($dryrun) {
        msg("[dry-run] Update existing customcert activity {$cm->name} in {$course->shortname}");
        return;
    }
    $cert = $DB->get_record('customcert', ['id' => $cm->instance], '*', MUST_EXIST);
    $cert->name = certificate_activity_name($row);
    $cert->intro = certificate_intro($course, $row);
    $cert->introformat = FORMAT_HTML;
    $cert->deliveryoption = csv_value($row, 'certificate_download_mode', 'D');
    $cert->usecustomfilename = 1;
    $cert->customfilenamepattern = csv_value(
        $row,
        'certificate_filename_pattern',
        '{COURSE_SHORT_NAME}-{FIRST_NAME}-{LAST_NAME}-{ISSUE_DATE}'
    );
    $cert->emailstudents = as_bool_int(csv_value($row, 'certificate_email_students', '0'), 0);
    $cert->emailteachers = 0;
    $cert->emailothers = '';
    $cert->verifyany = as_bool_int(csv_value($row, 'certificate_verification_enabled', '1'), 1);
    $cert->requiredtime = (int)csv_value($row, 'certificate_required_minutes', '0');
    $cert->protection = 'modify, copy';
    $cert->timemodified = time();
    $DB->update_record('customcert', $cert);

    $mod = $DB->get_record('course_modules', ['id' => $cm->id], '*', MUST_EXIST);
    $mod->section = $DB->get_field('course_sections', 'id', ['course' => $course->id, 'section' => $sectionnum], MUST_EXIST);
    $mod->visible = as_bool_int(csv_value($row, 'visible_to_student', '1'), 1);
    $mod->visibleoncoursepage = $mod->visible;
    $mod->idnumber = certificate_activity_key($row);
    $mod->completion = COMPLETION_TRACKING_AUTOMATIC;
    $mod->completionview = COMPLETION_VIEW_REQUIRED;
    $DB->update_record('course_modules', $mod);
}

function ensure_customcert_activity($course, array $row, $sectionnum, $dryrun) {
    $activitykey = certificate_activity_key($row);
    $activityname = certificate_activity_name($row);
    $cm = find_customcert_cm($course, $activitykey, $activityname);
    if ($cm) {
        update_existing_customcert($cm, $course, $row, $sectionnum, $dryrun);
        return $cm;
    }
    if ($dryrun) {
        msg("[dry-run] Create customcert activity in {$course->shortname}: $activityname");
        return null;
    }
    $info = build_customcert_module_info($course, $row, $sectionnum);
    $created = add_moduleinfo($info, $course);
    msg("Created customcert activity in {$course->shortname}: $activityname");
    return get_coursemodule_from_id('customcert', $created->coursemodule, $course->id, false, MUST_EXIST);
}

function cert_payload(array $data) {
    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function cert_text($pageid, $name, $text, $x, $y, $fontsize, $colour, $width, $alignment = 'C', $sequence = 1) {
    return [
        'pageid' => $pageid,
        'name' => $name,
        'element' => 'text',
        'data' => cert_payload([
            'text' => $text,
            'font' => 'helvetica',
            'fontsize' => $fontsize,
            'colour' => $colour,
            'width' => $width,
        ]),
        'posx' => $x,
        'posy' => $y,
        'refpoint' => 1,
        'alignment' => $alignment,
        'sequence' => $sequence,
    ];
}

function cert_dynamic($pageid, $name, $element, array $data, $x, $y, $width, $alignment = 'C', $sequence = 1) {
    $data += [
        'font' => 'helvetica',
        'fontsize' => 12,
        'colour' => '#18394f',
        'width' => $width,
    ];
    return [
        'pageid' => $pageid,
        'name' => $name,
        'element' => $element,
        'data' => cert_payload($data),
        'posx' => $x,
        'posy' => $y,
        'refpoint' => 1,
        'alignment' => $alignment,
        'sequence' => $sequence,
    ];
}

function insert_certificate_element(array $row) {
    global $DB;
    $record = (object)$row;
    $record->timecreated = time();
    $record->timemodified = $record->timecreated;
    $DB->insert_record('customcert_elements', $record);
}

function build_certificate_elements($pageid, array $row) {
    $school = csv_value($row, 'school_name', 'Drona Public School');
    $board = csv_value($row, 'board_name', 'Gujarat Board Education');
    $grade = csv_value($row, 'grade_name', csv_value($row, 'grade_code', ''));
    $medium = csv_value($row, 'medium_name', csv_value($row, 'medium_code', ''));
    $subject = csv_value($row, 'subject_name', '');
    $year = csv_value($row, 'academic_year', '');
    $principal = csv_value($row, 'principal_name', 'Anita Sharma');
    $primary = csv_value($row, 'certificate_brand_primary', '#0B4F71');
    $accent = csv_value($row, 'certificate_brand_accent', '#F2B705');
    $red = csv_value($row, 'certificate_brand_highlight', '#E51B23');
    $mediumlabel = ($medium && stripos($medium, 'medium') === false) ? $medium . ' Medium' : $medium;
    $metadata = array_filter([
        $grade,
        $mediumlabel,
        $subject,
        $year ? 'Academic Year ' . $year : '',
    ]);

    $elements = [];
    $elements[] = [
        'pageid' => $pageid,
        'name' => 'Modern navy border',
        'element' => 'border',
        'data' => cert_payload(['colour' => $primary, 'width' => 1]),
        'posx' => 0,
        'posy' => 0,
        'refpoint' => 0,
        'alignment' => 'L',
        'sequence' => 1,
    ];
    $elements[] = cert_text($pageid, 'School name', '<strong>' . s($school) . '</strong>', 148, 18, 19, $primary, 260, 'C', 2);
    $elements[] = cert_text($pageid, 'Board name', s($board), 148, 29, 10, '#546E7A', 230, 'C', 3);
    $elements[] = cert_text($pageid, 'Top accent line', '------------------------------', 148, 36, 10, $accent, 180, 'C', 4);
    $elements[] = cert_text($pageid, 'Certificate title', '<strong>Certificate of Course Completion</strong>', 148, 48, 25, $primary, 250, 'C', 5);
    $elements[] = cert_text($pageid, 'Award text', 'This is to certify that', 148, 68, 12, '#455A64', 220, 'C', 6);
    $elements[] = cert_dynamic($pageid, 'Student full name', 'studentname', [
        'font' => 'helvetica',
        'fontsize' => 25,
        'colour' => $red,
        'width' => 240,
    ], 148, 82, 240, 'C', 7);
    $elements[] = cert_text($pageid, 'Completion statement', 'has successfully completed the course', 148, 103, 12, '#455A64', 230, 'C', 8);
    $elements[] = cert_dynamic($pageid, 'Course full name', 'coursename', [
        'coursenamedisplay' => 2,
        'font' => 'helvetica',
        'fontsize' => 13,
        'colour' => $primary,
        'width' => 250,
    ], 148, 116, 250, 'C', 9);
    $elements[] = cert_text(
        $pageid,
        'Course metadata',
        s(implode(' | ', $metadata)),
        148,
        134,
        10,
        '#263238',
        250,
        'C',
        10
    );
    $elements[] = cert_text($pageid, 'Issue date label', '<strong>Issue date</strong>', 68, 151, 9, '#546E7A', 75, 'L', 11);
    $elements[] = cert_dynamic($pageid, 'Issue date', 'date', [
        'dateitem' => '-1',
        'dateformat' => 'strftimedate',
        'font' => 'helvetica',
        'fontsize' => 11,
        'colour' => '#263238',
        'width' => 75,
    ], 68, 160, 75, 'L', 12);
    $elements[] = cert_text($pageid, 'Final grade label', '<strong>Final grade</strong>', 148, 151, 9, '#546E7A', 60, 'C', 13);
    $elements[] = cert_dynamic($pageid, 'Course grade', 'grade', [
        'gradeitem' => '0',
        'gradeformat' => '2',
        'font' => 'helvetica',
        'fontsize' => 11,
        'colour' => '#263238',
        'width' => 60,
    ], 148, 160, 60, 'C', 14);
    $elements[] = cert_text($pageid, 'Certificate code label', '<strong>Certificate ID</strong>', 228, 151, 9, '#546E7A', 75, 'R', 15);
    $elements[] = cert_dynamic($pageid, 'Certificate code', 'code', [
        'font' => 'helvetica',
        'fontsize' => 10,
        'colour' => '#263238',
        'width' => 75,
    ], 228, 160, 75, 'R', 16);
    $elements[] = cert_dynamic($pageid, 'Verification QR code', 'qrcode', [
        'width' => 24,
        'height' => 24,
    ], 246, 175, 24, 'C', 17);
    $elements[] = cert_text($pageid, 'Principal signature line', '________________________', 74, 174, 10, '#263238', 85, 'C', 18);
    $elements[] = cert_text($pageid, 'Principal name', s($principal), 74, 184, 10, $primary, 90, 'C', 19);
    $elements[] = cert_text($pageid, 'Principal title', 'Principal', 74, 190, 9, '#546E7A', 90, 'C', 20);
    $elements[] = cert_text($pageid, 'Verification note', 'Scan the QR code or use the Certificate ID to verify authenticity.', 148, 198, 8, '#607D8B', 240, 'C', 21);
    return $elements;
}

function configure_customcert_template($cm, $course, array $row, $dryrun, $refreshtemplate) {
    global $DB;
    if (!$cm) {
        return;
    }
    $cert = $DB->get_record('customcert', ['id' => $cm->instance], '*', MUST_EXIST);
    if ($dryrun) {
        msg("[dry-run] Configure modern certificate template for {$course->shortname}");
        return;
    }
    $template = $DB->get_record('customcert_templates', ['id' => $cert->templateid], '*', MUST_EXIST);
    $template->name = csv_value($row, 'certificate_template_name', 'Drona Modern Course Completion Certificate') . ' - ' . $course->shortname;
    $template->timemodified = time();
    $DB->update_record('customcert_templates', $template);

    $page = $DB->get_record('customcert_pages', ['templateid' => $template->id], '*', IGNORE_MISSING);
    if (!$page) {
        $page = (object)[
            'templateid' => $template->id,
            'width' => 297,
            'height' => 210,
            'leftmargin' => 0,
            'rightmargin' => 0,
            'sequence' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $page->id = $DB->insert_record('customcert_pages', $page);
    } else {
        $page->width = 297;
        $page->height = 210;
        $page->leftmargin = 0;
        $page->rightmargin = 0;
        $page->sequence = 1;
        $page->timemodified = time();
        $DB->update_record('customcert_pages', $page);
    }

    $existing = $DB->count_records('customcert_elements', ['pageid' => $page->id]);
    if (!$refreshtemplate && $existing > 0) {
        msg("Certificate template already has elements, keeping existing layout: {$course->shortname}");
        return;
    }
    $DB->delete_records('customcert_elements', ['pageid' => $page->id]);
    foreach (build_certificate_elements($page->id, $row) as $element) {
        insert_certificate_element($element);
    }
    msg("Configured modern certificate template for {$course->shortname}");
}

function apply_final_activity_availability($cm, $course, array $row, $dryrun) {
    global $DB;
    if (!$cm) {
        return;
    }
    $gateidnumber = csv_value($row, 'certificate_unlock_activity_key', 'final_project_portfolio');
    if ($gateidnumber === '') {
        return;
    }
    $gatecm = $DB->get_record('course_modules', ['course' => $course->id, 'idnumber' => $gateidnumber], '*', IGNORE_MISSING);
    if (!$gatecm) {
        msg("Certificate is visible without activity restriction; unlock activity not found in {$course->shortname}: $gateidnumber");
        return;
    }
    if ($dryrun) {
        msg("[dry-run] Restrict certificate in {$course->shortname} until $gateidnumber is complete");
        return;
    }
    $condition = [
        'op' => '&',
        'showc' => [true],
        'c' => [[
            'type' => 'completion',
            'cm' => (int)$gatecm->id,
            'e' => COMPLETION_COMPLETE,
        ]],
    ];
    $record = $DB->get_record('course_modules', ['id' => $cm->id], '*', MUST_EXIST);
    $record->availability = json_encode($condition);
    $DB->update_record('course_modules', $record);
    rebuild_course_cache($course->id, true);
}

require_customcert_plugin();

msg($dryrun ? 'Mode: DRY RUN' : 'Mode: EXECUTE');
$rows = read_csv_file($csvdir, 'course_certificates.csv');
$count = 0;
$skipped = 0;
foreach ($rows as $row) {
    if ($limit > 0 && $count >= $limit) {
        break;
    }
    if (!as_bool_int(csv_value($row, 'certificate_enabled', '1'), 1)) {
        $skipped++;
        continue;
    }
    $course = get_course_by_certificate_row($row);
    if (!$course) {
        msg("Course not found, skipping certificate: " . csv_value($row, 'course_shortname', csv_value($row, 'course_code', 'unknown')));
        $skipped++;
        continue;
    }
    $sectionnum = ensure_course_certificate_section($course, $row, $dryrun);
    $cm = ensure_customcert_activity($course, $row, $sectionnum, $dryrun);
    configure_customcert_template($cm, $course, $row, $dryrun, $refreshtemplate);
    apply_final_activity_availability($cm, $course, $row, $dryrun);
    if (!$dryrun) {
        rebuild_course_cache($course->id, true);
    }
    $count++;
}

msg("Course certificates processed for $count courses. Skipped: $skipped.");
