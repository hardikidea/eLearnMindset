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

namespace tool_datasetup\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Workbook schema helper.
 *
 * @package    tool_datasetup
 * @copyright  2026 Hardik Chauhan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class schema {
    /** Schema JSON path relative to this class. */
    private const SCHEMA_FILE = '/../../data/sheets.json';

    /** Sheets that are safe standard defaults from the CLI pack. */
    private const STANDARD_PREFILLED_SHEETS = [
        '16_profile_fields',
        '17_custom_roles',
        '18_role_guidelines',
        '26_lookup_values',
        '27_validation_rules',
        '28_source_refs',
        '30_master_template',
        '31_template_sections',
        '32_template_activities',
        '33_template_gradebook',
        '34_grade_band_adjust',
        '35_subject_adjust',
        '36_completion_defaults',
        '38_template_custom_fields',
        '39_template_review',
        '40_certificate_policy',
        '41_report_access',
        '42_test_coverage',
        '43_content_template',
        '44_transition_models',
        '45_promotion_rules',
        '46_rollover_checklist',
        '47_promotion_policy',
        '48_promotion_status',
        '49_promotion_validation',
        '50_student_status',
        '59_archive_policy',
        '61_compatibility',
    ];

    /**
     * Loads the generated workbook schema.
     *
     * @return array
     */
    public static function load(): array {
        static $schema = null;

        if ($schema !== null) {
            return $schema;
        }

        $path = __DIR__ . self::SCHEMA_FILE;
        if (!is_readable($path)) {
            $schema = ['modules' => []];
            return $schema;
        }

        $decoded = json_decode(file_get_contents($path), true);
        $schema = is_array($decoded) ? $decoded : ['modules' => []];

        return $schema;
    }

    /**
     * Seeds or refreshes module metadata from the workbook schema snapshot.
     *
     * @return void
     */
    public static function ensure_seeded(): void {
        global $DB;

        $dbman = $DB->get_manager();
        if (!$dbman->table_exists(module_repository::MODULE_TABLE)) {
            return;
        }

        $now = time();
        foreach (self::load()['modules'] ?? [] as $module) {
            $record = self::module_to_record($module, $now);
            $existing = $DB->get_record(module_repository::MODULE_TABLE, ['sheet_name' => $record->sheet_name]);

            if ($existing) {
                $record->id = $existing->id;
                $record->timecreated = $existing->timecreated;
                $DB->update_record(module_repository::MODULE_TABLE, $record);
            } else {
                $record->timecreated = $now;
                $DB->insert_record(module_repository::MODULE_TABLE, $record);
            }
        }
    }

    /**
     * Converts a module schema entry to a DB record.
     *
     * @param array $module Module schema.
     * @param int $now Timestamp.
     * @return \stdClass
     */
    private static function module_to_record(array $module, int $now): \stdClass {
        $record = new \stdClass();
        $record->sheet_name = (string) ($module['sheet_name'] ?? '');
        $record->title = (string) ($module['title'] ?? $record->sheet_name);
        $record->module_group = (string) ($module['group'] ?? get_string('modulegroupother', 'tool_datasetup'));
        $record->source_csv = (string) ($module['source_csv'] ?? '');
        $record->ordered_csv = (string) ($module['ordered_csv'] ?? '');
        $record->purpose = (string) ($module['purpose'] ?? '');
        $record->required = empty($module['required']) ? 0 : 1;
        $record->standard_prefilled = !empty($module['standard_prefilled']) ||
            self::is_standard_prefilled_sheet($record->sheet_name) ? 1 : 0;
        $record->header_row = (int) ($module['header_row'] ?? 5);
        $record->example_row = (int) ($module['example_row'] ?? 6);
        $record->data_start_row = (int) ($module['data_start_row'] ?? 7);
        $record->column_count = (int) ($module['column_count'] ?? count($module['columns'] ?? []));
        $record->columns_json = json_encode($module['columns'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $record->sort_order = (int) ($module['sort_order'] ?? 0);
        $record->status = empty($module['ui_visible']) ? 0 : 1;
        $record->timemodified = $now;

        return $record;
    }

    /**
     * Returns whether a sheet should be seeded from bundled standard defaults.
     *
     * @param string $sheetname Sheet name.
     * @return bool
     */
    public static function is_standard_prefilled_sheet(string $sheetname): bool {
        return in_array($sheetname, self::STANDARD_PREFILLED_SHEETS, true);
    }

    /**
     * Returns decoded columns for a module DB record.
     *
     * @param \stdClass $module Module record.
     * @return array
     */
    public static function columns(\stdClass $module): array {
        $columns = json_decode($module->columns_json ?? '[]', true);

        return is_array($columns) ? $columns : [];
    }

    /**
     * Normalises a row to the module's column contract.
     *
     * @param \stdClass $module Module record.
     * @param array $row Row values keyed by column name.
     * @return array
     */
    public static function normalise_row(\stdClass $module, array $row): array {
        $normalised = [];

        foreach (self::columns($module) as $column) {
            $name = (string) $column['name'];
            $value = $row[$name] ?? '';
            $normalised[$name] = is_string($value) ? trim($value) : (string) $value;
        }

        return $normalised;
    }

    /**
     * Validates a row against the module schema.
     *
     * @param \stdClass $module Module record.
     * @param array $row Row values keyed by column name.
     * @return array Validation errors keyed by column name.
     */
    public static function validate_row(\stdClass $module, array $row): array {
        $errors = [];
        $row = self::normalise_row($module, $row);

        foreach (self::columns($module) as $column) {
            $name = (string) $column['name'];
            $value = $row[$name] ?? '';
            $pattern = (string) ($column['pattern'] ?? '');
            $required = !empty($column['required']);

            if ($required && $value === '') {
                $errors[$name] = get_string('validationrequired', 'tool_datasetup');
                continue;
            }

            if ($value === '') {
                continue;
            }

            $lowername = strtolower($name);
            $lowerpattern = strtolower($pattern);

            if (str_contains($lowername, 'email') && !validate_email($value)) {
                $errors[$name] = get_string('validationemail', 'tool_datasetup');
            } else if ((str_contains($lowername, 'website') || str_contains($lowername, 'url')) &&
                    (!filter_var($value, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $value))) {
                $errors[$name] = get_string('validationurl', 'tool_datasetup');
            } else if (str_contains($lowername, 'pincode') && !preg_match('/^\d{6}$/', $value)) {
                $errors[$name] = get_string('validationpincode', 'tool_datasetup');
            } else if ((str_contains($lowername, 'phone') || str_contains($lowername, 'mobile')) &&
                    !preg_match('/^\d{10,15}$/', preg_replace('/\D+/', '', $value))) {
                $errors[$name] = get_string('validationphone', 'tool_datasetup');
            } else if (str_contains($lowername, 'academic_year') && !self::valid_academic_year($value)) {
                $errors[$name] = get_string('academicyearinvalid', 'tool_datasetup');
            } else if (str_contains($lowerpattern, 'yyyy-mm-dd') && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $errors[$name] = get_string('validationdate', 'tool_datasetup');
            } else if (str_contains($lowerpattern, 'whole number') && !preg_match('/^\d+$/', $value)) {
                $errors[$name] = get_string('validationnumber', 'tool_datasetup');
            } else if (str_contains($lowerpattern, '0-100') &&
                    (!is_numeric($value) || (float) $value < 0 || (float) $value > 100)) {
                $errors[$name] = get_string('validationpercent', 'tool_datasetup');
            } else if (str_contains($lowerpattern, '1 yes, 0 no') &&
                    !in_array(strtolower($value), ['0', '1', 'yes', 'no', 'true', 'false'], true)) {
                $errors[$name] = get_string('validationboolean', 'tool_datasetup');
            } else if (self::looks_like_code_field($name, $pattern) &&
                    !preg_match('/^[A-Za-z0-9][A-Za-z0-9_.:-]*$/', $value)) {
                $errors[$name] = get_string('validationcode', 'tool_datasetup');
            }
        }

        return $errors;
    }

    /**
     * Returns whether a value matches the academic-year formula.
     *
     * @param string $value Value.
     * @return bool
     */
    private static function valid_academic_year(string $value): bool {
        if (!preg_match('/^(\d{4})-(\d{4})$/', $value, $matches)) {
            return false;
        }

        return (int) $matches[2] === (int) $matches[1] + 1;
    }

    /**
     * Returns whether a column should use code/id validation.
     *
     * @param string $name Column name.
     * @param string $pattern Pattern guidance.
     * @return bool
     */
    private static function looks_like_code_field(string $name, string $pattern): bool {
        $lowername = strtolower($name);
        $lowerpattern = strtolower($pattern);

        return str_ends_with($lowername, '_code') ||
            str_ends_with($lowername, '_idnumber') ||
            $lowername === 'idnumber' ||
            str_contains($lowerpattern, '<') && str_contains($lowerpattern, 'code>');
    }
}
