<?php
/**
 * WP-CLI for APCu Manager.
 *
 * Adds WP-CLI commands to APCu Manager
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace APCuManager\Plugin\Feature;

use APCuManager\System\APCu;
use APCuManager\System\Environment;
use APCuManager\System\Option;
use APCuManager\Plugin\Feature\Analytics;
use APCuManager\System\Markdown;

/**
 * WP-CLI for APCu Manager.
 *
 * Defines methods and properties for WP-CLI commands.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class Wpcli {

	/**
	 * Get APCu Manager details and operation modes.
	 *
	 * ## EXAMPLES
	 *
	 * wp apcu status
	 *
	 *
	 *     === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-apcu-manager/blob/master/WP-CLI.md ===
	 *
	 */
	public static function status( $args, $assoc_args ) {
		\WP_CLI::line( sprintf( '%s is running.', Environment::plugin_version_text() ) );
		$name = APCu::name();
		if ( '' === $name ) {
			\WP_CLI::line( 'APCu is not activated for command-line.' );
		} else {
			\WP_CLI::line( sprintf( '%s is activated for command-line.', $name ) );
		}
		if ( Option::network_get( 'gc' ) ) {
			\WP_CLI::line( 'Garbage collector: enabled.' );
		} else {
			\WP_CLI::line( 'Garbage collector: disabled.' );
		}
		if ( Option::network_get( 'analytics' ) ) {
			\WP_CLI::line( 'Analytics: enabled.' );
		} else {
			\WP_CLI::line( 'Analytics: disabled.' );
		}
		if ( defined( 'DECALOG_VERSION' ) ) {
			\WP_CLI::line( 'Logging support: yes (DecaLog v' . DECALOG_VERSION . ').');
		} else {
			\WP_CLI::line( 'Logging support: no.' );
		}
	}

	/**
	 * Modify APCu Manager main settings.
	 *
	 * <enable|disable>
	 * : The action to take.
	 *
	 * <gc|analytics>
	 * : The setting to change.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message, if any.
	 *
	 * ## EXAMPLES
	 *
	 * wp apcu settings enable analytics
	 * wp apcu settings disable gc --yes
	 *
	 *
	 *     === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-apcu-manager/blob/master/WP-CLI.md ===
	 *
	 */
	public static function settings( $args, $assoc_args ) {
		$action  = isset( $args[0] ) ? (string) $args[0] : '';
		$setting = isset( $args[1] ) ? (string) $args[1] : '';
		switch ( $action ) {
			case 'enable':
				switch ( $setting ) {
					case 'analytics':
						Option::network_set( 'analytics', true );
						\WP_CLI::success( 'analytics are now activated.' );
						break;
					case 'gc':
						Option::network_set( 'gc', true );
						\WP_CLI::success( 'garbage collection is now activated.' );
						break;
					default:
						\WP_CLI::error( 'unrecognized setting.' );
				}
				break;
			case 'disable':
				switch ( $setting ) {
					case 'analytics':
						\WP_CLI::confirm( 'Are you sure you want to deactivate analytics?', $assoc_args );
						Option::network_set( 'analytics', false );
						\WP_CLI::success( 'analytics are now deactivated.' );
						break;
					case 'gc':
						\WP_CLI::confirm( 'Are you sure you want to deactivate garbage collection?', $assoc_args );
						Option::network_set( 'gc', false );
						\WP_CLI::success( 'garbage collection is now deactivated.' );
						break;
					default:
						\WP_CLI::error( 'unrecognized setting.' );
				}
				break;
			default:
				\WP_CLI::error( 'unrecognized action.' );
		}
	}

	/**
	 * Get APCu analytics for today.
	 *
	 * [--format=<format>]
	 * : Set the output format. Note if json is chosen: full metadata is outputted too.
	 * ---
	 * default: table
	 * options:
	 *  - table
	 *  - json
	 *  - csv
	 *  - yaml
	 *  - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 * wp apcu analytics
	 *
	 *
	 *    === For other examples and recipes, visit https://github.com/Pierre-Lannoy/wp-apcu-manager/blob/master/WP-CLI.md ===
	 *
	 */
	public static function analytics( $args, $assoc_args ) {
		if ( ! Option::network_get( 'analytics' ) ) {
			\WP_CLI::error( 'analytics are disabled.' );
		}
		$analytics = Analytics::get_status_kpi_collection();
		$result    = [];
		if ( array_key_exists( 'data', $analytics ) ) {
			foreach ( $analytics['data'] as $kpi ) {
				$item                = [];
				$item['kpi']         = $kpi['name'];
				$item['description'] = $kpi['description'];
				$item['value']       = $kpi['value']['human'];
				if ( array_key_exists( 'ratio', $kpi ) && isset( $kpi['ratio'] ) ) {
					$item['ratio'] = $kpi['ratio']['percent'] . '%';
				} else {
					$item['ratio'] = '-';
				}
				$item['variation'] = ( 0 < $kpi['variation']['percent'] ? '+' : '' ) . (string) $kpi['variation']['percent'] . '%';
				$result[]          = $item;
			}
		}
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';
		if ( 'json' === $format ) {
			\WP_CLI::line( wp_json_encode( $analytics ) );
		} else {
			\WP_CLI\Utils\format_items( $assoc_args['format'], $result, [ 'kpi', 'description', 'value', 'ratio', 'variation' ] );
		}
	}

	/**
	 * Get the WP-CLI help file.
	 *
	 * @param   array $attributes  'style' => 'markdown', 'html'.
	 *                             'mode'  => 'raw', 'clean'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public static function sc_get_helpfile( $attributes ) {
		$md = new Markdown();
		return $md->get_shortcode(  'WP-CLI.md', $attributes  );
	}

}

add_shortcode( 'apcm-wpcli', [ 'APCuManager\Plugin\Feature\Wpcli', 'sc_get_helpfile' ] );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'apcu status', [ Wpcli::class, 'status' ] );
	\WP_CLI::add_command( 'apcu analytics', [ Wpcli::class, 'analytics' ] );
	\WP_CLI::add_command( 'apcu settings', [ Wpcli::class, 'settings' ] );
}