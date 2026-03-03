<?php
/**
 * Class Lite_LoadHooksTest
 *
 * @package git-updater-lite
 */

/**
 * Tests for Lite::load_hooks()
 *
 * @covers \Fragen\Git_Updater\Lite::load_hooks
 */
class Lite_LoadHooksTest extends GitUpdater_UnitTestCase {
	/**
	 * Tests that 'upgrader_source_selection' is filtered.
	 */
	public function test_should_filter_upgrader_source_selection() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->set_property_value( $lite, 'api_data', (object) array( 'type' => 'plugin' ) );
		$lite->load_hooks();

		$this->assertIsInt( has_filter( 'upgrader_source_selection', array( get_class( $lite ), 'upgrader_source_selection' ) ) );
	}

	/**
	 * Tests that 'plugins_api|themes_api' is filtered.
	 *
	 * @dataProvider data_types
	 *
	 * @param string $type The type of asset. 'plugin' or 'theme'.
	 */
	public function test_should_filter_type_api( $type ) {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->set_property_value( $lite, 'api_data', (object) array( 'type' => $type ) );
		$lite->load_hooks();

		if ( 'plugin' === $type ) {
			$this->assertIsInt( has_filter( 'plugins_api', array( $lite, 'plugin_api_details' ) ) );
		}
		if ( 'theme' === $type ) {
			$this->assertIsInt( has_filter( 'themes_api', array( $lite, 'theme_api_details' ) ) );
		}
	}

	/**
	 * Tests that 'site_transient_update_{$type}s' is filtered.
	 *
	 * @dataProvider data_types
	 *
	 * @param string $type The type of asset. 'plugin' or 'theme'.
	 */
	public function test_should_filter_site_transient_update_type( $type ) {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->set_property_value( $lite, 'api_data', (object) array( 'type' => $type ) );
		$lite->load_hooks();

		$this->assertIsInt( has_filter( "site_transient_update_{$type}s", array( $lite, 'update_site_transient' ) ) );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_types() {
		return self::text_array_to_dataprovider( array( 'plugin', 'theme' ) );
	}

	/**
	 * Tests that 'site_transient_update_{$type}s' is filtered on single site.
	 *
	 * @group ms-excluded
	 */
	public function test_should_filter_wp_prepare_themes_for_js_on_single_site() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['theme'] );
		$this->set_property_value( $lite, 'api_data', (object) array( 'type' => 'theme' ) );
		$lite->load_hooks();

		$this->assertIsInt( has_filter( 'wp_prepare_themes_for_js', array( $lite, 'customize_theme_update_html' ) ) );
	}

	/**
	 * Tests that 'site_transient_update_{$type}s' is not filtered on multisite.
	 *
	 * @group ms-required
	 */
	public function test_should_not_filter_wp_prepare_themes_for_js_on_multisite() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['theme'] );
		$this->set_property_value( $lite, 'api_data', (object) array( 'type' => 'theme' ) );
		$lite->load_hooks();

		$this->assertFalse( has_filter( 'wp_prepare_themes_for_js', array( $lite, 'customize_theme_update_html' ) ) );
	}

	/**
	 * Tests that 'http_request_args' is filtered.
	 */
	public function test_should_filter_http_request_args() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->set_property_value( $lite, 'api_data', (object) array( 'type' => 'plugin' ) );
		$lite->load_hooks();

		apply_filters( 'upgrader_pre_download', false );
		$this->assertIsInt( has_filter( 'http_request_args', array( $lite, 'add_auth_header' ) ) );
	}
}
