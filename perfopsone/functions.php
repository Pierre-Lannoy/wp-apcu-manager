<?php
/**
 * Global functions for Perfops One features.
 *
 * @package @package PerfOpsOne
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

global $wp_version;

if ( version_compare( $wp_version, '5.5', '<' ) ) {
	/**
	 * Provide WP 5.5 compatibility for wp_is_auto_update_enabled_for_type() function.
	 */
	function wp_is_auto_update_enabled_for_type( $type ) {
		return false;
	}
}

if ( ! function_exists( 'poo_switch_autoupdate_callback' ) ) {
	/**
	 * Ajax callback for autoupdate switching.
	 *
	 * @since    2.0.0
	 */
	function poo_switch_autoupdate_callback() {
		//wp_die( 200 );
		//check_ajax_referer( 'ajax_poo_switch_autoupdate', 'nonce' );
		/*$analytics = self::get_analytics( true );
		$query     = filter_input( INPUT_POST, 'query' );
		$queried   = filter_input( INPUT_POST, 'queried' );*/
		exit( wp_json_encode( ['result'=>'ok'] ) );
	}
	add_action( 'wp_ajax_poo_switch_autoupdate', 'poo_switch_autoupdate_callback' );
}
