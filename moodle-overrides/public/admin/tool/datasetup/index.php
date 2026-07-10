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
 * Core setup module dashboard.
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

$filters = [
    'q' => optional_param('q', '', PARAM_RAW_TRIMMED),
    'group' => optional_param('group', '', PARAM_RAW_TRIMMED),
    'required' => optional_param('required', -1, PARAM_INT),
];

$PAGE->set_url(new moodle_url('/admin/tool/datasetup/index.php', tool_datasetup_dashboard_params($filters)));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'tool_datasetup'));
$PAGE->set_heading(get_string('pluginname', 'tool_datasetup'));
$PAGE->requires->css('/admin/tool/datasetup/styles.css');

$summary = module_repository::summary();
$groups = module_repository::groups();
$modules = module_repository::modules($filters);

echo $OUTPUT->header();
echo html_writer::start_div('tool-datasetup');

echo html_writer::start_div('tool-datasetup-hero tool-datasetup-hero-compact');
echo html_writer::start_div('tool-datasetup-hero-content');
echo html_writer::tag('span', get_string('workbookdriven', 'tool_datasetup'), ['class' => 'tool-datasetup-kicker']);
echo html_writer::tag('h2', get_string('manageheading', 'tool_datasetup'));
echo html_writer::tag('p', get_string('moduleintro', 'tool_datasetup'));
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('tool-datasetup-summary');
echo tool_datasetup_summary_card(get_string('summarymodules', 'tool_datasetup'), $summary['modules']);
echo tool_datasetup_summary_card(get_string('summaryrequiredmodules', 'tool_datasetup'), $summary['required']);
echo tool_datasetup_summary_card(get_string('summaryrecords', 'tool_datasetup'), $summary['records']);
echo tool_datasetup_summary_card(get_string('summarymodulegroups', 'tool_datasetup'), $summary['groups']);
echo html_writer::end_div();

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => new moodle_url('/admin/tool/datasetup/index.php'),
    'class' => 'tool-datasetup-filter',
]);
echo html_writer::start_div('tool-datasetup-filter-grid tool-datasetup-filter-grid-dashboard');
echo html_writer::empty_tag('input', [
    'type' => 'search',
    'name' => 'q',
    'value' => s($filters['q']),
    'class' => 'form-control',
    'placeholder' => get_string('searchmodules', 'tool_datasetup'),
]);

$groupoptions = ['' => get_string('allgroups', 'tool_datasetup')];
foreach ($groups as $group) {
    $groupoptions[$group] = $group;
}
echo html_writer::select($groupoptions, 'group', $filters['group'], false, [
    'class' => 'custom-select form-select',
    'aria-label' => get_string('modulegroup', 'tool_datasetup'),
]);

echo html_writer::select([
    -1 => get_string('allmoduletypes', 'tool_datasetup'),
    1 => get_string('mandatorymodules', 'tool_datasetup'),
    0 => get_string('referencemodules', 'tool_datasetup'),
], 'required', $filters['required'], false, [
    'class' => 'custom-select form-select',
    'aria-label' => get_string('required', 'tool_datasetup'),
]);
echo html_writer::tag('button', get_string('filter', 'tool_datasetup'), ['type' => 'submit', 'class' => 'btn btn-secondary']);
echo html_writer::link(new moodle_url('/admin/tool/datasetup/index.php'), get_string('clearfilters', 'tool_datasetup'), [
    'class' => 'btn btn-link',
]);
echo html_writer::end_div();
echo html_writer::end_tag('form');

$currentgroup = '';
echo html_writer::start_div('tool-datasetup-module-list');
foreach ($modules as $module) {
    if ($module->module_group !== $currentgroup) {
        if ($currentgroup !== '') {
            echo html_writer::end_div();
        }
        $currentgroup = $module->module_group;
        echo html_writer::tag('h3', s($currentgroup), ['class' => 'tool-datasetup-module-group-title']);
        echo html_writer::start_div('tool-datasetup-module-grid');
    }

    echo tool_datasetup_module_card($module);
}
if ($currentgroup !== '') {
    echo html_writer::end_div();
}
if (!$modules) {
    echo html_writer::div(get_string('nomodules', 'tool_datasetup'), 'tool-datasetup-empty');
}
echo html_writer::end_div();

echo html_writer::end_div();
echo $OUTPUT->footer();

/**
 * Removes empty dashboard params.
 *
 * @param array $params Params.
 * @return array
 */
function tool_datasetup_dashboard_params(array $params): array {
    return array_filter($params, static function($value): bool {
        return $value !== '' && $value !== null && $value !== -1;
    });
}

/**
 * Builds a summary card.
 *
 * @param string $label Label.
 * @param int $value Value.
 * @return string
 */
function tool_datasetup_summary_card(string $label, int $value): string {
    return html_writer::div(
        html_writer::tag('strong', (string) $value) . html_writer::tag('span', s($label)),
        'tool-datasetup-summary-card'
    );
}

/**
 * Builds a module card.
 *
 * @param \stdClass $module Module record.
 * @return string
 */
function tool_datasetup_module_card(\stdClass $module): string {
    $manageurl = new moodle_url('/admin/tool/datasetup/module.php', ['sheet' => $module->sheet_name]);
    $importurl = new moodle_url('/admin/tool/datasetup/import.php', ['sheet' => $module->sheet_name]);
    $exporturl = new moodle_url('/admin/tool/datasetup/export.php', ['sheet' => $module->sheet_name]);
    $templateurl = new moodle_url('/admin/tool/datasetup/export.php', ['sheet' => $module->sheet_name, 'template' => 1]);
    if ((int) $module->standard_prefilled === 1) {
        $badgelabel = get_string('standarddefault', 'tool_datasetup');
        $badgeclass = 'tool-datasetup-badge-standard';
    } else {
        $badgelabel = $module->required ? get_string('mandatory', 'tool_datasetup') : get_string('reference', 'tool_datasetup');
        $badgeclass = $module->required ? 'tool-datasetup-badge-required' : 'tool-datasetup-badge-reference';
    }

    $html = html_writer::start_div('tool-datasetup-module-card');
    $html .= html_writer::start_div('tool-datasetup-module-card-top');
    $html .= html_writer::tag('span', s($module->sheet_name), ['class' => 'tool-datasetup-code']);
    $html .= html_writer::tag('span', $badgelabel, ['class' => 'tool-datasetup-badge ' . $badgeclass]);
    $html .= html_writer::end_div();
    $html .= html_writer::tag('h4', s($module->title));
    $html .= html_writer::tag('p', s($module->purpose), ['class' => 'tool-datasetup-module-purpose']);
    if ((int) $module->standard_prefilled === 1) {
        $html .= html_writer::tag('p', get_string('standarddefaulthelp', 'tool_datasetup'), [
            'class' => 'tool-datasetup-module-purpose',
        ]);
    }
    $html .= html_writer::start_div('tool-datasetup-module-meta');
    $html .= html_writer::tag('span', get_string('columnsx', 'tool_datasetup', $module->column_count));
    $html .= html_writer::tag('span', get_string('recordsx', 'tool_datasetup', $module->recordcount));
    if (!empty($module->ordered_csv)) {
        $html .= html_writer::tag('span', s($module->ordered_csv));
    }
    $html .= html_writer::end_div();
    $html .= html_writer::start_div('tool-datasetup-module-actions');
    $html .= html_writer::link($manageurl, get_string('manage', 'tool_datasetup'), ['class' => 'btn btn-primary btn-sm']);
    $html .= html_writer::link($importurl, get_string('import', 'tool_datasetup'), ['class' => 'btn btn-outline-secondary btn-sm']);
    $html .= html_writer::link($exporturl, get_string('export', 'tool_datasetup'), ['class' => 'btn btn-outline-secondary btn-sm']);
    $html .= html_writer::link($templateurl, get_string('template', 'tool_datasetup'), ['class' => 'btn btn-link btn-sm']);
    $html .= html_writer::end_div();
    $html .= html_writer::end_div();

    return $html;
}
