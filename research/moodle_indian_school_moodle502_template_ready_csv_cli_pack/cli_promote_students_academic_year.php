<?php
// Promote students from one academic year to the next using student_promotion_plan CSV.
// Copy to /path/to/moodle/admin/cli/cli_promote_students_academic_year.php
// Run: php admin/cli/cli_promote_students_academic_year.php --dir=/path/to/csv-pack --plan=student_promotion_plan_2027_2028.csv --dry-run=1

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->libdir . '/accesslib.php');
require_once($CFG->libdir . '/enrollib.php');

list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'dir' => null,
    'plan' => 'student_promotion_plan_2027_2028.csv',
    'dry-run' => true,
    'remove-old-cohort' => true,
    'update-profile' => true,
    'suspend-transfer-out' => false,
    'sync-cohort-enrolments' => true,
], ['h' => 'help']);

if ($options['help'] || empty($options['dir'])) {
    echo "Promote students between academic years\n\n";
    echo "Options:\n";
    echo "  --dir=/path/to/csv-pack\n";
    echo "  --plan=student_promotion_plan_2027_2028.csv\n";
    echo "  --dry-run=1|0\n";
    echo "  --remove-old-cohort=1|0\n";
    echo "  --update-profile=1|0\n";
    echo "  --suspend-transfer-out=1|0\n";
    echo "  --sync-cohort-enrolments=1|0\n";
    exit(0);
}

$csvdir = rtrim($options['dir'], DIRECTORY_SEPARATOR);
$dryrun = !empty($options['dry-run']) && $options['dry-run'] !== '0';
$removeolddefault = !empty($options['remove-old-cohort']) && $options['remove-old-cohort'] !== '0';
$updateprofiledefault = !empty($options['update-profile']) && $options['update-profile'] !== '0';
$suspendtransferout = !empty($options['suspend-transfer-out']) && $options['suspend-transfer-out'] !== '0';
$synccohorts = !empty($options['sync-cohort-enrolments']) && $options['sync-cohort-enrolments'] !== '0';
\core\session\manager::set_user(get_admin());
\core_php_time_limit::raise(0);
raise_memory_limit(MEMORY_EXTRA);

function pmsg($text) { cli_writeln($text); }
function csv_rows($filename) {
    global $csvdir;
    $path = $csvdir . DIRECTORY_SEPARATOR . $filename;
    if (!file_exists($path)) { cli_error("Missing CSV: $filename"); }
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
function boolish($v, $default = true) {
    if ($v === '' || $v === null) { return $default; }
    return in_array(strtolower((string)$v), ['1','yes','true','y'], true);
}
function user_by_username($username) {
    global $DB, $CFG;
    return $DB->get_record('user', ['username' => core_text::strtolower($username), 'mnethostid' => $CFG->mnet_localhost_id, 'deleted' => 0], '*', IGNORE_MISSING);
}
function cohort_by_code($code) {
    global $DB;
    if ($code === '') { return false; }
    return $DB->get_record('cohort', ['idnumber' => $code], '*', IGNORE_MISSING);
}
function ensure_member($user, $cohort, $dryrun) {
    global $DB;
    if (!$cohort) { cli_error('Target cohort missing.'); }
    if ($DB->record_exists('cohort_members', ['cohortid' => $cohort->id, 'userid' => $user->id])) { return; }
    if ($dryrun) { pmsg("[dry-run] Add {$user->username} to cohort {$cohort->idnumber}"); return; }
    cohort_add_member($cohort->id, $user->id);
    pmsg("Added {$user->username} to cohort {$cohort->idnumber}");
}
function remove_member_if_exists($user, $cohort, $dryrun) {
    global $DB;
    if (!$cohort) { return; }
    if (!$DB->record_exists('cohort_members', ['cohortid' => $cohort->id, 'userid' => $user->id])) { return; }
    if ($dryrun) { pmsg("[dry-run] Remove {$user->username} from old cohort {$cohort->idnumber}"); return; }
    cohort_remove_member($cohort->id, $user->id);
    pmsg("Removed {$user->username} from old cohort {$cohort->idnumber}");
}
function update_student_profile($user, $row, $dryrun) {
    $obj = new stdClass();
    $obj->id = $user->id;
    // These fields match user_profile_fields.csv shortnames in this pack.
    $map = [
        'target_academic_year' => 'profile_field_academic_year',
        'target_grade_code' => 'profile_field_grade_code',
        'target_stream_code' => 'profile_field_stream_code',
        'target_division_code' => 'profile_field_division_code',
        'target_board_code' => 'profile_field_board_code',
        'target_school_code' => 'profile_field_school_code',
        'target_medium_code' => 'profile_field_medium_code',
        'new_roll_no' => 'profile_field_roll_no',
    ];
    foreach ($map as $csvfield => $profilefield) {
        if (isset($row[$csvfield]) && $row[$csvfield] !== '') { $obj->{$profilefield} = $row[$csvfield]; }
    }
    // Also update standard Moodle department for quick visibility.
    if (!empty($row['target_grade_code'])) {
        $obj->department = trim(($row['target_grade_code'] ?? '') . '-' . ($row['target_stream_code'] ?? '') . '-' . ($row['target_division_code'] ?? ''), '-');
    }
    if ($dryrun) { pmsg("[dry-run] Update profile for {$user->username}"); return; }
    user_update_user($obj, false, false);
    profile_save_data($obj);
    pmsg("Updated profile for {$user->username}");
}
function suspend_user($user, $dryrun) {
    if ($dryrun) { pmsg("[dry-run] Suspend transfer-out user {$user->username}"); return; }
    $obj = (object)['id' => $user->id, 'suspended' => 1];
    user_update_user($obj, false, false);
    pmsg("Suspended transfer-out user {$user->username}");
}

pmsg('Student promotion: ' . ($dryrun ? 'DRY RUN' : 'LIVE'));
$rows = csv_rows($options['plan']);
$processed = 0;
foreach ($rows as $i => $row) {
    $line = $i + 2;
    $username = core_text::strtolower($row['student_username'] ?? '');
    if ($username === '') { pmsg("Skipping blank username at line $line"); continue; }
    $user = user_by_username($username);
    if (!$user) { pmsg("SKIP line $line: user not found: $username"); continue; }
    $decision = strtoupper($row['promotion_decision'] ?? '');
    $result = strtoupper($row['result_status'] ?? '');
    if ($decision === '' || $result === 'PENDING') { pmsg("SKIP {$username}: pending decision/result"); continue; }

    $oldcohort = cohort_by_code($row['current_cohort_code'] ?? '');
    $targetcohort = cohort_by_code($row['target_cohort_code'] ?? '');
    $removeold = boolish($row['remove_from_previous_cohort'] ?? '', $removeolddefault);
    $updateprofile = boolish($row['update_user_profile'] ?? '', $updateprofiledefault);

    pmsg("Processing {$username}: {$decision}");

    if (in_array($decision, ['PROMOTED','STREAM_SELECTED','REPEATED','ALUMNI'], true)) {
        if (!$targetcohort) { pmsg("ERROR line $line: target cohort not found: " . ($row['target_cohort_code'] ?? '')); continue; }
        ensure_member($user, $targetcohort, $dryrun);
        if ($removeold) { remove_member_if_exists($user, $oldcohort, $dryrun); }
        if ($updateprofile && $decision !== 'ALUMNI') { update_student_profile($user, $row, $dryrun); }
        if ($updateprofile && $decision === 'ALUMNI') {
            $row['target_grade_code'] = 'ALUMNI';
            $row['target_stream_code'] = 'ALUMNI';
            $row['target_division_code'] = '';
            update_student_profile($user, $row, $dryrun);
        }
        $processed++;
        continue;
    }

    if (in_array($decision, ['TRANSFER_OUT','LEFT_SCHOOL'], true)) {
        if ($removeold) { remove_member_if_exists($user, $oldcohort, $dryrun); }
        if ($updateprofile) { update_student_profile($user, $row, $dryrun); }
        if ($suspendtransferout) { suspend_user($user, $dryrun); }
        $processed++;
        continue;
    }

    pmsg("SKIP {$username}: unknown decision {$decision}");
}

if (!$dryrun && $synccohorts && file_exists($CFG->dirroot . '/enrol/cohort/locallib.php')) {
    require_once($CFG->dirroot . '/enrol/cohort/locallib.php');
    if (function_exists('enrol_cohort_sync')) { enrol_cohort_sync(new null_progress_trace(), null); }
}
pmsg("Done. Processed rows: $processed");
if ($dryrun) { pmsg('Dry run only. Re-run with --dry-run=0 after review and backup.'); }
