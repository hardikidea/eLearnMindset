<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * School setup delete confirmation page.
 *
 * @package    tool_datasetup
 * @copyright  2026 Hardik Chauhan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use tool_datasetup\local\repository;

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$listurl = new moodle_url('/admin/tool/datasetup/index.php');

admin_externalpage_setup('tool_datasetup');

if (!is_siteadmin()) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('siteadminsonly', 'tool_datasetup'));
}

$record = repository::get($id);
$PAGE->set_url(new moodle_url('/admin/tool/datasetup/delete.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('delete'));
$PAGE->set_heading(get_string('pluginname', 'tool_datasetup'));

if ($confirm && confirm_sesskey()) {
    if (repository::delete($id)) {
        redirect(
            $listurl,
            get_string('schooldeleted', 'tool_datasetup'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    redirect(
        $listurl,
        get_string('deletefailed', 'tool_datasetup'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

echo $OUTPUT->header();
echo $OUTPUT->confirm(
    get_string('confirmdelete', 'tool_datasetup', format_string($record->school_name)),
    new moodle_url('/admin/tool/datasetup/delete.php', ['id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]),
    $listurl
);
echo $OUTPUT->footer();
