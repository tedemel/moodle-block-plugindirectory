# Changelog

All notable changes to `block_plugindirectory` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] — 2026-05-19

### Changed
- Promoted maturity from `MATURITY_ALPHA` to `MATURITY_STABLE`.
- Compatibility verified for Moodle 5.2.
- `$plugin->supported = [405, 502]` set in `version.php` (range 4.5 LTS – 5.2).
- CI matrix extended to `MOODLE_501_STABLE` and `MOODLE_502_STABLE` (PHP 8.3 and 8.4).
- CI postgres service image bumped to `postgres:16` (required by Moodle 5.2).

## [1.0.0] — 2026-05-11

### Added
- Initial release.
- Admin dashboard block listing installed non-core add-on plugins.
- Compact row view with click-to-expand details.
- Search + plugin-type filter.
- "NEW" badge for plugins installed within the last 7 days.
- Compatibility indicator based on `versionrequires`, `pluginsupported`, `pluginincompatible`.
- Inline README rendering (Markdown, max. 8 KB).
- Verified links to the Moodle plugin directory (7-day cache) and to GitHub.
- Site-admin-only visibility.
