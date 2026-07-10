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
 * Language strings for the core dataset setup admin tool.
 *
 * @package    tool_datasetup
 * @copyright  2026 Hardik Chauhan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actions'] = 'Actions';
$string['addrow'] = 'Add row';
$string['active'] = 'Active';
$string['academic_year'] = 'Academic year';
$string['academicyearinvalid'] = 'Use YYYY-YYYY format, for example 2026-2027, with the second year exactly one year later.';
$string['addschool'] = 'Add school setup';
$string['address'] = 'Address';
$string['address_line1'] = 'Address line 1';
$string['address_line2'] = 'Address line 2';
$string['affiliation_no'] = 'Affiliation number';
$string['allstatuses'] = 'All statuses';
$string['allgroups'] = 'All module groups';
$string['allmoduletypes'] = 'All module types';
$string['allvalidationstates'] = 'All validation states';
$string['city'] = 'City';
$string['clearfilters'] = 'Clear filters';
$string['columncontract'] = 'Column contract';
$string['columncontractfor'] = 'Column contract: {$a}';
$string['columnsx'] = '{$a} columns';
$string['column'] = 'Column';
$string['contact'] = 'Contact';
$string['confirmdelete'] = 'Delete the school setup record for {$a}?';
$string['confirmdeleterow'] = 'Delete this setup row: {$a}?';
$string['coremodules'] = 'Core setup modules';
$string['csvmapping'] = 'CSV mapping';
$string['customcontract'] = 'Custom contract';
$string['deletefailed'] = 'Unable to delete this school setup record.';
$string['downloadtemplate'] = 'Download template CSV';
$string['district'] = 'District';
$string['duplicateemail'] = 'This email is already used by another school setup record.';
$string['duplicateschoolyear'] = 'This school code already has a setup record for the selected academic year.';
$string['editingschool'] = 'Edit school setup';
$string['editrow'] = 'Edit row';
$string['email'] = 'Email';
$string['emailinvalid'] = 'Enter a valid email address.';
$string['empty'] = 'No school setup records match the current filters.';
$string['emptyrecords'] = 'No rows yet. Add a row manually or import this module CSV.';
$string['export'] = 'Export';
$string['exportcsv'] = 'Export CSV';
$string['example'] = 'Example';
$string['filter'] = 'Filter';
$string['formulaorpattern'] = 'Formula / pattern';
$string['identity'] = 'School identity';
$string['import'] = 'Import';
$string['importcreated'] = '{$a} rows imported.';
$string['importcontractcolumnhelp'] = 'Use the Column contract button on this page to review every column, purpose, required flag, formula/pattern and example value before preparing the CSV.';
$string['importcontractintro'] = 'Prepare the upload file from the standard template for this sheet. Each setup module has its own custom contract, so reuse only the template downloaded from this page.';
$string['importcontractrulegenerated'] = 'Do not manually import generated automation outputs unless the sheet is intentionally exposed here. Generated categories, courses, cohorts, groups and enrolments should come from the CLI pack process.';
$string['importcontractruleheader'] = 'Keep the header row unchanged. Column names must match the template exactly; extra or renamed columns are rejected.';
$string['importcontractrulelookup'] = 'For dropdown/lookup fields, use the stored code value such as board_code, medium_code, grade_code, subject_code or role_shortname, not only the display label.';
$string['importcontractrulereplace'] = 'Use Replace existing rows only for a full-sheet refresh after exporting or rebuilding the complete CSV. Leave it unchecked for normal append imports.';
$string['importcontractrulerequired'] = 'Fill every mandatory column and keep optional columns blank only when the column contract allows it.';
$string['importcontractrulestandarddefault'] = 'This standard-default module is already prefilled from the bundled CLI pack. Import only when you need to override or extend those defaults.';
$string['importcontracttitle'] = 'CSV import standard template';
$string['importcsv'] = 'Import CSV';
$string['importempty'] = 'The CSV file is empty.';
$string['importfile'] = 'CSV file';
$string['importhelp'] = 'Upload a CSV with the same header row as this setup module. Download the template first when you are not sure about the exact columns.';
$string['importrowerror'] = 'Row {$a->row}: {$a->errors}';
$string['importunknowncolumns'] = 'The CSV has columns not used by this module: {$a}';
$string['inactive'] = 'Inactive';
$string['invalid'] = 'Invalid';
$string['mandatory'] = 'Mandatory';
$string['mandatorymodules'] = 'Mandatory modules';
$string['manage'] = 'Manage';
$string['manageheading'] = 'Core dataset setup';
$string['manageintro'] = 'Maintain the master school data used by category, course, cohort and enrolment setup processes.';
$string['modulegroup'] = 'Module group';
$string['modulegroupother'] = 'Other';
$string['moduleintro'] = 'Manage only editable master data, registration inputs, templates and policies. Generated matrices, categories, courses, cohorts, groups, enrolments and promotion outputs are created by automation from this setup data.';
$string['newschool'] = 'New school setup';
$string['nomodules'] = 'No setup modules match the current filters.';
$string['no'] = 'No';
$string['optional'] = 'Optional';
$string['phone'] = 'Phone';
$string['phoneinvalid'] = 'Use 10 to 15 digits for Indian phone numbers.';
$string['pincode'] = 'PIN code';
$string['pincodeinvalid'] = 'Use a 6 digit Indian PIN code.';
$string['pluginname'] = 'Core dataset setup';
$string['principal'] = 'Principal';
$string['principal_username'] = 'Principal username';
$string['principalnotfound'] = 'No active Moodle user exists with this principal username.';
$string['privacy:metadata:tool_datasetup_school'] = 'Stores school master setup records used by site administrators.';
$string['privacy:metadata:tool_datasetup_school:email'] = 'The school contact email address.';
$string['privacy:metadata:tool_datasetup_school:principal_username'] = 'The username of the principal user linked to the school setup.';
$string['privacy:metadata:tool_datasetup_school:usermodified'] = 'The user who last modified the school setup record.';
$string['privacy:metadata:tool_datasetup_module'] = 'Stores workbook sheet module definitions used by site administrators.';
$string['privacy:metadata:tool_datasetup_record'] = 'Stores rows imported or manually created for setup modules.';
$string['privacy:metadata:tool_datasetup_record:row_data'] = 'The row values for a setup module.';
$string['privacy:metadata:tool_datasetup_record:usermodified'] = 'The user who last modified the setup row.';
$string['purpose'] = 'Purpose';
$string['recordsx'] = '{$a} records';
$string['reference'] = 'Reference';
$string['referencemodules'] = 'Reference modules';
$string['replaceexisting'] = 'Replace existing rows for this module';
$string['requiredtokeninvalid'] = 'Use 2 to 40 uppercase letters, numbers or underscores.';
$string['required'] = 'Required';
$string['rowsperpage'] = 'Rows per page';
$string['rowdeleted'] = 'Setup row deleted.';
$string['rowkey'] = 'Row key';
$string['rowsaved'] = 'Setup row saved.';
$string['school_code'] = 'School code';
$string['school_name'] = 'School name';
$string['school_type'] = 'School type';
$string['schooldetails'] = 'School details';
$string['schoolsaved'] = 'School setup saved.';
$string['schooldeleted'] = 'School setup deleted.';
$string['searchplaceholder'] = 'Search school, trust, city, email or year';
$string['searchmodules'] = 'Search sheets, CSV files, purpose or group';
$string['searchrecords'] = 'Search records in this module';
$string['selectavalue'] = 'Select a value';
$string['selectstate'] = 'All states';
$string['selectyear'] = 'All academic years';
$string['siteadminsonly'] = 'Only site administrators can manage core dataset setup.';
$string['state'] = 'State';
$string['status'] = 'Status';
$string['standarddefault'] = 'Standard default';
$string['standarddefaulthelp'] = 'This module is prefilled from the bundled CLI-pack defaults when it has no rows. Existing administrator changes are not overwritten.';
$string['summaryactive'] = 'Active setups';
$string['summarymodulegroups'] = 'Module groups';
$string['summarymodules'] = 'Modules';
$string['summaryrecords'] = 'Rows';
$string['summaryrequiredmodules'] = 'Mandatory modules';
$string['summaryschools'] = 'Schools';
$string['summarystates'] = 'States';
$string['summaryyears'] = 'Academic years';
$string['trust_code'] = 'Trust code';
$string['trust_name'] = 'Trust name';
$string['udise_code'] = 'UDISE code';
$string['udiseinvalid'] = 'UDISE code must be exactly 11 digits when provided.';
$string['valid'] = 'Valid';
$string['validation'] = 'Validation';
$string['validationboolean'] = 'Use 1/0, yes/no or true/false.';
$string['validationcode'] = 'Use letters, numbers, underscore, hyphen, period or colon only.';
$string['validationcsvempty'] = 'Upload a CSV file that contains a header row and at least one data row.';
$string['validationcsvextension'] = 'Upload a file with the .csv extension.';
$string['validationcsvrequired'] = 'Choose a CSV file to import.';
$string['validationdate'] = 'Use YYYY-MM-DD format.';
$string['validationemail'] = 'Enter a valid email address.';
$string['validationnumber'] = 'Use a whole number.';
$string['validationpercent'] = 'Use a number between 0 and 100.';
$string['validationphone'] = 'Use 10 to 15 digits.';
$string['validationpincode'] = 'Use a 6 digit Indian PIN code.';
$string['validationreference'] = 'Select a value from the configured master list.';
$string['validationrequired'] = 'This value is required by the workbook contract.';
$string['validationurl'] = 'Enter a valid HTTP or HTTPS URL.';
$string['viewcolumncontract'] = 'Column contract';
$string['website'] = 'Website';
$string['websiteinvalid'] = 'Enter a valid HTTP or HTTPS URL.';
$string['template'] = 'Template';
$string['workbookdriven'] = 'Workbook driven';
$string['yes'] = 'Yes';
