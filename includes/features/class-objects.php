<?php
/**
 * Objects list
 *
 * Lists all available objects.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace APCuManager\Plugin\Feature;

use APCuManager\System\Cache;
use APCuManager\System\Conversion;

use APCuManager\System\Date;
use APCuManager\System\Timezone;
use APCuManager\System\APCu;
use Feather\Icons;
use APCMKint\Kint;
use APCuManager\System\Option;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Define the objects list functionality.
 *
 * Lists all available objects.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Objects extends \WP_List_Table {

	/**
	 * The objects handler.
	 *
	 * @since    1.0.0
	 * @var      array    $objects    The objects list.
	 */
	private $objects = [];

	/**
	 * The plugins description.
	 *
	 * @since    3.0.0
	 * @var      array    $plugins    The plugins list.
	 */
	private $plugins = [];

	/**
	 * The number of lines to display.
	 *
	 * @since    1.0.0
	 * @var      integer    $limit    The number of lines to display.
	 */
	private $limit = 0;

	/**
	 * The page to display.
	 *
	 * @since    1.0.0
	 * @var      integer    $limit    The page to display.
	 */
	private $paged = 1;

	/**
	 * The order by of the list.
	 *
	 * @since    1.0.0
	 * @var      string    $orderby    The order by of the list.
	 */
	private $orderby = 'source';

	/**
	 * The order of the list.
	 *
	 * @since    1.0.0
	 * @var      string    $order    The order of the list.
	 */
	private $order = 'desc';

	/**
	 * The current url.
	 *
	 * @since    1.0.0
	 * @var      string    $url    The current url.
	 */
	private $url = '';

	/**
	 * The form nonce.
	 *
	 * @since    1.0.0
	 * @var      string    $nonce    The form nonce.
	 */
	private $nonce = '';

	/**
	 * The action to perform.
	 *
	 * @since    1.0.0
	 * @var      string    $action    The action to perform.
	 */
	private $action = '';

	/**
	 * The bulk args.
	 *
	 * @since    1.0.0
	 * @var      array    $bulk    The bulk args.
	 */
	private $bulk = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		require_once APCM_VENDOR_DIR . 'kint/apcm_init.php';
		parent::__construct(
			[
				'singular' => 'object',
				'plural'   => 'objects',
				'ajax'     => true,
			]
		);
		global $wp_version;
		if ( version_compare( $wp_version, '4.2-z', '>=' ) && $this->compat_fields && is_array( $this->compat_fields ) ) {
			array_push( $this->compat_fields, 'all_items' );
		}
		$this->process_args();
		$this->process_action();
		$time = time();
		foreach ( APCu::get_all_objects() as $object ) {
			if ( 0 < $object['ttl'] && ( $object['timestamp'] + $object['ttl'] < $time ) ) {
				$object['status'] = 0;
			} else {
				$object['status'] = 1;
			}
			$this->objects[] = $object;
		}
		$this->plugins = apply_filters( 'perfopsone_plugin_info', [] );
	}

	/**
	 * Default column formatter.
	 *
	 * @param   array  $item   The current item.
	 * @param   string $column_name The current column name.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Check box column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk[]" value="%s" />',
			$item['oid']
		);
	}

	/**
	 * "source" column formatter.
	 *
	 * @param   array  $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_source( $item ) {
		$icon = $this->get_base64_blank_icon();
		$name = '-';
		switch ( $item['source'] ) {
			case 'wordpress':
				$icon = $this->get_base64_wordpress_icon();
				$name = 'WordPress';
				break;
			case 'w3tc':
				$icon = $this->get_base64_w3tc_icon();
				$name = 'W3 Total Cache';
				break;
			default:
				if ( array_key_exists( $item['source'], $this->plugins ) ) {
					if ( array_key_exists( 'icon', $this->plugins[ $item['source'] ] ) ) {
						$icon = $this->plugins[ $item['source'] ]['icon'];
					}
					if ( array_key_exists( 'name', $this->plugins[ $item['source'] ] ) ) {
						$name = $this->plugins[ $item['source'] ]['name'];
					}
				}
		}
		return '<img style="width:28px;float:left;padding-top:6px;padding-right:6px;" src="' . $icon . '" />' . $name . '<br /><span style="color:silver">' . $item['path'] . '</span>';
	}

	/**
	 * "hit" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_hit( $item ) {
		return Conversion::number_shorten( $item['hit'] );
	}

	/**
	 * "ttl" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_ttl( $item ) {
		if ( 0 < $item['ttl'] ) {
			$ttl = implode( ', ', Date::get_age_array_from_seconds( $item['ttl'], true, true ) );
		} else {
			$ttl = esc_html__( '(no expiration)', 'apcu-manager' );
		}
		return $ttl . $this->get_actions( 'ttl', $item );
	}

	/**
	 * "status" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    3.1.1
	 */
	protected function column_status( $item ) {
		if ( 0 === $item['status'] ) {
			$status = esc_html__( 'Expired', 'apcu-manager' );
		} else {
			$status = esc_html__( 'Live', 'apcu-manager' );
		}
		return $status;
	}

	/**
	 * "object" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_object( $item ) {
		return $item['object'] . $this->get_viewer( $item ) . $this->get_actions( 'object', $item );
	}

	/**
	 * "memory" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_memory( $item ) {
		return Conversion::data_shorten( $item['memory'] );
	}

	/**
	 * "used" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_used( $item ) {
		$time = new \DateTime();
		$time->setTimestamp( $item['used'] );
		return ucfirst( Date::get_positive_time_diff_from_mysql_utc( $time->format( 'Y-m-d H:i:s' ) ) );
	}

	/**
	 * "timestamp" column formatter.
	 *
	 * @param   array $item   The current item.
	 * @return  string  The cell formatted, ready to print.
	 * @since    1.0.0
	 */
	protected function column_timestamp( $item ) {
		$time = new \DateTime();
		$time->setTimestamp( $item['timestamp'] );
		return Date::get_date_from_mysql_utc( $time->format( 'Y-m-d H:i:s' ), Timezone::network_get()->getName(), 'Y-m-d H:i:s' );
	}

	/**
	 * Enumerates columns.
	 *
	 * @return      array   The columns.
	 * @since    1.0.0
	 */
	public function get_columns() {
		$columns = [
			'cb'        => '<input type="checkbox" />',
			'source'    => esc_html__( 'Container', 'apcu-manager' ),
			'object'    => esc_html__( 'Object', 'apcu-manager' ),
			'status'    => esc_html__( 'Status', 'apcu-manager' ),
			'timestamp' => esc_html__( 'Timestamp', 'apcu-manager' ),
			'ttl'       => esc_html__( 'TTL', 'apcu-manager' ),
			'hit'       => esc_html__( 'Hits', 'apcu-manager' ),
			'memory'    => esc_html__( 'Memory size', 'apcu-manager' ),
			'used'      => esc_html__( 'Used', 'apcu-manager' ),
		];
		return $columns;
	}

	/**
	 * Enumerates hidden columns.
	 *
	 * @return      array   The hidden columns.
	 * @since    1.0.0
	 */
	protected function get_hidden_columns() {
		return [];
	}

	/**
	 * Enumerates sortable columns.
	 *
	 * @return      array   The sortable columns.
	 * @since    1.0.0
	 */
	protected function get_sortable_columns() {
		$sortable_columns = [
			'source'    => [ 'source', true ],
			'object'    => [ 'object', false ],
			'status'    => [ 'status', false ],
			'hit'       => [ 'hit', false ],
			'memory'    => [ 'memory', false ],
			'timestamp' => [ 'timestamp', false ],
			'used'      => [ 'used', false ],
			'ttl'       => [ 'ttl', false ],
		];
		return $sortable_columns;
	}

	/**
	 * Enumerates bulk actions.
	 *
	 * @return      array   The bulk actions.
	 * @since    1.0.0
	 */
	public function get_bulk_actions() {
		return [
			'invalidate' => esc_html__( 'Delete', 'apcu-manager' ),
		];
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @param string $which Position of extra control.
	 * @since 1.0.0
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-apcm-tools', '_wpnonce', false );
		}
		echo '<div class="tablenav ' . esc_attr( $which ) . '">';
		if ( $this->has_items() ) {
			echo '<div class="alignleft actions bulkactions">';
			$this->bulk_actions( $which );
			echo '</div>';
		}
		$this->extra_tablenav( $which );
		$this->pagination( $which );
		echo '<br class="clear" />';
		echo '</div>';
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which Position of extra control.
	 * @since 1.0.0
	 */
	public function extra_tablenav( $which ) {
		$list = $this;
		$args = compact( 'list', 'which' );
		foreach ( $args as $key => $val ) {
			$$key = $val;
		}
		if ( 'top' === $which || 'bottom' === $which ) {
			include APCM_ADMIN_DIR . 'partials/apcu-manager-admin-tools-lines.php';
		}
	}

	/**
	 * Prepares the list to be displayed.
	 *
	 * @since    1.0.0
	 */
	public function prepare_items() {
		$this->set_pagination_args(
			[
				'total_items' => count( $this->objects ),
				'per_page'    => $this->limit,
				'total_pages' => ceil( count( $this->objects ) / $this->limit ),
			]
		);
		$current_page          = $this->get_pagenum();
		$columns               = $this->get_columns();
		$hidden                = $this->get_hidden_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];
		$data                  = $this->objects;
		usort(
			$data,
			function ( $a, $b ) {
				if ( 'source' === $this->orderby ) {
					$result = strcmp( strtolower( $a['oid'] ), strtolower( $b['oid'] ) );
				} elseif ( 'object' === $this->orderby ) {
					$result = strcmp( strtolower( $a[ $this->orderby ] ), strtolower( $b[ $this->orderby ] ) );
				} else {
					$result = intval( $a[ $this->orderby ] ) < intval( $b[ $this->orderby ] ) ? 1 : -1;
				}
				return ( 'asc' === $this->order ) ? -$result : $result;
			}
		);

		$this->items = array_slice( $data, ( ( $current_page - 1 ) * $this->limit ), $this->limit );
	}

	/**
	 * Get available lines breakdowns.
	 *
	 * @since 1.0.0
	 */
	public function get_line_number_select() {
		$_disp  = [ 50, 100, 250, 500 ];
		$result = [];
		foreach ( $_disp as $d ) {
			$l          = [];
			$l['value'] = $d;
			// phpcs:ignore
			$l['text']     = sprintf( esc_html__( 'Display %d objects per page', 'apcu-manager' ), $d );
			$l['selected'] = ( intval( $d ) === intval( $this->limit ) ? 'selected="selected" ' : '' );
			$result[]      = $l;
		}
		return $result;
	}

	/**
	 * Pagination links.
	 *
	 * @param string $which Position of extra control.
	 * @since 1.0.0
	 */
	protected function pagination( $which ) {
		if ( empty( $this->_pagination_args ) ) {
			return;
		}
		$total_items     = (int) $this->_pagination_args['total_items'];
		$total_pages     = (int) $this->_pagination_args['total_pages'];
		$infinite_scroll = false;
		if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
			$infinite_scroll = $this->_pagination_args['infinite_scroll'];
		}
		if ( 'top' === $which && $total_pages > 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}
		// phpcs:ignore
		$output               = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';
		$current              = $this->get_pagenum();
		$removable_query_args = wp_removable_query_args();
		$current_url          = $this->url;
		$current_url          = remove_query_arg( $removable_query_args, $current_url );
		$page_links           = [];
		$total_pages_before   = '<span class="paging-input">';
		$total_pages_after    = '</span></span>';
		$disable_first        = false;
		$disable_last         = false;
		$disable_prev         = false;
		$disable_next         = false;
		if ( 1 === $current ) {
			$disable_first = true;
			$disable_prev  = true;
		}
		if ( 2 === $current ) {
			$disable_first = true;
		}
		if ( $current === $total_pages ) {
			$disable_last = true;
			$disable_next = true;
		}
		if ( $current === $total_pages - 1 ) {
			$disable_last = true;
		}
		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				$this->get_url( remove_query_arg( 'paged', $current_url ), true ),
				__( 'First page' ),
				'&laquo;'
			);
		}
		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				$this->get_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ), true ),
				__( 'Previous page' ),
				'&lsaquo;'
			);
		}
		if ( 'bottom' === $which ) {
			$html_current_page  = $current;
			$total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
		} else {
			$html_current_page = sprintf(
				"%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
				'<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
				$current,
				strlen( $total_pages )
			);
		}
		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		// phpcs:ignore
		$page_links[]     = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;
		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				$this->get_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ), true ),
				__( 'Next page' ),
				'&rsaquo;'
			);
		}
		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				$this->get_url( add_query_arg( 'paged', $total_pages, $current_url ), true ),
				__( 'Last page' ),
				'&raquo;'
			);
		}
		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) ) {
			$pagination_links_class .= ' hide-if-js';
		}
		$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';
		if ( $total_pages ) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}
		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";
		// phpcs:ignore
		echo $this->_pagination;
	}

	/**
	 * Print column headers, accounting for hidden and sortable columns.
	 *
	 * @staticvar int $cb_counter.
	 * @param bool $with_id Whether to set the id attribute or not.
	 * @since 1.0.0
	 */
	public function print_column_headers( $with_id = true ) {
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();
		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;
			$columns['cb']     = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All' ) . '</label><input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
			$cb_counter++;
		}
		foreach ( $columns as $column_key => $column_display_name ) {
			$class = [ 'manage-column', "column-$column_key" ];
			if ( in_array( $column_key, $hidden, true ) ) {
				$class[] = 'hidden';
			}
			if ( 'cb' === $column_key ) {
				$class[] = 'check-column';
			} elseif ( in_array( $column_key, [ 'posts', 'comments', 'links' ], true ) ) {
				$class[] = 'num';
			}
			if ( $column_key === $primary ) {
				$class[] = 'column-primary';
			}
			if ( isset( $sortable[ $column_key ] ) ) {
				list( $orderby, $desc_first ) = $sortable[ $column_key ];
				if ( $this->orderby === $orderby ) {
					$order   = 'asc' === $this->order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $this->order;
				} else {
					$order   = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}
				$column_display_name = '<a href="' . $this->get_url( add_query_arg( compact( 'orderby', 'order' ), $this->url ), true ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
			}
			$tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
			$scope = ( 'th' === $tag ) ? 'scope="col"' : '';
			$id    = $with_id ? "id='$column_key'" : '';
			if ( ! empty( $class ) ) {
				$class = "class='" . join( ' ', $class ) . "'";
			}
			// phpcs:ignore
			echo "<$tag $scope $id $class>$column_display_name</$tag>";
		}
	}

	/**
	 * Display a warning if needed.
	 *
	 * @since    1.0.0
	 */
	public function warning() {
		$message = '';
		if ( ! function_exists( 'apcu_cache_info' ) ) {
			$message = esc_html__( 'APCu is not enabled on this site. There\'s nothing to see here.', 'apcu-manager' );
		}
		if ( '' !== $message ) {
			// phpcs:ignore
			echo '<div id="apcm-warning" class="notice notice-warning"><p><strong>' . $message . '</strong></p></div>';
		}
	}

	/**
	 * Get the cleaned url.
	 *
	 * @param boolean $url Optional. The url, false for current url.
	 * @param boolean $limit Optional. Has the limit to be in the url.
	 * @return string The url cleaned, ready to use.
	 * @since 1.0.0
	 */
	public function get_url( $url = false, $limit = false ) {
		$url = remove_query_arg( 'limit', $url );
		if ( $limit ) {
			$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'limit=' . $this->limit;
		}
		return esc_url( $url );
	}

	/**
	 * Get the item value.
	 *
	 * @param   array   $item       The row item, an object.
	 * @return  string  The value, ready to print.
	 * @since   3.1.0
	 */
	private function get_value( $item ) {
		$result = '<p>' . esc_html__( 'Unable to fetch value…', 'apcu-manager' ) . '</p>';
		if ( function_exists( 'apcu_fetch' ) ) {
			$result = '<p>' . esc_html__( 'Object too large to be displayed…', 'apcu-manager' ) . '</p>';
			if ( 1024 * Option::network_get( 'maxsize' ) > (int) $item['memory'] ) {
				$found   = false;
				$result  = '<p>' . esc_html__( 'Unable to fetch value…', 'apcu-manager' ) . '</p>';
				$content = apcu_fetch( $item['oid'], $found );
				if ( $found ) {
					$result = Kint::dump( $content );
				}
			}
		}
		return $result;
	}

	/**
	 * Get the viewer content & image.
	 *
	 * @param   array   $item       The row item, an object.
	 * @param   boolean $soft       Optional. The image must be softened.
	 * @return  string  The viewer content & image, ready to print.
	 * @since   3.1.0
	 */
	protected function get_viewer( $item, $soft = false ) {
		if ( 0 === $item['status'] ) {
			return '';
		}
		$id = md5( (string) $item['oid'] );
		return '<div id="apcm-oviewer-' . $id . '" style="display:none;">' . $this->get_value( $item) . '</div>&nbsp;<a title="' . esc_html__( 'Object: ', 'apcu-manager' ) . $item['oid'] . '" href="#TB_inline?&width=600&height=800&inlineId=apcm-oviewer-' . $id . '" class="thickbox"><img title="' . esc_html__( 'Display current value', 'apcu-manager' ) . '" style="width:11px;vertical-align:baseline;" src="' . Icons::get_base64( 'eye', 'none', $soft ? '#C0C0FF' : '#3333AA' ) . '" /></a>';
	}

	/**
	 * Get the action image.
	 *
	 * @param   string  $url        The url to call.
	 * @param   string  $hint       The hint to display.
	 * @param   string  $icon       The icon to display.
	 * @param   boolean $soft       Optional. The image must be softened.
	 * @return  string  The action image, ready to print.
	 * @since   3.1.0
	 */
	protected function get_action( $url, $hint, $icon, $soft = false ) {
		return '&nbsp;<a href="' . $url . '" target="_blank"><img title="' . esc_html( $hint ) . '" style="width:11px;vertical-align:baseline;" src="' . Icons::get_base64( $icon, 'none', $soft ? '#C0C0FF' : '#3333AA' ) . '" /></a>';
	}

	/**
	 * Get the action image.
	 *
	 * @param   string  $column     The column where to display actions.
	 * @param   array   $item       The row item, an object.
	 * @return  string  The actions images, ready to print.
	 * @since   3.1.0
	 */
	protected function get_actions( $column, $item ) {

		/**
		 * Filters the available actions for the current item and column.
		 *
		 * @See https://github.com/Pierre-Lannoy/wp-apcu-manager/blob/master/HOOKS.md
		 * @since 3.1.0
		 * @param   array   $item       The full object with metadata.
		 */
		$actions = apply_filters( 'apcm_objects_list_actions_for_' . $column, [], $item );

		$result = '';
		foreach ( $actions as $action ) {
			if ( isset( $action['url'] ) ) {
				$result .= $this->get_action( $action['url'], isset( $action['hint'] ) ? $action['hint'] : __( 'Unknown action', 'apcu-manager' ), isset( $action['icon'] ) ? $action['icon'] : '' );
			}
		}
		return $result;
	}

	/**
	 * Initializes all the list properties.
	 *
	 * @since 1.0.0
	 */
	public function process_args() {
		if ( ! ( $this->nonce = filter_input( INPUT_POST, '_wpnonce' ) ) ) {
			$this->nonce = filter_input( INPUT_GET, '_wpnonce' );
		}
		$this->url   = set_url_scheme( 'http://' . filter_input( INPUT_SERVER, 'HTTP_HOST' ) . filter_input( INPUT_SERVER, 'REQUEST_URI' ) );
		$this->limit = filter_input( INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT );
		foreach ( [ 'top', 'bottom' ] as $which ) {
			if ( wp_verify_nonce( $this->nonce, 'bulk-apcm-tools' ) && array_key_exists( 'dolimit-' . $which, $_POST ) ) {
				$this->limit = filter_input( INPUT_POST, 'limit-' . $which, FILTER_SANITIZE_NUMBER_INT );
			}
		}
		if ( 0 === intval( $this->limit ) ) {
			$this->limit = filter_input( INPUT_POST, 'limit-top', FILTER_SANITIZE_NUMBER_INT );
		}
		if ( 0 === intval( $this->limit ) ) {
			$this->limit = 50;
		}
		$this->paged = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );
		if ( ! $this->paged ) {
			$this->paged = filter_input( INPUT_POST, 'paged', FILTER_SANITIZE_NUMBER_INT );
			if ( ! $this->paged ) {
				$this->paged = 1;
			}
		}
		$this->order = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $this->order ) {
			$this->order = 'desc';
		}
		$this->orderby = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $this->orderby ) {
			$this->orderby = 'source';
		}
		foreach ( [ 'top', 'bottom' ] as $which ) {
			if ( wp_verify_nonce( $this->nonce, 'bulk-apcm-tools' ) && array_key_exists( 'doinvalidate-' . $which, $_POST ) ) {
				$this->action = 'reset';
			}
		}
		if ( array_key_exists( 'quick-action', $_GET ) && wp_verify_nonce( $this->nonce, 'quick-action-apcm-tools' ) ) {
			$this->action = filter_input( INPUT_GET, 'quick-action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}
		if ( '' === $this->action ) {
			$action = '-1';
			if ( '-1' === $action && wp_verify_nonce( $this->nonce, 'bulk-apcm-tools' ) && array_key_exists( 'action', $_POST ) ) {
				$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			}
			if ( '-1' === $action && wp_verify_nonce( $this->nonce, 'bulk-apcm-tools' ) && array_key_exists( 'action2', $_POST ) ) {
				$action = filter_input( INPUT_POST, 'action2', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			}
			if ( '-1' !== $action && wp_verify_nonce( $this->nonce, 'bulk-apcm-tools' ) && array_key_exists( 'bulk', $_POST ) ) {
				$this->bulk = filter_input( INPUT_POST, 'bulk', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FORCE_ARRAY );
				if ( 0 < count( $this->bulk ) ) {
					$this->action = $action;
				}
			}
		}
	}

	/**
	 * Processes the selected action.
	 *
	 * @since 1.0.0
	 */
	public function process_action() {
		switch ( $this->action ) {
			case 'reset':
				APCu::reset();
				$message = esc_html__( 'Cache clearing done.', 'apcu-manager' );
				break;
			case 'invalidate':
				// phpcs:ignore
				$message = esc_html( sprintf( __( 'Deletion done: %d object(s).', 'apcu-manager' ), APCu::delete( $this->bulk ) ) );
				break;
			default:
				return;
		}
		add_settings_error( 'apcu_manager_no_error', 0, $message, 'updated' );
	}

	/**
	 * Returns a base64 svg resource for the blank icon.
	 *
	 * @return string The svg resource as a base64.
	 * @since 3.0.0
	 */
	private function get_base64_blank_icon() {
		$source  = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" fill-rule="evenodd"  fill="none" width="100%" height="100%"  viewBox="0 0 100 100">';
		$source .= '</svg>';
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

	/**
	 * Returns a base64 svg resource for the WordPress icon.
	 *
	 * @param string $color Optional. Color of the icon.
	 * @return string The svg resource as a base64.
	 * @since 3.1.0
	 */
	private function get_base64_wordpress_icon( $color = '#0073AA' ) {
		$source  = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" fill-rule="evenodd"  fill="none" width="100%" height="100%"  viewBox="0 0 100 100">';
		$source .= '<g transform="translate(-54,-26) scale(18,18)">';
		$source .= '<path style="fill:' . $color . '" d="m5.8465 1.9131c0.57932 0 1.1068 0.222 1.5022 0.58547-0.1938-0.0052-0.3872 0.11-0.3952 0.3738-0.0163 0.5333 0.6377 0.6469 0.2853 1.7196l-0.2915 0.8873-0.7939-2.3386c-0.0123-0.0362 0.002-0.0568 0.0465-0.0568h0.22445c0.011665 0 0.021201-0.00996 0.021201-0.022158v-0.13294c0-0.012193-0.00956-0.022657-0.021201-0.022153-0.42505 0.018587-0.8476 0.018713-1.2676 0-0.0117-0.0005-0.0212 0.01-0.0212 0.0222v0.13294c0 0.012185 0.00954 0.022158 0.021201 0.022158h0.22568c0.050201 0 0.064256 0.016728 0.076091 0.049087l0.3262 0.8921-0.4907 1.4817-0.8066-2.3758c-0.01-0.0298 0.0021-0.0471 0.0308-0.0471h0.25715c0.011661 0 0.021197-0.00996 0.021197-0.022158v-0.13294c0-0.012193-0.00957-0.022764-0.021197-0.022153-0.2698 0.014331-0.54063 0.017213-0.79291 0.019803 0.39589-0.60984 1.0828-1.0134 1.8639-1.0134l-0.0000029-0.0000062zm1.9532 1.1633c0.17065 0.31441 0.26755 0.67464 0.26755 1.0574 0 0.84005-0.46675 1.5712-1.1549 1.9486l0.6926-1.9617c0.1073-0.3036 0.2069-0.7139 0.1947-1.0443h-0.000004zm-1.2097 3.1504c-0.2325 0.0827-0.4827 0.1278-0.7435 0.1278-0.2247 0-0.4415-0.0335-0.6459-0.0955l0.68415-1.9606 0.70524 1.9284v-1e-7zm-1.6938-0.0854c-0.75101-0.35617-1.2705-1.1213-1.2705-2.0075 0-0.32852 0.071465-0.64038 0.19955-0.92096l1.071 2.9285 0.000003-0.000003zm0.95023-4.4367c1.3413 0 2.4291 1.0878 2.4291 2.4291s-1.0878 2.4291-2.4291 2.4291-2.4291-1.0878-2.4291-2.4291 1.0878-2.4291 2.4291-2.4291zm0-0.15354c1.4261 0 2.5827 1.1566 2.5827 2.5827s-1.1566 2.5827-2.5827 2.5827-2.5827-1.1566-2.5827-2.5827 1.1566-2.5827 2.5827-2.5827z"/>';
		$source .= '</g>';
		$source .= '</svg>';
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}
	/**
	 * Returns a base64 svg resource for the W3TC icon.
	 *
	 * @param string $color1 Optional. Color 1 of the icon.
	 * @param string $color2 Optional. Color 2 of the icon.
	 * @param string $color3 Optional. Color 3 of the icon.
	 * @return string The svg resource as a base64.
	 * @since 3.0.0
	 */
	private function get_base64_w3tc_icon( $color1 = '#3b7e83', $color2 = '#3b7e83', $color3 = '#3b7e83' ) {
		$source  = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" fill-rule="evenodd"  fill="none" width="100" height="100"  viewBox="0 0 100 100">';
		$source .= '<g transform="scale(6.4,6.4) translate(0,0)">';
		$source .= '<path fill="' . $color1 . '" d="M10.39 6.69C10.79 6.9 11.26 6.9 11.67 6.7C12.35 6.36 13.75 5.68 14.43 5.34C14.71 5.2 14.71 4.8 14.43 4.65C13.12 3.96 9.87 2.25 8.56 1.57C8.11 1.33 7.57 1.3 7.09 1.49C6.33 1.8 4.85 2.4 4.11 2.7C3.78 2.84 3.76 3.29 4.07 3.46C5.46 4.17 8.97 5.96 10.39 6.69Z"/>';
		$source .= '<path fill="' . $color2 . '" d="M9.02 14.58C8.7 14.76 8.33 14.45 8.46 14.11C8.97 12.77 10.26 9.32 10.81 7.87C10.92 7.57 11.13 7.33 11.41 7.19C12.17 6.8 13.89 5.92 14.62 5.54C14.83 5.44 15.06 5.64 14.99 5.86C14.55 7.17 13.45 10.49 13.02 11.79C12.89 12.19 12.62 12.53 12.25 12.73C11.42 13.21 9.78 14.15 9.02 14.58Z"/>';
		$source .= '<path fill="' . $color3 . '" d="M3.95 3.7L10.24 6.91L10.39 7.01L10.5 7.13L10.58 7.28L10.62 7.45L10.62 7.62L10.58 7.79L8.23 14.02L8.14 14.18L8.02 14.3L7.87 14.37L7.7 14.41L7.53 14.39L7.36 14.33L1.64 10.97L1.39 10.78L1.2 10.55L1.07 10.28L1 9.99L1 9.68L1.07 9.38L3.04 4.06L3.13 3.89L3.26 3.76L3.42 3.67L3.59 3.63L3.77 3.64L3.95 3.7ZM3.76 9.39L4.66 8.34L4.66 9.93L5.06 10.11L6.23 8.91L7.38 9.51L6.79 9.86L6.91 10.05L6.98 10.2L7.02 10.33L7.01 10.42L6.95 10.49L6.84 10.53L6.74 10.51L6.62 10.43L6.48 10.29L6.3 10.11L6.15 10.11L6.01 10.1L5.89 10.1L5.79 10.11L5.7 10.11L6.1 10.65L6.47 11.04L6.82 11.27L7.15 11.35L7.45 11.28L7.76 11.03L7.88 10.74L7.86 10.47L7.75 10.24L7.61 10.11L7.7 10.04L7.82 9.94L7.97 9.82L8.17 9.68L8.39 9.51L6.18 8.19L5.22 9.16L5.13 7.66L4.73 7.44L3.9 8.42L3.9 6.9L3.28 6.58L3.28 9.09L3.76 9.39Z"/>';
		$source .= '</g>';
		$source .= '</svg>';
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

}
