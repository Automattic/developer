<?php

include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

class Automattic_Developer_Empty_Upgrader_Skin extends WP_Upgrader_Skin {
	function __construct($args = array()) {
		$defaults = array( 'type' => 'web', 'url' => '', 'plugin' => '', 'nonce' => '', 'title' => '' );
		$args = wp_parse_args( $args, $defaults );

		$this->type = $args['type'];
		$this->api = isset( $args['api'] ) ? $args['api'] : array();

		parent::__construct( $args );
	}

	public function request_filesystem_credentials( $error = false, $context = false, $allow_relaxed_file_ownership = false  ) {
		return true;
	}

	public function error( $errors ) {
		die( '-1' );
	}

	public function header() {}
	public function footer() {}
	public function feedback( $string ) {}
}
