<?php
/**
 * Drive the Developer plugin from the CLI.
 */
class Developer_CLI_Command extends WP_CLI_Command {

	/**
	 * Install plugins for a given Developer project
	 *
	 * @subcommand install-plugins
	 * @synopsis <project-type> [--activate]
	 */
	public function install_plugins( $args, $assoc_args ) {
		global $automattic_developer;

		list( $project_type ) = $args;

		$defaults = array(
				'activate'       => '',
			);
		$assoc_args = array_merge( $defaults, $assoc_args );

		$project_types = wp_list_pluck( $automattic_developer->recommended_plugins, 'project_type' );
		$project_types = array_unique( $project_types );
		if ( empty( $project_type ) || ! in_array( $project_type, $project_types ) )
			WP_CLI::error( sprintf( "'%s' is an invalid project type. Please choose from the following: %s", $project_type, implode( ', ', $project_types ) ) );

		$plugins = $automattic_developer->get_recommended_plugins_by_type( $project_type );
		$all_plugins = get_plugins();
		foreach( $plugins as $plugin_slug => $plugin_details ) {

			$plugin_path = $automattic_developer->get_path_for_recommended_plugin( $plugin_slug );

			if ( isset( $all_plugins[$plugin_path] ) ) {
				$installed = false;
			} else {
				$ret = $automattic_developer->install_plugin( $plugin_slug );
				if ( is_wp_error( $ret ) ) {
					WP_CLI::warning( sprintf( "Error installing '%s': %s", $plugin_slug, $ret->get_error_message() ) );
					continue;
				} else {
					$installed = true;
				}
			}

			if ( (bool)$assoc_args['activate'] ) {
				$ret = activate_plugin( $plugin_path );
				if ( is_wp_error( $ret ) && $installed )
					WP_CLI::warning( sprintf( "Installed '%s' but error activating: %s", $plugin_slug, $ret->get_error_message() ) );
				else if ( $installed )
					WP_CLI::line( sprintf( "Installed and activated '%s'.", $plugin_slug ) );
				else 
					WP_CLI::line( sprintf( "Activated '%s' which was already installed.", $plugin_slug ) );
			} else {
				if ( $installed )
					WP_CLI::line( sprintf( "Installed '%s'.", $plugin_slug ) );
				else
					WP_CLI::line( sprintf( "'%s' is already installed.", $plugin_slug ) );
			}
		}

		if ( (bool)$assoc_args['activate'] )
			$install_text = 'Installed and activated';
		else
			$install_text = 'Installed';

		if ( 'all' == $project_type )
			WP_CLI::success( sprintf( "%s all recommended plugins", $install_text ) );
		else
			WP_CLI::success( sprintf( "%s all plugins for the '%s' project.", $install_text, $project_type ) );


	}


}

WP_CLI::add_command( 'developer', 'Developer_CLI_Command' );