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

/**
 * CSV import form.
 *
 * @package    tool_datasetup
 * @copyright  2026 Hardik Chauhan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_form extends \moodleform {
    /**
     * Defines import controls.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $sheet = $this->_customdata['sheet'];

        $mform->addElement('hidden', 'sheet', $sheet);
        $mform->setType('sheet', PARAM_RAW_TRIMMED);
        $mform->addElement('filepicker', 'importfile', get_string('importfile', 'tool_datasetup'), null, [
            'accepted_types' => ['.csv'],
            'maxbytes' => 10485760,
        ]);
        $mform->addRule('importfile', null, 'required', null, 'client');
        $mform->addElement('advcheckbox', 'replace', get_string('replaceexisting', 'tool_datasetup'));
        $mform->setDefault('replace', 0);

        $this->add_action_buttons(true, get_string('import', 'tool_datasetup'));
    }

    /**
     * Server-side validation.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $filename = (string) $this->get_new_filename('importfile');

        if ($filename === '') {
            $errors['importfile'] = get_string('validationcsvrequired', 'tool_datasetup');
            return $errors;
        }

        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'csv') {
            $errors['importfile'] = get_string('validationcsvextension', 'tool_datasetup');
        }

        $content = (string) $this->get_file_content('importfile');
        if (!self::csv_has_data_row($content)) {
            $errors['importfile'] = get_string('validationcsvempty', 'tool_datasetup');
        }

        return $errors;
    }

    /**
     * Returns whether uploaded CSV content has a header and at least one data row.
     *
     * @param string $content CSV content.
     * @return bool
     */
    private static function csv_has_data_row(string $content): bool {
        if (trim($content) === '') {
            return false;
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return false;
        }

        while (($row = fgetcsv($handle)) !== false) {
            foreach ($row as $value) {
                if (trim((string) $value) !== '') {
                    fclose($handle);
                    return true;
                }
            }
        }

        fclose($handle);
        return false;
    }
}
