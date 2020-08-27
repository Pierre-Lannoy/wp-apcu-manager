<?php
/**
 * DataBeam integration
 *
 * Handles all DataBeam integration and queries.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace APCuManager\Plugin\Integration;

use APCuManager\System\Option;
use APCuManager\System\Role;
use APCuManager\Plugin\Core;

/**
 * Define the DataBeam integration.
 *
 * Handles all DataBeam integration and queries.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.1.1
 */
class Databeam {

	/**
	 * Init the class.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		add_filter( 'databeam_source_register', [ static::class, 'register_kpi' ] );
	}

	/**
	 * Register APCu kpis endpoints for DataBeam.
	 *
	 * @param   array   $integrations   The already registered integrations.
	 * @return  array   The new integrations.
	 * @since    1.0.0
	 */
	public static function register_kpi( $integrations ) {
		$integrations[ APCM_SLUG . '::kpi' ] = [
			'name'         => APCM_PRODUCT_NAME,
			'version'      => APCM_VERSION,
			'subname'      => __( 'KPIs', 'apcu-manager' ),
			'description'  => __( 'Allows to integrate, as a DataBeam source, all KPIs related to APCu.', 'apcu-manager' ),
			'instruction'  => __( 'Just add this and use it as source in your favorite visualizers and publishers.', 'apcu-manager' ),
			'note'         => __( 'In multisite environments, this source is available for all network sites.', 'apcu-manager' ),
			'legal'        =>
				[
					'author'  => 'Pierre Lannoy',
					'url'     => 'https://github.com/Pierre-Lannoy',
					'license' => 'gpl3',
				],
			'icon'         =>
				[
					'static' => [
						'class'  => '\APCuManager\Plugin\Core',
						'method' => 'get_base64_logo',
					],
				],
			'type'         => 'collection::kpi',
			'restrictions' => [ 'only_network' ],
			'ttl'          => '0-3600:300',
			'caching'      => [ 'locale' ],
			'data_call'    =>
				[
					'static' => [
						'class'  => '\APCuManager\Plugin\Feature\Analytics',
						'method' => 'get_status_kpi_collection',
					],
				],
			'data_args'    => [],
		];
		return $integrations;
	}

	/**
	 * Returns a base64 svg resource for the banner.
	 *
	 * @return string The svg resource as a base64.
	 * @since 1.0.0
	 */
	public static function get_base64_banner() {
		$filename = __DIR__ . '/banner.svg';
		if ( file_exists( $filename ) ) {
			// phpcs:ignore
			$content = @file_get_contents( $filename );
		} else {
			$content = '';
		}
		if ( $content ) {
			// phpcs:ignore
			return 'data:image/svg+xml;base64,' . base64_encode( $content );
		}
		return '';
	}

	/**
	 * Register server infos endpoints for DataBeam.
	 *
	 * @param   array   $integrations   The already registered integrations.
	 * @return  array   The new integrations.
	 * @since    1.0.0
	 */
	public static function register_info( $integrations ) {
		return $integrations;
	}

}
