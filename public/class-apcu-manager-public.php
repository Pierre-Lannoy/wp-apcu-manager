<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace APCuManager\Plugin;

use APCuManager\System\Assets;

/**
 * The class responsible for the public-facing functionality of the plugin.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Apcu_Manager_Public {


	/**
	 * The assets manager that's responsible for handling all assets of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Assets    $assets    The plugin assets manager.
	 */
	protected $assets;

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->assets = new Assets();
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		//$this->assets->register_style( APCM_ASSETS_ID, APCM_PUBLIC_URL, 'css/apcu-manager.min.css' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		//$this->assets->register_script( APCM_ASSETS_ID, APCM_PUBLIC_URL, 'js/apcu-manager.min.js', [ 'jquery' ] );
	}

}
