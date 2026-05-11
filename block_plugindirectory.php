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

/**
 * Block "Plugin directory" — lists all non-standard plugins with README and
 * compatibility information.
 *
 * Business logic is delegated to the helper classes under
 * `\block_plugindirectory\local\*`; this file only wires the block lifecycle
 * to the renderer.
 *
 * @package    block_plugindirectory
 * @copyright  2026 moodle-td.de
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use block_plugindirectory\local\plugin_collector;

/**
 * Block "Plugin directory" — lists all non-standard plugins with README and
 * compatibility information.
 */
class block_plugindirectory extends block_base {
    /**
     * Initialise the block title.
     */
    public function init(): void {
        $this->title = get_string('pluginname', 'block_plugindirectory');
    }

    /**
     * Allow the block on the personal dashboard, site front page and admin pages.
     *
     * @return array<string,bool>
     */
    public function applicable_formats(): array {
        return ['my' => true, 'site' => true, 'admin' => true];
    }

    /**
     * The block has no global or instance configuration.
     */
    public function has_config(): bool {
        return false;
    }

    /**
     * Disallow more than one instance per page.
     */
    public function instance_allow_multiple(): bool {
        return false;
    }

    /**
     * Build the block contents — delegates to the plugin_collector helper.
     */
    public function get_content(): ?stdClass {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text   = '';
        $this->content->footer = '';

        // Site admins always see it; other roles must hold the explicit cap.
        $context = context_block::instance($this->instance->id);
        if (!is_siteadmin() && !has_capability('block/plugindirectory:view', $context)) {
            return $this->content;
        }

        $plugins = plugin_collector::collect();

        if ($plugins === []) {
            $this->content->text = html_writer::div(
                get_string('noplugins', 'block_plugindirectory'),
                'alert alert-info'
            );
            return $this->content;
        }

        global $OUTPUT;
        $newplugins   = array_values(array_filter($plugins, fn($p) => $p['is_new']));
        $otherplugins = array_values(array_filter($plugins, fn($p) => !$p['is_new']));
        $types        = array_unique(array_column($plugins, 'type'));
        sort($types);
        $typeoptions  = array_map(fn($t) => ['value' => $t, 'label' => $t], $types);

        $this->content->text = $OUTPUT->render_from_template(
            'block_plugindirectory/content',
            [
                'plugins'     => $otherplugins,
                'newplugins'  => $newplugins,
                'has_new'     => $newplugins !== [],
                'newcount'    => count($newplugins),
                'plugincount' => get_string('plugincount', 'block_plugindirectory', count($plugins)),
                'typeoptions' => $typeoptions,
            ]
        );
        return $this->content;
    }
}
