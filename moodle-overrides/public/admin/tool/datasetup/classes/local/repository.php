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
 * Repository for school setup records.
 *
 * @package    tool_datasetup
 * @copyright  2026 Hardik Chauhan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository {
    /** Database table name. */
    public const TABLE = 'tool_datasetup_school';

    /** Editable fields. */
    public const EDITABLE_FIELDS = [
        'trust_code',
        'trust_name',
        'school_code',
        'school_name',
        'udise_code',
        'affiliation_no',
        'school_type',
        'address_line1',
        'address_line2',
        'city',
        'district',
        'state',
        'pincode',
        'phone',
        'email',
        'website',
        'principal_username',
        'academic_year',
        'status',
    ];

    /** Sortable fields exposed by the list page. */
    public const SORTABLE_FIELDS = [
        'school_name',
        'school_code',
        'trust_code',
        'city',
        'district',
        'state',
        'academic_year',
        'status',
        'timemodified',
    ];

    /**
     * Returns one setup record.
     *
     * @param int $id Record id.
     * @return \stdClass
     */
    public static function get(int $id): \stdClass {
        global $DB;

        return $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Creates a setup record.
     *
     * @param \stdClass $data Form data.
     * @return int New record id.
     */
    public static function create(\stdClass $data): int {
        global $DB, $USER;

        $record = self::normalise_record($data);
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;
        $record->usermodified = $USER->id;

        return $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Updates a setup record.
     *
     * @param int $id Record id.
     * @param \stdClass $data Form data.
     * @return void
     */
    public static function update(int $id, \stdClass $data): void {
        global $DB, $USER;

        $record = self::normalise_record($data);
        $record->id = $id;
        $record->timemodified = time();
        $record->usermodified = $USER->id;

        $DB->update_record(self::TABLE, $record);
    }

    /**
     * Deletes a setup record.
     *
     * @param int $id Record id.
     * @return bool
     */
    public static function delete(int $id): bool {
        global $DB;

        return $DB->delete_records(self::TABLE, ['id' => $id]);
    }

    /**
     * Converts user input to DB-safe values.
     *
     * @param \stdClass $data Form data.
     * @return \stdClass
     */
    public static function normalise_record(\stdClass $data): \stdClass {
        $record = new \stdClass();

        foreach (self::EDITABLE_FIELDS as $field) {
            $value = $data->{$field} ?? '';
            if (is_string($value)) {
                $value = trim($value);
            }
            $record->{$field} = $value;
        }

        $record->trust_code = strtoupper($record->trust_code);
        $record->school_code = strtoupper($record->school_code);
        $record->udise_code = preg_replace('/\D+/', '', (string) $record->udise_code);
        $record->pincode = preg_replace('/\D+/', '', (string) $record->pincode);
        $record->phone = preg_replace('/\D+/', '', (string) $record->phone);
        $record->email = strtolower($record->email);
        $record->academic_year = trim((string) $record->academic_year);
        $record->status = empty($record->status) ? 0 : 1;

        return $record;
    }

    /**
     * Searches setup records.
     *
     * @param array $filters Filters.
     * @param string $sort Sort field.
     * @param string $dir Sort direction.
     * @param int $page Page number.
     * @param int $perpage Rows per page.
     * @return array With total and records.
     */
    public static function search(array $filters, string $sort, string $dir, int $page, int $perpage): array {
        global $DB;

        [$where, $params] = self::build_filter_sql($filters);
        $sortsql = self::get_sort_sql($sort, $dir);

        $total = $DB->count_records_sql('SELECT COUNT(1) FROM {' . self::TABLE . '} s ' . $where, $params);
        $records = $DB->get_records_sql(
            'SELECT s.* FROM {' . self::TABLE . '} s ' . $where . ' ORDER BY ' . $sortsql,
            $params,
            $page * $perpage,
            $perpage
        );

        return [$total, $records];
    }

    /**
     * Returns count summary for the dashboard cards.
     *
     * @return array
     */
    public static function get_summary(): array {
        global $DB;

        $total = $DB->count_records(self::TABLE);
        $active = $DB->count_records(self::TABLE, ['status' => 1]);
        $states = count(self::get_distinct_values('state'));
        $years = count(self::get_distinct_values('academic_year'));

        return [
            'total' => $total,
            'active' => $active,
            'states' => $states,
            'years' => $years,
        ];
    }

    /**
     * Returns distinct values for a field.
     *
     * @param string $field Field name.
     * @return array
     */
    public static function get_distinct_values(string $field): array {
        global $DB;

        $allowed = ['state', 'academic_year'];
        if (!in_array($field, $allowed, true)) {
            return [];
        }

        return $DB->get_fieldset_sql(
            'SELECT DISTINCT ' . $field . ' FROM {' . self::TABLE . '} WHERE ' . $field . " <> '' ORDER BY " . $field
        );
    }

    /**
     * Checks whether a school/year pair already exists.
     *
     * @param string $schoolcode School code.
     * @param string $academicyear Academic year.
     * @param int $excludeid Record id to exclude.
     * @return bool
     */
    public static function school_year_exists(string $schoolcode, string $academicyear, int $excludeid = 0): bool {
        global $DB;

        $params = [
            'schoolcode' => strtoupper(trim($schoolcode)),
            'academicyear' => trim($academicyear),
        ];
        $sql = 'school_code = :schoolcode AND academic_year = :academicyear';

        if ($excludeid > 0) {
            $sql .= ' AND id <> :excludeid';
            $params['excludeid'] = $excludeid;
        }

        return $DB->record_exists_select(self::TABLE, $sql, $params);
    }

    /**
     * Checks whether an email already exists.
     *
     * @param string $email Email.
     * @param int $excludeid Record id to exclude.
     * @return bool
     */
    public static function email_exists(string $email, int $excludeid = 0): bool {
        global $DB;

        $params = ['email' => strtolower(trim($email))];
        $sql = 'email = :email';

        if ($excludeid > 0) {
            $sql .= ' AND id <> :excludeid';
            $params['excludeid'] = $excludeid;
        }

        return $DB->record_exists_select(self::TABLE, $sql, $params);
    }

    /**
     * Checks whether a principal username exists in Moodle.
     *
     * @param string $username Moodle username.
     * @return bool
     */
    public static function principal_user_exists(string $username): bool {
        global $DB;

        return $DB->record_exists_select(
            'user',
            'username = :username AND deleted = 0',
            ['username' => trim($username)]
        );
    }

    /**
     * Builds the WHERE clause for search filters.
     *
     * @param array $filters Filters.
     * @return array SQL and params.
     */
    private static function build_filter_sql(array $filters): array {
        global $DB;

        $clauses = [];
        $params = [];

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $searchfields = [
                'trust_code',
                'trust_name',
                'school_code',
                'school_name',
                'city',
                'district',
                'state',
                'email',
                'principal_username',
                'academic_year',
            ];
            $likes = [];
            foreach ($searchfields as $index => $field) {
                $param = 'q' . $index;
                $likes[] = $DB->sql_like('s.' . $field, ':' . $param, false, false);
                $params[$param] = '%' . $DB->sql_like_escape($query) . '%';
            }
            $clauses[] = '(' . implode(' OR ', $likes) . ')';
        }

        $state = trim((string) ($filters['state'] ?? ''));
        if ($state !== '') {
            $clauses[] = 's.state = :state';
            $params['state'] = $state;
        }

        $year = trim((string) ($filters['academic_year'] ?? ''));
        if ($year !== '') {
            $clauses[] = 's.academic_year = :academicyear';
            $params['academicyear'] = $year;
        }

        if (isset($filters['status']) && (int) $filters['status'] >= 0) {
            $clauses[] = 's.status = :status';
            $params['status'] = (int) $filters['status'];
        }

        $where = '';
        if ($clauses) {
            $where = ' WHERE ' . implode(' AND ', $clauses);
        }

        return [$where, $params];
    }

    /**
     * Returns a safe ORDER BY expression.
     *
     * @param string $sort Sort field.
     * @param string $dir Sort direction.
     * @return string
     */
    private static function get_sort_sql(string $sort, string $dir): string {
        if (!in_array($sort, self::SORTABLE_FIELDS, true)) {
            $sort = 'school_name';
        }

        $dir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';

        return 's.' . $sort . ' ' . $dir . ', s.id ASC';
    }
}
