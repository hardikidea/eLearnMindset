const CONFIG = {
  schoolCode: 'DPS',
  schoolName: 'Drona Public School',
  boardCode: 'GSEB',
  city: 'Ahmedabad',
  country: 'IN',
  timezone: 'Asia/Kolkata',
  defaultLanguage: 'en',
  staffPassword: 'DronaTeacher2026!',
  staffEmailDomain: 'dronapublicschool.example',
  staffSheet: '19_users_staff',
  roleAssignmentSheet: '23_role_assignments',
  coursesSheet: '12_courses',
  gradeDivisionMatrixSheet: '07_grade_division_matrix',
  errorSheet: '_teacher_intake_errors',
  auditSheet: '_teacher_intake_audit',
  headerRow: 5,
  firstDataRow: 7
};

function installTeacherRegistrationTrigger() {
  const ss = SpreadsheetApp.getActive();
  ScriptApp.getProjectTriggers().forEach(trigger => {
    if (trigger.getHandlerFunction() === 'onTeacherRegistrationSubmit') {
      ScriptApp.deleteTrigger(trigger);
    }
  });
  ScriptApp.newTrigger('onTeacherRegistrationSubmit')
    .forSpreadsheet(ss)
    .onFormSubmit()
    .create();
}

function onTeacherRegistrationSubmit(e) {
  const ss = SpreadsheetApp.getActive();
  const data = normalizeNamedValues(e.namedValues);
  normalizeDropdownCodes(data);
  if (!isTeacherRegistrationSubmission(data)) {
    return;
  }

  try {
    validateTeacherSubmission(data);
    validateTeacherGradeDivisionScope(ss, data);
    const result = createTeacherRegistration(ss, data);
    logAudit(ss, data, result);
  } catch (err) {
    logError(ss, data, err.message);
    throw err;
  }
}

function isTeacherRegistrationSubmission(data) {
  return Boolean(data.teacher_firstname || data.teacher_lastname || data.teacher_email || data.subject_codes);
}

function processLatestTeacherFormResponse() {
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
  onTeacherRegistrationSubmit({ namedValues });
}

function debugLatestTeacherResponseKeys() {
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

function createTeacherRegistration(ss, data) {
  const staffSheet = ss.getSheetByName(CONFIG.staffSheet);
  const roleSheet = ss.getSheetByName(CONFIG.roleAssignmentSheet);
  const coursesSheet = ss.getSheetByName(CONFIG.coursesSheet);
  if (!staffSheet || !roleSheet || !coursesSheet) {
    throw new Error('Missing one or more target sheets: 19_users_staff, 23_role_assignments, 12_courses.');
  }

  const staffRows = getRowsByHeader(staffSheet);
  const existing = staffRows.find(row =>
    String(row.email || '').toLowerCase() === String(data.teacher_email || '').toLowerCase() ||
    String(row.phone1 || '') === String(data.teacher_mobile || '')
  );
  if (existing && existing.username) {
    throw new Error(`Teacher already exists: ${existing.username}. Add role assignments manually if access has changed.`);
  }

  const subjects = splitMulti(data.subject_codes);
  const firstSubject = subjects[0];
  const teacherNumber = nextTeacherNumber(staffRows);
  const username = nextTeacherUsername(staffRows, data.medium_code, firstSubject, teacherNumber);
  const idnumber = `${CONFIG.schoolCode}-TCH-${String(teacherNumber).padStart(3, '0')}`;
  const employeeCode = data.employee_code || idnumber;

  appendByHeader(staffSheet, {
    username: username,
    password: CONFIG.staffPassword,
    firstname: data.teacher_firstname,
    lastname: data.teacher_lastname,
    email: data.teacher_email,
    auth: 'manual',
    city: data.city || CONFIG.city,
    country: CONFIG.country,
    timezone: CONFIG.timezone,
    lang: languageFromMedium(data.medium_code),
    institution: CONFIG.schoolName,
    department: data.department_code || 'ACADEMIC',
    idnumber: idnumber,
    phone1: data.teacher_mobile,
    phone2: data.alternate_mobile,
    address: data.address_line1,
    profile_field_employee_code: employeeCode,
    profile_field_staff_designation: data.designation_code || 'TEACHER',
    profile_field_staff_department: data.department_code || 'ACADEMIC',
    profile_field_staff_joining_date: data.joining_date,
    profile_field_staff_qualification: data.qualification,
    profile_field_staff_type: data.staff_type || 'Teaching',
    profile_field_aadhaar_last4: data.teacher_aadhaar_last4,
    profile_field_aadhaar_masked: maskAadhaar(data.teacher_aadhaar_last4),
    profile_field_aadhaar_consent: yesNoToOneZero(data.teacher_aadhaar_consent)
  });

  const assignments = buildTeacherRoleAssignments(coursesSheet, roleSheet, username, data);
  assignments.forEach(row => appendByHeader(roleSheet, row));

  return {
    username: username,
    idnumber: idnumber,
    role_assignments_created: assignments.length
  };
}

function buildTeacherRoleAssignments(coursesSheet, roleSheet, username, data) {
  const courses = getRowsByHeader(coursesSheet);
  const existingRoles = getRowsByHeader(roleSheet);
  const grades = splitMulti(data.grade_codes);
  const streams = splitMulti(data.stream_codes);
  const subjects = splitMulti(data.subject_codes);
  const role = data.role_shortname || 'editingteacher';

  const matchedCourses = courses.filter(course =>
    String(course.academic_year || '') === data.academic_year &&
    String(course.medium_code || '') === data.medium_code &&
    grades.includes(String(course.grade_code || '')) &&
    streams.includes(String(course.stream_code || '')) &&
    subjects.includes(String(course.subject_code || ''))
  );

  if (!matchedCourses.length) {
    throw new Error('No matching courses found for teacher assignment. Check academic year, medium, grades, streams, and subjects.');
  }

  return matchedCourses
    .map(course => ({
      username: username,
      role_shortname: role,
      context_type: 'course',
      context_identifier: course.course_code,
      notes: `Google Form teacher registration for ${course.shortname || course.course_code}.`
    }))
    .filter(row => !existingRoles.some(existing =>
      String(existing.username || '') === row.username &&
      String(existing.role_shortname || '') === row.role_shortname &&
      String(existing.context_type || '') === row.context_type &&
      String(existing.context_identifier || '') === row.context_identifier
    ));
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
    teacher_first_name: 'teacher_firstname',
    teacher_last_name: 'teacher_lastname',
    teacher_mobile_number: 'teacher_mobile',
    teacher_email: 'teacher_email',
    alternate_mobile_number: 'alternate_mobile',
    employee_code: 'employee_code',
    designation: 'designation_code',
    department: 'department_code',
    staff_type: 'staff_type',
    joining_date: 'joining_date',
    qualification: 'qualification',
    address_line_1: 'address_line1',
    city: 'city',
    teacher_aadhaar_last_4_digits: 'teacher_aadhaar_last4',
    teacher_aadhaar_consent: 'teacher_aadhaar_consent',
    academic_year: 'academic_year',
    medium: 'medium_code',
    grades_to_teach: 'grade_codes',
    streams_to_teach: 'stream_codes',
    subjects_to_teach: 'subject_codes',
    moodle_role: 'role_shortname',
    declaration_teacher_details_are_correct: 'declaration_correct',
    submitted_by_name: 'submitted_by_name',
    submitted_by_mobile: 'submitted_by_mobile'
  };
  return aliases[normalized] || normalized;
}

function normalizeDropdownCodes(data) {
  data.designation_code = firstCode(data.designation_code);
  data.department_code = firstCode(data.department_code);
  data.academic_year = firstCode(data.academic_year);
  data.medium_code = firstCode(data.medium_code);
  data.role_shortname = firstCode(data.role_shortname);
  data.grade_codes = normalizeMultiCodes(data.grade_codes);
  data.stream_codes = normalizeMultiCodes(data.stream_codes);
  data.subject_codes = normalizeMultiCodes(data.subject_codes);
}

function validateTeacherSubmission(data) {
  required(data, [
    'teacher_firstname',
    'teacher_lastname',
    'teacher_mobile',
    'teacher_email',
    'designation_code',
    'department_code',
    'staff_type',
    'joining_date',
    'qualification',
    'address_line1',
    'academic_year',
    'medium_code',
    'grade_codes',
    'stream_codes',
    'subject_codes',
    'role_shortname',
    'declaration_correct'
  ]);

  regex(data.teacher_mobile, /^[6-9][0-9]{9}$/, 'Teacher mobile must be a valid 10 digit Indian mobile number.');
  regex(data.teacher_email, /^[^\s@]+@[^\s@]+\.[^\s@]+$/, 'Teacher email is invalid.');
  optionalRegex(data.alternate_mobile, /^[6-9][0-9]{9}$/, 'Alternate mobile must be a valid 10 digit Indian mobile number.');
  optionalRegex(data.submitted_by_mobile, /^[6-9][0-9]{9}$/, 'Submitted-by mobile must be a valid 10 digit Indian mobile number.');
  optionalRegex(data.teacher_aadhaar_last4, /^[0-9]{4}$/, 'Teacher Aadhaar last 4 must be 4 digits.');

  validateGradeStreamPairs(splitMulti(data.grade_codes), splitMulti(data.stream_codes));
}

function validateGradeStreamPairs(grades, streams) {
  grades.forEach(grade => {
    const expected = expectedStreamForGrade(grade);
    if (expected && !streams.includes(expected)) {
      throw new Error(`${grade} must use stream ${expected}.`);
    }
  });
}

function expectedStreamForGrade(gradeCode) {
  const grade = String(gradeCode || '');
  if (/^PRE[0-9]{2}$/.test(grade) || /^STD0[1-9]$/.test(grade) || grade === 'STD10') {
    return 'GEN';
  }
  if (/^STD1[12]_SCI$/.test(grade)) return 'SCI';
  if (/^STD1[12]_COM$/.test(grade)) return 'COM';
  if (/^STD1[12]_ART$/.test(grade)) return 'ART';
  return '';
}

function validateTeacherGradeDivisionScope(ss, data) {
  const matrixSheet = ss.getSheetByName(CONFIG.gradeDivisionMatrixSheet);
  if (!matrixSheet) {
    throw new Error('Missing 07_grade_division_matrix tab. Run GenerateGradeDivisionMatrix before accepting teacher registrations.');
  }
  const rows = getRowsByHeader(matrixSheet);
  const grades = splitMulti(data.grade_codes);
  const streams = splitMulti(data.stream_codes);

  grades.forEach(grade => {
    streams.forEach(stream => {
      const expected = expectedStreamForGrade(grade);
      if (expected && stream !== expected) {
        return;
      }
      const match = rows.some(row =>
        String(row.academic_year || '') === data.academic_year &&
        String(row.board_code || CONFIG.boardCode) === CONFIG.boardCode &&
        String(row.medium_code || '') === data.medium_code &&
        String(row.grade_code || '') === grade &&
        String(row.stream_code || '') === stream &&
        isActiveValue(row.is_active)
      );
      if (!match) {
        throw new Error(`No active grade/division matrix rows found for ${data.academic_year} ${CONFIG.boardCode} ${data.medium_code} ${grade} ${stream}. Update 07_grade_division_rules and rerun GenerateGradeDivisionMatrix if teachers should be assigned here.`);
      }
    });
  });
}

function isActiveValue(value) {
  const normalized = String(value || '').trim().toLowerCase();
  return normalized === '1' || normalized === 'true' || normalized === 'yes';
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

function nextTeacherNumber(rows) {
  let max = 0;
  rows.forEach(row => {
    const match = String(row.idnumber || '').match(/^DPS-TCH-(\d{3})$/);
    if (match) {
      max = Math.max(max, Number(match[1]));
    }
  });
  return max + 1;
}

function nextTeacherUsername(rows, mediumCode, subjectCode, teacherNumber) {
  const used = new Set(rows.map(row => String(row.username || '')));
  const medium = String(mediumCode || 'gen').toLowerCase();
  const subject = String(subjectCode || `t${teacherNumber}`).toLowerCase();
  const base = `dps.tch.${medium}.${subject}`;
  if (!used.has(base)) {
    return base;
  }
  let candidate = `${base}_${String(teacherNumber).padStart(3, '0')}`;
  let index = 2;
  while (used.has(candidate)) {
    candidate = `${base}_${String(teacherNumber + index).padStart(3, '0')}`;
    index += 1;
  }
  return candidate;
}

function firstCode(value) {
  return String(value || '').split(' - ')[0].trim();
}

function normalizeMultiCodes(value) {
  return splitMulti(value).map(firstCode).join('|');
}

function splitMulti(value) {
  return String(value || '')
    .split(/[|,;]/)
    .map(item => firstCode(item).trim())
    .filter(Boolean);
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
    sheet.appendRow(['timestamp', 'username', 'idnumber', 'role_assignments_created', 'submitted_by_name', 'submitted_by_mobile']);
  }
  sheet.appendRow([
    new Date(),
    result.username,
    result.idnumber,
    result.role_assignments_created,
    data.submitted_by_name || '',
    data.submitted_by_mobile || ''
  ]);
}
