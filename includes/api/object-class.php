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
	public $available_metrics = [ 'add', 'dec', 'inc', 'set', 'replace', 'fetch', 'delete' ];

	/**
	 * List of global groups.
	 *
	 * @var     array
	 * @since   3.0.0
	 */
	private $global_groups = [
		'blog-details',
		'blog-id-cache',
		'blog-lookup',
		'global-posts',
		'networks',
		'rss',
		'sites',
		'site-details',
		'site-lookup',
		'site-options',
		'site-transient',
		'users',
		'useremail',
		'userlogins',
		'usermeta',
		'user_meta',
		'userslugs',
	];

	/**
	 * List of non persistent groups.
	 *
	 * @var     array
	 * @since   3.0.0
	 */
	private $non_persistent_groups = [];

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
	private $apcu_available = false;

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
			self::$instance = new WP_Object_Cache();
		}
		return self::$instance;
	}

	/**
	 * Set instance of \DecaLog\TracesLogger.
	 *
	 * @param   \DecaLog\EventsLogger   $logger     The logger to attach.
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
	 * @param   \DecaLog\TracesLogger   $logger     The logger to attach.
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
	 * @param   \DecaLog\MetricsLogger   $logger     The logger to attach.
	 * @since   3.0.0
	 */
	public static function set_metrics_logger( $logger ) {
		if ( $logger instanceof \DecaLog\MetricsLogger ) {
			self::$metrics_logger = $logger;
			if ( isset( self::$events_logger ) && self::$debug ) {
				self::$events_logger->debug( self::$events_prefix . 'Metrics logger attached.' );
			}
		}
		if ( Option::network_get( 'metrics' ) && isset( self::$metrics_logger ) ) {
			add_action( 'shutdown', [ self::instance(), 'collate_metrics' ], DECALOG_MAX_SHUTDOWN_PRIORITY, 0 );
			self::$metrics_logger->createProdGauge( 'object_cache_all_hit_ratio', 0, 'Object cache hit ratio per request - [percent]' );
			self::$metrics_logger->createProdCounter( 'object_cache_all_time', 'Object cache time per request - [second]' );
			if ( self::$debug ) {
				self::$metrics_logger->createDevCounter( 'object_cache_all_size', 'Object cache size per request - [byte]' );
			}
			foreach ( self::instance()->available_metrics as $metric ) {
				self::$metrics_logger->createProdCounter( 'object_cache_' . $metric . '_success', sprintf( 'Number of successfully %s objects per request - [count]', 'aaa' ) );
				self::$metrics_logger->createProdCounter( 'object_cache_' . $metric . '_fail', sprintf( 'Number of unsuccessfully %s objects per request - [count]', 'aaa' ) );
				self::$metrics_logger->createProdCounter( 'object_cache_' . $metric . '_time', sprintf( 'Cache time for successfully %s objects per request - [second]', 'aaa' ) );
				if ( self::$debug ) {
					self::$metrics_logger->createDevCounter( 'object_cache_' . $metric . '_size', sprintf( 'Size of successfully %s objects per request - [byte]', 'aaa' ) );
				}
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
	 * Collates metrics.
	 *
	 * @since   3.0.0
	 */
	public function collate_metrics() {
		//$span     = \DecaLog\Engine::tracesLogger( APCM_SLUG )->startSpan( 'Object caching metrics collation', DECALOG_SPAN_PLUGINS_LOAD );
		if ( isset( self::$events_logger ) ) {
			//self::$events_logger->critical( print_r($this->metrics,true) );
		}
	}

	/**
	 * Compute a full cache key name.
	 *
	 * @param   int|string  $key    The key.
	 * @param   string      $group  The group.
	 * @return  string  The full cache key name.
	 * @since   3.0.0
	 */
	private function full_item_name( $key, $group ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}
		$prefix = '';
		if ( ! in_array( (string) $group, $this->global_groups, true ) ) {
			$prefix = $this->blog_prefix . '_';
		}
		return 'wordpress' . $this->cache_prefix . $prefix . $group . '_' . $key;
	}

	/**
	 * Compute the size of variable in APCu (so a serialized var).
	 *
	 * @param   mixed   $var    The variable.
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
	 * @param   int     $blog_id    The blog ID.
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
	 * @param   string|array    $groups     The list of groups to add.
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
	 * gets the list of non persistent groups.
	 *
	 * @return  array   The list of groups to add.
	 * @since   3.0.0
	 */
	public function get_non_persistent_groups() {
		return $this->non_persistent_groups;
	}

	/**
	 * Adds a list of non persistent groups.
	 *
	 * @param   string|array    $groups     The list of groups to add.
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
	 * @param   string  $group  The group to check.
	 * @return  bool    True if the group is a non persistent group, false otherwise.
	 * @since   3.0.0
	 */
	private function is_non_persistent_group( $group ) {
		return in_array( (string) $group, $this->non_persistent_groups, true );
	}

	/**
	 * Invalidate groups.
	 *
	 * @param   string|array    $groups     The list of groups to invalidate.
	 * @return  bool    True if there is something to invalidate, false otherwise.
	 * @since   3.0.0
	 */
	public function flush_groups( $groups ) {
		$groups = (array) $groups;
		if ( empty( $groups ) ) {
			return false;
		}
		if ( $this->apcu_available ) {
			// Iterate in objects list
		}
		return false;
	}

	/**
	 * Clears the object cache of all data.
	 *
	 * @return bool Always returns true.
	 */
	public function flush() {
		$this->non_persistent_cache = [];
		if ( $this->apcu_available ) {
			// Iterate in objects list
		}
		return true;
	}

	/**
	 * Invalidate sites' object cache.
	 *
	 * @param string|array $sites Sites ID's that want flushing.
	 *                     Don't pass a site to flush current site
	 *
	 * @return bool
	 */
	public function flush_sites( $sites ) {
		$sites = (array) $sites;
		if ( ! empty( $sites ) ) {
			if ( ! in_array(0, $sites, true ) ) {
				$sites[] = 0;
			}
			if ( $this->apcu_available ) {
				// Iterate in objects list
			}
		}
		return true;
	}

	/**
	 * Checks if the cached non persistent key exists.
	 *
	 * @param   string  $key    What the contents in the cache are called.
	 * @return  bool    True if cache key exists, false otherwise.
	 */
	private function is_non_persistent_key( $key ) {
		return array_key_exists( $key, $this->non_persistent_cache );
	}

	/**
	 * Adds data to the cache, if the cache key does not already exist.
	 *
	 * @param   int|string  $key    The cache key to use for later retrieval.
	 * @param   mixed       $var    The data to add to the cache store.
	 * @param   string      $group  Optional. The group to add the cache to.
	 * @param   int         $ttl    Optional. When the cache data should be expired.
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
	 * @param   int|string  $key    The cache key to use for later retrieval.
	 * @param   mixed       $var    The data to add to the cache store.
	 * @param   int         $ttl    When the cache data should be expired.
	 * @return  bool    False if cache key and group already exist, true otherwise.
	 * @since   3.0.0
	 */
	private function add_persistent( $key, $var, $ttl ) {
		$chrono                        = microtime( true );
		$result                        = true === apcu_add( $key, $var, max( (int) $ttl, 0 ) );
		$this->metrics['add']['time'] += microtime( true ) - $chrono;
		if ( $result ) {
			$this->local_cache[ $key ]        = is_object( $var ) ? clone $var : $var;
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
	 * @param   int|string  $key    The cache key to use for later retrieval.
	 * @param   mixed       $var    The data to add to the cache store.
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
	 * @param   int|string  $key    The cache key to increment
	 * @param   int $offset         Optional. The amount by which to decrement the item's value.
	 * @param   string $group       Optional. The group the key is in.
	 * @return  false|int   False on failure, the item's new value on success.
	 * @since   3.0.0
	 */
	public function decr( $key, $offset = 1, $group = 'default' ) {
		$key = $this->full_item_name( $key, $group );
		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->decr_non_persistent( $key, $offset );
		}
		return $this->decr_persistent ($key, $offset );
	}

	/**
	 * Decrement numeric APCu cache item's value.
	 *
	 * @param   int|string  $key    The cache key to increment
	 * @param   int $offset         The amount by which to decrement the item's value.
	 * @return  false|int   False on failure, the item's new value on success.
	 * @since   3.0.0
	 */
	private function decr_persistent( $key, $offset ) {
		$this->get_persistent( $key, $success );
		if ( ! $success ) {
			$this->metrics['dec']['fail'] += 1;
			return false;
		}
		$chrono                        = microtime( true );
		$result                        = false !== apcu_dec( $key, max( (int) $offset, 0 ) );
		$this->metrics['dec']['time'] += microtime( true ) - $chrono;
		if ( $result ) {
			$this->local_cache[ $key ]        = $result;
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
	 * @param   int|string  $key    The cache key to increment
	 * @param   int $offset         The amount by which to decrement the item's value.
	 * @return  false|int   False on failure, the item's new value on success.
	 * @since   3.0.0
	 */
	private function decr_non_persistent( $key, $offset ) {
		if ( ! $this->is_non_persistent_key ($key ) ) {
			return false;
		}
		$offset = max( (int) $offset, 0 );
		$var    = $this->get_non_persitent( $key );
		$var    = is_numeric( $var ) ? $var : 0;
		$var   -= $offset;
		return $this->set_non_persistent( $key, $var );
	}

	/**
	 * Increment numeric cache item's value.
	 *
	 * @param   int|string  $key    The cache key to increment
	 * @param   int $offset         Optional. The amount by which to increment the item's value.
	 * @param   string $group       Optional. The group the key is in.
	 * @return  false|int   False on failure, the item's new value on success.
	 * @since   3.0.0
	 */
	public function incr( $key, $offset = 1, $group = 'default' ) {
		$key = $this->full_item_name( $key, $group );
		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->incr_non_persistent( $key, $offset );
		}
		return $this->incr_persistent ($key, $offset );
	}

	/**
	 * Increment numeric APCu cache item's value.
	 *
	 * @param   int|string  $key    The cache key to increment
	 * @param   int $offset         The amount by which to increment the item's value.
	 * @return  false|int   False on failure, the item's new value on success.
	 * @since   3.0.0
	 */
	private function incr_persistent( $key, $offset ) {
		$this->get_persistent( $key, $success );
		if ( ! $success ) {
			$this->metrics['inc']['fail'] += 1;
			return false;
		}
		$chrono                        = microtime( true );
		$result                        = false !== apcu_inc( $key, max( (int) $offset, 0 ) );
		$this->metrics['inc']['time'] += microtime( true ) - $chrono;
		if ( $result ) {
			$this->local_cache[ $key ]        = $result;
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
	 * @param   int|string  $key    The cache key to increment
	 * @param   int $offset         The amount by which to increment the item's value.
	 * @return  false|int   False on failure, the item's new value on success.
	 * @since   3.0.0
	 */
	private function incr_non_persistent( $key, $offset ) {
		if ( ! $this->is_non_persistent_key ($key ) ) {
			return false;
		}
		$offset = max( (int) $offset, 0 );
		$var    = $this->get_non_persitent( $key );
		$var    = is_numeric( $var ) ? $var : 0;
		$var   += $offset;
		return $this->set_non_persistent( $key, $var );
	}

	/**
	 * Remove the contents of the cache key in the group.
	 *
	 * @param   int|string  $key        What the contents in the cache are called.
	 * @param   string      $group      Optional. Where the cache contents are grouped.
	 * @param   bool        $deprecated Optional. Deprecated.
	 * @return  bool    True on success, false otherwise.
	 * @since   3.0.0
	 */
	public function delete( $key, $group = 'default', $deprecated = false ) {
		$key = $this->full_item_name( $key, $group );
		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->delete_non_persistent( $key );
		}
		return $this->delete_persistent( $key );
	}

	/**
	 * Remove the contents of the APCu cache key in the group.
	 *
	 * @param   int|string  $key        What the contents in the cache are called.
	 * @return  bool    True on success, false otherwise.
	 * @since   3.0.0
	 */
	private function delete_persistent( $key ) {
		$chrono                        = microtime( true );
		$result                        = true === apcu_delete( $key );
		$this->metrics['delete']['time'] += microtime( true ) - $chrono;
		unset( $this->local_cache[ $key ] );
		if ( $result ) {
			$this->metrics['delete']['success'] += 1;
			if ( isset( self::$events_logger ) && self::$debug ) {
				self::$events_logger->debug( self::$events_prefix . sprintf( 'Key "%s" successfully deleted.', $key ) );
			}
		} else {
			$this->metrics['delete']['fail'] += 1;
			if ( isset( self::$events_logger ) && self::$debug ) {
				self::$events_logger->debug( self::$events_prefix . sprintf( 'Key "%s" unsuccessfully deleted.', $key ) );
			}
		}
		return $result;
	}

	/**
	 * Remove the contents of the non persistent cache key in the group.
	 *
	 * @param   int|string  $key        What the contents in the cache are called.
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
	 * @param   int|string  $key        What the contents in the cache are called.
	 * @param   string      $group      Optional. Where the cache contents are grouped.
	 * @param   bool        $force      Not used.
	 * @param   bool        &$success   Optional. Return success - or not.
	 * @return  bool|mixed  False on failure to retrieve contents or the cache contents on success.
	 * @since   3.0.0
	 */
	public function get( $key, $group = 'default', $force = false, &$success = null ) {
		$key = $this->full_item_name( $key, $group );
		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			$var = $this->get_non_persitent( $key, $success );
		} else {
			$var = $this->get_persistent( $key, $success );
		}
		return $var;
	}

	/**
	 * Retrieves the APCu cache contents, if it exists.
	 *
	 * @param   int|string  $key        What the contents in the cache are called.
	 * @param   bool        &$success   Optional. Return success - or not.
	 * @return  bool|mixed  False on failure to retrieve contents or the cache contents on success.
	 * @since   3.0.0
	 */
	private function get_persistent( $key, &$success = null ) {
		if ( array_key_exists( $key, $this->local_cache ) ) {
			$success = true;
			$var     = $this->local_cache[ $key ];
		} else {
			$chrono                          = microtime( true );
			$var                             = apcu_fetch( $key, $success );
			$this->metrics['fetch']['time'] += microtime( true ) - $chrono;
			if ( $success ) {
				$this->local_cache[ $key ]          = $var;
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
	 * @param   int|string  $key        What the contents in the cache are called.
	 * @param   bool        &$success   Optional. Return success - or not.
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
	 * Retrieve multiple values from cache.
	 *
	 * Usage: array( 'group0' => array( 'key0', 'key1', 'key2', ), 'group1' => array( 'key0' ) )
	 *
	 * @param   array   $groups Array of groups and keys to retrieve.
	 *
	 * @return  array|bool  Array of cached values as
	 *    array( 'group0' => array( 'key0' => 'value0', 'key1' => 'value1', 'key2' => 'value2', ) )
	 *    Non-existent keys are not returned.
	 * @since   3.0.0
	 */
	public function get_multi( $groups ) {
		if ( empty( $groups ) || ! is_array( $groups ) ) {
			return false;
		}
		$vars    = [];
		$success = false;
		foreach ( $groups as $group => $keys ) {
			$vars[ $group ] = [];
			foreach ( $keys as $key ) {
				$var = $this->get( $key, $group, false, $success );
				if ( $success ) {
					$vars[ $group ][ $key ] = $var;
				}
			}
		}
		return $vars;
	}

	/**
	 * Replace the contents in the cache, if contents already exist.
	 *
	 * @param   int|string  $key    What to call the contents in the cache.
	 * @param   mixed       $var    The contents to store in the cache.
	 * @param   string      $group  Optional. Where to group the cache contents.
	 * @param   int         $ttl    Optional. When to expire the cache contents.
	 * @return  bool    True if contents were replaced, false otherwise.
	 * @since   3.0.0
	 */
	public function replace($key, $var, $group = 'default', $ttl = 0) {
		$key = $this->full_item_name( $key, $group );
		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->replace_non_persistent( $key, $var );
		}
		return $this->replace_persistent( $key, $var, $ttl );
	}

	/**
	 * Replace the contents in the APCu cache, if contents already exist.
	 *
	 * @param   int|string  $key    What to call the contents in the cache.
	 * @param   mixed       $var    The contents to store in the cache.
	 * @param   int         $ttl    When to expire the cache contents.
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
	 * @param   int|string  $key    What to call the contents in the cache.
	 * @param   mixed       $var    The contents to store in the cache.
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
	 * @param   int|string  $key    What to call the contents in the cache.
	 * @param   mixed       $var    The contents to store in the cache.
	 * @param   int         $ttl    When to expire the cache contents.
	 * @return  bool    True if contents were set, false otherwise.
	 * @since   3.0.0
	 */
	public function set( $key, $var, $group = 'default', $ttl = 0 ) {
		$key = $this->full_item_name($key, $group);
		if ( ! $this->apcu_available || $this->is_non_persistent_group( $group ) ) {
			return $this->set_non_persistent( $key, $var );
		}
		return $this->set_persistent( $key, $var, $ttl );
	}

	/**
	 * Sets the data contents into the APCu cache.
	 *
	 * @param   int|string  $key        What to call the contents in the cache.
	 * @param   mixed       $var        The contents to store in the cache.
	 * @param   int         $ttl        When to expire the cache contents.
	 * @param   bool        $replace    Optional. It is a replace operation.
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
			$this->local_cache[ $key ]        = $var;
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
	 * @param   int|string  $key    What to call the contents in the cache.
	 * @param   mixed       $var    The contents to store in the cache.
	 * @return  bool    True if contents were replaced, false otherwise.
	 * @since   3.0.0
	 */
	private function set_non_persistent( $key, $var ) {
		if ( is_object( $var ) ) {
			$var = clone $var;
		}
		return $this->non_persistent_cache[ $key ] = $var;
	}

}