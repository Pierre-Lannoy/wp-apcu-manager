<?php
/**
 * Autoload for APCu Manager.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

spl_autoload_register(
	function ( $class ) {
		$classname = $class;
		$filepath  = __DIR__ . '/';
		if ( strpos( $classname, 'APCuManager\\' ) === 0 ) {
			while ( strpos( $classname, '\\' ) !== false ) {
				$classname = substr( $classname, strpos( $classname, '\\' ) + 1, 1000 );
			}
			$filename = 'class-' . str_replace( '_', '-', strtolower( $classname ) ) . '.php';
			if ( strpos( $class, 'APCuManager\System\\' ) === 0 ) {
				$filepath = APCM_INCLUDES_DIR . 'system/';
			}
			if ( strpos( $class, 'APCuManager\Plugin\Feature\\' ) === 0 ) {
				$filepath = APCM_INCLUDES_DIR . 'features/';
			} elseif ( strpos( $class, 'APCuManager\Plugin\Integration\\' ) === 0 ) {
				$filepath = APCM_INCLUDES_DIR . 'integrations/';
			} elseif ( strpos( $class, 'APCuManager\Plugin\\' ) === 0 ) {
				$filepath = APCM_INCLUDES_DIR . 'plugin/';
			}
			if ( strpos( $class, 'APCuManager\Library\\' ) === 0 ) {
				$filepath = APCM_VENDOR_DIR;
			}
			if ( strpos( $filename, '-public' ) !== false ) {
				$filepath = APCM_PUBLIC_DIR;
			}
			if ( strpos( $filename, '-admin' ) !== false ) {
				$filepath = APCM_ADMIN_DIR;
			}
			$file = $filepath . $filename;
			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}
	}
);
