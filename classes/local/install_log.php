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
 * Reads earliest install timestamps from the upgrade_log.
 *
 * @package    block_plugindirectory
 * @copyright  2026 moodle-td.de
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
final class install_log {

    /**
     * Map of `component` → earliest `timemodified` recorded in `mdl_upgrade_log`.
     *
     * @return array<string,int>
     */
    public static function earliest_install_times(): array {
        global $DB;
        $rows = $DB->get_records_sql(
            'SELECT plugin, MIN(timemodified) AS installtime
               FROM {upgrade_log}
              WHERE plugin IS NOT NULL AND plugin <> :empty
              GROUP BY plugin',
            ['empty' => '']
        );
        $map = [];
        foreach ($rows as $row) {
            $map[$row->plugin] = (int) $row->installtime;
        }
        return $map;
    }
}
