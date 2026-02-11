<?php
/**
 * Class Lite_RepoApiDetailsTest
 *
 * @package git-updater-lite
 */

/**
 * Tests for Lite::plugin_api_details() and Lite::theme_api_details()
 *
 * @covers \Fragen\Git_Updater\Lite::plugin_api_details
 * @covers \Fragen\Git_Updater\Lite::theme_api_details
 */
class Lite_RepoApiDetailsTest extends GitUpdater_UnitTestCase {
	/**
	 * Tests that the original result is returned when the action
	 * is not for information on the current type of asset.
	 *
	 * @dataProvider plugin_data_types_and_actions
	 *
	 * @param string $type   The type of asset. 'plugin' or 'theme'.
	 * @param string $action The non-information action.
	 */
	public function test_plugin_should_return_original_result_for_non_information_actions( $type, $action ) {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files[ $type ] );
		$this->set_property_value( $lite, 'api_data', (object) array( 'type' => $type ) );

		$this->assertTrue(
			$lite->plugin_api_details( true, $action, (object) array( 'test' => 'response' ) )
		);
	}
		/**
	 * Tests that the original result is returned when the action
	 * is not for information on the current type of asset.
	 *
	 * @dataProvider theme_data_types_and_actions
	 *
	 * @param string $type   The type of asset. 'plugin' or 'theme'.
	 * @param string $action The non-information action.
	 */
	public function test_theme__should_return_original_result_for_non_information_actions( $type, $action ) {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files[ $type ] );
		$this->set_property_value( $lite, 'api_data', (object) array( 'type' => $type ) );

		$this->assertTrue(
			$lite->theme_api_details( true, $action, (object) array( 'test' => 'response' ) )
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function plugin_data_types_and_actions() {
		return array(
			'query_plugins'                  => array(
				'type'   => 'plugin',
				'action' => 'query_plugins',
			),
			'theme_information for a plugin' => array(
				'type'   => 'plugin',
				'action' => 'theme_information',
			),
			'hot_tags for a plugin'          => array(
				'type'   => 'plugin',
				'action' => 'hot_tags',
			),
			'feature_list for a plugin'      => array(
				'type'   => 'plugin',
				'action' => 'feature_list',
			),
		);
	}

		/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function theme_data_types_and_actions() {
		return array(
			'query_themes'                   => array(
				'type'   => 'theme',
				'action' => 'query_theme',
			),
			'plugin_information for a theme' => array(
				'type'   => 'theme',
				'action' => 'plugin_information',
			),
			'hot_tags for a theme'           => array(
				'type'   => 'theme',
				'action' => 'hot_tags',
			),
			'feature_list for a theme'       => array(
				'type'   => 'theme',
				'action' => 'feature_list',
			),
		);
	}

	/**
	 * Tests that the original result is returned when the response's slug
	 * doesn't match the slug in 'api_data'.
	 */
	public function test_should_return_original_result_when_response_slug_does_not_match_api_data_slug() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->set_property_value(
			$lite,
			'api_data',
			(object) array(
				'type' => 'plugin',
				'slug' => 'my-plugin',
			)
		);

		$this->assertTrue(
			$lite->plugin_api_details( true, 'plugin_information', (object) array( 'slug' => 'not-my-plugin' ) )
		);
	}

	/**
	 * Tests that 'api_data' is returned when the action
	 * is for information about the current type of asset
	 * and the response's slug matches the slug in 'api_data'.
	 *
	 * @dataProvider data_types
	 *
	 * @param string $type The type of asset. 'plugin' or 'theme'.
	 */
	public function test_should_return_api_data_when_type_and_slug_match( $type ) {
		$lite     = new \Fragen\Git_Updater\Lite( $this->test_files[ $type ] );
		$api_data = (object) array(
			'type' => $type,
			'slug' => "my-{$type}",
		);
		$this->set_property_value( $lite, 'api_data', $api_data );

		$this->assertSame(
			$api_data,
			$lite->plugin_api_details( true, 'plugin_information', (object) array( 'slug' => "my-{$type}" ) )
		);
		$this->assertSame(
			$api_data,
			$lite->theme_api_details( true, 'theme_information', (object) array( 'slug' => "my-{$type}" ) )
		);

	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_types() {
		return self::text_array_to_dataprovider( array( 'plugin', 'theme' ) );
	}
}
