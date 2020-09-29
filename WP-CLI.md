APCu Manager is fully usable from command-line, thanks to [WP-CLI](https://wp-cli.org/). You can set APCu Manager options and much more, without using a web browser.

1. [Obtaining statistics about APCu usage](#obtaining-statistics-about-apcu-usage) - `wp apcu analytics`
2. [Getting APCu Manager status](#getting-apcu-manager-status) - `wp apcu status`
3. [Managing main settings](#managing-main-settings) - `wp apcu settings`

## Obtaining statistics about APCu usage

You can get APCu analytics for today (compared with yesterday). To do that, use the `wp apcu analytics` command.

By default, the outputted format is a simple table. If you want to customize the format, just use `--format=<format>`. Note if you choose `json` as format, the output will contain full data and metadata for the current day.

## Getting APCu Manager status

To get detailed status and operation mode, use the `wp apcu status` command.

> Note this command may tell you APCu is not activated for command-line even if it's available for WordPress itself. It is due to the fact that PHP configuration is often different between command-line and web server.
>
> Nevertheless, if APCu is available for WordPress, other APCu Manager commands are operational.

## Managing main settings

To toggle on/off main settings, use `wp apcu settings <enable|disable> <gc|analytics>`.

If you try to disable a setting, wp-cli will ask you to confirm. To force answer to yes without prompting, just use `--yes`.

### Available settings

- `gc`: garbage collection feature
- `analytics`: analytics feature

### Example

To disable garbage collection without confirmation prompt, type the following command:
```console
pierre@dev:~$ wp apcu settings disable gc --yes
Success: garbage collection is now deactivated.
```
