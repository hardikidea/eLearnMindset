<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Create or edit a module row.
 *
 * @package    tool_datasetup
 * @copyright  2026 Hardik Chauhan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

use tool_datasetup\form\module_record_form;
use tool_datasetup\local\module_repository;
use tool_datasetup\output\column_contract;

admin_externalpage_setup('tool_datasetup');

if (!is_siteadmin()) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('siteadminsonly', 'tool_datasetup'));
}

$sheet = required_param('sheet', PARAM_RAW_TRIMMED);
$id = optional_param('id', 0, PARAM_INT);
$module = module_repository::get_module_by_sheet($sheet);
$url = new moodle_url('/admin/tool/datasetup/record.php', $id ? ['sheet' => $sheet, 'id' => $id] : ['sheet' => $sheet]);
$moduleurl = new moodle_url('/admin/tool/datasetup/module.php', ['sheet' => $sheet]);

$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string($id ? 'editrow' : 'addrow', 'tool_datasetup'));
$PAGE->set_heading(get_string('pluginname', 'tool_datasetup'));
$PAGE->requires->css('/admin/tool/datasetup/styles.css');

$mform = new module_record_form($url, ['module' => $module]);

if ($id) {
    $record = module_repository::get_record($id);
    $row = json_decode($record->row_data, true);
    $mform->set_data(module_record_form::row_to_form_data($module, is_array($row) ? $row : [], $id));
}

if ($mform->is_cancelled()) {
    redirect($moduleurl);
}

if ($data = $mform->get_data()) {
    $row = module_record_form::form_data_to_row($module, $data);
    if ($id) {
        module_repository::update_record($module, $id, $row);
    } else {
        module_repository::create_record($module, $row);
    }

    redirect($moduleurl, get_string('rowsaved', 'tool_datasetup'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo html_writer::start_div('tool-datasetup tool-datasetup-edit');
echo html_writer::start_div('tool-datasetup-breadcrumbbar');
echo html_writer::link(new moodle_url('/admin/tool/datasetup/index.php'), get_string('coremodules', 'tool_datasetup'));
echo html_writer::span('/');
echo html_writer::link($moduleurl, s($module->title));
echo html_writer::span('/');
echo html_writer::span(get_string($id ? 'editrow' : 'addrow', 'tool_datasetup'));
echo html_writer::end_div();
echo html_writer::start_div('tool-datasetup-page-titlebar');
echo html_writer::tag('h2', get_string($id ? 'editrow' : 'addrow', 'tool_datasetup') . ': ' . s($module->title));
echo column_contract::render($module);
echo html_writer::end_div();
echo html_writer::start_div('tool-datasetup-form-card');
$mform->display();
echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();
