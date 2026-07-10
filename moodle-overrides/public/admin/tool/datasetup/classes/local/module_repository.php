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
 * Repository for workbook modules and records.
 *
 * @package    tool_datasetup
 * @copyright  2026 Hardik Chauhan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module_repository {
    /** Module metadata table. */
    public const MODULE_TABLE = 'tool_datasetup_module';

    /** Module row table. */
    public const RECORD_TABLE = 'tool_datasetup_record';

    /** Default CSV path relative to this class. */
    private const DEFAULT_CSV_DIR = '/../../data/defaults';

    /**
     * Returns dashboard summary.
     *
     * @return array
     */
    public static function summary(): array {
        global $DB;

        schema::ensure_seeded();
        self::ensure_default_records_seeded();

        $modules = $DB->count_records(self::MODULE_TABLE, ['status' => 1]);
        $required = $DB->count_records(self::MODULE_TABLE, ['required' => 1, 'status' => 1]);
        $records = $DB->count_records_sql(
            'SELECT COUNT(1)
               FROM {' . self::RECORD_TABLE . '} r
               JOIN {' . self::MODULE_TABLE . '} m ON m.id = r.moduleid
              WHERE m.status = 1'
        );
        $groups = count(self::groups());

        return [
            'modules' => $modules,
            'required' => $required,
            'records' => $records,
            'groups' => $groups,
        ];
    }

    /**
     * Returns module groups.
     *
     * @return array
     */
    public static function groups(): array {
        global $DB;

        schema::ensure_seeded();
        self::ensure_default_records_seeded();

        return $DB->get_fieldset_sql(
            'SELECT DISTINCT module_group FROM {' . self::MODULE_TABLE . "}
              WHERE module_group <> '' AND status = 1
           ORDER BY module_group"
        );
    }

    /**
     * Searches module cards.
     *
     * @param array $filters Filters.
     * @return array Module records.
     */
    public static function modules(array $filters = []): array {
        global $DB;

        schema::ensure_seeded();
        self::ensure_default_records_seeded();

        [$where, $params] = self::module_filter_sql($filters);
        $sql = "SELECT m.*, COALESCE(rc.recordcount, 0) AS recordcount
                  FROM {" . self::MODULE_TABLE . "} m
             LEFT JOIN (
                    SELECT moduleid, COUNT(1) AS recordcount
                      FROM {" . self::RECORD_TABLE . "}
                  GROUP BY moduleid
                  ) rc ON rc.moduleid = m.id
                 $where
              ORDER BY m.sort_order ASC, m.title ASC";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Returns one module by sheet name.
     *
     * @param string $sheet Sheet name.
     * @return \stdClass
     */
    public static function get_module_by_sheet(string $sheet): \stdClass {
        global $DB;

        schema::ensure_seeded();
        self::ensure_default_records_seeded();

        return $DB->get_record(self::MODULE_TABLE, ['sheet_name' => $sheet, 'status' => 1], '*', MUST_EXIST);
    }

    /**
     * Searches records for a module.
     *
     * @param int $moduleid Module id.
     * @param array $filters Filters.
     * @param string $sort Sort field.
     * @param string $dir Sort direction.
     * @param int $page Page number.
     * @param int $perpage Rows per page.
     * @return array Total and records.
     */
    public static function search_records(
        int $moduleid,
        array $filters,
        string $sort,
        string $dir,
        int $page,
        int $perpage
    ): array {
        global $DB;

        [$where, $params] = self::record_filter_sql($moduleid, $filters);
        $sortsql = self::record_sort_sql($sort, $dir);
        $total = $DB->count_records_sql('SELECT COUNT(1) FROM {' . self::RECORD_TABLE . '} r ' . $where, $params);
        $records = $DB->get_records_sql(
            'SELECT r.* FROM {' . self::RECORD_TABLE . '} r ' . $where . ' ORDER BY ' . $sortsql,
            $params,
            $page * $perpage,
            $perpage
        );

        return [$total, $records];
    }

    /**
     * Returns a single record.
     *
     * @param int $id Record id.
     * @return \stdClass
     */
    public static function get_record(int $id): \stdClass {
        global $DB;

        return $DB->get_record(self::RECORD_TABLE, ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Creates a module row.
     *
     * @param \stdClass $module Module record.
     * @param array $row Row data.
     * @param int $rownumber Source row number.
     * @return int New record id.
     */
    public static function create_record(\stdClass $module, array $row, int $rownumber = 0): int {
        global $DB, $USER;

        $record = self::row_to_record($module, $row, $rownumber);
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;
        $record->usermodified = $USER->id ?? 0;

        return $DB->insert_record(self::RECORD_TABLE, $record);
    }

    /**
     * Updates a module row.
     *
     * @param \stdClass $module Module record.
     * @param int $id Record id.
     * @param array $row Row data.
     * @return void
     */
    public static function update_record(\stdClass $module, int $id, array $row): void {
        global $DB, $USER;

        $existing = self::get_record($id);
        $record = self::row_to_record($module, $row, (int) $existing->row_number);
        $record->id = $id;
        $record->timecreated = $existing->timecreated;
        $record->timemodified = time();
        $record->usermodified = $USER->id;

        $DB->update_record(self::RECORD_TABLE, $record);
    }

    /**
     * Deletes a module row.
     *
     * @param int $id Record id.
     * @return bool
     */
    public static function delete_record(int $id): bool {
        global $DB;

        return $DB->delete_records(self::RECORD_TABLE, ['id' => $id]);
    }

    /**
     * Imports CSV content.
     *
     * @param \stdClass $module Module record.
     * @param string $content CSV content.
     * @param bool $replace Replace existing module rows.
     * @return array Import summary.
     */
    public static function import_csv(\stdClass $module, string $content, bool $replace): array {
        global $DB;

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return ['created' => 0, 'errors' => [get_string('importempty', 'tool_datasetup')]];
        }

        $headers = array_map(static fn($value): string => trim((string) $value), $headers);
        $allowed = array_column(schema::columns($module), 'name');
        $unknown = array_diff($headers, $allowed);
        if ($unknown) {
            fclose($handle);
            return ['created' => 0, 'errors' => [get_string('importunknowncolumns', 'tool_datasetup', implode(', ', $unknown))]];
        }

        $transaction = $DB->start_delegated_transaction();
        if ($replace) {
            $DB->delete_records(self::RECORD_TABLE, ['moduleid' => $module->id]);
        }

        $created = 0;
        $errors = [];
        $rownumber = 1;

        while (($values = fgetcsv($handle)) !== false) {
            $rownumber++;
            if (self::csv_row_empty($values)) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = isset($values[$index]) ? trim((string) $values[$index]) : '';
            }

            $validation = schema::validate_row($module, $row) + self::validate_reference_values($module, $row);
            if ($validation) {
                $errors[] = get_string('importrowerror', 'tool_datasetup', [
                    'row' => $rownumber,
                    'errors' => implode('; ', array_map(
                        static fn($field, $error): string => $field . ': ' . $error,
                        array_keys($validation),
                        $validation
                    )),
                ]);
                continue;
            }

            self::create_record($module, $row, $rownumber);
            $created++;
        }

        fclose($handle);
        $transaction->allow_commit();

        return ['created' => $created, 'errors' => $errors];
    }

    /**
     * Returns CSV content for module records or a blank template.
     *
     * @param \stdClass $module Module record.
     * @param bool $templateonly Whether to export only header/example rows.
     * @return string
     */
    public static function export_csv(\stdClass $module, bool $templateonly = false): string {
        global $DB;

        $columns = schema::columns($module);
        $headers = array_column($columns, 'name');
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        if ($templateonly) {
            fputcsv($handle, array_map(static fn($column): string => (string) ($column['example'] ?? ''), $columns));
        } else {
            $records = $DB->get_records(self::RECORD_TABLE, ['moduleid' => $module->id], 'row_number ASC, id ASC');
            foreach ($records as $record) {
                $row = json_decode($record->row_data, true);
                $row = is_array($row) ? $row : [];
                fputcsv($handle, array_map(static fn($header): string => (string) ($row[$header] ?? ''), $headers));
            }
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }

    /**
     * Returns select options for a column when a master-data lookup is available.
     *
     * @param string $columnname Column name.
     * @return array
     */
    public static function options_for_column(string $columnname, ?\stdClass $currentmodule = null): array {
        global $DB;

        $columnname = strtolower($columnname);
        $profileoptions = self::profile_field_options($columnname);
        if ($profileoptions !== null) {
            return $profileoptions;
        }

        if ($columnname === 'role_shortname') {
            if ($currentmodule !== null && (string) $currentmodule->sheet_name === '17_custom_roles') {
                return [];
            }

            return self::role_options();
        }

        $fixed = self::fixed_options_for_column($columnname);
        if ($fixed !== null) {
            return $fixed;
        }

        $lookupkey = self::lookup_key_for_column($columnname);
        $lookupmap = [
            'academic_year' => ['02_academic_years', 'academic_year', 'academic_year'],
            'trust_code' => ['01_school_master', 'trust_code', 'trust_name'],
            'school_code' => ['01_school_master', 'school_code', 'school_name'],
            'board_code' => ['03_boards', 'board_code', 'board_name'],
            'medium_code' => ['04_mediums', 'medium_code', 'medium_name'],
            'grade_code' => ['05_grades', 'grade_code', 'grade_name'],
            'stream_code' => ['06_streams', 'stream_code', 'stream_name'],
            'division_code' => ['07_divisions', 'division_code', 'division_name'],
            'subject_code' => ['08_subjects', 'subject_code', 'subject_name'],
            'term_code' => ['exam_terms', 'term_code', 'name'],
            'policy_code' => ['40_certificate_policy', 'policy_code', 'implementation'],
            'certificate_policy_code' => ['40_certificate_policy', 'policy_code', 'implementation'],
            'student_status' => ['50_student_status', 'status_code', 'status_name'],
            'promotion_status' => ['48_promotion_status', 'status_code', 'status_name'],
        ];

        if (!isset($lookupmap[$lookupkey])) {
            return [];
        }

        [$sheet, $valuefield, $labelfield] = $lookupmap[$lookupkey];
        if ($currentmodule !== null && (string) $currentmodule->sheet_name === $sheet) {
            return [];
        }

        $module = $DB->get_record(self::MODULE_TABLE, ['sheet_name' => $sheet, 'status' => 1]);
        if (!$module) {
            return self::lookup_value_options($lookupkey);
        }

        $records = $DB->get_records(self::RECORD_TABLE, ['moduleid' => $module->id, 'status' => 1], 'row_number ASC, id ASC');
        $options = [];

        foreach ($records as $record) {
            $row = json_decode($record->row_data, true);
            if (!is_array($row) || empty($row[$valuefield])) {
                continue;
            }
            $value = (string) $row[$valuefield];
            $label = !empty($row[$labelfield]) ? $value . ' - ' . $row[$labelfield] : $value;
            $options[$value] = $label;
        }

        $options = $options ?: self::lookup_value_options($lookupkey);

        if ($currentmodule !== null && (string) $currentmodule->sheet_name === '45_promotion_rules' &&
                in_array($lookupkey, ['grade_code', 'stream_code'], true)) {
            $options += [
                'ANY' => 'ANY',
                'SAME' => 'SAME',
                'ALUMNI' => 'ALUMNI',
            ];
        }

        return $options;
    }

    /**
     * Validates dropdown/reference values against configured master rows.
     *
     * @param \stdClass $module Current module.
     * @param array $row Row values.
     * @return array Validation errors keyed by column name.
     */
    public static function validate_reference_values(\stdClass $module, array $row): array {
        $errors = [];

        foreach (schema::columns($module) as $column) {
            $name = (string) $column['name'];
            $value = trim((string) ($row[$name] ?? ''));
            if ($value === '') {
                continue;
            }

            $options = self::options_for_column($name, $module);
            if ($options && !self::option_exists($value, $options)) {
                $errors[$name] = get_string('validationreference', 'tool_datasetup');
            }
        }

        return $errors;
    }

    /**
     * Seeds bundled standard default rows when those modules are still empty.
     *
     * School-specific and generated sheets are intentionally not included.
     *
     * @return void
     */
    private static function ensure_default_records_seeded(): void {
        global $DB;

        static $seeded = false;
        if ($seeded) {
            return;
        }

        $modules = $DB->get_records(self::MODULE_TABLE, ['standard_prefilled' => 1, 'status' => 1], '', '*');
        foreach ($modules as $module) {
            if (empty($module->ordered_csv)) {
                continue;
            }

            if ($DB->record_exists(self::RECORD_TABLE, ['moduleid' => $module->id, 'status' => 1])) {
                continue;
            }

            $path = self::default_csv_path((string) $module->ordered_csv);
            if (!is_readable($path)) {
                continue;
            }

            self::import_csv($module, file_get_contents($path), false);
        }

        $seeded = true;
    }

    /**
     * Maps a workbook column name to the master lookup it references.
     *
     * @param string $columnname Column name.
     * @return string
     */
    private static function lookup_key_for_column(string $columnname): string {
        $columnname = strtolower($columnname);

        if (in_array($columnname, ['academic_year', 'profile_field_current_academic_year', 'profile_field_previous_academic_year'], true)) {
            return 'academic_year';
        }
        if ($columnname === 'profile_field_student_status') {
            return 'student_status';
        }
        if (str_ends_with($columnname, 'board_code')) {
            return 'board_code';
        }
        if (str_ends_with($columnname, 'school_code')) {
            return 'school_code';
        }
        if (str_ends_with($columnname, 'medium_code')) {
            return 'medium_code';
        }
        if (str_ends_with($columnname, 'grade_code')) {
            return 'grade_code';
        }
        if (str_ends_with($columnname, 'stream_code')) {
            return 'stream_code';
        }
        if (str_ends_with($columnname, 'division_code')) {
            return 'division_code';
        }
        if (str_ends_with($columnname, 'subject_code')) {
            return 'subject_code';
        }

        return $columnname;
    }

    /**
     * Returns fallback options from the standard lookup-values module.
     *
     * @param string $lookupkey Lookup type.
     * @return array
     */
    private static function lookup_value_options(string $lookupkey): array {
        global $DB;

        $module = $DB->get_record(self::MODULE_TABLE, ['sheet_name' => '26_lookup_values']);
        if (!$module) {
            return [];
        }

        $records = $DB->get_records(self::RECORD_TABLE, ['moduleid' => $module->id, 'status' => 1], 'row_number ASC, id ASC');
        $options = [];

        foreach ($records as $record) {
            $row = json_decode($record->row_data, true);
            if (!is_array($row) || ($row['lookup_type'] ?? '') !== $lookupkey || empty($row['code'])) {
                continue;
            }
            $value = (string) $row['code'];
            $label = !empty($row['label']) ? $value . ' - ' . $row['label'] : $value;
            $options[$value] = $label;
        }

        return $options;
    }

    /**
     * Returns Moodle core roles plus custom role rows from the setup module.
     *
     * @return array
     */
    private static function role_options(): array {
        global $DB;

        $options = [
            'student' => 'student',
            'teacher' => 'teacher',
            'editingteacher' => 'editingteacher',
            'manager' => 'manager',
            'coursecreator' => 'coursecreator',
        ];

        $module = $DB->get_record(self::MODULE_TABLE, ['sheet_name' => '17_custom_roles']);
        if (!$module) {
            return $options;
        }

        $records = $DB->get_records(self::RECORD_TABLE, ['moduleid' => $module->id, 'status' => 1], 'row_number ASC, id ASC');
        foreach ($records as $record) {
            $row = json_decode($record->row_data, true);
            if (!is_array($row) || empty($row['role_shortname'])) {
                continue;
            }
            $value = (string) $row['role_shortname'];
            $label = !empty($row['role_name']) ? $value . ' - ' . $row['role_name'] : $value;
            $options[$value] = $label;
        }

        return $options;
    }

    /**
     * Returns dropdown values from custom profile field menu definitions.
     *
     * @param string $columnname Column name.
     * @return array|null Null when the column is not a menu profile field.
     */
    private static function profile_field_options(string $columnname): ?array {
        global $DB;

        if (!str_starts_with($columnname, 'profile_field_')) {
            return null;
        }

        $shortname = substr($columnname, strlen('profile_field_'));
        $module = $DB->get_record(self::MODULE_TABLE, ['sheet_name' => '16_profile_fields']);
        if (!$module) {
            return null;
        }

        $records = $DB->get_records(self::RECORD_TABLE, ['moduleid' => $module->id, 'status' => 1], 'row_number ASC, id ASC');
        foreach ($records as $record) {
            $row = json_decode($record->row_data, true);
            if (!is_array($row) || ($row['shortname'] ?? '') !== $shortname ||
                    strtolower((string) ($row['datatype'] ?? '')) !== 'menu' || empty($row['options'])) {
                continue;
            }

            $options = [];
            foreach (explode('|', (string) $row['options']) as $option) {
                $option = trim($option);
                if ($option !== '') {
                    $options[$option] = $option;
                }
            }

            return $options;
        }

        return null;
    }

    /**
     * Returns whether a submitted value is present in option keys, case-insensitively.
     *
     * @param string $value Submitted value.
     * @param array $options Options.
     * @return bool
     */
    private static function option_exists(string $value, array $options): bool {
        if (array_key_exists($value, $options)) {
            return true;
        }

        $lower = strtolower($value);
        foreach ($options as $optionvalue => $optionlabel) {
            if (strtolower((string) $optionvalue) === $lower || strtolower((string) $optionlabel) === $lower) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the bundled default CSV path for a file name.
     *
     * @param string $filename CSV file name from module metadata.
     * @return string
     */
    private static function default_csv_path(string $filename): string {
        return __DIR__ . self::DEFAULT_CSV_DIR . '/' . basename($filename);
    }

    /**
     * Returns fixed select options for standard Moodle/school fields.
     *
     * @param string $columnname Column name.
     * @return array|null
     */
    private static function fixed_options_for_column(string $columnname): ?array {
        $basecolumnname = str_starts_with($columnname, 'profile_field_') ?
            substr($columnname, strlen('profile_field_')) : $columnname;
        $booleanfields = [
            'is_current',
            'visible',
            'is_compulsory',
            'is_elective',
            'enabled',
            'required_for_completion',
            'certificate_enabled',
            'requires_plugin',
            'expiry_enabled',
            'compatible_moodle_5_0_2',
            'compatible_moodle_5_2_branch_502',
            'permanent_address_same',
            'aadhaar_consent',
            'transport_required',
            'rte_category',
            'bpl',
            'disability_status',
        ];

        if (in_array($basecolumnname, $booleanfields, true) || str_starts_with($columnname, 'is_')) {
            return [
                '1' => get_string('yes'),
                '0' => get_string('no'),
            ];
        }

        return match ($columnname) {
            'auth' => [
                'manual' => 'manual',
                'email' => 'email',
                'oauth2' => 'oauth2',
                'ldap' => 'ldap',
            ],
            'course_format' => [
                'topics' => 'topics',
                'weeks' => 'weeks',
                'singleactivity' => 'singleactivity',
                'social' => 'social',
            ],
            'credential_type' => [
                'certificate' => 'certificate',
                'badge' => 'badge',
            ],
            'issue_condition' => [
                'course_completion' => 'course_completion',
                'grade_threshold' => 'grade_threshold',
                'manual_review' => 'manual_review',
            ],
            'enrolment_method' => [
                'cohort_sync' => 'cohort_sync',
                'manual' => 'manual',
                'self' => 'self',
            ],
            default => null,
        };
    }

    /**
     * Converts input row to DB record.
     *
     * @param \stdClass $module Module record.
     * @param array $row Row.
     * @param int $rownumber Source row number.
     * @return \stdClass
     */
    private static function row_to_record(\stdClass $module, array $row, int $rownumber): \stdClass {
        $row = schema::normalise_row($module, $row);
        $validation = schema::validate_row($module, $row) + self::validate_reference_values($module, $row);
        $record = new \stdClass();
        $record->moduleid = $module->id;
        $record->row_number = $rownumber;
        $record->row_key = self::row_key($row);
        $record->row_hash = sha1(json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $record->row_data = json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $record->search_text = implode(' ', array_values($row));
        $record->validation_state = $validation ? 'invalid' : 'valid';
        $record->validation_errors = $validation ? json_encode($validation, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $record->status = 1;

        return $record;
    }

    /**
     * Builds a human-friendly row key.
     *
     * @param array $row Row data.
     * @return string
     */
    private static function row_key(array $row): string {
        foreach ($row as $name => $value) {
            if ($value !== '' && (str_ends_with($name, '_code') || $name === 'idnumber' ||
                    str_contains($name, 'username') || str_contains($name, 'email'))) {
                return \core_text::substr((string) $value, 0, 255);
            }
        }

        foreach ($row as $value) {
            if ($value !== '') {
                return \core_text::substr((string) $value, 0, 255);
            }
        }

        return '';
    }

    /**
     * Returns whether a CSV row is empty.
     *
     * @param array $values Values.
     * @return bool
     */
    private static function csv_row_empty(array $values): bool {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Builds module filter SQL.
     *
     * @param array $filters Filters.
     * @return array
     */
    private static function module_filter_sql(array $filters): array {
        global $DB;

        $clauses = ['m.status = 1'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $likes = [];
            foreach (['sheet_name', 'title', 'module_group', 'purpose', 'ordered_csv'] as $index => $field) {
                $param = 'q' . $index;
                $likes[] = $DB->sql_like('m.' . $field, ':' . $param, false, false);
                $params[$param] = '%' . $DB->sql_like_escape($q) . '%';
            }
            $clauses[] = '(' . implode(' OR ', $likes) . ')';
        }

        $group = trim((string) ($filters['group'] ?? ''));
        if ($group !== '') {
            $clauses[] = 'm.module_group = :modulegroup';
            $params['modulegroup'] = $group;
        }

        if (isset($filters['required']) && (int) $filters['required'] >= 0) {
            $clauses[] = 'm.required = :required';
            $params['required'] = (int) $filters['required'];
        }

        return [' WHERE ' . implode(' AND ', $clauses), $params];
    }

    /**
     * Builds record filter SQL.
     *
     * @param int $moduleid Module id.
     * @param array $filters Filters.
     * @return array
     */
    private static function record_filter_sql(int $moduleid, array $filters): array {
        global $DB;

        $clauses = ['r.moduleid = :moduleid'];
        $params = ['moduleid' => $moduleid];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $clauses[] = $DB->sql_like('r.search_text', ':q', false, false);
            $params['q'] = '%' . $DB->sql_like_escape($q) . '%';
        }

        $state = trim((string) ($filters['validation_state'] ?? ''));
        if ($state !== '') {
            $clauses[] = 'r.validation_state = :validationstate';
            $params['validationstate'] = $state;
        }

        return [' WHERE ' . implode(' AND ', $clauses), $params];
    }

    /**
     * Returns safe record sort SQL.
     *
     * @param string $sort Sort field.
     * @param string $dir Direction.
     * @return string
     */
    private static function record_sort_sql(string $sort, string $dir): string {
        $allowed = ['row_number', 'row_key', 'validation_state', 'timemodified'];
        if (!in_array($sort, $allowed, true)) {
            $sort = 'row_number';
        }

        $dir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';

        return 'r.' . $sort . ' ' . $dir . ', r.id ASC';
    }
}
