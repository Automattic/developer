<?php
/**
 * Developer Plugin commands for WP-CLI.
 * @since 1.2
 */
class Developer_Command extends WP_CLI_Command {

	/**
	 * Sets up Developer Plugin
	 * @subcommand install-plugins
	 * @synopsis --type=<wpcom-vip|wporg-theme>
	 */
	function install_plugins( $args, $assoc_args ) {
		global $automattic_developer;
		$plugins = $automattic_developer->recommended_plugins;
		$type = $assoc_args['type'];

		switch ( $type ) {
			case 'wpcom-vip':
			case 'wporg-theme':
				foreach ( $plugins as $slug => $plugin ) {
					// Don't try to install plugins that already exist
					if ( file_exists( WP_CONTENT_DIR . '/plugins/' . $slug ) )
						continue;

					// Install and activate the plugin
					if ( 'all' == $plugin['project_type'] || $type == $plugin['project_type'] )
						WP_CLI::run_command( array( "plugin", "install", "$slug" ), array( "activate" => true ) );
				}
				break;

			default:
				WP_CLI::error( "Specify type of thing to install" );
		}
	}
}

WP_CLI::add_command( 'developer', 'Developer_Command' );
