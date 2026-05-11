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
 * File for moodledir_verifier.
 *
 * @package    block_plugindirectory
 * @copyright  2026 moodle-td.de
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace block_plugindirectory\local;


/**
 * Verifies which Moodle components actually exist in the public Moodle Plugin
 * Directory (`https://moodle.org/plugins/<component>`).
 *
 * Uses parallel HEAD requests through `curl_multi_*` and caches the result for
 * 7 days inside `config_plugins` (`block_plugindirectory/moodledir_cache`).
 *
 * @package    block_plugindirectory
 * @copyright  2026 moodle-td.de
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
final class moodledir_verifier {
    /** @var string|int|array config_plugins key under which verifier results are cached. */
    private const CACHE_KEY    = 'moodledir_cache';
    /** @var string|int|array Cache lifetime for a verified plugin-directory entry. */
    private const CACHE_TTL    = 7 * DAYSECS;
    /** @var string|int|array Total cURL timeout (seconds) per HEAD request. */
    private const TIMEOUT      = 4;
    /** @var string|int|array cURL connect timeout (seconds) per HEAD request. */
    private const CONNECT_TIME = 3;

    /**
     * Returns [component => bool] indicating which components map to a live
     * plugin-directory entry.
     *
     * @param string[] $components
     * @return array<string,bool>
     */
    public static function verify(array $components): array {
        if ($components === []) {
            return [];
        }

        $cacheraw = get_config('block_plugindirectory', self::CACHE_KEY);
        $cache    = $cacheraw ? (json_decode($cacheraw, true) ?: []) : [];
        $now      = time();

        $stale = [];
        foreach ($components as $component) {
            if (!isset($cache[$component]) || ($now - ($cache[$component]['t'] ?? 0)) > self::CACHE_TTL) {
                $stale[] = $component;
            }
        }

        if ($stale !== [] && function_exists('curl_multi_init')) {
            $cache = array_merge($cache, self::check_remote($stale, $now));
            set_config(self::CACHE_KEY, json_encode($cache), 'block_plugindirectory');
        }

        $result = [];
        foreach ($components as $component) {
            $result[$component] = !empty($cache[$component]['exists']);
        }
        return $result;
    }

    /**
     * Run HEAD requests in parallel and return cache fragments keyed by
     * component.
     *
     * @param string[] $components
     * @return array<string,array{exists:bool,t:int}>
     */
    private static function check_remote(array $components, int $now): array {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($components as $component) {
            $ch = curl_init('https://moodle.org/plugins/' . $component);
            curl_setopt_array($ch, [
                CURLOPT_NOBODY         => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIME,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT      => 'Moodle block_plugindirectory',
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$component] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            if ($running > 0) {
                curl_multi_select($mh, 1.0);
            }
        } while ($running > 0);

        $out = [];
        foreach ($handles as $component => $ch) {
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $out[$component] = ['exists' => $code === 200, 't' => $now];
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        return $out;
    }
}
