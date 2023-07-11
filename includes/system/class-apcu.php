<?php
/**
 * APCu handling
 *
 * Handles all APCu operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace APCuManager\System;


use APCuManager\System\Option;
use APCuManager\System\File;

/**
 * Define the APCu functionality.
 *
 * Handles all APCu operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class APCu {

	/**
	 * The list of status.
	 *
	 * @since  1.0.0
	 * @var    array    $status    Maintains the status list.
	 */
	public static $status = [ 'disabled', 'enabled', 'recycle_in_progress' ];

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Sets APCu identification hook.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_filter( 'perfopsone_apcu_info', [ self::class, 'perfopsone_apcu_info' ] );
	}

	/**
	 * Adds APCu identification.
	 *
	 * @param array $apcu The already set identifiers.
	 * @return array The extended identifiers if needed.
	 * @since 1.0.0
	 */
	public static function perfopsone_apcu_info( $apcu ) {
		$apcu[ APCM_SLUG ] = [
			'name' => APCM_PRODUCT_NAME,
		];
		return $apcu;
	}

	/**
	 * Get name and version.
	 *
	 * @return string The name and version of the product.
	 * @since   1.0.0
	 */
	public static function name() {
		$result = '';
		if ( function_exists( 'apcu_cache_info' ) ) {
			// phpcs:ignore
			set_error_handler( null );
			// phpcs:ignore
			$info = @apcu_cache_info( false );
			// phpcs:ignore
			restore_error_handler();
			if ( is_array( $info ) && array_key_exists( 'memory_type', $info ) ) {
				$result .= 'APCu (' . $info['memory_type'] . ') ' . phpversion( 'apcu' );
			}
		}
		return $result;
	}

	/**
	 * Delete cached objects.
	 *
	 * @param   array $objects List of objects to delete.
	 * @return integer The number of deleted objects.
	 * @since   1.0.0
	 */
	public static function delete( $objects ) {
		$cpt = 0;
		if ( function_exists( 'apcu_delete' ) ) {
			foreach ( $objects as $object ) {
				if ( false !== apcu_delete( $object ) ) {
					$cpt++;
				}
			}
			\DecaLog\Engine::eventsLogger( APCM_SLUG )->info( sprintf( '%d object(s) deleted.', $cpt ) );
		}
		return $cpt;
	}

	/**
	 * Clear the cache.
	 *
	 * @since   1.0.0
	 */
	public static function reset() {
		if ( function_exists( 'apcu_cache_info' ) && function_exists( 'apcu_delete' ) ) {
			$infos    = apcu_cache_info( false );
			$prefixes = self::get_prefixes();
			if ( is_array( $infos ) && array_key_exists( 'cache_list', $infos ) && is_array( $infos['cache_list'] ) ) {
				foreach ( $infos['cache_list'] as $object ) {
					$oid = $object['info'];
					if ( 1 < strpos( $oid, '_' ) ) {
						$oid = substr( $oid, strpos( $oid, '_' ) );
						foreach ( $prefixes as $prefix ) {
							if ( 0 === strpos( $oid, $prefix ) ) {
								apcu_delete( $object['info'] );
								break;
							}
						}
					}
				}
			}
			\DecaLog\Engine::eventsLogger( APCM_SLUG )->notice( 'Cache cleared.' );
		}
	}

	/**
	 * Get the list of potential prefixes.
	 *
	 * @return array    The prefixes list.
	 * @since   3.0.0
	 */
	public static function get_prefixes() {
		$result = [ APCU_CACHE_PREFIX ];
		if ( class_exists( '\W3TC\Util_Environment' ) && method_exists( \W3TC\Util_Environment::class, 'instance_id' ) ) {
			$result[] = '_' . \W3TC\Util_Environment::instance_id() . '_';
		}
		return $result;
	}

	/**
	 * Get the detailed list of all objects.
	 *
	 * @param boolean   $self_only  Optional. Restrict the list to self objects.
	 * @return array    The detailed list.
	 * @since   3.0.0
	 */
	public static function get_all_objects( $self_only = true ) {
		$result = [];
		if ( function_exists( 'apcu_cache_info' ) ) {
			try {
				$raw      = apcu_cache_info( false );
				$prefixes = self::get_prefixes();
				if ( is_array( $raw ) && array_key_exists( 'cache_list', $raw ) ) {
					foreach ( $raw['cache_list'] as $object ) {
						$oid               = $object['info'];
						$item              = [];
						$item['oid']       = $oid;
						$item['hit']       = $object['num_hits'];
						$item['memory']    = $object['mem_size'];
						$item['timestamp'] = apcm_unix_ts( $object['mtime'] );
						$item['used']      = apcm_unix_ts( $object['access_time'] );
						$item['ttl']       = $object['ttl'];
						$is_self           = false;
						$item['source']    = '';
						$item['path']      = '';
						$oid               = str_replace( '::is_active', '/is-active', $oid );
						$oid               = str_replace( [ ':', '::' ], '/', $oid );
						if ( 1 < strpos( $oid, '_' ) ) {
							$item['source'] = substr( $oid, 0, strpos( $oid, '_' ) );
							$oid            = substr( $oid, strlen( $item['source'] ) );
							foreach ( $prefixes as $prefix ) {
								if ( 0 === strpos( $oid, $prefix ) ) {
									$oid     = substr( $oid, strlen( $prefix ) );
									$is_self = true;
									break;
								}
							}
						}
						$oid = str_replace( '_', '/', $oid );
						while ( 0 < strpos( $oid, '//' ) ) {
							$oid = str_replace( '//', '/', $oid );
						}
						while ( 0 === strpos( $oid, '/' ) ) {
							$oid = substr( $oid, 1 );
						}
						while ( 0 < strpos( $oid, '/' ) ) {
							$segment       = substr( $oid, 0, strpos( $oid, '/' ) );
							$oid           = substr( $oid, strlen( $segment ) );
							$item['path'] .= '/' . $segment;
							while ( 0 === strpos( $oid, '/' ) ) {
								$oid = substr( $oid, 1 );
							}
						}
						if ( '' === $item['path'] ) {
							$item['path'] = '/';
						}
						$item['object'] = $oid;
						if ( ! $self_only || ( $is_self && $self_only ) ) {
							$result[] = $item;
						}
					}
				}
			} catch ( \Throwable $e ) {
				\DecaLog\Engine::eventsLogger( APCM_SLUG )->error( sprintf( 'Unable to query APCu status: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
			}
		}
		return $result;
	}

}
