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
 * Static registry for Custom LMS bundle pages.
 *
 * @package    theme_custom_lms
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_custom_lms\local;

use moodle_url;

/**
 * Reads generated bundle page metadata and exposes Moodle-safe route helpers.
 */
class bundle_page_repository {

    /** @var array|null Generated page metadata cache. */
    private static ?array $data = null;

    /**
     * Return whether a page exists in the bundle registry.
     *
     * @param string $page Page slug.
     * @return bool
     */
    public function exists(string $page): bool {
        $data = $this->get_data();
        return isset($data['pages'][$page]);
    }

    /**
     * Return page metadata.
     *
     * @param string $page Page slug.
     * @return array
     */
    public function get_metadata(string $page): array {
        return $this->get_raw_page($page);
    }

    /**
     * Return all registered page slugs.
     *
     * @return array
     */
    public function get_page_slugs(): array {
        $data = $this->get_data();
        return array_keys($data['pages']);
    }

    /**
     * Return common URL placeholders used by generated templates.
     *
     * @return array
     */
    public function get_page_url_context(): array {
        $context = [];
        foreach ($this->get_page_slugs() as $page) {
            $context['url_' . self::slug_to_key($page)] = $this->page_url($page);
        }

        $context['url_moodle_home'] = (new moodle_url('/'))->out(false);
        $context['url_moodle_login'] = (new moodle_url('/login/index.php'))->out(false);
        $context['url_moodle_dashboard'] = (new moodle_url('/my/'))->out(false);
        $context['url_moodle_courses'] = (new moodle_url('/my/courses.php'))->out(false);
        $context['url_moodle_calendar'] = (new moodle_url('/calendar/view.php'))->out(false);

        return $context;
    }

    /**
     * Return the route for a bundle page.
     *
     * @param string $page Page slug.
     * @param string $anchor Optional fragment including leading hash.
     * @return string
     */
    public function page_url(string $page, string $anchor = ''): string {
        $anchor = ltrim($anchor, '#');
        $url = new moodle_url('/theme/custom_lms/page.php', ['page' => $page]);

        if ($anchor !== '') {
            $url->set_anchor($anchor);
        }

        return $url->out(false);
    }

    /**
     * Convert a page slug to a safe Mustache key suffix.
     *
     * @param string $slug Page slug.
     * @return string
     */
    public static function slug_to_key(string $slug): string {
        return str_replace('-', '_', $slug);
    }

    /**
     * Return a raw page from generated JSON.
     *
     * @param string $page Page slug.
     * @return array
     */
    private function get_raw_page(string $page): array {
        $data = $this->get_data();
        if (!isset($data['pages'][$page])) {
            throw new \moodle_exception('bundlepagenotfound', 'theme_custom_lms', new moodle_url('/'));
        }

        return $data['pages'][$page];
    }

    /**
     * Load generated JSON page metadata once.
     *
     * @return array
     */
    private function get_data(): array {
        global $CFG;

        if (self::$data !== null) {
            return self::$data;
        }

        $filepath = $CFG->dirroot . '/theme/custom_lms/data/bundle_pages.json';
        if (!is_readable($filepath)) {
            throw new \moodle_exception('bundlepagesdatamissing', 'theme_custom_lms', new moodle_url('/'));
        }

        $rawjson = file_get_contents($filepath);
        $data = json_decode($rawjson, true);
        if (!is_array($data) || !isset($data['pages']) || !is_array($data['pages'])) {
            throw new \moodle_exception('bundlepagesdatamissing', 'theme_custom_lms', new moodle_url('/'));
        }

        self::$data = $data;
        return self::$data;
    }
}
