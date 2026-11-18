<?php
/**
 * APCuManager capture
 *
 * Handles all captures operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace APCuManager\Plugin\Feature;

use APCuManager\System\Cache;
use APCuManager\Plugin\Feature\Schema;
use APCuManager\System\APCu;
use APCuManager\System\Option;

if ( ! defined( 'APCU_ITERATOR_DETAIL_CHUNCK_SIZE' ) ) {
	define( 'APCU_ITERATOR_DETAIL_CHUNCK_SIZE', 10000 );
}

/**
 * Define the captures functionality.
 *
 * Handles all captures operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Capture {

	/**
	 * Delta time.
	 *
	 * @since  1.0.0
	 * @var    integer    $delta    The authorized delta time in seconds.
	 */
	public static $delta = 90;

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
	public static function add_cron_05_minutes_interval( $schedules ) {
		$schedules['five_minutes'] = [
			'interval' => 300,
			'display'  => __( 'Every five minutes', 'apcu-manager' ),
		];
		return $schedules;
	}

	/**
	 * Get the items details.
	 *
	 * @return array    The details.
	 * @since 1.0.0
	 */
	public static function get_details() {
		$ids      = apply_filters( 'perfopsone_apcu_info', [ 'w3tc' => 'W3 Total Cache', 'wordpress' => 'WordPress' ] );
		$schema   = new Schema();
		$result   = [];
		$details  = [];
		$prefixes = APCu::get_prefixes();
		if ( ! ( function_exists( 'apcu_enabled' ) && apcu_enabled() && class_exists( '\APCUIterator' ) ) ) {
			return $result;
		}
		foreach ( $ids as $k => $id ) {
			foreach ( $prefixes as $prefix ) {
				foreach ( new \APCUIterator( '/' . preg_quote( $k . $prefix, '/' ) . '/', APC_ITER_MEM_SIZE, APCU_ITERATOR_DETAIL_CHUNCK_SIZE, APC_LIST_ACTIVE ) as $object ) {
					if ( array_key_exists( $k, $details ) ) {
						$details[ $k ]['items'] = $details[ $k ]['items'] + 1;
						$details[ $k ]['size']  = $details[ $k ]['size'] + (int) $object['mem_size'];
					} else {
						$details[ $k ]['items'] = 1;
						$details[ $k ]['size']  = (int) $object['mem_size'];
					}
				}
			}
		}
		foreach ( $details as $key => $detail ) {
			$d          = $schema->init_detail();
			$d['id']    = $key;
			$d['items'] = $detail['items'];
			$d['size']  = $detail['size'];
			$result[]   = $d;
		}
		return $result;
	}

	/**
	 * Publish metrics.
	 *
	 * @since    2.4.0
	 */
	public static function metrics() {
		if ( function_exists( 'apcu_cache_info' ) && function_exists( 'apcu_sma_info' ) ) {
			$span     = \DecaLog\Engine::tracesLogger( APCM_SLUG )->startSpan( 'APCu metrics collation', DECALOG_SPAN_PLUGINS_LOAD );
			$cache_id = 'metrics/lastcheck';
			$metrics  = Cache::get_global( $cache_id );
			if ( ! isset( $metrics ) ) {
				try {
					$memory  = apcu_sma_info( false );
					$value   = apcu_cache_info( true );
					$metrics = [];
					if ( is_array( $memory ) && array_key_exists( 'num_seg', $memory ) && array_key_exists( 'seg_size', $memory ) && array_key_exists( 'mem_size', $memory ) ) {
						$mem_total = (int) ( $memory['num_seg'] * $memory['seg_size'] );
						$mem_used  = (int) $memory['mem_size'];
						if ( 0 < $mem_total ) {
							$metrics['mem'] = $mem_used / $mem_total;
						} else {
							$metrics['mem'] = 0.0;
						}
					}
					if ( is_array( $value ) && array_key_exists( 'num_slots', $value ) && array_key_exists( 'num_entries', $value ) ) {
						$total = (int) $value['num_slots'];
						$used  = (int) $value['num_entries'];
						if ( 0 < $total ) {
							$metrics['slot'] = $used / $total;
						} else {
							$metrics['slot'] = 0.0;
						}
					}
					if ( is_array( $value ) && array_key_exists( 'num_hits', $value ) && array_key_exists( 'num_misses', $value ) && array_key_exists( 'num_inserts', $value ) ) {
						$hit  = (int) $value['num_hits'];
						$miss = (int) $value['num_misses'];
						$ins  = (int) $value['num_inserts'];
						if ( 0 < $hit + $miss ) {
							$metrics['hit_ratio'] = $hit / ( $hit + $miss );
						} else {
							$metrics['hit_ratio'] = 0.0;
						}
						if ( 0 < $hit + $miss + $ins ) {
							$metrics['ins_ratio'] = $ins / ( $hit + $miss + $ins );
						} else {
							$metrics['ins_ratio'] = 0.0;
						}
					}
					if ( is_array( $memory ) && array_key_exists( 'block_lists', $memory ) && is_array( $memory['block_lists'] ) ) {
						$frag_small = 0;
						$frag_big   = 0;
						foreach ( $memory['block_lists'] as $chunk ) {
							if ( is_array( $chunk ) ) {
								foreach ( $chunk as $block ) {
									if ( array_key_exists( 'size', $block ) ) {
										if ( $block['size'] < ( 5 * 1024 ) ) {
											$frag_small = ( (int) $block['size'] ) + $frag_small;
										} else {
											$frag_big = ( (int) $block['size'] ) + $frag_big;
										}
									}
								}
							}
						}
						if ( 0 < $frag_small + $frag_big ) {
							$metrics['frag'] = $frag_small / ( $frag_small + $frag_big );
						} else {
							$metrics['frag'] = 0.0;
						}
					}
					Cache::set_global( $cache_id, $metrics, 'metrics' );
					\DecaLog\Engine::eventsLogger( APCM_SLUG )->debug( 'APCu is enabled. Statistics recorded.' );
				} catch ( \Throwable $e ) {
					$metrics = null;
					\DecaLog\Engine::eventsLogger( APCM_SLUG )->error( sprintf( 'Unable to query APCu status: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
				}
			}
			if ( isset( $metrics ) ) {
				$monitor = \DecaLog\Engine::metricsLogger( APCM_SLUG );
				if ( array_key_exists( 'mem', $metrics ) ) {
					$monitor->createProdGauge( 'memory_saturation', $metrics['mem'], 'APCu used memory - [percent]' );
				}
				if ( array_key_exists( 'frag', $metrics ) ) {
					$monitor->createProdGauge( 'memory_fragmentation', $metrics['frag'], 'APCu fragmented memory - [percent]' );
				}
				if ( array_key_exists( 'slot', $metrics ) ) {
					$monitor->createProdGauge( 'key_saturation', $metrics['slot'], 'APCu used keys - [percent]' );
				}
				if ( array_key_exists( 'hit_ratio', $metrics ) ) {
					$monitor->createProdGauge( 'hit_ratio', $metrics['hit_ratio'], 'APCu hit ratio - [percent]' );
				}
				if ( array_key_exists( 'ins_ratio', $metrics ) ) {
					$monitor->createProdGauge( 'insert_ratio', $metrics['ins_ratio'], 'APCu insert ratio - [percent]' );
				}
			}
			\DecaLog\Engine::tracesLogger( APCM_SLUG )->endSpan( $span );
		} else {
			\DecaLog\Engine::eventsLogger( APCM_SLUG )->debug( 'APCu is disabled. No metrics to collate.' );
		}
	}

	/**
	 * Check status and record it if needed.
	 *
	 * @since    1.0.0
	 */
	public static function check() {
		// APCu is shared across the whole FPM pool. On a network, only capture on the
		// main site; on a standard install is_main_site() is always true, so this is a no-op.
		if ( is_multisite() && ! is_main_site() ) {
			return;
		}

		// In CLI the local APCu segment is empty/separate, so bounce the capture back
		// into FPM by hitting the REST door on the main site, then bail out.
		if ( PHP_SAPI === 'cli' ) {
			$blog_id = function_exists( 'get_main_site_id' ) ? get_main_site_id() : null;
			$url	 = add_query_arg(
				'secret',
				self::get_secret_or_create(),
				get_rest_url( $blog_id, 'apcu/v1/capture' )
			);
			wp_remote_get( $url, [
				'timeout'   => 20,
				'sslverify' => apply_filters( 'apcm_proxy_sslverify', true ),
				'blocking'  => true, // нужен ответ, чтобы запрос реально ушёл до конца процесса
			] );
			return;
		}
		$schema = new Schema();
		$record = $schema->init_record();
		$time   = time();
		if ( function_exists( 'apcu_cache_info' ) && function_exists( 'apcu_sma_info' ) ) {
			$details  = self::get_details();
			$cache_id = 'data/lastcheck';
			$old      = Cache::get_global( $cache_id );
			if ( ! isset( $old ) ) {
				\DecaLog\Engine::eventsLogger( APCM_SLUG )->debug( 'No APCu transient.' );
			} elseif ( ! array_key_exists( 'timestamp', $old ) ) {
				\DecaLog\Engine::eventsLogger( APCM_SLUG )->debug( 'No APCu timestamp.' );
			} elseif ( 300 - self::$delta > $time - $old['timestamp'] ) {
				\DecaLog\Engine::eventsLogger( APCM_SLUG )->debug( sprintf( 'Delta time too short: %d sec. Launching recycling process.', $time - $old['timestamp'] ) );
			} elseif ( 300 + self::$delta < $time - $old['timestamp'] ) {
				\DecaLog\Engine::eventsLogger( APCM_SLUG )->debug( sprintf( 'Delta time too long: %d sec. Launching recycling process.', $time - $old['timestamp'] ) );
			}
			if ( isset( $old ) && array_key_exists( 'timestamp', $old ) && ( 300 + self::$delta > $time - $old['timestamp'] ) ) {
				try {
					$memory             = apcu_sma_info( false );
					$value['raw']       = apcu_cache_info( true );
					$value['timestamp'] = $time;
					$record['status']   = 'enabled';
					$record['delta']    = $time - $old['timestamp'];
					if ( is_array( $memory ) && array_key_exists( 'num_seg', $memory ) && array_key_exists( 'seg_size', $memory ) ) {
						$record['mem_total'] = (int) ( $memory['num_seg'] * $memory['seg_size'] );
					}
					if ( is_array( $value ) && array_key_exists( 'mem_size', $value['raw'] ) ) {
						$record['mem_used'] = (int) ( $value['raw']['mem_size'] );
					}
					if ( is_array( $value ) && array_key_exists( 'num_slots', $value['raw'] ) ) {
						$record['slot_total'] = (int) $value['raw']['num_slots'];
					}
					if ( is_array( $value ) && array_key_exists( 'num_entries', $value['raw'] ) ) {
						$record['slot_used'] = (int) $value['raw']['num_entries'];
					}
					if ( is_array( $value ) && array_key_exists( 'num_hits', $value['raw'] ) && array_key_exists( 'num_hits', $old['raw'] ) ) {
						$record['hit'] = (int) $value['raw']['num_hits'] - (int) $old['raw']['num_hits'];
					}
					if ( is_array( $value ) && array_key_exists( 'num_misses', $value['raw'] ) && array_key_exists( 'num_misses', $old['raw'] ) ) {
						$record['miss'] = (int) $value['raw']['num_misses'] - (int) $old['raw']['num_misses'];
					}
					if ( is_array( $value ) && array_key_exists( 'num_inserts', $value['raw'] ) && array_key_exists( 'num_inserts', $old['raw'] ) ) {
						$record['ins'] = (int) $value['raw']['num_inserts'] - (int) $old['raw']['num_inserts'];
					}
					if ( is_array( $memory ) && array_key_exists( 'block_lists', $memory ) && is_array( $memory['block_lists'] ) ) {
						foreach ( $memory['block_lists'] as $chunk ) {
							if ( is_array( $chunk ) ) {
								foreach ( $chunk as $block ) {
									if ( array_key_exists( 'size', $block ) ) {
										$record['frag_count'] = $record['frag_count'] + 1;
										if ( $block['size'] < ( 5 * 1024 ) ) {
											$record['frag_small'] = ( (int) $block['size'] ) + $record['frag_small'];
										} else {
											$record['frag_big'] = ( (int) $block['size'] ) + $record['frag_big'];
										}
									}
								}
							}
						}
					}
					Cache::set_global( $cache_id, $value, 'check' );
					$schema->write_statistics_record_to_database( $record, $details );
					\DecaLog\Engine::eventsLogger( APCM_SLUG )->debug( 'APCu is enabled. Statistics recorded.' );
				} catch ( \Throwable $e ) {
					\DecaLog\Engine::eventsLogger( APCM_SLUG )->error( sprintf( 'Unable to query APCu status: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
				}
			} elseif ( isset( $old ) && array_key_exists( 'timestamp', $old ) && ( 300 - self::$delta > $time - $old['timestamp'] ) ) {
				// What to do when delta is less than 59 sec?
			} else {
				try {
					$value['raw']       = apcu_cache_info( true );
					$value['timestamp'] = $time;
					Cache::set_global( $cache_id, $value, 'check' );
					$record['status'] = 'recycle_in_progress';
					$schema->write_statistics_record_to_database( $record, $details );
					\DecaLog\Engine::eventsLogger( APCM_SLUG )->debug( 'APCu is enabled. Recovery cycle.' );
				} catch ( \Throwable $e ) {
					\DecaLog\Engine::eventsLogger( APCM_SLUG )->error( sprintf( 'Unable to query APCu status: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
				}
			}
		} else {
			$schema->write_statistics_record_to_database( $record );
			\DecaLog\Engine::eventsLogger( APCM_SLUG )->debug( 'APCu is disabled. No statistics to record.' );
		}
	}


	/**
	 * Registers rest api route for crons running via CLI wp wron.
	 *
	 * @since 4.6.2
	 */
	public static function register_rest_api() {
		register_rest_route( 'apcu/v1', '/capture', [
			'methods'			 => 'GET',
			'permission_callback' => function ( \WP_REST_Request $r ) {
				return hash_equals( self::get_secret_or_create(), (string) $r->get_param( 'secret' ) );
			},
			'callback'			=> function () {
				// Guard again here in case cron fires apcm_apcu_stats directly on a sub-site.
				if ( ! is_multisite() || is_main_site() ) {
					do_action( APCM_CRON_STATS_NAME ); // = Capture::check(), in FPM.
				}

				return new \WP_REST_Response( null, 204 );
			},
		] );
	}

	/**
	 * Reads secret from option and creates it if not exists
	 *
	 * @return string
	 *
	 * @since 4.6.2
	 */
	private static function get_secret_or_create() {
		$option_name = 'rest_api_secret';

		$secret = Option::network_get( $option_name );

		if (! $secret) {
			$secret = wp_generate_password( 32, false );
			Option::network_set( $option_name, $secret );
		}

		return $secret;
	}
}
