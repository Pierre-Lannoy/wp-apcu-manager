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
use APCuManager\System\Logger;
use APCuManager\Plugin\Feature\Schema;

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
	public static $delta = 59;

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
	private static function get_details() {
		$schema  = new Schema();
		$result  = [];
		$details = [];
		if ( function_exists( 'apcu_cache_info' ) ) {
			$infos = apcu_cache_info( false );
			if ( array_key_exists( 'cache_list', $infos ) ) {
				foreach ( $infos['cache_list'] as $item ) {
					$name = '';
					if ( false !== strpos( $item['info'], '_' ) ) {
						$name = substr( $item['info'], 0, strpos( $item['info'], '_' ) );
					}
					if ( '' === $name ) {
						$name = '-';
					}
					if ( array_key_exists( $name, $details ) ) {
						$details[ $name ]['items'] = $details[ $name ]['items'] + 1;
						$details[ $name ]['size']  = $details[ $name ]['size'] + (int) $item['mem_size'];
					} else {
						$details[ $name ]['items'] = 1;
						$details[ $name ]['size']  = (int) $item['mem_size'];
					}
				}
			}
			if ( 20 > count( $details ) ) {
				foreach ( $details as $key => $detail ) {
					$d          = $schema->init_detail();
					$d['id']    = $key;
					$d['items'] = $detail['items'];
					$d['size']  = $detail['size'];
					$result[]   = $d;
				}
			}
		}
		return $result;
	}

	/**
	 * Check status and record it if needed.
	 *
	 * @since    1.0.0
	 */
	public static function check() {
		$schema = new Schema();
		$record = $schema->init_record();
		$time   = time();
		if ( function_exists( 'apcu_cache_info' ) && function_exists( 'apcu_sma_info' ) ) {
			$details  = self::get_details();
			$cache_id = '/Data/LastCheck';
			$old      = Cache::get_global( $cache_id );
			if ( ! isset( $old ) ) {
				Logger::debug( 'No APCu transient.' );
			} elseif ( ! array_key_exists( 'timestamp', $old ) ) {
				Logger::debug( 'No APCu timestamp.' );
			} elseif ( 300 - self::$delta > $time - $old['timestamp'] ) {
				Logger::debug( sprintf( 'Delta time too short: %d sec. Launching recycling process.', $time - $old['timestamp'] ) );
			} elseif ( 300 + self::$delta < $time - $old['timestamp'] ) {
				Logger::debug( sprintf( 'Delta time too long: %d sec. Launching recycling process.', $time - $old['timestamp'] ) );
			}
			if ( isset( $old ) && array_key_exists( 'timestamp', $old ) && ( 300 + self::$delta > $time - $old['timestamp'] ) ) {
				try {
					$memory             = apcu_sma_info( false );
					$value['raw']       = apcu_cache_info( true );
					$value['timestamp'] = $time;
					$record['status']   = 'enabled';
					$record['delta']    = $time - $old['timestamp'];
					if ( array_key_exists( 'num_seg', $memory ) && array_key_exists( 'seg_size', $memory ) ) {
						$record['mem_total'] = (int) ( $memory['num_seg'] * $memory['seg_size'] );
					}
					if ( array_key_exists( 'avail_mem', $memory ) ) {
						$record['mem_used'] = (int) ( $record['mem_total'] - $memory['avail_mem'] );
					}
					if ( array_key_exists( 'num_slots', $value['raw'] ) ) {
						$record['slot_total'] = (int) $value['raw']['num_slots'];
					}
					if ( array_key_exists( 'num_entries', $value['raw'] ) ) {
						$record['slot_used'] = (int) $value['raw']['num_entries'];
					}
					if ( array_key_exists( 'num_hits', $value['raw'] ) && array_key_exists( 'num_hits', $old['raw'] ) ) {
						$record['hit'] = (int) $value['raw']['num_hits'] - (int) $old['raw']['num_hits'];
					}
					if ( array_key_exists( 'num_misses', $value['raw'] ) && array_key_exists( 'num_misses', $old['raw'] ) ) {
						$record['miss'] = (int) $value['raw']['num_misses'] - (int) $old['raw']['num_misses'];
					}
					if ( array_key_exists( 'num_inserts', $value['raw'] ) && array_key_exists( 'num_inserts', $old['raw'] ) ) {
						$record['ins'] = (int) $value['raw']['num_inserts'] - (int) $old['raw']['num_inserts'];
					}
					if ( array_key_exists( 'block_lists', $memory ) && is_array( $memory['block_lists'] ) ) {
						foreach ( $memory['block_lists'] as $chunk ) {
							if ( is_array( $chunk ) ) {
								foreach ( $chunk as $block ) {
									if ( array_key_exists( 'size', $block ) ) {
										$record['frag_count'] = $record['frag_count'] + 1;
										/* Like apc.php, only consider blocks <5M for the fragmentation % */
										if ( $block['size'] < ( 5 * 1024 * 1024 ) ) {
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
					Logger::debug( 'APCu is enabled. Statistics recorded.' );
				} catch ( \Throwable $e ) {
					Logger::error( sprintf( 'Unable to query APCu status: %s.', $e->getMessage() ), $e->getCode() );
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
					Logger::debug( 'APCu is enabled. Recovery cycle.' );
				} catch ( \Throwable $e ) {
					Logger::error( sprintf( 'Unable to query APCu status: %s.', $e->getMessage() ), $e->getCode() );
				}
			}
		} else {
			$schema->write_statistics_record_to_database( $record );
			Logger::debug( 'APCu is disabled. No statistics to record.' );
		}
	}

}
