APCu Manager is fully usable from command-line, thanks to [WP-CLI](https://wp-cli.org/). You can set APCu Manager options and much more, without using a web browser.

1. [Getting APCu Manager status](#getting-apcu-manager-status)

## Getting APCu Manager status

To get detailed status and operation mode, use the following command:

```bash
wp log status
```

> Note this command can tell you APCu is not activated for command-line even if it's available for WordPress itself. It is due to the fact that PHP configuration is often different between command-line and web server.
>
> Nevertheless, if APCu is available for WordPress, other APCu Manager commands are operational.