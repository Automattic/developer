<?php
/**
 * @group developer
 */
class WP_Test_Developer extends WP_UnitTestCase {

	/**
	 * A simple test to ensure the Developer plugin is here
	 */
	function test_plugin_exists() {

		$this->assertTrue( class_exists( 'Automattic_Developer' ) );
	}
}