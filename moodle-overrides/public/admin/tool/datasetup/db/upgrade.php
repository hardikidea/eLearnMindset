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
 * Upgrade steps for the core dataset setup admin tool.
 *
 * @package    tool_datasetup
 * @copyright  2026 Hardik Chauhan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Runs plugin upgrade steps.
 *
 * @param int $oldversion Installed plugin version.
 * @return bool
 */
function xmldb_tool_datasetup_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026071000) {
        $table = new xmldb_table('tool_datasetup_school');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('trust_code', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
            $table->add_field('trust_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('school_code', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
            $table->add_field('school_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('udise_code', XMLDB_TYPE_CHAR, '11', null, null, null, null);
            $table->add_field('affiliation_no', XMLDB_TYPE_CHAR, '80', null, null, null, null);
            $table->add_field('school_type', XMLDB_TYPE_CHAR, '80', null, null, null, null);
            $table->add_field('address_line1', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('address_line2', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('city', XMLDB_TYPE_CHAR, '120', null, XMLDB_NOTNULL, null, null);
            $table->add_field('district', XMLDB_TYPE_CHAR, '120', null, XMLDB_NOTNULL, null, null);
            $table->add_field('state', XMLDB_TYPE_CHAR, '120', null, XMLDB_NOTNULL, null, null);
            $table->add_field('pincode', XMLDB_TYPE_CHAR, '6', null, XMLDB_NOTNULL, null, null);
            $table->add_field('phone', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $table->add_field('email', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('website', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('principal_username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('academic_year', XMLDB_TYPE_CHAR, '9', null, XMLDB_NOTNULL, null, null);
            $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('schoolyear_uix', XMLDB_INDEX_UNIQUE, ['school_code', 'academic_year']);
            $table->add_index('email_uix', XMLDB_INDEX_UNIQUE, ['email']);
            $table->add_index('trust_idx', XMLDB_INDEX_NOTUNIQUE, ['trust_code']);
            $table->add_index('state_year_idx', XMLDB_INDEX_NOTUNIQUE, ['state', 'academic_year']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026071000, 'tool', 'datasetup');
    }

    if ($oldversion < 2026071001) {
        $moduletable = new xmldb_table('tool_datasetup_module');

        if (!$dbman->table_exists($moduletable)) {
            $moduletable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $moduletable->add_field('sheet_name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $moduletable->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $moduletable->add_field('module_group', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $moduletable->add_field('source_csv', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $moduletable->add_field('ordered_csv', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $moduletable->add_field('purpose', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $moduletable->add_field('required', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $moduletable->add_field('standard_prefilled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $moduletable->add_field('header_row', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '5');
            $moduletable->add_field('example_row', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '6');
            $moduletable->add_field('data_start_row', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '7');
            $moduletable->add_field('column_count', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $moduletable->add_field('columns_json', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $moduletable->add_field('sort_order', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $moduletable->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $moduletable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $moduletable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $moduletable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $moduletable->add_index('sheetname_uix', XMLDB_INDEX_UNIQUE, ['sheet_name']);
            $moduletable->add_index('group_order_idx', XMLDB_INDEX_NOTUNIQUE, ['module_group', 'sort_order']);
            $dbman->create_table($moduletable);
        }

        $recordtable = new xmldb_table('tool_datasetup_record');

        if (!$dbman->table_exists($recordtable)) {
            $recordtable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $recordtable->add_field('moduleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $recordtable->add_field('row_number', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $recordtable->add_field('row_key', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $recordtable->add_field('row_hash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
            $recordtable->add_field('row_data', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $recordtable->add_field('search_text', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $recordtable->add_field('validation_state', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'valid');
            $recordtable->add_field('validation_errors', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $recordtable->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $recordtable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $recordtable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $recordtable->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $recordtable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $recordtable->add_key('modulefk', XMLDB_KEY_FOREIGN, ['moduleid'], 'tool_datasetup_module', ['id']);
            $recordtable->add_index('module_row_idx', XMLDB_INDEX_NOTUNIQUE, ['moduleid', 'row_number']);
            $recordtable->add_index('module_status_idx', XMLDB_INDEX_NOTUNIQUE, ['moduleid', 'status']);
            $recordtable->add_index('module_hash_idx', XMLDB_INDEX_NOTUNIQUE, ['moduleid', 'row_hash']);
            $dbman->create_table($recordtable);
        }

        upgrade_plugin_savepoint(true, 2026071001, 'tool', 'datasetup');
    }

    return true;
}
