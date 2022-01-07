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
if ( ! file_exists( $handler_file ) ) {
	exit;
}
require_once $handler_file;

/**
 * Adds data to the cache, if the cache key does not already exist.
 *
 * @param int|string $key The cache key to use for retrieval later
 * @param mixed $data The data to add to the cache store
 * @param string $group The group to add the cache to
 * @param int $expire When the cache data should be expired
 *
 * @return bool False if cache key and group already exist, true on success
 */
function wp_cache_add( $key, $data, $group = 'default', $expire = 0 ) {
	return WP_Object_Cache::instance()->add( $key, $data, $group, $expire );
}

/**
 * Closes the cache.
 *
 * This function has ceased to do anything since WordPress 2.5. The
 * functionality was removed along with the rest of the persistent cache. This
 * does not mean that plugins can't implement this function when they need to
 * make sure that the cache is cleaned up after WordPress no longer needs it.
 *
 * @return bool Always returns True
 */
function wp_cache_close() {
	return true;
}

/**
 * Decrement numeric cache item's value
 *
 * @param int|string $key The cache key to increment
 * @param int $offset The amount by which to decrement the item's value. Default is 1.
 * @param string $group The group the key is in.
 *
 * @return false|int False on failure, the item's new value on success.
 */
function wp_cache_decr( $key, $offset = 1, $group = 'default' ) {
	return WP_Object_Cache::instance()->decr( $key, $offset, $group );
}

/**
 * Removes the cache contents matching key and group.
 *
 * @param int|string $key What the contents in the cache are called
 * @param string $group Where the cache contents are grouped
 *
 * @return bool True on successful removal, false on failure
 */
function wp_cache_delete( $key, $group = 'default' ) {
	return WP_Object_Cache::instance()->delete( $key, $group );
}

/**
 * Removes all cache items.
 *
 * @return bool False on failure, true on success
 */
function wp_cache_flush() {
	return WP_Object_Cache::instance()->flush();
}

/**
 * Retrieves the cache contents from the cache by key and group.
 *
 * @param int|string $key What the contents in the cache are called
 * @param string $group Where the cache contents are grouped
 * @param bool $force Does nothing with APCu object cache
 * @param bool       &$found Whether key was found in the cache. Disambiguates a return of false, a storable value.
 *
 * @return bool|mixed False on failure to retrieve contents or the cache contents on success
 */
function wp_cache_get( $key, $group = 'default', $force = false, &$found = null ) {
	return WP_Object_Cache::instance()->get( $key, $group, $force, $found );
}

/**
 * Retrieve multiple values from cache.
 *
 * Gets multiple values from cache, including across multiple groups
 *
 * Usage: array( 'group0' => array( 'key0', 'key1', 'key2', ), 'group1' => array( 'key0' ) )
 *
 * @param array $groups Array of groups and keys to retrieve
 *
 * @return array Array of cached values as
 *    array( 'group0' => array( 'key0' => 'value0', 'key1' => 'value1', 'key2' => 'value2', ) )
 *    Non-existent keys are not returned.
 */
function wp_cache_get_multi( $groups ) {
	return WP_Object_Cache::instance()->get_multi( $groups );
}

/**
 * Increment numeric cache item's value
 *
 * @param int|string $key The cache key to increment
 * @param int $offset The amount by which to increment the item's value. Default is 1.
 * @param string $group The group the key is in.
 *
 * @return false|int False on failure, the item's new value on success.
 */
function wp_cache_incr( $key, $offset = 1, $group = 'default' ) {
	return WP_Object_Cache::instance()->incr( $key, $offset, $group );
}

/**
 * Sets up Object Cache Global and assigns it.
 *
 * @global WP_Object_Cache $wp_object_cache WordPress Object Cache
 */
function wp_cache_init() {
	$GLOBALS['wp_object_cache'] = WP_Object_Cache::instance();
}

/**
 * Replaces the contents of the cache with new data.
 *
 * @param int|string $key What to call the contents in the cache
 * @param mixed $data The contents to store in the cache
 * @param string $group Where to group the cache contents
 * @param int $expire When to expire the cache contents
 *
 * @return bool False if not exists, true if contents were replaced
 */
function wp_cache_replace( $key, $data, $group = 'default', $expire = 0 ) {
	return WP_Object_Cache::instance()->replace( $key, $data, $group, $expire );
}

/**
 * Saves the data to the cache.
 *
 * @param int|string $key What to call the contents in the cache
 * @param mixed $data The contents to store in the cache
 * @param string $group Where to group the cache contents
 * @param int $expire When to expire the cache contents
 *
 * @return bool False on failure, true on success
 */
function wp_cache_set( $key, $data, $group = 'default', $expire = 0 ) {
	return WP_Object_Cache::instance()->set( $key, $data, $group, $expire );
}

/**
 * Switch the internal blog id.
 *
 * This changes the blog id used to create keys in blog specific groups.
 *
 * @param int $blog_id Blog ID
 */
function wp_cache_switch_to_blog( $blog_id ) {
	WP_Object_Cache::instance()->switch_to_blog( $blog_id );
}

/**
 * Adds a group or set of groups to the list of global groups.
 *
 * @param string|array $groups A group or an array of groups to add
 */
function wp_cache_add_global_groups( $groups ) {
	WP_Object_Cache::instance()->add_global_groups( $groups );
}

/**
 * Adds a group or set of groups to the list of non-persistent groups.
 *
 * @param string|array $groups A group or an array of groups to add
 */
function wp_cache_add_non_persistent_groups( $groups ) {
	WP_Object_Cache::instance()->add_non_persistent_groups( $groups );
}

/**
 * Function was depreciated and now does nothing
 *
 * @return bool Always returns false
 */
function wp_cache_reset() {
	_deprecated_function( __FUNCTION__, '3.5', 'wp_cache_switch_to_blog()' );
	return false;
}

/**
 * Invalidate a site's object cache
 *
 * @param mixed $sites Sites ID's that want flushing.
 *                     Don't pass a site to flush current site
 *
 * @return bool
 */
function wp_cache_flush_site( $sites = null ) {
	return WP_Object_Cache::instance()->flush_sites( $sites );
}

/**
 * Invalidate a groups object cache
 *
 * @param mixed $groups A group or an array of groups to invalidate
 *
 * @return bool
 */
function wp_cache_flush_group( $groups = 'default' ) {
	return WP_Object_Cache::instance()->flush_groups( $groups );
}