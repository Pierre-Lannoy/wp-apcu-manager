<?php
/**
 * Plugin initialization handling.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace APCuManager\Plugin;

/**
 * Fired after 'plugins_loaded' hook.
 *
 * This class defines all code necessary to run during the plugin's initialization.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Initializer {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function initialize() {
		\APCuManager\System\Sitehealth::init();
		\APCuManager\System\APCu::init();
		unload_textdomain( APCM_SLUG );
		load_plugin_textdomain( APCM_SLUG );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function late_initialize() {
		require_once APCM_PLUGIN_DIR . 'perfopsone/init.php';
	}

}
