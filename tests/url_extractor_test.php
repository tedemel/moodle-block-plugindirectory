<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace block_plugindirectory;

use block_plugindirectory\local\url_extractor;

/**
 * Tests for {@see url_extractor}.
 *
 * @package    block_plugindirectory
 * @copyright  2026 moodle-td.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_plugindirectory\local\url_extractor
 */
final class url_extractor_test extends \advanced_testcase {
    public function test_strip_html_preserving_hrefs_keeps_anchor_targets(): void {
        $html = '<p>Source: <a href="https://example.com/repo">repo</a></p>';
        $plain = url_extractor::strip_html_preserving_hrefs($html);
        $this->assertStringContainsString('https://example.com/repo', $plain);
        $this->assertStringContainsString('repo', $plain);
    }

    public function test_extract_github_url_from_visible_text(): void {
        $tmp = make_request_directory();
        $readme = '<p>Source: https://github.com/example/repo</p>';
        $this->assertSame(
            'https://github.com/example/repo',
            url_extractor::extract_github_url($tmp, $readme)
        );
    }

    public function test_extract_github_url_from_anchor_href(): void {
        // This was a regression: strip_tags() alone dropped href URLs. Now the
        // anchor's target survives the HTML cleanup.
        $tmp = make_request_directory();
        $readme = '<p>See <a href="https://github.com/foo/bar">repo</a></p>';
        $this->assertSame(
            'https://github.com/foo/bar',
            url_extractor::extract_github_url($tmp, $readme)
        );
    }

    public function test_extract_github_url_from_version_php_fallback(): void {
        $tmp = make_request_directory();
        file_put_contents(
            $tmp . '/version.php',
            "<?php\n// see https://github.com/foo/bar for source\n"
        );
        $this->assertSame(
            'https://github.com/foo/bar',
            url_extractor::extract_github_url($tmp, '')
        );
    }

    public function test_extract_github_url_returns_empty_when_nothing_found(): void {
        $tmp = make_request_directory();
        $this->assertSame('', url_extractor::extract_github_url($tmp, '<p>Just text.</p>'));
    }

    public function test_extract_moodledir_url_from_anchor_href(): void {
        $tmp = make_request_directory();
        $readme = '<p>Available <a href="https://moodle.org/plugins/local_x">on Moodle</a>.</p>';
        $this->assertSame(
            'https://moodle.org/plugins/local_x',
            url_extractor::extract_moodledir_url($tmp, $readme)
        );
    }

    public function test_extract_moodledir_url_returns_empty_when_no_match(): void {
        $tmp = make_request_directory();
        $this->assertSame('', url_extractor::extract_moodledir_url($tmp, '<p>nothing</p>'));
    }
}
