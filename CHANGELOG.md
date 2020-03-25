# Changelog
All notable changes to **APCu Manager** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **APCu Manager** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased - will be 1.2.0]
### Added
- Compatibility with [DecaLog](https://wordpress.org/plugins/decalog/) early loading feature.
### Changed
- The settings page have now the standard WordPress style.
- Better styling in "PerfOps Settings" page.
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