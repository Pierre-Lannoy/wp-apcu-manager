This plugin has a number of hooks that you can use, as developer or as a user, to customize the user experience or to give access to extended functionalities.

## Addition of custom actions to objects in list
It is possible to add custom actions for each object displayed in the APCU Manager "Tool" section.

### Objects list view
For the list view, you can use the `apcm_objects_list_actions_for_object` and `apcm_objects_list_actions_for_ttl` filters to add (for each of the corresponding columns) an icon associated with an action (an url).

The format of the filtered value is an array of array(s). Each of the deepest array MUST contain 3 fields:

* `url`: the full url of the action to perfom. This url is opened in a new tab of the user's browser.
* `hint`: the text displayed while hovering the icon.
* `icon`: the "index" of the icon. Since APCu Manager embeds the [Feather icon library](https://feathericons.com/), you can choose any index of this library.

#### Example
To add a "clock" icon near the TTL to perform something related to this TTL:
```php
  add_filter(
    'apcm_objects_list_actions_for_ttl',
    function( $actions, $item ) {
      $actions[] = [
        'url'  => 'something to do with TTL',
        'hint' => 'Do something with this TTL',
        'icon' => 'clock',
      ];
      return $actions;
    },
    10,
    2
  );
```

### Available objects fields
Each item passed to the filter as second parameter is an array containing details about the current object. The fields are as follow:

* `oid` _string_: the full id of the cached object;
* `hit` _integer_: the number of hits on this object;
* `memory` _integer_: the size of the object (in bytes);
* `timestamp` _integer_: the unix timestamp at which the object was created;
* `used` _integer_: the unix timestamp at which the object was last accessed;
* `ttl` _integer_: the TTL of the cached object (in seconds) - note: "0" means no expiration;
* `source` _string_: the source id, as detected by APCu Manager;
* `path` _string_: the computed path;
* `object` _string_: the "short" object name.

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