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
 * Site administrator dashboard for the Custom LMS theme.
 *
 * @package    theme_custom_lms
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/xmldb/xmldb_table.php');

require_login();

$context = context_system::instance();
if (!is_siteadmin()) {
    throw new required_capability_exception($context, 'moodle/site:config', 'nopermissions', '');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/theme/custom_lms/admin_dashboard.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('admindashboard', 'theme_custom_lms'));
$PAGE->set_heading(format_string($SITE->fullname, true, ['context' => $context]));
$PAGE->requires->css(new moodle_url('/theme/custom_lms/style/admin_pages.css'));

$formatnumber = static function($value): string {
    return number_format((int)$value);
};

$formatpercent = static function($value): string {
    return number_format((float)$value, 0) . '%';
};

$activeusercutoff = time() - DAYSECS * 30;
$totalusers = (int)$DB->count_records_select('user', 'deleted = 0 AND id > 1');
$activeusers = (int)$DB->count_records_select(
    'user',
    'deleted = 0 AND suspended = 0 AND id > 1 AND lastaccess >= :lastaccess',
    ['lastaccess' => $activeusercutoff]
);
$totalcourses = (int)$DB->count_records_select('course', 'id <> :siteid', ['siteid' => SITEID]);
$visiblecourses = (int)$DB->count_records_select('course', 'id <> :siteid AND visible = 1', ['siteid' => SITEID]);
$totalactivities = (int)$DB->count_records('course_modules');
$activeenrolments = (int)$DB->count_records_sql(
    "SELECT COUNT(1)
       FROM {user_enrolments} ue
       JOIN {enrol} e ON e.id = ue.enrolid
      WHERE ue.status = 0
        AND e.status = 0"
);
$completedcourses = (int)$DB->count_records_select('course_completions', 'timecompleted IS NOT NULL AND timecompleted > 0');
$completionpercent = $activeenrolments > 0 ? min(100, round(($completedcourses / $activeenrolments) * 100)) : 0;
$duetasks = (int)$DB->count_records_select(
    'task_scheduled',
    'disabled = 0 AND nextruntime > 0 AND nextruntime <= :nowtime',
    ['nowtime' => time()]
);

$stats = [
    [
        'label' => get_string('users'),
        'value' => $formatnumber($totalusers),
        'meta' => $formatnumber($activeusers) . ' active in last 30 days',
        'icon' => 'users',
    ],
    [
        'label' => get_string('courses'),
        'value' => $formatnumber($totalcourses),
        'meta' => $formatnumber($visiblecourses) . ' visible courses',
        'icon' => 'courses',
    ],
    [
        'label' => get_string('enrolments'),
        'value' => $formatnumber($activeenrolments),
        'meta' => 'Active enrolments',
        'icon' => 'enrolments',
    ],
    [
        'label' => get_string('activities'),
        'value' => $formatnumber($totalactivities),
        'meta' => 'Course modules in Moodle',
        'icon' => 'activities',
    ],
    [
        'label' => 'Course completion',
        'value' => $formatpercent($completionpercent),
        'meta' => $formatnumber($completedcourses) . ' completed records',
        'icon' => 'completion',
    ],
    [
        'label' => 'Scheduled tasks',
        'value' => $formatnumber($duetasks),
        'meta' => 'Due now',
        'icon' => 'tasks',
    ],
];

$monthlabels = [];
$monthbuckets = [];
$startmonth = strtotime(date('Y-m-01 00:00:00', strtotime('-11 months')));
for ($index = 0; $index < 12; $index++) {
    $monthtime = strtotime('+' . $index . ' months', $startmonth);
    $monthkey = date('Y-m', $monthtime);
    $monthlabels[$monthkey] = userdate($monthtime, '%b');
    $monthbuckets[$monthkey] = 0;
}

$enrolmentrows = $DB->get_records_sql(
    "SELECT ue.id,
            CASE
                WHEN ue.timecreated > 0 THEN ue.timecreated
                WHEN ue.timestart > 0 THEN ue.timestart
                ELSE ue.timemodified
            END AS createdtime
       FROM {user_enrolments} ue
      WHERE ue.timemodified >= :starttime OR ue.timecreated >= :starttimecreated OR ue.timestart >= :starttimestart",
    [
        'starttime' => $startmonth,
        'starttimecreated' => $startmonth,
        'starttimestart' => $startmonth,
    ]
);
foreach ($enrolmentrows as $row) {
    $createdtime = (int)$row->createdtime;
    if ($createdtime <= 0) {
        continue;
    }
    $monthkey = date('Y-m', $createdtime);
    if (isset($monthbuckets[$monthkey])) {
        $monthbuckets[$monthkey]++;
    }
}
$maxmonthvalue = max($monthbuckets) ?: 1;
$monthlyenrolments = [];
foreach ($monthbuckets as $monthkey => $count) {
    $monthlyenrolments[] = [
        'label' => $monthlabels[$monthkey],
        'value' => $formatnumber($count),
        'height' => max(8, (int)round(($count / $maxmonthvalue) * 100)),
    ];
}

$rolecounts = [];
$rolecountrows = $DB->get_records_sql(
    "SELECT r.id,
            r.name,
            r.shortname,
            COUNT(DISTINCT ra.userid) AS usercount
       FROM {role_assignments} ra
       JOIN {role} r ON r.id = ra.roleid
      GROUP BY r.id, r.name, r.shortname
      ORDER BY usercount DESC, r.sortorder ASC",
    [],
    0,
    6
);
$maxrolecount = 1;
foreach ($rolecountrows as $row) {
    $maxrolecount = max($maxrolecount, (int)$row->usercount);
}
foreach ($rolecountrows as $row) {
    $label = role_get_name($row, $context, ROLENAME_ALIAS);
    $rolecounts[] = [
        'label' => $label,
        'shortname' => $row->shortname,
        'value' => $formatnumber($row->usercount),
        'percent' => (int)round(((int)$row->usercount / $maxrolecount) * 100),
    ];
}

$categoryrows = $DB->get_records_sql(
    "SELECT cc.id,
            cc.name,
            COUNT(c.id) AS coursecount
       FROM {course_categories} cc
  LEFT JOIN {course} c ON c.category = cc.id
      GROUP BY cc.id, cc.name
      ORDER BY coursecount DESC, cc.name ASC",
    [],
    0,
    6
);
$maxcategorycount = 1;
foreach ($categoryrows as $row) {
    $maxcategorycount = max($maxcategorycount, (int)$row->coursecount);
}
$categorycounts = [];
foreach ($categoryrows as $row) {
    $categorycounts[] = [
        'label' => format_string($row->name, true, ['context' => $context]),
        'value' => $formatnumber($row->coursecount),
        'percent' => (int)round(((int)$row->coursecount / $maxcategorycount) * 100),
    ];
}

$topcourserows = $DB->get_records_sql(
    "SELECT c.id,
            c.fullname,
            COUNT(ue.id) AS enrolments
       FROM {course} c
  LEFT JOIN {enrol} e ON e.courseid = c.id
  LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.status = 0
      WHERE c.id <> :siteid
      GROUP BY c.id, c.fullname
      ORDER BY enrolments DESC, c.fullname ASC",
    ['siteid' => SITEID],
    0,
    5
);
$topcourses = [];
foreach ($topcourserows as $row) {
    $coursecontext = context_course::instance((int)$row->id, IGNORE_MISSING);
    $coursecompletioncount = (int)$DB->count_records_select(
        'course_completions',
        'course = :courseid AND timecompleted IS NOT NULL AND timecompleted > 0',
        ['courseid' => $row->id]
    );
    $coursepercent = (int)$row->enrolments > 0 ? min(100, round(($coursecompletioncount / (int)$row->enrolments) * 100)) : 0;
    $topcourses[] = [
        'name' => format_string($row->fullname, true, [
            'context' => $coursecontext ?: $context,
        ]),
        'url' => (new moodle_url('/course/view.php', ['id' => $row->id]))->out(false),
        'enrolments' => $formatnumber($row->enrolments),
        'completion' => $formatpercent($coursepercent),
        'completionraw' => $coursepercent,
    ];
}

$recentactivity = [];
$logtable = new xmldb_table('logstore_standard_log');
if ($DB->get_manager()->table_exists($logtable)) {
    $logrows = $DB->get_records_sql(
        "SELECT id, action, target, objecttable, timecreated
           FROM {logstore_standard_log}
          ORDER BY timecreated DESC",
        [],
        0,
        6
    );
    foreach ($logrows as $row) {
        $recentactivity[] = [
            'label' => ucfirst(str_replace('_', ' ', $row->action . ' ' . $row->target)),
            'meta' => $row->objecttable ?: 'Moodle activity',
            'time' => userdate((int)$row->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
        ];
    }
}

$dbinfo = method_exists($DB, 'get_server_info') ? $DB->get_server_info() : [];
$dbversion = is_array($dbinfo) && !empty($dbinfo['version']) ? $dbinfo['version'] : get_string('unknown');
$lastcron = (int)$DB->get_field_sql('SELECT MAX(lastruntime) FROM {task_scheduled}');

$quickactions = [
    [
        'label' => 'Add new user',
        'url' => (new moodle_url('/user/editadvanced.php', ['id' => -1]))->out(false),
        'icon' => 'users',
    ],
    [
        'label' => 'Add new course',
        'url' => (new moodle_url('/course/edit.php', ['category' => 0]))->out(false),
        'icon' => 'courses',
    ],
    [
        'label' => 'Create category',
        'url' => (new moodle_url('/course/editcategory.php', ['parent' => 0]))->out(false),
        'icon' => 'categories',
    ],
    [
        'label' => 'Purge caches',
        'url' => (new moodle_url('/admin/purgecaches.php', ['confirm' => 1, 'sesskey' => sesskey()]))->out(false),
        'icon' => 'cache',
    ],
];

$reportshortcuts = [
    ['label' => 'Logs', 'url' => (new moodle_url('/report/log/index.php', ['id' => 0]))->out(false)],
    ['label' => 'Security checks', 'url' => (new moodle_url('/report/security/index.php'))->out(false)],
    ['label' => 'Performance overview', 'url' => (new moodle_url('/report/performance/index.php'))->out(false)],
    ['label' => 'Scheduled tasks', 'url' => (new moodle_url('/admin/tool/task/scheduledtasks.php'))->out(false)],
    ['label' => 'Config changes', 'url' => (new moodle_url('/report/configlog/index.php'))->out(false)],
];

$templatecontext = [
    'stats' => $stats,
    'monthlyenrolments' => $monthlyenrolments,
    'rolecounts' => $rolecounts,
    'categorycounts' => $categorycounts,
    'topcourses' => $topcourses,
    'recentactivity' => $recentactivity,
    'hasrecentactivity' => !empty($recentactivity),
    'quickactions' => $quickactions,
    'reportshortcuts' => $reportshortcuts,
    'systemstatus' => [
        ['label' => 'Moodle version', 'value' => $release ?? get_config('moodle', 'release')],
        ['label' => 'PHP version', 'value' => PHP_VERSION],
        ['label' => 'Database', 'value' => $DB->get_dbfamily() . ' ' . $dbversion],
        ['label' => 'Due scheduled tasks', 'value' => $formatnumber($duetasks)],
        ['label' => 'Last scheduled task run', 'value' => $lastcron > 0 ? userdate($lastcron, get_string('strftimedatetimeshort', 'langconfig')) : get_string('never')],
    ],
    'urls' => [
        'admin' => (new moodle_url('/admin/search.php'))->out(false),
        'users' => (new moodle_url('/admin/user.php'))->out(false),
        'courses' => (new moodle_url('/course/management.php'))->out(false),
        'reports' => (new moodle_url('/admin/search.php', [], 'linkreports'))->out(false),
    ],
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('theme_custom_lms/admin_dashboard', $templatecontext);
echo $OUTPUT->footer();
