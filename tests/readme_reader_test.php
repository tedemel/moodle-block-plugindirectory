<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace block_plugindirectory;

use block_plugindirectory\local\readme_reader;

/**
 * Tests for {@see readme_reader}.
 *
 * @package    block_plugindirectory
 * @copyright  2026 moodle-td.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_plugindirectory\local\readme_reader
 */
final class readme_reader_test extends \advanced_testcase {
    public function test_empty_when_no_readme_present(): void {
        $tmp = make_request_directory();
        $this->assertSame('', readme_reader::render($tmp));
    }

    public function test_markdown_file_is_rendered(): void {
        $tmp = make_request_directory();
        file_put_contents($tmp . '/README.md', "# Title\n\nHello world.\n");
        $rendered = readme_reader::render($tmp);
        $this->assertStringContainsString('Title', $rendered);
        $this->assertStringContainsString('Hello world', $rendered);
    }

    public function test_long_file_is_truncated_with_ellipsis(): void {
        $tmp = make_request_directory();
        file_put_contents($tmp . '/README.md', str_repeat('A', readme_reader::MAX_BYTES + 1000));
        $rendered = readme_reader::render($tmp);
        $this->assertStringContainsString('…', $rendered);
    }

    public function test_blank_file_is_skipped(): void {
        $tmp = make_request_directory();
        file_put_contents($tmp . '/README.md', "   \n  ");
        $this->assertSame('', readme_reader::render($tmp));
    }

    public function test_txt_file_is_rendered_as_plain_text(): void {
        $tmp = make_request_directory();
        file_put_contents($tmp . '/README.txt', 'Plain ASCII content.');
        $rendered = readme_reader::render($tmp);
        $this->assertStringContainsString('Plain ASCII content', $rendered);
    }
}
