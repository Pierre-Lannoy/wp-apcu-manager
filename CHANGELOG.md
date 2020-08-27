# Changelog
All notable changes to **APCu Manager** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **APCu Manager** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased - Will be 1.3.1]
### Changed
- The positions of PerfOps menus are pushed lower to avoid collision with other plugins (thanks to [Loïc Antignac](https://github.com/webaxones)).

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
### Initial release