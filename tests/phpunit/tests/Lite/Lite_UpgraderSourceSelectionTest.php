<?php
/**
 * Class Lite_UpgraderSourceSelectionTest
 *
 * @package git-updater-lite
 */

/**
 * Tests for Lite::upgrader_source_selection()
 *
 * @covers \Fragen\Git_Updater\Lite::upgrader_source_selection
 */
class Lite_UpgraderSourceSelectionTest extends GitUpdater_UnitTestCase {
	/**
	 * Tests that a TypeError is thrown when the 'upgrader' argument is not
	 * of the correct type.
	 */
	public function test_should_throw_typeerror_for_invalid_upgrader_argument_type() {
		$this->expectException( TypeError::class );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->upgrader_source_selection(
			'source',
			'remote-source',
			new stdClass()
		);
	}

	/**
	 * Tests that the original source is returned when installing.
	 */
	public function test_should_return_original_source_when_installing() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );

		$this->assertSame(
			'source',
			$lite->upgrader_source_selection(
				'source',
				'remote-source',
				new Plugin_Upgrader(),
				array( 'action' => 'install' )
			)
		);
	}

	/**
	 * Tests that the original source is returned when a plugin does not need to be renamed.
	 */
	public function test_should_return_original_source_when_a_plugin_does_not_need_to_be_renamed() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );

		$this->assertSame(
			'path/to/my-plugin',
			$lite->upgrader_source_selection(
				'path/to/my-plugin',
				'remote-source',
				new Plugin_Upgrader(),
				array( 'plugin' => 'my-plugin/my-plugin.php' )
			)
		);
	}

	/**
	 * Tests that the original source is returned when a theme does not need to be renamed.
	 */
	public function test_should_return_original_source_when_a_theme_does_not_need_to_be_renamed() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['theme'] );

		$this->assertSame(
			'path/to/my-theme',
			$lite->upgrader_source_selection(
				'path/to/my-theme',
				'remote-source',
				new Theme_Upgrader(),
				array( 'theme' => 'my-theme' )
			)
		);
	}

	/**
	 * Tests that a plugin is renamed when the source and new source are different.
	 */
	public function test_should_rename_plugin_when_source_and_new_source_are_different() {
		global $wp_filesystem;

		$wp_filesystem = new Mock_Filesystem();
		$call_count    = $wp_filesystem->get_call_count( 'move' );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->upgrader_source_selection(
			'path/to/my-plugin-download',
			'new/path/to/',
			new Plugin_Upgrader(),
			array( 'plugin' => 'my-plugin/my-plugin.php' )
		);

		$this->assertSame( $call_count + 1, $wp_filesystem->get_call_count( 'move' ) );
	}

	/**
	 * Tests that a theme is renamed when the source and new source are different.
	 */
	public function test_should_rename_theme_when_source_and_new_source_are_different() {
		global $wp_filesystem;

		$wp_filesystem = new Mock_Filesystem();
		$call_count    = $wp_filesystem->get_call_count( 'move' );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['theme'] );
		$lite->upgrader_source_selection(
			'path/to/my-theme-download',
			'new/path/to/',
			new Theme_Upgrader(),
			array( 'theme' => 'my-theme' )
		);

		$this->assertSame( $call_count + 1, $wp_filesystem->get_call_count( 'move' ) );
	}

	/**
	 * Tests that the original error is returned if present.
	 */
	public function test_should_return_original_error_if_present() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );

		$error = new WP_Error( 'some_error_code', 'Some error message.' );

		$this->assertSame(
			$error,
			$lite->upgrader_source_selection(
				$error,
				'remote-source',
				new Plugin_Upgrader(),
				array( 'plugin' => 'my-plugin/my-plugin.php' )
			)
		);
	}
}
