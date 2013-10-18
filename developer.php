<?php /*

**************************************************************************

Plugin Name:  Developer
Plugin URI:   http://wordpress.org/extend/plugins/developer/
Description:  The first stop for every WordPress developer
Version:      1.2.5
Author:       Automattic
Author URI:   http://automattic.com
License:      GPLv2 or later

Text Domain:  a8c-developer
Domain Path:  /languages/

**************************************************************************/

// Load helper class if installing a plugin
if ( ! empty( $_POST['action'] ) && 'a8c_developer_install_plugin' == $_POST['action'] )
	require_once( dirname( __FILE__ ) . '/includes/class-empty-upgrader-skin.php' );


class Automattic_Developer {

	public $settings               = array();
	public $default_settings       = array();

	const VERSION                  = '1.2.5';
	const OPTION                   = 'a8c_developer';
	const PAGE_SLUG                = 'a8c_developer';

	private $recommended_plugins   = array();
	private $recommended_constants = array();

	function __construct() {
		add_action( 'init', 									array( $this, 'load_plugin_textdomain') );
		add_action( 'init',										array( $this, 'init' ) );
		add_action( 'admin_init',								array( $this, 'admin_init' ) );

		add_action( 'admin_menu',								array( $this, 'register_settings_page' ) );
		add_action( 'admin_bar_menu',							array( $this, 'add_node_to_admin_bar' ) );

		add_action( 'admin_enqueue_scripts',					array( $this, 'load_settings_page_script_and_style' ) );

		add_action( 'wp_ajax_a8c_developer_lightbox_step_1',	array( $this, 'ajax_handler' ) );
		add_action( 'wp_ajax_a8c_developer_install_plugin',		array( $this, 'ajax_handler' ) );
		add_action( 'wp_ajax_a8c_developer_activate_plugin',	array( $this, 'ajax_handler' ) );

		if ( defined ( 'WP_CLI' ) && WP_CLI )
			require_once( __DIR__ . '/includes/class-wp-cli-commands.php' );
	}

	// Internationalization
	function load_plugin_textdomain () {
		load_plugin_textdomain ( 'a8c-developer', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	// Allows private variables to be read. Basically implements read-only variables.
	function __get( $var ) {
		return ( isset( $this->$var ) ) ? $this->$var : null;
	}

	public function init() {

		$this->default_settings = array(
			'project_type' => false,
		);

		$this->settings = wp_parse_args( (array) get_option( self::OPTION ), $this->default_settings );
	}

	public function admin_init() {
		if ( ! empty( $_GET['developer_plugin_reset'] ) && current_user_can( 'manage_options' ) ) {
			delete_option( self::OPTION );
		}

		$this->recommended_plugins = array(
			'debug-bar' => array(
				'project_type' => 'all',
				'name'         => esc_html__( 'Debug Bar', 'a8c-developer' ),
				'active'       => class_exists( 'Debug_Bar' ),
			),
			'debug-bar-console' => array(
				'project_type' => 'all',
				'name'         => esc_html__( 'Debug Bar Console', 'a8c-developer' ),
				'active'       => function_exists( 'debug_bar_console_panel' ),
			),
			'debug-bar-cron' => array(
				'project_type' => 'all',
				'name'         => esc_html__( 'Debug Bar Cron', 'a8c-developer' ),
				'active'       => function_exists( 'zt_add_debug_bar_cron_panel' ),
			),
			'debug-bar-extender' => array(
				'project_type' => 'all',
				'name'         => esc_html__( 'Debug Bar Extender', 'a8c-developer' ),
				'active'       => class_exists( 'Debug_Bar_Extender' ),
			),
			'rewrite-rules-inspector' => array(
				'project_type' 	=> 'all',
				'name' 		=> esc_html__( 'Rewrite Rules Inspector', 'a8c-developer' ),
				'active'	=> class_exists( 'Rewrite_Rules_Inspector' ),
			),
			'log-deprecated-notices' => array(
				'project_type' => 'all',
				'name'         => esc_html__( 'Log Deprecated Notices', 'a8c-developer' ),
				'active'       => class_exists( 'Deprecated_Log' ),
			),
			'log-viewer' => array(
				'project_type' => 'all',
				'name'         => esc_html__( 'Log Viewer', 'a8c-developer' ),
				'active'       => class_exists( 'ciLogViewer' ),
			),
			'vip-scanner' => array(
				'project_type' => 'wpcom-vip',
				'name'         => esc_html__( 'VIP Scanner', 'a8c-developer' ),
				'active'       => class_exists( 'VIP_Scanner' ),
			),
			'jetpack' => array(
				'project_type' => 'wpcom-vip',
				'name'         => esc_html__( 'Jetpack', 'a8c-developer' ),
				'active'       => class_exists( 'Jetpack' ),
			),
			'polldaddy' => array(
				'project_type' => 'wpcom-vip',
				'name'         => esc_html__( 'Polldaddy Polls & Ratings', 'a8c-developer' ),
				'active'       => class_exists( 'WP_Polldaddy' ),
			),
			'monster-widget' => array(
				'project_type' => 'all',
				'name'         => esc_html__( 'Monster Widget', 'a8c-developer' ),
				'active'       => class_exists( 'Monster_Widget' ),
			),
			'user-switching' => array(
				'project_type' => 'all',
				'name'         => esc_html__( 'User Switching', 'a8c-developer' ),
				'active'       => class_exists( 'user_switching' ),
			),
			'piglatin' => array(
				'project_type' 	=> array( 'wporg-theme', 'wporg' ),
				'name'		=> esc_html__( 'Pig Latin', 'a8c-developer' ),
				'active'	=> class_exists( 'PigLatin' ),
			),
			'mp6' => array(
				'project_type' => 'wpcom-vip',
				'name' 		   => esc_html__( 'MP6', 'a8c-developer' ),
				'active'       => function_exists( 'mp6_replace_wp_default_styles' ),
			),

			// Theme Developer
			'rtl-tester' => array(
				'project_type' => 'wporg-theme',
				'name'         => esc_html__( 'RTL Tester', 'a8c-developer' ),
				'active'       => class_exists( 'RTLTester' ),
			),
			'regenerate-thumbnails' => array(
				'project_type' => 'wporg-theme',
				'name'         => esc_html__( 'Regenerate Thumbnails', 'a8c-developer' ),
				'active'       => class_exists( 'RegenerateThumbnails' ),
			),
			'simply-show-ids' => array(
				'project_type' => 'wporg-theme',
				'name'         => esc_html__( 'Simply Show IDs', 'a8c-developer' ),
				'active'       => function_exists( 'ssid_add' ),
			),
			'theme-test-drive' => array(
				'project_type' => 'wporg-theme',
				'name'         => esc_html__( 'Theme Test Drive', 'a8c-developer' ),
				'active'       => function_exists( 'TTD_filters' ),
				'filename'     => 'themedrive.php',
			),
			'theme-check' => array(
				'project_type' => 'wporg-theme',
				'name'         => esc_html__( 'Theme Check', 'a8c-developer' ),
				'active'       => function_exists( 'tc_add_headers' ),
			),
		);

		if ( ! self::is_dev_version() ) {
			$this->recommended_plugins['wordpress-beta-tester'] = array(
				'project_type' => 'all',
				'name'         => esc_html__( 'Beta Tester', 'a8c-developer' ),
				'active'       => class_exists( 'wp_beta_tester' ),
				'filename'     => 'wp-beta-tester.php',
			);
		}

		$this->recommended_constants = array(
			'WP_DEBUG'    => array(
				'project_type'	=> 'all',
				'description' 	=> __( 'Enables <a href="http://codex.wordpress.org/Debugging_in_WordPress" target="_blank">debug mode</a> which helps identify and resolve issues', 'a8c-developer' )
			),
			'SAVEQUERIES' => array(
				'project_type'	=> 'all',
				'description'	=> esc_html__( 'Logs database queries to an array so you can review them. The Debug Bar plugin will list out database queries if you set this constant.', 'a8c-developer' )
			),
			'JETPACK_DEV_DEBUG'	=> array(
				'project_type'	=> 'wpcom-vip',
				'description'	=> __( 'Enables <a href="http://jetpack.me/2013/03/28/jetpack-dev-mode-release/">Development Mode</a> in Jetpack for testing features without a connection to WordPress.com.', 'a8c-developer' )
			)
		);

		register_setting( self::OPTION, self::OPTION, array( $this, 'settings_validate' ) );

		wp_register_script( 'a8c-developer', plugins_url( 'developer.js', __FILE__ ), array( 'jquery' ), self::VERSION );
		$strings = array(
			'settings_slug'  => self::PAGE_SLUG,
			'go_to_step_2'   => ( current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' ) && 'direct' == get_filesystem_method() ) ? 'yes' : 'no',
			'lightbox_title' => __( 'Developer: Plugin Setup', 'a8c-developer' ),
			'saving'         => __( 'Saving...', 'a8c-developer' ),
			'installing'     => '<img src="images/loading.gif" alt="" /> ' . esc_html__( 'Installing...', 'a8c-developer' ),
			'installed'      => __( 'Installed', 'a8c-developer' ),
			'activating'     => '<img src="images/loading.gif" alt="" /> ' . esc_html__( 'Activating...', 'a8c-developer' ),
			'activated'      => __( 'Activated', 'a8c-developer' ),
			'error'          => __( 'Error!', 'a8c-developer' ),
			'ACTIVE'      	 => __( 'ACTIVE', 'a8c-developer' ),
			'INSTALLED'      => __( 'INSTALLED', 'a8c-developer' ),
			'ERROR'          => __( 'ERROR!', 'a8c-developer' ),
		);
		wp_localize_script( 'a8c-developer', 'a8c_developer_i18n', $strings );

		wp_register_style( 'a8c-developer', plugins_url( 'developer.css', __FILE__ ), array(), self::VERSION );

		// Handle the submission of the lightbox form if step 2 won't be shown
		if ( ! empty( $_POST['action'] ) && 'a8c_developer_lightbox_step_1' == $_POST['action'] && ! empty( $_POST['a8c_developer_project_type'] ) && check_admin_referer( 'a8c_developer_lightbox_step_1' ) ) {
			$this->save_project_type( $_POST['a8c_developer_project_type'] );
			add_settings_error( 'general', 'settings_updated', __( 'Settings saved.' ), 'updated' );
		}

		if ( ! get_option( self::OPTION ) ) {
			if ( ! empty( $_GET['a8cdev_errorsaving'] ) ) {
				add_settings_error( self::PAGE_SLUG, self::PAGE_SLUG . '_error_saving', __( 'Error saving settings. Please try again.', 'a8c-developer' ) );
			} elseif ( ! is_network_admin() && current_user_can( 'manage_options' ) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'load_lightbox_scripts_and_styles' ) );
				add_action( 'admin_footer', array( $this, 'output_setup_box_html' ) );
			}
		}
	}

	public function register_settings_page() {
		add_management_page( esc_html__( 'Developer Helper', 'a8c-developer' ), esc_html__( 'Developer', 'a8c-developer' ), 'manage_options', self::PAGE_SLUG, array( $this, 'settings_page' ) );
	}

	public function add_node_to_admin_bar( $wp_admin_bar ) {

		if ( !current_user_can( 'manage_options' ) )
			return;

		$wp_admin_bar->add_node( array(
			'id'     => self::PAGE_SLUG,
			'title'  => esc_html__( 'Developer', 'a8c-developer' ),
			'parent' => 'top-secondary', // Off on the right side
			'href'   => admin_url( 'tools.php?page=' . self::PAGE_SLUG ),
			'meta'   => array(
			'title'  => esc_html__( 'View the Developer Helper settings and status page', 'a8c-developer' ),
			),
		) );
	}

	public function load_settings_page_script_and_style( $hook_suffix ) {
		if ( 'tools_page_' . self::PAGE_SLUG != $hook_suffix )
			return;

		wp_enqueue_script( 'a8c-developer' );
		wp_enqueue_style( 'a8c-developer' );
	}

	public function load_lightbox_scripts_and_styles() {
		wp_enqueue_script( 'colorbox', plugins_url( 'colorbox/jquery.colorbox-min.js', __FILE__ ), array( 'jquery' ), '1.3.19' );
		wp_enqueue_style( 'a8c-developer-colorbox', plugins_url( 'colorbox/colorbox.css', __FILE__ ), array(), '1.3.19' );

		wp_enqueue_script( 'a8c-developer' );
		wp_enqueue_style( 'a8c-developer' );
	}

	public function output_setup_box_html() {
?>

		<div style="display:none">
			<div id="a8c-developer-setup-dialog-step-1" class="a8c-developer-dialog">
				<strong><?php esc_html_e( "Thanks for installing the Developer Helper plugin!", 'a8c-developer' ); ?></strong>

				<p><?php esc_html_e( 'Before we begin, what type of website are you developing?', 'a8c-developer' ); ?></p>

				<form id="a8c-developer-setup-dialog-step-1-form" action="tools.php?page=a8c_developer" method="post">
					<?php wp_nonce_field( 'a8c_developer_lightbox_step_1' ); ?>
					<input type="hidden" name="action" value="a8c_developer_lightbox_step_1" />

					<?php $i = 0; ?>
					<?php foreach ( $this->get_project_types() as $project_slug => $project_description ) : ?>
						<?php $i++; ?>
						<p>
							<label>
								<input type="radio" name="a8c_developer_project_type" value="<?php echo esc_attr( $project_slug ); ?>" <?php checked( $i, 1 ); ?> />
								<?php echo $project_description; ?>
							</label>
						</p>
					<?php endforeach; ?>

					<?php submit_button( null, 'primary', 'a8c-developer-setup-dialog-step-1-submit' ); ?>
				</form>
			</div>
			<div id="a8c-developer-setup-dialog-step-2" class="a8c-developer-dialog">
				<!-- This gets populated via AJAX -->
			</div>
		</div>

		<script type="text/javascript">a8c_developer_lightbox();</script>
<?php
	}

	public function ajax_handler( $action ) {
		$action = isset( $_POST['action'] ) ? $_POST['action'] : $action;
		switch ( $action ) {

			case 'a8c_developer_lightbox_step_1':
				check_ajax_referer( 'a8c_developer_lightbox_step_1' );

				if ( empty( $_POST['a8c_developer_project_type'] ) )
					die( '-1' );

				$this->save_project_type( $_POST['a8c_developer_project_type'] );

				$to_install_or_enable = 0;

				$recommended_plugins = $this->get_recommended_plugins();

				foreach ( $recommended_plugins as $plugin_slug => $plugin_details ) {
					if ( ! $plugin_details['active'] ) {
						$to_install_or_enable++;
					}
				}

				// If no plugins to take action on, head to the settings page
				if ( ! $to_install_or_enable )
					die( 'redirect' );

				echo '<strong>' . esc_html__( 'Plugins', 'a8c-developer' ) . '</strong>';

				echo '<p>' . esc_html__( 'We recommend that you also install and activate the following plugins:', 'a8c-developer' ) . '</p>';

				echo '<table class="recommended-plugins">';

					foreach ( $recommended_plugins as $plugin_slug => $plugin_details ) {
						if ( $plugin_details['active'] )
							continue;

						echo '<tr>';

						$details = $this->get_plugin_details( $plugin_slug );

						if ( is_wp_error( $details ) )
							$details = array();

						$plugin_details = array_merge( (array) $details, array( 'slug' => $plugin_slug ), $plugin_details );

						echo '<td><strong>' . $plugin_details['name'] . '</strong></td>';

						echo '<td>';

						if ( $this->is_recommended_plugin_installed( $plugin_slug ) ) {
							$path = $this->get_path_for_recommended_plugin( $plugin_slug );

							echo '<button type="button" class="a8c-developer-button-activate" data-path="' . esc_attr( $path ) . '" data-nonce="' . wp_create_nonce( 'a8c_developer_activate_plugin_' . $path ) . '">' . esc_html__( 'Activate', 'a8c-developer' ) . '</button>';
						} else {
							echo '<button type="button" class="a8c-developer-button-install" data-pluginslug="' . esc_attr( $plugin_slug ) . '" data-nonce="' . wp_create_nonce( 'a8c_developer_install_plugin_' . $plugin_slug ) . '">' . esc_html__( 'Install', 'a8c-developer' ) . '</button>';
						}

						if ( ! empty( $plugin_details['short_description'] ) )
								echo '<br /><span class="description">' . esc_html__( $plugin_details['short_description'] ) . '</span>';

						echo '</td>';

						echo '</tr>';
					}

				echo '<tr><td colspan="2"><button type="button" class="button button-primary a8c-developer-button-close">' . esc_html__( 'Get Developing!', 'a8c-developer' ) . '</button></td></tr>';

				echo '</table>';

				echo '<script type="text/javascript">a8c_developer_bind_events();</script>';

				exit();

			case 'a8c_developer_install_plugin':
				if ( empty( $_POST['plugin_slug'] ) )
					die( __( 'ERROR: No slug was passed to the AJAX callback.', 'a8c-developer' ) );

				check_ajax_referer( 'a8c_developer_install_plugin_' . $_POST['plugin_slug'] );

				if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) )
					die( __( 'ERROR: You lack permissions to install and/or activate plugins.', 'a8c-developer' ) );

				include_once ( ABSPATH . 'wp-admin/includes/plugin-install.php' );

				$api = plugins_api( 'plugin_information', array( 'slug' => $_POST['plugin_slug'], 'fields' => array( 'sections' => false ) ) );

				if ( is_wp_error( $api ) )
					die( sprintf( __( 'ERROR: Error fetching plugin information: %s', 'a8c-developer' ), $api->get_error_message() ) );

				$upgrader = new Plugin_Upgrader( new Automattic_Developer_Empty_Upgrader_Skin( array(
					'nonce'  => 'install-plugin_' . $_POST['plugin_slug'],
					'plugin' => $_POST['plugin_slug'],
					'api'    => $api,
				) ) );

				$install_result = $upgrader->install( $api->download_link );

				if ( ! $install_result || is_wp_error( $install_result ) ) {
					// $install_result can be false if the file system isn't writeable.
					$error_message = __( 'Please ensure the file system is writeable', 'a8c-developer' );

					if ( is_wp_error( $install_result ) )
						$error_message = $install_result->get_error_message();

					die( sprintf( __( 'ERROR: Failed to install plugin: %s', 'a8c-developer' ), $error_message ) );
				}

				$activate_result = activate_plugin( $this->get_path_for_recommended_plugin( $_POST['plugin_slug'] ) );

				if ( is_wp_error( $activate_result ) )
					die( sprintf( __( 'ERROR: Failed to activate plugin: %s', 'a8c-developer' ), $activate_result->get_error_message() ) );

				exit( '1' );

			case 'a8c_developer_activate_plugin':
				if ( empty( $_POST['path'] ) )
					die( __( 'ERROR: No slug was passed to the AJAX callback.', 'a8c-developer' ) );

				check_ajax_referer( 'a8c_developer_activate_plugin_' . $_POST['path'] );

				if ( ! current_user_can( 'activate_plugins' ) )
					die( __( 'ERROR: You lack permissions to activate plugins.', 'a8c-developer' ) );

				$activate_result = activate_plugin( $_POST['path'] );

				if ( is_wp_error( $activate_result ) )
					die( sprintf( __( 'ERROR: Failed to activate plugin: %s', 'a8c-developer' ), $activate_result->get_error_message() ) );

				exit( '1' );
		}

		// Unknown action
		die( '-1' );
	}

	public function settings_page() {
		add_settings_section( 'a8c_developer_main', esc_html__( 'Main Configuration', 'a8c-developer' ), '__return_false', self::PAGE_SLUG . '_settings' );
		add_settings_field( 'a8c_developer_project_type', esc_html__( 'Project Type', 'a8c-developer' ), array( $this, 'settings_field_radio' ), self::PAGE_SLUG . '_settings', 'a8c_developer_main', array(
			'name'        => 'project_type',
			'description' => '',
			'options'     => $this->get_project_types(),
		) );

		echo '<script type="text/javascript">
			jQuery(function( $ ) {
				a8c_developer_bind_settings_events();
			});
		</script>';

		// Plugins
		add_settings_section( 'a8c_developer_plugins', esc_html__( 'Plugins', 'a8c-developer' ), array( $this, 'settings_section_plugins' ), self::PAGE_SLUG . '_status' );

		wp_enqueue_script( 'plugin-install' );

		add_thickbox();

		$recommended_plugins = $this->get_recommended_plugins();

		foreach ( $recommended_plugins as $plugin_slug => $plugin_details ) {
			$details = $this->get_plugin_details( $plugin_slug );

			if ( is_wp_error( $details ) )
				$details = array();

			$plugin_details = array_merge( (array) $details, array( 'slug' => $plugin_slug ), $plugin_details );

			$label = '<strong>' . esc_html( $plugin_details['name'] ) . '</strong>';

			$label .= '<br /><a href="' . self_admin_url( 'plugin-install.php?tab=plugin-information&amp;plugin=' . $plugin_slug .
								'&amp;TB_iframe=true&amp;width=600&amp;height=550' ) . '" class="thickbox" title="' .
								esc_attr( sprintf( __( 'More information about %s' ), $plugin_details['name'] ) ) . '">' . __( 'Details' ) . '</a>';

			add_settings_field( 'a8c_developer_plugin_' . $plugin_slug, $label, array( $this, 'settings_field_plugin' ), self::PAGE_SLUG . '_status', 'a8c_developer_plugins', $plugin_details );
		}

		// Constants
		add_settings_section( 'a8c_developer_constants', esc_html__( 'Constants', 'a8c-developer' ), array( $this, 'settings_section_constants' ), self::PAGE_SLUG . '_status' );

		$recommended_constants = $this->get_recommended_constants();

		foreach ( $recommended_constants as $constant => $constant_details ) {
			add_settings_field( 'a8c_developer_constant_' . $constant, $constant, array( $this, 'settings_field_constant' ), self::PAGE_SLUG . '_status', 'a8c_developer_constants', array(
				'constant'    => $constant,
				'description' => $constant_details['description'],
			) );
		}

		// Settings
		add_settings_section( 'a8c_developer_settings', esc_html__( 'Settings', 'a8c-developer' ), array( $this, 'settings_section_settings' ), self::PAGE_SLUG . '_status' );
		add_settings_field( 'a8c_developer_setting_permalink_structure', esc_html__( 'Pretty Permalinks', 'a8c-developer' ), array( $this, 'settings_field_setting_permalink_structure' ), self::PAGE_SLUG . '_status', 'a8c_developer_settings' );
		if ( 'wpcom-vip' == $this->settings['project_type'] ) {
			add_settings_field( 'a8c_developer_setting_development_version', esc_html__( 'Development Version', 'a8c-developer' ), array( $this, 'settings_field_setting_development_version' ), self::PAGE_SLUG . '_status', 'a8c_developer_settings' );
			add_settings_field( 'a8c_developer_setting_shared_plugins', esc_html__( 'Shared Plugins', 'a8c-developer' ), array( $this, 'settings_field_setting_shared_plugins' ), self::PAGE_SLUG . '_status', 'a8c_developer_settings' );
		}

		// Resources
		add_settings_section( 'a8c_developer_resources', esc_html__( 'Resources', 'a8c-developer' ), array( $this, 'settings_section_resources' ), self::PAGE_SLUG . '_status' );

		add_settings_field( 'a8c_developer_setting_codex', esc_html__( 'Codex', 'a8c-developer' ), array( $this, 'settings_field_setting_resource_codex' ), self::PAGE_SLUG . '_status', 'a8c_developer_resources' );

		if ( 'wpcom-vip' == $this->settings['project_type'] )
			add_settings_field( 'a8c_developer_setting_vip_docs', esc_html__( 'VIP Docs', 'a8c-developer' ), array( $this, 'settings_field_setting_resource_vip_docs' ), self::PAGE_SLUG . '_status', 'a8c_developer_resources' );

		if ( in_array( $this->settings['project_type'], array( 'wporg-theme', 'wpcom-vip' ) ) )
			add_settings_field( 'a8c_developer_setting_starter_themes', esc_html__( 'Starter Themes', 'a8c-developer' ), array( $this, 'settings_field_setting_resource_starter_themes' ), self::PAGE_SLUG . '_status', 'a8c_developer_resources' );

		# Add more sections and fields here as needed
?>

		<div class="wrap">

		<?php screen_icon( 'tools' ); ?>

		<h2><?php esc_html_e( 'Developer Helper', 'a8c-developer' ); ?></h2>

		<form action="options.php" method="post">

			<?php settings_fields( self::OPTION ); // matches value from register_setting() ?>

			<?php do_settings_sections( self::PAGE_SLUG . '_settings' ); // matches values from add_settings_section/field() ?>

			<?php submit_button(); ?>

			<?php do_settings_sections( self::PAGE_SLUG . '_status' ); ?>
		</form>

		</div>
<?php
	}

	public function settings_field_radio( $args ) {
		if ( empty( $args['name'] ) || ! is_array( $args['options'] ) )
			return false;

		$selected = ( isset( $this->settings[ $args['name'] ] ) ) ? $this->settings[ $args['name'] ] : '';

		foreach ( (array) $args['options'] as $value => $label )
			echo '<p><label><input type="radio" name="a8c_developer[' . esc_attr( $args['name'] ) . ']" value="' . esc_attr( $value ) . '"' . checked( $value, $selected, false ) . '> ' . $label . '</input></label></p>';

		if ( ! empty( $args['description'] ) )
			echo ' <p class="description">' . $args['description'] . '</p>';
	}

	public function settings_field_select( $args ) {
		if ( empty( $args['name'] ) || ! is_array( $args['options'] ) )
			return false;

		$selected = ( isset( $this->settings[ $args['name'] ] ) ) ? $this->settings[ $args['name'] ] : '';

		echo '<select name="a8c_developer[' . esc_attr( $args['name'] ) . ']">';

		foreach ( (array) $args['options'] as $value => $label )
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $value, $selected, false ) . '>' . $label . '</option>';

		echo '</select>';

		if ( ! empty( $args['description'] ) )
			echo ' <p class="description">' . $args['description'] . '</p>';
	}

	public function settings_section_plugins() {
		echo '<p>' . esc_html__( 'We recommend you have the following plugins installed:', 'a8c-developer' ) . '</p>';
	}

	public function settings_field_plugin( $args ) {
		if ( $args['active'] ) {
			echo '<span class="a8c-developer-active">' . esc_html__( 'ACTIVE', 'a8c-developer' ) . '</span>';
		} elseif ( $this->is_recommended_plugin_installed( $args['slug'] ) ) {
			// Needs to be activated
			if ( current_user_can('activate_plugins') ) {
				$path = $this->get_path_for_recommended_plugin( $args['slug'] );
				echo '<a class="a8c-developer-notactive a8c-developer-button-activate" href="' . esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $path ), 'activate-plugin_' . $path ) ) . '" data-path="' . esc_attr( $path ) . '" data-nonce="' . wp_create_nonce( 'a8c_developer_activate_plugin_' . $path ) . '" title="' . esc_attr__( 'Click here to activate', 'a8c-developer' ) . '">' . esc_html__( 'INACTIVE', 'a8c-developer' ) . ' - <em>' . esc_html__( 'Click to Activate', 'a8c-developer' ) . '</em></a>';
			} else {
				echo '<span class="a8c-developer-notactive">' . esc_html__( 'INACTIVE', 'a8c-developer' ) . '</span>';
			}
		} else {
			// Needs to be installed
			if ( current_user_can('install_plugins') ) {
				echo '<a class="a8c-developer-notactive a8c-developer-button-install" href="' . esc_url( wp_nonce_url( admin_url( 'update.php?action=install-plugin&plugin=' . $args['slug'] ), 'install-plugin_' . $args['slug'] ) ) . '" data-pluginslug="' . esc_attr( $args['slug'] ) . '" data-nonce="' . wp_create_nonce( 'a8c_developer_install_plugin_' . $args['slug'] ) . '" title="' . esc_attr__( 'Click here to install', 'a8c-developer' ) . '">' . esc_html__( 'NOT INSTALLED', 'a8c-developer' ) . ' - <em>' . esc_html__( 'Click to Install', 'a8c-developer' ) . '</em></a>';
			} else {
				echo '<span class="a8c-developer-notactive">' . esc_html__( 'NOT INSTALLED', 'a8c-developer' ) . '</span>';
			}
		}

		if ( ! empty( $args['short_description'] ) )
			echo '<br /><span class="description">' . $args['short_description']  . '</span>';
	}

	public function settings_section_constants() {
		echo '<p>' . __( 'We recommend you set the following constants to <code>true</code> in your <code>wp-config.php</code> file. <a href="http://codex.wordpress.org/Editing_wp-config.php" target="_blank">Need help?</a>', 'a8c-developer' ) . '</p>';
	}

	public function settings_field_constant( $args ) {
		if ( defined( $args['constant'] ) && constant( $args['constant'] ) ) {
			echo '<span class="a8c-developer-active">' . esc_html__( 'SET', 'a8c-developer' ) . '</span>';
		} else {
			echo '<span class="a8c-developer-notactive">' . esc_html__( 'NOT SET', 'a8c-developer' ) . '</span>';
		}

		if ( ! empty( $args['description'] ) )
			echo '<br /><span class="description">' . $args['description'] . '</span>';
	}


	public function settings_section_settings() {
		echo '<p>' . esc_html__( 'We recommend the following settings and configurations.', 'a8c-developer' ) . '</p>';
	}

	public function settings_field_setting_permalink_structure() {
		if ( get_option( 'permalink_structure' ) ) {
			echo '<span class="a8c-developer-active">' . esc_html__( 'ENABLED', 'a8c-developer' ) . '</span>';
		} else {
			echo '<a class="a8c-developer-notactive" href="' . admin_url( 'options-permalink.php' ) . '">' . esc_html__( 'DISABLED', 'a8c-developer' ) . '</a> ' . __( '<a href="http://codex.wordpress.org/Using_Permalinks" target="_blank">Need help?</a>', 'a8c-developer' );
		}
	}

	public function settings_field_setting_development_version() {
		if ( self::is_dev_version() ) {
			echo '<span class="a8c-developer-active">' . esc_html__( 'ENABLED', 'a8c-developer' ) . '</span>';
		} else {
			echo '<a href="'. network_admin_url( 'update-core.php' ) .'" class="a8c-developer-notactive">' . esc_html__( 'DISABLED', 'a8c-developer' ) . '</a>';
		}
	}

	public function settings_field_setting_shared_plugins() {
		if ( file_exists( WP_CONTENT_DIR . '/themes/vip' ) && file_exists( WP_CONTENT_DIR . '/themes/vip/plugins' ) ) {
			echo '<span class="a8c-developer-active">' . esc_html__( 'ENABLED', 'a8c-developer' ) . '</span>';
		} else {
			echo '<a href="http://vip.wordpress.com/documentation/development-environment/#plugins-and-helper-functions" class="a8c-developer-notactive">' . esc_html__( 'DISABLED', 'a8c-developer' ) . '</a>';
		}
	}

	public function settings_section_resources() {}

	public function settings_field_setting_resource_codex() {
		_e( "The <a href='http://codex.wordpress.org/Developer_Documentation'>Developer Documentation section</a> of the Codex offers guidelines and references for anyone wishing to modify, extend, or contribute to WordPress.", 'a8c-developer' );
	}

	public function settings_field_setting_resource_vip_docs() {
		_e( "The <a href='http://vip.wordpress.com/documentation/'>VIP Documentation</a> is a technical resource for developing sites on WordPress.com including best practices and helpful tips to help you code better, faster, and stronger.", 'a8c-developer' );
	}

	public function settings_field_setting_resource_starter_themes() {
		_e( "<a href='http://underscores.me'>_s (or underscores)</a>: a starter theme meant for hacking that will give you a \"1000-Hour Head Start\". Use it to create the next, most awesome WordPress theme out there.", 'a8c-developer' );
	}

	public function settings_validate( $raw_settings ) {
		$settings = array();

		$project_type_slugs = array_keys( $this->get_project_types() );
		if ( empty( $raw_settings['project_type'] ) || ! in_array( $raw_settings['project_type'], $project_type_slugs ) )
			$settings['project_type'] = current( $project_type_slugs );
		else
			$settings['project_type'] = $raw_settings['project_type'];

		return $settings;
	}

	public function save_project_type( $type ) {
		$settings = $this->settings;
		$settings['project_type'] = $type;

		$this->settings = $this->settings_validate( $settings );

		update_option( self::OPTION, $this->settings );
	}

	public function get_path_for_recommended_plugin( $slug ) {
		$filename = ( ! empty( $this->recommended_plugins[$slug]['filename'] ) ) ? $this->recommended_plugins[$slug]['filename'] : $slug . '.php';

		return $slug . '/' . $filename;
	}

	public function is_recommended_plugin_active( $slug ) {
		if ( empty( $this->recommended_plugins[$slug] ) )
			return false;

		return $this->recommended_plugins[$slug]['active'];
	}

	public function is_recommended_plugin_installed( $slug ) {
		if ( empty( $this->recommended_plugins[$slug] ) )
			return false;

		if ( $this->is_recommended_plugin_active( $slug ) || file_exists( WP_PLUGIN_DIR . '/' . $this->get_path_for_recommended_plugin( $slug ) ) )
			return true;

		return false;
	}

	/**
	 * Retrieve plugin information for a given $slug
	 *
	 * Note that this does not use plugins_api(), as the .org api does not return
	 * short descriptions in POST requests (that api endpoint is different from this one)
	 *
	 * @param string $slug The plugin slug
	 * @return object The response object containing plugin details
	 */
	public function get_plugin_details( $slug ){
		$cache_key = md5( 'a8c_developer_plugin_details_' . $slug );

		if ( false === ( $details = get_transient( $cache_key ) ) ) {
			$request = wp_remote_get( 'http://api.wordpress.org/plugins/info/1.0/' . esc_url( $slug ), array( 'timeout' => 15 ) );

			if ( is_wp_error( $request ) ) {
				$details = new WP_Error('a8c_developer_plugins_api_failed', __( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="http://wordpress.org/support/">support forums</a>.' ), $request->get_error_message() );
			} else {
				$details = maybe_unserialize( wp_remote_retrieve_body( $request ) );

				if ( ! is_object( $details ) && ! is_array( $details ) )
					$details = new WP_Error('a8c_developer_plugins_api_failed', __( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="http://wordpress.org/support/">support forums</a>.' ), wp_remote_retrieve_body( $request ) );
				else
					set_transient( $cache_key, $details, WEEK_IN_SECONDS );
			}
		}

		return $details;
	}

	/**
	 * Return an array of all plugins recommended for the current project type
	 *
	 * Only returns plugins that have been recommended for the project type defined
	 * in $this->settings['project_type']
	 *
	 * @return array An array of plugins recommended for the current project type
	 */
	public function get_recommended_plugins() {
		return $this->get_recommended_plugins_by_type( $this->settings['project_type'] );
	}

	/**
	 * Return an array of all plugins recommended for the given project type
	 *
	 * @param  string $type The project type to return plugins for
	 * @return array An associative array of plugins for the project type
	 */
	public function get_recommended_plugins_by_type( $type ) {
		$plugins_by_type = array();

		foreach( $this->recommended_plugins as $plugin_slug => $plugin_details ) {
			if ( ! $this->plugin_is_recommended_for_project_type( $plugin_slug, $type ) )
				continue;

			$plugins_by_type[ $plugin_slug ] = $plugin_details;
		}

		return $plugins_by_type;
	}

	/**
	 * Should the given plugin be recommended for the given project type?
	 *
	 * Determines whether or not a given $plugin_slug is recommended for a given $project_type
	 * by checking the project types defined for it
	 *
	 * @param  string $plugin_slug The plugin slug to check
	 * @param  string $project_type The project type to check the plugin against
	 * @return bool Boolean indicating if the plugin is recommended for the project type
	 */
	public function plugin_is_recommended_for_project_type( $plugin_slug, $project_type = null ) {
		if ( null == $project_type )
			$project_type = $this->settings['project_type'];

		$plugin_details = $this->recommended_plugins[ $plugin_slug ];

		if ( 'all' == $plugin_details['project_type'] )
			return true;

		return self::is_project_type( $plugin_details, $project_type );
	}

	/**
	 * Return an array of all constants recommended for the current project type
	 *
	 * Only returns constants that have been recommended for the project type defined
	 * in $this->settings['project_type']
	 *
	 * @return array An array of constants recommended for the current project type
	 */
	public function get_recommended_constants() {
		return $this->get_recommended_constants_by_type( $this->settings['project_type'] );
	}

	/**
	 * Return an array of all constants recommended for the given project type
	 *
	 * @param  string $type The project type to return constants for
	 * @return array An associative array of constants for the project type
	 */
	public function get_recommended_constants_by_type( $type ) {
		$constants_by_type = array();

		foreach( $this->recommended_constants as $constant => $constant_details ) {
			if ( ! $this->constant_is_recommended_for_project_type( $constant, $type ) )
				continue;

			$constants_by_type[ $constant ] = $constant_details;
		}

		return $constants_by_type;
	}

	/**
	 * Should the given constant be recommended for the given project type?
	 *
	 * Determines whether or not a given $constant is recommended for a given $project_type
	 * by checking the project types defined for it
	 *
	 * @param  string $constant The constant to check
	 * @param  string $project_type The project type to check the constant against
	 * @return bool Boolean indicating if the constant is recommended for the project type
	 */
	public function constant_is_recommended_for_project_type( $constant, $project_type = null ) {
		if ( null == $project_type )
			$project_type = $this->settings['project_type'];

		$constant_details = $this->recommended_constants[ $constant ];

		if ( 'all' == $constant_details['project_type'] )
			return true;

		return self::is_project_type( $constant_details, $project_type );
	}

	public function get_project_types() {
		return array(
			'wporg'       => __( 'Plugin for a self-hosted WordPress installation', 'a8c-developer' ),
			'wporg-theme' => __( 'Theme for a self-hosted WordPress installation', 'a8c-developer' ),
			'wpcom-vip'   => __( 'Theme for a <a href="http://vip.wordpress.com" target="_blank">WordPress.com VIP</a> site', 'a8c-developer' ),
		);
	}

	private static function is_dev_version() {
		$cur = get_preferred_from_update_core();
		return $cur->response == 'development';
	}

	private static function is_project_type( $project, $type ) {
		$project_type = $project['project_type'];

		if ( is_array( $project_type ) )
			return in_array( $type, $project_type );

		return $project_type == $type;
	}
}

$automattic_developer = new Automattic_Developer();
