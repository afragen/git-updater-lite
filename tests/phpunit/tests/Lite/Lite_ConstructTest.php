<?php
/**
 * Class Lite_ConstructTest
 *
 * @package git-updater-lite
 */

/**
 * Tests for Lite::__construct()
 *
 * @covers \Fragen\Git_Updater\Lite::__construct
 */
class Lite_ConstructTest extends GitUpdater_UnitTestCase {
	/**
	 * Tests that the 'slug' property is set.
	 *
	 * @dataProvider data_file_paths_and_slugs
	 *
	 * @param string $file_path The file path.
	 * @param string $slug      The expected slug.
	 */
	public function test_should_set_slug_property( $file_path, $slug ) {
		$this->assertSame(
			$slug,
			$this->get_property_value( new \Fragen\Git_Updater\Lite( $file_path ), 'slug' )
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_file_paths_and_slugs() {
		return array(
			'a theme'  => array(
				'file_path' => $this->test_files['theme'],
				'slug'      => 'my-theme',
			),
			'a plugin' => array(
				'file_path' => $this->test_files['plugin'],
				'slug'      => 'my-plugin',
			),
		);
	}

	/**
	 * Tests that the 'file' property is set.
	 *
	 * @dataProvider data_file_paths_and_files
	 *
	 * @param string $file_path The file path.
	 * @param string $file      The expected file.
	 */
	public function test_should_set_file_property( $file_path, $file ) {
		$this->assertSame(
			$file,
			$this->get_property_value( new \Fragen\Git_Updater\Lite( $file_path ), 'file' )
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_file_paths_and_files() {
		return array(
			'a theme'  => array(
				'file_path' => $this->test_files['theme'],
				'file'      => 'my-theme/style.css',
			),
			'a plugin' => array(
				'file_path' => $this->test_files['plugin'],
				'file'      => 'my-plugin/my-plugin.php',
			),
			'a plugin with a server which has a trailing slash' => array(
				'file_path' => $this->test_files['plugin_server_with_trailing_slash'],
				'file'      => 'my-plugin-server-with-trailing-slash/my-plugin-server-with-trailing-slash.php',
			),
		);
	}

	/**
	 * Tests that the 'local_version' property is set.
	 *
	 * @dataProvider data_file_paths_and_versions
	 *
	 * @param string $file_path The file path.
	 * @param string $version   The expected version.
	 */
	public function test_should_set_local_version_property( $file_path, $version ) {
		$this->assertSame(
			$version,
			$this->get_property_value( new \Fragen\Git_Updater\Lite( $file_path ), 'local_version' )
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_file_paths_and_versions() {
		return array(
			'a theme'  => array(
				'file_path' => $this->test_files['theme'],
				'version'   => '1.0.1',
			),
			'a plugin' => array(
				'file_path' => $this->test_files['plugin'],
				'version'   => '1.0.2',
			),
		);
	}

	/**
	 * Tests that the 'update_server' property is set to a URL.
	 *
	 * @dataProvider data_file_paths_and_servers
	 *
	 * @covers \Fragen\Git_Updater\Lite::check_update_uri
	 *
	 * @param string $file_path The file path.
	 * @param string $expected  The expected URL.
	 */
	public function test_should_set_update_server_property_to_url( $file_path, $expected ) {
		$actual = $this->get_property_value( new \Fragen\Git_Updater\Lite( $file_path ), 'update_server' );
		$this->assertSame(
			$expected,
			$actual,
			'The update_server property was not set to the expected value.'
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_file_paths_and_servers() {
		return array(
			'a theme'  => array(
				'file_path' => $this->test_files['theme'],
				'expected'  => 'https://my-theme.com',
			),
			'a plugin' => array(
				'file_path' => $this->test_files['plugin'],
				'expected'  => 'https://my-plugin.com',
			),
			'a plugin with a server which has a trailing slash' => array(
				'file_path' => $this->test_files['plugin_server_with_trailing_slash'],
				'expected'  => 'https://my-plugin.com',
			),
		);
	}

	/**
	 * Tests that the 'update_server' property is set to a WP_Error object
	 * for invalid server values.
	 *
	 * @dataProvider data_file_paths_with_no_server
	 *
	 * @covers \Fragen\Git_Updater\Lite::check_update_uri
	 *
	 * @param string $file_path The file path.
	 */
	public function test_should_set_update_server_property_to_wp_error( $file_path ) {
		$actual = $this->get_property_value( new \Fragen\Git_Updater\Lite( $file_path ), 'update_server' );
		$this->assertWPError(
			$actual,
			'The update_server property was not set to a WP_Error object .'
		);

		$this->assertSame(
			$actual->get_error_code(),
			'invalid_header_data',
			"The update_server property's error code is incorrect."
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_file_paths_with_no_server() {
		return array(
			'a theme with no update server'           => array(
				'file_path' => $this->test_files['theme_no_server'],
			),
			'a plugin with no update server'          => array(
				'file_path' => $this->test_files['plugin_no_server'],
			),
			'a theme with a server which has a path'  => array(
				'file_path' => $this->test_files['theme_server_with_path'],
			),
			'a plugin with a server which has a path' => array(
				'file_path' => $this->test_files['plugin_server_with_path'],
			),
		);
	}
}
