<?php
/**
 * Global functions.
 *
 * @package Functions
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.4.1
 */


if ( ! function_exists('decalog_get_psr_log_version') ) {
	/**
	 * Get the needed version of PSR-3.
	 *
	 * @return  int  The PSR-3 needed version.
	 * @since 4.0.0
	 */
	function decalog_get_psr_log_version() {
		$required = 1;
		if ( ! defined( 'DECALOG_PSR_LOG_VERSION') ) {
			define( 'DECALOG_PSR_LOG_VERSION', 'V1' );
		}
		switch ( strtolower( DECALOG_PSR_LOG_VERSION ) ) {
			case 'v3':
				$required = 3;
				break;
			case 'auto':
				if ( class_exists( '\Psr\Log\NullLogger') ) {
					$reflection = new \ReflectionMethod(\Psr\Log\NullLogger::class, 'log');
					foreach ( $reflection->getParameters() as $param ) {
						if ( 'message' === $param->getName() ) {
							if ( str_contains($param->getType() ?? '', '|') ) {
								$required = 3;
							}
						}
					}
				}
		}
		return $required;
	}
}


/**
 * Verify if a timestamp is a TRUE unix timestamp or based on monotonic clock.
 * If it's based on monotonic clock, fix it.
 *
 * Patch done for supporting APCu > 5.1.21
 * @see https://stackoverflow.com/questions/74227993/php-apcu-monotonic-ttl-clock-change-please-confirm-my-understanding-of-timesta
 *
 * @since 3.4.1
 *
 * @param integer $value  A timestamp.
 * @return integer A true unix timestamp.
 */
function apcm_unix_ts( $value ) {
	if ( $value > 946681201 ) {
		return $value;
	}
	$time = time();
	if ( ! function_exists( 'hrtime' ) ) {
		return $time;
	}
	$hr = hrtime();
	if ( ! is_array( $hr ) ) {
		return $time;
	}
	$hrtime = (int) $hr[0];
	if ( $hrtime < $value ) {
		return $time;
	}
	return $time - $hrtime + $value;
}