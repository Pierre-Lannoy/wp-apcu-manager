=== APCu Manager ===
Contributors: PierreLannoy, hosterra
Tags: APCu, cache, monitor, object cache, w3tc
Requires at least: 5.2
Requires PHP: 7.2
Tested up to: 6.4
Stable tag: 3.7.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

APCu statistics and management right in the WordPress admin dashboard.

== Description ==

**APCu statistics and management right in the WordPress admin dashboard.**

**APCu Manager** is a full featured APCu management and analytics reporting tool. It allows you to monitor and optimize APCu operations on your WordPress site or network.

**APCu Manager** offers a persistent object cache backend to WordPress: just activate the option in the settings and you will experience a real speed up of your site or network.

**APCu Manager** works on dedicated or shared servers. It is compatible with all plugins using APCu, like PerfOps One suite or W3 Total Cache. Its main management features are:

* drop-in replacement for WordPress object caching;
* individual object deletion;
* bulk object deletion;
* objects browsing and inspecting;
* smart garbage collection;
* full cache clearing.

> ⚠️ **APCu Manager** doesn't work on PHP clustered environments!

**APCu Manager** is also a full featured analytics reporting tool that analyzes all APCu operations on your site. It can report:

* KPIs: hit ratio, free memory, cached objects, keys saturation, memory fragmentation and availability;
* metrics variations;
* metrics distributions;
* plugins consumption.

**APCu Manager** supports multisite report delegation (see FAQ).

**APCu Manager** supports a set of WP-CLI commands to:
                 
* toggle on/off main settings - see `wp help apcu settings` for details;
* obtain operational statistics - see `wp help apcu analytics` for details.

For a full help on WP-CLI commands in APCu Manager, please [read this guide](https://perfops.one/apcu-manager-wpcli).

> **APCu Manager** is part of [PerfOps One](https://perfops.one/), a suite of free and open source WordPress plugins dedicated to observability and operations performance.

**APCu Manager** is a free and open source plugin for WordPress. It integrates many other free and open source works (as-is or modified). Please, see 'about' tab in the plugin settings to see the details.

= Support =

This plugin is free and provided without warranty of any kind. Use it at your own risk, I'm not responsible for any improper use of this plugin, nor for any damage it might cause to your site. Always backup all your data before installing a new plugin.

Anyway, I'll be glad to help you if you encounter issues when using this plugin. Just use the support section of this plugin page.

= Privacy =

This plugin, as any piece of software, is neither compliant nor non-compliant with privacy laws and regulations. It is your responsibility to use it with respect for the personal data of your users and applicable laws.

This plugin doesn't set any cookie in the user's browser.

This plugin doesn't handle personally identifiable information (PII).

= Donation =

If you like this plugin or find it useful and want to thank me for the work done, please consider making a donation to [La Quadrature Du Net](https://www.laquadrature.net/en) or the [Electronic Frontier Foundation](https://www.eff.org/) which are advocacy groups defending the rights and freedoms of citizens on the Internet. By supporting them, you help the daily actions they perform to defend our fundamental freedoms!

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'.
2. Search for 'APCu Manager'.
3. Click on the 'Install Now' button.
4. Activate APCu Manager.

= From WordPress.org =

1. Download APCu Manager.
2. Upload the `apcu-manager` directory to your `/wp-content/plugins/` directory, using your favorite method (ftp, sftp, scp, etc...).
3. Activate APCu Manager from your Plugins page.

= Once Activated =

1. Visit 'PerfOps One > Control Center > APCu Manager' in the left-hand menu of your WP Admin to adjust settings.
2. Enjoy!

== Frequently Asked Questions ==

= What are the requirements for this plugin to work? =

You need at least **WordPress 5.2** and **PHP 7.2**.

You need APCu enabled on your server.

= Can this plugin work on multisite? =

Yes. It is designed to work on multisite too. Network Admins can configure the plugin, use management tools and have access to analytics reports. Sites Admins have only access to analytics reports.

= What are the requirements for statistics to work? =

You need to have a fully operational WordPress cron. If you've set an external cron (crontab, online cron, etc.), its frequency must be less than 5 minutes - 1 or 2 minutes is a recommended value.

= Where can I get support? =

Support is provided via the official [WordPress page](https://wordpress.org/support/plugin/apcu-manager/).

= Where can I report a bug? =
 
You can report bugs and suggest ideas via the [GitHub issue tracker](https://github.com/Pierre-Lannoy/wp-apcu-manager/issues) of the plugin.

== Changelog ==

Please, see [full changelog](https://perfops.one/apcu-manager-changelog).

== Upgrade Notice ==

== Screenshots ==

1. Daily Statistics
2. Management Tools
3. Object Inspection
4. Quick Action in Admin Bar