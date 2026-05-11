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
 * File for readme_reader.
 *
 * @package    block_plugindirectory
 * @copyright  2026 moodle-td.de
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace block_plugindirectory\local;


/**
 * Reads and renders README.md / README.txt from a plugin root directory.
 *
 * @package    block_plugindirectory
 * @copyright  2026 moodle-td.de
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
final class readme_reader {
    /** Maximum number of bytes preserved from the original file. */
    /** @var string|int|array Maximum number of bytes preserved from the original README file. */
    public const MAX_BYTES = 8000;

    /** Filenames inspected, in priority order. */
    /** @var string|int|array README filenames inspected, in priority order. */
    private const CANDIDATES = [
        'README.md', 'README.MD', 'readme.md',
        'README.txt', 'README.TXT', 'readme.txt',
    ];

    /**
     * Locate, read and render the README for a plugin root.
     *
     * Returns the empty string if no readable README is found or it is blank.
     *
     * @param string $rootdir Absolute path to the plugin directory.
     * @return string
     */
    public static function render(string $rootdir): string {
        foreach (self::CANDIDATES as $filename) {
            $filepath = $rootdir . '/' . $filename;
            if (!is_readable($filepath)) {
                continue;
            }
            $raw = file_get_contents($filepath);
            if ($raw === false || trim($raw) === '') {
                continue;
            }
            if (strlen($raw) > self::MAX_BYTES) {
                $raw = substr($raw, 0, self::MAX_BYTES) . "\n\n…";
            }
            $ismarkdown = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'md';
            $format = $ismarkdown ? FORMAT_MARKDOWN : FORMAT_PLAIN;
            return format_text($raw, $format, ['trusted' => false, 'noclean' => false]);
        }
        return '';
    }
}
