<?php
// Moodle academic year promotion / student rollover CLI.
// Copy this file to /path/to/moodle/admin/cli/ and run as the web-server user.
// Example:
// php admin/cli/cli_promote_academic_year.php --dir=/path/to/csv-pack --file=promotion_actions.csv --dry-run=1

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'dir' => null,
    'file' => 'promotion_actions.csv',
    'dry-run' => true,
    'remove-old-cohort' => false,
    'update-profile' => true,
    'allow-missing-to-cohort' => false,
], [
    'h' => 'help',
]);

if ($options['help'] || empty($options['dir'])) {
    echo "Academic year promotion / student rollover importer\n\n" .
        "Options:\n" .
        "  --dir=/absolute/path/to/csv-pack        Required CSV directory\n" .
        "  --file=promotion_actions.csv           Promotion CSV file name\n" .
        "  --dry-run=1                            Preview only, no writes\n" .
        "  --dry-run=0                            Execute writes\n" .
        "  --remove-old-cohort=1                  Remove old cohort membership after adding next cohort\n" .
        "  --update-profile=0                     Do not update custom profile fields\n" .
        "  --allow-missing-to-cohort=1            Log missing target cohort instead of stopping\n\n" .
        "CSV columns: action, username, from_cohort_code, to_cohort_code, from_academic_year, to_academic_year, from_grade_code, to_grade_code, from_stream_code, to_stream_code, from_division_code, to_division_code, result_status, remove_from_old_cohort, update_profile_fields, effective_date\n";
    exit(0);
}

$csvdir = rtrim($options['dir'], DIRECTORY_SEPARATOR);
$csvfile = $options['file'];
$dryrun = !empty($options['dry-run']) && $options['dry-run'] !== '0';
$globalremoveold = !empty($options['remove-old-cohort']) && $options['remove-old-cohort'] !== '0';
$globalupdateprofile = !empty($options['update-profile']) && $options['update-profile'] !== '0';
$allowmissingto = !empty($options['allow-missing-to-cohort']) && $options['allow-missing-to-cohort'] !== '0';

function msg($text) {
    cli_writeln($text);
}

function read_csv_file($dir, $filename) {
    $path = $dir . DIRECTORY_SEPARATOR . $filename;
    if (!file_exists($path)) {
        cli_error("CSV file not found: $path");
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
        if (count($data) === 1 && trim($data[0]) === '') {
            continue;
        }
        $row = [];
        foreach ($headers as $i => $h) {
            $row[$h] = isset($data[$i]) ? trim($data[$i]) : '';
        }
        $rows[] = $row;
    }
    fclose($handle);
    return $rows;
}

function truthy($value, $default = false) {
    if ($value === '' || $value === null) {
        return $default;
    }
    return in_array(strtolower((string)$value), ['1', 'true', 'yes', 'y'], true);
}

function get_user_by_username($username) {
    global $DB, $CFG;
    $username = core_text::strtolower(trim($username));
    return $DB->get_record('user', [
        'username' => $username,
        'mnethostid' => $CFG->mnet_localhost_id,
        'deleted' => 0,
    ]);
}

function get_cohort_by_idnumber($idnumber) {
    global $DB;
    if (trim($idnumber) === '') {
        return false;
    }
    return $DB->get_record('cohort', ['idnumber' => trim($idnumber)]);
}

function add_user_to_cohort_if_needed($user, $cohort, $dryrun) {
    global $DB;
    if ($DB->record_exists('cohort_members', ['cohortid' => $cohort->id, 'userid' => $user->id])) {
        msg("Cohort member exists: {$user->username} -> {$cohort->idnumber}");
        return;
    }
    if ($dryrun) {
        msg("[dry-run] Add cohort member: {$user->username} -> {$cohort->idnumber}");
        return;
    }
    cohort_add_member($cohort->id, $user->id);
    msg("Added cohort member: {$user->username} -> {$cohort->idnumber}");
}

function remove_user_from_cohort_if_present($user, $cohort, $dryrun) {
    global $DB;
    if (!$DB->record_exists('cohort_members', ['cohortid' => $cohort->id, 'userid' => $user->id])) {
        msg("Old cohort member not present: {$user->username} -> {$cohort->idnumber}");
        return;
    }
    if ($dryrun) {
        msg("[dry-run] Remove old cohort member: {$user->username} -> {$cohort->idnumber}");
        return;
    }
    cohort_remove_member($cohort->id, $user->id);
    msg("Removed old cohort member: {$user->username} -> {$cohort->idnumber}");
}

function set_profile_value(&$user, $shortname, $value) {
    global $DB;
    $field = $DB->get_record('user_info_field', ['shortname' => $shortname]);
    if (!$field) {
        msg("Profile field missing, skipped: $shortname");
        return;
    }
    if ($field->datatype === 'datetime' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        $value = strtotime($value . ' 00:00:00');
    }
    $prop = 'profile_field_' . $shortname;
    $user->$prop = $value;
}

function update_student_profile_for_promotion($user, $row, $dryrun) {
    $u = clone($user);
    set_profile_value($u, 'previous_academic_year', $row['from_academic_year'] ?? '');
    set_profile_value($u, 'previous_grade_code', $row['from_grade_code'] ?? '');
    set_profile_value($u, 'previous_stream_code', $row['from_stream_code'] ?? '');
    set_profile_value($u, 'previous_division_code', $row['from_division_code'] ?? '');
    set_profile_value($u, 'current_academic_year', $row['to_academic_year'] ?? '');
    set_profile_value($u, 'current_board_code', $row['to_board_code'] ?? '');
    set_profile_value($u, 'current_school_code', $row['to_school_code'] ?? '');
    set_profile_value($u, 'current_medium_code', $row['to_medium_code'] ?? '');
    set_profile_value($u, 'current_grade_code', $row['to_grade_code'] ?? '');
    set_profile_value($u, 'current_stream_code', $row['to_stream_code'] ?? '');
    set_profile_value($u, 'current_division_code', $row['to_division_code'] ?? '');
    set_profile_value($u, 'student_status', $row['result_status'] ?? ($row['action'] ?? 'Promoted'));
    set_profile_value($u, 'last_promotion_date', $row['effective_date'] ?? date('Y-m-d'));
    set_profile_value($u, 'last_promotion_result', $row['result_status'] ?? ($row['action'] ?? 'Promoted'));

    if ($dryrun) {
        msg("[dry-run] Update promotion profile fields for {$user->username}");
        return;
    }
    profile_save_data($u);
    msg("Updated promotion profile fields for {$user->username}");
}

function append_log($dir, $row, $status, $message, $dryrun) {
    $file = $dir . DIRECTORY_SEPARATOR . 'promotion_run_log.csv';
    $exists = file_exists($file);
    $handle = fopen($file, 'a');
    if (!$exists) {
        fputcsv($handle, ['run_time', 'mode', 'username', 'action', 'from_cohort_code', 'to_cohort_code', 'status', 'message']);
    }
    fputcsv($handle, [date('Y-m-d H:i:s'), $dryrun ? 'dry-run' : 'execute', $row['username'] ?? '', $row['action'] ?? '', $row['from_cohort_code'] ?? '', $row['to_cohort_code'] ?? '', $status, $message]);
    fclose($handle);
}

msg('Academic year promotion importer starting.');
msg($dryrun ? 'Mode: DRY RUN' : 'Mode: EXECUTE');

$rows = read_csv_file($csvdir, $csvfile);
$processed = 0;
$warnings = 0;

foreach ($rows as $row) {
    $processed++;
    $action = strtoupper(trim($row['action'] ?? 'PROMOTE'));
    if ($action === '' || $action === 'SKIP') {
        append_log($csvdir, $row, 'SKIP', 'Skipped by action.', $dryrun);
        continue;
    }

    $username = $row['username'] ?? '';
    $user = get_user_by_username($username);
    if (!$user) {
        $warnings++;
        $message = "User not found: $username";
        msg("WARNING: $message");
        append_log($csvdir, $row, 'WARNING', $message, $dryrun);
        continue;
    }

    $targetneeded = !in_array($action, ['ALUMNI', 'TRANSFER_OUT', 'LEFT', 'SUSPEND'], true);
    $tocohort = get_cohort_by_idnumber($row['to_cohort_code'] ?? '');
    if ($targetneeded && !$tocohort) {
        $warnings++;
        $message = "Target cohort not found for {$user->username}: " . ($row['to_cohort_code'] ?? '');
        msg("WARNING: $message");
        append_log($csvdir, $row, 'WARNING', $message, $dryrun);
        if (!$allowmissingto) {
            continue;
        }
    }

    if ($tocohort) {
        add_user_to_cohort_if_needed($user, $tocohort, $dryrun);
    }

    $rowremoveold = truthy($row['remove_from_old_cohort'] ?? '', false);
    if ($globalremoveold || $rowremoveold) {
        $oldcohort = get_cohort_by_idnumber($row['from_cohort_code'] ?? '');
        if ($oldcohort) {
            remove_user_from_cohort_if_present($user, $oldcohort, $dryrun);
        } else {
            $warnings++;
            msg("WARNING: Old cohort not found for {$user->username}: " . ($row['from_cohort_code'] ?? ''));
        }
    }

    $rowupdateprofile = truthy($row['update_profile_fields'] ?? '', true);
    if ($globalupdateprofile && $rowupdateprofile) {
        update_student_profile_for_promotion($user, $row, $dryrun);
    }

    append_log($csvdir, $row, 'OK', 'Processed.', $dryrun);
}

msg("Promotion import completed. Rows processed: $processed. Warnings: $warnings.");
msg("Review promotion_run_log.csv and spot-check Moodle users/courses/cohorts.");
