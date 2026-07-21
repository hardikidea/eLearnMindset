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

        if ($this->page->pagelayout === 'frontpage') {
            return $this->frontpage_experience();
        }

        if ($this->is_course_catalogue_page()) {
            return $header . $this->course_catalogue_experience();
        }

        return $header;
    }

    /**
     * Whether Moodle is rendering the public course category index.
     *
     * @return bool
     */
    private function is_course_catalogue_page(): bool {
        return ($this->page->pagelayout === 'coursecategory' && $this->page->pagetype === 'course-index-category') ||
            $this->page->pagetype === 'course-search';
    }

    /**
     * Whether Moodle is rendering the root course category index.
     *
     * @return bool
     */
    private function is_course_index_root_page(): bool {
        return $this->page->pagetype === 'course-index-category' && optional_param('categoryid', 0, PARAM_INT) === 0;
    }

    /**
     * Build the visual catalogue layer for /course/index.php.
     *
     * @return string
     */
    private function course_catalogue_experience(): string {
        $data = $this->course_catalogue_data();

        if ($data['issearchpage']) {
            return $this->course_search_experience($data);
        }

        $coursesurl = s($data['coursesurl']);
        $searchurl = s($data['searchurl']);
        $herotitle = 'Find the right course path faster.';
        $herotext = 'Browse live Moodle categories by board, medium, grade, stream and training pathway from one clear academic catalogue.';

        $dashboardhtml = '';
        if (!empty($data['isrootindex'])) {
            $dashboardhtml = $this->render_course_category_catalogue($data);
        } else if (!$data['issearchpage']) {
            $streamhtml = '';
            foreach ($data['streams'] as $stream) {
                $streamhtml .= '<a class="boc-ci-stream ' . s($stream['tone']) . '" href="' . s($stream['url']) . '">' .
                    '<span class="boc-ci-stream-icon"><i class="fa ' . s($stream['icon']) . '" aria-hidden="true"></i></span>' .
                    '<span><strong>' . s($stream['title']) . '</strong><small>' . s($stream['body']) . '</small></span>' .
                    '<em>' . s($stream['meta']) . '</em></a>';
            }

            $coursehtml = '';
            foreach (array_slice($data['courses'], 0, 3) as $course) {
                $coursehtml .= '<a class="boc-ci-course" href="' . s($course['url']) . '">' .
                    '<span><i class="fa ' . s($course['icon']) . '" aria-hidden="true"></i></span>' .
                    '<strong>' . s($course['name']) . '</strong>' .
                    '<small>' . s($course['type']) . '</small></a>';
            }

            $dashboardhtml = '
    <div class="boc-ci-dashboard" aria-label="Catalogue discovery">
        <div class="boc-ci-programmes">
            <div class="boc-ci-section-title">
                <h3>Explore Programmes</h3>
                <a href="' . $coursesurl . '">View catalogue <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="boc-ci-stream-grid">' . $streamhtml . '</div>
        </div>
        <aside class="boc-ci-guide">
            <div class="boc-ci-section-title">
                <h3>Live Course Highlights</h3>
                <a href="' . $searchurl . '">Search <i class="fa fa-search" aria-hidden="true"></i></a>
            </div>
            <div class="boc-ci-course-list">' . $coursehtml . '</div>
        </aside>
    </div>';
        }

        return '
<section class="boc-course-catalogue' . ($data['issearchpage'] ? ' boc-ci-search-page' : '') . '" aria-labelledby="boc-course-catalogue-title">
    <div class="boc-ci-hero">
        <div class="boc-ci-copy">
            <span class="boc-ci-kicker"><i class="fa fa-circle" aria-hidden="true"></i> Moodle course catalogue</span>
            <p class="boc-ci-current">' . s($data['currenttitle']) . '</p>
            <h2 id="boc-course-catalogue-title">' . s($herotitle) . '</h2>
            <p class="boc-ci-text">' . s($herotext) . '</p>
            <div class="boc-ci-chips" aria-label="Catalogue highlights">
                ' . ($data['query'] !== '' ? '<span>Search: ' . s($data['query']) . '</span>' : '') . '
                <span>Gujarat Board</span>
                <span>English Medium</span>
                <span>Academic Year 2026-2027</span>
                <span>School + University + Training</span>
            </div>
        </div>
        <div class="boc-ci-stats" aria-label="Catalogue statistics">
            <article><span></span><strong>' . s(number_format((int)$data['coursecount'])) . '</strong><small>Visible Moodle courses</small></article>
            <article><span></span><strong>' . s(number_format((int)$data['categorycount'])) . '</strong><small>Academic categories</small></article>
            <article><span></span><strong>K-12</strong><small>Nursery to Standard 12</small></article>
            <article><span></span><strong>UG+</strong><small>Vocational, degree, PG and certificates</small></article>
        </div>
    </div>
' . $dashboardhtml . '
</section>';
    }

    /**
     * Render the root course index as category cards backed by Moodle data.
     *
     * @param array $data Public-safe course catalogue data.
     * @return string
     */
    private function render_course_category_catalogue(array $data): string {
        $cards = $data['categorycards'];
        $cardshtml = '';

        foreach ($cards as $card) {
            $tagshtml = '';
            foreach ($card['tags'] as $tag) {
                $tagshtml .= '<span>' . s($tag) . '</span>';
            }

            $sampleshtml = '';
            foreach ($card['samples'] as $sample) {
                $sampleshtml .= '<li>' . s($sample) . '</li>';
            }

            if ($sampleshtml !== '') {
                $sampleshtml = '<ul class="boc-category-samples">' . $sampleshtml . '</ul>';
            }

            $cardshtml .= '
        <a class="boc-category-card boc-category-tone-' . s($card['tone']) . '" href="' . s($card['url']) . '">
            <span class="boc-category-media" aria-hidden="true">
                <span class="boc-category-media-grid"></span>
                <span class="boc-category-media-panel"></span>
                <span class="boc-category-icon3d">' . $this->course_category_svg_icon($card['tone']) . '</span>
                <em>' . s($card['type']) . '</em>
            </span>
            <span class="boc-category-card-body">
                <span class="boc-category-card-top">
                    <span class="boc-category-type">' . s($card['type']) . '</span>
                    <span class="boc-category-count"><strong>' . s(number_format((int)$card['coursecount'])) . '</strong> ' .
                        s((int)$card['coursecount'] === 1 ? 'course' : 'courses') . '</span>
                </span>
                <strong class="boc-category-name">' . s($card['name']) . '</strong>
                <span class="boc-category-desc">' . s($card['description']) . '</span>
                <span class="boc-category-tags">' . $tagshtml . '</span>
                ' . $sampleshtml . '
                <span class="boc-category-cta">Browse category <i class="fa fa-arrow-right" aria-hidden="true"></i></span>
            </span>
        </a>';
        }

        if ($cardshtml === '') {
            $cardshtml = '<div class="boc-category-empty"><i class="fa fa-folder-open-o" aria-hidden="true"></i>' .
                '<strong>No course-ready categories found</strong><span>Add visible courses to Moodle categories to populate this catalogue.</span></div>';
        }

        return '
    <section class="boc-category-catalogue" aria-labelledby="boc-category-catalogue-title">
        <div class="boc-category-banner">
            <div>
                <span class="boc-category-eyebrow"><i class="fa fa-database" aria-hidden="true"></i> Live Moodle category data</span>
                <h3 id="boc-category-catalogue-title">Explore course categories by standard, stream and programme</h3>
                <p>Each category card is generated from Moodle data and opens the original category page with existing browsing, permissions and course behaviour intact.</p>
                <div class="boc-category-summary" aria-label="Category catalogue summary">
                    <span><strong>' . s(number_format(count($cards))) . '</strong><small>course-ready categories</small></span>
                    <span><strong>' . s(number_format((int)$data['coursecount'])) . '</strong><small>visible courses</small></span>
                    <a href="' . s($data['searchurl']) . '">Search courses <i class="fa fa-search" aria-hidden="true"></i></a>
                </div>
            </div>
            <div class="boc-category-banner-art" aria-hidden="true">
                ' . $this->course_category_banner_svg() . '
            </div>
        </div>
        <div class="boc-category-grid">' . $cardshtml . '
        </div>
    </section>';
    }

    /**
     * Decorative SVG for the course category section banner.
     *
     * @return string Inline SVG.
     */
    private function course_category_banner_svg(): string {
        return '<svg viewBox="0 0 420 260" role="img" focusable="false">
            <path d="M36 204c44 27 138 32 207 22 63-9 137-33 150-88 13-56-38-102-94-122C229-9 115 13 66 61 18 108-8 177 36 204Z" fill="#e0f2fe"/>
            <path d="M54 196c46 18 125 17 186 6 59-11 124-36 132-83 8-45-37-79-88-94-62-19-159-2-202 39-41 39-71 112-28 132Z" fill="#dbeafe" opacity=".82"/>
            <rect x="92" y="48" width="228" height="138" rx="22" fill="#fff" opacity=".9" transform="rotate(-5 206 117)"/>
            <rect x="106" y="62" width="200" height="110" rx="18" fill="#bfdbfe" opacity=".58" transform="rotate(-5 206 117)"/>
            <rect x="163" y="56" width="98" height="98" rx="20" fill="#fff" filter="drop-shadow(0 20px 22px rgba(15, 108, 191, .18))"/>
            <path d="M214 83 255 101l-41 18-43-18 43-18Z" fill="#0f6cbf"/>
            <path d="M184 111v28c18 11 41 11 58 0v-28l-28 13-30-13Z" fill="#0891b2"/>
            <path d="M255 103v33" stroke="#0750ca" stroke-width="7" stroke-linecap="round"/>
            <circle cx="255" cy="143" r="8" fill="#f59e0b"/>
            <rect x="42" y="158" width="126" height="52" rx="16" fill="#fff" opacity=".96"/>
            <path d="M66 184h78" stroke="#0f6cbf" stroke-width="10" stroke-linecap="round"/>
            <rect x="285" y="164" width="84" height="36" rx="18" fill="#eff6ff"/>
            <path d="M305 182h43" stroke="#0750ca" stroke-width="8" stroke-linecap="round"/>
            <circle cx="344" cy="47" r="10" fill="#f59e0b"/>
            <circle cx="70" cy="74" r="12" fill="#14b8a6" opacity=".72"/>
        </svg>';
    }

    /**
     * Category tone-specific decorative SVG icon.
     *
     * @param string $tone Category card tone.
     * @return string Inline SVG.
     */
    private function course_category_svg_icon(string $tone): string {
        switch ($tone) {
            case 'school':
                return '<svg viewBox="0 0 120 120" role="img" focusable="false"><path d="M60 18 104 38 60 58 15 38 60 18Z" fill="currentColor"/><path d="M32 54v28c17 13 40 16 56 0V54L60 67 32 54Z" fill="currentColor" opacity=".82"/><path d="M103 40v30" stroke="#0f172a" stroke-width="8" stroke-linecap="round"/><circle cx="103" cy="76" r="9" fill="#f59e0b"/><path d="M39 84h42" stroke="#fff" stroke-width="8" stroke-linecap="round"/></svg>';

            case 'science':
                return '<svg viewBox="0 0 120 120" role="img" focusable="false"><path d="M47 18h26v13l-8 12v12l30 40c5 7 0 16-9 16H34c-9 0-14-9-9-16l30-40V43L47 31V18Z" fill="currentColor"/><path d="M41 79h38l12 17H29l12-17Z" fill="#fff" opacity=".78"/><path d="M48 18h24" stroke="#fff" stroke-width="8" stroke-linecap="round"/><circle cx="78" cy="77" r="7" fill="#f59e0b"/><circle cx="48" cy="91" r="6" fill="#0f172a"/></svg>';

            case 'commerce':
                return '<svg viewBox="0 0 120 120" role="img" focusable="false"><rect x="23" y="34" width="74" height="58" rx="14" fill="currentColor"/><path d="M45 34v-8h30v8" fill="none" stroke="#fff" stroke-width="8" stroke-linecap="round"/><path d="M39 75h13l10-17 11 11h15" fill="none" stroke="#fff" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="36" cy="49" r="6" fill="#f59e0b"/></svg>';

            case 'technology':
                return '<svg viewBox="0 0 120 120" role="img" focusable="false"><rect x="25" y="24" width="70" height="64" rx="16" fill="currentColor"/><path d="m49 46-16 14 16 14M71 46l16 14-16 14" fill="none" stroke="#fff" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/><path d="M62 42 54 78" stroke="#fff" stroke-width="7" stroke-linecap="round" opacity=".86"/><path d="M45 101h30" stroke="#0f172a" stroke-width="9" stroke-linecap="round"/><circle cx="88" cy="29" r="8" fill="#f59e0b"/></svg>';

            case 'health':
                return '<svg viewBox="0 0 120 120" role="img" focusable="false"><path d="M60 101C36 83 20 68 20 47c0-16 12-27 27-27 8 0 14 4 18 10 4-6 10-10 18-10 15 0 27 11 27 27 0 21-16 36-50 54Z" fill="currentColor"/><path d="M60 43v35M43 60h35" stroke="#fff" stroke-width="10" stroke-linecap="round"/><circle cx="93" cy="31" r="8" fill="#f59e0b"/></svg>';

            case 'law':
                return '<svg viewBox="0 0 120 120" role="img" focusable="false"><path d="M60 20v72" stroke="currentColor" stroke-width="10" stroke-linecap="round"/><path d="M29 38h62M43 38 25 74h36L43 38ZM77 38 59 74h36L77 38Z" fill="none" stroke="currentColor" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/><path d="M39 93h42" stroke="#fff" stroke-width="9" stroke-linecap="round"/><circle cx="60" cy="20" r="10" fill="#f59e0b"/></svg>';

            case 'creative':
                return '<svg viewBox="0 0 120 120" role="img" focusable="false"><path d="M30 91c22 2 46-5 61-20 17-17 14-37-2-47-18-11-45-3-59 14-14 16-19 50 0 53Z" fill="currentColor"/><path d="M52 78 82 48" stroke="#fff" stroke-width="10" stroke-linecap="round"/><path d="M87 24 99 36 83 52 71 40 87 24Z" fill="#f59e0b"/><circle cx="39" cy="52" r="7" fill="#fff"/><circle cx="53" cy="39" r="7" fill="#fff"/><circle cx="69" cy="35" r="7" fill="#fff"/></svg>';

            case 'hospitality':
                return '<svg viewBox="0 0 120 120" role="img" focusable="false"><rect x="30" y="39" width="60" height="54" rx="14" fill="currentColor"/><path d="M46 39v-9h28v9" fill="none" stroke="#fff" stroke-width="8" stroke-linecap="round"/><path d="M39 66h42" stroke="#fff" stroke-width="8" stroke-linecap="round"/><path d="M60 24c13 0 24 9 24 21H36c0-12 11-21 24-21Z" fill="#f59e0b"/><circle cx="94" cy="87" r="8" fill="#0f172a"/></svg>';

            default:
                return '<svg viewBox="0 0 120 120" role="img" focusable="false"><path d="M28 28h38c15 0 26 11 26 26v38H54c-15 0-26-11-26-26V28Z" fill="currentColor"/><path d="M44 50h31M44 68h25M44 86h34" stroke="#fff" stroke-width="8" stroke-linecap="round"/><path d="M75 28v22h22" fill="#f59e0b"/><circle cx="31" cy="91" r="8" fill="#14b8a6"/></svg>';
        }
    }

    /**
     * Render a search-first experience for Moodle's course search page.
     *
     * @param array $data Public-safe course catalogue data.
     * @return string
     */
    private function course_search_experience(array $data): string {
        $query = $data['query'];
        $searchurl = s($data['searchurl']);
        $taghtml = $this->course_search_tag_groups($query);

        return '
<section class="boc-course-catalogue boc-ci-search-page boc-search-command boc-search-only" aria-label="Course search">
    <div class="boc-search-stage">
        <span class="boc-search-shape boc-shape-one" aria-hidden="true"></span>
        <span class="boc-search-shape boc-shape-two" aria-hidden="true"></span>
        <span class="boc-search-shape boc-shape-three" aria-hidden="true"></span>
        <form class="boc-search-panel boc-search-only-panel" action="' . $searchurl . '" method="get">
            <div class="boc-search-command-form" role="search">
                <label class="visually-hidden" for="boc-search-command-input">Search courses</label>
                <span class="boc-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span>
                <input id="boc-search-command-input" type="search" name="search" value="' . s($query) . '" placeholder="Search courses">
                <button type="submit">Search <i class="fa fa-arrow-right" aria-hidden="true"></i></button>
            </div>
            ' . $taghtml . '
        </form>
    </div>
</section>';
    }

    /**
     * Render lightweight tag links for the search page.
     *
     * @param string $query Current search query.
     * @return string
     */
    private function course_search_tag_groups(string $query): string {
        $countcache = [];
        $groups = [
            [
                'title' => 'Standards',
                'tone' => 'primary',
                'icon' => 'fa-graduation-cap',
                'items' => [
                    ['All current courses', '2026-2027', '3,222 courses'],
                    ['Nursery', 'Nursery', 'Early learning'],
                    ['Preschool', 'Preschool', 'Early learning'],
                    ['LKG', 'LKG', 'Lower Kindergarten'],
                    ['UKG', 'UKG', 'Upper Kindergarten'],
                    ['Standard 1', 'Standard 1', 'Foundation'],
                    ['Standard 2', 'Standard 2', 'Foundation'],
                    ['Standard 3', 'Standard 3', 'Primary'],
                    ['Standard 4', 'Standard 4', 'Primary'],
                    ['Standard 5', 'Standard 5', 'Primary'],
                    ['Standard 6', 'Standard 6', 'Middle'],
                    ['Standard 7', 'Standard 7', 'Middle'],
                    ['Standard 8', 'Standard 8', 'Middle'],
                    ['Standard 9', 'Standard 9', 'Secondary'],
                    ['Standard 10', 'Standard 10', 'Board prep'],
                    ['Std 11 Science', 'Standard 11 Science', 'Science'],
                    ['Std 11 Commerce', 'Standard 11 Commerce', 'Commerce'],
                    ['Std 11 Arts', 'Standard 11 Arts', 'Arts'],
                    ['Std 12 Science', 'Standard 12 Science', 'Science'],
                    ['Std 12 Commerce', 'Standard 12 Commerce', 'Commerce'],
                    ['Std 12 Arts', 'Standard 12 Arts', 'Arts'],
                ],
            ],
            [
                'title' => 'Degrees',
                'tone' => 'secondary',
                'icon' => 'fa-university',
                'items' => [
                    ['ITI', 'ITI', 'Vocational'],
                    ['Polytechnic', 'Polytechnic Diploma', 'Diploma'],
                    ['B.Tech', 'B.Tech', 'Engineering'],
                    ['BCA', 'BCA', 'Applications'],
                    ['BBA', 'BBA', 'Management'],
                    ['B.Sc Data Science', 'B.Sc Data Science', 'Analytics'],
                    ['MBBS', 'MBBS', 'Medical'],
                    ['Nursing', 'Nursing', 'Healthcare'],
                    ['BA LL.B', 'BA LL.B', 'Law'],
                    ['B.Des', 'B.Des', 'Design'],
                    ['CA', 'CA', 'Finance'],
                    ['CS Executive', 'CS Executive', 'Company Secretary'],
                    ['M.Tech', 'M.Tech', 'Postgraduate'],
                    ['MBA', 'MBA', 'Business'],
                    ['MCA', 'MCA', 'Applications'],
                    ['Ph.D.', 'Ph.D.', 'Research'],
                    ['Certificates', 'Certification', 'Professional'],
                ],
            ],
        ];

        $html = '<div class="boc-search-tags" aria-label="Standard and degree filters">';
        foreach ($groups as $group) {
            $html .= '<section class="boc-tag-section boc-tag-section-' . s($group['tone']) . '">' .
                '<h3><span><i class="fa ' . s($group['icon']) . '" aria-hidden="true"></i></span>' .
                s($group['title']) . '</h3><div class="boc-tag-list">';

            foreach ($group['items'] as $item) {
                $label = $item[0];
                $term = $item[1];
                $count = $this->course_search_tag_count($term, $countcache);
                $isactive = $this->course_search_query_has_term($query, $term);
                $url = (new moodle_url('/course/search.php', ['search' => $term]))->out(false);
                $html .= '<a class="boc-search-tag' . ($isactive ? ' is-active' : '') . '" href="' . s($url) . '">' .
                    '<strong>' . s($label) . '</strong><small>' . s($this->course_search_count_label($count)) . '</small></a>';
            }

            $html .= '</div></section>';
        }

        return $html . '</div>';
    }

    /**
     * Count Moodle courses matching a tag search term.
     *
     * @param string $term Search term.
     * @param array $countcache Counts keyed by lower-case term.
     * @return int
     */
    private function course_search_tag_count(string $term, array &$countcache): int {
        $term = trim($term);
        $cachekey = \core_text::strtolower($term);

        if ($term === '') {
            return 0;
        }

        if (!array_key_exists($cachekey, $countcache)) {
            $countcache[$cachekey] = $this->safe_course_search_count($term);
        }

        return (int)$countcache[$cachekey];
    }

    /**
     * Format a course count for compact search tags.
     *
     * @param int $count Course count.
     * @return string
     */
    private function course_search_count_label(int $count): string {
        return number_format($count) . ' ' . ($count === 1 ? 'course' : 'courses');
    }

    /**
     * Whether the current Moodle search query already contains a tag term.
     *
     * @param string $query Current query.
     * @param string $term Tag search term.
     * @return bool
     */
    private function course_search_query_has_term(string $query, string $term): bool {
        $query = trim($query);
        $term = trim($term);

        if ($query === '' || $term === '') {
            return false;
        }

        return (bool)preg_match('/(^|\s)' . preg_quote($term, '/') . '(?=\s|$)/i', $query);
    }

    /**
     * Build a Moodle course search URL with an optional filter token.
     *
     * @param string $query Current search query.
     * @param string $token Token to append to the query.
     * @param array $remove Tokens to remove before appending the new token.
     * @return string
     */
    private function course_search_filter_url(string $query, string $token = '', array $remove = []): string {
        $nextquery = trim($query);

        foreach ($remove as $removeitem) {
            $nextquery = preg_replace('/\b' . preg_quote($removeitem, '/') . '\b/i', ' ', $nextquery);
        }

        $nextquery = trim(preg_replace('/\s+/', ' ', $nextquery));
        if ($token !== '' && stripos($nextquery, $token) === false) {
            $nextquery = trim($nextquery . ' ' . $token);
        }

        $params = [];
        if ($nextquery !== '') {
            $params['search'] = $nextquery;
        }

        return (new moodle_url('/course/search.php', $params))->out(false);
    }

    /**
     * Render one horizontal dropdown filter from database-backed options.
     *
     * @param array $group Filter group data.
     * @return string
     */
    private function render_course_search_filter_dropdown(array $group): string {
        $selectedcount = 0;
        $optionshtml = '';

        foreach ($group['options'] as $option) {
            if (!empty($option['selected'])) {
                $selectedcount++;
            }
            $id = 'boc-filter-' . md5($group['key'] . '-' . $option['label']);
            $optionshtml .= '<label class="boc-dropdown-option" for="' . s($id) . '">' .
                '<input id="' . s($id) . '" type="checkbox" name="bocfilter[]" value="' . s($option['label']) . '"' .
                ' data-boc-filter-option data-boc-filter-group="' . s($group['key']) . '" data-boc-searchable="' .
                (!empty($group['searchable']) ? '1' : '0') . '"' . (!empty($option['selected']) ? ' checked' : '') . '>' .
                '<span><i class="fa ' . s($option['icon']) . '" aria-hidden="true"></i></span><strong>' .
                s($option['label']) . '</strong><small>' . s($option['meta']) . '</small></label>';
        }

        if ($optionshtml === '') {
            $optionshtml = '<p class="boc-dropdown-empty">No visible values found in Moodle data.</p>';
        }

        $countlabel = $selectedcount > 0 ? $selectedcount . ' selected' : 'Select';

        return '<details class="boc-filter-dropdown" data-boc-filter-dropdown data-boc-filter-group="' .
            s($group['key']) . '"><summary><span><i class="fa ' . s($group['icon']) .
            '" aria-hidden="true"></i></span><b>' . s($group['title']) .
            '</b><em data-boc-dropdown-count>' . s($countlabel) .
            '</em><i class="fa fa-angle-down" aria-hidden="true"></i></summary><div class="boc-dropdown-menu">' .
            $optionshtml . '</div></details>';
    }

    /**
     * Selected filter labels from query params.
     *
     * @return array
     */
    private function course_search_selected_filters(): array {
        $filters = optional_param_array('bocfilter', [], PARAM_RAW);
        $clean = [];

        foreach ($filters as $filter) {
            $label = $this->course_search_clean_label((string)$filter);
            if ($label === '') {
                continue;
            }
            $clean[\core_text::strtolower($label)] = $label;
        }

        return array_values($clean);
    }

    /**
     * Generate horizontal filter data from visible Moodle categories, courses and teachers.
     *
     * @param array $selectedfilters Selected filter labels from the request.
     * @return array
     */
    private function course_search_filter_groups(array $selectedfilters): array {
        global $DB, $SITE;

        $selectedmap = [];
        foreach ($selectedfilters as $filter) {
            $selectedmap[\core_text::strtolower($filter)] = true;
        }

        $groups = [
            'medium' => [
                'key' => 'medium',
                'title' => 'Medium',
                'icon' => 'fa-language',
                'searchable' => true,
                'options' => [],
                'seen' => [],
            ],
            'standards' => [
                'key' => 'standards',
                'title' => 'Standards',
                'icon' => 'fa-list-ol',
                'searchable' => true,
                'options' => [],
                'seen' => [],
            ],
            'streams' => [
                'key' => 'streams',
                'title' => 'Streams and subjects',
                'icon' => 'fa-sitemap',
                'searchable' => false,
                'options' => [],
                'seen' => [],
            ],
            'programmes' => [
                'key' => 'programmes',
                'title' => 'Programmes',
                'icon' => 'fa-briefcase',
                'searchable' => true,
                'options' => [],
                'seen' => [],
            ],
            'teacher' => [
                'key' => 'teacher',
                'title' => 'Teacher',
                'icon' => 'fa-user-o',
                'searchable' => false,
                'options' => [],
                'seen' => [],
            ],
        ];

        try {
            $categories = $DB->get_records_select(
                'course_categories',
                'visible = ?',
                [1],
                'sortorder ASC',
                'id, name, parent'
            );
        } catch (\dml_exception $exception) {
            $categories = [];
        }

        $categorymap = [];
        foreach ($categories as $category) {
            $categorymap[(int)$category->id] = [
                'name' => $this->course_search_clean_label($category->name),
                'parent' => (int)$category->parent,
            ];
        }

        foreach ($categorymap as $category) {
            $name = $category['name'];
            $parentname = $categorymap[$category['parent']]['name'] ?? '';

            if ($name === '' || $this->course_search_is_system_category($name)) {
                continue;
            }

            if (preg_match('/\bmedium$/i', $name)) {
                $this->course_search_add_filter_option($groups, 'medium', $name, 'Language medium', 'fa-language', $selectedmap);
                continue;
            }

            if ($this->course_search_is_standard_label($name)) {
                $this->course_search_add_filter_option($groups, 'standards', $name, 'Academic level', 'fa-graduation-cap', $selectedmap);
                continue;
            }

            if (preg_match('/\bmedium$/i', $parentname)) {
                $this->course_search_add_filter_option($groups, 'programmes', $name, 'Programme category', 'fa-book', $selectedmap);
                continue;
            }

            if ($parentname !== '') {
                $this->course_search_add_filter_option($groups, 'streams', $name, 'Category: ' . $parentname, 'fa-th-large', $selectedmap);
            }
        }

        try {
            $courses = $DB->get_records_select(
                'course',
                'id <> ? AND visible = ?',
                [$SITE->id, 1],
                'sortorder ASC',
                'id, fullname',
                0,
                800
            );
        } catch (\dml_exception $exception) {
            $courses = [];
        }

        foreach ($courses as $course) {
            $subject = $this->course_search_extract_subject($course->fullname);
            if ($subject !== '') {
                $this->course_search_add_filter_option($groups, 'streams', $subject, 'Course subject', 'fa-bookmark', $selectedmap);
            }
        }

        foreach ($this->course_search_teacher_names() as $teacher) {
            $this->course_search_add_filter_option($groups, 'teacher', $teacher, 'Course teacher', 'fa-user', $selectedmap);
        }

        foreach ($groups as &$group) {
            unset($group['seen']);
        }
        unset($group);

        return array_values($groups);
    }

    /**
     * Add a unique filter option to a group.
     *
     * @param array $groups Filter groups by key.
     * @param string $groupkey Group key.
     * @param string $label Option label.
     * @param string $meta Option support text.
     * @param string $icon Font Awesome icon.
     * @param array $selectedmap Selected labels keyed by lower-case label.
     */
    private function course_search_add_filter_option(
        array &$groups,
        string $groupkey,
        string $label,
        string $meta,
        string $icon,
        array $selectedmap
    ): void {
        $label = $this->course_search_clean_label($label);
        $meta = $this->course_search_clean_label($meta);
        if ($label === '' || !isset($groups[$groupkey])) {
            return;
        }

        $key = \core_text::strtolower($label);
        if (isset($groups[$groupkey]['seen'][$key])) {
            return;
        }

        $groups[$groupkey]['seen'][$key] = true;
        $groups[$groupkey]['options'][] = [
            'label' => $label,
            'meta' => $meta,
            'icon' => $icon,
            'selected' => !empty($selectedmap[$key]),
        ];
    }

    /**
     * Selected labels that exist in the rendered dropdown groups.
     *
     * @param array $filtergroups Filter group data.
     * @return array
     */
    private function course_search_selected_labels(array $filtergroups): array {
        $labels = [];

        foreach ($filtergroups as $group) {
            foreach ($group['options'] as $option) {
                if (!empty($option['selected'])) {
                    $labels[] = $option['label'];
                }
            }
        }

        return $labels;
    }

    /**
     * Remove selected filter terms from the free-text input value.
     *
     * @param string $query Full Moodle search query.
     * @param array $terms Selected filter terms.
     * @return string
     */
    private function course_search_without_terms(string $query, array $terms): string {
        usort($terms, function($first, $second) {
            return strlen($second) <=> strlen($first);
        });

        $nextquery = $query;
        foreach ($terms as $term) {
            $nextquery = preg_replace('/(^|\s)' . preg_quote($term, '/') . '(?=\s|$)/i', ' ', $nextquery);
        }

        return trim(preg_replace('/\s+/', ' ', $nextquery));
    }

    /**
     * Clean labels read from Moodle data before showing them as filters.
     *
     * @param string $label Raw label.
     * @return string
     */
    private function course_search_clean_label(string $label): string {
        $label = html_entity_decode(strip_tags($label), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/', ' ', $label));
    }

    /**
     * Whether a category is an internal parent level that should not appear as a filter.
     *
     * @param string $name Category name.
     * @return bool
     */
    private function course_search_is_system_category(string $name): bool {
        return (bool)preg_match('/^(category\s+\d+|drona education trust|gujarat board education|drona public school|academic year\s+\d{4}(?:-\d{4})?)$/i', $name);
    }

    /**
     * Whether a category name represents an academic standard.
     *
     * @param string $name Category name.
     * @return bool
     */
    private function course_search_is_standard_label(string $name): bool {
        return (bool)preg_match('/^(nursery|preschool|lkg\b|ukg\b|standard\s+\d+)/i', $name);
    }

    /**
     * Extract a subject-like label from a Moodle course fullname.
     *
     * @param string $fullname Course fullname.
     * @return string
     */
    private function course_search_extract_subject(string $fullname): string {
        $name = $this->course_search_clean_label($fullname);
        $name = preg_replace('/\s+-\s+(?:20\d{2}(?:-\d{4})?|y\d+|year\s+\d+)$/i', '', $name);
        $parts = preg_split('/\s+-\s+/', $name);
        $subject = $parts ? trim((string)end($parts)) : '';

        if ($subject === '' || preg_match('/^(gen|general|english medium|gujarati medium|hindi medium)$/i', $subject)) {
            return '';
        }

        return $subject;
    }

    /**
     * Get unique teacher names assigned to visible courses.
     *
     * @return array
     */
    private function course_search_teacher_names(): array {
        global $DB;

        try {
            [$rolesql, $params] = $DB->get_in_or_equal(['teacher', 'editingteacher'], SQL_PARAMS_NAMED, 'role');
            $params['contextlevel'] = CONTEXT_COURSE;
            $params['visible'] = 1;
            $params['deleted'] = 0;
            $params['suspended'] = 0;
            $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname
                      FROM {user} u
                      JOIN {role_assignments} ra ON ra.userid = u.id
                      JOIN {role} r ON r.id = ra.roleid
                      JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :contextlevel
                      JOIN {course} c ON c.id = ctx.instanceid AND c.visible = :visible
                     WHERE r.shortname {$rolesql}
                       AND u.deleted = :deleted
                       AND u.suspended = :suspended
                  ORDER BY u.lastname ASC, u.firstname ASC";
            $records = $DB->get_records_sql($sql, $params);
        } catch (\dml_exception $exception) {
            $records = [];
        }

        $teachers = [];
        foreach ($records as $record) {
            $name = $this->course_search_clean_label($record->firstname . ' ' . $record->lastname);
            if ($name !== '') {
                $teachers[] = $name;
            }
        }

        return $teachers;
    }

    /**
     * Public-safe course catalogue data.
     *
     * @return array
     */
    private function course_catalogue_data(): array {
        global $SITE;

        $query = trim(strip_tags((string)optional_param('q', optional_param('search', '', PARAM_RAW), PARAM_RAW)));
        $issearchpage = $this->page->pagetype === 'course-search';
        $currenttitle = format_string($this->page->heading, true, [
            'context' => $this->page->context,
            'escape' => false,
        ]);

        $searchurl = new moodle_url('/course/search.php');
        $streams = [
            [
                'title' => 'School Learning',
                'body' => 'Nursery, preschool, LKG, UKG and standards 1-12.',
                'meta' => 'GSEB',
                'icon' => 'fa-book',
                'tone' => 'blue',
                'url' => (new moodle_url('/course/search.php', ['search' => 'Drona Public School']))->out(false),
            ],
            [
                'title' => 'ITI & Vocational',
                'body' => 'Electrician, fitter and practical training streams.',
                'meta' => 'Skills',
                'icon' => 'fa-wrench',
                'tone' => 'green',
                'url' => (new moodle_url('/course/search.php', ['search' => 'ITI']))->out(false),
            ],
            [
                'title' => 'Polytechnic Diploma',
                'body' => 'Mechanical, civil and technical year-wise paths.',
                'meta' => 'Y1-Y3',
                'icon' => 'fa-cogs',
                'tone' => 'orange',
                'url' => (new moodle_url('/course/search.php', ['search' => 'Polytechnic Diploma']))->out(false),
            ],
            [
                'title' => 'UG & PG Degrees',
                'body' => 'B.Tech, BCA, BBA, MBA, M.Tech, MCA and research.',
                'meta' => 'College',
                'icon' => 'fa-university',
                'tone' => 'violet',
                'url' => (new moodle_url('/course/search.php', ['search' => 'B.Tech']))->out(false),
            ],
            [
                'title' => 'Medical & Health',
                'body' => 'MBBS, GNM, nursing and allied healthcare tracks.',
                'meta' => 'Medical',
                'icon' => 'fa-heartbeat',
                'tone' => 'teal',
                'url' => (new moodle_url('/course/search.php', ['search' => 'MBBS']))->out(false),
            ],
            [
                'title' => 'Commerce & Certificates',
                'body' => 'CA, CS, cloud, MLOps, agile and marketing paths.',
                'meta' => 'Certificate',
                'icon' => 'fa-certificate',
                'tone' => 'rose',
                'url' => (new moodle_url('/course/search.php', ['search' => 'Certificate']))->out(false),
            ],
        ];

        return [
            'currenttitle' => $currenttitle,
            'issearchpage' => $issearchpage,
            'query' => $query,
            'searchcount' => $query === '' ? 0 : $this->safe_course_search_count($query),
            'coursecount' => $this->safe_count_records_select('course', 'id <> ? AND visible = ?', [$SITE->id, 1]),
            'categorycount' => $this->safe_count_records_select('course_categories', 'visible = ?', [1]),
            'courses' => $this->get_course_cards(),
            'categorycards' => $this->get_course_category_cards(),
            'coursesurl' => (new moodle_url('/course/index.php'))->out(false),
            'isrootindex' => $this->is_course_index_root_page(),
            'searchurl' => $searchurl->out(false),
            'streams' => $streams,
        ];
    }

    /**
     * Count matching visible courses for the active Moodle search query.
     *
     * @param string $query Course search query.
     * @return int
     */
    private function safe_course_search_count(string $query): int {
        try {
            return (int)\core_course_category::search_courses_count(['search' => $query]);
        } catch (\Throwable $exception) {
            return 0;
        }
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
            'categorycount' => $this->safe_count_records_select('course_categories', 'visible = ?', [1]),
            'activeusers' => $activeusers,
            'onlineusers' => $onlineusers,
            'students' => $this->count_role_users(['student']),
            'parents' => $this->count_role_users(['parent']),
            'teachers' => $this->count_role_users(['teacher', 'editingteacher']),
            'principals' => $this->count_role_users(['manager', 'coursecreator', 'principal']),
            'quizattempts' => $this->safe_count_records_select('quiz_attempts', 'state = ?', ['finished']),
            'assignmentsubmissions' => $this->safe_count_records_select('assign_submission', 'status = ?', ['submitted']),
            'discussionposts' => $this->safe_count_records_select('forum_discussions', 'id > ?', [0]),
            'badgesawarded' => $this->safe_count_records_select('badge_issued', 'id > ?', [0]),
            'mobilelearners' => $this->safe_count_records_sql('SELECT COUNT(DISTINCT userid) FROM {user_devices} WHERE userid > ?', [0]),
            'completionrate' => $this->safe_course_completion_rate(),
            'learningtools' => $this->safe_count_records_select('modules', 'visible = ?', [1]),
            'categorygroups' => $this->frontpage_course_category_groups(),
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
            certificates, and role-based learning access from one digital campus.
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
        $categorycount = s(number_format((int)$data['categorycount']));
        $activeusers = s(number_format((int)$data['activeusers']));
        $students = s(number_format((int)$data['students']));
        $teachers = s(number_format((int)$data['teachers']));
        $parents = s(number_format((int)$data['parents']));
        $principals = s(number_format((int)$data['principals']));
        $coursesurl = s($data['coursesurl']);
        $quizmetric = $this->render_hero_single_metric((int)$data['quizattempts'], 'quizzes completed');
        $assignmentmetric = $this->render_hero_single_metric((int)$data['assignmentsubmissions'], 'assignments submitted');
        $completionmetric = $this->render_hero_single_metric($data['completionrate'], 'completion rate', '%');
        $discussionmetric = $this->render_hero_single_metric((int)$data['discussionposts'], 'learning discussions');
        $mobilemetric = $this->render_hero_single_metric((int)$data['mobilelearners'], 'mobile learners');
        $badgemetric = $this->render_hero_single_metric((int)$data['badgesawarded'], 'badges awarded');
        $toolmetric = $this->render_hero_single_metric((int)$data['learningtools'], 'learning tools');

        return '
<div class="boc-slider-card boc-hero-depth" data-boc-hero-depth>
    <div id="boc-hero-slider" class="carousel slide boc-hero-slider boc-spotlight-slider boc-hero-showcase" data-bs-ride="carousel" data-bs-interval="5600" data-bs-pause="hover" aria-label="Featured learning highlights">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#boc-hero-slider" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Go to feature 1"><span>01</span></button>
            <button type="button" data-bs-target="#boc-hero-slider" data-bs-slide-to="1" aria-label="Go to feature 2"><span>02</span></button>
            <button type="button" data-bs-target="#boc-hero-slider" data-bs-slide-to="2" aria-label="Go to feature 3"><span>03</span></button>
            <button type="button" data-bs-target="#boc-hero-slider" data-bs-slide-to="3" aria-label="Go to feature 4"><span>04</span></button>
            <button type="button" data-bs-target="#boc-hero-slider" data-bs-slide-to="4" aria-label="Go to feature 5"><span>05</span></button>
            <button type="button" data-bs-target="#boc-hero-slider" data-bs-slide-to="5" aria-label="Go to feature 6"><span>06</span></button>
            <button type="button" data-bs-target="#boc-hero-slider" data-bs-slide-to="6" aria-label="Go to feature 7"><span>07</span></button>
            <button type="button" data-bs-target="#boc-hero-slider" data-bs-slide-to="7" aria-label="Go to feature 8"><span>08</span></button>
            <button type="button" data-bs-target="#boc-hero-slider" data-bs-slide-to="8" aria-label="Go to feature 9"><span>09</span></button>
            <button type="button" data-bs-target="#boc-hero-slider" data-bs-slide-to="9" aria-label="Go to feature 10"><span>10</span></button>
        </div>
        <div class="carousel-inner">
            <article class="carousel-item active">
                <div class="boc-slider-slide boc-slide-ecosystem boc-slide-smart-campus">
                    <div class="boc-slide-copy">
                        <span class="boc-slide-label"><i class="fa fa-bolt" aria-hidden="true"></i> Live learning ecosystem</span>
                        <h3>One digital campus for every learner journey</h3>
                        <p>Live courses, categories, learners and educators are presented with a public-first learning experience.</p>
                        <div class="boc-slide-metrics">
                            <span><b>' . $coursecount . '</b> courses</span>
                            <span><b>' . $categorycount . '</b> categories</span>
                            <span><b>' . $activeusers . '</b> active users</span>
                        </div>
                        <div class="boc-slide-feature-row">
                            <span><i class="fa fa-shield" aria-hidden="true"></i> Secure access</span>
                            <span><i class="fa fa-language" aria-hidden="true"></i> Multi medium</span>
                            <span><i class="fa fa-certificate" aria-hidden="true"></i> Certificates</span>
                        </div>
                        <a href="#discover">Discover platform</a>
                    </div>
                    <div class="boc-slide-visual boc-visual-orbit boc-visual-smart" aria-hidden="true">
                        <svg class="boc-hero-svg boc-svg-campus" viewBox="0 0 620 390" focusable="false">
                            <defs>
                                <linearGradient id="bocCampusBlue" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0" stop-color="#0f6cbf"/>
                                    <stop offset=".52" stop-color="#0891b2"/>
                                    <stop offset="1" stop-color="#f59e0b"/>
                                </linearGradient>
                                <linearGradient id="bocCampusGlass" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0" stop-color="#ffffff" stop-opacity=".92"/>
                                    <stop offset="1" stop-color="#dbeafe" stop-opacity=".58"/>
                                </linearGradient>
                            </defs>
                            <path class="boc-svg-track" d="M92 281 C168 141 327 83 487 126"/>
                            <path class="boc-svg-track two" d="M150 317 C240 221 344 192 542 221"/>
                            <g class="boc-svg-building">
                                <path d="M235 285 L235 181 L310 131 L388 181 L388 285 Z" fill="url(#bocCampusGlass)"/>
                                <path d="M221 285 H403" stroke="#0f172a" stroke-opacity=".18" stroke-width="8" stroke-linecap="round"/>
                                <path d="M261 203 H361 M261 233 H361 M261 263 H361" stroke="#0f6cbf" stroke-opacity=".62" stroke-width="8" stroke-linecap="round"/>
                                <path d="M310 131 L408 188 L212 188 Z" fill="url(#bocCampusBlue)" opacity=".88"/>
                                <circle cx="311" cy="175" r="18" fill="#fff" fill-opacity=".84"/>
                            </g>
                            <g class="boc-svg-node node-one"><circle cx="112" cy="253" r="30"/><text x="112" y="260">CBSE</text></g>
                            <g class="boc-svg-node node-two"><circle cx="476" cy="126" r="31"/><text x="476" y="133">GSEB</text></g>
                            <g class="boc-svg-node node-three"><circle cx="505" cy="263" r="34"/><text x="505" y="270">Hub</text></g>
                            <g class="boc-svg-node node-four"><circle cx="172" cy="122" r="28"/><text x="172" y="129">AI</text></g>
                        </svg>
                        <div class="boc-orbit">
                            <span class="boc-orbit-ring ring-one"></span>
                            <span class="boc-orbit-ring ring-two"></span>
                            <span class="boc-orbit-ring ring-three"></span>
                            <span class="boc-orbit-core"><i class="fa fa-graduation-cap" aria-hidden="true"></i><small>Learning hub</small></span>
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
                <div class="boc-slider-slide boc-slide-courseflow">
                    <div class="boc-slide-copy">
                        <span class="boc-slide-label"><i class="fa fa-play-circle" aria-hidden="true"></i> Interactive course delivery</span>
                        <h3>Course spaces that feel clear before login</h3>
                        <p>Explain lessons, activities, assignments, quizzes and completion pathways with a modern public overview.</p>
                        ' . $quizmetric . '
                        <div class="boc-slide-feature-row">
                            <span><i class="fa fa-video-camera" aria-hidden="true"></i> Smart lessons</span>
                            <span><i class="fa fa-check-square-o" aria-hidden="true"></i> Activities</span>
                            <span><i class="fa fa-trophy" aria-hidden="true"></i> Completion</span>
                        </div>
                        <a href="' . $coursesurl . '">Browse courses</a>
                    </div>
                    <div class="boc-slide-visual boc-visual-courseflow" aria-hidden="true">
                        ' . $this->render_hero_feature_svg('course', true) . '
                        <div class="boc-lesson-device">
                            <span class="boc-device-toolbar"><i></i><i></i><i></i></span>
                            <strong>Interactive Learning</strong>
                            <em>Video lesson</em>
                            <span class="boc-device-wave"></span>
                            <div class="boc-device-modules">
                                <b><i class="fa fa-book"></i> Resource</b>
                                <b><i class="fa fa-question-circle"></i> Quiz</b>
                                <b><i class="fa fa-upload"></i> Assignment</b>
                            </div>
                        </div>
                        <div class="boc-floating-card card-one"><i class="fa fa-certificate"></i><b>Certificate</b><small>Auto issue ready</small></div>
                        <div class="boc-floating-card card-two"><i class="fa fa-comments-o"></i><b>Feedback</b><small>Teacher review</small></div>
                        <div class="boc-floating-card card-three"><i class="fa fa-line-chart"></i><b>Progress</b><small>Track completion</small></div>
                    </div>
                </div>
            </article>
            <article class="carousel-item">
                <div class="boc-slider-slide boc-slide-gradeflow">
                    <div class="boc-slide-copy">
                        <span class="boc-slide-label"><i class="fa fa-line-chart" aria-hidden="true"></i> Results and grade system</span>
                        <h3>Transparent grades for students, teachers and principals</h3>
                        <p>Show gradebook progress, exam readiness, teacher feedback and report visibility in one clean workflow.</p>
                        ' . $assignmentmetric . '
                        <div class="boc-slide-feature-row">
                            <span><i class="fa fa-list-alt" aria-hidden="true"></i> Gradebook</span>
                            <span><i class="fa fa-bar-chart" aria-hidden="true"></i> Results</span>
                            <span><i class="fa fa-file-text-o" aria-hidden="true"></i> Reports</span>
                        </div>
                        <a href="#grade-system">View grade system</a>
                    </div>
                    <div class="boc-slide-visual boc-visual-gradebook" aria-hidden="true">
                        ' . $this->render_hero_feature_svg('assignment', true) . '
                        <div class="boc-gradebook-panel">
                            <span class="boc-gradebook-head"><b>Gradebook</b><em>2026-2027</em></span>
                            <div class="boc-grade-row"><span>Assignments</span><i style="--boc-grade: 86%"></i><b>A</b></div>
                            <div class="boc-grade-row"><span>Quizzes</span><i style="--boc-grade: 78%"></i><b>B+</b></div>
                            <div class="boc-grade-row"><span>Term exam</span><i style="--boc-grade: 91%"></i><b>A+</b></div>
                            <div class="boc-grade-total"><strong>85%</strong><small>Result readiness</small></div>
                        </div>
                        <div class="boc-result-card"><i class="fa fa-check"></i><b>Parent view</b><small>Progress summary shared</small></div>
                    </div>
                </div>
            </article>
            <article class="carousel-item">
                <div class="boc-slider-slide boc-slide-roleflow">
                    <div class="boc-slide-copy">
                        <span class="boc-slide-label"><i class="fa fa-users" aria-hidden="true"></i> Role based access</span>
                        <h3>Every role lands in the right learning space</h3>
                        <p>Students learn, teachers assess, parents monitor and principals review outcomes through secure access.</p>
                        <div class="boc-slide-metrics boc-role-metrics">
                            <span><b>' . $students . '</b> students</span>
                            <span><b>' . $teachers . '</b> teachers</span>
                            <span><b>' . $parents . '</b> parents</span>
                            <span><b>' . $principals . '</b> leaders</span>
                        </div>
                        <a href="#role-login">Explore roles</a>
                    </div>
                    <div class="boc-slide-visual boc-visual-roles" aria-hidden="true">
                        ' . $this->render_hero_feature_svg('role', true) . '
                        <div class="boc-role-hub">
                            <strong>Secure Login</strong>
                            <small>One secure account</small>
                            <span class="boc-role-line line-student"></span>
                            <span class="boc-role-line line-teacher"></span>
                            <span class="boc-role-line line-parent"></span>
                            <span class="boc-role-line line-principal"></span>
                        </div>
                        <div class="boc-role-node node-student"><i class="fa fa-graduation-cap"></i><b>Student</b><small>Courses and grades</small></div>
                        <div class="boc-role-node node-teacher"><i class="fa fa-user-md"></i><b>Teacher</b><small>Activities and feedback</small></div>
                        <div class="boc-role-node node-parent"><i class="fa fa-users"></i><b>Parent</b><small>Progress view</small></div>
                        <div class="boc-role-node node-principal"><i class="fa fa-institution"></i><b>Principal</b><small>Reports and outcomes</small></div>
                    </div>
                </div>
            </article>
            <article class="carousel-item">
                <div class="boc-slider-slide boc-slide-progressflow">
                    <div class="boc-slide-copy">
                        <span class="boc-slide-label"><i class="fa fa-flag-checkered" aria-hidden="true"></i> Progress tracking</span>
                        <h3>Track every learning milestone</h3>
                        <p>Monitor activity completion, course progress, grades, competencies and learner achievements from one central view.</p>
                        ' . $completionmetric . '
                        <div class="boc-slide-feature-row">
                            <span><i class="fa fa-check-circle" aria-hidden="true"></i> Completion</span>
                            <span><i class="fa fa-line-chart" aria-hidden="true"></i> Progress trends</span>
                            <span><i class="fa fa-bullseye" aria-hidden="true"></i> Learning gaps</span>
                        </div>
                        <a href="#grade-system">Track progress</a>
                    </div>
                    <div class="boc-slide-visual boc-visual-svgscene boc-visual-progress" aria-hidden="true">
                        ' . $this->render_hero_feature_svg('progress') . '
                    </div>
                </div>
            </article>
            <article class="carousel-item">
                <div class="boc-slider-slide boc-slide-collaborationflow">
                    <div class="boc-slide-copy">
                        <span class="boc-slide-label"><i class="fa fa-comments-o" aria-hidden="true"></i> Collaborative learning</span>
                        <h3>Learn better together</h3>
                        <p>Encourage participation through discussions, messaging, shared resources, group activities and peer learning.</p>
                        ' . $discussionmetric . '
                        <div class="boc-slide-feature-row">
                            <span><i class="fa fa-users" aria-hidden="true"></i> Study groups</span>
                            <span><i class="fa fa-commenting-o" aria-hidden="true"></i> Discussions</span>
                            <span><i class="fa fa-share-alt" aria-hidden="true"></i> Shared work</span>
                        </div>
                        <a href="#role-login">Join the community</a>
                    </div>
                    <div class="boc-slide-visual boc-visual-svgscene boc-visual-collaboration" aria-hidden="true">
                        ' . $this->render_hero_feature_svg('collaboration') . '
                    </div>
                </div>
            </article>
            <article class="carousel-item">
                <div class="boc-slider-slide boc-slide-mobileflow">
                    <div class="boc-slide-copy">
                        <span class="boc-slide-label"><i class="fa fa-mobile" aria-hidden="true"></i> Mobile learning</span>
                        <h3>Learn anytime, anywhere</h3>
                        <p>Access courses, activities, messages, notifications and learning resources from classroom, home or mobile devices.</p>
                        ' . $mobilemetric . '
                        <div class="boc-slide-feature-row">
                            <span><i class="fa fa-cloud-download" aria-hidden="true"></i> Resource access</span>
                            <span><i class="fa fa-bell-o" aria-hidden="true"></i> Notifications</span>
                            <span><i class="fa fa-refresh" aria-hidden="true"></i> Sync ready</span>
                        </div>
                        <a href="#discover">Learn anywhere</a>
                    </div>
                    <div class="boc-slide-visual boc-visual-svgscene boc-visual-mobile" aria-hidden="true">
                        ' . $this->render_hero_feature_svg('mobile') . '
                    </div>
                </div>
            </article>
            <article class="carousel-item">
                <div class="boc-slider-slide boc-slide-achievementflow">
                    <div class="boc-slide-copy">
                        <span class="boc-slide-label"><i class="fa fa-certificate" aria-hidden="true"></i> Badges and competencies</span>
                        <h3>Recognise skills and achievements</h3>
                        <p>Reward course completion, activity milestones, demonstrated competencies and measurable learner accomplishments.</p>
                        ' . $badgemetric . '
                        <div class="boc-slide-feature-row">
                            <span><i class="fa fa-star" aria-hidden="true"></i> Badges</span>
                            <span><i class="fa fa-trophy" aria-hidden="true"></i> Awards</span>
                            <span><i class="fa fa-crosshairs" aria-hidden="true"></i> Competencies</span>
                        </div>
                        <a href="#grade-system">Discover achievements</a>
                    </div>
                    <div class="boc-slide-visual boc-visual-svgscene boc-visual-achievement" aria-hidden="true">
                        ' . $this->render_hero_feature_svg('achievement') . '
                    </div>
                </div>
            </article>
            <article class="carousel-item">
                <div class="boc-slider-slide boc-slide-analyticsflow">
                    <div class="boc-slide-copy">
                        <span class="boc-slide-label"><i class="fa fa-pie-chart" aria-hidden="true"></i> Reports and analytics</span>
                        <h3>Turn learning data into insights</h3>
                        <p>Use completion data, participation information, grades and reports to support better academic decisions.</p>
                        <div class="boc-slide-feature-row">
                            <span><i class="fa fa-bar-chart" aria-hidden="true"></i> Trends</span>
                            <span><i class="fa fa-search" aria-hidden="true"></i> Insights</span>
                            <span><i class="fa fa-area-chart" aria-hidden="true"></i> Outcomes</span>
                        </div>
                        <a href="#grade-system">View insights</a>
                    </div>
                    <div class="boc-slide-visual boc-visual-svgscene boc-visual-analytics" aria-hidden="true">
                        ' . $this->render_hero_feature_svg('analytics') . '
                    </div>
                </div>
            </article>
            <article class="carousel-item">
                <div class="boc-slider-slide boc-slide-platformflow">
                    <div class="boc-slide-copy">
                        <span class="boc-slide-label"><i class="fa fa-shield" aria-hidden="true"></i> Flexible learning platform</span>
                        <h3>Secure, accessible and customisable</h3>
                        <p>Build an inclusive learning environment with privacy controls, themes, extensions and connected learning tools.</p>
                        ' . $toolmetric . '
                        <div class="boc-slide-feature-row">
                            <span><i class="fa fa-universal-access" aria-hidden="true"></i> Accessibility</span>
                            <span><i class="fa fa-lock" aria-hidden="true"></i> Privacy</span>
                            <span><i class="fa fa-plug" aria-hidden="true"></i> Extensions</span>
                        </div>
                        <a href="#discover">Explore features</a>
                    </div>
                    <div class="boc-slide-visual boc-visual-svgscene boc-visual-platform" aria-hidden="true">
                        ' . $this->render_hero_feature_svg('platform') . '
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
     * Render a single safe metric for feature slides.
     *
     * @param int|null $value Real aggregate value.
     * @param string $label Metric label.
     * @param string $suffix Suffix appended to the number.
     * @return string
     */
    private function render_hero_single_metric(?int $value, string $label, string $suffix = '+'): string {
        if ($value === null || $value < 1) {
            return '';
        }

        return '<div class="boc-slide-metrics"><span><b>' . s(number_format($value) . $suffix) . '</b> ' .
            s($label) . '</span></div>';
    }

    /**
     * Render a scoped decorative 3D SVG for the hero feature slider.
     *
     * @param string $variant Scene variant.
     * @return string
     */
    private function render_hero_feature_svg(string $variant, bool $underlay = false): string {
        $variant = preg_replace('/[^a-z0-9]+/', '', strtolower($variant)) ?: 'feature';
        $idbase = 'bocHeroFeature' . ucfirst($variant);
        $palette = [
            'progress' => ['#0f6cbf', '#10b981', '#f59e0b'],
            'course' => ['#0f6cbf', '#0891b2', '#f59e0b'],
            'assignment' => ['#2563eb', '#f97316', '#22c55e'],
            'role' => ['#7c3aed', '#0f6cbf', '#f59e0b'],
            'collaboration' => ['#7c3aed', '#0891b2', '#f97316'],
            'mobile' => ['#0284c7', '#06b6d4', '#84cc16'],
            'achievement' => ['#f59e0b', '#ec4899', '#0f6cbf'],
            'analytics' => ['#2563eb', '#14b8a6', '#f97316'],
            'platform' => ['#0f6cbf', '#22c55e', '#8b5cf6'],
        ];
        [$primary, $secondary, $accent] = $palette[$variant] ?? ['#0f6cbf', '#0891b2', '#f59e0b'];

        $defs = '
            <defs>
                <linearGradient id="' . $idbase . 'A" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="' . s($primary) . '"/>
                    <stop offset=".58" stop-color="' . s($secondary) . '"/>
                    <stop offset="1" stop-color="' . s($accent) . '"/>
                </linearGradient>
                <linearGradient id="' . $idbase . 'B" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#ffffff" stop-opacity=".94"/>
                    <stop offset="1" stop-color="#dbeafe" stop-opacity=".68"/>
                </linearGradient>
                <filter id="' . $idbase . 'Shadow" x="-20%" y="-20%" width="140%" height="150%">
                    <feDropShadow dx="0" dy="18" stdDeviation="14" flood-color="#1f375b" flood-opacity=".18"/>
                </filter>
            </defs>';

        $scene = '';
        switch ($variant) {
            case 'course':
                $scene = '
                    <g class="boc-svg-card" filter="url(#' . $idbase . 'Shadow)">
                        <rect x="146" y="118" width="318" height="178" rx="28" fill="url(#' . $idbase . 'B)"/>
                        <rect x="190" y="88" width="230" height="138" rx="22" fill="url(#' . $idbase . 'A)" opacity=".88"/>
                        <path d="M225 154 h90 M225 184 h134" stroke="#fff" stroke-width="12" stroke-linecap="round" opacity=".9"/>
                        <path d="M314 118 l66 37 l-66 37 z" fill="#fff" opacity=".92"/>
                        <path d="M180 304 h250" stroke="#0f172a" stroke-width="12" stroke-linecap="round" opacity=".14"/>
                    </g>
                    <g class="boc-svg-float">
                        <rect x="106" y="80" width="98" height="72" rx="18" fill="#fff" opacity=".86"/>
                        <rect x="432" y="202" width="92" height="68" rx="18" fill="#fff" opacity=".86"/>
                        <path d="M132 112 h46 M452 232 h48" stroke="' . s($primary) . '" stroke-width="10" stroke-linecap="round"/>
                    </g>';
                break;
            case 'assignment':
                $scene = '
                    <g class="boc-svg-card" filter="url(#' . $idbase . 'Shadow)">
                        <rect x="174" y="78" width="250" height="238" rx="28" fill="url(#' . $idbase . 'B)"/>
                        <rect x="236" y="56" width="126" height="48" rx="18" fill="url(#' . $idbase . 'A)"/>
                        <path d="M218 146 h150 M218 184 h112 M218 222 h132" stroke="#64748b" stroke-width="12" stroke-linecap="round" opacity=".36"/>
                        <path d="M382 270 l76 -76 l32 32 l-76 76 l-42 10z" fill="' . s($accent) . '" opacity=".88"/>
                    </g>
                    <g class="boc-svg-float">
                        <circle cx="462" cy="124" r="44" fill="url(#' . $idbase . 'A)"/>
                        <path d="M462 91 l10 22 l24 3 l-17 17 l4 24 l-21 -12 l-22 12 l5 -24 l-18 -17 l24 -3z" fill="#fff"/>
                    </g>';
                break;
            case 'role':
                $scene = '
                    <g class="boc-svg-card" filter="url(#' . $idbase . 'Shadow)">
                        <rect x="238" y="92" width="148" height="148" rx="42" fill="url(#' . $idbase . 'A)"/>
                        <circle cx="312" cy="146" r="24" fill="#fff" opacity=".88"/>
                        <path d="M268 214 q44 -48 88 0" fill="#fff" opacity=".88"/>
                    </g>
                    <g class="boc-svg-float">
                        <circle cx="144" cy="112" r="34" fill="#fff" opacity=".86"/>
                        <circle cx="496" cy="122" r="34" fill="#fff" opacity=".86"/>
                        <circle cx="144" cy="274" r="34" fill="#fff" opacity=".86"/>
                        <circle cx="496" cy="276" r="34" fill="#fff" opacity=".86"/>
                        <path d="M178 126 L238 154 M462 136 L386 154 M178 260 L238 206 M462 260 L386 206" stroke="' . s($secondary) . '" stroke-width="9" stroke-linecap="round" opacity=".42"/>
                    </g>';
                break;
            case 'progress':
                $scene = '
                    <g class="boc-svg-card" filter="url(#' . $idbase . 'Shadow)">
                        <rect x="126" y="76" width="238" height="204" rx="26" fill="url(#' . $idbase . 'B)"/>
                        <circle class="boc-svg-ring" cx="214" cy="172" r="58" fill="none" stroke="' . s($primary) . '" stroke-width="18" stroke-dasharray="235 365"/>
                        <circle cx="214" cy="172" r="31" fill="#fff"/>
                        <path d="M290 213 C321 162 350 182 388 120 S470 128 498 88" fill="none" stroke="' . s($secondary) . '" stroke-width="13" stroke-linecap="round"/>
                        <path d="M290 239 H475" stroke="#94a3b8" stroke-width="10" stroke-linecap="round" opacity=".35"/>
                    </g>
                    <g class="boc-svg-float">
                        <path d="M426 210 h84 v62 h-84 z" fill="url(#' . $idbase . 'A)" rx="16"/>
                        <path d="M450 214 v-23 h36 v23" fill="none" stroke="#fff" stroke-width="10" stroke-linecap="round"/>
                        <circle cx="468" cy="242" r="9" fill="#fff"/>
                    </g>';
                break;
            case 'collaboration':
                $scene = '
                    <g class="boc-svg-card" filter="url(#' . $idbase . 'Shadow)">
                        <rect x="190" y="92" width="246" height="184" rx="28" fill="url(#' . $idbase . 'B)"/>
                        <path d="M230 138 h150 M230 170 h112 M230 202 h136" stroke="#64748b" stroke-width="12" stroke-linecap="round" opacity=".35"/>
                        <path d="M422 146 q46 10 62 48 q-30 -6 -58 14" fill="#fff" opacity=".82"/>
                        <path d="M168 224 q-45 -7 -63 -43 q32 3 58 -18" fill="#fff" opacity=".82"/>
                    </g>
                    <g class="boc-svg-float">
                        <circle cx="144" cy="116" r="40" fill="' . s($primary) . '"/>
                        <circle cx="504" cy="126" r="38" fill="' . s($secondary) . '"/>
                        <circle cx="504" cy="270" r="34" fill="' . s($accent) . '"/>
                        <circle cx="144" cy="116" r="16" fill="#fff" opacity=".88"/>
                        <circle cx="504" cy="126" r="15" fill="#fff" opacity=".88"/>
                        <circle cx="504" cy="270" r="14" fill="#fff" opacity=".88"/>
                    </g>
                    <path class="boc-svg-line" d="M183 127 C246 72 402 72 468 119 M190 236 C270 304 404 306 476 274"/>';
                break;
            case 'mobile':
                $scene = '
                    <g class="boc-svg-card" filter="url(#' . $idbase . 'Shadow)">
                        <rect x="240" y="54" width="162" height="278" rx="34" fill="url(#' . $idbase . 'A)"/>
                        <rect x="258" y="84" width="126" height="210" rx="24" fill="url(#' . $idbase . 'B)"/>
                        <path d="M282 134 h72 M282 166 h48 M282 218 h82" stroke="' . s($primary) . '" stroke-width="10" stroke-linecap="round" opacity=".62"/>
                        <circle cx="321" cy="314" r="9" fill="#fff"/>
                    </g>
                    <g class="boc-svg-float">
                        <path d="M132 190 q13 -37 50 -28 q15 -31 52 -16 q23 9 28 38 q35 2 43 32 q7 31 -31 38 h-118 q-39 -3 -39 -32 q0 -24 15 -32z" fill="#fff" opacity=".88"/>
                        <path d="M474 136 q40 28 55 72 M492 102 q62 42 84 112" fill="none" stroke="' . s($secondary) . '" stroke-width="12" stroke-linecap="round" opacity=".56"/>
                        <path d="M462 266 l46 -46 l46 46" fill="none" stroke="' . s($accent) . '" stroke-width="13" stroke-linecap="round" stroke-linejoin="round"/>
                    </g>';
                break;
            case 'achievement':
                $scene = '
                    <g class="boc-svg-card" filter="url(#' . $idbase . 'Shadow)">
                        <rect x="146" y="100" width="204" height="184" rx="26" fill="url(#' . $idbase . 'B)"/>
                        <path d="M184 152 h126 M184 188 h96 M184 224 h126" stroke="#64748b" stroke-width="11" stroke-linecap="round" opacity=".35"/>
                        <path d="M246 252 l24 42 l24 -42" fill="' . s($primary) . '" opacity=".76"/>
                    </g>
                    <g class="boc-svg-float" filter="url(#' . $idbase . 'Shadow)">
                        <circle cx="426" cy="172" r="70" fill="url(#' . $idbase . 'A)"/>
                        <path d="M426 118 l15 32 l35 5 l-25 24 l6 34 l-31 -16 l-31 16 l6 -34 l-25 -24 l35 -5z" fill="#fff"/>
                        <path d="M470 239 h64 v42 h-64 z M486 239 v-36 h32 v36" fill="#fff" opacity=".88"/>
                    </g>
                    <path class="boc-svg-spark" d="M118 132 l18 9 l-18 9 l-9 18 l-9 -18 l-18 -9 l18 -9 l9 -18z"/>';
                break;
            case 'analytics':
                $scene = '
                    <g class="boc-svg-card" filter="url(#' . $idbase . 'Shadow)">
                        <rect x="122" y="74" width="330" height="228" rx="28" fill="url(#' . $idbase . 'B)"/>
                        <rect x="170" y="214" width="38" height="48" rx="9" fill="' . s($primary) . '"/>
                        <rect x="234" y="180" width="38" height="82" rx="9" fill="' . s($secondary) . '"/>
                        <rect x="298" y="132" width="38" height="130" rx="9" fill="' . s($accent) . '"/>
                        <path d="M164 166 C214 114 264 178 318 126 S410 118 438 82" fill="none" stroke="' . s($primary) . '" stroke-width="11" stroke-linecap="round"/>
                    </g>
                    <g class="boc-svg-float">
                        <circle cx="458" cy="242" r="42" fill="#fff" opacity=".88"/>
                        <circle cx="458" cy="242" r="25" fill="none" stroke="' . s($secondary) . '" stroke-width="10"/>
                        <path d="M488 272 l50 50" stroke="' . s($secondary) . '" stroke-width="13" stroke-linecap="round"/>
                    </g>';
                break;
            case 'platform':
            default:
                $scene = '
                    <g class="boc-svg-card" filter="url(#' . $idbase . 'Shadow)">
                        <path d="M310 68 l126 48 v82 c0 82 -54 134 -126 160 c-72 -26 -126 -78 -126 -160 v-82z" fill="url(#' . $idbase . 'A)"/>
                        <path d="M275 198 l28 30 l70 -82" fill="none" stroke="#fff" stroke-width="18" stroke-linecap="round" stroke-linejoin="round"/>
                    </g>
                    <g class="boc-svg-float">
                        <rect x="104" y="140" width="98" height="78" rx="22" fill="#fff" opacity=".88"/>
                        <rect x="438" y="124" width="98" height="78" rx="22" fill="#fff" opacity=".88"/>
                        <rect x="438" y="244" width="98" height="78" rx="22" fill="#fff" opacity=".88"/>
                        <path d="M202 179 h74 M436 164 h-73 M436 283 h-78" stroke="' . s($secondary) . '" stroke-width="10" stroke-linecap="round" opacity=".56"/>
                        <circle cx="153" cy="179" r="16" fill="' . s($primary) . '"/>
                        <circle cx="487" cy="163" r="16" fill="' . s($secondary) . '"/>
                        <circle cx="487" cy="283" r="16" fill="' . s($accent) . '"/>
                    </g>';
                break;
        }

        $svgclass = 'boc-feature-svg' . ($underlay ? ' boc-feature-svg-underlay' : '') . ' boc-feature-svg-' . s($variant);

        return '
            <svg class="' . $svgclass . '" viewBox="0 0 620 390" focusable="false" aria-hidden="true">
                ' . $defs . '
                <rect class="boc-svg-stage" x="52" y="36" width="516" height="318" rx="42"/>
                <path class="boc-svg-gridline" d="M102 122 H518 M102 202 H518 M102 282 H518 M180 70 V328 M308 70 V328 M436 70 V328"/>
                <circle class="boc-svg-glow one" cx="150" cy="98" r="70"/>
                <circle class="boc-svg-glow two" cx="498" cy="292" r="84"/>
                ' . $scene . '
            </svg>';
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
        $html = $this->section_open('courses', 'Available Courses', 'Browse the live Moodle catalogue. Last-accessed courses appear first.');
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
                '</p><em><i class="fa fa-clock-o" aria-hidden="true"></i> ' . s($course['meta']) . '</em></a>';
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
        $categoryblock = $this->render_frontpage_category_block($data);

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
    ' . $categoryblock . '
</aside>';
    }

    /**
     * Render the front-page course category summary using live Moodle data.
     *
     * @param array $data Front-page data.
     * @return string
     */
    private function render_frontpage_category_block(array $data): string {
        $groups = $data['categorygroups'] ?? [];
        $maxcount = 1;

        foreach ($groups as $group) {
            $maxcount = max($maxcount, (int)$group['count']);
        }

        $itemshtml = '';
        foreach ($groups as $group) {
            $count = (int)$group['count'];
            $percent = $count > 0 ? max(6, (int)round(($count / $maxcount) * 100)) : 0;
            $itemshtml .= '<a class="boc-category-item boc-category-item-' . s($group['tone']) .
                ($count === 0 ? ' is-empty' : '') . '" href="' . s($group['url']) .
                '" style="--boc-category-progress: ' . s((string)$percent) . '%;">' .
                '<span class="boc-category-icon"><i class="fa ' . s($group['icon']) . '" aria-hidden="true"></i></span>' .
                '<span class="boc-category-copy"><strong>' . s($group['label']) . '</strong><small>' .
                s($group['description']) . '</small></span>' .
                '<span class="boc-category-number"><b>' . s(number_format($count)) . '</b><small>' .
                s($count === 1 ? 'course' : 'courses') . '</small></span>' .
                '<span class="boc-category-meter" aria-hidden="true"><i></i></span>' .
                '</a>';
        }

        return '
    <section class="boc-rail-block boc-rail-category-block">
        <div class="boc-category-block-head">
            <h3><i class="fa fa-folder-open-o" aria-hidden="true"></i> Course Categories</h3>
            <span><i class="fa fa-database" aria-hidden="true"></i> Live DB</span>
        </div>
        <div class="boc-category-total">
            <span><i class="fa fa-th-large" aria-hidden="true"></i></span>
            <strong>' . s(number_format((int)$data['coursecount'])) . '</strong>
            <small>visible Moodle courses grouped from the database</small>
        </div>
        <div class="boc-category-list" aria-label="Course categories by live course count">' . $itemshtml . '</div>
        <a class="boc-category-all" href="' . s($data['coursesurl']) . '">Browse all courses <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
    </section>';
    }

    /**
     * Build front-page course category groups from visible Moodle courses.
     *
     * @return array
     */
    private function frontpage_course_category_groups(): array {
        global $DB, $SITE;

        $groups = [
            'school' => [
                'label' => 'School K-12',
                'description' => 'Nursery to Standard 12',
                'icon' => 'fa-graduation-cap',
                'tone' => 'blue',
                'url' => (new moodle_url('/course/search.php', ['search' => 'Standard']))->out(false),
                'count' => 0,
            ],
            'university' => [
                'label' => 'University & Diploma',
                'description' => 'UG, PG, diploma and degree paths',
                'icon' => 'fa-university',
                'tone' => 'violet',
                'url' => (new moodle_url('/course/search.php', ['search' => 'B.Tech']))->out(false),
                'count' => 0,
            ],
            'professional' => [
                'label' => 'Professional Training',
                'description' => 'ITI, vocational and career programmes',
                'icon' => 'fa-briefcase',
                'tone' => 'green',
                'url' => (new moodle_url('/course/search.php', ['search' => 'ITI']))->out(false),
                'count' => 0,
            ],
            'certification' => [
                'label' => 'Certification Courses',
                'description' => 'Cloud, MLOps, Agile and Web3',
                'icon' => 'fa-certificate',
                'tone' => 'orange',
                'url' => (new moodle_url('/course/search.php', ['search' => 'Certification']))->out(false),
                'count' => 0,
            ],
            'teacher' => [
                'label' => 'Teacher Training',
                'description' => 'Educator development courses',
                'icon' => 'fa-users',
                'tone' => 'rose',
                'url' => (new moodle_url('/course/search.php', ['search' => 'Teacher Training']))->out(false),
                'count' => 0,
            ],
        ];

        try {
            $records = $DB->get_records_sql(
                "SELECT c.id, c.fullname, c.shortname, cc.name AS categoryname
                   FROM {course} c
                   JOIN {course_categories} cc ON cc.id = c.category
                  WHERE c.id <> :siteid
                    AND c.visible = :visible",
                ['siteid' => $SITE->id, 'visible' => 1]
            );
        } catch (\dml_exception $exception) {
            return array_values($groups);
        }

        foreach ($records as $record) {
            $text = \core_text::strtolower($this->course_search_clean_label(
                $record->fullname . ' ' . $record->shortname . ' ' . $record->categoryname
            ));
            $groupkey = $this->frontpage_course_category_group_key($text);
            $groups[$groupkey]['count']++;
        }

        return array_values($groups);
    }

    /**
     * Classify a visible Moodle course into the public category summary.
     *
     * @param string $text Lower-case searchable course/category text.
     * @return string Group key.
     */
    private function frontpage_course_category_group_key(string $text): string {
        if (preg_match('/(teacher training|faculty|educator|teaching pedagogy|\bteacher\b)/', $text)) {
            return 'teacher';
        }

        if (preg_match('/(certificate|certification|lms_cert|certified|scrum|agile|mlops|cloud|web3|blockchain|executive pg)/', $text)) {
            return 'certification';
        }

        if (preg_match('/(standard\s+[0-9]+|nursery|preschool|lkg|ukg|kindergarten)/', $text)) {
            return 'school';
        }

        if (preg_match('/(b\.?tech|b\.?sc|bca|bba|b\.?des|ba ll\.?b|ll\.?b|mbbs|gnm|nursing|mba|mca|' .
                'm\.?tech|m\.?arch|mpt|msw|mfa|m\.?com|mcom|ll\.?m|ph\.?d|polytechnic|diploma|degree|ug|pg)/', $text)) {
            return 'university';
        }

        return 'professional';
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
     * Get visible course-bearing category cards from Moodle's category tree.
     *
     * @return array
     */
    private function get_course_category_cards(): array {
        global $DB, $SITE;

        try {
            $categories = $DB->get_records_select(
                'course_categories',
                'visible = ?',
                [1],
                'sortorder ASC',
                'id, name, idnumber, parent, depth, path, coursecount, description, descriptionformat, sortorder'
            );

            $courses = $DB->get_records_select(
                'course',
                'id <> ? AND visible = ?',
                [$SITE->id, 1],
                'category ASC, sortorder ASC',
                'id, fullname, shortname, category',
                0,
                6000
            );
        } catch (\dml_exception $exception) {
            return [];
        }

        if (!$categories) {
            return [];
        }

        $categorymap = [];
        foreach ($categories as $category) {
            $categorymap[(int)$category->id] = $category;
        }

        $directcounts = [];
        $samples = [];
        foreach ($courses as $course) {
            $categoryid = (int)$course->category;
            $directcounts[$categoryid] = ($directcounts[$categoryid] ?? 0) + 1;

            if (!isset($samples[$categoryid])) {
                $samples[$categoryid] = [];
            }

            if (count($samples[$categoryid]) < 2) {
                $samples[$categoryid][] = shorten_text(
                    $this->course_search_clean_label(format_string($course->fullname, true, ['escape' => false])),
                    74,
                    true
                );
            }
        }

        $cards = [];
        foreach ($categories as $category) {
            $categoryid = (int)$category->id;
            $coursecount = (int)($directcounts[$categoryid] ?? 0);

            if ($coursecount < 1) {
                continue;
            }

            $labels = $this->course_category_path_labels($category, $categorymap);
            $name = $this->course_category_display_name($category, $labels);
            if ($name === '') {
                continue;
            }

            $cardprofile = $this->course_category_card_profile($name, $labels);
            $tags = $this->course_category_attribute_tags($labels);
            $cards[] = [
                'name' => $name,
                'type' => $cardprofile['type'],
                'tone' => $cardprofile['tone'],
                'icon' => $cardprofile['icon'],
                'coursecount' => $coursecount,
                'description' => $this->course_category_description($category, $tags, $coursecount),
                'tags' => $tags,
                'samples' => $samples[$categoryid] ?? [],
                'url' => (new moodle_url('/course/index.php', ['categoryid' => $categoryid]))->out(false),
            ];
        }

        return $cards;
    }

    /**
     * Clean category path labels for public display.
     *
     * @param \stdClass $category Moodle course category.
     * @param array $categorymap Categories keyed by id.
     * @return array
     */
    private function course_category_path_labels(\stdClass $category, array $categorymap): array {
        $labels = [];
        $pathids = array_filter(array_map('intval', explode('/', trim((string)$category->path, '/'))));

        foreach ($pathids as $pathid) {
            if (!isset($categorymap[$pathid])) {
                continue;
            }

            $label = $this->course_search_clean_label((string)$categorymap[$pathid]->name);
            if ($label === '' || $this->course_category_is_placeholder_label($label)) {
                continue;
            }

            $labels[] = $label;
        }

        return $labels;
    }

    /**
     * Whether a category label is a default shell category, not an academic value.
     *
     * @param string $label Category label.
     * @return bool
     */
    private function course_category_is_placeholder_label(string $label): bool {
        return (bool)preg_match('/^(category\s+\d+|course templates)$/i', $label);
    }

    /**
     * Build a useful card title from repeated leaf categories and their parent path.
     *
     * @param \stdClass $category Moodle course category.
     * @param array $labels Clean path labels.
     * @return string
     */
    private function course_category_display_name(\stdClass $category, array $labels): string {
        $leaf = $this->course_search_clean_label((string)$category->name);
        if ($leaf === '') {
            return '';
        }

        $parent = '';
        if (count($labels) > 1) {
            $parent = $labels[count($labels) - 2];
        }

        if ($parent !== '' && $this->course_category_is_generic_leaf($leaf)) {
            return shorten_text($parent . ' - ' . $leaf, 92, true);
        }

        return shorten_text($leaf, 92, true);
    }

    /**
     * Whether a course-bearing leaf category needs parent context in its title.
     *
     * @param string $label Category label.
     * @return bool
     */
    private function course_category_is_generic_leaf(string $label): bool {
        return (bool)preg_match(
            '/^(general|pure sciences?|commerce\s*&\s*biz|humanities\s*&\s*arts|engineering\s*&\s*tech|' .
            'industrial training|nursing|medical\s*&\s*health|management\s*&\s*biz|design\s*&\s*creative|' .
            'law\s*&\s*legal|architecture|allied healthcare|hospitality\s*&\s*tourism|computer applications)$/i',
            $label
        );
    }

    /**
     * Choose the category card icon, label and colour family.
     *
     * @param string $name Public card name.
     * @param array $labels Path labels.
     * @return array
     */
    private function course_category_card_profile(string $name, array $labels): array {
        $haystack = $this->course_search_clean_label($name . ' ' . implode(' ', $labels));
        $rules = [
            ['/engineering|polytechnic|iti|industrial|technology|tech|computer|applications|coding|software/i',
                'technology', 'fa-cogs', 'Technical Pathway'],
            ['/science|mathematics|physics|chemistry|biology|analytics|laboratory/i',
                'science', 'fa-flask', 'Science Stream'],
            ['/commerce|biz|business|management|finance|marketing|account/i',
                'commerce', 'fa-briefcase', 'Commerce Pathway'],
            ['/medical|health|nursing|healthcare|physiotherapy|midwifery/i',
                'health', 'fa-heartbeat', 'Health Programme'],
            ['/law|legal|justice/i',
                'law', 'fa-balance-scale', 'Legal Studies'],
            ['/design|creative|architecture|arts|art\s*&\s*craft|drawing/i',
                'creative', 'fa-paint-brush', 'Creative Studies'],
            ['/hospitality|tourism/i',
                'hospitality', 'fa-map-signs', 'Hospitality Track'],
            ['/nursery|preschool|kindergarten|lkg|ukg|standard|grade|general/i',
                'school', 'fa-graduation-cap', 'School Category'],
        ];

        foreach ($rules as $rule) {
            if (preg_match($rule[0], $haystack)) {
                return [
                    'tone' => $rule[1],
                    'icon' => $rule[2],
                    'type' => $rule[3],
                ];
            }
        }

        return [
            'tone' => 'academic',
            'icon' => 'fa-book',
            'type' => 'Academic Category',
        ];
    }

    /**
     * Extract compact public attributes from the category path and example courses.
     *
     * @param array $labels Category path labels.
     * @return array
     */
    private function course_category_attribute_tags(array $labels): array {
        $tags = [];
        $seen = [];
        $addtag = function(string $tag) use (&$tags, &$seen): void {
            $tag = $this->course_search_clean_label($tag);
            if ($tag === '' || count($tags) >= 6) {
                return;
            }

            $key = \core_text::strtolower($tag);
            if (isset($seen[$key])) {
                return;
            }

            $seen[$key] = true;
            $tags[] = shorten_text($tag, 28, true);
        };

        foreach ($labels as $label) {
            if (preg_match('/gujarat board/i', $label)) {
                $addtag('Gujarat Board');
                continue;
            }

            if (preg_match('/\bmedium$/i', $label)) {
                $addtag($label);
                continue;
            }

            if (preg_match('/academic year\s+([0-9-]+)/i', $label, $matches)) {
                $addtag($matches[1]);
                continue;
            }

            if ($this->course_search_is_standard_label($label)) {
                $addtag($label);
                continue;
            }

            if ($this->course_category_is_generic_leaf($label) ||
                    preg_match('/iti|polytechnic|b\.?tech|b\.?sc|bca|bba|mba|mca|gnm|certificate|training/i', $label)) {
                $addtag(preg_replace('/\s+-\s+Y\d+$/i', '', $label));
            }
        }

        return $tags;
    }

    /**
     * Create a concise category card description.
     *
     * @param \stdClass $category Moodle course category.
     * @param array $tags Public attribute tags.
     * @param int $coursecount Direct visible course count.
     * @return string
     */
    private function course_category_description(\stdClass $category, array $tags, int $coursecount): string {
        $description = $this->course_search_clean_label((string)($category->description ?? ''));

        if ($description !== '') {
            return shorten_text($description, 118, true);
        }

        $focus = $tags ? implode(', ', array_slice($tags, 0, 3)) : 'this learning pathway';
        $courselabel = $coursecount === 1 ? 'visible course' : 'visible courses';

        return shorten_text('Live Moodle category for ' . $focus . ' with ' .
            number_format($coursecount) . ' ' . $courselabel . ' ready for learners.', 118, true);
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
     * Count records from a complete SQL statement defensively.
     *
     * @param string $sql SQL returning a count.
     * @param array $params SQL params.
     * @return int
     */
    private function safe_count_records_sql(string $sql, array $params = []): int {
        global $DB;

        try {
            return (int)$DB->count_records_sql($sql, $params);
        } catch (\dml_exception $exception) {
            return 0;
        }
    }

    /**
     * Calculate a public aggregate course-completion rate when enrolment data is available.
     *
     * @return int|null Completion percentage, or null when it cannot be calculated.
     */
    private function safe_course_completion_rate(): ?int {
        global $DB, $SITE;

        try {
            $enrolled = (int)$DB->count_records_sql(
                "SELECT COUNT(1)
                   FROM (
                         SELECT ue.userid, e.courseid
                           FROM {user_enrolments} ue
                           JOIN {enrol} e ON e.id = ue.enrolid
                           JOIN {course} c ON c.id = e.courseid
                          WHERE c.id <> :siteid
                            AND c.visible = :visible
                            AND e.status = :enrolenabled
                            AND ue.status = :userenabled
                       GROUP BY ue.userid, e.courseid
                        ) enrolled",
                [
                    'siteid' => $SITE->id,
                    'visible' => 1,
                    'enrolenabled' => 0,
                    'userenabled' => 0,
                ]
            );

            if ($enrolled < 1) {
                return null;
            }

            $completed = (int)$DB->count_records_sql(
                "SELECT COUNT(1)
                   FROM (
                         SELECT cc.userid, cc.course
                           FROM {course_completions} cc
                           JOIN {course} c ON c.id = cc.course
                          WHERE c.id <> :siteid
                            AND c.visible = :visible
                            AND cc.timecompleted IS NOT NULL
                            AND cc.timecompleted > :notcompleted
                       GROUP BY cc.userid, cc.course
                        ) completed",
                [
                    'siteid' => $SITE->id,
                    'visible' => 1,
                    'notcompleted' => 0,
                ]
            );

            return min(100, max(0, (int)round(($completed / $enrolled) * 100)));
        } catch (\dml_exception $exception) {
            return null;
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
        global $DB, $SITE, $USER;

        $fallback = [
            ['AI for Educators', 'Professional Training', 'Future-ready teaching with classroom technology.', 'fa-lightbulb-o'],
            ['Business Analytics', 'Professional Training', 'Data-driven decisions for academic and business teams.', 'fa-line-chart'],
            ['Digital Law & Ethics', 'Certification Course', 'Compliance and responsible digital practice.', 'fa-balance-scale'],
            ['Science Grade 10', 'School K-12', 'Board-aligned science learning and assessments.', 'fa-flask'],
            ['Gujarati Medium Mathematics', 'School K-12', 'Mathematics learning path for Gujarati medium learners.', 'fa-calculator'],
        ];

        try {
            $params = [
                'siteid' => $SITE->id,
                'visible' => 1,
            ];
            $useraccessjoin = '';
            if (isloggedin() && !isguestuser()) {
                $params['userid'] = $USER->id;
                $useraccessjoin = 'AND ula.userid = :userid';
            }

            $records = $DB->get_records_sql(
                "SELECT c.id, c.fullname, c.shortname, c.summary, c.summaryformat, c.timemodified,
                        COALESCE(MAX(ula.timeaccess), 0) AS lastaccess
                   FROM {course} c
              LEFT JOIN {user_lastaccess} ula ON ula.courseid = c.id {$useraccessjoin}
                  WHERE c.id <> :siteid
                    AND c.visible = :visible
               GROUP BY c.id, c.fullname, c.shortname, c.summary, c.summaryformat, c.timemodified, c.sortorder
               ORDER BY COALESCE(MAX(ula.timeaccess), 0) DESC, c.timemodified DESC, c.sortorder ASC",
                $params,
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
                'meta' => $this->course_access_meta((int)$course->lastaccess, (int)$course->timemodified),
                'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            ];
        }

        return $cards;
    }

    /**
     * Format course access metadata for public course cards.
     *
     * @param int $lastaccess Course last access timestamp.
     * @param int $timemodified Course modified timestamp.
     * @return string
     */
    private function course_access_meta(int $lastaccess, int $timemodified): string {
        if ($lastaccess > 0) {
            return 'Last accessed ' . userdate($lastaccess, '%d %b %Y');
        }
        if ($timemodified > 0) {
            return 'Recently updated ' . userdate($timemodified, '%d %b %Y');
        }
        return 'Live Moodle course';
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
                'meta' => 'Live Moodle course',
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
