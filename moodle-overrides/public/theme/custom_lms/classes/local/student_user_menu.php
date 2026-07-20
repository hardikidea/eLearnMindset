<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Student account menu presentation helpers.
 *
 * @package    theme_custom_lms
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_custom_lms\local;

use context_system;
use moodle_url;

/**
 * Decorates Moodle's capability-generated user menu for the student shell.
 */
final class student_user_menu {

    /**
     * Add stable self-profile URLs and meaningful icons to existing menu items.
     *
     * The links themselves remain owned by Moodle core. Content bank is added only
     * when the current user has the same capability required by its destination.
     *
     * @param array $items Exported Moodle user menu items.
     * @param int $userid Current user id.
     */
    public static function prepare(array &$items, int $userid): void {
        $profileurl = (new moodle_url('/user/profile.php', ['id' => $userid]))->out(false);
        $hascontentbank = false;

        foreach ($items as $item) {
            if (!is_object($item) || empty($item->url)) {
                continue;
            }

            $path = parse_url((string) $item->url, PHP_URL_PATH) ?: '';
            if ($path === '/user/profile.php') {
                $item->url = $profileurl;
            }
            if ($path === '/contentbank/index.php') {
                $hascontentbank = true;
            }

            $pixicon = self::icon_for_path($path);
            if ($pixicon !== null) {
                $item->pixicon = $pixicon;
            }
        }

        if (!$hascontentbank && has_capability('moodle/contentbank:access', context_system::instance())) {
            self::add_content_bank_item($items);
        }
    }

    /**
     * Resolve a Moodle pix icon for a core account destination.
     *
     * @param string $path URL path.
     * @return string|null
     */
    private static function icon_for_path(string $path): ?string {
        if ($path === '/user/profile.php') {
            return 'i/user';
        }
        if (strpos($path, '/grade/') === 0) {
            return 'i/grades';
        }
        if ($path === '/calendar/view.php') {
            return 'i/calendar';
        }
        if ($path === '/user/files.php') {
            return 'i/privatefiles';
        }
        if ($path === '/contentbank/index.php') {
            return 'i/contentbank';
        }
        if (strpos($path, '/report/') === 0 || strpos($path, '/reportbuilder/') === 0) {
            return 'i/report';
        }
        if ($path === '/user/preferences.php') {
            return 'i/settings';
        }
        if ($path === '/login/logout.php') {
            return 'a/logout';
        }

        return null;
    }

    /**
     * Place Content bank beside the other personal content destinations.
     *
     * @param array $items Exported Moodle user menu items.
     */
    private static function add_content_bank_item(array &$items): void {
        $contentbankitem = (object) [
            'itemtype' => 'link',
            'title' => get_string('contentbank'),
            'url' => (new moodle_url('/contentbank/index.php'))->out(false),
            'pixicon' => 'i/contentbank',
            'divider' => false,
            'link' => true,
        ];

        $position = count($items);
        foreach ($items as $index => $item) {
            if (!empty($item->url)
                    && parse_url((string) $item->url, PHP_URL_PATH) === '/user/files.php') {
                $position = $index + 1;
                break;
            }
            if (!empty($item->divider)) {
                $position = $index;
                break;
            }
        }

        array_splice($items, $position, 0, [$contentbankitem]);
    }
}
