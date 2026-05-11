<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace block_plugindirectory;

use block_plugindirectory\local\install_log;

/**
 * Tests for {@see install_log}.
 *
 * @package    block_plugindirectory
 * @copyright  2026 moodle-td.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_plugindirectory\local\install_log
 */
final class install_log_test extends \advanced_testcase {

    public function test_earliest_install_times_returns_min_per_plugin(): void {
        global $DB;
        $this->resetAfterTest();

        $now = time();
        $DB->insert_record('upgrade_log', (object) [
            'type' => 0, 'plugin' => 'local_x', 'version' => '1',
            'targetversion' => '1', 'info' => '', 'details' => '', 'backtrace' => '',
            'userid' => 0, 'timemodified' => $now - 1000,
        ]);
        $DB->insert_record('upgrade_log', (object) [
            'type' => 0, 'plugin' => 'local_x', 'version' => '2',
            'targetversion' => '2', 'info' => '', 'details' => '', 'backtrace' => '',
            'userid' => 0, 'timemodified' => $now - 100,
        ]);
        $DB->insert_record('upgrade_log', (object) [
            'type' => 0, 'plugin' => 'local_y', 'version' => '1',
            'targetversion' => '1', 'info' => '', 'details' => '', 'backtrace' => '',
            'userid' => 0, 'timemodified' => $now - 500,
        ]);

        $times = install_log::earliest_install_times();

        $this->assertArrayHasKey('local_x', $times);
        $this->assertArrayHasKey('local_y', $times);
        $this->assertSame($now - 1000, $times['local_x']);
        $this->assertSame($now - 500, $times['local_y']);
    }

    public function test_earliest_install_times_skips_empty_plugin_field(): void {
        global $DB;
        $this->resetAfterTest();

        $DB->insert_record('upgrade_log', (object) [
            'type' => 0, 'plugin' => '', 'version' => '',
            'targetversion' => '', 'info' => '', 'details' => '', 'backtrace' => '',
            'userid' => 0, 'timemodified' => time(),
        ]);
        $times = install_log::earliest_install_times();
        $this->assertArrayNotHasKey('', $times);
    }
}
