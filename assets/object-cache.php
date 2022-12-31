<?php
/**
 * Plugin Name:         APCu Objects Cache Handler
 * Plugin URI:          https://perfops.one/apcu-manager
 * Description:         APCu statistics and management right in the WordPress admin dashboard.
 * Version:             3.x
 * Author:              Pierre Lannoy / PerfOps One
 * Author URI:          https://perfops.one
 * License:             GPLv3
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}
$handler_file = WP_PLUGIN_DIR . '/apcu-manager/includes/api/object-class.php';
if ( ! file_exists( $handler_file ) ) {
	$handler_file = __DIR__ . '/plugins/apcu-manager/includes/api/object-class.php';
}
if ( file_exists( $handler_file ) ) {

	require_once $handler_file;

	if ( ! defined( 'APCM_BOOTSTRAPPED' ) ) {
		define( 'APCM_BOOTSTRAPPED', true );
	}

	/**
	 * Sets up Object Cache Global and assigns it.
	 *
	 * @since 3.0.0
	 *
	 * @global WP_Object_Cache $wp_object_cache
	 */
	function wp_cache_init() {
		global $wp_object_cache;
		if ( ! ( $wp_object_cache instanceof WP_Object_Cache ) ) {
			$wp_object_cache = WP_Object_Cache::instance();
		}
	}

	/**
	 * Adds data to the cache, if the cache key doesn't already exist.
	 *
	 * @since 3.0.0
	 *
	 * @see WP_Object_Cache::add()
	 *
	 * @param int|string $key    The cache key to use for retrieval later.
	 * @param mixed      $data   The data to add to the cache.
	 * @param string     $group  Optional. The group to add the cache to. Enables the same key
	 *                           to be used across groups. Default empty.
	 * @param int        $expire Optional. When the cache data should expire, in seconds.
	 *                           Default 0 (no expiration).
	 * @return bool True on success, false if cache key and group already exist.
	 */
	function wp_cache_add( $key, $data, $group = 'default', $expire = 0 ) {
		global $wp_object_cache;
		return $wp_object_cache instanceof WP_Object_Cache ? $wp_object_cache->add( $key, $data, $group, $expire ) : false;
	}

	/**
	 * Adds multiple values to the cache in one call.
	 *
	 * @since 3.1.0
	 *
	 * @see WP_Object_Cache::add_multiple()
	 *
	 * @param array  $data   Array of keys and values to be set.
	 * @param string $group  Optional. Where the cache contents are grouped. Default empty.
	 * @param int    $expire Optional. When to expire the cache contents, in seconds.
	 *                       Default 0 (no expiration).
	 * @return bool[] Array of return values, grouped by key. Each value is either
	 *                true on success, or false if cache key and group already exist.
	 */
	function wp_cache_add_multiple( $data, $group = 'default', $expire = 0 ) {
		global $wp_object_cache;
		$error = [];
		foreach ( $data as $key => $datum ) {
			$error[$key] = false;
		}
		return $wp_object_cache instanceof WP_Object_Cache ? $wp_object_cache->add_multiple( $data, $group, $expire ) : $error;
	}

	/**
	 * Replaces the contents of the cache with new data.
	 *
	 * @since 3.0.0
	 *
	 * @see WP_Object_Cache::replace()
	 *
	 * @param int|string $key    The key for the cache data that should be replaced.
	 * @param mixed      $data   The new data to store in the cache.
	 * @param string     $group  Optional. The group for the cache data that should be replaced.
	 *                           Default empty.
	 * @param int        $expire Optional. When to expire the cache contents, in seconds.
	 *                           Default 0 (no expiration).
	 * @return bool True if contents were replaced, false if original value does not exist.
	 */
	function wp_cache_replace( $key, $data, $group = 'default', $expire = 0 ) {
		global $wp_object_cache;
		return $wp_object_cache instanceof WP_Object_Cache ? $wp_object_cache->replace( $key, $data, $group, $expire ) : false;
	}

	/**
	 * Saves the data to the cache.
	 *
	 * Differs from wp_cache_add() and wp_cache_replace() in that it will always write data.
	 *
	 * @since 3.0.0
	 *
	 * @see WP_Object_Cache::set()
	 *
	 * @param int|string $key    The cache key to use for retrieval later.
	 * @param mixed      $data   The contents to store in the cache.
	 * @param string     $group  Optional. Where to group the cache contents. Enables the same key
	 *                           to be used across groups. Default empty.
	 * @param int        $expire Optional. When to expire the cache contents, in seconds.
	 *                           Default 0 (no expiration).
	 * @return bool True on success, false on failure.
	 */
	function wp_cache_set( $key, $data, $group = 'default', $expire = 0 ) {
		global $wp_object_cache;
		return $wp_object_cache instanceof WP_Object_Cache ? $wp_object_cache->set( $key, $data, $group, $expire ) : false;
	}

	/**
	 * Sets multiple values to the cache in one call.
	 *
	 * @since 3.1.0
	 *
	 * @see WP_Object_Cache::set_multiple()
	 *
	 * @param array  $data   Array of keys and values to be set.
	 * @param string $group  Optional. Where the cache contents are grouped. Default empty.
	 * @param int    $expire Optional. When to expire the cache contents, in seconds.
	 *                       Default 0 (no expiration).
	 * @return bool[] Array of return values, grouped by key. Each value is either
	 *                true on success, or false on failure.
	 */
	function wp_cache_set_multiple( $data, $group = 'default', $expire = 0 ) {
		global $wp_object_cache;
		$error = [];
		foreach ( $data as $key => $datum ) {
			$error[$key] = false;
		}
		return $wp_object_cache instanceof WP_Object_Cache ? $wp_object_cache->set_multiple( $data, $group, $expire ) : $error;
	}

	/**
	 * Retrieves the cache contents from the cache by key and group.
	 *
	 * @since 3.0.0
	 *
	 * @see WP_Object_Cache::get()
	 *
	 * @param int|string $key   The key under which the cache contents are stored.
	 * @param string     $group Optional. Where the cache contents are grouped. Default empty.
	 * @param bool       $force Optional. Whether to force an update of the local cache
	 *                          from the persistent cache. Default false.
	 * @param bool       $found Optional. Whether the key was found in the cache (passed by reference).
	 *                          Disambiguates a return of false, a storable value. Default null.
	 * @return mixed|false The cache contents on success, false on failure to retrieve contents.
	 */
	function wp_cache_get( $key, $group = 'default', $force = false, &$found = null ) {
		global $wp_object_cache;
		return $wp_object_cache instanceof WP_Object_Cache ? $wp_object_cache->get( $key, $group, $force, $found ) : false;
	}

	/**
	 * Retrieves multiple values from the cache in one call.
	 *
	 * @since 3.1.0
	 *
	 * @see WP_Object_Cache::get_multiple()
	 *
	 * @param array  $keys  Array of keys under which the cache contents are stored.
	 * @param string $group Optional. Where the cache contents are grouped. Default empty.
	 * @param bool   $force Optional. Whether to force an update of the local cache
	 *                      from the persistent cache. Default false.
	 * @return array Array of return values, grouped by key. Each value is either
	 *               the cache contents on success, or false on failure.
	 */
	function wp_cache_get_multiple( $keys, $group = 'default', $force = false ) {
		global $wp_object_cache;
		$error = [];
		foreach ( $keys as $key ) {
			$error[$key] = false;
		}
		return $wp_object_cache instanceof WP_Object_Cache ? $wp_object_cache->get_multiple( $keys, $group, $force ) : $error;
	}

	/**
	 * Removes the cache contents matching key and group.
	 *
	 * @since 3.0.0
	 *
	 * @see WP_Object_Cache::delete()
	 *
	 * @param int|string $key   What the contents in the cache are called.
	 * @param string     $group Optional. Where the cache contents are grouped. Default empty.
	 * @return bool True on successful removal, false on failure.
	 */
	function wp_cache_delete( $key, $group = 'default' ) {
		global $wp_object_cache;
		return $wp_object_cache instanceof WP_Object_Cache ? $wp_object_cache->delete( $key, $group ) : false;
	}

	/**
	 * Deletes multiple values from the cache in one call.
	 *
	 * @since 3.1.0
	 *
	 * @see WP_Object_Cache::delete_multiple()
	 *
	 * @param array  $keys  Array of keys under which the cache to deleted.
	 * @param string $group Optional. Where the cache contents are grouped. Default empty.
	 * @return bool[] Array of return values, grouped by key. Each value is either
	 *                true on success, or false if the contents were not deleted.
	 */
	function wp_cache_delete_multiple( $keys, $group = 'default' ) {
		global $wp_object_cache;
		$error = [];
		foreach ( $keys as $key ) {
			$error[$key] = false;
		}
		return $wp_object_cache instanceof WP_Object_Cache ? $wp_object_cache->delete_multiple( $keys, $group ) : $error;
	}

	/**
	 * Increments numeric cache item's value.
	 *
	 * @since 3.0.0
	 *
	 * @see WP_Object_Cache::incr()
	 *
	 * @param int|string $key    The key for the cache contents that should be incremented.
	 * @param int        $offset Optional. The amount by which to increment the item's value.
	 *                           Default 1.
	 * @param string     $group  Optional. The group the key is in. Default empty.
	 * @return int|false The item's new value on success, false on failure.
	 */
	function wp_cache_incr( $key, $offset = 1, $group = 'default' ) {
		global $wp_object_cache;
		return $wp_object_cache instanceof WP_Object_Cache ? $wp_object_cache->incr( $key, $offset, $group ) : false;
	}

	/**
	 * Decrements numeric cache item's value.
	 *
	 * @since 3.0.0
	 *
	 * @see WP_Object_Cache::decr()
	 *
	 * @param int|string $key    The cache key to decrement.
	 * @param int        $offset Optional. The amount by which to decrement the item's value.
	 *                           Default 1.
	 * @param string     $group  Optional. The group the key is in. Default empty.
	 * @return int|false The item's new value on success, false on failure.
	 */
	function wp_cache_decr( $key, $offset = 1, $group = 'default' ) {
		global $wp_object_cache;
		return $wp_object_cache instanceof WP_Object_Cache ? $wp_object_cache->decr( $key, $offset, $group ) : false;
	}

	/**
	 * Removes all cache items.
	 *
	 * @since 3.0.0
	 *
	 * @see WP_Object_Cache::flush()
	 *
	 * @return bool True on success, false on failure.
	 */
	function wp_cache_flush() {
		global $wp_object_cache;
		return $wp_object_cache instanceof WP_Object_Cache && $wp_object_cache->flush();
	}

	/**
	 * Removes all cache items.
	 *
	 * @since 3.3.0
	 *
	 * @see WP_Object_Cache::flush()
	 *
	 * @return bool True on success, false on failure.
	 */
	function wp_cache_flush_runtime() {
		global $wp_object_cache;
		return $wp_object_cache instanceof WP_Object_Cache && $wp_object_cache->flush_runtime();
	}

	/**
	 * Removes all cache items in a group.
	 *
	 * @since 3.4.0
	 *
	 * @see WP_Object_Cache::flush_group()
	 *
	 * @param string $group Name of group to remove from cache.
	 * @return bool True on success, false on failure.
	 */
	function wp_cache_flush_group( $group ) {
		global $wp_object_cache;
		return $wp_object_cache instanceof WP_Object_Cache && $wp_object_cache->flush_group( $group );
	}

	/**
	 * Closes the cache.
	 *
	 * This function has ceased to do anything since WordPress 2.5. The
	 * functionality was removed along with the rest of the persistent cache.
	 *
	 * This does not mean that plugins can't implement this function when they need
	 * to make sure that the cache is cleaned up after WordPress no longer needs it.
	 *
	 * @since 3.0.0
	 *
	 * @return true Always returns true.
	 */
	function wp_cache_close() {
		return true;
	}

	/**
	 * Adds a group or set of groups to the list of global groups.
	 *
	 * @since 3.0.0
	 *
	 * @see WP_Object_Cache::add_global_groups()
	 *
	 * @param string|string[] $groups A group or an array of groups to add.
	 */
	function wp_cache_add_global_groups( $groups ) {
		global $wp_object_cache;
		if ( $wp_object_cache instanceof WP_Object_Cache ) {
			$wp_object_cache->add_global_groups( $groups );
		}
	}

	/**
	 * Adds a group or set of groups to the list of non-persistent groups.
	 *
	 * @since 3.0.0
	 *
	 * @param string|string[] $groups A group or an array of groups to add.
	 */
	function wp_cache_add_non_persistent_groups( $groups ) {
		global $wp_object_cache;
		if ( $wp_object_cache instanceof WP_Object_Cache ) {
			$wp_object_cache->add_non_persistent_groups( $groups );
		}
	}

	/**
	 * Switches the internal blog ID.
	 *
	 * This changes the blog id used to create keys in blog specific groups.
	 *
	 * @since 3.0.0
	 *
	 * @see WP_Object_Cache::switch_to_blog()
	 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
	 *
	 * @param int $blog_id Site ID.
	 */
	function wp_cache_switch_to_blog( $blog_id ) {
		global $wp_object_cache;
		if ( $wp_object_cache instanceof WP_Object_Cache ) {
			$wp_object_cache->switch_to_blog( $blog_id );
		}
	}

	/**
	 * Resets internal cache keys and structures.
	 *
	 * If the cache back end uses global blog or site IDs as part of its cache keys,
	 * this function instructs the back end to reset those keys and perform any cleanup
	 * since blog or site IDs have changed since cache init.
	 *
	 * This function is deprecated. Use wp_cache_switch_to_blog() instead of this
	 * function when preparing the cache for a blog switch. For clearing the cache
	 * during unit tests, consider using wp_cache_init(). wp_cache_init() is not
	 * recommended outside of unit tests as the performance penalty for using it is high.
	 *
	 * @since 3.0.0
	 * @deprecated 3.5.0 Use wp_cache_switch_to_blog()
	 * @see WP_Object_Cache::reset()
	 *
	 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
	 */
	function wp_cache_reset() {
		_deprecated_function( __FUNCTION__, '3.5', 'wp_cache_switch_to_blog()' );
		return false;
	}
}