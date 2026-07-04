import fs from "node:fs/promises";
import path from "node:path";
import { SpreadsheetFile, Workbook } from "@oai/artifact-tool";

const packDir = path.dirname(new URL(import.meta.url).pathname);
const outputDir = path.join(packDir, "outputs", "master-import-workbook");
const outputFile = path.join(outputDir, "eLearnMindset_school_import_master.xlsx");
const maxRows = 10000;

const csvOrder = [
  ["Core master data", "school_master.csv", "School/trust identity and current academic year.", ""],
  ["Core master data", "academic_years.csv", "Academic-year calendar.", "school_master.csv"],
  ["Core master data", "boards.csv", "Education boards.", ""],
  ["Core master data", "mediums.csv", "Teaching mediums/languages.", ""],
  ["Core master data", "grades.csv", "Grade/class master list.", ""],
  ["Core master data", "streams.csv", "Streams such as General, Science, Commerce.", ""],
  ["Core master data", "divisions.csv", "Class divisions used for Moodle groups.", ""],
  ["Core master data", "subjects.csv", "Subject master list.", ""],
  ["Core master data", "grade_subject_matrix.csv", "Allowed board/medium/grade/stream/subject combinations.", "boards.csv, mediums.csv, grades.csv, streams.csv, subjects.csv"],
  ["Moodle structure", "categories.csv", "Moodle course-category hierarchy.", "school_master.csv, boards.csv, mediums.csv, grades.csv, streams.csv"],
  ["Moodle structure", "optional_year_category_model_categories.csv", "Optional year-based category model.", "categories.csv, academic_years.csv"],
  ["Moodle structure", "courses.csv", "Generated Moodle courses.", "categories.csv, grade_subject_matrix.csv"],
  ["Moodle structure", "courses_with_templatecourse_for_moodle_upload.csv", "Moodle native course-upload file using templatecourse.", "courses.csv, master_course_template.csv"],
  ["Moodle structure", "cohorts.csv", "Student enrolment cohorts.", "categories.csv, divisions.csv, academic_years.csv"],
  ["Moodle structure", "groups.csv", "Course-level division groups.", "courses.csv, divisions.csv"],
  ["Users and roles", "user_profile_fields.csv", "Custom Moodle user-profile fields.", ""],
  ["Users and roles", "custom_roles.csv", "Custom Moodle roles.", ""],
  ["Users and roles", "role_guidelines.csv", "Role-assignment guidance.", "custom_roles.csv"],
  ["Users and roles", "users_staff.csv", "Staff users.", "user_profile_fields.csv"],
  ["Users and roles", "users_students.csv", "Student users and school profile fields.", "user_profile_fields.csv, cohorts.csv"],
  ["Users and roles", "users_parents.csv", "Parent users.", "user_profile_fields.csv"],
  ["Users and roles", "cohort_members.csv", "Student-to-cohort membership.", "users_students.csv, cohorts.csv"],
  ["Users and roles", "role_assignments.csv", "Staff/admin role assignments.", "users_staff.csv, custom_roles.csv, categories.csv, courses.csv"],
  ["Users and roles", "parent_links.csv", "Parent-child relationships.", "users_parents.csv, users_students.csv, custom_roles.csv"],
  ["Users and roles", "enrolments.csv", "Cohort sync enrolment mappings.", "courses.csv, cohorts.csv, groups.csv"],
  ["Support", "lookup_values.csv", "General lookup/reference values.", ""],
  ["Support", "validation_rules.csv", "CSV validation rule catalog.", ""],
  ["Support", "source_references.csv", "Source URL/reference catalog.", ""],
  ["Support", "summary.csv", "Pack summary metrics.", ""],
  ["Course template", "master_course_template.csv", "Hidden master course template definition.", ""],
  ["Course template", "course_template_sections.csv", "Template course sections.", "master_course_template.csv"],
  ["Course template", "course_template_activities.csv", "Template activities and chapter gates.", "master_course_template.csv, course_template_sections.csv"],
  ["Course template", "course_template_gradebook.csv", "Template gradebook categories.", "master_course_template.csv"],
  ["Course template", "grade_band_template_adjustments.csv", "Grade-band weight and pedagogy adjustments.", "course_template_gradebook.csv, grades.csv"],
  ["Course template", "subject_template_adjustments.csv", "Subject-specific template suggestions.", "subjects.csv"],
  ["Course template", "completion_tracking_defaults.csv", "Recommended completion settings by activity type.", "course_template_activities.csv"],
  ["Course template", "course_template_application.csv", "Map real courses to the master template.", "courses.csv, master_course_template.csv"],
  ["Course template", "course_template_custom_fields.csv", "Template custom field guidance.", "master_course_template.csv"],
  ["Course template", "course_template_review_checklist.csv", "Template QA checklist.", "master_course_template.csv"],
  ["Course template", "certificate_badge_policy.csv", "Badge/certificate policy.", "course_template_activities.csv"],
  ["Course template", "template_report_access_matrix.csv", "Report access by role.", "custom_roles.csv"],
  ["Course template", "behat_course_template_coverage_mapping.csv", "Template test coverage mapping.", "course_template_activities.csv"],
  ["Course template", "diksha_content_template.csv", "DIKSHA/NCERT/state-board content mapping.", "courses.csv, grade_subject_matrix.csv"],
  ["Academic year rollover", "academic_year_transition_models.csv", "Academic-year transition models.", "academic_years.csv"],
  ["Academic year rollover", "academic_year_promotion_rules.csv", "Promotion rules by grade/stream/result.", "grades.csv, streams.csv"],
  ["Academic year rollover", "academic_year_rollover_checklist.csv", "Rollover checklist.", "academic_year_transition_models.csv"],
  ["Academic year rollover", "promotion_policy.csv", "Promotion policy reference.", "academic_year_promotion_rules.csv"],
  ["Academic year rollover", "promotion_status_codes.csv", "Promotion status codes.", ""],
  ["Academic year rollover", "promotion_validation_rules.csv", "Promotion validation rules.", "promotion_actions.csv"],
  ["Academic year rollover", "student_status_codes.csv", "Student status codes.", "users_students.csv"],
  ["Academic year rollover", "student_academic_history_template.csv", "Student academic history records.", "users_students.csv, academic_years.csv, cohorts.csv"],
  ["Academic year rollover", "student_promotion_plan_2027_2028.csv", "Next-year student promotion plan.", "users_students.csv, cohorts.csv, academic_years.csv"],
  ["Academic year rollover", "promotion_actions.csv", "Executable promotion action rows.", "student_promotion_plan_2027_2028.csv, cohorts.csv"],
  ["Academic year rollover", "next_year_courses_2027_2028.csv", "Prepared next-year courses.", "courses.csv, categories.csv"],
  ["Academic year rollover", "next_year_cohorts_2027_2028.csv", "Prepared next-year cohorts.", "cohorts.csv, categories.csv"],
  ["Academic year rollover", "next_year_groups_2027_2028.csv", "Prepared next-year groups.", "next_year_courses_2027_2028.csv, divisions.csv"],
  ["Academic year rollover", "next_year_enrolments_2027_2028.csv", "Prepared next-year enrolments.", "next_year_courses_2027_2028.csv, next_year_cohorts_2027_2028.csv, next_year_groups_2027_2028.csv"],
  ["Academic year rollover", "alumni_cohorts_2027.csv", "Alumni/exit cohorts.", "cohorts.csv, academic_years.csv"],
  ["Academic year rollover", "archive_policy.csv", "Archive policy.", "academic_years.csv"],
  ["Support", "improvement_backlog.csv", "Future improvements.", ""],
];

const referenceRules = [
  ["grade_subject_matrix.csv", "board_code", "boards.csv", "board_code", "Board must exist before subject matrix rows."],
  ["grade_subject_matrix.csv", "medium_code", "mediums.csv", "medium_code", "Medium must exist before subject matrix rows."],
  ["grade_subject_matrix.csv", "grade_code", "grades.csv", "grade_code", "Grade must exist before subject matrix rows."],
  ["grade_subject_matrix.csv", "stream_code", "streams.csv", "stream_code", "Stream must exist before subject matrix rows."],
  ["grade_subject_matrix.csv", "subject_code", "subjects.csv", "subject_code", "Subject must exist before subject matrix rows."],
  ["categories.csv", "parent_category_code", "categories.csv", "category_code", "Parent category must exist in the same category sheet unless blank."],
  ["courses.csv", "category_code", "categories.csv", "category_code", "Course category must exist."],
  ["courses.csv", "board_code", "boards.csv", "board_code", "Course board must exist."],
  ["courses.csv", "medium_code", "mediums.csv", "medium_code", "Course medium must exist."],
  ["courses.csv", "grade_code", "grades.csv", "grade_code", "Course grade must exist."],
  ["courses.csv", "stream_code", "streams.csv", "stream_code", "Course stream must exist."],
  ["courses.csv", "subject_code", "subjects.csv", "subject_code", "Course subject must exist."],
  ["cohorts.csv", "context_category_code", "categories.csv", "category_code", "Cohort context category must exist."],
  ["cohorts.csv", "division_code", "divisions.csv", "division_code", "Cohort division must exist."],
  ["groups.csv", "course_code", "courses.csv", "course_code", "Group course must exist."],
  ["groups.csv", "course_shortname", "courses.csv", "shortname", "Group course shortname must exist."],
  ["groups.csv", "division_code", "divisions.csv", "division_code", "Group division must exist."],
  ["users_students.csv", "cohort1", "cohorts.csv", "cohort_code", "Student default cohort must exist."],
  ["users_students.csv", "board_code", "boards.csv", "board_code", "Student board must exist."],
  ["users_students.csv", "medium_code", "mediums.csv", "medium_code", "Student medium must exist."],
  ["users_students.csv", "grade_code", "grades.csv", "grade_code", "Student grade must exist."],
  ["users_students.csv", "stream_code", "streams.csv", "stream_code", "Student stream must exist."],
  ["users_students.csv", "division_code", "divisions.csv", "division_code", "Student division must exist."],
  ["cohort_members.csv", "username", "users_students.csv", "username", "Cohort member must be an existing student user."],
  ["cohort_members.csv", "cohort_code", "cohorts.csv", "cohort_code", "Cohort member cohort must exist."],
  ["parent_links.csv", "parent_username", "users_parents.csv", "username", "Parent user must exist."],
  ["parent_links.csv", "student_username", "users_students.csv", "username", "Student user must exist."],
  ["enrolments.csv", "course_code", "courses.csv", "course_code", "Enrolment course must exist."],
  ["enrolments.csv", "course_shortname", "courses.csv", "shortname", "Enrolment course shortname must exist."],
  ["enrolments.csv", "cohort_code", "cohorts.csv", "cohort_code", "Enrolment cohort must exist."],
  ["course_template_application.csv", "course_shortname", "courses.csv", "shortname", "Template application course must exist."],
  ["course_template_application.csv", "template_code", "master_course_template.csv", "template_code", "Template code must exist."],
  ["course_template_activities.csv", "template_code", "master_course_template.csv", "template_code", "Activity template code must exist."],
  ["course_template_sections.csv", "template_code", "master_course_template.csv", "template_code", "Section template code must exist."],
  ["next_year_groups_2027_2028.csv", "course_code", "next_year_courses_2027_2028.csv", "course_code", "Next-year group course must exist."],
  ["next_year_enrolments_2027_2028.csv", "course_code", "next_year_courses_2027_2028.csv", "course_code", "Next-year enrolment course must exist."],
  ["next_year_enrolments_2027_2028.csv", "cohort_code", "next_year_cohorts_2027_2028.csv", "cohort_code", "Next-year enrolment cohort must exist."],
  ["promotion_actions.csv", "username", "users_students.csv", "username", "Promotion action student must exist."],
  ["promotion_actions.csv", "to_cohort_code", "next_year_cohorts_2027_2028.csv", "cohort_code", "Promotion target cohort must exist."],
];

function parseCSV(text) {
  const rows = [];
  let row = [];
  let field = "";
  let inQuotes = false;
  for (let i = 0; i < text.length; i++) {
    const ch = text[i];
    const next = text[i + 1];
    if (inQuotes) {
      if (ch === '"' && next === '"') {
        field += '"';
        i++;
      } else if (ch === '"') {
        inQuotes = false;
      } else {
        field += ch;
      }
    } else if (ch === '"') {
      inQuotes = true;
    } else if (ch === ",") {
      row.push(field);
      field = "";
    } else if (ch === "\n") {
      row.push(field);
      rows.push(row);
      row = [];
      field = "";
    } else if (ch !== "\r") {
      field += ch;
    }
  }
  if (field.length || row.length) {
    row.push(field);
    rows.push(row);
  }
  return rows.filter((r) => r.some((v) => v !== ""));
}

function colLetter(indexZeroBased) {
  let n = indexZeroBased + 1;
  let out = "";
  while (n > 0) {
    const rem = (n - 1) % 26;
    out = String.fromCharCode(65 + rem) + out;
    n = Math.floor((n - 1) / 26);
  }
  return out;
}

function sheetSafeName(index, csvFile) {
  const base = csvFile.replace(/\.csv$/i, "").replace(/[^A-Za-z0-9_]/g, "_");
  const prefix = String(index).padStart(2, "0") + "_";
  return (prefix + base).slice(0, 31);
}

function tableSafeName(sheetName) {
  return ("Tbl_" + sheetName).replace(/[^A-Za-z0-9_]/g, "_").slice(0, 254);
}

function a1Range(rowCount, colCount) {
  return `A1:${colLetter(colCount - 1)}${rowCount}`;
}

function findColumn(csvData, colName) {
  const headers = csvData?.rows?.[0] || [];
  return headers.indexOf(colName);
}

function getRangeFormula(sheetName, colIndex, startRow = 2, endRow = maxRows) {
  const col = colLetter(colIndex);
  return `'${sheetName}'!$${col}$${startRow}:$${col}$${endRow}`;
}

function invalidCountCurrent(csvData, targetCsv, targetField, refCsv, refField) {
  const target = csvData.get(targetCsv);
  const ref = csvData.get(refCsv);
  const targetCol = findColumn(target, targetField);
  const refCol = findColumn(ref, refField);
  if (!target || !ref || targetCol < 0 || refCol < 0) {
    return "Rule setup missing";
  }
  const refValues = new Set((ref.rows || []).slice(1).map((row) => row[refCol]).filter(Boolean));
  let invalid = 0;
  for (const row of (target.rows || []).slice(1)) {
    const value = row[targetCol];
    if (value && !refValues.has(value)) {
      invalid++;
    }
  }
  return invalid;
}

async function readCsvData() {
  const allFiles = (await fs.readdir(packDir)).filter((f) => f.endsWith(".csv")).sort();
  const orderNames = new Set(csvOrder.map(([, f]) => f));
  for (const file of allFiles) {
    if (!orderNames.has(file)) {
      csvOrder.push(["Support", file, "Additional CSV discovered in the pack.", ""]);
    }
  }

  const data = new Map();
  for (const [, file] of csvOrder) {
    const filePath = path.join(packDir, file);
    const text = await fs.readFile(filePath, "utf8");
    const rows = parseCSV(text);
    data.set(file, { file, rows, rowCount: Math.max(0, rows.length - 1), colCount: rows[0]?.length || 0 });
  }
  return data;
}

function styleControlSheet(sheet, rangeAddress) {
  sheet.showGridLines = false;
  sheet.freezePanes.freezeRows(1);
  const used = sheet.getRange(rangeAddress);
  used.format.borders = { preset: "outside", style: "thin", color: "#CBD5E1" };
  sheet.getRange(rangeAddress.split(":")[0] + ":" + rangeAddress.split(":")[1].replace(/\d+$/, "1")).format = {
    fill: "#0B3D5C",
    font: { bold: true, color: "#FFFFFF" },
  };
}

function writeMatrix(sheet, address, matrix) {
  sheet.getRange(address).values = matrix;
}

function createReadme(workbook) {
  const sheet = workbook.worksheets.getOrAdd("00_README");
  sheet.showGridLines = false;
  sheet.getRange("A1:H1").merge();
  sheet.getRange("A1").values = [["eLearn Mindset - School Import Master Workbook"]];
  sheet.getRange("A1").format = {
    fill: "#0B3D5C",
    font: { bold: true, color: "#FFFFFF", size: 16 },
  };
  const rows = [
    ["Purpose", "Single editable Excel control workbook for the Moodle Indian school CLI import CSV pack."],
    ["How to use", "Edit master data and CSV tabs, review Import Sequence and Validation Summary, export the required CSV sheet, then run the CLI dry-run before execute."],
    ["Do not change", "Do not rename CSV sheets or headers unless you also update the CLI scripts and CSV documentation."],
    ["Validation model", "Reference lists and validation formulas point from dependent sheets to their master sheets. Dropdowns are applied for common code columns where possible."],
    ["Privacy rule", "Do not enter full Aadhaar numbers, sensitive medical details, or parent contact data in course content. Use protected profile fields only."],
    ["Generated file", outputFile],
  ];
  writeMatrix(sheet, "A3:B8", rows);
  sheet.getRange("A3:A8").format = { fill: "#E0F2FE", font: { bold: true, color: "#0B3D5C" } };
  sheet.getRange("B3:B8").format.wrapText = true;
  sheet.getRange("A10:H10").merge();
  sheet.getRange("A10").values = [["Recommended workflow"]];
  sheet.getRange("A10").format = { fill: "#FEF3C7", font: { bold: true, color: "#78350F" } };
  writeMatrix(sheet, "A11:B17", [
    ["1", "Update master lookup sheets first: school, academic years, boards, mediums, grades, streams, divisions, subjects."],
    ["2", "Update generated structure sheets: categories, courses, cohorts, groups."],
    ["3", "Update users, cohort members, role assignments, parent links, and enrolments."],
    ["4", "Update template and academic-year rollover sheets only after the baseline structure is correct."],
    ["5", "Check 03_VALIDATION_SUMMARY for unresolved references."],
    ["6", "Export the required sheet to CSV with the original filename."],
    ["7", "Run CLI validation and dry-run before production import."],
  ]);
  sheet.getRange("A11:A17").format = { fill: "#F8FAFC", font: { bold: true } };
  sheet.getRange("A1:H17").format.autofitColumns();
  sheet.getRange("B3:B17").format.columnWidth = 86;
}

function createImportSequence(workbook, csvData, sheetMap) {
  const sheet = workbook.worksheets.getOrAdd("01_IMPORT_SEQUENCE");
  const rows = [["Order", "Phase", "CSV file", "Workbook sheet", "Purpose", "Depends on", "Data rows", "Status"]];
  csvOrder.forEach(([phase, file, purpose, depends], i) => {
    const sheetName = sheetMap.get(file);
    rows.push([
      i + 1,
      phase,
      file,
      sheetName,
      purpose,
      depends,
      `=MAX(0,COUNTA('${sheetName}'!$A$2:$A$${maxRows}))`,
      `=IF(G${i + 2}>=0,"Loaded","Check")`,
    ]);
  });
  writeMatrix(sheet, `A1:H${rows.length}`, rows);
  styleControlSheet(sheet, `A1:H${rows.length}`);
  sheet.getRange(`A2:A${rows.length}`).format.numberFormat = "0";
  sheet.getRange(`G2:G${rows.length}`).format.numberFormat = "#,##0";
  sheet.getRange(`E2:F${rows.length}`).format.wrapText = true;
  sheet.tables.add(`A1:H${rows.length}`, true, "Tbl_Import_Sequence");
  sheet.getRange("A1:H1").format = { fill: "#0B3D5C", font: { bold: true, color: "#FFFFFF" } };
  sheet.getRange("A1:H1").format.autofitColumns();
  sheet.getRange(`E1:F${rows.length}`).format.columnWidth = 48;
}

function createReferenceRules(workbook, csvData, sheetMap) {
  const rulesSheet = workbook.worksheets.getOrAdd("02_REFERENCE_RULES");
  const summarySheet = workbook.worksheets.getOrAdd("03_VALIDATION_SUMMARY");
  const ruleRows = [["Rule #", "Target CSV", "Target field", "Reference CSV", "Reference field", "Purpose", "Formula pattern"]];
  const summaryRows = [["Rule #", "Target CSV", "Target field", "Reference CSV", "Reference field", "Invalid references", "Status"]];
  referenceRules.forEach((rule, i) => {
    const [targetCsv, targetField, refCsv, refField, purpose] = rule;
    const targetSheet = sheetMap.get(targetCsv);
    const refSheet = sheetMap.get(refCsv);
    const targetCol = findColumn(csvData.get(targetCsv), targetField);
    const refCol = findColumn(csvData.get(refCsv), refField);
    const targetRange = targetSheet && targetCol >= 0 ? getRangeFormula(targetSheet, targetCol) : "";
    const refRange = refSheet && refCol >= 0 ? getRangeFormula(refSheet, refCol) : "";
    const patternCell = targetSheet && targetCol >= 0 ? `'${targetSheet}'!${colLetter(targetCol)}2` : "";
    const formula = targetRange && refRange ? `=IF(LEN(${patternCell})=0,"Blank",IF(COUNTIF(${refRange},${patternCell})>0,"OK","Missing"))` : "";
    const invalid = invalidCountCurrent(csvData, targetCsv, targetField, refCsv, refField);

    ruleRows.push([
      i + 1,
      targetCsv,
      targetField,
      refCsv,
      refField,
      purpose,
      formula ? formula.replace(/^=/, "'=") : "Column or sheet not found",
    ]);
    summaryRows.push([
      i + 1,
      targetCsv,
      targetField,
      refCsv,
      refField,
      invalid,
      invalid === 0 ? "OK" : "Check",
    ]);
  });
  writeMatrix(rulesSheet, `A1:G${ruleRows.length}`, ruleRows);
  styleControlSheet(rulesSheet, `A1:G${ruleRows.length}`);
  rulesSheet.getRange(`F2:G${ruleRows.length}`).format.wrapText = true;
  rulesSheet.getRange(`F1:G${ruleRows.length}`).format.columnWidth = 58;
  rulesSheet.tables.add(`A1:G${ruleRows.length}`, true, "Tbl_Reference_Rules");

  writeMatrix(summarySheet, `A1:G${summaryRows.length}`, summaryRows);
  styleControlSheet(summarySheet, `A1:G${summaryRows.length}`);
  summarySheet.getRange(`F2:F${summaryRows.length}`).format.numberFormat = "#,##0";
  summarySheet.tables.add(`A1:G${summaryRows.length}`, true, "Tbl_Validation_Summary");
  summarySheet.getRange("A1:G1").format = { fill: "#0B3D5C", font: { bold: true, color: "#FFFFFF" } };
}

function createReferenceLists(workbook, csvData, sheetMap) {
  const sheet = workbook.worksheets.getOrAdd("04_REF_LISTS");
  const listDefs = [
    ["board_codes", "boards.csv", "board_code"],
    ["medium_codes", "mediums.csv", "medium_code"],
    ["grade_codes", "grades.csv", "grade_code"],
    ["stream_codes", "streams.csv", "stream_code"],
    ["division_codes", "divisions.csv", "division_code"],
    ["subject_codes", "subjects.csv", "subject_code"],
    ["category_codes", "categories.csv", "category_code"],
    ["course_codes", "courses.csv", "course_code"],
    ["course_shortnames", "courses.csv", "shortname"],
    ["cohort_codes", "cohorts.csv", "cohort_code"],
    ["staff_usernames", "users_staff.csv", "username"],
    ["student_usernames", "users_students.csv", "username"],
    ["parent_usernames", "users_parents.csv", "username"],
    ["role_shortnames", "custom_roles.csv", "role_shortname"],
    ["template_codes", "master_course_template.csv", "template_code"],
    ["academic_years", "academic_years.csv", "academic_year"],
    ["next_year_course_codes", "next_year_courses_2027_2028.csv", "course_code"],
    ["next_year_cohort_codes", "next_year_cohorts_2027_2028.csv", "cohort_code"],
  ];

  sheet.showGridLines = false;
  const headers = listDefs.map(([name]) => name);
  writeMatrix(sheet, `A1:${colLetter(headers.length - 1)}1`, [headers]);
  for (let c = 0; c < listDefs.length; c++) {
    const [, csv, field] = listDefs[c];
    const source = csvData.get(csv);
    const sourceSheet = sheetMap.get(csv);
    const col = findColumn(source, field);
    const formulas = [];
    for (let r = 2; r <= maxRows; r++) {
      formulas.push([col >= 0 ? `='${sourceSheet}'!${colLetter(col)}${r}` : ""]);
    }
    sheet.getRangeByIndexes(1, c, formulas.length, 1).formulas = formulas;
  }
  sheet.freezePanes.freezeRows(1);
  sheet.getRange(`A1:${colLetter(headers.length - 1)}1`).format = { fill: "#0B3D5C", font: { bold: true, color: "#FFFFFF" } };
  sheet.getRange(`A1:${colLetter(headers.length - 1)}${maxRows}`).format.autofitColumns();
}

function validationSourceForHeader(header) {
  const normalized = header.toLowerCase();
  if (normalized.includes("board_code")) return "board_codes";
  if (normalized.includes("medium_code")) return "medium_codes";
  if (normalized.includes("grade_code")) return "grade_codes";
  if (normalized.includes("stream_code")) return "stream_codes";
  if (normalized.includes("division_code")) return "division_codes";
  if (normalized.includes("subject_code")) return "subject_codes";
  if (["category_code", "parent_category_code", "context_category_code", "category_idnumber"].includes(normalized)) return "category_codes";
  if (normalized === "course_code" || normalized.endsWith("_course_code")) return "course_codes";
  if (normalized === "course_shortname" || normalized.endsWith("_course_shortname")) return "course_shortnames";
  if (normalized.includes("cohort_code") || normalized === "cohort1") return "cohort_codes";
  if (normalized === "student_username" || normalized === "username" && false) return "student_usernames";
  if (normalized === "parent_username") return "parent_usernames";
  if (normalized === "role_shortname") return "role_shortnames";
  if (normalized === "template_code" || normalized === "course_template_code") return "template_codes";
  if (normalized.includes("academic_year")) return "academic_years";
  return null;
}

function refListRange(listName) {
  const listColumns = {
    board_codes: "A",
    medium_codes: "B",
    grade_codes: "C",
    stream_codes: "D",
    division_codes: "E",
    subject_codes: "F",
    category_codes: "G",
    course_codes: "H",
    course_shortnames: "I",
    cohort_codes: "J",
    staff_usernames: "K",
    student_usernames: "L",
    parent_usernames: "M",
    role_shortnames: "N",
    template_codes: "O",
    academic_years: "P",
    next_year_course_codes: "Q",
    next_year_cohort_codes: "R",
  };
  const col = listColumns[listName];
  return col ? `'04_REF_LISTS'!$${col}$2:$${col}$${maxRows}` : null;
}

function createCsvSheets(workbook, csvData, sheetMap) {
  csvOrder.forEach(([, file], i) => {
    const data = csvData.get(file);
    const sheetName = sheetSafeName(i + 5, file);
    sheetMap.set(file, sheetName);
  });

  csvOrder.forEach(([, file], i) => {
    const data = csvData.get(file);
    const sheetName = sheetMap.get(file);
    const sheet = workbook.worksheets.add(sheetName);
    sheet.showGridLines = false;
    const rows = data.rows.length ? data.rows : [[""]];
    const colCount = Math.max(1, data.colCount);
    const rowCount = rows.length;
    const address = a1Range(rowCount, colCount);
    sheet.getRange(address).values = rows;
    sheet.getRange(address).setNumberFormat("@");
    sheet.freezePanes.freezeRows(1);
    sheet.getRange(`A1:${colLetter(colCount - 1)}1`).format = {
      fill: "#123D5A",
      font: { bold: true, color: "#FFFFFF" },
    };
    sheet.getRange(`A1:${colLetter(colCount - 1)}1`).format.wrapText = true;
    sheet.getRange(address).format.borders = { preset: "outside", style: "thin", color: "#CBD5E1" };
    if (rowCount > 1 && colCount > 0) {
      sheet.tables.add(address, true, tableSafeName(sheetName));
    }
    const headers = rows[0] || [];
    headers.forEach((header, c) => {
      const listName = validationSourceForHeader(header);
      const formula1 = listName ? refListRange(listName) : null;
      if (formula1) {
        sheet.getRangeByIndexes(1, c, Math.min(maxRows - 1, Math.max(100, rowCount + 100)), 1).dataValidation = {
          rule: { type: "list", formula1 },
        };
      }
    });
    sheet.getRange(`A1:${colLetter(Math.min(colCount, 12) - 1)}${Math.min(rowCount, 200)}`).format.autofitColumns();
    if (colCount > 12) {
      sheet.getRangeByIndexes(0, 12, rowCount, colCount - 12).format.columnWidth = 18;
    }
  });
}

async function main() {
  await fs.mkdir(outputDir, { recursive: true });
  const csvData = await readCsvData();
  const workbook = Workbook.create();
  const sheetMap = new Map();

  ["00_README", "01_IMPORT_SEQUENCE", "02_REFERENCE_RULES", "03_VALIDATION_SUMMARY", "04_REF_LISTS"]
    .forEach((name) => workbook.worksheets.add(name));
  createCsvSheets(workbook, csvData, sheetMap);
  createReadme(workbook);
  createImportSequence(workbook, csvData, sheetMap);
  createReferenceRules(workbook, csvData, sheetMap);
  createReferenceLists(workbook, csvData, sheetMap);

  const overview = await workbook.inspect({
    kind: "sheet,table",
    maxChars: 6000,
    tableMaxRows: 3,
    tableMaxCols: 6,
  });
  console.log(overview.ndjson);

  const errors = await workbook.inspect({
    kind: "match",
    searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A",
    options: { useRegex: true, maxResults: 300 },
    summary: "final formula error scan",
  });
  console.log(errors.ndjson);

  await workbook.render({ sheetName: "00_README", range: "A1:H18", scale: 1, format: "png" });
  await workbook.render({ sheetName: "01_IMPORT_SEQUENCE", range: "A1:H30", scale: 1, format: "png" });
  await workbook.render({ sheetName: "03_VALIDATION_SUMMARY", range: "A1:G30", scale: 1, format: "png" });

  const output = await SpreadsheetFile.exportXlsx(workbook);
  await output.save(outputFile);
  await fs.rm(`${outputFile}.inspect.ndjson`, { force: true });
  console.log(`Saved ${outputFile}`);
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
