<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Delete a module row.
 *
 * @package    tool_datasetup
 * @copyright  2026 Hardik Chauhan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use tool_datasetup\local\module_repository;

admin_externalpage_setup('tool_datasetup');

if (!is_siteadmin()) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('siteadminsonly', 'tool_datasetup'));
}

$sheet = required_param('sheet', PARAM_RAW_TRIMMED);
$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$module = module_repository::get_module_by_sheet($sheet);
$record = module_repository::get_record($id);
$moduleurl = new moodle_url('/admin/tool/datasetup/module.php', ['sheet' => $sheet]);

$PAGE->set_url(new moodle_url('/admin/tool/datasetup/delete_record.php', ['sheet' => $sheet, 'id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('delete'));
$PAGE->set_heading(get_string('pluginname', 'tool_datasetup'));

if ($confirm && confirm_sesskey()) {
    module_repository::delete_record($record->id);
    redirect($moduleurl, get_string('rowdeleted', 'tool_datasetup'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->confirm(
    get_string('confirmdeleterow', 'tool_datasetup', s($record->row_key)),
    new moodle_url('/admin/tool/datasetup/delete_record.php', [
        'sheet' => $sheet,
        'id' => $id,
        'confirm' => 1,
        'sesskey' => sesskey(),
    ]),
    $moduleurl
);
echo $OUTPUT->footer();
