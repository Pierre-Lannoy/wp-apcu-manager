<?php
/**
 * APCuManager garbage collector
 *
 * Handles all gc operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace APCuManager\Plugin\Feature;

use APCuManager\System\Cache;

use APCuManager\Plugin\Feature\Schema;

/**
 * Define the gc functionality.
 *
 * Handles all gc operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class GC {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Add a new 5 minutes interval capacity to the WP cron feature.
	 *
	 * @since 1.0.0
	 */
	public static function add_cron_15_minutes_interval( $schedules ) {
		$schedules['fifteen_minutes'] = [
			'interval' => 900,
			'display'  => __( 'Every fifteen minutes', 'apcu-manager' ),
		];
		return $schedules;
	}

	/**
	 * Execute pro-active GC.
	 *
	 * @since    1.0.0
	 */
	public static function do() {
		$span = \DecaLog\Engine::tracesLogger( APCM_SLUG )->startSpan( 'Garbage collection', DECALOG_SPAN_MAIN_RUN );
		if ( function_exists( 'apcu_cache_info' ) && function_exists( 'apcu_delete' ) ) {
			try {
				$infos = apcu_cache_info( false );
				$cpt   = 0;
				if ( array_key_exists( 'cache_list', $infos ) ) {
					foreach ( $infos['cache_list'] as $script ) {
						if ( time() > $script['mtime'] + $script['ttl'] ) {
							apcu_delete( $script['info'] );
							$cpt++;
						}
					}
				}
				if ( 0 !== $cpt ) {
					\DecaLog\Engine::eventsLogger( APCM_SLUG )->info( sprintf( '%d out of date object(s) deleted.', $cpt ) );
				}
			} catch ( \Throwable $e ) {
				\DecaLog\Engine::eventsLogger( APCM_SLUG )->error( sprintf( 'Unable to query APCu status: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
			}
		}
		\DecaLog\Engine::tracesLogger( APCM_SLUG )->endSpan( $span );
	}

}
