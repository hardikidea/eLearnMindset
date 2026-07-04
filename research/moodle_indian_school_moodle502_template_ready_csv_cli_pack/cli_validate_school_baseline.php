<?php
// Standalone CSV validator for the Indian school Moodle baseline pack.
// It does not require Moodle bootstrap.
// Run: php cli_validate_school_baseline.php --dir=/path/to/csv-pack

$options = getopt('', ['help', 'dir:', 'limit:']);
if (isset($options['help']) || empty($options['dir'])) {
    echo "Indian school Moodle CSV validator\n\n" .
        "Options:\n" .
        "  --dir=/absolute/path/to/csv-pack    Required CSV directory\n" .
        "  --limit=100                         Optional row limit per file\n";
    exit(0);
}

$dir = rtrim($options['dir'], DIRECTORY_SEPARATOR);
$limit = isset($options['limit']) ? (int)$options['limit'] : 0;
$errors = [];
$warnings = [];

function add_error($message) {
    global $errors;
    $errors[] = $message;
}

function add_warning($message) {
    global $warnings;
    $warnings[] = $message;
}

function read_csv_rows($dir, $file, $requiredheaders = []) {
    global $limit;
    $path = $dir . DIRECTORY_SEPARATOR . $file;
    if (!is_file($path)) {
        add_error("Missing required file: $file");
        return [];
    }
    $handle = fopen($path, 'r');
    if ($handle === false) {
        add_error("Cannot open file: $file");
        return [];
    }
    $headers = fgetcsv($handle);
    if (!$headers) {
        add_error("File has no header: $file");
        fclose($handle);
        return [];
    }
    $headers = array_map('trim', $headers);
    foreach ($requiredheaders as $h) {
        if (!in_array($h, $headers, true)) {
            add_error("$file missing required header: $h");
        }
    }
    $rows = [];
    $line = 1;
    while (($data = fgetcsv($handle)) !== false) {
        $line++;
        if (count($data) === 1 && trim($data[0]) === '') {
            continue;
        }
        $row = ['_line' => $line];
        foreach ($headers as $i => $h) {
            $row[$h] = isset($data[$i]) ? trim($data[$i]) : '';
        }
        $rows[] = $row;
        if ($limit > 0 && count($rows) >= $limit) {
            break;
        }
    }
    fclose($handle);
    return $rows;
}

function index_unique($rows, $key, $label) {
    $index = [];
    foreach ($rows as $row) {
        $value = $row[$key] ?? '';
        if ($value === '') {
            add_error("$label line {$row['_line']} has empty $key");
            continue;
        }
        if (isset($index[$value])) {
            add_error("Duplicate $label $key: $value at line {$row['_line']}");
        }
        $index[$value] = $row;
    }
    return $index;
}

function user_key($username) {
    return strtolower(trim($username));
}

$required = [
    'categories.csv' => ['category_code','parent_category_code','name','idnumber'],
    'courses.csv' => ['course_code','fullname','shortname','idnumber','category_code'],
    'cohorts.csv' => ['cohort_code','name','idnumber','context_category_code'],
    'groups.csv' => ['course_code','course_shortname','group_name','group_idnumber'],
    'enrolments.csv' => ['course_code','course_shortname','cohort_code','role_shortname','group_idnumber'],
    'users_students.csv' => ['username','firstname','lastname','email','cohort1'],
    'users_staff.csv' => ['username','firstname','lastname','email'],
    'users_parents.csv' => ['username','firstname','lastname','email'],
    'cohort_members.csv' => ['username','cohort_code'],
    'custom_roles.csv' => ['role_shortname','role_name','based_on_role','context_levels'],
    'role_assignments.csv' => ['username','role_shortname','context_type','context_identifier'],
    'parent_links.csv' => ['parent_username','student_username','role_shortname'],
];

$rows = [];
foreach ($required as $file => $headers) {
    $rows[$file] = read_csv_rows($dir, $file, $headers);
}

$categories = index_unique($rows['categories.csv'], 'idnumber', 'category');
$categorycodes = index_unique($rows['categories.csv'], 'category_code', 'category');
foreach ($rows['categories.csv'] as $row) {
    $parent = $row['parent_category_code'] ?? '';
    if ($parent !== '' && !isset($categorycodes[$parent]) && !isset($categories[$parent])) {
        add_error("categories.csv line {$row['_line']} references missing parent_category_code: $parent");
    }
}

$coursesByShort = index_unique($rows['courses.csv'], 'shortname', 'course shortname');
$coursesByCode = index_unique($rows['courses.csv'], 'course_code', 'course code');
$coursesById = index_unique($rows['courses.csv'], 'idnumber', 'course idnumber');
foreach ($rows['courses.csv'] as $row) {
    $cat = $row['category_code'] ?? '';
    if (!isset($categorycodes[$cat]) && !isset($categories[$cat])) {
        add_error("courses.csv line {$row['_line']} references missing category_code: $cat");
    }
}

$cohortsById = index_unique($rows['cohorts.csv'], 'idnumber', 'cohort idnumber');
$cohortsByCode = index_unique($rows['cohorts.csv'], 'cohort_code', 'cohort code');

// Include optional next-year and alumni cohorts for promotion reference checks.
$optionalPromotionCohortFiles = ['next_year_cohorts_2027_2028.csv', 'alumni_cohorts_2027.csv'];
foreach ($optionalPromotionCohortFiles as $optionalFile) {
    $optionalPath = $dir . DIRECTORY_SEPARATOR . $optionalFile;
    if (is_file($optionalPath)) {
        $optionalRows = read_csv_rows($dir, $optionalFile, ['cohort_code','idnumber']);
        foreach ($optionalRows as $orow) {
            if (!empty($orow['idnumber'])) {
                $cohortsById[$orow['idnumber']] = $orow;
            }
            if (!empty($orow['cohort_code'])) {
                $cohortsByCode[$orow['cohort_code']] = $orow;
            }
        }
    }
}
foreach ($rows['cohorts.csv'] as $row) {
    $ctx = $row['context_category_code'] ?? '';
    if ($ctx !== '' && !isset($categorycodes[$ctx]) && !isset($categories[$ctx])) {
        add_error("cohorts.csv line {$row['_line']} references missing context_category_code: $ctx");
    }
}

$groups = [];
foreach ($rows['groups.csv'] as $row) {
    $courseCode = $row['course_code'] ?? '';
    $courseShort = $row['course_shortname'] ?? '';
    $gid = $row['group_idnumber'] ?? '';
    if (!isset($coursesByCode[$courseCode]) && !isset($coursesByShort[$courseShort])) {
        add_error("groups.csv line {$row['_line']} references missing course: $courseCode / $courseShort");
    }
    $gkey = $courseCode . '|' . $gid;
    if (isset($groups[$gkey])) {
        add_error("Duplicate group for course: $gkey at line {$row['_line']}");
    }
    $groups[$gkey] = $row;
}

$users = [];
$emailIndex = [];
foreach (['users_students.csv','users_staff.csv','users_parents.csv'] as $file) {
    foreach ($rows[$file] as $row) {
        $username = user_key($row['username'] ?? '');
        if ($username === '') {
            add_error("$file line {$row['_line']} has empty username");
            continue;
        }
        if (isset($users[$username])) {
            add_error("Duplicate username across user CSVs: $username at $file line {$row['_line']}");
        }
        $users[$username] = $row + ['_file' => $file];
        $email = strtolower(trim($row['email'] ?? ''));
        if ($email !== '') {
            if (isset($emailIndex[$email])) {
                add_warning("Duplicate email across user CSVs: $email at $file line {$row['_line']}");
            }
            $emailIndex[$email] = true;
        }
    }
}

$coreRoles = ['manager'=>true,'coursecreator'=>true,'editingteacher'=>true,'teacher'=>true,'student'=>true,'guest'=>true,'user'=>true,'frontpage'=>true];
$roles = $coreRoles;
foreach ($rows['custom_roles.csv'] as $row) {
    if (!empty($row['role_shortname'])) {
        $roles[$row['role_shortname']] = true;
    }
}

foreach ($rows['enrolments.csv'] as $row) {
    $courseCode = $row['course_code'] ?? '';
    $courseShort = $row['course_shortname'] ?? '';
    $cohort = $row['cohort_code'] ?? '';
    $gid = $row['group_idnumber'] ?? '';
    if (!isset($coursesByCode[$courseCode]) && !isset($coursesByShort[$courseShort])) {
        add_error("enrolments.csv line {$row['_line']} references missing course: $courseCode / $courseShort");
    }
    if (!isset($cohortsById[$cohort]) && !isset($cohortsByCode[$cohort])) {
        add_error("enrolments.csv line {$row['_line']} references missing cohort_code: $cohort");
    }
    if ($gid !== '' && !isset($groups[$courseCode . '|' . $gid])) {
        add_error("enrolments.csv line {$row['_line']} references missing group: $courseCode|$gid");
    }
    $role = $row['role_shortname'] ?? '';
    if ($role !== '' && !isset($roles[$role])) {
        add_error("enrolments.csv line {$row['_line']} references missing role_shortname: $role");
    }
}

foreach ($rows['cohort_members.csv'] as $row) {
    $username = user_key($row['username'] ?? '');
    $cohort = $row['cohort_code'] ?? '';
    if (!isset($users[$username])) {
        add_error("cohort_members.csv line {$row['_line']} references missing username: $username");
    }
    if (!isset($cohortsById[$cohort]) && !isset($cohortsByCode[$cohort])) {
        add_error("cohort_members.csv line {$row['_line']} references missing cohort_code: $cohort");
    }
}

foreach ($rows['role_assignments.csv'] as $row) {
    $username = user_key($row['username'] ?? '');
    if (!isset($users[$username])) {
        add_error("role_assignments.csv line {$row['_line']} references missing username: $username");
    }
    $role = $row['role_shortname'] ?? '';
    if ($role !== '' && !isset($roles[$role])) {
        add_error("role_assignments.csv line {$row['_line']} references missing role_shortname: $role");
    }
    $type = strtolower($row['context_type'] ?? '');
    $identifier = $row['context_identifier'] ?? '';
    if ($type === 'category' && !isset($categorycodes[$identifier]) && !isset($categories[$identifier])) {
        add_error("role_assignments.csv line {$row['_line']} references missing category context: $identifier");
    }
    if ($type === 'course' && !isset($coursesByCode[$identifier]) && !isset($coursesByShort[$identifier]) && !isset($coursesById[$identifier])) {
        add_error("role_assignments.csv line {$row['_line']} references missing course context: $identifier");
    }
    if ($type === 'user' && !isset($users[user_key($identifier)])) {
        add_error("role_assignments.csv line {$row['_line']} references missing user context: $identifier");
    }
}

foreach ($rows['parent_links.csv'] as $row) {
    $parent = user_key($row['parent_username'] ?? '');
    $student = user_key($row['student_username'] ?? '');
    if (!isset($users[$parent])) {
        add_error("parent_links.csv line {$row['_line']} references missing parent_username: $parent");
    }
    if (!isset($users[$student])) {
        add_error("parent_links.csv line {$row['_line']} references missing student_username: $student");
    }
    $role = $row['role_shortname'] ?? '';
    if ($role !== '' && !isset($roles[$role])) {
        add_error("parent_links.csv line {$row['_line']} references missing role_shortname: $role");
    }
}

$promotionFile = $dir . DIRECTORY_SEPARATOR . 'promotion_actions.csv';
if (is_file($promotionFile)) {
    $promotionRows = read_csv_rows($dir, 'promotion_actions.csv', ['action','username','from_cohort_code','to_cohort_code']);
    foreach ($promotionRows as $row) {
        $username = user_key($row['username'] ?? '');
        if (!isset($users[$username])) {
            add_warning("promotion_actions.csv line {$row['_line']} references username not present in sample user files: $username");
        }
        $from = $row['from_cohort_code'] ?? '';
        if ($from !== '' && !isset($cohortsById[$from]) && !isset($cohortsByCode[$from])) {
            add_warning("promotion_actions.csv line {$row['_line']} from_cohort_code is not in current cohorts.csv: $from");
        }
        $to = $row['to_cohort_code'] ?? '';
        if ($to !== '' && !isset($cohortsById[$to]) && !isset($cohortsByCode[$to])) {
            add_warning("promotion_actions.csv line {$row['_line']} to_cohort_code not found in current cohorts.csv; create next-year cohorts before executing promotion: $to");
        }
    }
}

echo "CSV validation summary\n";
echo "----------------------\n";
echo "Categories: " . count($categories) . "\n";
echo "Courses: " . count($coursesByShort) . "\n";
echo "Cohorts: " . count($cohortsById) . "\n";
echo "Groups: " . count($groups) . "\n";
echo "Users: " . count($users) . "\n";
echo "Warnings: " . count($warnings) . "\n";
echo "Errors: " . count($errors) . "\n";

if ($warnings) {
    echo "\nWarnings:\n";
    foreach (array_slice($warnings, 0, 50) as $warning) {
        echo "WARN: $warning\n";
    }
    if (count($warnings) > 50) {
        echo "WARN: ... " . (count($warnings) - 50) . " more warnings not shown\n";
    }
}

if ($errors) {
    echo "\nErrors:\n";
    foreach (array_slice($errors, 0, 100) as $error) {
        echo "ERROR: $error\n";
    }
    if (count($errors) > 100) {
        echo "ERROR: ... " . (count($errors) - 100) . " more errors not shown\n";
    }
    exit(2);
}

echo "\nValidation completed without blocking errors.\n";
exit(0);
