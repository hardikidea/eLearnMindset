<?php
// This file is part of the local Moodle Docker project.

define('CLI_SCRIPT', true);

require(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/testing/generator/component_generator_base.php');
require_once($CFG->libdir . '/testing/generator/module_generator.php');
require_once($CFG->libdir . '/testing/generator/data_generator.php');

global $CFG, $DB;

const DEMO_PASSWORD = 'SchoolDemo2026!';

/**
 * Print a CLI status line.
 */
function demo_out(string $message): void {
    echo $message . PHP_EOL;
}

/**
 * Read a CSV file into associative rows.
 *
 * @return array<int, array<string, string>>
 */
function demo_read_csv(string $path): array {
    if (!is_readable($path)) {
        throw new RuntimeException("Required demo CSV is not readable: {$path}");
    }

    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException("Unable to open demo CSV: {$path}");
    }

    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        throw new RuntimeException("Demo CSV has no header row: {$path}");
    }

    $rows = [];
    while (($values = fgetcsv($handle)) !== false) {
        if ($values === [null] || $values === false) {
            continue;
        }

        $row = [];
        foreach ($headers as $index => $header) {
            $row[$header] = $values[$index] ?? '';
        }
        $rows[] = $row;
    }

    fclose($handle);
    return $rows;
}

/**
 * Return a stable lowercase id-safe slug.
 */
function demo_slug(string $value): string {
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
    $slug = trim((string)$slug, '_');
    return $slug !== '' ? $slug : 'item';
}

/**
 * Build a compact HTML description for a seeded item.
 */
function demo_intro(string $topic, string $purpose): string {
    return '<p><strong>Demo topic:</strong> ' . s($topic) . '</p>' .
        '<p><strong>Purpose:</strong> ' . s($purpose) . '</p>' .
        '<p>This sample item is part of the eLearn Mindset Indian K-12 Moodle demo data package.</p>';
}

/**
 * Ensure a current admin session exists for Moodle APIs that need a user context.
 */
function demo_set_admin_user(): void {
    $admin = get_admin();
    if (!$admin || empty($admin->id)) {
        throw new RuntimeException('No site administrator account was found.');
    }

    \core\session\manager::set_user($admin);
}

/**
 * Create or update a course category by idnumber.
 */
function demo_upsert_category(array $row): stdClass {
    global $DB;

    $parentid = 0;
    if ($row['parent_idnumber'] !== '') {
        $parentid = (int)$DB->get_field('course_categories', 'id', ['idnumber' => $row['parent_idnumber']], MUST_EXIST);
    }

    $existing = $DB->get_record('course_categories', ['idnumber' => $row['idnumber']], '*', IGNORE_MISSING);
    $data = [
        'name' => $row['name'],
        'idnumber' => $row['idnumber'],
        'parent' => $parentid,
        'description' => '<p>eLearn Mindset demo category: ' . s($row['name']) . '</p>',
        'descriptionformat' => FORMAT_HTML,
        'visible' => 1,
    ];

    if ($existing) {
        $category = core_course_category::get($existing->id, MUST_EXIST, true);
        $category->update($data);
        return $DB->get_record('course_categories', ['id' => $existing->id], '*', MUST_EXIST);
    }

    $category = core_course_category::create($data);
    return $DB->get_record('course_categories', ['id' => $category->id], '*', MUST_EXIST);
}

/**
 * Return the id for a seeded category.
 */
function demo_category_id(string $idnumber): int {
    global $DB;
    return (int)$DB->get_field('course_categories', 'id', ['idnumber' => $idnumber], MUST_EXIST);
}

/**
 * Create or update a course by shortname.
 */
function demo_upsert_course(array $course): stdClass {
    global $DB;

    $existing = $DB->get_record('course', ['shortname' => $course['shortname']], '*', IGNORE_MISSING);
    $data = (object)[
        'fullname' => $course['fullname'],
        'shortname' => $course['shortname'],
        'idnumber' => $course['shortname'],
        'category' => $course['category'],
        'summary' => '<p>' . s($course['summary']) . '</p>',
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'numsections' => $course['numsections'] ?? 6,
        'visible' => $course['visible'] ?? 1,
        'enablecompletion' => 1,
    ];

    if ($existing) {
        $data->id = $existing->id;
        update_course($data);
        return get_course($existing->id);
    }

    return create_course($data);
}

/**
 * Create or update a manual user.
 */
function demo_upsert_user(array $row): stdClass {
    global $CFG, $DB;

    $existing = $DB->get_record('user', ['username' => $row['username'], 'deleted' => 0], '*', IGNORE_MISSING);
    if ($existing) {
        $existing->firstname = $row['firstname'];
        $existing->lastname = $row['lastname'];
        $existing->email = $row['email'];
        $existing->city = 'Ahmedabad';
        $existing->country = 'IN';
        $existing->auth = 'manual';
        $existing->confirmed = 1;
        $existing->suspended = 0;
        $existing->timemodified = time();
        $DB->update_record('user', $existing);
        update_internal_user_password($existing, $row['password']);
        return $DB->get_record('user', ['id' => $existing->id], '*', MUST_EXIST);
    }

    $user = (object)[
        'username' => $row['username'],
        'password' => $row['password'],
        'firstname' => $row['firstname'],
        'lastname' => $row['lastname'],
        'email' => $row['email'],
        'auth' => 'manual',
        'confirmed' => 1,
        'suspended' => 0,
        'mnethostid' => $CFG->mnet_localhost_id,
        'city' => 'Ahmedabad',
        'country' => 'IN',
    ];

    $userid = user_create_user($user, true, true);
    return $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
}

/**
 * Ensure a manual enrolment exists and the requested course role is assigned.
 */
function demo_enrol_user(stdClass $user, stdClass $course, string $roleshortname): void {
    global $DB;

    $manual = enrol_get_plugin('manual');
    if (!$manual) {
        throw new RuntimeException('The manual enrolment plugin is not available.');
    }

    $instance = null;
    foreach (enrol_get_instances($course->id, true) as $candidate) {
        if ($candidate->enrol === 'manual') {
            $instance = $candidate;
            break;
        }
    }

    if (!$instance) {
        $manual->add_instance($course);
        foreach (enrol_get_instances($course->id, true) as $candidate) {
            if ($candidate->enrol === 'manual') {
                $instance = $candidate;
                break;
            }
        }
    }

    if (!$instance) {
        throw new RuntimeException('Unable to create manual enrolment instance for ' . $course->shortname);
    }

    $roleid = (int)$DB->get_field('role', 'id', ['shortname' => $roleshortname], MUST_EXIST);
    if (!$DB->record_exists('user_enrolments', ['enrolid' => $instance->id, 'userid' => $user->id])) {
        $manual->enrol_user($instance, $user->id, $roleid);
    }

    $context = context_course::instance($course->id);
    role_assign($roleid, $user->id, $context->id);
}

/**
 * Assign a system role to a user if needed.
 */
function demo_assign_system_role(stdClass $user, string $roleshortname): void {
    global $DB;

    $roleid = (int)$DB->get_field('role', 'id', ['shortname' => $roleshortname], MUST_EXIST);
    role_assign($roleid, $user->id, context_system::instance()->id);
}

/**
 * Test whether an activity module can be created.
 */
function demo_module_available(string $module): bool {
    global $DB;
    return $DB->record_exists('modules', ['name' => $module, 'visible' => 1]);
}

/**
 * Translate a blueprint recommendation into activity module specs.
 *
 * @return array<int, array<string, string>>
 */
function demo_activity_specs(string $recommendation): array {
    $lower = strtolower($recommendation);
    $specs = [];

    if (str_contains($lower, 'page')) {
        $specs[] = ['module' => 'page', 'label' => 'Reading Page'];
    }
    if (str_contains($lower, 'glossary')) {
        $specs[] = ['module' => 'glossary', 'label' => 'Glossary'];
    }
    if (str_contains($lower, 'forum')) {
        $specs[] = ['module' => 'forum', 'label' => 'Discussion Forum'];
    }
    if (str_contains($lower, 'assignment')) {
        $specs[] = ['module' => 'assign', 'label' => 'Assignment'];
    }
    if (str_contains($lower, 'quiz')) {
        $specs[] = ['module' => 'quiz', 'label' => 'Quiz'];
    }
    if (str_contains($lower, 'lesson')) {
        $specs[] = ['module' => 'lesson', 'label' => 'Lesson'];
    }
    if (str_contains($lower, 'question bank')) {
        $specs[] = ['module' => 'page', 'label' => 'Question Bank Practice Plan'];
    }
    if (str_contains($lower, 'h5p')) {
        $specs[] = ['module' => 'h5pactivity', 'label' => $recommendation];
    }

    return $specs ?: [['module' => 'page', 'label' => $recommendation]];
}

/**
 * Pick a bundled Moodle H5P fixture for the activity label.
 */
function demo_h5p_fixture(string $label): string {
    $lower = strtolower($label);

    if (str_contains($lower, 'drag')) {
        return 'h5p/tests/fixtures/drag.h5p';
    }
    if (str_contains($lower, 'memory')) {
        return 'h5p/tests/fixtures/find-the-words.h5p';
    }
    if (str_contains($lower, 'branching')) {
        return 'h5p/tests/fixtures/guess-the-answer.h5p';
    }
    if (str_contains($lower, 'presentation')) {
        return 'h5p/tests/fixtures/multiple-choice-2-6.h5p';
    }

    return 'h5p/tests/fixtures/ipsums.h5p';
}

/**
 * Build a generator record for an activity module.
 */
function demo_module_record(string $module, string $name, string $topic, string $purpose, int $courseid): array {
    $record = [
        'course' => $courseid,
        'name' => $name,
        'intro' => demo_intro($topic, $purpose),
        'introformat' => FORMAT_HTML,
    ];

    if ($module === 'page') {
        $record['content'] = '<h3>' . s($topic) . '</h3>' .
            '<p>This demo resource gives teachers a starting point for classroom explanation, board alignment, and student practice.</p>' .
            '<ul><li>Connect the topic to Indian school examples.</li><li>Add images, worksheets, or textbook excerpts as needed.</li><li>Use the activity below for follow-up practice.</li></ul>';
        $record['contentformat'] = FORMAT_HTML;
    } else if ($module === 'forum') {
        $record['type'] = 'general';
    } else if ($module === 'quiz') {
        $record['grade'] = 20;
        $record['attempts'] = 0;
        $record['questionsperpage'] = 1;
    } else if ($module === 'h5pactivity') {
        $record['packagefilepath'] = demo_h5p_fixture($name);
        $record['grade'] = 20;
    }

    return $record;
}

/**
 * Create one activity module, falling back to a page if an optional module fails.
 */
function demo_create_activity(testing_data_generator $generator, stdClass $course, array $blueprint, array $spec, int $section, int $index): void {
    global $DB;

    $requestedmodule = $spec['module'];
    $module = demo_module_available($requestedmodule) ? $requestedmodule : 'page';
    $label = $spec['label'];
    $idnumber = substr('demo_' . demo_slug($course->shortname . '_' . $blueprint['topic'] . '_' . $label . '_' . $index), 0, 100);

    if ($DB->record_exists('course_modules', ['course' => $course->id, 'idnumber' => $idnumber])) {
        return;
    }

    $name = $label . ': ' . $blueprint['topic'];
    $record = demo_module_record($module, $name, $blueprint['topic'], $blueprint['purpose'], $course->id);

    try {
        $generator->create_module($module, $record, [
            'section' => $section,
            'idnumber' => $idnumber,
            'visible' => 1,
        ]);
    } catch (Throwable $exception) {
        if ($module === 'page') {
            throw $exception;
        }

        $fallbackname = $label . ' Placeholder: ' . $blueprint['topic'];
        $fallbackrecord = demo_module_record('page', $fallbackname, $blueprint['topic'], $blueprint['purpose'], $course->id);
        $generator->create_module('page', $fallbackrecord, [
            'section' => $section,
            'idnumber' => $idnumber,
            'visible' => 1,
        ]);
        demo_out("Created page placeholder for {$requestedmodule} activity {$blueprint['topic']}: {$exception->getMessage()}");
    }
}

/**
 * Update a course section title and summary.
 */
function demo_update_section(stdClass $course, int $section, string $topic, string $purpose): void {
    global $DB;

    course_create_sections_if_missing($course, [$section]);
    $record = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $section], '*', MUST_EXIST);
    $record->name = $topic;
    $record->summary = '<p>' . s($purpose) . '</p>';
    $record->summaryformat = FORMAT_HTML;
    $record->timemodified = time();
    $DB->update_record('course_sections', $record);
}

/**
 * Return the hard-coded course blueprint rows.
 *
 * @return array<int, array<string, string>>
 */
function demo_blueprint(): array {
    return [
        ['category' => 'Class 1', 'course' => 'c1_eng', 'topic' => 'My Family', 'recommendation' => 'H5P Interactive Video', 'purpose' => 'Activity-based vocabulary building through familiar Indian family contexts'],
        ['category' => 'Class 1', 'course' => 'c1_maths', 'topic' => 'Numbers 1 to 100', 'recommendation' => 'H5P Drag and Drop', 'purpose' => 'Foundational numeracy using visual counting and playful practice'],
        ['category' => 'Class 1', 'course' => 'c1_evs', 'topic' => 'Our Festivals', 'recommendation' => 'Page + H5P Memory Game', 'purpose' => 'Holistic learning through Indian festivals, culture, and observation'],
        ['category' => 'Class 3', 'course' => 'c3_evs', 'topic' => 'Food We Eat', 'recommendation' => 'Glossary + Forum', 'purpose' => 'Activity-based learning for local food habits, healthy eating, and discussion'],
        ['category' => 'Class 3', 'course' => 'c3_evs', 'topic' => 'Our Festivals', 'recommendation' => 'Assignment', 'purpose' => 'Connect classroom learning with home traditions and reflective writing'],
        ['category' => 'Class 3', 'course' => 'c3_maths', 'topic' => 'Multiplication Tables', 'recommendation' => 'Quiz', 'purpose' => 'Practice-based fluency building with instant feedback'],
        ['category' => 'Class 3', 'course' => 'c3_maths', 'topic' => 'Money and Bills', 'recommendation' => 'H5P Branching Scenario', 'purpose' => 'Practical numeracy using rupees, paise, market bills, and daily-life examples'],
        ['category' => 'Class 11 Science', 'course' => 'c11_phy', 'topic' => 'Units and Measurements', 'recommendation' => 'Quiz', 'purpose' => 'NCERT-aligned concept checking and numerical accuracy practice'],
        ['category' => 'Class 11 Science', 'course' => 'c11_phy', 'topic' => 'Electrostatics Foundation', 'recommendation' => 'Quiz + Question Bank', 'purpose' => 'Rigorous board exam and JEE/NEET MCQ preparation'],
        ['category' => 'Class 11 Science', 'course' => 'c11_chem', 'topic' => 'Some Basic Concepts of Chemistry', 'recommendation' => 'Lesson', 'purpose' => 'Structured mole concept learning with stepwise remediation'],
        ['category' => 'Class 11 Science', 'course' => 'c11_chem', 'topic' => 'Chemical Bonding', 'recommendation' => 'H5P Interactive Presentation', 'purpose' => 'Visual conceptual learning for diagrams, structures, and bond types'],
        ['category' => 'Class 11 Science', 'course' => 'c11_maths', 'topic' => 'Sets and Relations', 'recommendation' => 'Assignment', 'purpose' => 'NCERT exercise practice with teacher feedback'],
        ['category' => 'Class 11 Science', 'course' => 'c11_maths', 'topic' => 'Trigonometric Functions', 'recommendation' => 'Quiz', 'purpose' => 'Board and entrance exam style problem-solving practice'],
        ['category' => 'Class 11 Commerce', 'course' => 'c11_acc', 'topic' => 'Introduction to Accounting', 'recommendation' => 'Page + Glossary', 'purpose' => 'Build commerce vocabulary using standard accounting terms'],
        ['category' => 'Class 11 Commerce', 'course' => 'c11_acc', 'topic' => 'Journal Entries', 'recommendation' => 'Assignment', 'purpose' => 'Ledger-writing practice aligned with CBSE/State Board formats'],
        ['category' => 'Class 11 Commerce', 'course' => 'c11_bst', 'topic' => 'Nature and Purpose of Business', 'recommendation' => 'Forum', 'purpose' => 'Case-based discussion using Indian business examples'],
        ['category' => 'Class 11 Commerce', 'course' => 'c11_bst', 'topic' => 'Forms of Business Organisation', 'recommendation' => 'Quiz', 'purpose' => 'Board exam readiness through objective and short-answer checks'],
        ['category' => 'Class 11 Commerce', 'course' => 'c11_eco', 'topic' => 'Consumer Equilibrium', 'recommendation' => 'Lesson + Quiz', 'purpose' => 'Conceptual clarity with graphs, examples, and exam-style questions'],
        ['category' => 'Class 11 Commerce', 'course' => 'c11_eco', 'topic' => 'Collection of Data', 'recommendation' => 'Assignment', 'purpose' => 'Practical data handling using school/community survey examples'],
        ['category' => 'Class 11 Commerce', 'course' => 'c11_acc', 'topic' => 'Partnership Accounts', 'recommendation' => 'Quiz + Assignment', 'purpose' => 'Rigorous board exam preparation with structured numerical practice'],
    ];
}

demo_set_admin_user();

$datadir = dirname(__DIR__) . '/demo-data/indian-school';
$categories = demo_read_csv($datadir . '/categories.csv');
$users = demo_read_csv($datadir . '/users.csv');

demo_out('Seeding Indian school demo categories...');
foreach ($categories as $categoryrow) {
    demo_upsert_category($categoryrow);
}

$defaultcategoryid = core_course_category::get_default()->id;
$coursespecs = [
    ['fullname' => 'School Administration Hub', 'shortname' => 'school_admin_hub', 'category' => $defaultcategoryid, 'summary' => 'Hidden support course for Principal and IT Coordinator demo enrolments.', 'visible' => 0, 'numsections' => 1],
    ['fullname' => 'Class 1 English', 'shortname' => 'c1_eng', 'category' => demo_category_id('class1'), 'summary' => 'Primary English activity course for Class 1 learners.', 'numsections' => 4],
    ['fullname' => 'Class 1 Mathematics', 'shortname' => 'c1_maths', 'category' => demo_category_id('class1'), 'summary' => 'Primary numeracy course for Class 1 learners.', 'numsections' => 4],
    ['fullname' => 'Class 1 EVS', 'shortname' => 'c1_evs', 'category' => demo_category_id('class1'), 'summary' => 'Environmental Studies through stories, observation, and activity-based learning.', 'numsections' => 4],
    ['fullname' => 'Class 3 EVS', 'shortname' => 'c3_evs', 'category' => demo_category_id('class3'), 'summary' => 'Class 3 EVS course using Indian home, school, food, and festival contexts.', 'numsections' => 5],
    ['fullname' => 'Class 3 Mathematics', 'shortname' => 'c3_maths', 'category' => demo_category_id('class3'), 'summary' => 'Class 3 mathematics practice course with daily-life applications.', 'numsections' => 5],
    ['fullname' => 'Class 11 Physics', 'shortname' => 'c11_phy', 'category' => demo_category_id('c11_sci'), 'summary' => 'NCERT and entrance-preparation Physics course for Class 11 Science.', 'numsections' => 6],
    ['fullname' => 'Class 11 Chemistry', 'shortname' => 'c11_chem', 'category' => demo_category_id('c11_sci'), 'summary' => 'NCERT Chemistry course for Class 11 Science with concept practice.', 'numsections' => 6],
    ['fullname' => 'Class 11 Mathematics', 'shortname' => 'c11_maths', 'category' => demo_category_id('c11_sci'), 'summary' => 'Class 11 Mathematics course for board and competitive exam preparation.', 'numsections' => 6],
    ['fullname' => 'Class 11 Accountancy', 'shortname' => 'c11_acc', 'category' => demo_category_id('c11_com'), 'summary' => 'Class 11 Accountancy course aligned with Indian board formats.', 'numsections' => 6],
    ['fullname' => 'Class 11 Business Studies', 'shortname' => 'c11_bst', 'category' => demo_category_id('c11_com'), 'summary' => 'Class 11 Business Studies course with Indian business examples.', 'numsections' => 6],
    ['fullname' => 'Class 11 Economics', 'shortname' => 'c11_eco', 'category' => demo_category_id('c11_com'), 'summary' => 'Class 11 Economics course with graph practice and community examples.', 'numsections' => 6],
];

demo_out('Seeding Indian school demo courses...');
$courses = [];
foreach ($coursespecs as $coursespec) {
    $course = demo_upsert_course($coursespec);
    $courses[$course->shortname] = $course;
}

demo_out('Seeding Indian school demo activity shells...');
$generator = new testing_data_generator();
$sectionbycourse = [];
foreach (demo_blueprint() as $blueprint) {
    $course = $courses[$blueprint['course']] ?? null;
    if (!$course) {
        throw new RuntimeException('Missing seeded course for blueprint shortname ' . $blueprint['course']);
    }

    $sectionbycourse[$course->shortname] = ($sectionbycourse[$course->shortname] ?? 0) + 1;
    $section = $sectionbycourse[$course->shortname];
    demo_update_section($course, $section, $blueprint['topic'], $blueprint['purpose']);

    $index = 0;
    foreach (demo_activity_specs($blueprint['recommendation']) as $spec) {
        $index++;
        demo_create_activity($generator, $course, $blueprint, $spec, $section, $index);
    }
}

demo_out('Seeding Indian school demo users and enrolments...');
foreach ($users as $userrow) {
    $user = demo_upsert_user($userrow);
    $course = $courses[$userrow['course1']] ?? null;
    if (!$course) {
        throw new RuntimeException("CSV references unknown course shortname {$userrow['course1']} for user {$userrow['username']}");
    }

    demo_enrol_user($user, $course, $userrow['role1']);
    if (in_array($userrow['username'], ['principal_sharma', 'it_coord_nair'], true)) {
        demo_assign_system_role($user, 'manager');
    }
}

foreach ($courses as $course) {
    rebuild_course_cache($course->id, true);
}

purge_all_caches();

demo_out('Indian school demo data seeding complete.');
demo_out('Users: ' . count($users) . ' | Categories: ' . count($categories) . ' | Courses: ' . count($courses) . ' | Blueprint rows: ' . count(demo_blueprint()));
demo_out('Demo password for seeded users: ' . DEMO_PASSWORD);
