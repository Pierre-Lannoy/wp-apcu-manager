<?php
/**
 * Main plugin file.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       APCu Manager
 * Plugin URI:        https://perfops.one/apcu-manager
 * Description:       APCu statistics and management right in the WordPress admin dashboard.
 * Version:           3.7.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Pierre Lannoy / PerfOps One
 * Author URI:        https://perfops.one
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Network:           true
 * Text Domain:       apcu-manager
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/system/class-option.php';
require_once __DIR__ . '/includes/system/class-environment.php';
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/includes/libraries/class-libraries.php';
require_once __DIR__ . '/includes/libraries/autoload.php';
require_once __DIR__ . '/includes/features/class-wpcli.php';

/**
 * Copy the file responsible to early initialization in drop-ins dir.
 *
 * @since 3.0.0
 */
function apcm_check_earlyloading() {
	if ( (bool) get_site_option( 'apcm_earlyloading', false ) ) {
		if ( defined( 'APCM_BOOTSTRAPPED' ) && APCM_BOOTSTRAPPED ) {
			return;
		}
		if ( ! defined( 'APCM_BOOTSTRAPPED' ) ) {
			$target = WP_CONTENT_DIR . '/object-cache.php';
			$source = __DIR__ . '/assets/object-cache.php';
			if ( file_exists( $target ) && (bool) get_site_option( 'apcm_forceearlyloading', true ) ) {
				// phpcs:ignore
				@unlink( $target );
				define( 'APCM_BOOTSTRAP_ALREADY_EXISTS_REMOVED', true );
			}
			if ( ! file_exists( $target ) ) {
				if ( file_exists( $source ) ) {
					// phpcs:ignore
					@copy( $source, $target );
					// phpcs:ignore
					@chmod( $target, 0644 );
				}
				if ( ! file_exists( $target ) ) {
					define( 'APCM_BOOTSTRAP_COPY_ERROR', true );
				}
			} else {
				define( 'APCM_BOOTSTRAP_ALREADY_EXISTS_ERROR', true );
			}
		}
	} else {
		if ( defined( 'APCM_BOOTSTRAPPED' ) ) {
			$file = WP_CONTENT_DIR . '/object-cache.php';
			if ( file_exists( $file ) ) {
				// phpcs:ignore
				@unlink( $file );
				define( 'APCM_BOOTSTRAP_COPY_REMOVED', true );
			}
		}
	}
}

/**
 * Removes the file responsible to early initialization in drop-ins dir.
 *
 * @since 3.1.0
 */
function apcm_reset_earlyloading() {
	if ( defined( 'APCM_BOOTSTRAPPED' ) ) {
		$target = WP_CONTENT_DIR . '/object-cache.php';
		if ( file_exists( $target ) ) {
			// phpcs:ignore
			@unlink( $target );
		}
	}
}

/**
 * The code that runs during plugin activation.
 *
 * @since 1.0.0
 */
function apcm_activate() {
	APCuManager\Plugin\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since 1.0.0
 */
function apcm_deactivate() {
	APCuManager\Plugin\Deactivator::deactivate();
}

/**
 * The code that runs during plugin uninstallation.
 *
 * @since 1.0.0
 */
function apcm_uninstall() {
	APCuManager\Plugin\Uninstaller::uninstall();
}

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function apcm_run() {
	apcm_check_earlyloading();
	\DecaLog\Engine::initPlugin( APCM_SLUG, APCM_PRODUCT_NAME, APCM_VERSION, \APCuManager\Plugin\Core::get_base64_logo() );
	if ( wp_using_ext_object_cache() ) {
		global $wp_object_cache;
		if ( method_exists( $wp_object_cache, 'get_manager_id' ) && 'apcm' === $wp_object_cache::get_manager_id() ) {
			if ( method_exists( $wp_object_cache, 'set_events_logger' ) ) {
				$wp_object_cache::set_events_logger( \DecaLog\Engine::eventsLogger( APCM_SLUG ) );
			}
			if ( method_exists( $wp_object_cache, 'set_traces_logger' ) ) {
				$wp_object_cache::set_traces_logger( \DecaLog\Engine::tracesLogger( APCM_SLUG ) );
			}
			if ( method_exists( $wp_object_cache, 'set_metrics_logger' ) ) {
				$wp_object_cache::set_metrics_logger( \DecaLog\Engine::metricsLogger( APCM_SLUG ) );
			}
		}
	}
	\APCuManager\System\Cache::init();
	$plugin = new APCuManager\Plugin\Core();
	$plugin->run();
}

register_activation_hook( __FILE__, 'apcm_activate' );
register_deactivation_hook( __FILE__, 'apcm_deactivate' );
register_uninstall_hook( __FILE__, 'apcm_uninstall' );
apcm_run();
