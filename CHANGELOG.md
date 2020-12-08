# Changelog
All notable changes to **APCu Manager** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **APCu Manager** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- Improved translation loading.

## [2.2.0] - 2020-11-23

### Added
- Compatibility with WordPress 5.6.

### Changed
- Improvement in the way roles are detected.
- [WP-CLI] Memory analytics now displays total memory and occupation ratio.

### Fixed
- [SEC001] User may be wrongly detected in XML-RPC or Rest API calls.
- When site is in english and a user choose another language for herself/himself, menu may be stuck in english.

## [2.1.0] - 2020-10-13

### Added
- [WP-CLI] New command to discover exit codes: see `wp help apcu exitcode` for details.
- APCu Manager now integrates [Spyc](https://github.com/mustangostang/spyc) as yaml parser.
- New "Objects" box in analytics page, displaying objects breakdown.

### Changed
- Improved IP detection  (thanks to [Ludovic Riaudel](https://github.com/lriaudel)).
- [WP-CLI] `wp apcu settings` and `wp apcu analytics` now support `--stdout` flag.
- [WP-CLI] Improved `wp apcu analytics` yaml formatting.
- [WP-CLI] Improved documentation (with examples).
- The analytics dashboard now displays a warning if analytics features are not activated.
- Prepares PerfOps menus to future 5.6 version of WordPress.

### Fixed
- In admin dashboard, the statistics link is visible even if analytics features are not activated.
- Typos in admin screens.
- [WP-CLI] Typos in documentation.

### Removed
- The "Objects Count" box in analytics page because it was useless.

## [2.0.0] - 2020-09-29

### Added
- [WP-CLI] New command to toggle on/off main settings: see `wp help apcu settings` for details.
- [WP-CLI] New command to display APCu Manager status: see `wp help apcu status` for details.
- [WP-CLI] New command to display APCu analytics: see `wp help apcu analytics` for details.
- Support for APCu multi configuration (web and command-line).
- Support for data feeds - reserved for future use.
- New Site Health "info" section about shared memory.

### Changed
- The positions of PerfOps menus are pushed lower to avoid collision with other plugins (thanks to [Loïc Antignac](https://github.com/webaxones)).
- Improved layout for language indicator.
- Admin notices are now set to "don't display" by default.
- Improved changelog readability.
- The integrated markdown parser is now [Markdown](https://github.com/cebe/markdown) from Carsten Brandt.

### Fixed
- In some cases, APCu detection may cause a PHP Warning.
- With Firefox, some links are unclickable in the Control Center (thanks to [Emil1](https://wordpress.org/support/users/milouze/)).

### Removed
- Parsedown as integrated markdown parser.

## [1.3.0] - 2020-07-20

### Added
- Compatibility with WordPress 5.5.
- Garbage collector for out-of-date cached objects (see "PerfOps Settings" > "APCu Manager").

## [1.2.3] - 2020-06-29

### Changed
- Full compatibility with PHP 7.4.
- Automatic switching between memory and transient when a cache plugin is installed without a properly configured Redis / Memcached.

### Fixed
- When used for the first time, settings checkboxes may remain checked after being unchecked.

## [1.2.2] - 2020-05-05

### Changed
- The charts take now care of DST in user's browser.
- The daily distribution charts have now a better timeline.

## [1.2.1] - 2020-05-04

### Fixed
- There's an error while activating the plugin when the server is Microsoft IIS with Windows 10.
- With Microsoft Edge, some layouts may be ugly.

## [1.2.0] - 2020-04-12

### Added
- Compatibility with [DecaLog](https://wordpress.org/plugins/decalog/) early loading feature.

### Changed
- The settings page have now the standard WordPress style.
- Better styling in "PerfOps Settings" page.
- In site health "info" tab, the boolean are now clearly displayed.

### Removed
- Unneeded tool links in settings page.

## [1.1.0] - 2020-03-01

### Added
- Full integration with PerfOps.One suite.
- Compatibility with WordPress 5.4.

### Changed
- New menus (in the left admin bar) for accessing features: "PerfOps Analytics", "PerfOps Tools" and "PerfOps Settings".
- Analysis delta time has been increased to avoid holes in stats when cron is not fully reliable.

### Fixed
- With some plugins, box tooltips may be misplaced (css collision).

### Removed
- Compatibility with WordPress versions prior to 5.2.
- Old menus entries, due to PerfOps integration.

## [1.0.0] - 2020-01-09

- Initial release