<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace APCuManager\Plugin;

use APCuManager\Plugin\Feature\Analytics;
use APCuManager\Plugin\Feature\AnalyticsFactory;
use APCuManager\System\Assets;
use APCuManager\System\Environment;
use APCuManager\System\Role;
use APCuManager\System\Option;
use APCuManager\System\Form;
use APCuManager\System\Blog;
use APCuManager\System\Date;
use APCuManager\System\Timezone;
use PerfOpsOne\Menus;
use PerfOpsOne\AdminBar;
use APCuManager\System\APCu;

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Apcu_Manager_Admin {

	/**
	 * The assets manager that's responsible for handling all assets of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Assets    $assets    The plugin assets manager.
	 */
	protected $assets;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->assets = new Assets();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		$this->assets->register_style( APCM_ASSETS_ID, APCM_ADMIN_URL, 'css/apcu-manager.min.css' );
		$this->assets->register_style( 'apcm-daterangepicker', APCM_ADMIN_URL, 'css/daterangepicker.min.css' );
		$this->assets->register_style( 'apcm-tooltip', APCM_ADMIN_URL, 'css/tooltip.min.css' );
		$this->assets->register_style( 'apcm-chartist', APCM_ADMIN_URL, 'css/chartist.min.css' );
		$this->assets->register_style( 'apcm-chartist-tooltip', APCM_ADMIN_URL, 'css/chartist-plugin-tooltip.min.css' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$this->assets->register_script( APCM_ASSETS_ID, APCM_ADMIN_URL, 'js/apcu-manager.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'apcm-moment-with-locale', APCM_ADMIN_URL, 'js/moment-with-locales.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'apcm-daterangepicker', APCM_ADMIN_URL, 'js/daterangepicker.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'apcm-chartist', APCM_ADMIN_URL, 'js/chartist.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'apcm-chartist-tooltip', APCM_ADMIN_URL, 'js/chartist-plugin-tooltip.min.js', [ 'apcm-chartist' ] );
	}

	/**
	 * Init PerfOps admin menus.
	 *
	 * @param array $perfops    The already declared menus.
	 * @return array    The completed menus array.
	 * @since 1.0.0
	 */
	public function init_perfopsone_admin_menus( $perfops ) {
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
			$perfops['tools'][]    = [
				'name'          => esc_html__( 'APCu', 'apcu-manager' ),
				/* translators: as in the sentence "Explore and delete APCu objects used by your network." or "Explore and delete APCu objects used by your website." */
				'description'   => sprintf( esc_html__( 'Explore and delete APCu objects used by your %s.', 'apcu-manager' ), Environment::is_wordpress_multisite() ? esc_html__( 'network', 'apcu-manager' ) : esc_html__( 'website', 'apcu-manager' ) ),
				'icon_callback' => [ \APCuManager\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'apcm-tools',
				'page_title'    => esc_html__( 'APCu Management', 'apcu-manager' ),
				'menu_title'    => esc_html__( 'APCu', 'apcu-manager' ),
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_tools_page' ],
				'plugin'        => APCM_SLUG,
				'activated'     => true,
				'remedy'        => '',
			];
			$perfops['settings'][] = [
				'name'          => APCM_PRODUCT_NAME,
				'description'   => '',
				'icon_callback' => [ \APCuManager\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'apcm-settings',
				/* translators: as in the sentence "APCu Manager Settings" or "WordPress Settings" */
				'page_title'    => sprintf( esc_html__( '%s Settings', 'apcu-manager' ), APCM_PRODUCT_NAME ),
				'menu_title'    => APCM_PRODUCT_NAME,
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_settings_page' ],
				'plugin'        => APCM_SLUG,
				'version'       => APCM_VERSION,
				'activated'     => true,
				'remedy'        => '',
				'statistics'    => [ '\APCuManager\System\Statistics', 'sc_get_raw' ],
			];
		}
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$perfops['analytics'][] = [
				'name'          => esc_html__( 'APCu', 'apcu-manager' ),
				/* translators: as in the sentence "View APCu key performance indicators and activity metrics for your network." or "View APCu key performance indicators and activity metrics for your website." */
				'description'   => sprintf( esc_html__( 'View APCu key performance indicators and activity metrics for your %s.', 'apcu-manager' ), Environment::is_wordpress_multisite() ? esc_html__( 'network', 'apcu-manager' ) : esc_html__( 'website', 'apcu-manager' ) ),
				'icon_callback' => [ \APCuManager\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'apcm-viewer',
				'page_title'    => esc_html__( 'APCu Analytics', 'apcu-manager' ),
				'menu_title'    => esc_html__( 'APCu', 'apcu-manager' ),
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_viewer_page' ],
				'plugin'        => APCM_SLUG,
				'activated'     => Option::network_get( 'analytics' ),
				'remedy'        => esc_url( admin_url( 'admin.php?page=apcm-settings' ) ),
			];
		}
		return $perfops;
	}

	/**
	 * Init PerfOps admin bar.
	 *
	 * @param array $perfops    The already declared items.
	 * @return array    The completed items array.
	 * @since 3.2.0
	 */
	public function init_perfopsone_admin_bar( $perfops ) {
		if ( ! ( $action = filter_input( INPUT_GET, 'action' ) ) ) {
			$action = filter_input( INPUT_POST, 'action' );
		}
		if ( ! ( $tab = filter_input( INPUT_GET, 'tab' ) ) ) {
			$tab = filter_input( INPUT_POST, 'tab' );
		}
		$early_signal  = ( 'misc' === $tab && 'do-save' === $action ) && ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() );
		$early_signal &= ( ! empty( $_POST ) && array_key_exists( 'submit', $_POST ) );
		$early_signal &= ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'apcm-plugin-options' ) );
		if ( $early_signal ) {
			Option::network_set( 'adminbar', array_key_exists( 'apcm_plugin_options_adminbar', $_POST ) );
		}
		if ( Option::network_get( 'adminbar' ) && ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) ) {
			$perfops[] = [
				'id'    => 'apcm-tools-reset',
				'title' => '<strong>APCu</strong>&nbsp;&nbsp;➜&nbsp;&nbsp;' . __( 'Delete All', 'apcu-manager' ),
				'href'  => add_query_arg( '_wpnonce', wp_create_nonce( 'quick-action-apcm-tools' ), admin_url( 'admin.php?page=apcm-tools&quick-action=reset' ) ),
				'meta'  => false,
			];
		}
		return $perfops;
	}

	/**
	 * Dispatch the items in the settings menu.
	 *
	 * @since 2.0.0
	 */
	public function finalize_admin_menus() {
		Menus::finalize();
	}

	/**
	 * Removes unneeded items from the settings menu.
	 *
	 * @since 2.0.0
	 */
	public function normalize_admin_menus() {
		Menus::normalize();
	}

	/**
	 * Set the items in the settings menu.
	 *
	 * @since 1.0.0
	 */
	public function init_admin_menus() {
		add_filter( 'init_perfopsone_admin_menus', [ $this, 'init_perfopsone_admin_menus' ] );
		add_filter( 'init_perfopsone_admin_bar', [ $this, 'init_perfopsone_admin_bar' ] );
		Menus::initialize();
		AdminBar::initialize();
	}

	/**
	 * Initializes settings sections.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_sections() {
		add_settings_section( 'apcm_plugin_features_section', esc_html__( 'Plugin Features', 'apcu-manager' ), [ $this, 'plugin_features_section_callback' ], 'apcm_plugin_features_section' );
		add_settings_section( 'apcm_plugin_options_section', esc_html__( 'Plugin options', 'apcu-manager' ), [ $this, 'plugin_options_section_callback' ], 'apcm_plugin_options_section' );
	}

	/**
	 * Add links in the "Actions" column on the plugins view page.
	 *
	 * @param string[] $actions     An array of plugin action links. By default this can include 'activate',
	 *                              'deactivate', and 'delete'.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array    $plugin_data An array of plugin data. See `get_plugin_data()`.
	 * @param string   $context     The plugin context. By default this can include 'all', 'active', 'inactive',
	 *                              'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
	 * @return array Extended list of links to print in the "Actions" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_actions_links( $actions, $plugin_file, $plugin_data, $context ) {
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=apcm-settings' ) ), esc_html__( 'Settings', 'apcu-manager' ) );
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=apcm-tools' ) ), esc_html__( 'Tools', 'apcu-manager' ) );
		if ( Option::network_get( 'analytics' ) ) {
			$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=apcm-viewer' ) ), esc_html__( 'Statistics', 'apcu-manager' ) );
		}
		return $actions;
	}

	/**
	 * Add links in the "Description" column on the plugins view page.
	 *
	 * @param array  $links List of links to print in the "Description" column on the Plugins page.
	 * @param string $file Path to the plugin file relative to the plugins directory.
	 * @return array Extended list of links to print in the "Description" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_row_meta( $links, $file ) {
		if ( 0 === strpos( $file, APCM_SLUG . '/' ) ) {
			$links[] = '<a href="https://wordpress.org/support/plugin/' . APCM_SLUG . '/">' . __( 'Support', 'apcu-manager' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Get the content of the tools page.
	 *
	 * @since 1.0.0
	 */
	public function get_tools_page() {
		include APCM_ADMIN_DIR . 'partials/apcu-manager-admin-tools.php';
	}

	/**
	 * Get the content of the viewer page.
	 *
	 * @since 1.0.0
	 */
	public function get_viewer_page() {
		$analytics = AnalyticsFactory::get_analytics();
		include APCM_ADMIN_DIR . 'partials/apcu-manager-admin-view-analytics.php';
	}

	/**
	 * Get the content of the settings page.
	 *
	 * @since 1.0.0
	 */
	public function get_settings_page() {
		if ( ! ( $tab = filter_input( INPUT_GET, 'tab' ) ) ) {
			$tab = filter_input( INPUT_POST, 'tab' );
		}
		if ( ! ( $action = filter_input( INPUT_GET, 'action' ) ) ) {
			$action = filter_input( INPUT_POST, 'action' );
		}
		$nonce = filter_input( INPUT_GET, 'nonce' );
		if ( $action && $tab ) {
			switch ( $tab ) {
				case 'misc':
					switch ( $action ) {
						case 'do-save':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( ! empty( $_POST ) && array_key_exists( 'submit', $_POST ) ) {
									$this->save_options();
								} elseif ( ! empty( $_POST ) && array_key_exists( 'reset-to-defaults', $_POST ) ) {
									$this->reset_options();
								}
							}
							break;
						case 'install-decalog':
							if ( class_exists( 'PerfOpsOne\Installer' ) && $nonce && wp_verify_nonce( $nonce, $action ) ) {
								$result = \PerfOpsOne\Installer::do( 'decalog', true );
								if ( '' === $result ) {
									add_settings_error( 'apcm_no_error', '', esc_html__( 'Plugin successfully installed and activated with default settings.', 'apcu-manager' ), 'info' );
								} else {
									add_settings_error( 'apcm_install_error', '', sprintf( esc_html__( 'Unable to install or activate the plugin. Error message: %s.', 'apcu-manager' ), $result ), 'error' );
								}
							}
							break;
					}
					break;
			}
		}
		include APCM_ADMIN_DIR . 'partials/apcu-manager-admin-settings-main.php';
	}

	/**
	 * Save the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function save_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'apcm-plugin-options' ) ) {
				Option::network_set( 'use_cdn', array_key_exists( 'apcm_plugin_options_usecdn', $_POST ) ? (bool) filter_input( INPUT_POST, 'apcm_plugin_options_usecdn' ) : false );
				Option::network_set( 'display_nag', array_key_exists( 'apcm_plugin_options_nag', $_POST ) ? (bool) filter_input( INPUT_POST, 'apcm_plugin_options_nag' ) : false );
				Option::network_set( 'analytics', array_key_exists( 'apcm_plugin_features_analytics', $_POST ) ? (bool) filter_input( INPUT_POST, 'apcm_plugin_features_analytics' ) : false );
				Option::network_set( 'metrics', array_key_exists( 'apcm_plugin_features_metrics', $_POST ) ? (bool) filter_input( INPUT_POST, 'apcm_plugin_features_metrics' ) : false );
				Option::network_set( 'gc', array_key_exists( 'apcm_plugin_features_gc', $_POST ) ? (bool) filter_input( INPUT_POST, 'apcm_plugin_features_gc' ) : false );
				Option::network_set( 'adminbar', array_key_exists( 'apcm_plugin_options_adminbar', $_POST ) ? (bool) filter_input( INPUT_POST, 'apcm_plugin_options_adminbar' ) : false );
				Option::network_set( 'history', array_key_exists( 'apcm_plugin_features_history', $_POST ) ? (string) filter_input( INPUT_POST, 'apcm_plugin_features_history', FILTER_SANITIZE_NUMBER_INT ) : Option::network_get( 'history' ) );
				$old_earlyloading = Option::network_get( 'earlyloading' );
				$new_earlyloading = array_key_exists( 'apcm_plugin_features_earlyloading', $_POST ) ? (bool) filter_input( INPUT_POST, 'apcm_plugin_features_earlyloading' ) : false;
				Option::network_set( 'earlyloading', $new_earlyloading );
				$ecode = 0;
				if ( $old_earlyloading !== $new_earlyloading ) {
					APCu::reset();
					apcm_check_earlyloading();
					if ( $new_earlyloading ) {
						$emessage = esc_html__( 'APCu Manager is now the WordPress object cache handler.', 'apcu-manager' );
						$ecode    = 1;
						if ( defined ( 'APCM_BOOTSTRAP_ALREADY_EXISTS_REMOVED' ) && APCM_BOOTSTRAP_ALREADY_EXISTS_REMOVED ) {
							$emessage .= '<br/>' . esc_html__( 'The previous handler has been removed.', 'apcu-manager' );
						}
						if ( defined ( 'APCM_BOOTSTRAP_COPY_ERROR' ) && APCM_BOOTSTRAP_COPY_ERROR ) {
							$emessage = esc_html__( 'Unable to activate the APCu Manager handler.', 'apcu-manager' );
							$ecode    = 10;
						}
						if ( defined ( 'APCM_BOOTSTRAP_ALREADY_EXISTS_ERROR' ) && APCM_BOOTSTRAP_ALREADY_EXISTS_ERROR ) {
							$emessage = esc_html__( 'Unable to activate the APCu Manager handler.', 'apcu-manager' );
							$ecode    = 20;
						}
					} else {
						$emessage = esc_html__( 'APCu Manager is no longer the WordPress object cache handler.', 'apcu-manager' );
						$ecode    = 1;
					}
				}
				if ( ! Option::network_get( 'analytics' ) ) {
					wp_clear_scheduled_hook( APCM_CRON_STATS_NAME );
				}
				if ( ! Option::network_get( 'gc' ) ) {
					wp_clear_scheduled_hook( APCM_CRON_GC_NAME );
				}
				$message = esc_html__( 'Plugin settings have been saved.', 'apcu-manager' );
				$code    = 0;
				add_settings_error( 'apcm_no_error', $code, $message, 'updated' );
				if ( 0 !== $ecode ) {
					add_settings_error( 'apcm_object_cache', $ecode - 1, $emessage, 1 === $ecode ? 'info' : 'error' );
				}
				\DecaLog\Engine::eventsLogger( APCM_SLUG )->info( 'Plugin settings updated.', [ 'code' => $code ] );
			} else {
				$message = esc_html__( 'Plugin settings have not been saved. Please try again.', 'apcu-manager' );
				$code    = 2;
				add_settings_error( 'apcm_nonce_error', $code, $message, 'error' );
				\DecaLog\Engine::eventsLogger( APCM_SLUG )->warning( 'Plugin settings not updated.', [ 'code' => $code ] );
			}
		}
	}

	/**
	 * Reset the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function reset_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'apcm-plugin-options' ) ) {
				Option::reset_to_defaults();
				$message = esc_html__( 'Plugin settings have been reset to defaults.', 'apcu-manager' );
				$code    = 0;
				add_settings_error( 'apcm_no_error', $code, $message, 'updated' );
				\DecaLog\Engine::eventsLogger( APCM_SLUG )->info( 'Plugin settings reset to defaults.', [ 'code' => $code ] );
			} else {
				$message = esc_html__( 'Plugin settings have not been reset to defaults. Please try again.', 'apcu-manager' );
				$code    = 2;
				add_settings_error( 'apcm_nonce_error', $code, $message, 'error' );
				\DecaLog\Engine::eventsLogger( APCM_SLUG )->warning( 'Plugin settings not reset to defaults.', [ 'code' => $code ] );
			}
		}
	}

	/**
	 * Callback for plugin options section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_options_section_callback() {
		$form = new Form();
		if ( \DecaLog\Engine::isDecalogActivated() ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site is currently using %s.', 'apcu-manager' ), '<em>' . \DecaLog\Engine::getVersionString() .'</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site does not use any logging plugin. To log all events triggered in APCu Manager, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'apcu-manager' ), '<a href="https://wordpress.org/plugins/decalog/">DecaLog</a>' );
			if ( class_exists( 'PerfOpsOne\Installer' ) && ! Environment::is_wordpress_multisite() ) {
				$help .= '<br/><a href="' . wp_nonce_url( admin_url( 'admin.php?page=apcm-settings&tab=misc&action=install-decalog' ), 'install-decalog', 'nonce' ) . '" class="poo-button-install"><img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'download-cloud', 'none', '#FFFFFF', 3 ) . '" />&nbsp;&nbsp;' . esc_html__('Install It Now', 'apcu-manager' ) . '</a>';
			}
		}
		add_settings_field(
			'apcm_plugin_options_logger',
			esc_html__( 'Logging', 'apcu-manager' ),
			[ $form, 'echo_field_simple_text' ],
			'apcm_plugin_options_section',
			'apcm_plugin_options_section',
			[
				'text' => $help
			]
		);
		register_setting( 'apcm_plugin_options_section', 'apcm_plugin_options_logger' );
		add_settings_field(
			'apcm_plugin_options_adminbar',
			__( 'Quick actions', 'apcu-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'apcm_plugin_options_section',
			'apcm_plugin_options_section',
			[
				'text'        => esc_html__( 'Display in admin bar', 'apcu-manager' ),
				'id'          => 'apcm_plugin_options_adminbar',
				'checked'     => Option::network_get( 'adminbar' ),
				'description' => esc_html__( 'If checked, APCu Manager will display in admin bar the most important actions, if any.', 'apcu-manager' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'apcm_plugin_options_section', 'apcm_plugin_options_adminbar' );
		add_settings_field(
			'apcm_plugin_options_usecdn',
			esc_html__( 'Resources', 'apcu-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'apcm_plugin_options_section',
			'apcm_plugin_options_section',
			[
				'text'        => esc_html__( 'Use public CDN', 'apcu-manager' ),
				'id'          => 'apcm_plugin_options_usecdn',
				'checked'     => Option::network_get( 'use_cdn' ),
				'description' => esc_html__( 'If checked, APCu Manager will use a public CDN (jsDelivr) to serve scripts and stylesheets.', 'apcu-manager' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'apcm_plugin_options_section', 'apcm_plugin_options_usecdn' );
		add_settings_field(
			'apcm_plugin_options_nag',
			esc_html__( 'Admin notices', 'apcu-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'apcm_plugin_options_section',
			'apcm_plugin_options_section',
			[
				'text'        => esc_html__( 'Display', 'apcu-manager' ),
				'id'          => 'apcm_plugin_options_nag',
				'checked'     => Option::network_get( 'display_nag' ),
				'description' => esc_html__( 'Allows APCu Manager to display admin notices throughout the admin dashboard.', 'apcu-manager' ) . '<br/>' . esc_html__( 'Note: APCu Manager respects DISABLE_NAG_NOTICES flag.', 'apcu-manager' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'apcm_plugin_options_section', 'apcm_plugin_options_nag' );
	}

	/**
	 * Get the available frequencies.
	 *
	 * @return array An array containing the history modes.
	 * @since  3.2.0
	 */
	protected function get_frequencies_array() {
		$result   = [];
		$result[] = [ 'never', esc_html__( 'Never', 'apcu-manager' ) ];
		$result[] = [ 'hourly', esc_html__( 'Once Hourly', 'apcu-manager' ) ];
		$result[] = [ 'twicedaily', esc_html__( 'Twice Daily', 'apcu-manager' ) ];
		$result[] = [ 'daily', esc_html__( 'Once Daily', 'apcu-manager' ) ];
		return $result;
	}

	/**
	 * Get the available history retentions.
	 *
	 * @return array An array containing the history modes.
	 * @since  3.2.0
	 */
	protected function get_retentions_array() {
		$result = [];
		for ( $i = 1; $i < 4; $i++ ) {
			// phpcs:ignore
			$result[] = [ (int) ( 7 * $i ), esc_html( sprintf( _n( '%d week', '%d weeks', $i, 'apcu-manager' ), $i ) ) ];
		}
		for ( $i = 1; $i < 4; $i++ ) {
			// phpcs:ignore
			$result[] = [ (int) ( 30 * $i ), esc_html( sprintf( _n( '%d month', '%d months', $i, 'apcu-manager' ), $i ) ) ];
		}
		return $result;
	}

	/**
	 * Callback for plugin features section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_features_section_callback() {
		$apcu_available = function_exists( 'apcu_delete' ) && function_exists( 'apcu_fetch' ) && function_exists( 'apcu_store' ) && function_exists( 'apcu_add' ) && function_exists( 'apcu_dec' ) && function_exists( 'apcu_inc' );
		if ( $apcu_available ) {
			$note = sprintf( __( 'Note: %s is currently enabled on you %s.', 'apcu-manager' ), APCu::name(), Environment::is_wordpress_multisite() ? esc_html__( 'network', 'apcu-manager' ) : esc_html__( 'website', 'apcu-manager' ) );
		} else {
			$note = esc_html__( 'Note: to activate it, your must have APCu enabled in PHP. It is not currently the case.', 'apcu-manager' );
		}
		$form           = new Form();
		add_settings_field(
			'apcm_plugin_features_earlyloading',
			esc_html__( 'Object cache', 'apcu-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'apcm_plugin_features_section',
			'apcm_plugin_features_section',
			[
				'text'        => esc_html__( 'Activated', 'apcu-manager' ),
				'id'          => 'apcm_plugin_features_earlyloading',
				'checked'     => $apcu_available ? Option::network_get( 'earlyloading' ) : false,
				'description' => sprintf( __( 'If checked, APCu Manager will use APCu as object cache to speed up your %s.', 'apcu-manager' ), Environment::is_wordpress_multisite() ? esc_html__( 'network', 'apcu-manager' ) : esc_html__( 'website', 'apcu-manager' ) ) . '<br/>' . $note,
				'full_width'  => false,
				'enabled'     => $apcu_available,
			]
		);
		register_setting( 'apcm_plugin_features_section', 'apcm_plugin_features_earlyloading' );
		if ( $apcu_available ) {
			$note = esc_html__( 'Note: for this to work, your WordPress site must have an operational CRON.', 'apcu-manager' );
		} else {
			$note = esc_html__( 'Note: to activate it, your must have APCu enabled in PHP. It is not currently the case.', 'apcu-manager' );
		}
		add_settings_field(
			'apcm_plugin_features_gc',
			esc_html__( 'Garbage collector', 'apcu-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'apcm_plugin_features_section',
			'apcm_plugin_features_section',
			[
				'text'        => esc_html__( 'Activated', 'apcu-manager' ),
				'id'          => 'apcm_plugin_features_gc',
				'checked'     => $apcu_available ? Option::network_get( 'gc' ) : false,
				'description' => esc_html__( 'If checked, APCu Manager will delete cached objects as soon as they\'re out of date.', 'apcu-manager' ) . '<br/>' . $note,
				'full_width'  => false,
				'enabled'     => $apcu_available,
			]
		);
		register_setting( 'apcm_plugin_features_section', 'apcm_plugin_features_gc' );
		if ( $apcu_available ) {
			$note = esc_html__( 'Note: for this to work, your WordPress site must have an operational CRON.', 'apcu-manager' );
		} else {
			$note = esc_html__( 'Note: to activate it, your must have APCu enabled in PHP. It is not currently the case.', 'apcu-manager' );
		}
		add_settings_field(
			'apcm_plugin_features_analytics',
			esc_html__( 'Analytics', 'apcu-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'apcm_plugin_features_section',
			'apcm_plugin_features_section',
			[
				'text'        => esc_html__( 'Activated', 'apcu-manager' ),
				'id'          => 'apcm_plugin_features_analytics',
				'checked'     => $apcu_available ? Option::network_get( 'analytics' ) : false,
				'description' => esc_html__( 'If checked, APCu Manager will analyze APCu operations and store statistics every five minutes.', 'apcu-manager' ) . '<br/>' . $note,
				'full_width'  => false,
				'enabled'     => $apcu_available,
			]
		);
		register_setting( 'apcm_plugin_features_section', 'apcm_plugin_features_analytics' );
		add_settings_field(
			'apcm_plugin_features_history',
			esc_html__( 'Historical data', 'apcu-manager' ),
			[ $form, 'echo_field_select' ],
			'apcm_plugin_features_section',
			'apcm_plugin_features_section',
			[
				'list'        => $this->get_retentions_array(),
				'id'          => 'apcm_plugin_features_history',
				'value'       => Option::network_get( 'history' ),
				'description' => esc_html__( 'Maximum age of data to keep for statistics.', 'apcu-manager' ),
				'full_width'  => false,
				'enabled'     => $apcu_available,
			]
		);
		register_setting( 'apcm_plugin_features_section', 'apcm_plugin_features_history' );
		add_settings_field(
			'apcm_plugin_features_metrics',
			esc_html__( 'Metrics', 'apcu-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'apcm_plugin_features_section',
			'apcm_plugin_features_section',
			[
				'text'        => esc_html__( 'Activated', 'apcu-manager' ),
				'id'          => 'apcm_plugin_features_metrics',
				'checked'     => \DecaLog\Engine::isDecalogActivated() && $apcu_available ? Option::network_get( 'metrics' ) : false,
				'description' => esc_html__( 'If checked, APCu Manager will collate and publish APCu metrics.', 'apcu-manager' ) . ( \DecaLog\Engine::isDecalogActivated() ? '' : '<br/>' . esc_html__( 'Note: for this to work, you must install DecaLog. It is not currently the case.', 'apcu-manager' ) ),
				'full_width'  => false,
				'enabled'     => \DecaLog\Engine::isDecalogActivated() && $apcu_available,
			]
		);
		register_setting( 'apcm_plugin_features_section', 'apcm_plugin_features_metrics' );
	}

}
