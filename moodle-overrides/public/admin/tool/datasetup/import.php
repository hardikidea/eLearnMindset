<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Import CSV rows for a setup module.
 *
 * @package    tool_datasetup
 * @copyright  2026 Hardik Chauhan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

use tool_datasetup\form\import_form;
use tool_datasetup\local\module_repository;
use tool_datasetup\output\column_contract;

admin_externalpage_setup('tool_datasetup');

if (!is_siteadmin()) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('siteadminsonly', 'tool_datasetup'));
}

$sheet = required_param('sheet', PARAM_RAW_TRIMMED);
$module = module_repository::get_module_by_sheet($sheet);
$url = new moodle_url('/admin/tool/datasetup/import.php', ['sheet' => $sheet]);
$moduleurl = new moodle_url('/admin/tool/datasetup/module.php', ['sheet' => $sheet]);

$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('importcsv', 'tool_datasetup'));
$PAGE->set_heading(get_string('pluginname', 'tool_datasetup'));
$PAGE->requires->css('/admin/tool/datasetup/styles.css');

$mform = new import_form($url, ['sheet' => $sheet]);

if ($mform->is_cancelled()) {
    redirect($moduleurl);
}

if ($data = $mform->get_data()) {
    $content = $mform->get_file_content('importfile');
    $summary = module_repository::import_csv($module, $content ?: '', !empty($data->replace));
    $message = get_string('importcreated', 'tool_datasetup', $summary['created']);

    if (!empty($summary['errors'])) {
        $message .= html_writer::alist(array_map('s', array_slice($summary['errors'], 0, 20)));
        redirect($moduleurl, $message, null, \core\output\notification::NOTIFY_WARNING);
    }

    redirect($moduleurl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo html_writer::start_div('tool-datasetup tool-datasetup-edit');
echo html_writer::start_div('tool-datasetup-breadcrumbbar');
echo html_writer::link(new moodle_url('/admin/tool/datasetup/index.php'), get_string('coremodules', 'tool_datasetup'));
echo html_writer::span('/');
echo html_writer::link($moduleurl, s($module->title));
echo html_writer::span('/');
echo html_writer::span(get_string('importcsv', 'tool_datasetup'));
echo html_writer::end_div();
echo html_writer::start_div('tool-datasetup-page-titlebar');
echo html_writer::tag('h2', get_string('importcsv', 'tool_datasetup') . ': ' . s($module->title));
echo column_contract::render($module);
echo html_writer::end_div();
echo html_writer::tag('p', get_string('importhelp', 'tool_datasetup'), ['class' => 'tool-datasetup-helptext']);
if ((int) $module->standard_prefilled === 1) {
    echo html_writer::tag('p', get_string('standarddefaulthelp', 'tool_datasetup'), ['class' => 'tool-datasetup-helptext']);
}
echo tool_datasetup_import_guidance($module, $sheet);
echo html_writer::start_div('tool-datasetup-form-card');
$mform->display();
echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();

/**
 * Renders CSV import guidance for the selected setup module.
 *
 * @param \stdClass $module Module record.
 * @param string $sheet Sheet name.
 * @return string
 */
function tool_datasetup_import_guidance(\stdClass $module, string $sheet): string {
    $templateurl = new moodle_url('/admin/tool/datasetup/export.php', [
        'sheet' => $sheet,
        'template' => 1,
    ]);
    $exporturl = new moodle_url('/admin/tool/datasetup/export.php', ['sheet' => $sheet]);

    $html = html_writer::start_div('tool-datasetup-import-guide');
    $html .= html_writer::start_div('tool-datasetup-import-guide-header');
    $html .= html_writer::start_div();
    $html .= html_writer::tag('span', get_string('customcontract', 'tool_datasetup'), ['class' => 'tool-datasetup-kicker']);
    $html .= html_writer::tag('h3', get_string('importcontracttitle', 'tool_datasetup'));
    $html .= html_writer::tag('p', get_string('importcontractintro', 'tool_datasetup'), [
        'class' => 'tool-datasetup-helptext',
    ]);
    $html .= html_writer::end_div();
    $html .= html_writer::start_div('tool-datasetup-import-guide-actions');
    $html .= html_writer::link($templateurl, get_string('downloadtemplate', 'tool_datasetup'), [
        'class' => 'btn btn-primary',
    ]);
    $html .= html_writer::link($exporturl, get_string('exportcsv', 'tool_datasetup'), [
        'class' => 'btn btn-outline-secondary',
    ]);
    $html .= html_writer::end_div();
    $html .= html_writer::end_div();

    $html .= html_writer::start_div('tool-datasetup-import-contract-meta');
    $html .= html_writer::tag('span', s($module->sheet_name));
    $html .= html_writer::tag('span', get_string('columnsx', 'tool_datasetup', $module->column_count));
    if (!empty($module->ordered_csv)) {
        $html .= html_writer::tag('span', s($module->ordered_csv));
    }
    if ((int) $module->standard_prefilled === 1) {
        $html .= html_writer::tag('span', get_string('standarddefault', 'tool_datasetup'));
    }
    $html .= html_writer::end_div();

    $rules = [
        get_string('importcontractruleheader', 'tool_datasetup'),
        get_string('importcontractrulerequired', 'tool_datasetup'),
        get_string('importcontractrulelookup', 'tool_datasetup'),
        get_string('importcontractrulereplace', 'tool_datasetup'),
        get_string('importcontractrulegenerated', 'tool_datasetup'),
    ];

    if ((int) $module->standard_prefilled === 1) {
        $rules[] = get_string('importcontractrulestandarddefault', 'tool_datasetup');
    }

    $html .= html_writer::alist($rules, ['class' => 'tool-datasetup-import-rules']);
    $html .= html_writer::tag('p', get_string('importcontractcolumnhelp', 'tool_datasetup'), [
        'class' => 'tool-datasetup-helptext mb-0',
    ]);
    $html .= html_writer::end_div();

    return $html;
}
