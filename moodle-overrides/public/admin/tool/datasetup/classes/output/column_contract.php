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

namespace tool_datasetup\output;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use tool_datasetup\local\schema;

/**
 * Column contract popup renderer.
 *
 * @package    tool_datasetup
 * @copyright  2026 Hardik Chauhan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class column_contract {
    /**
     * Renders a button and popup dialog for a module column contract.
     *
     * @param \stdClass $module Module record.
     * @param string $buttonclass Button CSS class.
     * @return string
     */
    public static function render(\stdClass $module, string $buttonclass = 'btn btn-outline-secondary'): string {
        self::require_js();

        $dialogid = 'tool-datasetup-contract-' . clean_param($module->sheet_name, PARAM_ALPHANUMEXT);
        if ($dialogid === 'tool-datasetup-contract-') {
            $dialogid .= substr(sha1($module->sheet_name), 0, 10);
        }
        $titleid = $dialogid . '-title';
        $columns = schema::columns($module);

        $buttoncontent = html_writer::span('i', 'tool-datasetup-contract-icon', ['aria-hidden' => 'true']) .
            html_writer::span(get_string('viewcolumncontract', 'tool_datasetup'));
        $html = html_writer::tag('button', $buttoncontent, [
            'type' => 'button',
            'class' => trim($buttonclass . ' tool-datasetup-contract-trigger'),
            'data-tool-datasetup-contract-open' => $dialogid,
            'aria-haspopup' => 'dialog',
            'aria-controls' => $dialogid,
        ]);

        $html .= html_writer::start_tag('dialog', [
            'id' => $dialogid,
            'class' => 'tool-datasetup-contract-dialog',
            'aria-labelledby' => $titleid,
        ]);
        $html .= html_writer::start_div('tool-datasetup-contract-shell');
        $html .= html_writer::start_div('tool-datasetup-contract-header');
        $html .= html_writer::start_div();
        $html .= html_writer::tag('span', s($module->sheet_name), ['class' => 'tool-datasetup-kicker']);
        $html .= html_writer::tag('h3', get_string('columncontractfor', 'tool_datasetup', format_string($module->title)), [
            'id' => $titleid,
        ]);
        $html .= html_writer::tag('p', s($module->purpose), ['class' => 'tool-datasetup-helptext']);
        $html .= html_writer::end_div();
        $html .= html_writer::tag('button', '&times;', [
            'type' => 'button',
            'class' => 'tool-datasetup-contract-close',
            'data-tool-datasetup-contract-close' => '1',
            'aria-label' => get_string('closebuttontitle'),
        ]);
        $html .= html_writer::end_div();

        $html .= html_writer::start_div('tool-datasetup-contract-meta');
        $html .= html_writer::tag('span', get_string('columnsx', 'tool_datasetup', count($columns)));
        if ((int) $module->standard_prefilled === 1) {
            $html .= html_writer::tag('span', get_string('standarddefault', 'tool_datasetup'));
        }
        if (!empty($module->ordered_csv)) {
            $html .= html_writer::tag('span', s($module->ordered_csv));
        }
        if (!empty($module->source_csv)) {
            $html .= html_writer::tag('span', s($module->source_csv));
        }
        $html .= html_writer::end_div();

        $html .= html_writer::start_div('tool-datasetup-contract-tablewrap');
        $html .= html_writer::start_tag('table', ['class' => 'generaltable table tool-datasetup-contract-table']);
        $html .= html_writer::start_tag('thead');
        $html .= html_writer::tag('tr',
            html_writer::tag('th', get_string('column', 'tool_datasetup')) .
            html_writer::tag('th', get_string('required', 'tool_datasetup')) .
            html_writer::tag('th', get_string('purpose', 'tool_datasetup')) .
            html_writer::tag('th', get_string('formulaorpattern', 'tool_datasetup')) .
            html_writer::tag('th', get_string('example', 'tool_datasetup'))
        );
        $html .= html_writer::end_tag('thead');
        $html .= html_writer::start_tag('tbody');

        foreach ($columns as $column) {
            $required = !empty($column['required']);
            $badge = html_writer::tag('span', get_string($required ? 'mandatory' : 'optional', 'tool_datasetup'), [
                'class' => 'tool-datasetup-badge ' . ($required ? 'tool-datasetup-badge-required' : 'tool-datasetup-badge-reference'),
            ]);
            $html .= html_writer::tag('tr',
                html_writer::tag('td',
                    html_writer::tag('strong', s($column['name'])) .
                    html_writer::tag('small', s($column['label'] ?? ''), ['class' => 'tool-datasetup-contract-label'])
                ) .
                html_writer::tag('td', $badge) .
                html_writer::tag('td', s($column['description'] ?? '')) .
                html_writer::tag('td', s($column['pattern'] ?? '')) .
                html_writer::tag('td', s($column['example'] ?? ''))
            );
        }

        $html .= html_writer::end_tag('tbody');
        $html .= html_writer::end_tag('table');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_tag('dialog');

        return $html;
    }

    /**
     * Adds the dialog behaviour once per page.
     *
     * @return void
     */
    private static function require_js(): void {
        global $PAGE;

        static $loaded = false;
        if ($loaded) {
            return;
        }

        $PAGE->requires->js_init_code(<<<'JS'
document.addEventListener('click', function(event) {
    var opener = event.target.closest('[data-tool-datasetup-contract-open]');
    if (opener) {
        var dialog = document.getElementById(opener.getAttribute('data-tool-datasetup-contract-open'));
        if (dialog && typeof dialog.showModal === 'function') {
            dialog.showModal();
        }
        event.preventDefault();
        return;
    }

    var closer = event.target.closest('[data-tool-datasetup-contract-close]');
    if (closer) {
        var openDialog = closer.closest('dialog');
        if (openDialog) {
            openDialog.close();
        }
        event.preventDefault();
        return;
    }

    if (event.target.matches('dialog.tool-datasetup-contract-dialog')) {
        event.target.close();
    }
});
JS);
        $loaded = true;
    }
}
