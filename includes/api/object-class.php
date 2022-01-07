<?php
/**
 * APCu Objects Cache Object
 *
 * Handles all APCU caching operations.
 *
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */


/**
 * Object cache class definition
 */
class WP_Object_Cache {

	/**
	 * Track how many requests were found in cache.
	 *
	 * @var     int
	 * @since   3.0.0
	 */
	private $cache_hits = 0;

	/**
	 * Track how may requests were not cached.
	 *
	 * @var     int
	 * @since   3.0.0
	 */
	private $cache_misses = 0;

	/**
	 * Track how long request took.
	 *
	 * @var     float
	 * @since   3.0.0
	 */
	private $cache_time = 0;

	/**
	 * Track how many read calls were made.
	 *
	 * @var     int
	 * @since   3.0.0
	 */
	private $cache_read = 0;

	/**
	 * Track how many read calls were made.
	 *
	 * @var     int
	 * @since   3.0.0
	 */
	private $cache_store = 0;

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
	 * Is APCu really available.
	 *
	 * @var     bool
	 * @since   3.0.0
	 */
	private $apcu_available = false;

	/**
	 * Is it a multisite.
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
	 * Instance of WP_Object_Cache.
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
	 * Switch the internal blog id.
	 *
	 * @param   int     $blog_id    The blog ID.
	 * @since   3.0.0
	 */
	public function switch_to_blog( $blog_id ) {
		$this->blog_prefix = $this->multi_site ? $blog_id : 1;
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
		foreach ( (array) $groups as $group ) {
			$this->global_groups[] = $group;
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
		foreach ( (array) $groups as $group ) {
			$this->non_persistent_groups[] = $group;
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
		return true === apcu_add( $key, $var, max( (int) $ttl, 0 ) );
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
			return false;
		}
		return apcu_dec( $key, max( (int) $offset, 0 ) );
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
			return false;
		}
		return apcu_inc( $key, max( (int) $offset, 0 ) );
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
		return apcu_delete( $key );
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
		if ( $success ) {
			$this->cache_hits++;
		} else {
			$this->cache_misses++;
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
		$var = apcu_fetch( $key, $success );
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
		if ( $success ) {
			return false;
		}
		return $this->set_persistent( $key, $var, $ttl );
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
	 * @return  bool    True if contents were replaced, false otherwise.
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
	 * @param   int|string  $key    What to call the contents in the cache.
	 * @param   mixed       $var    The contents to store in the cache.
	 * @param   int         $ttl    When to expire the cache contents.
	 * @return  bool    True if contents were replaced, false otherwise.
	 * @since   3.0.0
	 */
	private function set_persistent( $key, $var, $ttl ) {
		if ( is_object( $var ) ) {
			$var = clone $var;
		}
		return apcu_store( $key, $var, max( (int) $ttl, 0 ) );
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