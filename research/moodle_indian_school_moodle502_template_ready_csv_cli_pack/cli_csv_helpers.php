<?php
// Shared helpers for resolving ordered CSV filenames in this pack.
//
// Scripts should ask for the logical file name, for example "courses.csv".
// This helper resolves it to either:
// - courses.csv
// - 12_courses.csv
// - 012_courses.csv

if (!function_exists('csv_pack_resolve_file')) {
    function csv_pack_resolve_file($dir, $filename) {
        $filename = basename($filename);
        $exact = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($exact)) {
            return $exact;
        }

        $patterns = [
            rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '[0-9][0-9]_' . $filename,
            rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '[0-9][0-9][0-9]_' . $filename,
        ];

        foreach ($patterns as $pattern) {
            $matches = glob($pattern);
            if (!empty($matches)) {
                sort($matches, SORT_NATURAL);
                return $matches[0];
            }
        }

        return $exact;
    }
}

if (!function_exists('csv_pack_file_exists')) {
    function csv_pack_file_exists($dir, $filename) {
        return file_exists(csv_pack_resolve_file($dir, $filename));
    }
}
