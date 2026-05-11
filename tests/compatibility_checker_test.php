<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace block_plugindirectory;

use block_plugindirectory\local\compatibility_checker;

/**
 * Tests for {@see compatibility_checker}.
 *
 * @package    block_plugindirectory
 * @copyright  2026 moodle-td.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_plugindirectory\local\compatibility_checker
 */
final class compatibility_checker_test extends \advanced_testcase {
    public function test_ok_when_no_constraints(): void {
        $info = (object) [
            'versionrequires' => 0,
            'pluginsupported' => null,
            'pluginincompatible' => null,
        ];
        $result = compatibility_checker::evaluate($info);
        $this->assertSame('✓', $result['icon']);
        $this->assertSame(compatibility_checker::STATUS_OK, $result['class']);
    }

    public function test_danger_when_explicit_incompatible(): void {
        global $CFG;
        $info = (object) [
            'versionrequires' => 0,
            'pluginsupported' => null,
            'pluginincompatible' => (int) $CFG->branch,
        ];
        $result = compatibility_checker::evaluate($info);
        $this->assertSame('✗', $result['icon']);
        $this->assertSame(compatibility_checker::STATUS_DANGER, $result['class']);
    }

    public function test_danger_when_versionrequires_too_high(): void {
        global $CFG;
        $info = (object) [
            'versionrequires' => (float) $CFG->version + 1000000,
            'pluginsupported' => null,
            'pluginincompatible' => null,
        ];
        $result = compatibility_checker::evaluate($info);
        $this->assertSame(compatibility_checker::STATUS_DANGER, $result['class']);
    }

    public function test_warning_when_outside_supported_branches(): void {
        global $CFG;
        $futurebranch = (int) $CFG->branch + 100;
        $info = (object) [
            'versionrequires' => 0,
            'pluginsupported' => [$futurebranch, $futurebranch + 10],
            'pluginincompatible' => null,
        ];
        $result = compatibility_checker::evaluate($info);
        $this->assertSame('⚠', $result['icon']);
        $this->assertSame(compatibility_checker::STATUS_WARN, $result['class']);
    }
}
