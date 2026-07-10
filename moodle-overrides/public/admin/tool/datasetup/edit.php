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
 * School setup create/edit page.
 *
 * @package    tool_datasetup
 * @copyright  2026 Hardik Chauhan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

use tool_datasetup\form\school_setup_form;
use tool_datasetup\local\repository;

$id = optional_param('id', 0, PARAM_INT);
$url = new moodle_url('/admin/tool/datasetup/edit.php', $id ? ['id' => $id] : []);
$listurl = new moodle_url('/admin/tool/datasetup/index.php');

admin_externalpage_setup('tool_datasetup');

if (!is_siteadmin()) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('siteadminsonly', 'tool_datasetup'));
}

$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string($id ? 'editingschool' : 'newschool', 'tool_datasetup'));
$PAGE->set_heading(get_string('pluginname', 'tool_datasetup'));
$PAGE->requires->css('/admin/tool/datasetup/styles.css');

$record = null;
if ($id) {
    $record = repository::get($id);
}

$mform = new school_setup_form($url, ['id' => $id]);

if ($record) {
    $mform->set_data($record);
}

if ($mform->is_cancelled()) {
    redirect($listurl);
}

if ($data = $mform->get_data()) {
    if ($id) {
        repository::update($id, $data);
    } else {
        repository::create($data);
    }

    redirect(
        $listurl,
        get_string('schoolsaved', 'tool_datasetup'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo html_writer::start_div('tool-datasetup tool-datasetup-edit');
echo html_writer::tag('h2', get_string($id ? 'editingschool' : 'newschool', 'tool_datasetup'));
echo html_writer::start_div('tool-datasetup-form-card');
$mform->display();
echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();
