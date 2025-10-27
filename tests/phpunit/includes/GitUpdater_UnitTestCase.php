<?php
/**
 * Abstract base test class for \Fragen\Git_Updater
 *
 * All \Fragen\Git_Updater unit tests should inherit from this class.
 */
abstract class GitUpdater_UnitTestCase extends WP_UnitTestCase {
	/**
	 * File paths for testing.
	 *
	 * @var array
	 */
	protected $test_files = array(
		'plugin'                              => __DIR__ . '/../data/plugins/my-plugin/my-plugin.php',
		'plugin_no_server'                    => __DIR__ . '/../data/plugins/my-plugin-no-server/my-plugin-no-server.php',
		'plugin_server_with_path'             => __DIR__ . '/../data/plugins/my-plugin-server-with-path/my-plugin-server-with-path.php',
		'plugin_server_with_trailing_slash' => __DIR__ . '/../data/plugins/my-plugin-server-with-trailing-slash/my-plugin-server-with-trailing-slash.php',
		'theme'                               => __DIR__ . '/../data/themes/my-theme/functions.php',
		'theme_no_server'                     => __DIR__ . '/../data/themes/my-theme-no-server/functions.php',
		'theme_server_with_path'              => __DIR__ . '/../data/themes/my-theme-server-with-path/functions.php',
	);

	/**
	 * Gets a reflected property's value.
	 *
	 * @param object $obj  The object.
	 * @param string $name The property name.
	 * @return mixed The property's value.
	 */
	protected function get_property_value( $obj, $name ) {
		$reflected = new ReflectionProperty( $obj, $name );
		$reflected->setAccessible( true );
		$value = $reflected->getValue( $obj );
		$reflected->setAccessible( false );
		return $value;
	}

	/**
	 * Sets a reflected property's value.
	 *
	 * @param object $obj   The object.
	 * @param string $name  The property name.
	 * @param mixed  $value The property's new value.
	 */
	protected function set_property_value( $obj, $name, $value ) {
		$reflected = new ReflectionProperty( $obj, $name );
		$reflected->setAccessible( true );
		$reflected->setValue( $obj, $value );
		$reflected->setAccessible( false );
	}

		/**
		 * Filters a HTTP request.
		 *
		 * @param string $server The server substring to match.
		 * @param mixed  $res    The mocked response.
		 * @return void
		 */
	protected function filter_http_request( $server, $res = null ) {
		add_filter(
			'pre_http_request',
			static function ( $response, $parsed_args, $url ) use ( $server, $res ) {
				return str_contains( $url, $server ) ? $res : $response;
			},
			10,
			3
		);
	}
}
