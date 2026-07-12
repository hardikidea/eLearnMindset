const CONFIG = {
  schoolCode: 'DPS',
  schoolName: 'Drona Public School',
  boardCode: 'GSEB',
  city: 'Ahmedabad',
  country: 'IN',
  timezone: 'Asia/Kolkata',
  defaultLanguage: 'en',
  studentPassword: 'DronaStudent2026!',
  parentPassword: 'DronaParent2026!',
  studentEmailDomain: 'students.dronapublicschool.example',
  parentRole: 'parent',
  studentSheet: '20_users_students',
  parentSheet: '21_users_parents',
  parentLinkSheet: '24_parent_links',
  errorSheet: '_intake_errors',
  auditSheet: '_intake_audit',
  headerRow: 5,
  firstDataRow: 7
};

function installStudentRegistrationTrigger() {
  const ss = SpreadsheetApp.getActive();
  ScriptApp.getProjectTriggers().forEach(trigger => {
    if (trigger.getHandlerFunction() === 'onStudentRegistrationSubmit') {
      ScriptApp.deleteTrigger(trigger);
    }
  });
  ScriptApp.newTrigger('onStudentRegistrationSubmit')
    .forSpreadsheet(ss)
    .onFormSubmit()
    .create();
}

function onStudentRegistrationSubmit(e) {
  const ss = SpreadsheetApp.getActive();
  const data = normalizeNamedValues(e.namedValues);
  normalizeDropdownCodes(data);
  if (!isStudentRegistrationSubmission(data)) {
    return;
  }

  try {
    validateSubmission(data);
    const result = createStudentParentRegistration(ss, data);
    logAudit(ss, data, result);
  } catch (err) {
    logError(ss, data, err.message);
    throw err;
  }
}

function isStudentRegistrationSubmission(data) {
  return Boolean(data.student_firstname || data.student_lastname || data.parent_email || data.parent_mobile);
}

function processLatestFormResponse() {
  const ss = SpreadsheetApp.getActive();
  const formSheet = ss.getSheets().find(sheet =>
    sheet.getName().toLowerCase().includes('form responses')
  );
  if (!formSheet) {
    throw new Error('Form response sheet not found.');
  }
  const lastRow = formSheet.getLastRow();
  const lastCol = formSheet.getLastColumn();
  if (lastRow < 2) {
    throw new Error('No form response found.');
  }
  const headers = formSheet.getRange(1, 1, 1, lastCol).getValues()[0];
  const values = formSheet.getRange(lastRow, 1, 1, lastCol).getValues()[0];
  const namedValues = {};
  headers.forEach((header, index) => {
    namedValues[header] = [String(values[index] || '')];
  });
  onStudentRegistrationSubmit({ namedValues });
}

function debugLatestResponseKeys() {
  const ss = SpreadsheetApp.getActive();
  const formSheet = ss.getSheets().find(sheet =>
    sheet.getName().toLowerCase().includes('form responses')
  );
  if (!formSheet) {
    throw new Error('Form response sheet not found.');
  }
  const lastRow = formSheet.getLastRow();
  const lastCol = formSheet.getLastColumn();
  const headers = formSheet.getRange(1, 1, 1, lastCol).getValues()[0];
  const values = formSheet.getRange(lastRow, 1, 1, lastCol).getValues()[0];
  headers.forEach((header, index) => {
    Logger.log(`${header} -> ${canonicalKey(header)} = ${values[index]}`);
  });
}

function createStudentParentRegistration(ss, data) {
  const studentSheet = ss.getSheetByName(CONFIG.studentSheet);
  const parentSheet = ss.getSheetByName(CONFIG.parentSheet);
  const parentLinkSheet = ss.getSheetByName(CONFIG.parentLinkSheet);
  if (!studentSheet || !parentSheet || !parentLinkSheet) {
    throw new Error('One or more target sheets are missing: 20_users_students, 21_users_parents, 24_parent_links.');
  }

  const studentSeq = nextSequence(studentSheet, 'username', 'dps.stu.');
  const parentInfo = findOrCreateParent(parentSheet, data);
  const startYear = data.academic_year.split('-')[0];
  const shortYear = startYear.slice(-2);
  const studentUsername = `dps.stu.${pad5(studentSeq)}`;
  const studentId = `${CONFIG.schoolCode}${shortYear}-${pad5(studentSeq)}`;
  const cohort = `${CONFIG.schoolCode}-${startYear}-${CONFIG.boardCode}-${data.medium_code}-${data.grade_code}-${data.stream_code}-${data.division_code}`;

  appendByHeader(studentSheet, {
    username: studentUsername,
    password: CONFIG.studentPassword,
    firstname: data.student_firstname,
    lastname: data.student_lastname,
    email: `${studentUsername}@${CONFIG.studentEmailDomain}`,
    auth: 'manual',
    city: data.current_city || CONFIG.city,
    country: CONFIG.country,
    timezone: CONFIG.timezone,
    lang: languageFromMedium(data.medium_code),
    institution: CONFIG.schoolName,
    department: `${data.medium_code}-${data.grade_code}-${data.stream_code}-${data.division_code}`,
    idnumber: studentId,
    phone1: data.parent_mobile,
    phone2: data.emergency_contact_mobile,
    address: data.current_address_line1,
    cohort1: cohort,
    board_code: CONFIG.boardCode,
    school_code: CONFIG.schoolCode,
    medium_code: data.medium_code,
    grade_code: data.grade_code,
    stream_code: data.stream_code,
    division_code: data.division_code,
    profile_field_admission_no: studentId,
    profile_field_roll_no: data.student_roll_no || '',
    profile_field_student_gr_no: `GR-${CONFIG.schoolCode}-${startYear}-${pad5(studentSeq)}`,
    profile_field_birth_date: data.student_birth_date,
    profile_field_gender: data.student_gender,
    profile_field_blood_group: data.student_blood_group,
    profile_field_religion: data.student_religion,
    profile_field_category: data.student_category,
    profile_field_caste: data.student_caste,
    profile_field_nationality: data.student_nationality || 'Indian',
    profile_field_mother_tongue: data.student_mother_tongue,
    profile_field_admission_date: data.admission_date,
    profile_field_apaar_id: data.student_apaar_id,
    profile_field_udise_student_code: data.student_udise_code,
    profile_field_saral_id: data.student_saral_id,
    profile_field_aadhaar_last4: data.student_aadhaar_last4,
    profile_field_aadhaar_masked: maskAadhaar(data.student_aadhaar_last4),
    profile_field_aadhaar_consent: yesNoToOneZero(data.student_aadhaar_consent),
    profile_field_house: data.student_house,
    profile_field_transport_required: yesNoToOneZero(data.transport_required),
    profile_field_bus_route: data.bus_route,
    profile_field_pickup_point: data.pickup_point,
    profile_field_rte_category: yesNoToOneZero(data.rte_category),
    profile_field_bpl: yesNoToOneZero(data.bpl),
    profile_field_disability_status: data.disability_status || 'None',
    profile_field_medical_conditions: data.medical_conditions,
    profile_field_allergies: data.allergies,
    profile_field_doctor_name: data.doctor_name,
    profile_field_doctor_phone: data.doctor_phone,
    profile_field_sibling_admission_no: data.sibling_admission_no,
    profile_field_current_address_line1: data.current_address_line1,
    profile_field_current_address_line2: data.current_address_line2,
    profile_field_current_city: data.current_city,
    profile_field_current_taluka: data.current_taluka,
    profile_field_current_district: data.current_district,
    profile_field_current_state: data.current_state,
    profile_field_current_pincode: data.current_pincode,
    profile_field_permanent_address_line1: data.permanent_address_line1,
    profile_field_permanent_address_line2: data.permanent_address_line2,
    profile_field_permanent_city: data.permanent_city,
    profile_field_permanent_taluka: data.permanent_taluka,
    profile_field_permanent_district: data.permanent_district,
    profile_field_permanent_state: data.permanent_state,
    profile_field_permanent_pincode: data.permanent_pincode,
    profile_field_permanent_address_same: yesNoToOneZero(data.permanent_same),
    profile_field_father_name: data.father_name,
    profile_field_father_mobile: data.father_mobile,
    profile_field_father_email: data.father_email,
    profile_field_father_occupation: data.father_occupation,
    profile_field_father_qualification: data.father_qualification,
    profile_field_mother_name: data.mother_name,
    profile_field_mother_mobile: data.mother_mobile,
    profile_field_mother_email: data.mother_email,
    profile_field_mother_occupation: data.mother_occupation,
    profile_field_mother_qualification: data.mother_qualification,
    profile_field_guardian_name: data.guardian_name,
    profile_field_guardian_mobile: data.guardian_mobile,
    profile_field_guardian_email: data.guardian_email,
    profile_field_guardian_occupation: data.guardian_occupation,
    profile_field_guardian_qualification: data.guardian_qualification,
    profile_field_emergency_contact_name: data.emergency_contact_name,
    profile_field_emergency_contact_relation: data.emergency_contact_relation,
    profile_field_emergency_contact_mobile: data.emergency_contact_mobile,
    profile_field_emergency_contact_alt_mobile: data.emergency_alt_mobile,
    profile_field_current_academic_year: data.academic_year,
    profile_field_current_board_code: CONFIG.boardCode,
    profile_field_current_school_code: CONFIG.schoolCode,
    profile_field_current_medium_code: data.medium_code,
    profile_field_current_grade_code: data.grade_code,
    profile_field_current_stream_code: data.stream_code,
    profile_field_current_division_code: data.division_code,
    profile_field_student_status: 'ACTIVE'
  });

  appendByHeader(parentLinkSheet, {
    parent_username: parentInfo.username,
    student_username: studentUsername,
    relationship: data.parent_type,
    role_shortname: CONFIG.parentRole,
    allow_grade_view: '1',
    allow_activity_report_view: '1',
    notes: parentInfo.created ? 'Google Form registration, new parent.' : 'Google Form registration, existing parent reused.'
  });

  return {
    student_username: studentUsername,
    parent_username: parentInfo.username,
    parent_created: parentInfo.created,
    cohort1: cohort
  };
}

function findOrCreateParent(parentSheet, data) {
  const rows = getRowsByHeader(parentSheet);
  const normalizedEmail = String(data.parent_email || '').toLowerCase();
  const existing = rows.find(row =>
    String(row.email || '').toLowerCase() === normalizedEmail ||
    String(row.phone1 || '') === String(data.parent_mobile || '')
  );
  if (existing && existing.username) {
    return { username: existing.username, created: false };
  }

  const seq = nextSequence(parentSheet, 'username', 'dps.par.');
  const username = `dps.par.${pad5(seq)}`;
  appendByHeader(parentSheet, {
    username: username,
    password: CONFIG.parentPassword,
    firstname: data.parent_firstname,
    lastname: data.parent_lastname,
    email: data.parent_email,
    auth: 'manual',
    city: data.current_city || CONFIG.city,
    country: CONFIG.country,
    timezone: CONFIG.timezone,
    lang: data.parent_preferred_language || CONFIG.defaultLanguage,
    institution: CONFIG.schoolName,
    department: 'Parent',
    idnumber: `${CONFIG.schoolCode}-PAR-${pad5(seq)}`,
    phone1: data.parent_mobile,
    address: data.current_address_line1,
    profile_field_parent_type: data.parent_type,
    profile_field_parent_occupation: data.parent_occupation,
    profile_field_parent_qualification: data.parent_qualification,
    profile_field_parent_annual_income: data.parent_annual_income,
    profile_field_preferred_language: data.parent_preferred_language,
    profile_field_consent_student_data: '1',
    profile_field_aadhaar_last4: data.parent_aadhaar_last4,
    profile_field_aadhaar_masked: maskAadhaar(data.parent_aadhaar_last4),
    profile_field_aadhaar_consent: yesNoToOneZero(data.parent_aadhaar_consent)
  });
  return { username: username, created: true };
}

function normalizeNamedValues(namedValues) {
  const map = {};
  Object.keys(namedValues).forEach(title => {
    const value = Array.isArray(namedValues[title]) ? namedValues[title][0] : namedValues[title];
    map[canonicalKey(title)] = String(value || '').trim();
  });
  return map;
}

function canonicalKey(title) {
  const normalized = title.toLowerCase().trim()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_|_$/g, '');

  const aliases = {
    student_first_name: 'student_firstname',
    student_last_name: 'student_lastname',
    date_of_birth: 'student_birth_date',
    gender: 'student_gender',
    blood_group: 'student_blood_group',
    religion: 'student_religion',
    category: 'student_category',
    caste: 'student_caste',
    nationality: 'student_nationality',
    mother_tongue: 'student_mother_tongue',
    academic_year: 'academic_year',
    board: 'board_code',
    medium: 'medium_code',
    grade: 'grade_code',
    stream: 'stream_code',
    division: 'division_code',
    admission_date: 'admission_date',
    roll_number: 'student_roll_no',
    house: 'student_house',
    student_aadhaar_last_4_digits: 'student_aadhaar_last4',
    student_aadhaar_consent: 'student_aadhaar_consent',
    apaar_id: 'student_apaar_id',
    udise_student_code: 'student_udise_code',
    saral_id: 'student_saral_id',
    current_address_line_1: 'current_address_line1',
    current_address_line_2: 'current_address_line2',
    current_city: 'current_city',
    current_taluka: 'current_taluka',
    current_district: 'current_district',
    current_state: 'current_state',
    current_pin_code: 'current_pincode',
    permanent_address_same_as_current: 'permanent_same',
    permanent_address_line_1: 'permanent_address_line1',
    permanent_address_line_2: 'permanent_address_line2',
    permanent_city: 'permanent_city',
    permanent_taluka: 'permanent_taluka',
    permanent_district: 'permanent_district',
    permanent_state: 'permanent_state',
    permanent_pin_code: 'permanent_pincode',
    parent_account_type: 'parent_type',
    parent_first_name: 'parent_firstname',
    parent_last_name: 'parent_lastname',
    parent_mobile_number: 'parent_mobile',
    parent_email: 'parent_email',
    parent_occupation: 'parent_occupation',
    parent_qualification: 'parent_qualification',
    parent_annual_income: 'parent_annual_income',
    preferred_language: 'parent_preferred_language',
    parent_aadhaar_last_4_digits: 'parent_aadhaar_last4',
    parent_aadhaar_consent: 'parent_aadhaar_consent',
    father_name: 'father_name',
    father_mobile: 'father_mobile',
    father_email: 'father_email',
    father_occupation: 'father_occupation',
    father_qualification: 'father_qualification',
    mother_name: 'mother_name',
    mother_mobile: 'mother_mobile',
    mother_email: 'mother_email',
    mother_occupation: 'mother_occupation',
    mother_qualification: 'mother_qualification',
    guardian_name: 'guardian_name',
    guardian_mobile: 'guardian_mobile',
    guardian_email: 'guardian_email',
    guardian_occupation: 'guardian_occupation',
    guardian_qualification: 'guardian_qualification',
    emergency_contact_name: 'emergency_contact_name',
    emergency_contact_relation: 'emergency_contact_relation',
    emergency_mobile: 'emergency_contact_mobile',
    alternate_emergency_mobile: 'emergency_alt_mobile',
    medical_conditions: 'medical_conditions',
    allergies: 'allergies',
    family_doctor_name: 'doctor_name',
    doctor_phone: 'doctor_phone',
    transport_required: 'transport_required',
    bus_route: 'bus_route',
    pickup_point: 'pickup_point',
    rte_category: 'rte_category',
    bpl_category: 'bpl',
    disability_status: 'disability_status',
    sibling_admission_number: 'sibling_admission_no',
    i_consent_to_school_storing_student_and_parent_data_for_lms_and_school_operations: 'consent_student_data',
    i_confirm_all_details_are_correct: 'declaration_correct',
    submitted_by_name: 'submitted_by_name',
    submitted_by_mobile: 'submitted_by_mobile'
  };
  return aliases[normalized] || normalized;
}

function normalizeDropdownCodes(data) {
  data.board_code = firstCode(data.board_code);
  data.medium_code = firstCode(data.medium_code);
  data.grade_code = firstCode(data.grade_code);
  data.stream_code = firstCode(data.stream_code);
  data.division_code = firstCode(data.division_code);
  data.student_house = firstCode(data.student_house);
  data.parent_preferred_language = firstCode(data.parent_preferred_language);
}

function firstCode(value) {
  return String(value || '').split(' - ')[0].trim();
}

function validateSubmission(data) {
  required(data, [
    'student_firstname',
    'student_lastname',
    'student_birth_date',
    'student_gender',
    'academic_year',
    'medium_code',
    'grade_code',
    'stream_code',
    'division_code',
    'admission_date',
    'current_address_line1',
    'current_city',
    'current_district',
    'current_state',
    'current_pincode',
    'parent_type',
    'parent_firstname',
    'parent_lastname',
    'parent_mobile',
    'parent_email',
    'emergency_contact_name',
    'emergency_contact_relation',
    'emergency_contact_mobile',
    'consent_student_data',
    'declaration_correct'
  ]);

  if (data.board_code && data.board_code !== CONFIG.boardCode) {
    throw new Error(`Board must be ${CONFIG.boardCode}.`);
  }
  regex(data.parent_mobile, /^[6-9][0-9]{9}$/, 'Parent mobile must be a valid 10 digit Indian mobile number.');
  regex(data.emergency_contact_mobile, /^[6-9][0-9]{9}$/, 'Emergency mobile must be a valid 10 digit Indian mobile number.');
  regex(data.current_pincode, /^[1-9][0-9]{5}$/, 'Current PIN code must be 6 digits.');
  regex(data.parent_email, /^[^\s@]+@[^\s@]+\.[^\s@]+$/, 'Parent email is invalid.');

  optionalRegex(data.submitted_by_mobile, /^[6-9][0-9]{9}$/, 'Submitted-by mobile must be a valid 10 digit Indian mobile number.');
  optionalRegex(data.father_mobile, /^[6-9][0-9]{9}$/, 'Father mobile must be a valid 10 digit Indian mobile number.');
  optionalRegex(data.mother_mobile, /^[6-9][0-9]{9}$/, 'Mother mobile must be a valid 10 digit Indian mobile number.');
  optionalRegex(data.guardian_mobile, /^[6-9][0-9]{9}$/, 'Guardian mobile must be a valid 10 digit Indian mobile number.');
  optionalRegex(data.doctor_phone, /^[0-9]{6,15}$/, 'Doctor phone must contain 6 to 15 digits.');
  optionalRegex(data.permanent_pincode, /^[1-9][0-9]{5}$/, 'Permanent PIN code must be 6 digits.');
  optionalRegex(data.student_aadhaar_last4, /^[0-9]{4}$/, 'Student Aadhaar last 4 must be 4 digits.');
  optionalRegex(data.parent_aadhaar_last4, /^[0-9]{4}$/, 'Parent Aadhaar last 4 must be 4 digits.');
  optionalRegex(data.father_email, /^[^\s@]+@[^\s@]+\.[^\s@]+$/, 'Father email is invalid.');
  optionalRegex(data.mother_email, /^[^\s@]+@[^\s@]+\.[^\s@]+$/, 'Mother email is invalid.');
  optionalRegex(data.guardian_email, /^[^\s@]+@[^\s@]+\.[^\s@]+$/, 'Guardian email is invalid.');

  const primaryGrades = ['STD01', 'STD02', 'STD03', 'STD04', 'STD05', 'STD06', 'STD07', 'STD08', 'STD09', 'STD10'];
  const higherGrades = ['STD11', 'STD12'];
  if (primaryGrades.includes(data.grade_code) && data.stream_code !== 'GEN') {
    throw new Error('STD01 to STD10 must use stream GEN.');
  }
  if (higherGrades.includes(data.grade_code) && !['SCI', 'COM', 'ARTS'].includes(data.stream_code)) {
    throw new Error('STD11 and STD12 must use SCI, COM, or ARTS.');
  }
}

function required(data, fields) {
  fields.forEach(field => {
    if (!String(data[field] || '').trim()) {
      throw new Error(`Missing required field: ${field}`);
    }
  });
}

function regex(value, pattern, message) {
  if (!pattern.test(String(value || '').trim())) {
    throw new Error(message);
  }
}

function optionalRegex(value, pattern, message) {
  if (String(value || '').trim() && !pattern.test(String(value || '').trim())) {
    throw new Error(message);
  }
}

function getHeaders(sheet) {
  return sheet.getRange(CONFIG.headerRow, 1, 1, sheet.getLastColumn()).getValues()[0];
}

function getRowsByHeader(sheet) {
  const headers = getHeaders(sheet);
  const lastRow = sheet.getLastRow();
  if (lastRow < CONFIG.firstDataRow) {
    return [];
  }
  return sheet.getRange(CONFIG.firstDataRow, 1, lastRow - CONFIG.firstDataRow + 1, headers.length)
    .getValues()
    .filter(row => row.some(cell => String(cell || '').trim()))
    .map(row => Object.fromEntries(headers.map((header, index) => [header, row[index]])));
}

function appendByHeader(sheet, values) {
  const headers = getHeaders(sheet);
  const row = headers.map(header => values[header] || '');
  const keyColumnName = headers.includes('username') ? 'username' : headers[0];
  const keyColumn = headers.indexOf(keyColumnName) + 1;
  const targetRow = nextEmptyRowByColumn(sheet, keyColumn);
  sheet.getRange(targetRow, 1, 1, row.length).setValues([row]);
}

function nextEmptyRowByColumn(sheet, column) {
  const lastRow = Math.max(sheet.getLastRow(), CONFIG.firstDataRow);
  const count = lastRow - CONFIG.firstDataRow + 1;
  const values = sheet.getRange(CONFIG.firstDataRow, column, count, 1).getValues().flat();
  const emptyIndex = values.findIndex(value => String(value || '').trim() === '');
  return emptyIndex >= 0 ? CONFIG.firstDataRow + emptyIndex : lastRow + 1;
}

function nextSequence(sheet, usernameField, prefix) {
  const rows = getRowsByHeader(sheet);
  let max = 0;
  rows.forEach(row => {
    const username = String(row[usernameField] || '');
    if (username.startsWith(prefix)) {
      max = Math.max(max, Number(username.split('.').pop()));
    }
  });
  return max + 1;
}

function pad5(value) {
  return String(value).padStart(5, '0');
}

function yesNoToOneZero(value) {
  const normalized = String(value || '').toLowerCase();
  return normalized.startsWith('y') || normalized.includes('agree') || normalized.includes('confirm') ? '1' : '0';
}

function maskAadhaar(last4) {
  return last4 ? `XXXX-XXXX-${last4}` : '';
}

function languageFromMedium(medium) {
  return { GUJ: 'gu', ENG: 'en', HIN: 'hi' }[medium] || CONFIG.defaultLanguage;
}

function logError(ss, data, message) {
  let sheet = ss.getSheetByName(CONFIG.errorSheet);
  if (!sheet) {
    sheet = ss.insertSheet(CONFIG.errorSheet);
    sheet.appendRow(['timestamp', 'error', 'payload']);
  }
  sheet.appendRow([new Date(), message, JSON.stringify(data)]);
}

function logAudit(ss, data, result) {
  let sheet = ss.getSheetByName(CONFIG.auditSheet);
  if (!sheet) {
    sheet = ss.insertSheet(CONFIG.auditSheet);
    sheet.appendRow(['timestamp', 'student_username', 'parent_username', 'parent_created', 'cohort1', 'submitted_by_name', 'submitted_by_mobile']);
  }
  sheet.appendRow([
    new Date(),
    result.student_username,
    result.parent_username,
    result.parent_created ? '1' : '0',
    result.cohort1,
    data.submitted_by_name || '',
    data.submitted_by_mobile || ''
  ]);
}
