# Changelog
All notable changes to **APCu Manager** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **APCu Manager** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.7.0] - 2023-10-25

### Added
- Compatibility with WordPress 6.4.

### Fixed
- With PHP 8.2, in some edge cases, deprecation warnings may be triggered when viewing analytics.

## [3.6.0] - 2023-07-12

### Added
- Compatibility with WordPress 6.3.

### Changed
- The color for `shmop` test in Site Health is now gray to not worry to much about it (was previously orange).

### Fixed
- With APCu version greater than 5.1.21, the 'used' object field may be wrongly computed.

## [3.5.2] - 2023-05-15

### Changed
- Improved objects list rendering time.

### Fixed
- Sorting objects by status produces PHP warnings.

### Removed
- The ability to try to fetch expired objects.

## [3.5.1] - 2023-03-02

### Fixed
- [SEC004] CSRF vulnerability / [CVE-2023-27444](https://www.cve.org/CVERecord?id=CVE-2023-27444) (thanks to [Mika](https://patchstack.com/database/researcher/5ade6efe-f495-4836-906d-3de30c24edad) from [Patchstack](https://patchstack.com)).

## [3.5.0] - 2023-02-24

The developments of PerfOps One suite, of which this plugin is a part, is now sponsored by [Hosterra](https://hosterra.eu).

Hosterra is a web hosting company I founded in late 2022 whose purpose is to propose web services operating in a European data center that is water and energy efficient and ensures a first step towards GDPR compliance.

This sponsoring is a way to keep PerfOps One plugins suite free, open source and independent.

### Added
- Compatibility with WordPress 6.2.

### Fixed
- In some edge-cases, detecting IP may produce PHP deprecation warnings (thanks to [YR Chen](https://github.com/stevapple)).

## [3.4.1] - 2023-01-26

### Changed
- Better handling of hoster-specific configurations.

### Fixed
- The plugin doesn't fully support APCu version greater than 5.1.21 (many thanks to [vgostore](https://github.com/vgostore) for helping me to debunk this). 

## [3.4.0] - 2022-12-31

### Added
- Support for `wp_cache_flush_group()` function.

### Changed
- Improved loading by removing unneeded jQuery references in public rendering (thanks to [Kishorchand](https://github.com/Kishorchandth)).
- [WPCLI] Improved resilience to php configuration-related errors.

## [3.3.0] - 2022-10-06

### Added
- Compatibility with WordPress 6.1.
- Support for `wp_cache_flush_runtime()` function.
- [WPCLI] The results of `wp apcu` commands are now logged in [DecaLog](https://wordpress.org/plugins/decalog/).

### Changed
- Improved ephemeral cache in analytics.
- Updated Kint from version 4.1.1 to version 4.2.0.
- [WPCLI] The results of `wp apcu` commands are now prefixed by the product name.

### Fixed
- [SEC003] Moment.js library updated to 2.29.4 / [Regular Expression Denial of Service (ReDoS)](https://github.com/moment/moment/issues/6012).
- Flushing APCu doesn't flush the internal cache at the same time.

## [3.2.0] - 2022-04-20

### Added
- Compatibility with WordPress 6.0.
- Full support of `wp_cache_*_multi()` functions.

### Changed
- Improved preloading compatibility.
- Updated DecaLog SDK from version 2.0.2 to version 3.0.0.

### Fixed
- [SEC002] Moment.js library updated to 2.29.2 / [CVE-2022-24785](https://github.com/advisories/GHSA-8hfj-j24r-96c4).

## [3.1.1] - 2022-02-23

### Changed
- Hugely improved loading speed.
- Improved options hit ratio.
- In list view, the object's status is now displayed.
- The Y-axis and legend of the fragmentation graph are now more readable.
- Improved deactivation process - with failsafe.
- Implementation of `force` flag to force a refetch rather than relying on the local cache.

### Fixed
- The garbage collector may (wrongly) skip some objects.
- Ajax calls to save/update posts may not refetch the cache value in some conditions.

## [3.1.0] - 2022-02-15

### Added
- New objects viewer in APCu management tools.
- There's now `apcm_objects_list_actions_for_object` and `apcm_objects_list_actions_for_ttl` filters to add custom actions to objects list view.
- Experimental support of `wp_cache_*_multi()` functions that will be introduced in WordPress 6.0.
- Built-in compatibility with unusual hosting environments and poorly coded plugins or themes (thanks to [Renaud Pacouil](https://www.laboiteare.fr) for suggestion, support & tests).

### Changed
- Activating/deactivating object cache setting now forces APCu flush. 
- Deactivating/uninstalling the plugin now forces APCu flush (thanks to [Renaud Pacouil](https://www.laboiteare.fr) for the idea).
- Improved persistent / non-persistent groups handling.
- Improved built-in internal cache speed.
- Site Health page now presents a much more realistic test about object caching.
- Improved coexistence behaviour with other cache management plugins.
- The drop-in file is now updated when plugin is updated.

### Fixed
- Objects at APCu root have no path in tool list view.
- Some options may not be reset when clicking "Reset To Default" button.
- In some conditions, the message for updated settings is incomplete.

### Removed
- APCu metrics collation from command-line as it was meaningless and can cause issues on some hostings.

## [3.0.1] - 2022-01-17

### Fixed
- The Site Health page may launch deprecated tests.

## [3.0.0] - 2022-01-17

### Added
- NEW: APCu manager may be used as a full object cache drop-in replacement for WordPress caching.
- Compatibility with PHP 8.1.
- Added containers, paths and icons to APCu Management tool.
- Added WordPress and W3TC icons in analytics reports.

### Changed
- [BC] The "Delete All" command now flush cache for site or network only.
- [BC] The garbage collector now works only on objects belonging to the site or network.
- [BC] The APCu Management tool now displays only objects belonging to the site or network.
- [BC] The memory fragmentation is now computed from a WordPress point of view: expect a drastic reduction.
- Analytics reports are now able to display WordPress object caching KPIs.
- APCu Manager now supports non expiring TTL (including garbage collecting operations)
- Charts allow now to display more than 2 months of data.
- Improved timescale computation and date display for all charts.
- Bar charts have now a resizable width.
- The hit/miss/insert chart have now swapped values for better readability.
- Updated DecaLog SDK from version 2.0.0 to version 2.0.2.
- Updated PerfOps One library from 2.2.1 to 2.2.2.
- Refactored cache mechanisms to fully support Redis and Memcached.
- Improved bubbles display when width is less than 500px (thanks to [Pat Ol](https://profiles.wordpress.org/pasglop/)).
- The tables headers have now a better contrast (thanks to [Paul Bonaldi](https://profiles.wordpress.org/bonaldi/)).
- Updated documentation and readme files.

### Fixed
- The console menu may display an empty screen (thanks to [Renaud Pacouil](https://www.laboiteare.fr)).
- Object caching method may be wrongly detected in Site Health status (thanks to [freshuk](https://profiles.wordpress.org/freshuk/)).
- There may be name collisions with internal APCu cache.

## [2.6.0] - 2021-12-07

### Added
- Compatibility with WordPress 5.9.
- New button in settings to install recommended plugins.
- The available hooks (filters and actions) are now described in `HOOKS.md` file.

### Changed
- Improved update process on high-traffic sites to avoid concurrent resources accesses.
- Better publishing frequency for metrics.
- Updated labels and links in plugins page.
- X axis for graphs have been redesigned and are more accurate.
- Updated the `README.md` file.

### Fixed
- Country translation with i18n module may be wrong.

## [2.5.0] - 2021-09-07

### Added
- New menu in the admin bar for "flush APCu" quick action.
- It's now possible to hide the main PerfOps One menu via the `poo_hide_main_menu` filter or each submenu via the `poo_hide_analytics_menu`, `poo_hide_consoles_menu`, `poo_hide_insights_menu`, `poo_hide_tools_menu`, `poo_hide_records_menu` and `poo_hide_settings_menu` filters (thanks to [Jan Thiel](https://github.com/JanThiel)).

### Changed
- Updated DecaLog SDK from version 1.2.0 to version 2.0.0.

### Fixed
- There may be name collisions for some functions if version of WordPress is lower than 5.6.
- The main PerfOps One menu is not hidden when it doesn't contain any items (thanks to [Jan Thiel](https://github.com/JanThiel)).
- In some very special conditions, the plugin may be in the default site language rather than the user's language.
- The PerfOps One menu builder is not compatible with Admin Menu Editor plugin (thanks to [dvokoun](https://wordpress.org/support/users/dvokoun/)).

## [2.4.1] - 2021-08-11

### Changed
- New redesigned UI for PerfOps One plugins management and menus (thanks to [Loïc Antignac](https://github.com/webaxones), [Paul Bonaldi](https://profiles.wordpress.org/bonaldi/), [Axel Ducoron](https://github.com/aksld), [Laurent Millet](https://profiles.wordpress.org/wplmillet/), [Samy Rabih](https://github.com/samy) and [Raphaël Riehl](https://github.com/raphaelriehl) for their invaluable help).

### Fixed
- In some conditions, the plugin may be in the default site language rather than the user's language.

## [2.4.0] - 2021-06-22

### Added
- Compatibility with WordPress 5.8.
- Integration with DecaLog SDK.
- Traces and metrics collation and publication.
- New option, available via settings page and wp-cli, to disable/enable metrics collation.

### Changed
- [WP-CLI] `apcu status` command now displays DecaLog SDK version too.

## [2.3.0] - 2021-02-24

### Added
- Compatibility with WordPress 5.7.

### Changed
- Consistent reset for settings. 
- Improved translation loading.
- [WP_CLI] `apcu` command have now a definition and all synopsis are up to date.

### Fixed
- In Site Health section, Opcache status may be wrong (or generates PHP warnings) if OPcache API usage is restricted.

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
- Full integration with PerfOps One suite.
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