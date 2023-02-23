# APCu Manager
[![version](https://badgen.net/github/release/Pierre-Lannoy/wp-apcu-manager/)](https://wordpress.org/plugins/apcu-manager/)
[![php](https://badgen.net/badge/php/7.2+/green)](https://wordpress.org/plugins/apcu-manager/)
[![wordpress](https://badgen.net/badge/wordpress/5.2+/green)](https://wordpress.org/plugins/apcu-manager/)
[![license](https://badgen.net/github/license/Pierre-Lannoy/wp-apcu-manager/)](/license.txt)

__APCu Manager__ is a full featured APCu management and analytics reporting tool. It allows you to monitor and optimize APCu operations on your WordPress site or network.

See [WordPress directory page](https://wordpress.org/plugins/apcu-manager/) or [official website](https://perfops.one/apcu-manager).

__APCu Manager__ offers a persistent object cache backend to WordPress: just activate the option in the settings and you will experience a real speed up of your site or network.

__APCu Manager__ works on dedicated or shared servers. It is compatible with all plugins using APCu, like PerfOps One suite or W3 Total Cache. Its main management features are:

* drop-in replacement for WordPress object caching;
* individual object deletion;
* bulk object deletion;
* objects browsing and inspecting;
* smart garbage collection;
* full cache clearing.

> ⚠️ __APCu Manager__ doesn't work on PHP clustered environments!

__APCu Manager__ is also a full featured analytics reporting tool that analyzes all APCu operations on your site. It can report:

* KPIs: hit ratio, free memory, cached objects, keys saturation, memory fragmentation and availability;
* metrics variations;
* metrics distributions;
* plugins consumption.

__APCu Manager__ supports multisite report delegation.

> __APCu Manager__ is part of [PerfOps One](https://perfops.one/), a suite of free and open source WordPress plugins dedicated to observability and operations performance.
> 
> __The development of the PerfOps One suite is sponsored by [Hosterra - Ethical & Sustainable Internet Hosting](https://hosterra.eu/).__

__APCu Manager__ is a free and open source plugin for WordPress. It integrates many other free and open source works (as-is or modified). Please, see 'about' tab in the plugin settings to see the details.

## WP-CLI

__APCu Manager__ implements a set of WP-CLI commands. For a full help on these commands, please read [this guide](WP-CLI.md).

## Hooks

__APCu Manager__ introduces some filters and actions to allow plugin customization. Please, read the [hooks reference](HOOKS.md) to learn more about them.

## Installation

1. From your WordPress dashboard, visit _Plugins | Add New_.
2. Search for 'APCu Manager'.
3. Click on the 'Install Now' button.

You can now activate __APCu Manager__ from your _Plugins_ page.

## Support

For any technical issue, or to suggest new idea or feature, please use [GitHub issues tracker](https://github.com/Pierre-Lannoy/wp-apcu-manager/issues). Before submitting an issue, please read the [contribution guidelines](CONTRIBUTING.md).

Alternatively, if you have usage questions, you can open a discussion on the [WordPress support page](https://wordpress.org/support/plugin/apcu-manager/). 

## Contributing

Before submitting an issue or a pull request, please read the [contribution guidelines](CONTRIBUTING.md).

> ⚠️ The `master` branch is the current development state of the plugin. If you want a stable, production-ready version, please pick the last official [release](https://github.com/Pierre-Lannoy/wp-apcu-manager/releases).

## Smoke tests
[![WP compatibility](https://plugintests.com/plugins/apcu-manager/wp-badge.svg)](https://plugintests.com/plugins/apcu-manager/latest)
[![PHP compatibility](https://plugintests.com/plugins/apcu-manager/php-badge.svg)](https://plugintests.com/plugins/apcu-manager/latest)