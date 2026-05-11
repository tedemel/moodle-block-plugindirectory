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
 * File for url_extractor.
 *
 * @package    block_plugindirectory
 * @copyright  2026 moodle-td.de
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace block_plugindirectory\local;


/**
 * Extracts GitHub and Moodle-Plugin-Directory URLs from a plugin's README and
 * its version.php fallback.
 *
 * Both methods preserve `href` values before stripping HTML so that URLs in
 * markdown-rendered anchors are not lost (which was the case in the original
 * `strip_tags`-only implementation).
 *
 * @package    block_plugindirectory
 * @copyright  2026 moodle-td.de
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
final class url_extractor {
    /** @var string|int|array Regex matching a GitHub repository URL. */
    private const PATTERN_GITHUB    = '#https://github\.com/[\w.\-]+/[\w.\-]+#';
    /** @var string|int|array Regex matching a Moodle Plugin Directory URL. */
    private const PATTERN_MOODLEDIR = '#https://moodle\.org/plugins/[\w./\-]+#';

    /**
     * Find a GitHub repo URL — first in the rendered README, then in version.php.
     *
     * @param string $rootdir Plugin root directory.
     * @param string $readmecontent Pre-rendered README HTML.
     * @return string
     */
    public static function extract_github_url(string $rootdir, string $readmecontent): string {
        return self::find_url($rootdir, $readmecontent, self::PATTERN_GITHUB);
    }

    /**
     * Find a Moodle Plugin Directory URL — first in the rendered README, then
     * in version.php.
     *
     * @param string $rootdir Plugin root directory.
     * @param string $readmecontent Pre-rendered README HTML.
     * @return string
     */
    public static function extract_moodledir_url(string $rootdir, string $readmecontent): string {
        return self::find_url($rootdir, $readmecontent, self::PATTERN_MOODLEDIR);
    }

    /**
     * Convert HTML to plain text but inline href URLs from anchors first.
     *
     * Exposed for tests; production callers should use the extract_* methods.
     *
     * @param string $html HTML input.
     * @return string Plain text including inlined href URLs.
     */
    public static function strip_html_preserving_hrefs(string $html): string {
        $with = preg_replace(
            '#<a\s+[^>]*href="([^"]+)"[^>]*>(.*?)</a>#i',
            '$2 $1',
            $html
        );
        return strip_tags($with ?? $html);
    }

    /**
     * Apply the pattern to the README text first, fall back to version.php.
     *
     * @param string $rootdir Plugin root directory.
     * @param string $readmecontent Pre-rendered README HTML.
     * @param string $pattern PCRE pattern to apply.
     * @return string
     */
    private static function find_url(string $rootdir, string $readmecontent, string $pattern): string {
        $plain = self::strip_html_preserving_hrefs($readmecontent);
        if (preg_match($pattern, $plain, $m)) {
            return rtrim($m[0], '/.,);');
        }
        $versionfile = $rootdir . '/version.php';
        if (is_readable($versionfile)) {
            $src = file_get_contents($versionfile);
            if ($src !== false && preg_match($pattern, $src, $m)) {
                return rtrim($m[0], '/.,);');
            }
        }
        return '';
    }
}
