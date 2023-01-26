<?php
/**
 * APCu Objects Cache Object
 *
 * Handles all APCU caching operations.
 *
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

use APCuManager\System\Option;
use APCuManager\System\Cache;
use APCuManager\System\Environment;

/**
 * Object cache class definition
 */
class WP_Object_Cache {

	/**
	 * Tracked metrics.
	 *
	 * @var     array
	 * @since   3.0.0
	 */
	private $metrics = [];

	/**
	 * Local cache.
	 *
	 * @var     array
	 * @since   3.0.0
	 */
	private $local_cache = [];

	/**
	 * Available metrics.
	 *
	 * @var     array
	 * @since   3.0.0
	 */
	private $available_metrics = [ 'add', 'dec', 'inc', 'set', 'replace', 'fetch', 'delete', 'flush' ];

	/**
	 * List of global groups.
	 *
	 * @var     array
	 * @since   3.0.0
	 */
	private $global_groups = [
		//'blog-details',
		//'blog-id-cache',
		//'blog-lookup',
		//'global-posts',
		//'networks',
		//'rss',
		//'sites',
		//'site-details',
		//'site-lookup',
		//'site-options',
		//'site-transient',
		//'users',
		//'useremail',
		//'userlogins',
		//'usermeta',
		//'user_meta',
		//'userslugs',
	];

	/**
	 * List of non persistent groups.
	 *
	 * @var     array
	 * @since   3.0.0
	 */
	private $non_persistent_groups = [
		//'counts',
		//'options',
		//'plugins',
		//'themes',
	];

	/**
	 * Non persistent cache.
	 *
	 * @var     array
	 * @since   3.0.0
	 */
	private $non_persistent_cache = [];

	/**
	 * Prefix used for all groups.
	 *
	 * @var     string
	 * @since   3.0.0
	 */
	private $cache_prefix = '';

	/**
	 * Prefix used for non-global groups.
	 *
	 * @var     string
	 * @since   3.0.0
	 */
	private $blog_prefix = 1;

	/**
	 * Is APCu really available?
	 *
	 * @var     bool
	 * @since   3.0.0
	 */
	public $apcu_available = false;

	/**
	 * Is it a multisite?
	 *
	 * @var     bool
	 * @since   3.0.0
	 */
	private $multi_site = false;

	/**
	 * The self instance.
	 *
	 * @var     \WP_Object_Cache
	 * @since   3.0.0
	 */
	private static $instance;

	/**
	 * The events logger instance.
	 *
	 * @var     \DecaLog\EventsLogger
	 * @since   3.0.0
	 */
	private static $events_logger = null;

	/**
	 * Are we in debug mode?
	 *
	 * @var     bool
	 * @since   3.0.0
	 */
	private static $debug = false;

	/**
	 * The events prefix.
	 *
	 * @var     string
	 * @since   3.0.0
	 */
	private static $events_prefix = '[WPObjectCache] ';

	/**
	 * Available metrics.
	 *
	 * @var     array
	 * @since   3.0.0
	 */
	private static $metrics_definition = [
		'add'     => 'added',
		'dec'     => 'decremented',
		'inc'     => 'incremented',
		'set'     => 'set',
		'replace' => 'replaced',
		'fetch'   => 'fetched',
		'flush'   => 'flushed',
		'delete'  => 'deleted',
	];

	/**
	 * The traces logger instance.
	 *
	 * @var     \DecaLog\TracesLogger
	 * @since   3.0.0
	 */
	private static $traces_logger = null;

	/**
	 * The metrics logger instance.
	 *
	 * @var     \DecaLog\MetricsLogger
	 * @since   3.0.0
	 */
	private static $metrics_logger = null;

	/**
	 * Get instance of WP_Object_Cache.
	 *
	 * @return  \WP_Object_Cache
	 * @since   3.0.0
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	/**
	 * Get object manager id.
	 *
	 * @since   3.1.1
	 */
	public static function get_manager_id() {
		return 'apcm';
	}

	/**
	 * Set instance of \DecaLog\TracesLogger.
	 *
	 * @param \DecaLog\EventsLogger $logger The logger to attach.
	 *
	 * @since   3.0.0
	 */
	public static function set_events_logger( $logger ) {
		if ( $logger instanceof \DecaLog\EventsLogger ) {
			self::$events_logger = $logger;
			if ( isset( self::$events_logger ) && self::$debug ) {
				self::$events_logger->debug( self::$events_prefix . 'Events logger attached.' );
			}
		}
	}

	/**
	 * Set instance of \DecaLog\TracesLogger.
	 *
	 * @param \DecaLog\TracesLogger $logger The logger to attach.
	 *
	 * @since   3.0.0
	 */
	public static function set_traces_logger( $logger ) {
		if ( $logger instanceof \DecaLog\TracesLogger ) {
			self::$traces_logger = $logger;
			if ( isset( self::$events_logger ) && self::$debug ) {
				self::$events_logger->debug( self::$events_prefix . 'Traces logger attached.' );
			}
		}
	}

	/**
	 * Set instance of \DecaLog\TracesLogger.
	 *
	 * @param \DecaLog\MetricsLogger $logger The logger to attach.
	 *
	 * @since   3.0.0
	 */
	public static function set_metrics_logger( $logger ) {
		if ( $logger instanceof \DecaLog\MetricsLogger ) {
			self::$metrics_logger = $logger;
			if ( isset( self::$events_logger ) && self::$debug ) {
				self::$events_logger->debug( self::$events_prefix . 'Metrics logger attached.' );
			}
		}
		if ( Option::network_get( 'metrics' ) && isset( self::$metrics_logger ) && ! in_array( Environment::exec_mode(), [ 1, 3, 4 ], true ) ) {
			add_action( 'shutdown', [ self::instance(), 'compute_metrics' ], DECALOG_MAX_SHUTDOWN_PRIORITY - 1, 0 );
			self::$metrics_logger->createProdGauge( 'object_cache_all_hit_ratio', 0, 'Object cache hit ratio per request, 5 min average - [percent]' );
			self::$metrics_logger->createProdGauge( 'object_cache_all_success_ratio', 0, 'Object cache success ratio per request, 5 min average - [percent]' );
			self::$metrics_logger->createProdGauge( 'object_cache_all_time', 0, 'Object cache time per request, 5 min average - [second]' );
			if ( self::$debug ) {
				self::$metrics_logger->createDevGauge( 'object_cache_all_size', 0, 'Object cache size per request, 5 min average - [byte]' );
			}
			foreach ( self::$metrics_definition as $metric => $desc ) {
				self::$metrics_logger->createProdGauge( 'object_cache_' . $metric . '_success', 0, sprintf( 'Number of successfully %s keys per request, 5 min average - [count]', $desc ) );
				self::$metrics_logger->createProdGauge( 'object_cache_' . $metric . '_fail', 0, sprintf( 'Number of unsuccessfully %s keys per request, 5 min average - [count]', $desc ) );
				self::$metrics_logger->createProdGauge( 'object_cache_' . $metric . '_time', 0, sprintf( 'Cache time for successfully %s keys per request, 5 min average - [second]', $desc ) );
				if ( self::$debug ) {
					self::$metrics_logger->createDevGauge( 'object_cache_' . $metric . '_size', 0, sprintf( 'Size of successfully %s keys per request, 5 min average - [byte]', $desc ) );
				}
			}
			if ( self::$instance->apcu_available ) {
				Cache::init();
				self::instance()->collate_metrics();
			} else {
				add_action( 'shutdown', [ self::instance(), 'collate_metrics' ], DECALOG_MAX_SHUTDOWN_PRIORITY, 0 );
			}
		}
	}

	/**
	 * Initialize the instance and set its properties.
	 *
	 * @since   3.0.0
	 */
	private function __construct() {
		global $blog_id;
		$this->cache_prefix   = '_' . md5( ABSPATH ) . '_';
		$this->apcu_available = function_exists( 'apcu_delete' ) && function_exists( 'apcu_fetch' ) && function_exists( 'apcu_store' ) && function_exists( 'apcu_add' ) && function_exists( 'apcu_dec' ) && function_exists( 'apcu_inc' );
		$this->multi_site     = is_multisite();
		$this->blog_prefix    = $this->multi_site ? $blog_id : 1;
		foreach ( $this->available_metrics as $metric ) {
			$this->metrics[ $metric ] = [];
			foreach ( [ 'success', 'fail', 'time', 'size' ] as $kpi ) {
				$this->metrics[ $metric ][ $kpi ] = 0;
			}
		}
	}

	/**
	 * Disable cloning.
	 *
	 * @since   3.0.0
	 */
	private function __clone() {
	}

	/**
	 * Disable stats.
	 *
	 * @since   3.0.0
	 */
	public function stats() {
	}

	/**
	 * Computes metrics.
	 *
	 * @since   3.0.0
	 */
	public function compute_metrics() {
		if ( isset( self::$traces_logger ) ) {
			$span = self::$traces_logger->startSpan( 'Object caching metrics computation', DECALOG_SPAN_SHUTDOWN );
		}
		$metrics = Cache::get_global( 'metrics/data' );
		$new     = [];
		$time    = time();
		if ( isset( $metrics ) && is_array( $metrics ) ) {
			foreach ( $metrics as $ts => $metric ) {
				if ( 300 >= $time - (int) $ts ) {
					$new[ $ts ] = $metric;
				}
			}
		}
		$new[ time() ] = $this->metrics;
		Cache::set_global( 'metrics/data', $new, 'infinite' );
		if ( isset( self::$traces_logger ) ) {
			self::$traces_logger->endSpan( $span );
		}
	}

	/**
	 * Collates metrics.
	 *
	 * @since   3.0.0
	 */
	public function collate_metrics() {
		if ( isset( self::$traces_logger ) ) {
			$span = self::$traces_logger->startSpan( 'Object caching metrics collation', $this->apcu_available ? DECALOG_SPAN_PLUGINS_LOAD : DECALOG_SPAN_SHUTDOWN );
		}
		$metrics = Cache::get_global( 'metrics/data' );
		if ( isset( $metrics ) && is_array( $metrics ) && 0 < count( $metrics ) ) {
			$m = [];
			foreach ( $this->available_metrics as $metric ) {
				foreach ( [ 'success', 'fail', 'time', 'size' ] as $kpi ) {
					$m[ $metric . '_' . $kpi ] = [];
				}
			}
			foreach ( $metrics as $bucket ) {
				foreach ( $this->available_metrics as $metric ) {
					if ( array_key_exists( $metric, $bucket ) ) {
						foreach ( [ 'success', 'fail', 'time', 'size' ] as $kpi ) {
							if ( array_key_exists( $kpi, $bucket[ $metric ] ) ) {
								$m[ $metric . '_' . $kpi ][] = $bucket[ $metric ][ $kpi ];
							}
						}
					}
				}
			}
			$total = [];
			foreach ( [ 'success', 'fail', 'time', 'size', 'hit', 'miss' ] as $kpi ) {
				$total[ $kpi ] = 0;
			}
			foreach ( $this->available_metrics as $metric ) {
				foreach ( [ 'success', 'fail', 'time' ] as $kpi ) {
					if ( 0 < $m[ $metric . '_' . $kpi ] ) {
						$val = array_sum( $m[ $metric . '_' . $kpi ] ) / count( $m[ $metric . '_' . $kpi ] );
						self::$metrics_logger->setProdGauge( 'object_cache_' . $metric . '_' . $kpi, $val );
						if ( 'fetch' === $metric ) {
							if ( 'success' === $kpi ) {
								$total['hit'] += $val;
							} elseif ( 'fail' === $kpi ) {
								$total['miss'] += $val;
							} else {
								$total[ $kpi ] += $val;
							}
						} else {
							$total[ $kpi ] += $val;
						}
					}
				}
				if ( self::$debug && 0 < count( $m[ $metric . '_size' ] ) ) {
					$val = array_sum( $m[ $metric . '_size' ] ) / count( $m[ $metric . '_size' ] );
					self::$metrics_logger->setDevGauge( 'object_cache_' . $metric . '_size', $val );
					$total['size'] += $val;
				}
			}
			if ( 0 < $total['hit'] + $total['miss'] ) {
				self::$metrics_logger->setProdGauge( 'object_cache_all_hit_ratio', $total['hit'] / ( $total['hit'] + $total['miss'] ) );
			}
			if ( 0 < $total['success'] + $total['fail'] ) {
				self::$metrics_logger->setProdGauge( 'object_cache_all_success_ratio', $total['success'] / ( $total['success'] + $total['fail'] ) );
			}
			self::$metrics_logger->setProdGauge( 'object_cache_all_time', $total['time'] );
			if ( self::$debug ) {
				self::$metrics_logger->setDevGauge( 'object_cache_all_size', $total['size'] );
			}
		}
		if ( isset( self::$traces_logger ) ) {
			self::$traces_logger->endSpan( $span );
		}
	}

	/**
	 * Compute a full cache key name.
	 *
	 * @param int|string $key The key.
	 * @param string $group The group.
	 * @param integer $forced_site Optional. Forces the site.
	 *
	 * @return  string  The full cache key name.
	 * @since   3.0.0
	 */
	private function full_item_name( $key, $group, $forced_site = 0 ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}
		$prefix = '';
		if ( ! in_array( (string) $group, $this->global_groups, true ) ) {
			if ( 0 === $forced_site ) {
				$prefix = $this->blog_prefix . '_';
			} else {
				$prefix = $forced_site . '_';
			}
		}
		return 'wordpress' . $this->cache_prefix . $prefix . $group . '_' . $key;
	}

	/**
	 * Compute the size of variable in APCu (so a serialized var).
	 *
	 * @param mixed $var The variable.
	 *
	 * @return  integer  The size in octets.
	 * @since   3.0.0
	 */
	private function size_of( $var ) {
		try {
			$result = strlen( serialize( $var ) );
		} catch ( \Throwable $t ) {
			$result = 0;
		}
		return $result;
	}

	/**
	 * Switch the internal blog id.
	 *
	 * @param int $blog_id The blog ID.
	 *
	 * @since   3.0.0
	 */
	public function switch_to_blog( $blog_id ) {
		$old               = $this->blog_prefix;
		$this->blog_prefix = $this->multi_site ? $blog_id : 1;
		if ( isset( self::$events_logger ) && self::$debug ) {
			self::$events_logger->debug( self::$events_prefix . sprintf( 'Switching from site %d to site %d.', $old, $this->blog_prefix ) );
		}
	}

	/**
	 * Gets the list of global groups.
	 *
	 * @return  array   The list of global groups.
	 * @since   3.0.0
	 */
	public function get_global_groups() {
		return $this->global_groups;
	}

	/**
	 * Adds a list of global groups.
	 *
	 * @param string|array $groups The list of groups to add.
	 *
	 * @since   3.0.0
	 */
	public function add_global_groups( $groups ) {
		$groups = (array) $groups;
		foreach ( $groups as $group ) {
			$this->global_groups[] = $group;
		}
		if ( isset( self::$events_logger ) && self::$debug ) {
			self::$events_logger->debug( self::$events_prefix . sprintf( '%d global group(s) added.', count( $groups ) ) );
		}
	}

	/**
	 * Gets the list of non persistent groups.
	 *
	 * @return  array   The list of groups to get.
	 * @since   3.0.0
	 */
	public function get_non_persistent_groups() {
		return $this->non_persistent_groups;
	}

	/**
	 * Adds a list of non persistent groups.
	 *
	 * @param string|array $groups The list of groups to add.
	 *
	 * @since   3.0.0
	 */
	public function add_non_persistent_groups( $groups ) {
		$groups = (array) $groups;
		foreach ( $groups as $group ) {
			$this->non_persistent_groups[] = $group;
		}
		if ( isset( self::$events_logger ) && self::$debug ) {
			self::$events_logger->debug( self::$events_prefix . sprintf( '%d non-persistent group(s) added.', count( $groups ) ) );
		}
	}

	/**
	 * Checks if the given group is a non persistent group.
	 *
	 * @param string $group The group to check.
	 *
	 * @return  bool    True if the group is a non persistent group, false otherwise.
	 * @since   3.0.0
	 */
	private function is_non_persistent_group( $group ) {
		return in_array( (string) $group, $this->non_persistent_groups, true );
	}

	/**
	 * Clears the object cache of all data.
	 *
	 * @return bool Always returns true.
	 */
	public function flush() {
		$this->non_persistent_cache = [];
		$this->partial_flush_persistent( [ 'wordpress' . $this->cache_prefix ] );
		if ( isset( self::$events_logger ) ) {
			self::$events_logger->info( self::$events_prefix . 'Full cache successfully flushed.' );
		}
		return $this->flush_runtime();
	}

	/**
	 * Clears the internal object cache of all data.
	 *
	 * @return bool Always returns true.
	 */
	public function flush_runtime() {
		$this->local_cache = [];
		return true;
	}

	/**
	 * Invalidate sites' object cache.
	 *
	 * @param string|array $sites Sites ID's that want flushing.
	 *
	 * @return bool
	 */
	public function flush_sites( $sites ) {
		$sites = (array) $sites;
		if ( ! empty( $sites ) ) {
			if ( ! in_array( 0, $sites, true ) ) {
				$sites[] = 0;
			}
			$seeds = [];
			$group = '$$$$$';
			$key   = '%%%%%';
			$id    = $group . '_' . $key;
			foreach ( $sites as $site ) {
				$seeds[] = str_replace( $id, '', $this->full_item_name( $key, $group, $site ) );
			}
			$this->partial_flush_non_persistent( $seeds );
			$cpt = $this->partial_flush_persistent( $seeds );
			if ( isset( self::$events_logger ) ) {
				self::$events_logger->info( self::$events_prefix . 'Sites cache successfully flushed.' );
			}
			return 0 !== $cpt;
		}
		return false;
	}

	/**
	 * Invalidate a group cache.
	 *
	 * @param string|array $group Group to flush.
	 *
	 * @return bool
	 */
	public function flush_group( $group ) {
		$seeds = [ $this->full_item_name( '', $group ) ];
		$this->partial_flush_non_persistent( $seeds );
		$cpt = $this->partial_flush_persistent( $seeds );
		if ( isset( self::$events_logger ) ) {
			self::$events_logger->info( self::$events_prefix . 'Group cache successfully flushed.' );
		}
		return 0 !== $cpt;
	}

	/**
	 * Remove specific data from persistent cache.
	 *
	 * @param array $seeds The seeds to remove.
	 *
	 * @return  integer     Number of removed keys.
	 */
	private function partial_flush_persistent( $seeds ) {
		$cpt = 0;
		if ( $this->apcu_available && function_exists( 'apcu_cache_info' ) ) {
			$chrono = microtime( true );
			$infos  = apcu_cache_info( false );
			if ( is_array( $infos ) && array_key_exists( 'cache_list', $infos ) && is_array( $infos['cache_list'] ) ) {
				foreach ( $infos['cache_list'] as $object ) {
					$oid = $object['info'];
					foreach ( $seeds as $prefix ) {
						if ( 0 === strpos( $oid, $prefix ) ) {
							if ( $this->delete_persistent( $object['info'], false ) ) {
								$this->metrics['flush']['success'] += 1;
								$cpt ++;
							} else {
								$this->metrics['flush']['fail'] += 1;
							}
							break;
						}
					}
				}
			}
			$this->metrics['flush']['time'] += microtime( true ) - $chrono;
		}
		if ( self::$debug ) {
			self::$events_logger->debug( self::$events_prefix . sprintf( '%d keys removed in a flush operation.', $cpt ) );
		}
		return $cpt;
	}

	/**
	 * Remove specific data from non-persistent cache.
	 *
	 * @param array $seeds The seeds to remove.
	 *
	 * @return  integer     Number of removed keys.
	 */
	private function partial_flush_non_persistent( $seeds ) {
		$cpt = 0;
		foreach ( $this->non_persistent_cache as $key => $object ) {
			foreach ( $seeds as $prefix ) {
				if ( 0 === strpos( $key, $prefix ) ) {
					unset( $this->non_persistent_cache[ $key ] );
					$cpt ++;
					break;
				}
			}
		}
		return $cpt;
	}

	/**
	 * Checks if the cached non persistent key exists.
	 *
	 * @param string $key What the contents in the cache are called.
	 *
	 * @return  bool    True if cache key exists, false otherwise.
	 */
	private function is_non_persistent_key( $key ) {
		return array_key_exists( $key, $this->non_persistent_cache );
	}

	/**
	 * Adds data to the cache, if the cache key does not already exist.
	 *
	 * @param int|string $key The cache key to use for later retrieval.
	 * @param mixed $var The data to add to the cache store.
	 * @param string $group Optional. The group to add the cache to.
	 * @param int $ttl Optional. When the cache data should be expired.
	 *
	 * @return  bool    False if cache key and group already exist, true otherwise.
	 * @since   3.0.0
	 */
	public function add( $key, $var, $group = 'default', $ttl = 0 ) {
		if ( wp_suspend_cache_addition() ) {
			return false;
		}
		$key = $this->full_item_name( $key, $group );
		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->add_non_persistent( $key, $var );
		}
		return $this->add_persistent( $key, $var, $ttl );
	}

	/**
	 * Adds data to APCu cache, if the cache key does not already exist.
	 *
	 * @param int|string $key The cache key to use for later retrieval.
	 * @param mixed $var The data to add to the cache store.
	 * @param int $ttl When the cache data should be expired.
	 *
	 * @return  bool    False if cache key and group already exist, true otherwise.
	 * @since   3.0.0
	 */
	private function add_persistent( $key, $var, $ttl ) {
		$chrono                        = microtime( true );
		$result                        = true === apcu_add( $key, $var, max( (int) $ttl, 0 ) );
		$this->metrics['add']['time'] += microtime( true ) - $chrono;
		if ( $result ) {
			$this->local_cache[ $key ] = is_object( $var ) ? clone $var : $var;
			if ( $this::$debug ) {
				$this->metrics['add']['size'] += $this->size_of( $var );
			}
			$this->metrics['add']['success'] += 1;
			if ( isset( self::$events_logger ) && self::$debug ) {
				self::$events_logger->debug( self::$events_prefix . sprintf( 'Key "%s" successfully added.', $key ) );
			}
		} else {
			$this->metrics['add']['fail'] += 1;
			if ( isset( self::$events_logger ) && self::$debug ) {
				self::$events_logger->debug( self::$events_prefix . sprintf( 'Key "%s" unsuccessfully added.', $key ) );
			}
		}
		return $result;
	}

	/**
	 * Adds data to non persistent cache, if the cache key does not already exist.
	 *
	 * @param int|string $key The cache key to use for later retrieval.
	 * @param mixed $var The data to add to the cache store.
	 *
	 * @return  bool    False if cache key and group already exist, true otherwise.
	 * @since   3.0.0
	 */
	private function add_non_persistent( $key, $var ) {
		if ( $this->is_non_persistent_key( $key ) ) {
			return false;
		}
		return $this->set_non_persistent( $key, $var );
	}

	/**
	 * Decrement numeric cache item's value.
	 *
	 * @param int|string $key The cache key to increment
	 * @param int $offset Optional. The amount by which to decrement the item's value.
	 * @param string $group Optional. The group the key is in.
	 *
	 * @return  false|int   False on failure, the item's new value on success.
	 * @since   3.0.0
	 */
	public function decr( $key, $offset = 1, $group = 'default' ) {
		$key = $this->full_item_name( $key, $group );
		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->decr_non_persistent( $key, $offset );
		}
		return $this->decr_persistent( $key, $offset );
	}

	/**
	 * Decrement numeric APCu cache item's value.
	 *
	 * @param int|string $key The cache key to increment
	 * @param int $offset The amount by which to decrement the item's value.
	 *
	 * @return  false|int   False on failure, the item's new value on success.
	 * @since   3.0.0
	 */
	private function decr_persistent( $key, $offset ) {
		$this->get_persistent( $key, $success );
		if ( ! $success ) {
			$this->metrics['dec']['fail'] += 1;
			return false;
		}
		$chrono                       = microtime( true );
		$result                       = false !== apcu_dec( $key, max( (int) $offset, 0 ) );
		$this->metrics['dec']['time'] += microtime( true ) - $chrono;
		if ( $result ) {
			$this->local_cache[ $key ] = $result;
			if ( $this::$debug ) {
				$this->metrics['dec']['size'] += $this->size_of( $offset );
			}
			$this->metrics['dec']['success'] += 1;
			if ( isset( self::$events_logger ) && self::$debug ) {
				self::$events_logger->debug( self::$events_prefix . sprintf( 'Key "%s" successfully decremented.', $key ) );
			}
		} else {
			$this->metrics['dec']['fail'] += 1;
			if ( isset( self::$events_logger ) && self::$debug ) {
				self::$events_logger->debug( self::$events_prefix . sprintf( 'Key "%s" unsuccessfully decremented.', $key ) );
			}
		}
		return $result;
	}

	/**
	 * Decrement numeric non persistent cache item's value.
	 *
	 * @param int|string $key The cache key to increment
	 * @param int $offset The amount by which to decrement the item's value.
	 *
	 * @return  false|int   False on failure, the item's new value on success.
	 * @since   3.0.0
	 */
	private function decr_non_persistent( $key, $offset ) {
		if ( ! $this->is_non_persistent_key( $key ) ) {
			return false;
		}
		$offset = max( (int) $offset, 0 );
		$var    = $this->get_non_persitent( $key );
		$var    = is_numeric( $var ) ? $var : 0;
		$var    -= $offset;
		return $this->set_non_persistent( $key, $var );
	}

	/**
	 * Increment numeric cache item's value.
	 *
	 * @param int|string $key The cache key to increment
	 * @param int $offset Optional. The amount by which to increment the item's value.
	 * @param string $group Optional. The group the key is in.
	 *
	 * @return  false|int   False on failure, the item's new value on success.
	 * @since   3.0.0
	 */
	public function incr( $key, $offset = 1, $group = 'default' ) {
		$key = $this->full_item_name( $key, $group );
		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->incr_non_persistent( $key, $offset );
		}
		return $this->incr_persistent( $key, $offset );
	}

	/**
	 * Increment numeric APCu cache item's value.
	 *
	 * @param int|string $key The cache key to increment
	 * @param int $offset The amount by which to increment the item's value.
	 *
	 * @return  false|int   False on failure, the item's new value on success.
	 * @since   3.0.0
	 */
	private function incr_persistent( $key, $offset ) {
		$this->get_persistent( $key, $success );
		if ( ! $success ) {
			$this->metrics['inc']['fail'] += 1;
			return false;
		}
		$chrono                       = microtime( true );
		$result                       = false !== apcu_inc( $key, max( (int) $offset, 0 ) );
		$this->metrics['inc']['time'] += microtime( true ) - $chrono;
		if ( $result ) {
			$this->local_cache[ $key ] = $result;
			if ( $this::$debug ) {
				$this->metrics['inc']['size'] += $this->size_of( $offset );
			}
			$this->metrics['inc']['success'] += 1;
			if ( isset( self::$events_logger ) && self::$debug ) {
				self::$events_logger->debug( self::$events_prefix . sprintf( 'Key "%s" successfully decremented.', $key ) );
			}
		} else {
			$this->metrics['inc']['fail'] += 1;
			if ( isset( self::$events_logger ) && self::$debug ) {
				self::$events_logger->debug( self::$events_prefix . sprintf( 'Key "%s" unsuccessfully decremented.', $key ) );
			}
		}
		return $result;
	}

	/**
	 * Increment numeric non persistent cache item's value.
	 *
	 * @param int|string $key The cache key to increment
	 * @param int $offset The amount by which to increment the item's value.
	 *
	 * @return  false|int   False on failure, the item's new value on success.
	 * @since   3.0.0
	 */
	private function incr_non_persistent( $key, $offset ) {
		if ( ! $this->is_non_persistent_key( $key ) ) {
			return false;
		}
		$offset = max( (int) $offset, 0 );
		$var    = $this->get_non_persitent( $key );
		$var    = is_numeric( $var ) ? $var : 0;
		$var    += $offset;
		return $this->set_non_persistent( $key, $var );
	}

	/**
	 * Remove the contents of the cache key in the group.
	 *
	 * @param int|string $key What the contents in the cache are called.
	 * @param string $group Optional. Where the cache contents are grouped.
	 *
	 * @return  bool    True on success, false otherwise.
	 * @since   3.0.0
	 */
	public function delete( $key, $group = 'default' ) {
		$key = $this->full_item_name( $key, $group );
		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->delete_non_persistent( $key );
		}
		return $this->delete_persistent( $key );
	}

	/**
	 * Remove the contents of the APCu cache key in the group.
	 *
	 * @param int|string $key What the contents in the cache are called.
	 * @param bool $single Optional. If false, it is a flush.
	 *
	 * @return  bool    True on success, false otherwise.
	 * @since   3.0.0
	 */
	private function delete_persistent( $key, $single = true ) {
		if ( $single ) {
			$chrono = microtime( true );
		}
		$result = true === apcu_delete( $key );
		if ( $single ) {
			$this->metrics['delete']['time'] += microtime( true ) - $chrono;
		}
		unset( $this->local_cache[ $key ] );
		if ( $result ) {
			if ( $single ) {
				$this->metrics['delete']['success'] += 1;
				if ( isset( self::$events_logger ) && self::$debug ) {
					self::$events_logger->debug( self::$events_prefix . sprintf( 'Key "%s" successfully deleted.', $key ) );
				}
			}
		} else {
			if ( $single ) {
				$this->metrics['delete']['fail'] += 1;
				if ( isset( self::$events_logger ) && self::$debug ) {
					self::$events_logger->debug( self::$events_prefix . sprintf( 'Key "%s" unsuccessfully deleted.', $key ) );
				}
			}
		}
		return $result;
	}

	/**
	 * Remove the contents of the non persistent cache key in the group.
	 *
	 * @param int|string $key What the contents in the cache are called.
	 *
	 * @return  bool    True on success, false otherwise.
	 * @since   3.0.0
	 */
	private function delete_non_persistent( $key ) {
		if ( array_key_exists( $key, $this->non_persistent_cache ) ) {
			unset( $this->non_persistent_cache[ $key ] );
			return true;
		}
		return false;
	}

	/**
	 * Retrieves the cache contents, if it exists.
	 *
	 * @param int|string $key What the contents in the cache are called.
	 * @param string     $group Optional. Where the cache contents are grouped.
	 * @param bool       $force Optional. Whether to force an update of the local cache
	 *                          from the persistent cache. Default false.
	 * @param bool       &$success Optional. Return success - or not.
	 *
	 * @return  bool|mixed  False on failure to retrieve contents or the cache contents on success.
	 * @since   3.0.0
	 */
	public function get( $key, $group = 'default', $force = false, &$success = null ) {
		$key = $this->full_item_name( $key, $group );
		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			$var = $this->get_non_persitent( $key, $success );
		} else {
			$var = $this->get_persistent( $key, $success, $force );
		}
		return $var;
	}

	/**
	 * Retrieves the APCu cache contents, if it exists.
	 *
	 * @param int|string  $key What the contents in the cache are called.
	 * @param bool        &$success Optional. Return success - or not.
	 * @param bool       $force Optional. Whether to force an update of the local cache
	 *                          from the persistent cache. Default false.
	 *
	 * @return  bool|mixed  False on failure to retrieve contents or the cache contents on success.
	 * @since   3.0.0
	 */
	private function get_persistent( $key, &$success = null, $force = false ) {
		if ( ! $force && array_key_exists( $key, $this->local_cache ) ) {
			$success = true;
			$var     = $this->local_cache[ $key ];
		} else {
			$chrono                         = microtime( true );
			$var                            = apcu_fetch( $key, $success );
			$this->metrics['fetch']['time'] += microtime( true ) - $chrono;
			if ( $success ) {
				$this->local_cache[ $key ] = $var;
				if ( $this::$debug ) {
					$this->metrics['fetch']['size'] += $this->size_of( $var );
				}
				$this->metrics['fetch']['success'] += 1;
				if ( isset( self::$events_logger ) && self::$debug ) {
					self::$events_logger->debug( self::$events_prefix . sprintf( 'Key "%s" successfully fetched.', $key ) );
				}
			} else {
				$this->metrics['fetch']['fail'] += 1;
				if ( isset( self::$events_logger ) && self::$debug ) {
					self::$events_logger->debug( self::$events_prefix . sprintf( 'Key "%s" unsuccessfully fetched.', $key ) );
				}
			}
		}
		if ( is_object( $var ) ) {
			$var = clone $var;
		}
		return $var;
	}

	/**
	 * Retrieves the non persistent cache contents, if it exists
	 *
	 * @param int|string $key What the contents in the cache are called.
	 * @param bool        &$success Optional. Return success - or not.
	 *
	 * @return  bool|mixed  False on failure to retrieve contents or the cache contents on success.
	 * @since   3.0.0
	 */
	private function get_non_persitent( $key, &$success = null ) {
		if ( array_key_exists( $key, $this->non_persistent_cache ) ) {
			$success = true;
			return $this->non_persistent_cache[ $key ];
		}
		$success = false;
		return false;
	}

	/**
	 * Replace the contents in the cache, if contents already exist.
	 *
	 * @param int|string $key What to call the contents in the cache.
	 * @param mixed $var The contents to store in the cache.
	 * @param string $group Optional. Where to group the cache contents.
	 * @param int $ttl Optional. When to expire the cache contents.
	 *
	 * @return  bool    True if contents were replaced, false otherwise.
	 * @since   3.0.0
	 */
	public function replace( $key, $var, $group = 'default', $ttl = 0 ) {
		$key = $this->full_item_name( $key, $group );
		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->replace_non_persistent( $key, $var );
		}
		return $this->replace_persistent( $key, $var, $ttl );
	}

	/**
	 * Replace the contents in the APCu cache, if contents already exist.
	 *
	 * @param int|string $key What to call the contents in the cache.
	 * @param mixed $var The contents to store in the cache.
	 * @param int $ttl When to expire the cache contents.
	 *
	 * @return  bool    True if contents were replaced, false otherwise.
	 * @since   3.0.0
	 */
	private function replace_persistent( $key, $var, $ttl ) {
		$this->get_persistent( $key, $success );
		if ( ! $success ) {
			$this->metrics['replace']['fail'] += 1;
			return false;
		}
		return $this->set_persistent( $key, $var, $ttl, true );
	}

	/**
	 * Replace the contents in the non persistent cache, if contents already exist.
	 *
	 * @param int|string $key What to call the contents in the cache.
	 * @param mixed $var The contents to store in the cache.
	 *
	 * @return  bool    True if contents were replaced, false otherwise.
	 * @since   3.0.0
	 */
	private function replace_non_persistent( $key, $var ) {
		if ( ! $this->is_non_persistent_key( $key ) ) {
			return false;
		}
		return $this->set_non_persistent( $key, $var );
	}

	/**
	 * Sets the data contents into the cache.
	 *
	 * @param int|string $key What to call the contents in the cache.
	 * @param mixed $var The contents to store in the cache.
	 * @param int $ttl When to expire the cache contents.
	 *
	 * @return  bool    True if contents were set, false otherwise.
	 * @since   3.0.0
	 */
	public function set( $key, $var, $group = 'default', $ttl = 0 ) {
		$key = $this->full_item_name( $key, $group );
		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->set_non_persistent( $key, $var );
		}
		return $this->set_persistent( $key, $var, $ttl );
	}

	/**
	 * Sets the data contents into the APCu cache.
	 *
	 * @param int|string $key What to call the contents in the cache.
	 * @param mixed $var The contents to store in the cache.
	 * @param int $ttl When to expire the cache contents.
	 * @param bool $replace Optional. It is a replace operation.
	 *
	 * @return  bool    True if contents were set, false otherwise.
	 * @since   3.0.0
	 */
	private function set_persistent( $key, $var, $ttl, $replace = false ) {
		if ( is_object( $var ) ) {
			$var = clone $var;
		}
		$op                            = $replace ? 'replace' : 'set';
		$op_name                       = $replace ? 'replaced' : 'set';
		$chrono                        = microtime( true );
		$success                       = apcu_store( $key, $var, max( (int) $ttl, 0 ) );
		$this->metrics[ $op ]['time'] += microtime( true ) - $chrono;
		if ( $success ) {
			$this->local_cache[ $key ] = $var;
			if ( $this::$debug ) {
				$this->metrics[ $op ]['size'] += $this->size_of( $var );
			}
			$this->metrics[ $op ]['success'] += 1;
			if ( isset( self::$events_logger ) && self::$debug ) {
				self::$events_logger->debug( self::$events_prefix . sprintf( 'Key "%s" successfully %s.', $key, $op_name ) );
			}
		} else {
			$this->metrics[ $op ]['fail'] += 1;
			if ( isset( self::$events_logger ) && self::$debug ) {
				self::$events_logger->debug( self::$events_prefix . sprintf( 'Key "%s" unsuccessfully %s.', $key, $op_name ) );
			}
		}
		return $success;
	}

	/**
	 * Sets the data contents into the non persistent cache.
	 *
	 * @param int|string $key What to call the contents in the cache.
	 * @param mixed $var The contents to store in the cache.
	 *
	 * @return  bool    True if contents were replaced, false otherwise.
	 * @since   3.0.0
	 */
	private function set_non_persistent( $key, $var ) {
		if ( is_object( $var ) ) {
			$var = clone $var;
		}
		return $this->non_persistent_cache[ $key ] = $var;
	}

	/**
	 * Adds multiple values to the cache in one call.
	 *
	 * @param array $data Array of keys and values to be set.
	 * @param string $group Optional. Where the cache contents are grouped. Default empty.
	 * @param int $expire Optional. When to expire the cache contents, in seconds.
	 *                       Default 0 (no expiration).
	 *
	 * @return bool[] Array of return values, grouped by key. Each value is either
	 *                true on success, or false if cache key and group already exist.
	 * @since   3.1.0
	 */
	public function add_multiple( $data, $group = 'default', $expire = 0 ) {
		$values = [];
		foreach ( $data as $key => $value ) {
			$values[ $key ] = $this->add( $key, $value, $group, $expire );
		}
		return $values;
	}

	/**
	 * Sets multiple values to the cache in one call.
	 *
	 * @param array $data Array of keys and values to be set.
	 * @param string $group Optional. Where the cache contents are grouped. Default empty.
	 * @param int $expire Optional. When to expire the cache contents, in seconds.
	 *                       Default 0 (no expiration).
	 *
	 * @return bool[] Array of return values, grouped by key. Each value is either
	 *                true on success, or false on failure.
	 * @since 3.1.0
	 */
	public function set_multiple( $data, $group = 'default', $expire = 0 ) {
		$values = [];
		foreach ( $data as $key => $value ) {
			$values[ $key ] = $this->set( $key, $value, $group, $expire );
		}
		return $values;
	}

	/**
	 * Retrieves multiple values from the cache in one call.
	 *
	 * @param array  $keys  Array of keys under which the cache contents are stored.
	 * @param string $group Optional. Where the cache contents are grouped. Default empty.
	 * @param bool   $force Optional. Whether to force an update of the local cache
	 *                      from the persistent cache. Default false.
	 * @return array Array of return values, grouped by key. Each value is either
	 *               the cache contents on success, or false on failure.
	 * @since 3.1.0
	 */
	public function get_multiple( $keys, $group = 'default', $force = false ) {
		$values = [];
		foreach ( $keys as $key ) {
			$values[ $key ] = $this->get( $key, $group, $force );
		}
		return $values;
	}

	/**
	 * Deletes multiple values from the cache in one call.
	 *
	 * @param array  $keys  Array of keys under which the cache to deleted.
	 * @param string $group Optional. Where the cache contents are grouped. Default empty.
	 * @return bool[] Array of return values, grouped by key. Each value is either
	 *                true on success, or false if the contents were not deleted.
	 * @since 3.1.0
	 */
	public function delete_multiple( $keys, $group = 'default' ) {
		$values = [];
		foreach ( $keys as $key ) {
			$values[ $key ] = $this->delete( $key, $group );
		}
		return $values;
	}

}