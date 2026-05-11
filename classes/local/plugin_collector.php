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
 * File for plugin_collector.
 *
 * @package    block_plugindirectory
 * @copyright  2026 moodle-td.de
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace block_plugindirectory\local;


/**
 * Orchestrator: gathers all non-standard plugins into the row structure
 * consumed by the Mustache template.
 *
 * @package    block_plugindirectory
 * @copyright  2026 moodle-td.de
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
final class plugin_collector {
    /**
     * Whitelist of "main" plugin types. Subplugin types (e.g.
     * customcertelement_*, certificateelement_*, realtimeplugin_*) are
     * filtered out so the block stays focused on the user-facing plugin
     * inventory.
     */
    /** @var string|int|array Whitelist of "main" Moodle plugin types considered by the dashboard. */
    private const MAIN_TYPES = [
        'antivirus', 'auth', 'availability', 'block', 'communication',
        'contenttype', 'customfield', 'dataformat', 'editor', 'enrol',
        'factor', 'fileconverter', 'filter', 'format', 'gradeexport',
        'gradeimport', 'gradereport', 'gradingform', 'local', 'media',
        'message', 'mlbackend', 'mnetservice', 'mod', 'plagiarism',
        'portfolio', 'profilefield', 'qtype', 'report', 'repository',
        'search', 'theme', 'tiny', 'atto', 'tool', 'webservice',
    ];

    /** A plugin counts as "new" if it was installed in the last week. */
    /** @var string|int|array Window in seconds during which a freshly installed plugin counts as "new". */
    private const NEW_WINDOW = 7 * DAYSECS;

    /**
     * Collect the rendered row data for every contributed plugin.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function collect(): array {
        $pluginman    = \core_plugin_manager::instance();
        $alltypes     = $pluginman->get_plugins();
        $typenames    = $pluginman->get_plugin_types();
        $installtimes = install_log::earliest_install_times();
        $newthreshold = time() - self::NEW_WINDOW;

        $rows = [];
        foreach ($alltypes as $type => $typeplugins) {
            if (!in_array($type, self::MAIN_TYPES, true)) {
                continue;
            }
            foreach ($typeplugins as $info) {
                if ($info->is_standard()) {
                    continue;
                }
                $rows[] = self::build_row($info, $type, $typenames[$type] ?? $type, $installtimes, $newthreshold);
            }
        }

        // Verify Moodle-directory existence for rows that don't have a URL yet.
        $unverified = array_filter(array_column($rows, 'component', null), function ($unused, $i) use ($rows) {
            unset($unused);
            return empty($rows[$i]['moodledirurl']);
        }, ARRAY_FILTER_USE_BOTH);

        if ($unverified !== []) {
            $verified = moodledir_verifier::verify(array_values($unverified));
            foreach ($rows as &$row) {
                if (empty($row['moodledirurl']) && !empty($verified[$row['component']])) {
                    $row['moodledirurl']  = 'https://moodle.org/plugins/' . $row['component'];
                    $row['has_moodledir'] = true;
                }
            }
            unset($row);
        }

        usort($rows, static function (array $a, array $b): int {
            $typecmp = strcmp($a['type'], $b['type']);
            return $typecmp !== 0 ? $typecmp : strcmp($a['component'], $b['component']);
        });

        return $rows;
    }

    /**
     * Build a single row from a plugininfo object.
     *
     * @param object $info plugininfo object.
     * @param string $type Plugin type.
     * @param string $typename Human-readable type label.
     * @param array<string,int> $installtimes Map component=>install timestamp.
     * @param int $newthreshold Cutoff timestamp below which a plugin is no longer "new".
     * @return array<string,mixed>
     */
    private static function build_row(
        object $info,
        string $type,
        string $typename,
        array $installtimes,
        int $newthreshold
    ): array {
        $component   = $info->component;
        $rootdir     = $info->rootdir;
        $readmehtml  = readme_reader::render($rootdir);
        $githuburl   = url_extractor::extract_github_url($rootdir, $readmehtml);
        $moodledirurl = url_extractor::extract_moodledir_url($rootdir, $readmehtml);
        $installtime = $installtimes[$component] ?? 0;
        $isnew       = $installtime > 0 && $installtime >= $newthreshold;
        $compat      = compatibility_checker::evaluate($info);

        return [
            'component'      => $component,
            'displayname'    => $info->displayname ?? $component,
            'type'           => $type,
            'typename'       => $typename,
            'version'        => $info->versiondisk ?? '',
            'moodledirurl'   => $moodledirurl,
            'has_moodledir'  => !empty($moodledirurl),
            'githuburl'      => $githuburl,
            'has_github'     => !empty($githuburl),
            'readmehtml'     => $readmehtml,
            'has_readme'     => !empty($readmehtml),
            'readme_id'      => 'plugindir-readme-' . str_replace(['_', '.'], '-', $component),
            'is_new'         => $isnew,
            'installedstr'   => $installtime > 0
                ? userdate($installtime, get_string('strftimedate', 'langconfig'))
                : '',
            'compat_icon'    => $compat['icon'],
            'compat_class'   => $compat['class'],
            'compat_tooltip' => $compat['tooltip'],
        ];
    }
}
