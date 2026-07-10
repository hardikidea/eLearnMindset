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

namespace tool_datasetup\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;

/**
 * Privacy metadata provider.
 *
 * @package    tool_datasetup
 * @copyright  2026 Hardik Chauhan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider {
    /**
     * Describes stored metadata.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('tool_datasetup_school', [
            'email' => 'privacy:metadata:tool_datasetup_school:email',
            'principal_username' => 'privacy:metadata:tool_datasetup_school:principal_username',
            'usermodified' => 'privacy:metadata:tool_datasetup_school:usermodified',
        ], 'privacy:metadata:tool_datasetup_school');
        $collection->add_database_table('tool_datasetup_module', [], 'privacy:metadata:tool_datasetup_module');
        $collection->add_database_table('tool_datasetup_record', [
            'row_data' => 'privacy:metadata:tool_datasetup_record:row_data',
            'usermodified' => 'privacy:metadata:tool_datasetup_record:usermodified',
        ], 'privacy:metadata:tool_datasetup_record');

        return $collection;
    }
}
