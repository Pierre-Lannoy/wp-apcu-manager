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

use APCuManager\System\APCu;
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
				$infos    = apcu_cache_info( false );
				$cpt      = 0;
				$prefixes = APCu::get_prefixes();
				$time     = time();
				if ( is_array( $infos ) && array_key_exists( 'cache_list', $infos ) ) {
					foreach ( $infos['cache_list'] as $object ) {
						$oid = $object['info'];
						if ( 1 < strpos( $oid, '_' ) ) {
							$oid = substr( $oid, strlen( substr( $oid, 0, strpos( $oid, '_' ) ) ) );
							foreach ( $prefixes as $prefix ) {
								if ( 0 === strpos( $oid, $prefix ) && 0 !== (int) $object['ttl'] ) {
									if ( $time > apcm_unix_ts( $object['mtime'] ) + $object['ttl'] ) {
										apcu_delete( $object['info'] );
										$cpt++;
										break;
									}
								}
							}
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
