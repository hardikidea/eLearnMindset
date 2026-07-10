<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Export module CSV data.
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

$sheet = optional_param('sheet', '', PARAM_RAW_TRIMMED);
$template = optional_param('template', 0, PARAM_BOOL);

if ($sheet === '') {
    redirect(new moodle_url('/admin/tool/datasetup/index.php'));
}

$module = module_repository::get_module_by_sheet($sheet);
$csv = module_repository::export_csv($module, $template);
$filename = clean_filename(($template ? 'template_' : '') . ($module->ordered_csv ?: $module->sheet_name . '.csv'));

send_file($csv, $filename, 0, 0, true, true, 'text/csv');
