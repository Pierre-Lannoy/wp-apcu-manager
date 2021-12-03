This plugin has a number of hooks that you can use, as developer or as a user, to customize the user experience or to give access to extended functionalities.

## Customization of analytics and reports
By default, the PerfOps One plugins can identify a low number of third-party plugins in reports. If a third-party plugin is not identified, reports will reference it according to its slug only.

If you want to obtain cleaner reports and analytics, you can "teach" PerfOps One plugins how to name these third-party plugins with the `perfopsone_plugin_info` filter.

### Example
Add "W3 Total Cache" to the named plugins:
```php
  add_filter(
    'perfopsone_plugin_info',
    function( $plugins ) {
      $plugins['w3tc'] = [ 'name' => 'W3 Total Cache' ];
      return $plugins;
    },
  );
```

## Customization of PerfOps One menus
You can use the `poo_hide_main_menu` filter to completely hide the main PerfOps One menu or use the `poo_hide_analytics_menu`, `poo_hide_consoles_menu`, `poo_hide_insights_menu`, `poo_hide_tools_menu`, `poo_hide_records_menu` and `poo_hide_settings_menu` filters to selectively hide submenus.

### Example
Hide the main menu:
```php
  add_filter( 'poo_hide_main_menu', '__return_true' );
```

## Customization of the admin bar
You can use the `poo_hide_adminbar` filter to completely hide this plugin's item(s) from the admin bar.

### Example
Remove this plugin's item(s) from the admin bar:
```php
  add_filter( 'poo_hide_adminbar', '__return_true' );
```

## Advanced settings and controls
By default, advanced settings and controls are hidden to avoid cluttering admin screens. Nevertheless, if this plugin have such settings and controls, you can force them to display with `perfopsone_show_advanced` filter.

### Example
Display advanced settings and controls in admin screens:
```php
  add_filter( 'perfopsone_show_advanced', '__return_true' );
```