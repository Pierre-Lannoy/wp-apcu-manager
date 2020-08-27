<?php
/**
 * APCu Manager analytics
 *
 * Handles all analytics operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace APCuManager\Plugin\Feature;

use APCuManager\Plugin\Feature\Schema;
use APCuManager\System\Cache;
use APCuManager\System\Date;
use APCuManager\System\Conversion;
use APCuManager\System\L10n;
use APCuManager\System\APCu;
use APCuManager\System\Timezone;
use APCuManager\System\UUID;
use APCuManager\System\Logger;
use APCuManager\Plugin\Feature\Capture;
use Feather;


/**
 * Define the analytics functionality.
 *
 * Handles all analytics operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Analytics {

	/**
	 * The start date.
	 *
	 * @since  1.0.0
	 * @var    string    $start    The start date.
	 */
	private $start = '';

	/**
	 * The end date.
	 *
	 * @since  1.0.0
	 * @var    string    $end    The end date.
	 */
	private $end = '';

	/**
	 * The period duration in days.
	 *
	 * @since  1.0.0
	 * @var    integer    $duration    The period duration in days.
	 */
	private $duration = 0;

	/**
	 * The timezone.
	 *
	 * @since  1.0.0
	 * @var    string    $timezone    The timezone.
	 */
	private $timezone = 'UTC';

	/**
	 * The main query filter.
	 *
	 * @since  1.0.0
	 * @var    array    $filter    The main query filter.
	 */
	private $filter = [];

	/**
	 * The query filter fro the previous range.
	 *
	 * @since  1.0.0
	 * @var    array    $previous    The query filter fro the previous range.
	 */
	private $previous = [];

	/**
	 * Is the start date today's date.
	 *
	 * @since  1.0.0
	 * @var    boolean    $today    Is the start date today's date.
	 */
	private $is_today = false;

	/**
	 * Colors for graphs.
	 *
	 * @since  1.0.0
	 * @var    array    $colors    The colors array.
	 */
	private $colors = [ '#73879C', '#3398DB', '#9B59B6', '#b2c326', '#BDC3C6' ];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string  $start   The start date.
	 * @param string  $end     The end date.
	 * @param boolean $reload  Is it a reload of an already displayed analytics.
	 * @since 1.0.0
	 */
	public function __construct( $start, $end, $reload ) {
		$this->timezone = Timezone::site_get();
		$this->start    = $start;
		$this->end      = $end;
		$datetime       = new \DateTime( 'now' );
		$this->is_today = ( $this->start === $datetime->format( 'Y-m-d' ) || $this->end === $datetime->format( 'Y-m-d' ) );
		$start          = Date::get_mysql_utc_from_date( $this->start . ' 00:00:00', $this->timezone->getName() );
		$end            = Date::get_mysql_utc_from_date( $this->end . ' 23:59:59', $this->timezone->getName() );
		$this->filter[] = "timestamp>='" . $start . "' and timestamp<='" . $end . "'";
		$start          = new \DateTime( $start, $this->timezone );
		$end            = new \DateTime( $end, $this->timezone );
		$start->sub( new \DateInterval( 'PT1S' ) );
		$end->sub( new \DateInterval( 'PT1S' ) );
		$delta = $start->diff( $end, true );
		if ( $delta ) {
			$start->sub( $delta );
			$end->sub( $delta );
		}
		$this->duration   = $delta->days + 1;
		$this->previous[] = "timestamp>='" . $start->format( 'Y-m-d H:i:s' ) . "' and timestamp<='" . $end->format( 'Y-m-d H:i:s' ) . "'";
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $query   The query type.
	 * @param   mixed  $queried The query params.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	public function query( $query, $queried ) {
		switch ( $query ) {
			case 'main-chart':
				return $this->query_chart();
			case 'kpi':
				return $this->query_kpi( $queried );
			case 'top-size':
				return $this->query_top( 'size', (int) $queried );
			case 'count':
				return $this->query_pie( 'count', (int) $queried );
		}
		return [];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type    The type of pie.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_pie( $type, $limit ) {
		$uuid  = UUID::generate_unique_id( 5 );
		$data  = Schema::get_grouped_detail( 'id', [], $this->filter, ! $this->is_today, '', [], false, 'ORDER BY avg_items DESC' );
		$total = 0;
		$other = 0;
		$found = false;
		foreach ( $data as $key => $row ) {
			if ( '-' === $row['id'] ) {
				$other = $row['avg_items'];
				$total = $row['avg_items'];
				$found = $key;
				break;
			}
		}
		if ( false !== $found ) {
			unset( $data[ $found ] );
			$data = array_values( $data );
		}
		foreach ( $data as $key => $row ) {
			$total = $total + $row['avg_items'];
			if ( $limit <= $key ) {
				$other = $other + $row['avg_items'];
			}
		}
		if ( 0 < $other ) {
			--$limit;
		}
		$cpt     = 0;
		$plugins = apply_filters( 'perfopsone_plugin_info', [ 'w3tc' => [ 'name' => 'W3 Total Cache' ] ] );
		$labels  = [];
		$series  = [];
		while ( $cpt < $limit && array_key_exists( $cpt, $data ) ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $data[ $cpt ]['avg_items'] / $total, 1 );
			} else {
				$percent = 100;
			}
			if ( 0.1 > $percent ) {
				$percent = 0.1;
			}
			$meta = $data[ $cpt ]['id'];
			if ( array_key_exists( $meta, $plugins ) ) {
				if ( array_key_exists( 'name', $plugins[ $meta ] ) ) {
					$meta = $plugins[ $meta ]['name'];
				}
			}
			$labels[] = $meta;
			$series[] = [
				'meta'  => $meta,
				'value' => (float) $percent,
			];
			++$cpt;
		}
		if ( 0 < $other ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $other / $total, 1 );
			} else {
				$percent = 100;
			}
			if ( 0.1 > $percent ) {
				$percent = 0.1;
			}
			$labels[] = esc_html__( 'Other', 'apcu-manager' );
			$series[] = [
				'meta'  => esc_html__( 'Other', 'apcu-manager' ),
				'value' => (float) $percent,
			];
		}
		$result  = '<div class="apcm-pie-box">';
		$result .= '<div class="apcm-pie-graph">';
		$result .= '<div class="apcm-pie-graph-handler" id="apcm-pie-id"></div>';
		$result .= '</div>';
		$result .= '<div class="apcm-pie-legend">';
		foreach ( $labels as $key => $label ) {
			$icon    = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'square', $this->colors[ $key ], $this->colors[ $key ] ) . '" />';
			$result .= '<div class="apcm-pie-legend-item">' . $icon . '&nbsp;&nbsp;' . $label . '</div>';
		}
		$result .= '';
		$result .= '</div>';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var data' . $uuid . ' = ' . wp_json_encode(
				[
					'labels' => $labels,
					'series' => $series,
				]
			) . ';';
		$result .= ' var tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: true, appendToBody: true});';
		$result .= ' var option' . $uuid . ' = {width: 180, height: 180, showLabel: false, donut: true, donutWidth: "36%", startAngle: 270, plugins: [tooltip' . $uuid . ']};';
		$result .= ' new Chartist.Pie("#apcm-pie-id", data' . $uuid . ', option' . $uuid . ');';
		$result .= '});';
		$result .= '</script>';
		return [ 'apcm-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type    The type of top.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_top( $type, $limit ) {
		$data  = Schema::get_grouped_detail( 'id', [], $this->filter, ! $this->is_today, '', [], false, 'ORDER BY avg_size DESC' );
		$total = 0;
		$other = 0;
		$found = false;
		foreach ( $data as $key => $row ) {
			if ( '-' === $row['id'] ) {
				$other = $row['avg_size'];
				$total = $row['avg_size'];
				$found = $key;
				break;
			}
		}
		if ( false !== $found ) {
			unset( $data[ $found ] );
			$data = array_values( $data );
		}
		foreach ( $data as $key => $row ) {
			$total = $total + $row['avg_size'];
			if ( $limit <= $key ) {
				$other = $other + $row['avg_size'];
			}
		}
		$result  = '';
		$cpt     = 0;
		$plugins = apply_filters( 'perfopsone_plugin_info', [ 'w3tc' => [ 'name' => 'W3 Total Cache' ] ] );
		while ( $cpt < $limit && array_key_exists( $cpt, $data ) ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $data[ $cpt ]['avg_size'] / $total, 1 );
			} else {
				$percent = 100;
			}
			if ( 0.5 > $percent ) {
				$percent = 0.5;
			}
			$icon = Feather\Icons::get_base64( 'package', 'none', '#73879C' );
			$name = $data[ $cpt ]['id'];
			if ( array_key_exists( $name, $plugins ) ) {
				if ( array_key_exists( 'icon', $plugins[ $name ] ) ) {
					$icon = $plugins[ $name ]['icon'];
				}
				if ( array_key_exists( 'name', $plugins[ $name ] ) ) {
					$name = $plugins[ $name ]['name'];
				}
			}
			$result .= '<div class="apcm-top-line">';
			$result .= '<div class="apcm-top-line-title">';
			$result .= '<img style="width:16px;vertical-align:bottom;" src="' . $icon . '" />&nbsp;&nbsp;<span class="apcm-top-line-title-text">' . $name . '</span>';
			$result .= '</div>';
			$result .= '<div class="apcm-top-line-content">';
			$result .= '<div class="apcm-bar-graph"><div class="apcm-bar-graph-value" style="width:' . $percent . '%"></div></div>';
			$result .= '<div class="apcm-bar-detail">' . Conversion::data_shorten( $data[ $cpt ]['avg_size'], 2, false, '&nbsp;' ) . '</div>';
			$result .= '</div>';
			$result .= '</div>';
			++$cpt;
		}
		if ( 0 < $total ) {
			$percent = round( 100 * $other / $total, 1 );
		} else {
			$percent = 100;
		}
		$result .= '<div class="apcm-top-line apcm-minor-data">';
		$result .= '<div class="apcm-top-line-title">';
		$result .= '<span class="apcm-top-line-title-text">' . esc_html__( 'Other', 'apcu-manager' ) . '</span>';
		$result .= '</div>';
		$result .= '<div class="apcm-top-line-content">';
		$result .= '<div class="apcm-bar-graph"><div class="apcm-bar-graph-value" style="width:' . $percent . '%"></div></div>';
		$result .= '<div class="apcm-bar-detail">' . Conversion::data_shorten( $other, 2, false, '&nbsp;' ) . '</div>';
		$result .= '</div>';
		$result .= '</div>';
		return [ 'apcm-top-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @return array The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_chart() {
		$uuid       = UUID::generate_unique_id( 5 );
		$query      = Schema::get_time_series( $this->filter, ! $this->is_today, '', [], false );
		$data       = [];
		$series     = [];
		$labels     = [];
		$items      = [ 'status', 'mem_total', 'mem_used', 'slot_total', 'slot_used', 'hit', 'miss', 'ins', 'frag_small', 'frag_big' ];
		$maxhit     = 0;
		$maxstrings = 0;
		$maxscripts = 0;
		// Data normalization.
		if ( 0 !== count( $query ) ) {
			if ( 1 === $this->duration ) {
				$start  = new \DateTime( Date::get_mysql_utc_from_date( $this->start . ' 00:00:00', $this->timezone->getName() ), new \DateTimeZone( 'UTC' ) );
				$real   = new \DateTime( array_values( $query )[0]['timestamp'], new \DateTimeZone( 'UTC' ) );
				$offset = $this->timezone->getOffset( $real );
				$ts     = $start->getTimestamp();
				$record = [];
				foreach ( $items as $item ) {
					$record[ $item ] = 0;
				}
				while ( 300 + Capture::$delta < $real->getTimestamp() - $ts ) {
					$ts                    = $ts + 300;
					$data[ $ts + $offset ] = $record;
				}
				foreach ( $query as $timestamp => $row ) {
					$datetime    = new \DateTime( $timestamp, new \DateTimeZone( 'UTC' ) );
					$offset      = $this->timezone->getOffset( $datetime );
					$ts          = $datetime->getTimestamp() + $offset;
					$data[ $ts ] = $row;
				}
				$end       = new \DateTime( Date::get_mysql_utc_from_date( $this->end . ' 23:59:59', $this->timezone->getName() ), $this->timezone );
				$end       = $end->getTimestamp();
				$datetime  = new \DateTime( $timestamp, new \DateTimeZone( 'UTC' ) );
				$offset    = $this->timezone->getOffset( $datetime );
				$timestamp = $datetime->getTimestamp() + 300;
				while ( $timestamp <= $end + $offset ) {
					$datetime = new \DateTime( date( 'Y-m-d H:i:s', $timestamp ), new \DateTimeZone( 'UTC' ) );
					$offset   = $this->timezone->getOffset( $datetime );
					$ts       = $datetime->getTimestamp() + $offset;
					$record   = [];
					foreach ( $items as $item ) {
						$record[ $item ] = 0;
					}
					$data[ $ts ] = $record;
					$timestamp   = $timestamp + 300;
				}
			} else {
				$buffer = [];
				foreach ( $query as $timestamp => $row ) {
					$datetime = new \DateTime( $timestamp, new \DateTimeZone( 'UTC' ) );
					$datetime->setTimezone( $this->timezone );
					$buffer[ $datetime->format( 'Y-m-d' ) ][] = $row;
				}
				foreach ( $buffer as $timestamp => $rows ) {
					$record = [];
					foreach ( $items as $item ) {
						$record[ $item ] = 0;
					}
					foreach ( $rows as $row ) {
						foreach ( $items as $item ) {
							if ( 'status' === $item ) {
								$record[ $item ] = ( 'disabled' === $row[ $item ] ? 0 : 100 );
							} else {
								$record[ $item ] = $record[ $item ] + $row[ $item ];
							}
						}
					}
					$cpt = count( $rows );
					if ( 0 < $cpt ) {
						foreach ( $items as $item ) {
							$record[ $item ] = (int) round( $record[ $item ] / $cpt, 0 );
						}
					}
					$data[ strtotime( $timestamp . ' 12:00:00' ) ] = $record;
				}
			}
			// Series computation.
			foreach ( $data as $timestamp => $datum ) {
				$ts = 'new Date(' . (string) $timestamp . '000)';
				// Hit ratio.
				$val = 'null';
				if ( 0 !== (int) $datum['hit'] + (int) $datum['miss'] ) {
					$val = round( 100 * $datum['hit'] / ( $datum['hit'] + $datum['miss'] ), 3 );
				}
				$series['ratio'][] = [
					'x' => $ts,
					'y' => $val,
				];
				// Fragmentation.
				$val = 'null';
				if ( 0 !== (int) $datum['frag_small'] + (int) $datum['frag_big'] ) {
					$val = round( 100 * $datum['frag_small'] / ( $datum['frag_small'] + $datum['frag_big'] ), 3 );
				}
				$series['fragmentation'][] = [
					'x' => $ts,
					'y' => $val,
				];
				// Availablility.
				$series['availability'][] = [
					'x' => $ts,
					'y' => ( 'disabled' === $datum['status'] ? 0 : 100 ),
				];
				// Time series.
				foreach ( [ 'hit', 'miss', 'ins', 'slot_used' ] as $item ) {
					$val               = (int) $datum[ $item ];
					$series[ $item ][] = [
						'x' => $ts,
						'y' => $val,
					];
					switch ( $item ) {
						case 'hit':
						case 'miss':
						case 'ins':
							if ( $maxhit < $val ) {
								$maxhit = $val;
							}
							break;
						case 'slot_used':
							if ( $maxstrings < $val ) {
								$maxstrings = $val;
							}
							break;
					}
				}
				// Time series (free vs.used).
				foreach ( [ 'slot', 'mem' ] as $item ) {
					if ( 'slot' === $item ) {
						$factor = 1000;
					} else {
						$factor = 1024 * 1024;
					}
					$series[ $item ][0][] = round( $datum[ $item . '_used' ] / $factor, 2 );
					$series[ $item ][1][] = round( ( $datum[ $item . '_total' ] - $datum[ $item . '_used' ] ) / $factor, 2 );
				}
				// Labels.
				if ( 1 < $this->duration ) {
					$labels[] = 'moment(' . $timestamp . '000).format("ll")';
				} else {
					$control = ( $timestamp % 86400 ) % ( 3 * HOUR_IN_SECONDS );
					if ( 300 > $control ) {
						if ( 0 !== (int) floor( ( $timestamp % 86400 ) / ( HOUR_IN_SECONDS ) ) ) {
							$hour = (string) (int) floor( ( $timestamp % 86400 ) / ( HOUR_IN_SECONDS ) );
							if ( 1 === strlen( $hour ) ) {
								$hour = '0' . $hour;
							}
							$labels[] = $hour . ':00';
						} else {
							$labels[] = 'null';
						}
					} else {
						$labels[] = 'null';
					}
				}
			}
			// Result encoding.
			if ( 1 < $this->duration ) {
				$shift = 86400;
			} else {
				$shift = 0;
			}
			$datetime = new \DateTime( $this->start . ' 00:00:00', $this->timezone );
			$offset   = $this->timezone->getOffset( $datetime );
			$datetime = $datetime->getTimestamp() + $offset;
			$before   = [
				'x' => 'new Date(' . (string) ( $datetime - $shift ) . '000)',
				'y' => 'null',
			];
			$datetime = new \DateTime( $this->end . ' 23:59:59', $this->timezone );
			$offset   = $this->timezone->getOffset( $datetime );
			$datetime = $datetime->getTimestamp() + $offset;
			$after    = [
				'x' => 'new Date(' . (string) ( $datetime + $shift ) . '000)',
				'y' => 'null',
			];
			// Hit ratio.
			array_unshift( $series['ratio'], $before );
			$series['ratio'][] = $after;
			$json_ratio        = wp_json_encode(
				[
					'series' => [
						[
							'name' => esc_html_x( 'Hit Ratio', 'Noun - Cache hit ratio.', 'apcu-manager' ),
							'data' => $series['ratio'],
						],
					],
				]
			);
			$json_ratio        = str_replace( '"x":"new', '"x":new', $json_ratio );
			$json_ratio        = str_replace( ')","y"', '),"y"', $json_ratio );
			$json_ratio        = str_replace( '"null"', 'null', $json_ratio );

			// Hit, miss & inserts distribution.
			array_unshift( $series['hit'], $before );
			$series['hit'][] = $after;
			array_unshift( $series['miss'], $before );
			$series['miss'][] = $after;
			array_unshift( $series['ins'], $before );
			$series['ins'][] = $after;
			$json_hit        = wp_json_encode(
				[
					'series' => [
						[
							'name' => esc_html__( 'Hit Count', 'apcu-manager' ),
							'data' => $series['hit'],
						],
						[
							'name' => esc_html__( 'Miss Count', 'apcu-manager' ),
							'data' => $series['miss'],
						],
						[
							'name' => esc_html__( 'Insert Count', 'apcu-manager' ),
							'data' => $series['ins'],
						],
					],
				]
			);
			$json_hit        = str_replace( '"x":"new', '"x":new', $json_hit );
			$json_hit        = str_replace( ')","y"', '),"y"', $json_hit );
			$json_hit        = str_replace( '"null"', 'null', $json_hit );

			// Memory distribution.
			$json_memory = wp_json_encode(
				[
					'labels' => $labels,
					'series' => [
						[
							'name' => esc_html__( 'Used Memory', 'apcu-manager' ),
							'data' => $series['mem'][0],
						],
						[
							'name' => esc_html__( 'Free Memory', 'apcu-manager' ),
							'data' => $series['mem'][1],
						],
					],
				]
			);
			$json_memory = str_replace( '"null"', 'null', $json_memory );
			$json_memory = str_replace( '"labels":["moment', '"labels":[moment', $json_memory );
			$json_memory = str_replace( '","moment', ',moment', $json_memory );
			$json_memory = str_replace( '"],"series":', '],"series":', $json_memory );
			$json_memory = str_replace( '\\"', '"', $json_memory );

			// Objects variation.
			array_unshift( $series['slot_used'], $before );
			$series['slot_used'][] = $after;
			$json_scripts          = wp_json_encode(
				[
					'series' => [
						[
							'name' => esc_html__( 'Objects Count', 'apcu-manager' ),
							'data' => $series['slot_used'],
						],
					],
				]
			);
			$json_scripts          = str_replace( '"x":"new', '"x":new', $json_scripts );
			$json_scripts          = str_replace( ')","y"', '),"y"', $json_scripts );
			$json_scripts          = str_replace( '"null"', 'null', $json_scripts );

			// Key.
			$json_key = wp_json_encode(
				[
					'labels' => $labels,
					'series' => [
						[
							'name' => esc_html__( 'Used Key Slots', 'apcu-manager' ),
							'data' => $series['slot'][0],
						],
						[
							'name' => esc_html__( 'Free Key Slots', 'apcu-manager' ),
							'data' => $series['slot'][1],
						],
					],
				]
			);
			$json_key = str_replace( '"null"', 'null', $json_key );
			$json_key = str_replace( '"labels":["moment', '"labels":[moment', $json_key );
			$json_key = str_replace( '","moment', ',moment', $json_key );
			$json_key = str_replace( '"],"series":', '],"series":', $json_key );
			$json_key = str_replace( '\\"', '"', $json_key );

			// Fragmentation variation.
			array_unshift( $series['fragmentation'], $before );
			$series['fragmentation'][] = $after;
			$json_strings              = wp_json_encode(
				[
					'series' => [
						[
							'name' => esc_html__( 'Fragmentation', 'apcu-manager' ),
							'data' => $series['fragmentation'],
						],
					],
				]
			);
			$json_strings              = str_replace( '"x":"new', '"x":new', $json_strings );
			$json_strings              = str_replace( ')","y"', '),"y"', $json_strings );
			$json_strings              = str_replace( '"null"', 'null', $json_strings );

			// Availability.
			array_unshift( $series['availability'], $before );
			$series['availability'][] = $after;
			$json_availability        = wp_json_encode(
				[
					'series' => [
						[
							'name' => esc_html__( 'Availability', 'apcu-manager' ),
							'data' => $series['availability'],
						],
					],
				]
			);
			$json_availability        = str_replace( '"x":"new', '"x":new', $json_availability );
			$json_availability        = str_replace( ')","y"', '),"y"', $json_availability );
			$json_availability        = str_replace( '"null"', 'null', $json_availability );

			// Rendering.
			if ( 4 < $this->duration ) {
				if ( 1 === $this->duration % 2 ) {
					$divisor = 6;
				} else {
					$divisor = 5;
				}
			} else {
				$divisor = $this->duration + 1;
			}
			$result  = '<div class="apcm-multichart-handler">';
			$result .= '<div class="apcm-multichart-item active" id="apcm-chart-ratio">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var ratio_data' . $uuid . ' = ' . $json_ratio . ';';
			$result .= ' var ratio_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var ratio_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: true,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [ratio_tooltip' . $uuid . '],';
			if ( 1 < $this->duration ) {
				$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
			} else {
				$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:8, labelInterpolationFnc: function (value) {var shift=0;if(moment(value).isDST()){shift=3600000};return moment(value-shift).format("HH:00");}},';
			}
			$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString() + " %";}},';
			$result .= ' };';
			$result .= ' new Chartist.Line("#apcm-chart-ratio", ratio_data' . $uuid . ', ratio_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="apcm-multichart-item" id="apcm-chart-uptime">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var uptime_data' . $uuid . ' = ' . $json_availability . ';';
			$result .= ' var uptime_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var uptime_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: true,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [uptime_tooltip' . $uuid . '],';
			if ( 1 < $this->duration ) {
				$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("ll");}},';
			} else {
				$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:8, labelInterpolationFnc: function (value) {var shift=0;if(moment(value).isDST()){shift=3600000};return moment(value-shift).format("HH:00");}},';
			}
			$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString() + " %";}},';
			$result .= ' };';
			$result .= ' new Chartist.Line("#apcm-chart-uptime", uptime_data' . $uuid . ', uptime_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="apcm-multichart-item" id="apcm-chart-hit">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var hit_data' . $uuid . ' = ' . $json_hit . ';';
			$result .= ' var hit_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var hit_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: true,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [hit_tooltip' . $uuid . '],';
			if ( 1 < $this->duration ) {
				$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("ll");}},';
			} else {
				$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:8, labelInterpolationFnc: function (value) {var shift=0;if(moment(value).isDST()){shift=3600000};return moment(value-shift).format("HH:00");}},';
			}
			if ( $maxhit < 1000 ) {
				$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString();}},';
			} elseif ( $maxhit < 1000000 ) {
				$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {value = value / 1000; return value.toString() + " K";}},';
			} else {
				$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {value = value / 1000000; return value.toString() + " M";}},';
			}
			$result .= ' };';
			$result .= ' new Chartist.Line("#apcm-chart-hit", hit_data' . $uuid . ', hit_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="apcm-multichart-item" id="apcm-chart-string">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var string_data' . $uuid . ' = ' . $json_strings . ';';
			$result .= ' var string_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var string_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: true,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [string_tooltip' . $uuid . '],';
			if ( 1 < $this->duration ) {
				$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("ll");}},';
			} else {
				$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:8, labelInterpolationFnc: function (value) {var shift=0;if(moment(value).isDST()){shift=3600000};return moment(value-shift).format("HH:00");}},';
			}
			$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString() + " %";}},';
			$result .= ' };';
			$result .= ' new Chartist.Line("#apcm-chart-string", string_data' . $uuid . ', string_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="apcm-multichart-item" id="apcm-chart-file">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var file_data' . $uuid . ' = ' . $json_scripts . ';';
			$result .= ' var file_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var file_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: true,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [file_tooltip' . $uuid . '],';
			if ( 1 < $this->duration ) {
				$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("ll");}},';
			} else {
				$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:8, labelInterpolationFnc: function (value) {var shift=0;if(moment(value).isDST()){shift=3600000};return moment(value-shift).format("HH:00");}},';
			}
			if ( $maxscripts < 1000 ) {
				$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString();}},';
			} elseif ( $maxscripts < 1000000 ) {
				$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {value = value / 1000; return value.toString() + " K";}},';
			} else {
				$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {value = value / 1000000; return value.toString() + " M";}},';
			}
			$result .= ' };';
			$result .= ' new Chartist.Line("#apcm-chart-file", file_data' . $uuid . ', file_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="apcm-multichart-item" id="apcm-chart-memory">';
			$result .= '<style>';
			if ( 1 < $this->duration ) {
				$result .= '.apcm-multichart-item .ct-bar {stroke-width: 20px !important;stroke-opacity: 0.8 !important;}';
			} else {
				$result .= '.apcm-multichart-item .ct-bar {stroke-width: 3px !important;stroke-opacity: 0.8 !important;}';
			}
			$result .= '</style>';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var memory_data' . $uuid . ' = ' . $json_memory . ';';
			$result .= ' var memory_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var memory_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  stackBars: true,';
			$result .= '  stackMode: "accumulate",';
			$result .= '  seriesBarDistance: 1,';
			$result .= '  plugins: [memory_tooltip' . $uuid . '],';
			if ( 1 < $this->duration ) {
				$result .= '  axisX: {showGrid: false, labelOffset: {x: 18,y: 0}},';
			} else {
				$result .= '  axisX: {showGrid: true, labelOffset: {x: 18,y: 0}},';
			}
			$result .= '  axisY: {showGrid: true, labelInterpolationFnc: function (value) {return value.toString() + " ' . esc_html_x( 'MB', 'Abbreviation - Stands for "megabytes".', 'apcu-manager' ) . '";}},';
			$result .= ' };';
			$result .= ' new Chartist.Bar("#apcm-chart-memory", memory_data' . $uuid . ', memory_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="apcm-multichart-item" id="apcm-chart-key">';
			$result .= '<style>';
			if ( 1 < $this->duration ) {
				$result .= '.apcm-multichart-item .ct-bar {stroke-width: 20px !important;stroke-opacity: 0.8 !important;}';
			} else {
				$result .= '.apcm-multichart-item .ct-bar {stroke-width: 3px !important;stroke-opacity: 0.8 !important;}';
			}
			$result .= '</style>';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var key_data' . $uuid . ' = ' . $json_key . ';';
			$result .= ' var key_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var key_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  stackBars: true,';
			$result .= '  stackMode: "accumulate",';
			$result .= '  seriesBarDistance: 1,';
			$result .= '  plugins: [key_tooltip' . $uuid . '],';
			if ( 1 < $this->duration ) {
				$result .= '  axisX: {showGrid: false, labelOffset: {x: 18,y: 0}},';
			} else {
				$result .= '  axisX: {showGrid: true, labelOffset: {x: 18,y: 0}},';
			}
			$result .= '  axisY: {showGrid: true, labelInterpolationFnc: function (value) {return value.toString() + " ' . esc_html_x( 'K', 'Abbreviation - Stands for "thousand".', 'apcu-manager' ) . '";}},';
			$result .= ' };';
			$result .= ' new Chartist.Bar("#apcm-chart-key", key_data' . $uuid . ', key_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="apcm-multichart-item" id="apcm-chart-data">';
			$result .= '</div>';
		} else {
			$result  = '<div class="apcm-multichart-handler">';
			$result .= '<div class="apcm-multichart-item active" id="apcm-chart-ratio">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="apcm-multichart-item" id="apcm-chart-uptime">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="apcm-multichart-item" id="apcm-chart-hit">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="apcm-multichart-item" id="apcm-chart-string">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="apcm-multichart-item" id="apcm-chart-file">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="apcm-multichart-item" id="apcm-chart-memory">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="apcm-multichart-item" id="apcm-chart-key">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="apcm-multichart-item" id="apcm-chart-data">';
			$result .= '</div>';
		}
		return [ 'apcm-main-chart' => $result ];
	}

	/**
	 * Query all kpis in statistics table.
	 *
	 * @param   array   $args   Optional. The needed args.
	 * @return array  The KPIs ready to send.
	 * @since    1.0.0
	 */
	public static function get_status_kpi_collection( $args = [] ) {
		$result['meta'] = [
			'plugin' => APCM_PRODUCT_NAME . ' ' . APCM_VERSION,
			'apcu'   => APCu::name(),
			'period' => date( 'Y-m-d' ),
		];
		$result['data'] = [];
		$kpi            = new static( date( 'Y-m-d' ), date( 'Y-m-d' ), false );
		foreach ( [ 'ratio', 'memory', 'key', 'fragmentation', 'uptime', 'object' ] as $query ) {
			$data = $kpi->query_kpi( $query, false );

			switch ( $query ) {
				case 'ratio':
					$val                   = Conversion::number_shorten( $data['kpi-bottom-ratio'], 0, true );
					$result['data']['hit'] = [
						'name'        => esc_html_x( 'Hits', 'Noun - Cache hit.', 'apcu-manager' ),
						'short'       => esc_html_x( 'Hits', 'Noun - Short (max 4 char) - Cache hit.', 'apcu-manager' ),
						'description' => esc_html__( 'Successful calls to the cache.', 'apcu-manager' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-ratio'] / 100, 6 ),
							'percent'  => round( $data['kpi-main-ratio'], 2 ),
							'permille' => round( $data['kpi-main-ratio'] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-ratio'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-ratio'], 2 ),
							'permille' => round( $data['kpi-index-ratio'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-ratio'],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'memory':
					$val                      = Conversion::data_shorten( $data['kpi-bottom-memory'], 0, true );
					$result['data']['memory'] = [
						'name'        => esc_html_x( 'Free memory', 'Noun - Memory free of allocation.', 'apcu-manager' ),
						'short'       => esc_html_x( 'Mem.', 'Noun - Short (max 4 char) - Memory free of allocation.', 'apcu-manager' ),
						'description' => esc_html__( 'Free memory available for APCu.', 'apcu-manager' ),
						'dimension'   => 'memory',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-memory'] / 100, 6 ),
							'percent'  => round( $data['kpi-main-memory'], 2 ),
							'permille' => round( $data['kpi-main-memory'] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-memory'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-memory'], 2 ),
							'permille' => round( $data['kpi-index-memory'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-memory'],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'object':
					$val                      = Conversion::number_shorten( $data['kpi-main-object'], 0, true );
					$result['data']['object'] = [
						'name'        => esc_html_x( 'Objects', 'Noun - Cached objects.', 'apcu-manager' ),
						'short'       => esc_html_x( 'Obj.', 'Noun - Short (max 4 char) - Cached objects.', 'apcu-manager' ),
						'description' => esc_html__( 'Objects actually present in cache.', 'apcu-manager' ),
						'dimension'   => 'none',
						'ratio'       => null,
						'variation'   => [
							'raw'      => round( $data['kpi-index-object'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-object'], 2 ),
							'permille' => round( $data['kpi-index-object'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-main-object'],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'key':
					$val                   = Conversion::number_shorten( $data['kpi-bottom-key'], 0, true );
					$result['data']['key'] = [
						'name'        => esc_html_x( 'Keys', 'Noun - Allocated keys.', 'apcu-manager' ),
						'short'       => esc_html_x( 'Keys', 'Noun - Short (max 4 char) - Allocated keys.', 'apcu-manager' ),
						'description' => esc_html__( 'Keys allocated by APCu.', 'apcu-manager' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-key'] / 100, 6 ),
							'percent'  => round( $data['kpi-main-key'], 2 ),
							'permille' => round( $data['kpi-main-key'] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-key'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-key'], 2 ),
							'permille' => round( $data['kpi-index-key'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-key'],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'fragmentation':
					$val                     = Conversion::number_shorten( $data['kpi-bottom-fragmentation'], 0, true );
					$result['data']['block'] = [
						'name'        => esc_html_x( 'Small blocks', 'Noun - Small blocks.', 'apcu-manager' ),
						'short'       => esc_html_x( 'Blk.', 'Noun - Short (max 4 char) - Small blocks.', 'apcu-manager' ),
						'description' => esc_html__( 'Used memory small blocks (size < 5M).', 'apcu-manager' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-fragmentation'] / 100, 6 ),
							'percent'  => round( $data['kpi-main-fragmentation'], 2 ),
							'permille' => round( $data['kpi-main-fragmentation'] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-fragmentation'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-fragmentation'], 2 ),
							'permille' => round( $data['kpi-index-fragmentation'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-fragmentation'],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'uptime':
					$result['data']['uptime'] = [
						'name'        => esc_html_x( 'Availability', 'Noun - Extrapolated availability time over 24 hours.', 'apcu-manager' ),
						'short'       => esc_html_x( 'Avl.', 'Noun - Short (max 4 char) - Extrapolated availability time over 24 hours.', 'apcu-manager' ),
						'description' => esc_html__( 'Extrapolated availability time over 24 hours.', 'apcu-manager' ),
						'dimension'   => 'time',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-uptime'] / 100, 6 ),
							'percent'  => round( $data['kpi-main-uptime'], 2 ),
							'permille' => round( $data['kpi-main-uptime'] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-uptime'] / 100, 6 ),
							'percent'  => round( $data['kpi-index-uptime'], 2 ),
							'permille' => round( $data['kpi-index-uptime'] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-uptime'],
							'human' => implode( ', ', Date::get_age_array_from_seconds( $data['kpi-bottom-uptime'], true, true ) ),
						],
					];
					break;
			}
		}
		$result['assets'] = [];
		return $result;
	}

	/**
	 * Query statistics table.
	 *
	 * @param   mixed       $queried The query params.
	 * @param   boolean     $chart   Optional, return the chart if true, only the data if false;
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	public function query_kpi( $queried, $chart = true ) {
		$result = [];
		if ( 'ratio' === $queried || 'memory' === $queried || 'key' === $queried || 'fragmentation' === $queried || 'uptime' === $queried ) {
			$data        = Schema::get_std_kpi( $this->filter, ! $this->is_today );
			$pdata       = Schema::get_std_kpi( $this->previous );
			$base_value  = 0.0;
			$pbase_value = 0.0;
			$data_value  = 0.0;
			$pdata_value = 0.0;
			$current     = 0.0;
			$previous    = 0.0;
			if ( 'uptime' === $queried ) {
				$disabled_data  = Schema::get_std_kpi( $this->filter, ! $this->is_today, 'status', ['disabled'] );
				$disabled_pdata = Schema::get_std_kpi( $this->previous, true,  'status', ['disabled'] );
				if ( is_array( $data ) && array_key_exists( 'records', $data ) && is_array( $disabled_data ) && array_key_exists( 'records', $disabled_data ) ) {
					if ( empty( $data['records'] ) ) {
						$data['records'] = 0;
					}
					if ( ! is_array( $disabled_data ) || ! array_key_exists( 'records', $disabled_data ) ) {
						$disabled_data['records'] = 0;
					}
					$base_value = (float) $data['records'] + $disabled_data['records'];
					$data_value = (float) $data['records'];
				}
				if ( is_array( $pdata ) && array_key_exists( 'records', $pdata ) && is_array( $disabled_pdata ) && array_key_exists( 'records', $disabled_pdata ) ) {
					if ( empty( $pdata['records'] ) ) {
						$pdata['records'] = 0;
					}
					if ( ! is_array( $disabled_pdata ) || ! array_key_exists( 'records', $disabled_pdata ) ) {
						$disabled_pdata['records'] = 0;
					}
					$pbase_value = (float) $pdata['records'] + $disabled_pdata['records'];
					$pdata_value = (float) $pdata['records'];
				}
			}
			if ( 'ratio' === $queried ) {
				if ( is_array( $data ) && array_key_exists( 'avg_hit', $data ) && ! empty( $data['avg_hit'] ) && array_key_exists( 'avg_miss', $data ) && ! empty( $data['avg_miss'] ) ) {
					$base_value = (float) $data['avg_hit'] + (float) $data['avg_miss'];
					$data_value = (float) $data['avg_hit'];
				}
				if ( is_array( $pdata ) && array_key_exists( 'avg_hit', $pdata ) && ! empty( $pdata['avg_hit'] ) && array_key_exists( 'avg_miss', $pdata ) && ! empty( $pdata['avg_miss'] ) ) {
					$pbase_value = (float) $pdata['avg_hit'] + (float) $pdata['avg_miss'];
					$pdata_value = (float) $pdata['avg_hit'];
				}
			}
			if ( 'key' === $queried ) {
				if ( is_array( $data ) && array_key_exists( 'avg_slot_used', $data ) && ! empty( $data['avg_slot_used'] ) && array_key_exists( 'avg_slot_total', $data ) && ! empty( $data['avg_slot_total'] ) ) {
					$base_value = (float) $data['avg_slot_total'];
					$data_value = (float) $data['avg_slot_used'];
				}
				if ( is_array( $pdata ) && array_key_exists( 'avg_slot_used', $pdata ) && ! empty( $pdata['avg_slot_used'] ) && array_key_exists( 'avg_slot_total', $pdata ) && ! empty( $pdata['avg_slot_total'] ) ) {
					$pbase_value = (float) $pdata['avg_slot_total'];
					$pdata_value = (float) $pdata['avg_slot_used'];
				}
			}
			if ( 'fragmentation' === $queried ) {
				if ( is_array( $data ) && array_key_exists( 'avg_frag_small', $data ) && ! empty( $data['avg_frag_small'] ) && array_key_exists( 'avg_frag_big', $data ) && ! empty( $data['avg_frag_big'] ) ) {
					$base_value = (float) $data['avg_frag_small'] + (float) $data['avg_frag_big'];
					$data_value = (float) $data['avg_frag_small'];
				}
				if ( is_array( $pdata ) && array_key_exists( 'avg_frag_small', $pdata ) && ! empty( $pdata['avg_frag_small'] ) && array_key_exists( 'avg_frag_big', $pdata ) && ! empty( $pdata['avg_frag_big'] ) ) {
					$pbase_value = (float) $pdata['avg_frag_small'] + (float) $pdata['avg_frag_big'];
					$pdata_value = (float) $pdata['avg_frag_small'];
				}
			}
			if ( 'memory' === $queried ) {
				if ( is_array( $data ) && array_key_exists( 'avg_mem_total', $data ) && ! empty( $data['avg_mem_total'] ) && array_key_exists( 'avg_mem_used', $data ) && ! empty( $data['avg_mem_used'] ) ) {
					$base_value = (float) $data['avg_mem_total'];
					$data_value = (float) $data['avg_mem_total'] - (float) $data['avg_mem_used'];
				}
				if ( is_array( $pdata ) && array_key_exists( 'avg_mem_total', $pdata ) && ! empty( $pdata['avg_mem_total'] ) && array_key_exists( 'avg_mem_used', $pdata ) && ! empty( $pdata['avg_mem_used'] ) ) {
					$pbase_value = (float) $pdata['avg_mem_total'];
					$pdata_value = (float) $pdata['avg_mem_total'] - (float) $pdata['avg_mem_used'];
				}
			}
			if ( 0.0 !== $base_value && 0.0 !== $data_value ) {
				$current = 100 * $data_value / $base_value;
				if ( 1 > $current && 'fragmentation' === $queried ) {
					$result[ 'kpi-main-' . $queried ] = round( $current, $chart ? 2 : 4 );
				} else {
					$result[ 'kpi-main-' . $queried ] = round( $current, $chart ? 1 : 4 );
				}
			} else {
				if ( 0.0 !== $data_value ) {
					$result[ 'kpi-main-' . $queried ] = 100;
				} elseif ( 0.0 !== $base_value ) {
					$result[ 'kpi-main-' . $queried ] = 0;
				} else {
					$result[ 'kpi-main-' . $queried ] = null;
				}
			}
			if ( 0.0 !== $pbase_value && 0.0 !== $pdata_value ) {
				$previous = 100 * $pdata_value / $pbase_value;
			} else {
				if ( 0.0 !== $pdata_value ) {
					$previous = 100.0;
				}
			}
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$result[ 'kpi-index-' . $queried ] = round( 100 * ( $current - $previous ) / $previous, 4 );
			} else {
				$result[ 'kpi-index-' . $queried ] = null;
			}
			if ( ! $chart ) {
				$result[ 'kpi-bottom-' . $queried ] = null;
				switch ( $queried ) {
					case 'ratio':
						if ( is_array( $data ) && array_key_exists( 'sum_hit', $data ) ) {
							$result[ 'kpi-bottom-' . $queried ] = (int) $data['sum_hit'];
						}
						break;
					case 'memory':
						$result[ 'kpi-bottom-' . $queried ] = (int) round( $base_value, 0 );
						break;
					case 'fragmentation':
						if ( is_array( $data ) && array_key_exists( 'avg_frag_count', $data ) ) {
							$result[ 'kpi-bottom-' . $queried ] = (int) round( $data['avg_frag_count'], 0 );
						}
						break;
					case 'key':
						$result[ 'kpi-bottom-' . $queried ] = (int) round( $data_value, 0 );
						break;
					case 'uptime':
						if ( 0.0 !== $base_value ) {
							$result[ 'kpi-bottom-' . $queried ] = (int) round( $this->duration * DAY_IN_SECONDS * ( $data_value / $base_value ) );
						}
						break;
				}
				return $result;
			}
			if ( isset( $result[ 'kpi-main-' . $queried ] ) ) {
				$result[ 'kpi-main-' . $queried ] = $result[ 'kpi-main-' . $queried ] . '&nbsp;%';
			} else {
				$result[ 'kpi-main-' . $queried ] = '-';
			}
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0.0 === $previous && 0.0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 !== $previous && 100 !== $previous && 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			switch ( $queried ) {
				case 'ratio':
					if ( is_array( $data ) && array_key_exists( 'sum_hit', $data ) ) {
						$result[ 'kpi-bottom-' . $queried ] = '<span class="apcm-kpi-large-bottom-text">' . sprintf( esc_html__( '%s hits', 'apcu-manager' ), Conversion::number_shorten( $data['sum_hit'], 2, false, '&nbsp;' ) ) . '</span>';
					}
					break;
				case 'memory':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="apcm-kpi-large-bottom-text">' . sprintf( esc_html__( 'total memory: %s', 'apcu-manager' ), Conversion::data_shorten( $base_value, 0, false, '&nbsp;' ) ) . '</span>';
					break;
				case 'fragmentation':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="apcm-kpi-large-bottom-text">' . sprintf( esc_html__( '%s blocks (avg.)', 'apcu-manager' ), (int) round( $data['avg_frag_count'], 0 ) ) . '</span>';
					break;
				case 'key':
					$result[ 'kpi-bottom-' . $queried ] = '<span class="apcm-kpi-large-bottom-text">' . sprintf( esc_html__( '%s keys (avg.)', 'apcu-manager' ), (int) round( $data_value, 0 ) ) . '</span>';
					break;
				case 'uptime':
					if ( 0.0 !== $base_value ) {
						$duration = implode( ', ', Date::get_age_array_from_seconds( $this->duration * DAY_IN_SECONDS * ( $data_value / $base_value ), true, true ) );
						if ( '' === $duration ) {
							$duration = esc_html__( 'no availability', 'apcu-manager' );
						} else {
							$duration = sprintf( esc_html__( 'available %s', 'apcu-manager' ), $duration );
						}
						$result[ 'kpi-bottom-' . $queried ] = '<span class="apcm-kpi-large-bottom-text">' . $duration . '</span>';
					}
					break;
			}
		}
		if ( 'object' === $queried ) {
			$data     = Schema::get_std_kpi( $this->filter, ! $this->is_today );
			$pdata    = Schema::get_std_kpi( $this->previous );
			$current  = 0.0;
			$previous = 0.0;
			if ( is_array( $data ) && array_key_exists( 'avg_slot_used', $data ) && ! empty( $data['avg_slot_used'] ) ) {
				$current = (float) $data['avg_slot_used'];
			}
			if ( is_array( $pdata ) && array_key_exists( 'avg_slot_used', $pdata ) && ! empty( $pdata['avg_slot_used'] ) ) {
				$previous = (float) $pdata['avg_slot_used'];
			}
			$result[ 'kpi-main-' . $queried ] = (int) round( $current, 0 );
			if ( ! $chart ) {
				if ( 0.0 !== $current && 0.0 !== $previous ) {
					$result[ 'kpi-index-' . $queried ] = round( 100 * ( $current - $previous ) / $previous, 4 );
				} else {
					$result[ 'kpi-index-' . $queried ] = null;
				}
				$result[ 'kpi-bottom-' . $queried ] = null;
				return $result;
			}
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0.0 === $previous && 0.0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 !== $previous && 100 !== $previous && 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			if ( is_array( $data ) && array_key_exists( 'min_slot_used', $data ) && array_key_exists( 'max_slot_used', $data ) ) {
				if ( empty( $data['min_slot_used'] ) ) {
					$data['min_slot_used'] = 0;
				}
				if ( empty( $data['max_slot_used'] ) ) {
					$data['max_slot_used'] = 0;
				}
				$result[ 'kpi-bottom-' . $queried ] = '<span class="apcm-kpi-large-bottom-text">' . (int) round( $data['min_slot_used'], 0 ) . '&nbsp;<img style="width:12px;vertical-align:middle;" src="' . Feather\Icons::get_base64( 'arrow-right', 'none', '#73879C' ) . '" />&nbsp;' . (int) round( $data['max_slot_used'], 0 ) . '&nbsp;</span>';
			}
		}
		return $result;
	}

	/**
	 * Get the title bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_title_bar() {
		$result  = '<div class="apcm-box apcm-box-full-line">';
		$result .= '<span class="apcm-title">' . esc_html__( 'APCu Analytics', 'apcu-manager' ) . '</span>';
		$result .= '<span class="apcm-subtitle">' . APCu::name() . '</span>';
		$result .= '<span class="apcm-datepicker">' . $this->get_date_box() . '</span>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the KPI bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_kpi_bar() {
		$result  = '<div class="apcm-box apcm-box-full-line">';
		$result .= '<div class="apcm-kpi-bar">';
		$result .= '<div class="apcm-kpi-large">' . $this->get_large_kpi( 'ratio' ) . '</div>';
		$result .= '<div class="apcm-kpi-large">' . $this->get_large_kpi( 'memory' ) . '</div>';
		$result .= '<div class="apcm-kpi-large">' . $this->get_large_kpi( 'object' ) . '</div>';
		$result .= '<div class="apcm-kpi-large">' . $this->get_large_kpi( 'key' ) . '</div>';
		$result .= '<div class="apcm-kpi-large">' . $this->get_large_kpi( 'fragmentation' ) . '</div>';
		$result .= '<div class="apcm-kpi-large">' . $this->get_large_kpi( 'uptime' ) . '</div>';
		$result .= '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the main chart.
	 *
	 * @return string  The main chart ready to print.
	 * @since    1.0.0
	 */
	public function get_main_chart() {
		$help_ratio  = esc_html__( 'Hit ratio variation.', 'apcu-manager' );
		$help_hit    = esc_html__( 'Hit, miss and insert distribution.', 'apcu-manager' );
		$help_memory = esc_html__( 'Memory distribution.', 'apcu-manager' );
		$help_file   = esc_html__( 'Objects variation.', 'apcu-manager' );
		$help_key    = esc_html__( 'Keys distribution.', 'apcu-manager' );
		$help_string = esc_html__( 'Fragmentation variation.', 'apcu-manager' );
		$help_uptime = esc_html__( 'Availability variation.', 'apcu-manager' );
		$detail      = '<span class="apcm-chart-button not-ready left" id="apcm-chart-button-ratio" data-position="left" data-tooltip="' . $help_ratio . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'award', 'none', '#73879C' ) . '" /></span>';
		$detail     .= '&nbsp;&nbsp;&nbsp;<span class="apcm-chart-button not-ready left" id="apcm-chart-button-hit" data-position="left" data-tooltip="' . $help_hit . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'hash', 'none', '#73879C' ) . '" /></span>';
		$detail     .= '&nbsp;&nbsp;&nbsp;<span class="apcm-chart-button not-ready left" id="apcm-chart-button-memory" data-position="left" data-tooltip="' . $help_memory . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'cpu', 'none', '#73879C' ) . '" /></span>';
		$detail     .= '&nbsp;&nbsp;&nbsp;<span class="apcm-chart-button not-ready left" id="apcm-chart-button-file" data-position="left" data-tooltip="' . $help_file . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'box', 'none', '#73879C' ) . '" /></span>';
		$detail     .= '&nbsp;&nbsp;&nbsp;<span class="apcm-chart-button not-ready left" id="apcm-chart-button-key" data-position="left" data-tooltip="' . $help_key . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'key', 'none', '#73879C' ) . '" /></span>';
		$detail     .= '&nbsp;&nbsp;&nbsp;<span class="apcm-chart-button not-ready left" id="apcm-chart-button-string" data-position="left" data-tooltip="' . $help_string . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'layers', 'none', '#73879C' ) . '" /></span>';
		$detail     .= '&nbsp;&nbsp;&nbsp;<span class="apcm-chart-button not-ready left" id="apcm-chart-button-uptime" data-position="left" data-tooltip="' . $help_uptime . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'activity', 'none', '#73879C' ) . '" /></span>';
		$result      = '<div class="apcm-row">';
		$result     .= '<div class="apcm-box apcm-box-full-line">';
		$result     .= '<div class="apcm-module-title-bar"><span class="apcm-module-title">' . esc_html__( 'Metrics Variations', 'apcu-manager' ) . '<span class="apcm-module-more">' . $detail . '</span></span></div>';
		$result     .= '<div class="apcm-module-content" id="apcm-main-chart">' . $this->get_graph_placeholder( 274 ) . '</div>';
		$result     .= '</div>';
		$result     .= '</div>';
		$result     .= $this->get_refresh_script(
			[
				'query'   => 'main-chart',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get a large kpi box.
	 *
	 * @param   string $kpi     The kpi to render.
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_large_kpi( $kpi ) {
		switch ( $kpi ) {
			case 'ratio':
				$icon  = Feather\Icons::get_base64( 'award', 'none', '#73879C' );
				$title = esc_html_x( 'Hit Ratio', 'Noun - Cache hit ratio.', 'apcu-manager' );
				$help  = esc_html__( 'The ratio between hit and total calls.', 'apcu-manager' );
				break;
			case 'memory':
				$icon  = Feather\Icons::get_base64( 'cpu', 'none', '#73879C' );
				$title = esc_html_x( 'Free Memory', 'Noun - Memory free of allocation.', 'apcu-manager' );
				$help  = esc_html__( 'Ratio of free available memory.', 'apcu-manager' );
				break;
			case 'object':
				$icon  = Feather\Icons::get_base64( 'box', 'none', '#73879C' );
				$title = esc_html_x( 'Objects', 'Noun - Number of already cached objects.', 'apcu-manager' );
				$help  = esc_html__( 'Number of cached objects.', 'apcu-manager' );
				break;
			case 'key':
				$icon  = Feather\Icons::get_base64( 'key', 'none', '#73879C' );
				$title = esc_html_x( 'Keys Saturation', 'Noun - Ratio of the allocated keys to the total available keys slots.', 'apcu-manager' );
				$help  = esc_html__( 'Ratio of the allocated keys to the total available keys slots.', 'apcu-manager' );
				break;
			case 'fragmentation':
				$icon  = Feather\Icons::get_base64( 'layers', 'none', '#73879C' );
				$title = esc_html_x( 'Fragmentation', 'Noun - Ratio of the blocks < 5M to the total memory size.', 'apcu-manager' );
				$help  = esc_html__( 'Ratio of the small memory blocks to the total memory size.', 'apcu-manager' );
				break;
			case 'uptime':
				$icon  = Feather\Icons::get_base64( 'activity', 'none', '#73879C' );
				$title = esc_html_x( 'Availability', 'Noun - Ratio of time when APCu is not disabled.', 'apcu-manager' );
				$help  = esc_html__( 'Time ratio with an operational APCu.', 'apcu-manager' );
				break;
		}
		$top       = '<img style="width:12px;vertical-align:baseline;" src="' . $icon . '" />&nbsp;&nbsp;<span style="cursor:help;" class="apcm-kpi-large-top-text bottom" data-position="bottom" data-tooltip="' . $help . '">' . $title . '</span>';
		$indicator = '&nbsp;';
		$bottom    = '<span class="apcm-kpi-large-bottom-text">&nbsp;</span>';
		$result    = '<div class="apcm-kpi-large-top">' . $top . '</div>';
		$result   .= '<div class="apcm-kpi-large-middle"><div class="apcm-kpi-large-middle-left" id="kpi-main-' . $kpi . '">' . $this->get_value_placeholder() . '</div><div class="apcm-kpi-large-middle-right" id="kpi-index-' . $kpi . '">' . $indicator . '</div></div>';
		$result   .= '<div class="apcm-kpi-large-bottom" id="kpi-bottom-' . $kpi . '">' . $bottom . '</div>';
		$result   .= $this->get_refresh_script(
			[
				'query'   => 'kpi',
				'queried' => $kpi,
			]
		);
		return $result;
	}

	/**
	 * Get the top size box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_top_size_box() {
		$result  = '<div class="apcm-60-module">';
		$result .= '<div class="apcm-module-title-bar"><span class="apcm-module-title">' . esc_html__( 'Memory Usage', 'apcu-manager' ) . '</span></div>';
		$result .= '<div class="apcm-module-content" id="apcm-top-size">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'top-size',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get the top count box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_count_box() {
		$result  = '<div class="apcm-40-module">';
		$result .= '<div class="apcm-module-title-bar"><span class="apcm-module-title">' . esc_html__( 'Objects Count', 'apcu-manager' ) . '</span></div>';
		$result .= '<div class="apcm-module-content" id="apcm-count">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'count',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get a placeholder for graph.
	 *
	 * @param   integer $height The height of the placeholder.
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_graph_placeholder( $height ) {
		return '<p style="text-align:center;line-height:' . $height . 'px;"><img style="width:40px;vertical-align:middle;" src="' . APCM_ADMIN_URL . 'medias/bars.svg" /></p>';
	}

	/**
	 * Get a placeholder for graph with no data.
	 *
	 * @param   integer $height The height of the placeholder.
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_graph_placeholder_nodata( $height ) {
		return '<p style="color:#73879C;text-align:center;line-height:' . $height . 'px;">' . esc_html__( 'No Data', 'apcu-manager' ) . '</p>';
	}

	/**
	 * Get a placeholder for value.
	 *
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_value_placeholder() {
		return '<img style="width:26px;vertical-align:middle;" src="' . APCM_ADMIN_URL . 'medias/three-dots.svg" />';
	}

	/**
	 * Get refresh script.
	 *
	 * @param   array $args Optional. The args for the ajax call.
	 * @return string  The script, ready to print.
	 * @since    1.0.0
	 */
	private function get_refresh_script( $args = [] ) {
		$result  = '<script>';
		$result .= 'jQuery(document).ready( function($) {';
		$result .= ' var data = {';
		$result .= '  action:"apcm_get_stats",';
		$result .= '  nonce:"' . wp_create_nonce( 'ajax_apcm' ) . '",';
		foreach ( $args as $key => $val ) {
			$s = '  ' . $key . ':';
			if ( is_string( $val ) ) {
				$s .= '"' . $val . '"';
			} elseif ( is_numeric( $val ) ) {
				$s .= $val;
			} elseif ( is_bool( $val ) ) {
				$s .= $val ? 'true' : 'false';
			}
			$result .= $s . ',';
		}
		$result .= '  start:"' . $this->start . '",';
		$result .= '  end:"' . $this->end . '",';
		$result .= ' };';
		$result .= ' $.post(ajaxurl, data, function(response) {';
		$result .= ' var val = JSON.parse(response);';
		$result .= ' $.each(val, function(index, value) {$("#" + index).html(value);});';
		if ( array_key_exists( 'query', $args ) && 'main-chart' === $args['query'] ) {
			$result .= '$(".apcm-chart-button").removeClass("not-ready");';
			$result .= '$("#apcm-chart-button-ratio").addClass("active");';
		}
		$result .= ' });';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

	/**
	 * Get the url.
	 *
	 * @param   array $exclude Optional. The args to exclude.
	 * @param   array $replace Optional. The args to replace or add.
	 * @return string  The url.
	 * @since    1.0.0
	 */
	private function get_url( $exclude = [], $replace = [] ) {
		$params          = [];
		$params['start'] = $this->start;
		$params['end']   = $this->end;
		foreach ( $exclude as $arg ) {
			unset( $params[ $arg ] );
		}
		foreach ( $replace as $key => $arg ) {
			$params[ $key ] = $arg;
		}
		$url = admin_url( 'admin.php?page=apcm-viewer' );
		foreach ( $params as $key => $arg ) {
			if ( '' !== $arg ) {
				$url .= '&' . $key . '=' . $arg;
			}
		}
		return $url;
	}

	/**
	 * Get a date picker box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_date_box() {
		$result  = '<img style="width:13px;vertical-align:middle;" src="' . Feather\Icons::get_base64( 'calendar', 'none', '#5A738E' ) . '" />&nbsp;&nbsp;<span class="apcm-datepicker-value"></span>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' moment.locale("' . L10n::get_display_locale() . '");';
		$result .= ' var start = moment("' . $this->start . '");';
		$result .= ' var end = moment("' . $this->end . '");';
		$result .= ' function changeDate(start, end) {';
		$result .= '  $("span.apcm-datepicker-value").html(start.format("LL") + " - " + end.format("LL"));';
		$result .= ' }';
		$result .= ' $(".apcm-datepicker").daterangepicker({';
		$result .= '  opens: "left",';
		$result .= '  startDate: start,';
		$result .= '  endDate: end,';
		$result .= '  minDate: moment("' . Schema::get_oldest_date() . '"),';
		$result .= '  maxDate: moment(),';
		$result .= '  showCustomRangeLabel: true,';
		$result .= '  alwaysShowCalendars: true,';
		$result .= '  locale: {customRangeLabel: "' . esc_html__( 'Custom Range', 'apcu-manager' ) . '",cancelLabel: "' . esc_html__( 'Cancel', 'apcu-manager' ) . '", applyLabel: "' . esc_html__( 'Apply', 'apcu-manager' ) . '"},';
		$result .= '  ranges: {';
		$result .= '    "' . esc_html__( 'Today', 'apcu-manager' ) . '": [moment(), moment()],';
		$result .= '    "' . esc_html__( 'Yesterday', 'apcu-manager' ) . '": [moment().subtract(1, "days"), moment().subtract(1, "days")],';
		$result .= '    "' . esc_html__( 'This Month', 'apcu-manager' ) . '": [moment().startOf("month"), moment().endOf("month")],';
		$result .= '    "' . esc_html__( 'Last Month', 'apcu-manager' ) . '": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")],';
		$result .= '  }';
		$result .= ' }, changeDate);';
		$result .= ' changeDate(start, end);';
		$result .= ' $(".apcm-datepicker").on("apply.daterangepicker", function(ev, picker) {';
		$result .= '  var url = "' . $this->get_url( [ 'start', 'end' ] ) . '" + "&start=" + picker.startDate.format("YYYY-MM-DD") + "&end=" + picker.endDate.format("YYYY-MM-DD");';
		$result .= '  $(location).attr("href", url);';
		$result .= ' });';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

}
