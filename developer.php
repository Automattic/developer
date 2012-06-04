<?php /*

**************************************************************************

Plugin Name:  Developer
Plugin URI:   http://wordpress.org/extend/plugins/developer/
Description:  The first stop for every WordPress developer
Version:      1.0.0
Author:       Automattic
Author URI:   http://automattic.com/wordpress-plugins/
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

	// Using "private" for read-only functionality. See __get().
	private $version               = '1.0.0';

	private $option_name           = 'a8c_developer';
	private $settings_page_slug    = 'a8c_developer';

	private $recommended_plugins   = array();
	private $recommended_constants = array();

	function __construct() {
		add_action( 'init',           array( &$this, 'init' ) );
		add_action( 'admin_init',     array( &$this, 'admin_init' ) );

		add_action( 'admin_menu',     array( &$this, 'register_settings_page' ) );
		add_action( 'admin_bar_menu', array( &$this, 'add_node_to_admin_bar' ) );

		add_action( 'admin_enqueue_scripts', array( &$this, 'load_settings_page_script_and_style' ) );

		add_action( 'wp_ajax_a8c_developer_lightbox_step_1',  array( &$this, 'ajax_handler' ) );
		add_action( 'wp_ajax_a8c_developer_install_plugin',   array( &$this, 'ajax_handler' ) );
		add_action( 'wp_ajax_a8c_developer_activate_plugin',  array( &$this, 'ajax_handler' ) );

		// TODO: Remove, dev only for cache busting
		$this->version = mt_rand();
	}

	// Allows private variables to be read. Basically implements read-only variables.
	function __get( $var ) {
		return ( isset( $this->$var ) ) ? $this->$var : null;
	}

	public function init() {
		$this->default_settings = array(
			'project_type' => false,
		);

		// TODO: Delete this dev-only code
		if ( ! empty( $_GET['a8c_developer_reset'] ) )
			delete_option( $this->option_name );

		$this->settings = wp_parse_args( (array) get_option( $this->option_name ), $this->default_settings );
	}

	public function admin_init() {
		$this->recommended_plugins = array(
			'debug-bar' => array(
				'project_type' => 'all',
				'name'         => esc_html__( 'Debug Bar', 'a8c-developer' ),
				'active'       => class_exists( 'Debug_Bar' ),
			),
			'debug-bar-cron' => array(
				'project_type' => 'all',
				'name'         => esc_html__( 'Debug Bar Cron', 'a8c-developer' ),
				'active'       => function_exists( 'zt_add_debug_bar_cron_panel' ),
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
			'vip-scanner' => array(
				'project_type' => 'all',
				'name'         => esc_html__( 'VIP Scanner', 'a8c-developer' ),
				'active'       => class_exists( 'VIP_Scanner' ),
			),
			/*
			'jetpack' => array(
				'project_type' => 'wpcom-vip',
				'name'   => esc_html__( 'Jetpack', 'a8c-developer' ),
				'active' => class_exists( 'Jetpack' ),
			),
			/**/
			'grunion-contact-form' => array(
				'project_type' => 'wpcom-vip',
				'name'         => esc_html__( 'Grunion Contact Form', 'a8c-developer' ),
				'active'       => defined( 'GRUNION_PLUGIN_DIR' ),
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
			/*
			'foobar' => array(
				'name'     => 'Dummy Test Plugin',
				'active'   => false,
				'filename' => 'blah.php',
			),
			/**/
			// TODO: Add more?
		);

		$this->recommended_constants = array(
			'WP_DEBUG'    => __( 'Enables <a href="http://codex.wordpress.org/Debugging_in_WordPress" target="_blank">debug mode</a> which helps identify and resolve issues', 'a8c-developer' ),
			'SAVEQUERIES' => esc_html__( 'Logs database queries to an array so you can review them. The Debug Bar plugin will list out database queries if you set this constant.', 'a8c-developer' ),
			'FOOBAR'      => 'A dummy constant for showing a missing constant',
		);

		register_setting( $this->option_name, $this->option_name, array( &$this, 'settings_validate' ) );


		wp_register_script( 'a8c-developer', plugins_url( 'developer.js', __FILE__ ), array( 'jquery' ), $this->version );
		$strings = array(
			'settings_slug'  => $this->settings_page_slug,
			'go_to_step_2'   => ( current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' ) && 'direct' == get_filesystem_method() ) ? 'yes' : 'no',
			'lightbox_title' => __( 'Developer: Plugin Setup', 'a8c-developer' ),
			'saving'         => __( 'Saving...', 'a8c-developer' ),
			'installing'     => '<img src="images/loading.gif" alt="" /> ' . esc_html__( 'Installing...', 'a8c-developer' ),
			'installed'      => __( 'Installed', 'a8c-developer' ),
			'activating'     => '<img src="images/loading.gif" alt="" /> ' . esc_html__( 'Activating...', 'a8c-developer' ),
			'activated'      => __( 'Activated', 'a8c-developer' ),
			'error'          => __( 'Error!', 'a8c-developer' ),
		);
		wp_localize_script( 'a8c-developer', 'a8c_developer_i18n', $strings );

		wp_register_style( 'a8c-developer', plugins_url( 'developer.css', __FILE__ ), array(), $this->version );


		// Handle the submission of the lightbox form if step 2 won't be shown
		if ( ! empty( $_POST['a8c_developer_action'] ) ) {
			if ( 'lightbox_step_1' == $_POST['a8c_developer_action'] && ! empty( $_POST['a8c_developer_project_type'] ) && check_admin_referer( 'a8c_developer_action_lightbox_step_1' ) ) {
				$this->save_project_type( $_POST['a8c_developer_project_type'] );

				add_settings_error( 'general', 'settings_updated', __( 'Settings saved.' ), 'updated' );
			}
		}

		if ( ! get_option( $this->option_name ) ) {
			if ( ! empty( $_GET['a8cdev_errorsaving'] ) ) {
				add_settings_error( $this->settings_page_slug, $this->settings_page_slug . '_error_saving', __( 'Error saving settings. Please try again.', 'a8c-developer' ) );
			} elseif ( current_user_can( 'manage_options' ) ) {
				add_action( 'admin_enqueue_scripts', array( &$this, 'load_lightbox_scripts_and_styles' ) );
				add_action( 'admin_footer', array( &$this, 'output_setup_box_html' ) );
			}
		}
	}

	public function register_settings_page() {
		add_options_page( esc_html__( 'Automattic Developer Helper', 'a8c-developer' ), esc_html__( 'Developer', 'a8c-developer' ), 'manage_options', $this->settings_page_slug, array( &$this, 'settings_page' ) );
	}

	public function add_node_to_admin_bar( $wp_admin_bar ) {
		$wp_admin_bar->add_node( array(
			'id'     => $this->settings_page_slug,
			'title'  => esc_html__( 'Developer', 'a8c-developer' ),
			'parent' => 'top-secondary', // Off on the right side
			'href'   => admin_url( 'options-general.php?page=' . $this->settings_page_slug ),
			'meta'   => array(
				'title' => esc_html__( 'View the Automattic Developer Helper settings and status page', 'a8c-developer' ),
			),
		) );
	}

	public function load_settings_page_script_and_style( $hook_suffix ) {
		if ( 'settings_page_' . $this->settings_page_slug != $hook_suffix )
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
				<p><em>TODO: Copy+formatting+i18n</em></p>

				<strong><?php esc_html_e( "Thanks for installing Automattic's Developer helper plugin!", 'a8c-developer' ); ?></strong>

				<p><?php esc_html_e( 'Before we begin, what type of website are you developing?', 'a8c-developer' ); ?></p>

				<form id="a8c-developer-setup-dialog-step-1-form" action="options-general.php?page=a8c_developer" method="post">
					<?php wp_nonce_field( 'a8c_developer_lightbox_step_1' ); ?> 
					<input type="hidden" name="action" value="a8c_developer_lightbox_step_1" />

					<p><label><input type="radio" name="a8c_developer_project_type" value="wporg" checked="checked" /> <?php esc_html_e( 'A normal WordPress.org website', 'a8c-developer' ); ?></label></p>
					<p><label><input type="radio" name="a8c_developer_project_type" value="wpcom-vip" /> <?php esc_html_e( 'A website hosted on WordPress.com VIP', 'a8c-developer' ); ?></label></p>

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
		switch ( $action ) {

			case 'a8c_developer_lightbox_step_1':
				check_ajax_referer( 'a8c_developer_lightbox_step_1' );

				if ( empty( $_POST['a8c_developer_project_type'] ) )
					die( '-1' );

				$this->save_project_type( $_POST['a8c_developer_project_type'] );

				$to_install_or_enable = 0;

				foreach ( $this->recommended_plugins as $plugin_slug => $plugin_details ) {
					if ( 'all' != $plugin_details['project_type'] && $plugin_details['project_type'] != $this->settings['project_type'] )
						continue;

					if ( ! $plugin_details['active'] ) {
						$to_install_or_enable++;
					}
				}

				// If no plugins to take action on, head to the settings page
				if ( ! $to_install_or_enable )
					die( 'redirect' );

				echo '<strong>' . esc_html__( 'Plugins', 'a8c-developer' ) . '</strong>';

				echo '<p>' . esc_html__( 'We recommend that you also install and activate the following plugins:', 'a8c-developer' ) . '</p>';

				echo '<ul>';

					foreach ( $this->recommended_plugins as $plugin_slug => $plugin_details ) {
						if ( 'all' != $plugin_details['project_type'] && $plugin_details['project_type'] != $this->settings['project_type'] )
							continue;

						if ( $plugin_details['active'] )
							continue;

						if ( $this->is_recommended_plugin_installed( $plugin_slug ) ) {
							$path = $this->get_path_for_recommended_plugin( $plugin_slug );
							echo '<li>' . $plugin_details['name'] . ' <button type="button" class="a8c-developer-button-activate" data-path="' . esc_attr( $path ) . '" data-nonce="' . wp_create_nonce( 'a8c_developer_activate_plugin_' . $path ) . '">' . esc_html__( 'Activate', 'a8c-developer' ) . '</button></li>';
						} else {
							echo '<li>' . $plugin_details['name'] . ' <button type="button" class="a8c-developer-button-install" data-pluginslug="' . esc_attr( $plugin_slug ) . '" data-nonce="' . wp_create_nonce( 'a8c_developer_install_plugin_' . $plugin_slug ) . '">' . esc_html__( 'Install', 'a8c-developer' ) . '</button></li>';
						}
					}

				echo '</ul>';

				echo '<script type="text/javascript">a8c_developer_bind_events();</script>';

				exit();

			case 'a8c_developer_install_plugin':
				if ( empty( $_POST['plugin_slug'] ) )
					die( '-1' );

				check_ajax_referer( 'a8c_developer_install_plugin_' . $_POST['plugin_slug'] );

				if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) )
					die( '-1' );

				include_once ( ABSPATH . 'wp-admin/includes/plugin-install.php' );

				$api = plugins_api( 'plugin_information', array( 'slug' => $_POST['plugin_slug'], 'fields' => array( 'sections' => false ) ) );

				if ( is_wp_error( $api ) )
					die( '-1' );

				$upgrader = new Plugin_Upgrader( new Automattic_Developer_Empty_Upgrader_Skin( array(
					'nonce'  => 'install-plugin_' . $_POST['plugin_slug'],
					'plugin' => $_POST['plugin_slug'],
					'api'    => $api,
				) ) );

				$install_result = $upgrader->install( $api->download_link );

				if ( ! $install_result || is_wp_error( $install_result ) )
					die( '-1' );

				$activate_result = activate_plugin( $this->get_path_for_recommended_plugin( $_POST['plugin_slug'] ) );

				if ( is_wp_error( $activate_result ) )
					die( '-1' );

				exit( '1' );

			case 'a8c_developer_activate_plugin':
				if ( empty( $_POST['path'] ) )
					die( '-1' );

				check_ajax_referer( 'a8c_developer_activate_plugin_' . $_POST['path'] );

				if ( ! current_user_can( 'activate_plugins' ) )
					die( '-1' );

				$activate_result = activate_plugin( $_POST['path'] );

				if ( is_wp_error( $activate_result ) )
					die( '-1' );

				exit( '1' );
		}

		// Unknown action
		die( '-1' );
	}

	public function settings_page() {
		add_settings_section( 'a8c_developer_main', esc_html__( 'Main Configuration', 'a8c-developer' ), '__return_false', $this->settings_page_slug . '_settings' );
		add_settings_field( 'a8c_developer_project_type', esc_html__( 'Project Type', 'a8c-developer' ), array( &$this, 'settings_field_select' ), $this->settings_page_slug . '_settings', 'a8c_developer_main', array(
			'name'        => 'project_type',
			'description' => __( 'Are you developing plugins and themes for <a href="http://wordpress.org/">self-hosted blogs</a> or are you working on a <a href="http://vip.wordpess.com/">WordPress.com VIP</a> project?', 'a8c-developer' ),
			'options'     => array(
				'wporg'     => esc_html__( 'WordPress.org', 'a8c-developer' ),
				'wpcom-vip' => esc_html__( 'WordPress.com VIP', 'a8c-developer' ),
			),
		) );

		// TODO: Refactor this allow AJAX
		add_settings_section( 'a8c_developer_plugins', esc_html__( 'Plugins', 'a8c-developer' ), array( &$this, 'settings_section_plugins' ), $this->settings_page_slug . '_status' );
		foreach ( $this->recommended_plugins as $plugin_slug => $plugin_details ) {
			if ( 'all' != $plugin_details['project_type'] && $plugin_details['project_type'] != $this->settings['project_type'] )
				continue;

			$plugin_details = array_merge( array( 'slug' => $plugin_slug ), $plugin_details );
			add_settings_field( 'a8c_developer_plugin_' . $plugin_slug, $plugin_details['name'], array( &$this, 'settings_field_plugin' ), $this->settings_page_slug . '_status', 'a8c_developer_plugins', $plugin_details );
		}

		add_settings_section( 'a8c_developer_constants', esc_html__( 'Constants', 'a8c-developer' ), array( &$this, 'settings_section_constants' ), $this->settings_page_slug . '_status' );
		foreach ( $this->recommended_constants as $constant => $description ) {
			add_settings_field( 'a8c_developer_constant_' . $constant, $constant, array( &$this, 'settings_field_constant' ), $this->settings_page_slug . '_status', 'a8c_developer_constants', array(
				'constant'    => $constant,
				'description' => $description,
			) );
		}

		add_settings_section( 'a8c_developer_settings', esc_html__( 'Settings', 'a8c-developer' ), array( &$this, 'settings_section_settings' ), $this->settings_page_slug . '_status' );
		add_settings_field( 'a8c_developer_setting_permalink_structure', esc_html__( 'Pretty Permalinks', 'a8c-developer' ), array( &$this, 'settings_field_setting_permalink_structure' ), $this->settings_page_slug . '_status', 'a8c_developer_settings' );



		# Add more sections and fields here as needed
?>

		<div class="wrap">

		<?php screen_icon(); // TODO: Better icon? ?>

		<h2><?php esc_html_e( 'Automattic Developer Helper', 'vehicle-info' ); ?></h2>

		<form action="options.php" method="post">

			<?php settings_fields( $this->option_name ); // matches value from register_setting() ?>

			<?php do_settings_sections( $this->settings_page_slug . '_settings' ); // matches values from add_settings_section/field() ?>

			<?php submit_button(); ?>

			<?php do_settings_sections( $this->settings_page_slug . '_status' ); ?>
		</form>

		<!-- TODO: remove this dev-only stuff -->
		<h3 style="margin-top:150px">Current Settings Value:</h3>
		<?php var_dump( get_option( $this->option_name ) ); ?>
		<a href="<?php echo esc_url( add_query_arg( 'a8c_developer_reset', 1 ) ); ?>">Delete settings and start over</a>


		</div>
<?php
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
			echo ' <span class="description">' . $args['description'] . '</span>';
	}

	public function settings_section_plugins() {
		echo '<p>' . esc_html__( 'We recommend you have the following plugins installed:', 'a8c-developer' ) . '</p>';
	}

	// TODO: Make this not shitty
	public function settings_field_plugin( $args ) {
		if ( $args['active'] ) {
			echo '<span class="a8c-developer-active">' . esc_html__( 'ACTIVE', 'a8c-developer' ) . '</span>';
		} elseif ( $this->is_recommended_plugin_installed( $args['slug'] ) ) {
			// Needs to be activated
			if ( current_user_can('activate_plugins') ) {
				$path = $this->get_path_for_recommended_plugin( $args['slug'] );
				echo '<a class="a8c-developer-notactive" href="' . esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $path ), 'activate-plugin_' . $path ) ) . '" title="' . esc_attr__( 'Click here to activate', 'a8c-developer' ) . '">' . esc_html__( 'INACTIVE', 'a8c-developer' ) . '</a>';					
			} else {
				echo '<span class="a8c-developer-notactive">' . esc_html__( 'INACTIVE', 'a8c-developer' ) . '</span>';
			}
		} else {
			// Needs to be installed
			if ( current_user_can('install_plugins') ) {
				echo '<a class="a8c-developer-notactive" href="' . esc_url( wp_nonce_url( admin_url( 'update.php?action=install-plugin&plugin=' . $args['slug'] ), 'install-plugin_' . $args['slug'] ) ) . '" title="' . esc_attr__( 'Click here to install', 'a8c-developer' ) . '">' . esc_html__( 'NOT INSTALLED', 'a8c-developer' ) . '</a>';
			} else {
				echo '<span class="a8c-developer-notactive">' . esc_html__( 'NOT INSTALLED', 'a8c-developer' ) . '</span>';
			}
		}
	}

	public function settings_section_constants() {
		echo '<p>' . __( 'We recommend you set the following constants to <code>true</code> in your <code>wp-config.php</code> file. <a href="http://codex.wordpress.org/Editing_wp-config.php" target="_blank">Need help?</a>', 'a8c-developer' ) . '</p>';
	}

	// TODO: Make this not shitty
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

	public function settings_validate( $raw_settings ) {
		$settings = array();

		$settings['project_type'] = ( ! empty( $raw_settings['project_type'] ) && 'wpcom-vip' == $raw_settings['project_type'] ) ? 'wpcom-vip' : 'wporg';

		return $settings;
	}

	public function save_project_type( $type ) {
		$settings = $this->settings;
		$settings['project_type'] = $type;

		$this->settings = $this->settings_validate( $settings );

		update_option( $this->option_name, $this->settings );
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
}

$Automattic_Developer = new Automattic_Developer();

?>