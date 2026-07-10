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

namespace tool_datasetup\form;

defined('MOODLE_INTERNAL') || die();

use tool_datasetup\local\module_repository;
use tool_datasetup\local\schema;

/**
 * Dynamic module row form.
 *
 * @package    tool_datasetup
 * @copyright  2026 Hardik Chauhan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module_record_form extends \moodleform {
    /**
     * Defines form controls from module columns.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $module = $this->_customdata['module'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'sheet', $module->sheet_name);
        $mform->setType('sheet', PARAM_RAW_TRIMMED);

        $mform->addElement('html', \html_writer::start_div('tool-datasetup-dynamic-grid'));

        foreach (schema::columns($module) as $column) {
            $field = self::field_name((int) $column['position']);
            $label = self::field_label($column);
            $attributes = [
                'maxlength' => 2048,
                'placeholder' => (string) ($column['example'] ?? ''),
            ];

            $options = module_repository::options_for_column((string) $column['name'], $module);
            if ($options) {
                $options = ['' => get_string('selectavalue', 'tool_datasetup')] + $options;
                $mform->addElement('select', $field, $label, $options);
            } else if (self::use_date_selector($column)) {
                $mform->addElement('date_selector', $field, $label, [
                    'optional' => empty($column['required']),
                ]);
            } else if (self::use_textarea($column)) {
                $mform->addElement('textarea', $field, $label, ['rows' => 3, 'class' => 'tool-datasetup-textarea']);
            } else {
                $mform->addElement('text', $field, $label, $attributes);
            }

            $mform->setType($field, self::field_param_type($column, $options));

            if (!empty($column['required'])) {
                $mform->addRule($field, null, 'required', null, 'client');
            }
        }

        $mform->addElement('html', \html_writer::end_div());
        $this->add_action_buttons();
    }

    /**
     * Server-side validation.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $module = $this->_customdata['module'];
        $row = self::form_data_to_row($module, (object) $data);
        $validation = schema::validate_row($module, $row);
        $validation += module_repository::validate_reference_values($module, $row);

        foreach ($validation as $columnname => $message) {
            $field = self::field_name_by_column($module, $columnname);
            if ($field !== null) {
                $errors[$field] = $message;
            }
        }

        return $errors;
    }

    /**
     * Converts row data to form data.
     *
     * @param \stdClass $module Module record.
     * @param array $row Row data.
     * @param int $id Record id.
     * @return \stdClass
     */
    public static function row_to_form_data(\stdClass $module, array $row, int $id = 0): \stdClass {
        $data = new \stdClass();
        $data->id = $id;
        $data->sheet = $module->sheet_name;

        foreach (schema::columns($module) as $column) {
            $name = (string) $column['name'];
            $field = self::field_name((int) $column['position']);
            $value = $row[$name] ?? '';
            $data->{$field} = self::use_date_selector($column) ? self::csv_date_to_timestamp((string) $value) : $value;
        }

        return $data;
    }

    /**
     * Converts submitted form values to row data.
     *
     * @param \stdClass $module Module record.
     * @param \stdClass $data Form data.
     * @return array
     */
    public static function form_data_to_row(\stdClass $module, \stdClass $data): array {
        $row = [];

        foreach (schema::columns($module) as $column) {
            $field = self::field_name((int) $column['position']);
            $value = $data->{$field} ?? '';
            $row[(string) $column['name']] = self::use_date_selector($column) ? self::timestamp_to_csv_date($value) : $value;
        }

        return $row;
    }

    /**
     * Returns generated form field name.
     *
     * @param int $position Column position.
     * @return string
     */
    private static function field_name(int $position): string {
        return 'c_' . $position;
    }

    /**
     * Returns generated form field name for a column.
     *
     * @param \stdClass $module Module record.
     * @param string $columnname Column name.
     * @return string|null
     */
    private static function field_name_by_column(\stdClass $module, string $columnname): ?string {
        foreach (schema::columns($module) as $column) {
            if ((string) $column['name'] === $columnname) {
                return self::field_name((int) $column['position']);
            }
        }

        return null;
    }

    /**
     * Returns field label with CSV name and requirement badge.
     *
     * @param array $column Column metadata.
     * @return string
     */
    private static function field_label(array $column): string {
        $label = (string) ($column['label'] ?? $column['name']);
        $name = (string) $column['name'];
        $required = !empty($column['required']) ? ' *' : '';

        return $label . $required . ' (' . $name . ')';
    }

    /**
     * Returns whether the field needs textarea.
     *
     * @param array $column Column metadata.
     * @return bool
     */
    private static function use_textarea(array $column): bool {
        $name = strtolower((string) $column['name']);

        return str_contains($name, 'description') ||
            str_contains($name, 'notes') ||
            str_contains($name, 'address') ||
            str_contains($name, 'summary') ||
            str_contains($name, 'instruction');
    }

    /**
     * Returns whether a column should render as a Moodle date selector.
     *
     * @param array $column Column metadata.
     * @return bool
     */
    private static function use_date_selector(array $column): bool {
        $name = strtolower((string) $column['name']);
        $pattern = strtolower((string) ($column['pattern'] ?? ''));

        return str_contains($pattern, 'yyyy-mm-dd') || str_ends_with($name, '_date');
    }

    /**
     * Returns the field param type for a dynamic field.
     *
     * @param array $column Column metadata.
     * @param array $options Select options, if any.
     * @return string
     */
    private static function field_param_type(array $column, array $options): string {
        if ($options) {
            return PARAM_RAW_TRIMMED;
        }

        $name = strtolower((string) $column['name']);
        $pattern = strtolower((string) ($column['pattern'] ?? ''));

        if (self::use_date_selector($column)) {
            return PARAM_INT;
        }
        if (str_contains($name, 'email')) {
            return PARAM_EMAIL;
        }
        if (str_contains($name, 'url') || str_contains($name, 'website')) {
            return PARAM_URL;
        }
        if (str_contains($pattern, 'whole number')) {
            return PARAM_INT;
        }
        if (str_contains($pattern, '0-100')) {
            return PARAM_FLOAT;
        }

        return PARAM_RAW_TRIMMED;
    }

    /**
     * Converts a CSV date value to a timestamp for Moodle form controls.
     *
     * @param string $value Date string.
     * @return int
     */
    private static function csv_date_to_timestamp(string $value): int {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', trim($value), $matches)) {
            return 0;
        }

        return make_timestamp((int) $matches[1], (int) $matches[2], (int) $matches[3], 0, 0, 0);
    }

    /**
     * Converts a Moodle date selector value to YYYY-MM-DD.
     *
     * @param mixed $value Form value.
     * @return string
     */
    private static function timestamp_to_csv_date($value): string {
        $timestamp = (int) $value;
        if ($timestamp <= 0) {
            return '';
        }

        return date('Y-m-d', $timestamp);
    }
}
