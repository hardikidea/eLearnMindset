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

use tool_datasetup\local\repository;
use tool_datasetup\local\module_repository;

/**
 * School setup create/edit form.
 *
 * @package    tool_datasetup
 * @copyright  2026 Hardik Chauhan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class school_setup_form extends \moodleform {
    /**
     * Defines form controls.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('header', 'identity', get_string('identity', 'tool_datasetup'));
        $this->add_text('trust_code', true, 40);
        $this->add_text('trust_name', true, 255);
        $this->add_text('school_code', true, 40);
        $this->add_text('school_name', true, 255);
        $this->add_text('udise_code', false, 11);
        $this->add_text('affiliation_no', false, 80);
        $this->add_text('school_type', false, 80);

        $mform->addElement('header', 'address', get_string('address', 'tool_datasetup'));
        $this->add_text('address_line1', false, 255);
        $this->add_text('address_line2', false, 255);
        $this->add_text('city', true, 120);
        $this->add_text('district', true, 120);
        $this->add_text('state', true, 120);
        $this->add_text('pincode', true, 6);

        $mform->addElement('header', 'contact', get_string('contact', 'tool_datasetup'));
        $this->add_text('phone', false, 20);
        $this->add_text('email', true, 255, PARAM_EMAIL);
        $this->add_text('website', false, 255, PARAM_URL);

        $mform->addElement('header', 'schooldetails', get_string('schooldetails', 'tool_datasetup'));
        $this->add_text('principal_username', true, 100);
        $this->add_academic_year();
        $mform->addElement('selectyesno', 'status', get_string('status', 'tool_datasetup'));
        $mform->setDefault('status', 1);

        $this->add_action_buttons();
    }

    /**
     * Adds a standard text field.
     *
     * @param string $name Field name.
     * @param bool $required Whether field is required.
     * @param int $maxlength Maximum length.
     * @param string $type Moodle PARAM_* type.
     * @return void
     */
    private function add_text(string $name, bool $required, int $maxlength, string $type = PARAM_RAW_TRIMMED): void {
        $mform = $this->_form;

        $mform->addElement('text', $name, get_string($name, 'tool_datasetup'), ['maxlength' => $maxlength]);
        $mform->setType($name, $type);
        $mform->addRule($name, get_string('maximumchars', '', $maxlength), 'maxlength', $maxlength, 'client');

        if ($required) {
            $mform->addRule($name, null, 'required', null, 'client');
        }
    }

    /**
     * Adds academic year as a dropdown when master rows exist.
     *
     * @return void
     */
    private function add_academic_year(): void {
        $mform = $this->_form;
        $options = module_repository::options_for_column('academic_year');

        if ($options) {
            $mform->addElement('select', 'academic_year', get_string('academic_year', 'tool_datasetup'),
                ['' => get_string('selectavalue', 'tool_datasetup')] + $options);
        } else {
            $mform->addElement('text', 'academic_year', get_string('academic_year', 'tool_datasetup'), ['maxlength' => 9]);
            $mform->addRule('academic_year', get_string('maximumchars', '', 9), 'maxlength', 9, 'client');
        }

        $mform->setType('academic_year', PARAM_RAW_TRIMMED);
        $mform->addRule('academic_year', null, 'required', null, 'client');
    }

    /**
     * Server-side validation.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Errors keyed by field.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $id = (int) ($data['id'] ?? 0);

        foreach (['trust_code', 'school_code'] as $field) {
            $value = strtoupper(trim((string) ($data[$field] ?? '')));
            if ($value === '' || !preg_match('/^[A-Z0-9_]{2,40}$/', $value)) {
                $errors[$field] = get_string('requiredtokeninvalid', 'tool_datasetup');
            }
        }

        $udise = preg_replace('/\D+/', '', (string) ($data['udise_code'] ?? ''));
        if ($udise !== '' && !preg_match('/^\d{11}$/', $udise)) {
            $errors['udise_code'] = get_string('udiseinvalid', 'tool_datasetup');
        }

        $pincode = preg_replace('/\D+/', '', (string) ($data['pincode'] ?? ''));
        if (!preg_match('/^\d{6}$/', $pincode)) {
            $errors['pincode'] = get_string('pincodeinvalid', 'tool_datasetup');
        }

        $phone = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));
        if ($phone !== '' && !preg_match('/^\d{10,15}$/', $phone)) {
            $errors['phone'] = get_string('phoneinvalid', 'tool_datasetup');
        }

        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if (!validate_email($email)) {
            $errors['email'] = get_string('emailinvalid', 'tool_datasetup');
        } else if (repository::email_exists($email, $id)) {
            $errors['email'] = get_string('duplicateemail', 'tool_datasetup');
        }

        $website = trim((string) ($data['website'] ?? ''));
        if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
            $errors['website'] = get_string('websiteinvalid', 'tool_datasetup');
        } else if ($website !== '' && !preg_match('/^https?:\/\//i', $website)) {
            $errors['website'] = get_string('websiteinvalid', 'tool_datasetup');
        }

        $principalusername = trim((string) ($data['principal_username'] ?? ''));
        if ($principalusername === '' || !repository::principal_user_exists($principalusername)) {
            $errors['principal_username'] = get_string('principalnotfound', 'tool_datasetup');
        }

        $academicyear = trim((string) ($data['academic_year'] ?? ''));
        if (!preg_match('/^(\d{4})-(\d{4})$/', $academicyear, $matches) ||
                ((int) $matches[2] !== (int) $matches[1] + 1)) {
            $errors['academic_year'] = get_string('academicyearinvalid', 'tool_datasetup');
        }

        if (empty($errors['school_code']) && empty($errors['academic_year']) &&
                repository::school_year_exists($data['school_code'], $academicyear, $id)) {
            $errors['school_code'] = get_string('duplicateschoolyear', 'tool_datasetup');
        }

        return $errors;
    }
}
