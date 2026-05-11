<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace block_plugindirectory\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Determines compatibility of an installed plugin against the current Moodle core.
 *
 * Pure value-object/function style — no DB, no state. Caller passes the plugin
 * info object (typically a {@see \core\plugininfo\base} subclass) and gets a
 * structured result for rendering.
 *
 * @package    block_plugindirectory
 * @copyright  2026 moodle-td.de
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
final class compatibility_checker {

    public const STATUS_OK       = 'success';
    public const STATUS_WARN     = 'warning';
    public const STATUS_DANGER   = 'danger';

    /**
     * Compute a compatibility verdict for a plugin.
     *
     * @param object $plugininfo Either a real plugininfo object or any
     *   `\stdClass` exposing `versionrequires`, `pluginsupported`,
     *   `pluginincompatible` (test-friendly).
     * @return array{icon:string,class:string,tooltip:string}
     */
    public static function evaluate(object $plugininfo): array {
        global $CFG;

        $coreversion = (float) $CFG->version;
        $corebranch  = (int) $CFG->branch;

        $requires     = (float) ($plugininfo->versionrequires ?? 0);
        $supported    = $plugininfo->pluginsupported ?? null;
        $incompatible = $plugininfo->pluginincompatible ?? null;

        if ($incompatible !== null && $corebranch >= (int) $incompatible) {
            return [
                'icon'    => '✗',
                'class'   => self::STATUS_DANGER,
                'tooltip' => get_string('compat_incompatible', 'block_plugindirectory', $corebranch),
            ];
        }
        if ($requires > 0 && $coreversion < $requires) {
            return [
                'icon'    => '✗',
                'class'   => self::STATUS_DANGER,
                'tooltip' => get_string('compat_requires', 'block_plugindirectory', (string) $requires),
            ];
        }
        if (is_array($supported) && count($supported) === 2) {
            [$min, $max] = $supported;
            if ($corebranch < (int) $min || $corebranch > (int) $max) {
                return [
                    'icon'    => '⚠',
                    'class'   => self::STATUS_WARN,
                    'tooltip' => get_string('compat_unsupported', 'block_plugindirectory', "{$min}–{$max}"),
                ];
            }
        }
        return [
            'icon'    => '✓',
            'class'   => self::STATUS_OK,
            'tooltip' => get_string('compat_ok', 'block_plugindirectory'),
        ];
    }
}
