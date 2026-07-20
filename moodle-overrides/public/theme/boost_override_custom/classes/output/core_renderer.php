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

namespace theme_boost_override_custom\output;

use context_course;
use moodle_url;

/**
 * Front-page renderer additions for the Boost Override Custom theme.
 *
 * @package    theme_boost_override_custom
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer {

    /**
     * Replaces the front-page title card with the public landing experience.
     *
     * @return string HTML for the page header.
     */
    public function full_header() {
        $header = parent::full_header();

        if ($this->page->pagelayout !== 'frontpage') {
            return $header;
        }

        return $this->frontpage_experience();
    }

    /**
     * Build the complete public front-page UI.
     *
     * @return string Front-page HTML.
     */
    private function frontpage_experience(): string {
        $data = $this->frontpage_data();

        return '
<section class="boc-frontpage" aria-label="eLearn Mindset public front page">
    <div class="boc-shell">
        ' . $this->render_hero($data) . '
        <div class="boc-content-layout">
            <main class="boc-main-column">
                ' . $this->render_discover($data) . '
                ' . $this->render_programmes() . '
                ' . $this->render_admissions($data) . '
                ' . $this->render_boards_mediums() . '
                ' . $this->render_grade_system() . '
                ' . $this->render_role_login($data) . '
                ' . $this->render_courses($data) . '
                ' . $this->render_announcements($data) . '
                ' . $this->render_calendar($data) . '
                ' . $this->render_about() . '
                ' . $this->render_contact() . '
            </main>
            ' . $this->render_moodle_rail($data) . '
        </div>
        ' . $this->render_public_footer() . '
    </div>
</section>';
    }

    /**
     * Collect public-safe aggregate data and URLs.
     *
     * @return array
     */
    private function frontpage_data(): array {
        global $DB, $SITE;

        $sitecontext = context_course::instance(SITEID);
        $sitename = format_string($SITE->fullname, true, [
            'context' => $sitecontext,
            'escape' => false,
        ]);

        $coursecount = $this->safe_count_records_select('course', 'id <> ? AND visible = ?', [$SITE->id, 1]);
        $activeusers = $this->safe_count_records_select('user', 'deleted = 0 AND suspended = 0 AND id > 1', []);
        $onlineusers = $this->safe_count_records_select('user', 'deleted = 0 AND suspended = 0 AND lastaccess > ?', [
            time() - 300,
        ]);

        return [
            'sitename' => $sitename,
            'coursecount' => $coursecount,
            'activeusers' => $activeusers,
            'onlineusers' => $onlineusers,
            'students' => $this->count_role_users(['student']),
            'parents' => $this->count_role_users(['parent']),
            'teachers' => $this->count_role_users(['teacher', 'editingteacher']),
            'courses' => $this->get_course_cards(),
            'events' => $this->get_public_events(),
            'homeurl' => (new moodle_url('/'))->out(false),
            'loginurl' => (new moodle_url('/login/index.php'))->out(false),
            'coursesurl' => (new moodle_url('/course/index.php'))->out(false),
            'searchurl' => (new moodle_url('/course/search.php'))->out(false),
            'calendarurl' => (new moodle_url('/calendar/view.php'))->out(false),
        ];
    }

    /**
     * Render the hero and fragment hub.
     *
     * @param array $data Front-page data.
     * @return string
     */
    private function render_hero(array $data): string {
        $sitename = s($data['sitename']);
        $coursesurl = s($data['coursesurl']);
        $loginurl = s($data['loginurl']);

        return '
<section class="boc-hero" aria-labelledby="boc-hero-title">
    <div class="boc-hero-copy">
        <span class="boc-kicker"><i class="fa fa-circle" aria-hidden="true"></i> Public learning portal</span>
        <p class="boc-site-context">' . $sitename . '</p>
        <h2 id="boc-hero-title">Build Your Learning Path</h2>
        <p class="boc-hero-text">
            Explore school, university, professional training, boards, mediums, assessments,
            certificates, and role-based learning access from one Moodle-powered campus.
        </p>
        <div class="boc-actions">
            <a class="boc-btn boc-btn-primary" href="' . $coursesurl . '">
                <span>Explore Courses</span><i class="fa fa-arrow-right" aria-hidden="true"></i>
            </a>
            <a class="boc-btn boc-btn-ghost" href="#contact">
                <i class="fa fa-comments-o" aria-hidden="true"></i><span>Admission Enquiry</span>
            </a>
            <a class="boc-btn boc-btn-soft" href="' . $loginurl . '">
                <i class="fa fa-sign-in" aria-hidden="true"></i><span>Log in</span>
            </a>
        </div>
        ' . $this->render_fragment_nav() . '
    </div>
    ' . $this->render_hero_slider($data) . '
</section>';
    }

    /**
     * Render the right-side front-page hero slider.
     *
     * @param array $data Front-page data.
     * @return string
     */
    private function render_hero_slider(array $data): string {
        $coursecount = s(number_format((int)$data['coursecount']));
        $students = s(number_format((int)$data['students']));
        $teachers = s(number_format((int)$data['teachers']));
        $coursesurl = s($data['coursesurl']);

        return '
<div class="boc-slider-card">
    <div id="boc-hero-slider" class="carousel slide boc-hero-slider boc-spotlight-slider" data-bs-ride="carousel" data-bs-interval="5600" data-bs-pause="hover" aria-label="Featured learning highlights">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#boc-hero-slider" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Learning ecosystem"><span>01</span></button>
            <button type="button" data-bs-target="#boc-hero-slider" data-bs-slide-to="1" aria-label="Admissions journey"><span>02</span></button>
            <button type="button" data-bs-target="#boc-hero-slider" data-bs-slide-to="2" aria-label="Boards and mediums"><span>03</span></button>
            <button type="button" data-bs-target="#boc-hero-slider" data-bs-slide-to="3" aria-label="Certificates and training"><span>04</span></button>
        </div>
        <div class="carousel-inner">
            <article class="carousel-item active">
                <div class="boc-slider-slide boc-slide-ecosystem">
                    <div class="boc-slide-copy">
                        <span class="boc-slide-label"><i class="fa fa-bolt" aria-hidden="true"></i> Live Moodle ecosystem</span>
                        <h3>One learning campus for every role</h3>
                        <p>Bring courses, assessments, certificates, parents, teachers and coordinators into one public-first LMS journey.</p>
                        <div class="boc-slide-metrics">
                            <span><b>' . $coursecount . '</b> courses</span>
                            <span><b>' . $students . '</b> students</span>
                            <span><b>' . $teachers . '</b> teachers</span>
                        </div>
                        <a href="#discover">Discover platform</a>
                    </div>
                    <div class="boc-slide-visual boc-visual-orbit" aria-hidden="true">
                        <div class="boc-orbit">
                            <span class="boc-orbit-ring ring-one"></span>
                            <span class="boc-orbit-ring ring-two"></span>
                            <span class="boc-orbit-ring ring-three"></span>
                            <span class="boc-orbit-core"><b>eM</b><small>LMS</small></span>
                            <span class="boc-orbit-chip chip-cbse">CBSE</span>
                            <span class="boc-orbit-chip chip-gseb">GSEB</span>
                            <span class="boc-orbit-chip chip-eng">English</span>
                            <span class="boc-orbit-chip chip-guj">Gujarati</span>
                            <span class="boc-orbit-chip chip-hin">Hindi</span>
                            <span class="boc-orbit-chip chip-cert">Certificates</span>
                            <span class="boc-orbit-chip chip-exams">Term Exams</span>
                        </div>
                    </div>
                </div>
            </article>
            <article class="carousel-item">
                <div class="boc-slider-slide boc-slide-admission">
                    <div class="boc-slide-copy">
                        <span class="boc-slide-label"><i class="fa fa-compass" aria-hidden="true"></i> Admission 2026</span>
                        <h3>Move visitors from enquiry to login access</h3>
                        <p>A guided funnel for counselling, programme fit, registration and Moodle access without changing the login flow.</p>
                        <a href="#admissions">View admission journey</a>
                    </div>
                    <div class="boc-slide-visual boc-visual-steps" aria-hidden="true">
                        <span><i class="fa fa-paper-plane-o"></i><b>Enquiry</b></span>
                        <span><i class="fa fa-comments-o"></i><b>Counselling</b></span>
                        <span><i class="fa fa-check-square-o"></i><b>Select</b></span>
                        <span><i class="fa fa-lock"></i><b>Access</b></span>
                    </div>
                </div>
            </article>
            <article class="carousel-item">
                <div class="boc-slider-slide boc-slide-mediums">
                    <div class="boc-slide-copy">
                        <span class="boc-slide-label"><i class="fa fa-language" aria-hidden="true"></i> Boards & Mediums</span>
                        <h3>Show academic fit before users sign in</h3>
                        <p>Present supported boards, language mediums, standards, university tracks and training batches from the first screen.</p>
                        <a href="#boards-mediums">Explore boards</a>
                    </div>
                    <div class="boc-slide-visual boc-visual-matrix" aria-hidden="true">
                        <span>CBSE</span><span>GSEB</span><span>English</span><span>Gujarati</span><span>Hindi</span><span>Standard 1-12</span>
                    </div>
                </div>
            </article>
            <article class="carousel-item">
                <div class="boc-slider-slide boc-slide-certificate">
                    <div class="boc-slide-copy">
                        <span class="boc-slide-label"><i class="fa fa-certificate" aria-hidden="true"></i> Training & Certificates</span>
                        <h3>Assess, grade and certify every programme</h3>
                        <p>Connect activities, assignments, quizzes, exams, gradebook progress and certificate issue workflows.</p>
                        <a href="' . $coursesurl . '">Browse available courses</a>
                    </div>
                    <div class="boc-slide-visual boc-visual-certificate" aria-hidden="true">
                        <div><i class="fa fa-certificate"></i><b>Certificate Ready</b><small>82% workflow progress</small></div>
                        <span class="boc-slide-progress"><i></i></span>
                        <em>Learn</em><em>Assess</em><em>Certify</em>
                    </div>
                </div>
            </article>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#boc-hero-slider" data-bs-slide="prev">
            <span class="fa fa-angle-left" aria-hidden="true"></span>
            <span class="visually-hidden">Previous slide</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#boc-hero-slider" data-bs-slide="next">
            <span class="fa fa-angle-right" aria-hidden="true"></span>
            <span class="visually-hidden">Next slide</span>
        </button>
    </div>
</div>';
    }

    /**
     * Render same-page fragment navigation.
     *
     * @return string
     */
    private function render_fragment_nav(): string {
        $items = [
            ['#discover', '#discover', 'fa-compass'],
            ['#programmes', '#programmes', 'fa-university'],
            ['#admissions', '#admissions', 'fa-address-card-o'],
            ['#boards-mediums', '#boards-mediums', 'fa-language'],
            ['#grade-system', '#grade-system', 'fa-line-chart'],
            ['#role-login', '#role-login', 'fa-users'],
            ['#courses', '#courses', 'fa-book'],
            ['#announcements', '#announcements', 'fa-bullhorn'],
            ['#calendar', '#calendar', 'fa-calendar'],
            ['#about', '#about', 'fa-info-circle'],
            ['#contact', '#contact', 'fa-envelope-o'],
        ];

        $html = '<nav class="boc-fragment-nav" aria-label="Front page sections">';
        foreach ($items as $item) {
            $html .= '<a href="' . s($item[0]) . '"><i class="fa ' . s($item[2]) . '" aria-hidden="true"></i><span>' .
                s($item[1]) . '</span></a>';
        }
        $html .= '</nav>';

        return $html;
    }

    /**
     * Render the discovery section.
     *
     * @param array $data Front-page data.
     * @return string
     */
    private function render_discover(array $data): string {
        $cards = [
            ['Trusted Platform', 'Secure, reliable and transparent LMS for schools, colleges and training teams.', 'fa-shield'],
            ['Quality Content', 'Curriculum-aligned courses, activities, certificates and reusable resources.', 'fa-cloud'],
            ['Expert Educators', 'Structured spaces for teachers, coordinators and programme teams.', 'fa-graduation-cap'],
            ['Student Success', 'Clear learning paths, progress visibility and assessment readiness.', 'fa-line-chart'],
        ];

        $stats = [
            ['Active Users', $data['activeusers'], 'fa-users', 'blue'],
            ['Courses', $data['coursecount'], 'fa-book', 'violet'],
            ['Students', $data['students'], 'fa-graduation-cap', 'green'],
            ['Parents', $data['parents'], 'fa-user-circle-o', 'orange'],
            ['Teachers', $data['teachers'], 'fa-user-plus', 'rose'],
        ];

        $html = $this->section_open('discover', 'Why eLearn Mindset?', 'A public overview for families, learners, teachers and partner organizations.');
        $html .= '<div class="boc-card-grid boc-discover-grid">';
        foreach ($cards as $card) {
            $html .= $this->info_card($card[0], $card[1], $card[2]);
        }
        $html .= '</div><div class="boc-stat-strip">';
        foreach ($stats as $stat) {
            $html .= '<article class="boc-stat ' . s($stat[3]) . '"><i class="fa ' . s($stat[2]) .
                '" aria-hidden="true"></i><strong>' . s(number_format((int)$stat[1])) . '</strong><span>' .
                s($stat[0]) . '</span></article>';
        }
        $html .= '</div></section>';

        return $html;
    }

    /**
     * Render programme cards.
     *
     * @return string
     */
    private function render_programmes(): string {
        $programmes = [
            ['School K-12', 'Comprehensive academic learning with board-aligned content.', 'fa-building-o', 'green'],
            ['University & Diploma', 'UG, PG and diploma programmes with structured course spaces.', 'fa-university', 'blue'],
            ['Professional Training', 'Short-term and long-term courses for industry-ready skills.', 'fa-briefcase', 'orange'],
            ['Certification Courses', 'Assessment-led certificates for advancement and compliance.', 'fa-certificate', 'violet'],
            ['Teacher Training', 'Pedagogy, tools and digital assessment support for educators.', 'fa-user-md', 'teal'],
            ['Skill Development', 'Practical skills for employability, entrepreneurship and growth.', 'fa-bullseye', 'rose'],
        ];

        $html = $this->section_open('programmes', 'Our Programmes', 'Streams designed for Indian school, university and training delivery models.');
        $html .= '<div class="boc-card-grid boc-programme-grid">';
        foreach ($programmes as $programme) {
            $html .= '<article class="boc-programme-card ' . s($programme[3]) . '"><i class="fa ' . s($programme[2]) .
                '" aria-hidden="true"></i><h4>' . s($programme[0]) . '</h4><p>' . s($programme[1]) . '</p></article>';
        }
        $html .= '</div></section>';

        return $html;
    }

    /**
     * Render admissions journey.
     *
     * @param array $data Front-page data.
     * @return string
     */
    private function render_admissions(array $data): string {
        $steps = [
            ['Enquiry', 'Submit interest or ask for course guidance.', 'fa-paper-plane-o'],
            ['Counselling', 'Review board, medium, stream and eligibility.', 'fa-comments-o'],
            ['Select Course', 'Choose the programme or training batch.', 'fa-check-square-o'],
            ['Registration', 'Complete registration and account setup.', 'fa-file-text-o'],
            ['Login Access', 'Receive LMS access for the correct role.', 'fa-lock'],
            ['Start Learning', 'Begin your learning journey and assessments.', 'fa-rocket'],
        ];

        $html = $this->section_open('admissions', 'Admission Journey', 'A clear route from enquiry to role-based Moodle access.');
        $html .= '<div class="boc-admission-wrap"><div class="boc-stepper">';
        foreach ($steps as $index => $step) {
            $html .= '<article><span>' . s((string)($index + 1)) . '</span><i class="fa ' . s($step[2]) .
                '" aria-hidden="true"></i><h4>' . s($step[0]) . '</h4><p>' . s($step[1]) . '</p></article>';
        }
        $html .= '</div><aside class="boc-action-stack">';
        $html .= '<a href="#contact"><i class="fa fa-file-text-o" aria-hidden="true"></i><span><b>Apply for Admission</b><small>Start your admission enquiry</small></span></a>';
        $html .= '<a href="' . s($data['coursesurl']) . '"><i class="fa fa-download" aria-hidden="true"></i><span><b>Download Prospectus</b><small>Explore programmes and details</small></span></a>';
        $html .= '<a href="#contact"><i class="fa fa-phone" aria-hidden="true"></i><span><b>Talk to Counsellor</b><small>Call or chat with our experts</small></span></a>';
        $html .= '</aside></div></section>';

        return $html;
    }

    /**
     * Render boards and mediums.
     *
     * @return string
     */
    private function render_boards_mediums(): string {
        $html = $this->section_open('boards-mediums', 'Boards & Mediums', 'Support for common Indian academic boards, language mediums and programme mappings.');
        $html .= '
<div class="boc-board-layout">
    <div class="boc-panel">
        <h4>Boards</h4>
        <div class="boc-list-cards">
            <article><i class="fa fa-institution" aria-hidden="true"></i><span><b>CBSE</b><small>Central Board of Secondary Education</small></span></article>
            <article><i class="fa fa-certificate" aria-hidden="true"></i><span><b>State Board / GSEB</b><small>State-aligned learning pathways</small></span></article>
        </div>
    </div>
    <div class="boc-panel">
        <h4>Mediums</h4>
        <div class="boc-medium-stack">
            <span class="blue"><b>EN</b> English</span>
            <span class="orange"><b>ગુ</b> Gujarati</span>
            <span class="rose"><b>हि</b> Hindi</span>
        </div>
    </div>
    <div class="boc-panel">
        <h4>Academic Mapping</h4>
        <div class="boc-chip-grid">
            <span>Standard 1-12</span><span>UG</span><span>PG</span><span>Diploma</span><span>Training Batches</span><span>Certificates</span>
        </div>
    </div>
</div>
</section>';

        return $html;
    }

    /**
     * Render grade system workflow.
     *
     * @return string
     */
    private function render_grade_system(): string {
        $items = [
            ['Course Activities', 'Engage with lessons, videos and resources.', 'fa-cubes'],
            ['Assignments', 'Submit class and project work.', 'fa-clipboard'],
            ['Quizzes', 'Test knowledge with objective checks.', 'fa-question-circle-o'],
            ['Term Exams', 'Periodic assessment windows.', 'fa-calendar-check-o'],
            ['Final Exams', 'Comprehensive programme evaluation.', 'fa-file-text-o'],
            ['Certificates', 'Earn verified certificates.', 'fa-certificate'],
            ['Progress Reports', 'Track learning growth.', 'fa-bar-chart'],
        ];

        $html = $this->section_open('grade-system', 'Our Grade System Workflow', 'Transparent assessment from learning activity to certificate and progress report.');
        $html .= '<div class="boc-grade-flow">';
        foreach ($items as $item) {
            $html .= '<article><i class="fa ' . s($item[2]) . '" aria-hidden="true"></i><h4>' . s($item[0]) .
                '</h4><p>' . s($item[1]) . '</p></article>';
        }
        $html .= '</div><p class="boc-note">Parents and teachers can view assigned progress summaries after login. Personal learner data is never shown publicly.</p></section>';

        return $html;
    }

    /**
     * Render role login behaviour cards.
     *
     * @param array $data Front-page data.
     * @return string
     */
    private function render_role_login(array $data): string {
        $roles = [
            ['Student', 'Access courses, assignments, grades, feedback and certificates.', 'fa-graduation-cap', 'blue'],
            ['Teacher', 'Manage content, assessments, gradebook and progress tracking.', 'fa-user-md', 'green'],
            ['Parent', 'Track child progress, reports, attendance and important notices.', 'fa-users', 'orange'],
            ['Coordinator', 'Manage users, batches, reports and overall operations.', 'fa-briefcase', 'violet'],
        ];

        $html = $this->section_open('role-login', 'Role-based Access', 'The same public login leads each user to their permitted Moodle experience.');
        $html .= '<div class="boc-card-grid boc-role-grid">';
        foreach ($roles as $role) {
            $html .= '<article class="boc-role-card ' . s($role[3]) . '"><i class="fa ' . s($role[2]) .
                '" aria-hidden="true"></i><h4>' . s($role[0]) . '</h4><p>' . s($role[1]) .
                '</p><a href="' . s($data['loginurl']) . '">Open Login</a></article>';
        }
        $html .= '</div></section>';

        return $html;
    }

    /**
     * Render available courses carousel.
     *
     * @param array $data Front-page data.
     * @return string
     */
    private function render_courses(array $data): string {
        $html = $this->section_open('courses', 'Available Courses', 'Browse the live Moodle catalogue by board, medium, class, stream and skill.');
        $html .= '
<div class="boc-filter-row">
    <button type="button">All</button>
    <button type="button">School</button>
    <button type="button">University</button>
    <button type="button">Professional</button>
    <button type="button">Certification</button>
    <a href="' . s($data['coursesurl']) . '">View all <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
</div>
<div class="boc-course-carousel" aria-label="Available course carousel">
    <button class="boc-carousel-control" type="button" aria-label="Previous courses"><i class="fa fa-angle-left" aria-hidden="true"></i></button>
    <div class="boc-course-track">';
        foreach ($data['courses'] as $index => $course) {
            $tone = ['blue', 'green', 'violet', 'orange', 'teal'][$index % 5];
            $html .= '<a class="boc-course-card ' . s($tone) . '" href="' . s($course['url']) . '"><span class="boc-course-art">' .
                '<i class="fa ' . s($course['icon']) . '" aria-hidden="true"></i></span><strong>' . s($course['name']) .
                '</strong><small>' . s($course['type']) . '</small><p>' . s($course['summary']) .
                '</p><em><i class="fa fa-star" aria-hidden="true"></i> 4.' . s((string)(5 + ($index % 4))) . ' learner rating</em></a>';
        }
        $html .= '</div><button class="boc-carousel-control" type="button" aria-label="Next courses"><i class="fa fa-angle-right" aria-hidden="true"></i></button></div></section>';

        return $html;
    }

    /**
     * Render announcement timeline.
     *
     * @param array $data Front-page data.
     * @return string
     */
    private function render_announcements(array $data): string {
        $announcements = [
            ['Admission Window Open', 'Admissions for new batches are now open.', 0, 'blue'],
            ['Term Calendar Published', 'Review term dates, examinations and holidays.', 2, 'green'],
            ['New Courses Available', 'Explore school, university and professional skill courses.', 5, 'violet'],
            ['Certificate Issue Schedule', 'Certificate release dates are available after course completion.', 9, 'orange'],
        ];

        $html = $this->section_open('announcements', 'Latest Announcements', 'Public notices for learners, parents, teachers and admission visitors.');
        $html .= '<div class="boc-timeline">';
        foreach ($announcements as $item) {
            $html .= '<article class="' . s($item[3]) . '"><i class="fa fa-circle" aria-hidden="true"></i><span><b>' .
                s($item[0]) . '</b><small>' . s($item[1]) . '</small></span><time>' .
                s($this->relative_date((int)$item[2])) . '</time></article>';
        }
        $html .= '</div></section>';

        return $html;
    }

    /**
     * Render academic calendar.
     *
     * @param array $data Front-page data.
     * @return string
     */
    private function render_calendar(array $data): string {
        $eventhtml = '';
        foreach ($data['events'] as $event) {
            $eventhtml .= '<article><span>' . s($event['date']) . '</span><strong>' . s($event['name']) . '</strong></article>';
        }

        $html = $this->section_open('calendar', 'Academic Calendar', 'Term dates, exam windows, holidays, orientation and upcoming Moodle events.');
        $html .= '
<div class="boc-calendar-layout">
    <div class="boc-calendar-cards">
        <article class="green"><b>Term 1</b><span>Classes, assignments and first assessment cycle</span></article>
        <article class="blue"><b>Term 2</b><span>Advanced modules, review and final preparation</span></article>
        <article class="orange"><b>Examinations</b><span>Term and final exams with gradebook tracking</span></article>
        <article class="rose"><b>Holidays</b><span>National, regional and academic breaks</span></article>
        <article class="violet"><b>Orientation</b><span>New learner and parent onboarding sessions</span></article>
    </div>
    <div class="boc-mini-calendar">
        <header><i class="fa fa-calendar" aria-hidden="true"></i><span>Upcoming Events</span></header>
        ' . $eventhtml . '
        <a href="' . s($data['calendarurl']) . '">View full calendar <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
    </div>
</div>
</section>';

        return $html;
    }

    /**
     * Render about/service section.
     *
     * @return string
     */
    private function render_about(): string {
        $items = [
            ['School & University LMS', 'Learning spaces for standards, semesters and cohorts.', 'fa-university'],
            ['Training Delivery', 'Batch-based training with expert instructors.', 'fa-briefcase'],
            ['Certificate Support', 'Verified certificates for learners and professionals.', 'fa-certificate'],
            ['Parent Communication', 'Role-based updates and transparent communication.', 'fa-comments-o'],
            ['Digital Assessment', 'Quizzes, assignments, exams and grading workflows.', 'fa-check-square-o'],
            ['Learning Analytics', 'Public-safe aggregates and private progress after login.', 'fa-line-chart'],
        ];

        $html = $this->section_open('about', 'About eLearn Mindset', 'A Moodle-powered learning service for modern academic and training operations.');
        $html .= '<div class="boc-card-grid boc-about-grid">';
        foreach ($items as $item) {
            $html .= $this->info_card($item[0], $item[1], $item[2]);
        }
        $html .= '</div></section>';

        return $html;
    }

    /**
     * Render contact section.
     *
     * @return string
     */
    private function render_contact(): string {
        return $this->section_open('contact', 'Contact & Support', 'Send an enquiry or connect with the support team for admission and training guidance.') . '
<div class="boc-contact-layout">
    <form class="boc-contact-form" action="#contact" method="get">
        <label><span>Your Name</span><input type="text" name="name" autocomplete="name"></label>
        <label><span>Email Address</span><input type="email" name="email" autocomplete="email"></label>
        <label class="wide"><span>Subject</span><input type="text" name="subject"></label>
        <label class="wide"><span>Message</span><textarea name="message" rows="4"></textarea></label>
        <button type="submit">Send Enquiry <i class="fa fa-paper-plane-o" aria-hidden="true"></i></button>
    </form>
    <div class="boc-contact-cards">
        <article><i class="fa fa-phone" aria-hidden="true"></i><span><b>Call Us</b><small>+91 98765 43210</small></span></article>
        <article><i class="fa fa-envelope-o" aria-hidden="true"></i><span><b>Email Us</b><small>support@elearnmindset.in</small></span></article>
        <article><i class="fa fa-whatsapp" aria-hidden="true"></i><span><b>WhatsApp Support</b><small>Available for admission help</small></span></article>
        <article><i class="fa fa-map-marker" aria-hidden="true"></i><span><b>Location</b><small>Ahmedabad, Gujarat, India</small></span></article>
    </div>
</div>
</section>';
    }

    /**
     * Render custom Moodle-style quick rail.
     *
     * @param array $data Front-page data.
     * @return string
     */
    private function render_moodle_rail(array $data): string {
        $eventitems = '';
        foreach (array_slice($data['events'], 0, 3) as $event) {
            $eventitems .= '<article><time>' . s($event['date']) . '</time><strong>' . s($event['name']) . '</strong></article>';
        }

        return '
<aside class="boc-rail" aria-label="Moodle quick blocks">
    <section class="boc-rail-block">
        <h3><i class="fa fa-search" aria-hidden="true"></i> Site Search</h3>
        <form action="' . s($data['searchurl']) . '" method="get">
            <label class="visually-hidden" for="boc-course-search">Search courses</label>
            <input id="boc-course-search" type="search" name="search" placeholder="Search courses, news...">
            <button type="submit" aria-label="Search"><i class="fa fa-search" aria-hidden="true"></i></button>
        </form>
        <a href="' . s($data['searchurl']) . '">Advanced search</a>
    </section>
    <section class="boc-rail-block">
        <h3><i class="fa fa-calendar" aria-hidden="true"></i> Upcoming Events</h3>
        <div class="boc-rail-events">' . $eventitems . '</div>
        <a href="' . s($data['calendarurl']) . '">View full calendar</a>
    </section>
    <section class="boc-rail-block boc-online">
        <h3><i class="fa fa-user-o" aria-hidden="true"></i> Online Users</h3>
        <strong>' . s(number_format((int)$data['onlineusers'])) . '</strong>
        <span>users online in the last 5 minutes</span>
    </section>
    <section class="boc-rail-block">
        <h3><i class="fa fa-link" aria-hidden="true"></i> Quick Links</h3>
        <nav class="boc-rail-links" aria-label="Quick links">
            <a href="#calendar">Academic Calendar <i class="fa fa-angle-right" aria-hidden="true"></i></a>
            <a href="#announcements">Announcements <i class="fa fa-angle-right" aria-hidden="true"></i></a>
            <a href="#contact">Prospectus Download <i class="fa fa-angle-right" aria-hidden="true"></i></a>
            <a href="#contact">Admission Enquiry <i class="fa fa-angle-right" aria-hidden="true"></i></a>
            <a href="#contact">Help & Support <i class="fa fa-angle-right" aria-hidden="true"></i></a>
        </nav>
    </section>
    <section class="boc-rail-block">
        <h3><i class="fa fa-folder-open-o" aria-hidden="true"></i> Course Categories</h3>
        <div class="boc-category-list">
            <span>School K-12 <b>' . s(number_format(max(0, (int)round($data['coursecount'] * 0.38)))) . '</b></span>
            <span>University & Diploma <b>' . s(number_format(max(0, (int)round($data['coursecount'] * 0.24)))) . '</b></span>
            <span>Professional Training <b>' . s(number_format(max(0, (int)round($data['coursecount'] * 0.2)))) . '</b></span>
            <span>Certification Courses <b>' . s(number_format(max(0, (int)round($data['coursecount'] * 0.12)))) . '</b></span>
            <span>Teacher Training <b>' . s(number_format(max(0, (int)round($data['coursecount'] * 0.06)))) . '</b></span>
        </div>
        <a href="' . s($data['coursesurl']) . '">Browse all courses</a>
    </section>
</aside>';
    }

    /**
     * Render the public footer strip.
     *
     * @return string
     */
    private function render_public_footer(): string {
        return '
<footer class="boc-public-footer">
    <div><strong>eLearn Mindset</strong><span>Moodle Learning Platform</span></div>
    <nav aria-label="Public footer links">
        <a href="#about">About Us</a>
        <a href="#contact">Contact Us</a>
        <a href="#">Privacy Policy</a>
        <a href="#">Terms of Use</a>
        <a href="#">Accessibility</a>
    </nav>
    <p>2026 eLearn Mindset. All rights reserved.</p>
</footer>';
    }

    /**
     * Render a section opening header.
     *
     * @param string $id Section id.
     * @param string $title Section title.
     * @param string $intro Short introduction.
     * @return string
     */
    private function section_open(string $id, string $title, string $intro): string {
        return '<section id="' . s($id) . '" class="boc-section" aria-labelledby="boc-' . s($id) . '-title">' .
            '<div class="boc-section-heading"><span>#' . s($id) . '</span><div><h3 id="boc-' . s($id) .
            '-title">' . s($title) . '</h3><p>' . s($intro) . '</p></div></div>';
    }

    /**
     * Render a compact info card.
     *
     * @param string $title Card title.
     * @param string $body Card body.
     * @param string $icon Font Awesome icon class.
     * @return string
     */
    private function info_card(string $title, string $body, string $icon): string {
        return '<article class="boc-info-card"><i class="fa ' . s($icon) . '" aria-hidden="true"></i><h4>' .
            s($title) . '</h4><p>' . s($body) . '</p></article>';
    }

    /**
     * Count records defensively.
     *
     * @param string $table Table name without braces.
     * @param string $select SQL where clause.
     * @param array $params SQL params.
     * @return int
     */
    private function safe_count_records_select(string $table, string $select, array $params): int {
        global $DB;

        try {
            return (int)$DB->count_records_select($table, $select, $params);
        } catch (\dml_exception $exception) {
            return 0;
        }
    }

    /**
     * Count unique users assigned to the provided Moodle role shortnames.
     *
     * @param array $shortnames Role shortnames.
     * @return int
     */
    private function count_role_users(array $shortnames): int {
        global $DB;

        try {
            [$rolesql, $params] = $DB->get_in_or_equal($shortnames, SQL_PARAMS_NAMED, 'role');
            $params['deleted'] = 0;
            $params['suspended'] = 0;
            $sql = "SELECT COUNT(DISTINCT ra.userid)
                      FROM {role_assignments} ra
                      JOIN {role} r ON r.id = ra.roleid
                      JOIN {user} u ON u.id = ra.userid
                     WHERE r.shortname {$rolesql}
                       AND u.deleted = :deleted
                       AND u.suspended = :suspended";
            return (int)$DB->count_records_sql($sql, $params);
        } catch (\dml_exception $exception) {
            return 0;
        }
    }

    /**
     * Get a small public course carousel from visible Moodle courses.
     *
     * @return array
     */
    private function get_course_cards(): array {
        global $DB, $SITE;

        $fallback = [
            ['AI for Educators', 'Professional Training', 'Future-ready teaching with classroom technology.', 'fa-lightbulb-o'],
            ['Business Analytics', 'Professional Training', 'Data-driven decisions for academic and business teams.', 'fa-line-chart'],
            ['Digital Law & Ethics', 'Certification Course', 'Compliance and responsible digital practice.', 'fa-balance-scale'],
            ['Science Grade 10', 'School K-12', 'Board-aligned science learning and assessments.', 'fa-flask'],
            ['Gujarati Medium Mathematics', 'School K-12', 'Mathematics learning path for Gujarati medium learners.', 'fa-calculator'],
        ];

        try {
            $records = $DB->get_records_select(
                'course',
                'id <> ? AND visible = ?',
                [$SITE->id, 1],
                'sortorder ASC',
                'id, fullname, shortname, summary, summaryformat',
                0,
                5
            );
        } catch (\dml_exception $exception) {
            return $this->fallback_course_cards($fallback);
        }

        if (!$records) {
            return $this->fallback_course_cards($fallback);
        }

        $cards = [];
        $icons = ['fa-lightbulb-o', 'fa-line-chart', 'fa-balance-scale', 'fa-flask', 'fa-calculator'];
        foreach (array_values($records) as $index => $course) {
            $context = context_course::instance($course->id);
            $name = format_string($course->fullname, true, ['context' => $context, 'escape' => false]);
            $summary = trim(strip_tags((string)$course->summary));
            if ($summary === '') {
                $summary = 'Course from the live Moodle catalogue with structured learning activities.';
            }
            $cards[] = [
                'name' => shorten_text($name, 58, true),
                'type' => $this->guess_course_type($name),
                'summary' => shorten_text($summary, 92, true),
                'icon' => $icons[$index % count($icons)],
                'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            ];
        }

        return $cards;
    }

    /**
     * Render fallback course card data.
     *
     * @param array $fallback Fallback course tuples.
     * @return array
     */
    private function fallback_course_cards(array $fallback): array {
        $cards = [];
        foreach ($fallback as $course) {
            $cards[] = [
                'name' => $course[0],
                'type' => $course[1],
                'summary' => $course[2],
                'icon' => $course[3],
                'url' => (new moodle_url('/course/index.php'))->out(false),
            ];
        }
        return $cards;
    }

    /**
     * Guess a public course type from its name.
     *
     * @param string $name Course name.
     * @return string
     */
    private function guess_course_type(string $name): string {
        $lower = \core_text::strtolower($name);
        if (strpos($lower, 'certificate') !== false || strpos($lower, 'certification') !== false) {
            return 'Certification Course';
        }
        if (strpos($lower, 'training') !== false || strpos($lower, 'skill') !== false) {
            return 'Professional Training';
        }
        if (strpos($lower, 'ug') !== false || strpos($lower, 'pg') !== false || strpos($lower, 'diploma') !== false) {
            return 'University & Diploma';
        }
        return 'School K-12';
    }

    /**
     * Get public site/course events or generated upcoming placeholders.
     *
     * @return array
     */
    private function get_public_events(): array {
        global $DB;

        try {
            $records = $DB->get_records_select(
                'event',
                'timestart >= ? AND visible = ? AND userid = ?',
                [time(), 1, 0],
                'timestart ASC',
                'id, name, timestart',
                0,
                4
            );
        } catch (\dml_exception $exception) {
            $records = [];
        }

        $events = [];
        foreach ($records as $event) {
            $events[] = [
                'name' => shorten_text(format_string($event->name), 56, true),
                'date' => userdate($event->timestart, '%d %b %Y'),
            ];
        }

        if ($events) {
            return $events;
        }

        return [
            ['name' => 'Orientation Programme', 'date' => $this->relative_date(7)],
            ['name' => 'Admission Counselling Window', 'date' => $this->relative_date(14)],
            ['name' => 'Term Assessment Briefing', 'date' => $this->relative_date(21)],
            ['name' => 'Certificate Review Session', 'date' => $this->relative_date(30)],
        ];
    }

    /**
     * Create a future date label.
     *
     * @param int $days Number of days from now.
     * @return string
     */
    private function relative_date(int $days): string {
        return userdate(time() + ($days * DAYSECS), '%d %b %Y');
    }
}
