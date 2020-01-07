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

use APCuManager\System\Logger;
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
	 * The list of file not compilable/recompilable.
	 *
	 * @since  1.0.0
	 * @var    array    $do_not_compile    Maintains the file list.
	 */
	public static $do_not_compile = [ 'includes/plugin.php', 'includes/options.php', 'includes/misc.php', 'includes/menu.php' ];

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
	 * Get the options infos for Site Health "info" tab.
	 *
	 * @since 1.0.0
	 */
	public static function debug_info() {
		$result['product'] = [
			'label' => 'Product',
			'value' => self::name(),
		];
		if ( function_exists( 'opcache_get_configuration' ) && function_exists( 'opcache_get_status' ) ) {
			$raw = opcache_get_configuration();
			if ( array_key_exists( 'directives', $raw ) ) {
				foreach ( $raw['directives'] as $key => $directive ) {
					$result[ $key ] = [
						'label' => '[Directive] ' . str_replace( 'opcache.', '', $key ),
						'value' => $directive,
					];
				}
			}
			$raw = opcache_get_status();
			foreach ( $raw as $key => $status ) {
				if ( 'scripts' === $key ) {
					continue;
				}
				if ( is_array( $status ) ) {
					foreach ( $status as $skey => $sstatus ) {
						$result[ $skey ] = [
							'label' => '[Status] ' . $skey,
							'value' => $sstatus,
						];
					}
				} else {
					$result[ $key ] = [
						'label' => '[Status] ' . $key,
						'value' => $status,
					];
				}
			}
		} else {
			$result['product'] = [
				'label' => 'Status',
				'value' => 'Disabled',
			];
		}
		return $result;
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
			$result = 'APCu';
			$info   = apcu_cache_info( false );
			if ( array_key_exists( 'memory_type', $info ) ) {
				$result .= ' (' . $info['memory_type'] . ')';
			}
			$result .= ' ' . phpversion( 'apcu' );
		}
		return $result;
	}

	/**
	 * Invalidate files.
	 *
	 * @param   array $files List of files to invalidate.
	 * @param   boolean $force Optional. Has the invalidation to be forced.
	 * @return integer The number of invalidated files.
	 * @since   1.0.0
	 */
	public static function invalidate( $files, $force = false ) {
		$cpt = 0;
		if ( function_exists( 'opcache_invalidate' ) ) {
			foreach ( $files as $file ) {
				if ( 0 === strpos( $file, './' ) ) {
					$file = str_replace( '..', '', $file );
					$file = str_replace( './', ABSPATH, $file );
					if ( opcache_invalidate( $file, $force ) ) {
						$cpt++;
					}
				}
			}
			if ( $force ) {
				$s = 'Forced invalidation';
			} else {
				$s = 'Invalidation';
			}
			Logger::info( sprintf( '%s: %d file(s).', $s, $cpt ) );
		}
		return $cpt;
	}

	/**
	 * Recompile files.
	 *
	 * @param   array $files List of files to recompile.
	 * @param   boolean $force Optional. Has the invalidation to be forced.
	 * @return integer The number of recompiled files.
	 * @since   1.0.0
	 */
	public static function recompile( $files, $force = false ) {
		$cpt = 0;
		if ( function_exists( 'opcache_invalidate' ) && function_exists( 'opcache_compile_file' ) && function_exists( 'opcache_is_script_cached' ) ) {
			foreach ( $files as $file ) {
				if ( 0 === strpos( $file, './' ) ) {
					foreach ( self::$do_not_compile as $item ) {
						if ( false !== strpos( $file, $item ) ) {
							Logger::debug( sprintf( 'File "%s" must not be recompiled.', $file ) );
							continue 2;
						}
					}
					$file = str_replace( '..', '', $file );
					$file = str_replace( './', ABSPATH, $file );
					if ( $force ) {
						opcache_invalidate( $file, true );
					}
					if ( ! opcache_is_script_cached( $file ) ) {
						try {
							// phpcs:ignore
							if ( @opcache_compile_file( $file ) ) {
								$cpt++;
							} else {
								Logger::debug( sprintf( 'Unable to compile file "%s".', $file ) );
							}
						} catch ( \Throwable $e ) {
							Logger::debug( sprintf( 'Unable to compile file "%s": %s.', $file, $e->getMessage() ), $e->getCode() );
						}
					} else {
						Logger::debug( sprintf( 'File "%s" already cached.', $file ) );
					}
				}
			}
			Logger::info( sprintf( 'Recompilation: %d file(s).', $cpt ) );
		}
		return $cpt;
	}

	/**
	 * Reset the cache (force invalidate all).
	 *
	 * @param   boolean $automatic Optional. Is the reset automatically done (via cron, for example).
	 * @since   1.0.0
	 */
	public static function reset( $automatic = true ) {
		if ( $automatic && Option::network_get( 'warmup' ) ) {
			self::warmup( $automatic, true );
		} else {
			$files = [];
			if ( function_exists( 'opcache_get_status' ) ) {
				try {
					$raw = opcache_get_status( true );
					if ( array_key_exists( 'scripts', $raw ) ) {
						foreach ( $raw['scripts'] as $script ) {
							if ( false === strpos( $script['full_path'], ABSPATH ) ) {
								continue;
							}
							$files[] = str_replace( ABSPATH, './', $script['full_path'] );
						}
						self::invalidate( $files, true );
					}
				} catch ( \Throwable $e ) {
					Logger::error( sprintf( 'Unable to query OPcache status: %s.', $e->getMessage() ), $e->getCode() );
				}
			}
		}
	}

	/**
	 * Warm-up the site.
	 *
	 * @param   boolean $automatic Optional. Is the warmup done (via cron, for example).
	 * @param   boolean $force Optional. Has invalidation to be forced.
	 * @return integer The number of recompiled files.
	 * @since   1.0.0
	 */
	public static function warmup( $automatic = true, $force = false ) {
		$files = [];
		foreach ( File::list_files( ABSPATH, 100, [ '/^.*\.php$/i' ], [], true ) as $file ) {
			$files[] = str_replace( ABSPATH, './', $file );
		}
		if ( Environment::is_wordpress_multisite() ) {
			Logger::info( $automatic ? 'Network reset and warm-up initiated via cron.' : 'Network warm-up initiated via manual action.' );
		} else {
			Logger::info( $automatic ? 'Site reset and warm-up initiated via cron.' : 'Site warm-up initiated via manual action.' );
		}
		$result = self::recompile( $files, $force );
		if ( $automatic ) {
			Cache::set_global( '/Data/ResetWarmupTimestamp', time(), 'check' );
		} else {
			Cache::set_global( '/Data/WarmupTimestamp', time(), 'check' );
		}
		if ( Environment::is_wordpress_multisite() ) {
			Logger::info( sprintf( 'Network warm-up terminated. %d files were recompiled', $result ) );
		} else {
			Logger::info( sprintf( 'Site warm-up terminated. %d files were recompiled', $result ) );
		}
		return $result;
	}

}
