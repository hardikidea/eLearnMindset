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
 * Login page layout for the Boost Override Custom theme.
 *
 * This keeps Moodle's login form output intact and only adds a public visual
 * shell, page stylesheet, and non-blocking UI enhancement script.
 *
 * @package    theme_boost_override_custom
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$PAGE->requires->css(new \moodle_url('/theme/boost_override_custom/style/login.css'));
$PAGE->requires->css(new \moodle_url(
    '/theme/boost_override_custom/style/whitelabel.css',
    ['v' => '2026072410']
));
$PAGE->requires->js(new \moodle_url(
    '/theme/boost_override_custom/javascript/platform.js',
    ['v' => '2026072410']
));
$PAGE->requires->string_for_js('moreactions', 'theme_boost_override_custom');

$countrecords = static function(string $table, string $select, array $params): int {
    global $DB;

    try {
        return (int)$DB->count_records_select($table, $select, $params);
    } catch (\dml_exception $exception) {
        return 0;
    }
};

$countsql = static function(string $sql, array $params): int {
    global $DB;

    try {
        return (int)$DB->count_records_sql($sql, $params);
    } catch (\dml_exception $exception) {
        return 0;
    }
};

$countroleusers = static function(array $roles): int {
    global $DB;

    if (!$roles) {
        return 0;
    }

    try {
        [$rolesql, $params] = $DB->get_in_or_equal($roles, SQL_PARAMS_NAMED, 'bocrole');
        $params['deleted'] = 0;
        $params['confirmed'] = 1;

        return (int)$DB->count_records_sql(
            "SELECT COUNT(DISTINCT u.id)
               FROM {user} u
               JOIN {role_assignments} ra ON ra.userid = u.id
               JOIN {role} r ON r.id = ra.roleid
              WHERE r.shortname {$rolesql}
                AND u.deleted = :deleted
                AND u.confirmed = :confirmed",
            $params
        );
    } catch (\dml_exception $exception) {
        return 0;
    }
};

$formatcount = static function(int $value): string {
    return number_format($value);
};

$guestid = (int)($CFG->siteguest ?? 1);
$stats = [
    'users' => $countrecords('user', 'deleted = ? AND confirmed = ? AND id <> ?', [0, 1, $guestid]),
    'students' => $countroleusers(['student']),
    'teachers' => $countroleusers(['teacher', 'editingteacher']),
    'parents' => $countroleusers(['parent', 'guardian']),
    'courses' => $countrecords('course', 'id <> ? AND visible = ?', [$SITE->id, 1]),
    'categories' => $countrecords('course_categories', 'visible = ?', [1]),
    'activities' => $countsql(
        "SELECT COUNT(1)
           FROM {course_modules} cm
           JOIN {course} c ON c.id = cm.course
          WHERE c.id <> :siteid
            AND c.visible = :coursevisible
            AND cm.visible = :modulevisible",
        ['siteid' => $SITE->id, 'coursevisible' => 1, 'modulevisible' => 1]
    ),
    'assignments' => $countrecords('assign', 'course <> ?', [$SITE->id]),
    'quizzes' => $countrecords('quiz', 'course <> ?', [$SITE->id]),
    'certificates' => $countrecords('customcert', 'course <> ?', [$SITE->id]),
    'forums' => $countrecords('forum', 'course <> ? AND type <> ?', [$SITE->id, 'news']),
    'enrolments' => $countsql(
        "SELECT COUNT(DISTINCT ue.userid)
           FROM {user_enrolments} ue
           JOIN {enrol} e ON e.id = ue.enrolid
           JOIN {course} c ON c.id = e.courseid
          WHERE c.id <> :siteid
            AND c.visible = :coursevisible
            AND e.status = :enrolstatus
            AND ue.status = :userenrolstatus",
        ['siteid' => $SITE->id, 'coursevisible' => 1, 'enrolstatus' => 0, 'userenrolstatus' => 0]
    ),
];

$brandcontext = theme_boost_override_custom_get_brand_context($OUTPUT);
$boclogin = [
    'sitename' => $brandcontext['institutionname'],
    'productname' => $brandcontext['productname'],
    'tagline' => $brandcontext['tagline'],
    'hasmainlogo' => $brandcontext['hasmainlogo'],
    'mainlogourl' => $brandcontext['mainlogourl'],
    'hascompactlogo' => $brandcontext['hascompactlogo'],
    'compactlogourl' => $brandcontext['compactlogourl'],
    'homeurl' => (new \moodle_url('/'))->out(false),
    'loginurl' => (new \moodle_url('/login/index.php'))->out(false),
    'navitems' => [
        ['label' => 'Home', 'url' => (new \moodle_url('/'))->out(false), 'svgid' => 'bocIconHome'],
        ['label' => 'Discover', 'url' => (new \moodle_url('/', [], 'discover'))->out(false), 'svgid' => 'bocIconDiscover'],
        ['label' => 'Programmes', 'url' => (new \moodle_url('/', [], 'programmes'))->out(false), 'svgid' => 'bocIconProgrammes'],
        ['label' => 'Admissions', 'url' => (new \moodle_url('/', [], 'admissions'))->out(false), 'svgid' => 'bocIconAdmissions'],
        ['label' => 'Boards', 'url' => (new \moodle_url('/', [], 'boards-mediums'))->out(false), 'svgid' => 'bocIconBoards'],
        ['label' => 'Grade System', 'url' => (new \moodle_url('/', [], 'grade-system'))->out(false), 'svgid' => 'bocIconGrade'],
        ['label' => 'About', 'url' => (new \moodle_url('/', [], 'about'))->out(false), 'svgid' => 'bocIconAbout'],
        ['label' => 'Contact', 'url' => (new \moodle_url('/', [], 'contact'))->out(false), 'svgid' => 'bocIconContact'],
    ],
    'stats' => [
        'users' => ['raw' => $stats['users'], 'value' => $formatcount($stats['users'])],
        'students' => ['raw' => $stats['students'], 'value' => $formatcount($stats['students'])],
        'teachers' => ['raw' => $stats['teachers'], 'value' => $formatcount($stats['teachers'])],
        'parents' => ['raw' => $stats['parents'], 'value' => $formatcount($stats['parents'])],
        'courses' => ['raw' => $stats['courses'], 'value' => $formatcount($stats['courses'])],
        'categories' => ['raw' => $stats['categories'], 'value' => $formatcount($stats['categories'])],
        'activities' => ['raw' => $stats['activities'], 'value' => $formatcount($stats['activities'])],
        'assignments' => ['raw' => $stats['assignments'], 'value' => $formatcount($stats['assignments'])],
        'quizzes' => ['raw' => $stats['quizzes'], 'value' => $formatcount($stats['quizzes'])],
        'certificates' => ['raw' => $stats['certificates'], 'value' => $formatcount($stats['certificates'])],
        'forums' => ['raw' => $stats['forums'], 'value' => $formatcount($stats['forums'])],
        'enrolments' => ['raw' => $stats['enrolments'], 'value' => $formatcount($stats['enrolments'])],
    ],
];

$statsjson = json_encode($stats, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$PAGE->requires->js_init_code(<<<JS
    (function() {
        var stats = {$statsjson};

        var ready = function(callback) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', callback);
                return;
            }
            callback();
        };

        var animateCount = function(node) {
            var target = parseInt(node.getAttribute('data-boc-count') || '0', 10);
            var start = performance.now();
            var duration = 980;

            if (!Number.isFinite(target) || target < 1 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                node.textContent = target.toLocaleString();
                return;
            }

            var step = function(now) {
                var progress = Math.min(1, (now - start) / duration);
                var eased = 1 - Math.pow(1 - progress, 3);
                node.textContent = Math.round(target * eased).toLocaleString();
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };

            window.requestAnimationFrame(step);
        };

        ready(function() {
            document.body.classList.add('boc-login-ready');

            document.querySelectorAll('[data-boc-count]').forEach(function(node) {
                animateCount(node);
            });

            var slides = Array.prototype.slice.call(document.querySelectorAll('[data-boc-login-slide]'));
            var dots = Array.prototype.slice.call(document.querySelectorAll('[data-boc-login-dot]'));
            var index = Math.max(0, slides.findIndex(function(slide) {
                return slide.classList.contains('is-active');
            }));
            var timer = null;

            var setSlide = function(next) {
                index = (next + slides.length) % slides.length;
                slides.forEach(function(slide, slideindex) {
                    slide.classList.toggle('is-active', slideindex === index);
                });
                dots.forEach(function(dot, dotindex) {
                    var active = dotindex === index;
                    dot.classList.toggle('is-active', active);
                    dot.setAttribute('aria-selected', active ? 'true' : 'false');
                });
            };

            var schedule = function() {
                if (timer || slides.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    return;
                }
                timer = window.setInterval(function() {
                    setSlide(index + 1);
                }, 4600);
            };

            dots.forEach(function(dot, dotindex) {
                dot.addEventListener('click', function() {
                    if (timer) {
                        window.clearInterval(timer);
                        timer = null;
                    }
                    setSlide(dotindex);
                    schedule();
                });
            });

            setSlide(index);
            schedule();
            window.BoostOverrideCustomLoginStats = stats;

            document.addEventListener('visibilitychange', function() {
                document.body.classList.toggle('boc-login-paused', document.hidden);
            });
        });
    })();
JS);

$bodyattributes = $OUTPUT->body_attributes(['theme-boost-override-custom-login']);

$leftinstructions = !empty($CFG->auth_instructions)
    ? format_text($CFG->auth_instructions, FORMAT_MOODLE, ['context' => context_system::instance()])
    : null;

$templatecontext = [
    'sitename' => $boclogin['sitename'],
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'leftinstructions' => $leftinstructions,
    'boclogin' => $boclogin,
    'brand' => $brandcontext,
];

echo $OUTPUT->render_from_template('theme_boost/login', $templatecontext);
