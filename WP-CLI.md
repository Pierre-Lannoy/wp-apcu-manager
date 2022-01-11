APCu Manager is fully usable from command-line, thanks to [WP-CLI](https://wp-cli.org/). You can set APCu Manager options and much more, without using a web browser.

1. [Obtaining statistics about APCu usage](#obtaining-statistics-about-apcu-usage) - `wp apcu analytics`
2. [Getting APCu Manager status](#getting-apcu-manager-status) - `wp apcu status`
3. [Managing main settings](#managing-main-settings) - `wp apcu settings`
4. [Misc flags](#misc-flags)

## Obtaining statistics about APCu usage

You can get APCu analytics for today (compared with yesterday). To do that, use the `wp apcu analytics` command.

By default, the outputted format is a simple table. If you want to customize the format, just use `--format=<format>`. Note if you choose `json` or `yaml` as format, the output will contain full data and metadata for the current day.

### Examples

To display APCu analytics, type the following command:
```console
pierre@dev:~$ wp apcu analytics
+--------------+-----------------------------------------------+-------+--------+-----------+
| kpi          | description                                   | value | ratio  | variation |
+--------------+-----------------------------------------------+-------+--------+-----------+
| Hits         | Successful calls to the cache.                | 11K   | 81.51% | +0.68%    |
| Free memory  | Free memory available for APCu.               | 32MB  | 99.53% | +0.18%    |
| Keys         | Keys allocated by APCu.                       | 152   | 3.7%   | -0.38%    |
| Small blocks | Used memory small blocks (size < 5K).         | 29    | 0.38%  | +90.31%   |
| Availability | Extrapolated availability time over 24 hours. | 24 hr | 100%   | 0%        |
| Objects      | Objects currently present in cache.           | 152   | -      | -0.38%    |
+--------------+-----------------------------------------------+-------+--------+-----------+
```

## Getting APCu Manager status

To get detailed status and operation mode, use the `wp apcu status` command.

> Note this command may tell you APCu is not activated for command-line even if it's available for WordPress itself. It is due to the fact that PHP configuration is often different between command-line and web server.
>
> Nevertheless, if APCu is available for WordPress, other APCu Manager commands are operational.

## Managing main settings

To toggle on/off main settings, use `wp apcu settings <enable|disable> <object-caching|gc|analytics|metrics>`.

If you try to disable a setting, wp-cli will ask you to confirm. To force answer to yes without prompting, just use `--yes`.

### Available settings

- `object-caching`: WordPress object caching feature
- `gc`: garbage collection feature
- `analytics`: analytics feature
- `metrics`: metrics collation

### Example

To disable garbage collection without confirmation prompt, type the following command:
```console
pierre@dev:~$ wp apcu settings disable gc --yes
Success: garbage collection is now deactivated.
```

## Misc flags

For most commands, APCu Manager lets you use the following flags:
- `--yes`: automatically answer "yes" when a question is prompted during the command execution.
- `--stdout`: outputs a clean STDOUT string so you can pipe or store result of command execution.

> It's not mandatory to use `--stdout` when using `--format=count` or `--format=ids`: in such cases `--stdout` is assumed.

> Note APCu Manager sets exit code so you can use `$?` to write scripts.
> To know the meaning of APCu Manager exit codes, just use the command `wp apcu exitcode list`.
