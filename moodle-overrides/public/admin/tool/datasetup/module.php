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
 * Per-sheet setup module page.
 *
 * @package    tool_datasetup
 * @copyright  2026 Hardik Chauhan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use tool_datasetup\local\module_repository;
use tool_datasetup\local\schema;
use tool_datasetup\output\column_contract;

admin_externalpage_setup('tool_datasetup');

if (!is_siteadmin()) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('siteadminsonly', 'tool_datasetup'));
}

$sheet = required_param('sheet', PARAM_RAW_TRIMMED);
$module = module_repository::get_module_by_sheet($sheet);
$filters = [
    'q' => optional_param('q', '', PARAM_RAW_TRIMMED),
    'validation_state' => optional_param('validation_state', '', PARAM_ALPHA),
];
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = min(100, max(10, optional_param('perpage', 20, PARAM_INT)));
$sort = optional_param('sort', 'row_number', PARAM_ALPHAEXT);
$dir = strtolower(optional_param('dir', 'asc', PARAM_ALPHA));
$dir = $dir === 'desc' ? 'desc' : 'asc';

$baseurl = new moodle_url('/admin/tool/datasetup/module.php', tool_datasetup_module_params(
    $filters + ['sheet' => $sheet, 'perpage' => $perpage, 'sort' => $sort, 'dir' => $dir]
));

$PAGE->set_url($baseurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title($module->title);
$PAGE->set_heading(get_string('pluginname', 'tool_datasetup'));
$PAGE->requires->css('/admin/tool/datasetup/styles.css');

[$total, $records] = module_repository::search_records($module->id, $filters, $sort, $dir, $page, $perpage);
$columns = schema::columns($module);
$previewcolumns = array_slice($columns, 0, 6);

echo $OUTPUT->header();
echo html_writer::start_div('tool-datasetup');

echo html_writer::start_div('tool-datasetup-breadcrumbbar');
echo html_writer::link(new moodle_url('/admin/tool/datasetup/index.php'), get_string('coremodules', 'tool_datasetup'));
echo html_writer::span('/');
echo html_writer::span(s($module->title));
echo html_writer::end_div();

echo html_writer::start_div('tool-datasetup-hero tool-datasetup-hero-compact');
echo html_writer::start_div('tool-datasetup-hero-content');
echo html_writer::tag('span', s($module->sheet_name), ['class' => 'tool-datasetup-kicker']);
echo html_writer::tag('h2', s($module->title));
echo html_writer::tag('p', s($module->purpose));
echo html_writer::start_div('tool-datasetup-chips');
echo html_writer::tag('span', get_string('columnsx', 'tool_datasetup', $module->column_count));
echo html_writer::tag('span', get_string('recordsx', 'tool_datasetup', $total));
if ((int) $module->standard_prefilled === 1) {
    echo html_writer::tag('span', get_string('standarddefault', 'tool_datasetup'));
}
if (!empty($module->ordered_csv)) {
    echo html_writer::tag('span', s($module->ordered_csv));
}
echo html_writer::end_div();
if ((int) $module->standard_prefilled === 1) {
    echo html_writer::tag('p', get_string('standarddefaulthelp', 'tool_datasetup'), [
        'class' => 'tool-datasetup-helptext',
    ]);
}
echo html_writer::end_div();
echo html_writer::start_div('tool-datasetup-hero-actions');
echo html_writer::link(new moodle_url('/admin/tool/datasetup/record.php', ['sheet' => $sheet]), get_string('addrow', 'tool_datasetup'), [
    'class' => 'btn btn-primary',
]);
echo html_writer::link(new moodle_url('/admin/tool/datasetup/import.php', ['sheet' => $sheet]), get_string('importcsv', 'tool_datasetup'), [
    'class' => 'btn btn-outline-secondary',
]);
echo html_writer::link(new moodle_url('/admin/tool/datasetup/export.php', ['sheet' => $sheet]), get_string('exportcsv', 'tool_datasetup'), [
    'class' => 'btn btn-outline-secondary',
]);
echo column_contract::render($module);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => new moodle_url('/admin/tool/datasetup/module.php'),
    'class' => 'tool-datasetup-filter',
]);
echo html_writer::start_div('tool-datasetup-filter-grid tool-datasetup-filter-grid-records');
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sheet', 'value' => s($sheet)]);
echo html_writer::empty_tag('input', [
    'type' => 'search',
    'name' => 'q',
    'value' => s($filters['q']),
    'class' => 'form-control',
    'placeholder' => get_string('searchrecords', 'tool_datasetup'),
]);
echo html_writer::select([
    '' => get_string('allvalidationstates', 'tool_datasetup'),
    'valid' => get_string('valid', 'tool_datasetup'),
    'invalid' => get_string('invalid', 'tool_datasetup'),
], 'validation_state', $filters['validation_state'], false, ['class' => 'custom-select form-select']);
echo html_writer::select([10 => 10, 20 => 20, 50 => 50, 100 => 100], 'perpage', $perpage, false, [
    'class' => 'custom-select form-select',
    'aria-label' => get_string('rowsperpage', 'tool_datasetup'),
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sort', 'value' => s($sort)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'dir', 'value' => s($dir)]);
echo html_writer::tag('button', get_string('filter', 'tool_datasetup'), ['type' => 'submit', 'class' => 'btn btn-secondary']);
echo html_writer::link(new moodle_url('/admin/tool/datasetup/module.php', ['sheet' => $sheet]), get_string('clearfilters', 'tool_datasetup'), [
    'class' => 'btn btn-link',
]);
echo html_writer::end_div();
echo html_writer::end_tag('form');

echo html_writer::start_div('tool-datasetup-tablewrap');
echo html_writer::start_tag('table', ['class' => 'generaltable table table-hover tool-datasetup-table']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', tool_datasetup_record_sort_link('row_number', '#', $sheet, $filters, $sort, $dir, $perpage));
echo html_writer::tag('th', tool_datasetup_record_sort_link('row_key', get_string('rowkey', 'tool_datasetup'), $sheet, $filters, $sort, $dir, $perpage));
foreach ($previewcolumns as $column) {
    echo html_writer::tag('th', s($column['name']));
}
echo html_writer::tag('th', tool_datasetup_record_sort_link('validation_state', get_string('validation', 'tool_datasetup'), $sheet, $filters, $sort, $dir, $perpage));
echo html_writer::tag('th', get_string('actions', 'tool_datasetup'), ['class' => 'tool-datasetup-actions']);
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

if (!$records) {
    echo html_writer::tag('tr',
        html_writer::tag('td', get_string('emptyrecords', 'tool_datasetup'), [
            'colspan' => count($previewcolumns) + 4,
            'class' => 'text-center',
        ])
    );
} else {
    foreach ($records as $record) {
        $row = json_decode($record->row_data, true);
        $row = is_array($row) ? $row : [];
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', (string) $record->row_number);
        echo html_writer::tag('td', s($record->row_key));
        foreach ($previewcolumns as $column) {
            $value = (string) ($row[$column['name']] ?? '');
            echo html_writer::tag('td', s(core_text::substr($value, 0, 80)));
        }
        echo html_writer::tag('td', tool_datasetup_validation_badge($record->validation_state));
        echo html_writer::tag('td', tool_datasetup_record_actions($record, $sheet), ['class' => 'tool-datasetup-actions']);
        echo html_writer::end_tag('tr');
    }
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');
echo html_writer::end_div();

if ($total > $perpage) {
    echo $OUTPUT->paging_bar($total, $page, $perpage, $baseurl);
}

echo html_writer::end_div();
echo $OUTPUT->footer();

/**
 * Cleans module URL params.
 *
 * @param array $params Params.
 * @return array
 */
function tool_datasetup_module_params(array $params): array {
    return array_filter($params, static function($value): bool {
        return $value !== '' && $value !== null;
    });
}

/**
 * Sort link for record table.
 *
 * @param string $field Field.
 * @param string $label Label.
 * @param string $sheet Sheet.
 * @param array $filters Filters.
 * @param string $sort Current sort.
 * @param string $dir Current direction.
 * @param int $perpage Rows.
 * @return string
 */
function tool_datasetup_record_sort_link(
    string $field,
    string $label,
    string $sheet,
    array $filters,
    string $sort,
    string $dir,
    int $perpage
): string {
    $nextdir = ($sort === $field && $dir === 'asc') ? 'desc' : 'asc';
    $params = tool_datasetup_module_params($filters + [
        'sheet' => $sheet,
        'sort' => $field,
        'dir' => $nextdir,
        'perpage' => $perpage,
    ]);
    $indicator = $sort === $field ? html_writer::tag('span', $dir === 'asc' ? '&uarr;' : '&darr;', [
        'class' => 'tool-datasetup-sort-indicator',
        'aria-hidden' => 'true',
    ]) : '';

    return html_writer::link(new moodle_url('/admin/tool/datasetup/module.php', $params), s($label) . $indicator);
}

/**
 * Validation badge.
 *
 * @param string $state State.
 * @return string
 */
function tool_datasetup_validation_badge(string $state): string {
    $valid = $state === 'valid';

    return html_writer::tag('span', get_string($valid ? 'valid' : 'invalid', 'tool_datasetup'), [
        'class' => 'tool-datasetup-status ' . ($valid ? 'tool-datasetup-status-active' : 'tool-datasetup-status-inactive'),
    ]);
}

/**
 * Row action links.
 *
 * @param \stdClass $record Record.
 * @param string $sheet Sheet.
 * @return string
 */
function tool_datasetup_record_actions(\stdClass $record, string $sheet): string {
    global $OUTPUT;

    return html_writer::link(
        new moodle_url('/admin/tool/datasetup/record.php', ['sheet' => $sheet, 'id' => $record->id]),
        $OUTPUT->pix_icon('t/edit', get_string('edit')),
        ['class' => 'tool-datasetup-iconlink']
    ) . html_writer::link(
        new moodle_url('/admin/tool/datasetup/delete_record.php', ['sheet' => $sheet, 'id' => $record->id]),
        $OUTPUT->pix_icon('t/delete', get_string('delete')),
        ['class' => 'tool-datasetup-iconlink tool-datasetup-delete']
    );
}
