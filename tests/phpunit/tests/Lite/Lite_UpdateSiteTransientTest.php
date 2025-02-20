<?php
/**
 * Class Lite_UpdateSiteTransientTest
 *
 * @package git-updater-lite
 */

/**
 * Tests for Lite::update_site_transient()
 *
 * @covers \Fragen\Git_Updater\Lite::update_site_transient
 */
class Lite_UpdateSiteTransientTest extends GitUpdater_UnitTestCase {
	/**
	 * Tests that a plugin's transient's property is set to the expected value.
	 *
	 * @dataProvider data_plugin_transient_properties_and_api_data
	 *
	 * @param string   $property_name The name of the property.
	 * @param stdClass $api_data      The API data.
	 * @param mixed    $expected      The expected value.
	 */
	public function test_should_set_plugin_transient_property_to_expected_value( $property_name, $api_data, $expected ) {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->set_property_value( $lite, 'api_data', $api_data );

		$actual = $lite->update_site_transient( new stdClass() );

		$this->assertIsObject(
			$actual,
			"The transient's value is not an object."
		);

		$this->assertObjectHasProperty(
			'response',
			$actual,
			"The transient object not have the 'response' property."
		);

		$this->assertIsArray(
			$actual->response,
			"The transient's response value is not an array."
		);

		$this->assertArrayHasKey(
			$api_data->file,
			$actual->response,
			"The transient's response value has no {$api_data->file} key."
		);

		$this->assertIsObject(
			$actual->response[ $api_data->file ],
			"The transient's response value's {$api_data->file} value is not an object."
		);

		$this->assertObjectHasProperty(
			$property_name,
			$actual->response[ $api_data->file ],
			"The transient's response value does not have the '{$property_name}' property."
		);

		$this->assertSame(
			$expected,
			$actual->response[ $api_data->file ]->$property_name,
			'The property was not set to the expected value.'
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_plugin_transient_properties_and_api_data() {
		$plugin_api_data = (object) array(
			'slug'          => 'test-plugin-slug',
			'file'          => 'my-plugin/my-plugin.php',
			'type'          => 'plugin',
			'url'           => 'http://example.org',
			'version'       => '2.0.0',
			'icons'         => (object) array(
				'1x' => 'icon-1x.png',
				'2x' => 'icon-2x.png',
			),
			'banners'       => (object) array(
				'low'  => 'banner-low.png',
				'high' => 'banner-high.png',
			),
			'branch'        => 'test-branch',
			'git'           => 'test-githost',
			'requires'      => 'test-version',
			'requires_php'  => 'test-php-version',
			'download_link' => 'test-download-link',
			'tested'        => '10.0.0',
		);

		$no_url_plugin_api_data = clone $plugin_api_data;
		unset( $no_url_plugin_api_data->url );

		return array(
			'slug'                         => array(
				'property_name' => 'slug',
				'api_data'      => $plugin_api_data,
				'expected'      => $plugin_api_data->slug,
			),
			'"plugin"'                     => array(
				'property_name' => 'plugin',
				'api_data'      => $plugin_api_data,
				'expected'      => $plugin_api_data->file,
			),
			'url for a plugin with a URL'  => array(
				'property_name' => 'url',
				'api_data'      => $plugin_api_data,
				'expected'      => $plugin_api_data->url,
			),
			'url for a plugin with no URL' => array(
				'property_name' => 'url',
				'api_data'      => $no_url_plugin_api_data,
				'expected'      => $no_url_plugin_api_data->slug,
			),
			'banners'                      => array(
				'property_name' => 'banners',
				'api_data'      => $plugin_api_data,
				'expected'      => $plugin_api_data->banners,
			),
			'branch'                       => array(
				'property_name' => 'branch',
				'api_data'      => $plugin_api_data,
				'expected'      => $plugin_api_data->branch,
			),
			'type'                         => array(
				'property_name' => 'type',
				'api_data'      => $plugin_api_data,
				'expected'      => $plugin_api_data->git . '-' . $plugin_api_data->type,
			),
			'update-supported'             => array(
				'property_name' => 'update-supported',
				'api_data'      => $plugin_api_data,
				'expected'      => true,
			),
			'requires'                     => array(
				'property_name' => 'requires',
				'api_data'      => $plugin_api_data,
				'expected'      => $plugin_api_data->requires,
			),
			'requires_php'                 => array(
				'property_name' => 'requires_php',
				'api_data'      => $plugin_api_data,
				'expected'      => $plugin_api_data->requires_php,
			),
			'new_version'                  => array(
				'property_name' => 'new_version',
				'api_data'      => $plugin_api_data,
				'expected'      => $plugin_api_data->version,
			),
			'package'                      => array(
				'property_name' => 'package',
				'api_data'      => $plugin_api_data,
				'expected'      => $plugin_api_data->download_link,
			),
			'tested'                       => array(
				'property_name' => 'tested',
				'api_data'      => $plugin_api_data,
				'expected'      => $plugin_api_data->tested,
			),
		);
	}


	/**
	 * Tests that a theme's transient's response value is set to the expected value.
	 *
	 * @dataProvider data_theme_transient_keys_and_api_data
	 *
	 * @param string   $key_name      The name of the key.
	 * @param stdClass $api_data The API data.
	 * @param mixed    $expected The expected value.
	 */
	public function test_should_set_theme_transient_response_key_to_expected_value( $key_name, $api_data, $expected ) {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['theme'] );
		$this->set_property_value( $lite, 'api_data', $api_data );

		$actual = $lite->update_site_transient( new stdClass() );

		$this->assertIsObject(
			$actual,
			"The transient's value is not an object."
		);

		$this->assertObjectHasProperty(
			'response',
			$actual,
			"The transient object not have the 'response' property."
		);

		$this->assertIsArray(
			$actual->response,
			"The transient's response value is not an array."
		);

		$this->assertArrayHasKey(
			$api_data->slug,
			$actual->response,
			"The transient's response value has no {$api_data->slug} key."
		);

		$this->assertIsArray(
			$actual->response[ $api_data->slug ],
			"The transient's response value's {$api_data->slug} value is not an array."
		);

		$this->assertArrayHasKey(
			$key_name,
			$actual->response[ $api_data->slug ],
			"The transient's response value does not have the '{$key_name}' property."
		);

		$this->assertSame(
			$expected,
			$actual->response[ $api_data->slug ][ $key_name ],
			'The property was not set to the expected value.'
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_theme_transient_keys_and_api_data() {
		$theme_api_data = (object) array(
			'slug'          => 'test-theme-slug',
			'file'          => 'my-theme/style.css',
			'type'          => 'theme',
			'url'           => 'http://example.org',
			'version'       => '2.0.0',
			'icons'         => (object) array(
				'1x' => 'icon-1x.png',
				'2x' => 'icon-2x.png',
			),
			'banners'       => (object) array(
				'low'  => 'banner-low.png',
				'high' => 'banner-high.png',
			),
			'branch'        => 'test-branch',
			'git'           => 'test-githost',
			'requires'      => 'test-version',
			'requires_php'  => 'test-php-version',
			'download_link' => 'test-download-link',
			'tested'        => '10.0.0',
		);

		$no_url_theme_api_data = clone $theme_api_data;
		unset( $no_url_theme_api_data->url );

		return array(
			'slug'                              => array(
				'key_name' => 'slug',
				'api_data' => $theme_api_data,
				'expected' => $theme_api_data->slug,
			),
			'"theme"'                           => array(
				'key_name' => 'theme',
				'api_data' => $theme_api_data,
				'expected' => $theme_api_data->slug,
			),
			'url for a theme with a URL'        => array(
				'key_name' => 'url',
				'api_data' => $theme_api_data,
				'expected' => $theme_api_data->url,
			),
			'url for a theme with no URL'       => array(
				'key_name' => 'url',
				'api_data' => $no_url_theme_api_data,
				'expected' => $no_url_theme_api_data->slug,
			),
			'banners'                           => array(
				'key_name' => 'banners',
				'api_data' => $theme_api_data,
				'expected' => $theme_api_data->banners,
			),
			'branch'                            => array(
				'key_name' => 'branch',
				'api_data' => $theme_api_data,
				'expected' => $theme_api_data->branch,
			),
			'type'                              => array(
				'key_name' => 'type',
				'api_data' => $theme_api_data,
				'expected' => $theme_api_data->git . '-' . $theme_api_data->type,
			),
			'update-supported'                  => array(
				'key_name' => 'update-supported',
				'api_data' => $theme_api_data,
				'expected' => true,
			),
			'requires'                          => array(
				'key_name' => 'requires',
				'api_data' => $theme_api_data,
				'expected' => $theme_api_data->requires,
			),
			'requires_php'                      => array(
				'key_name' => 'requires_php',
				'api_data' => $theme_api_data,
				'expected' => $theme_api_data->requires_php,
			),
			'theme_uri for a theme with a URL'  => array(
				'key_name' => 'theme_uri',
				'api_data' => $theme_api_data,
				'expected' => $theme_api_data->url,
			),
			'theme_uri for a theme with no URL' => array(
				'key_name' => 'theme_uri',
				'api_data' => $no_url_theme_api_data,
				'expected' => $no_url_theme_api_data->slug,
			),
			'new_version'                       => array(
				'property_name' => 'new_version',
				'api_data'      => $theme_api_data,
				'expected'      => $theme_api_data->version,
			),
			'package'                           => array(
				'property_name' => 'package',
				'api_data'      => $theme_api_data,
				'expected'      => $theme_api_data->download_link,
			),
			'tested'                            => array(
				'property_name' => 'tested',
				'api_data'      => $theme_api_data,
				'expected'      => $theme_api_data->tested,
			),
		);
	}

	/**
	 * Tests that the plugin response is added to no_update when no update is available.
	 *
	 * @dataProvider data_plugin_no_update_properties
	 *
	 * @param string $property_name The name of the property.
	 * @param string $expected      The expected value.
	 */
	public function test_should_add_plugin_response_to_no_update_when_no_update_is_available( $property_name, $expected ) {
		$api_data = (object) array(
			'slug'          => 'test-plugin-slug',
			'file'          => 'my-plugin/my-plugin.php',
			'type'          => 'plugin',
			'url'           => 'http://example.org',
			'version'       => '0.0.1',
			'icons'         => (object) array(
				'1x' => 'icon-1x.png',
				'2x' => 'icon-2x.png',
			),
			'banners'       => (object) array(
				'low'  => 'banner-low.png',
				'high' => 'banner-high.png',
			),
			'branch'        => 'test-branch',
			'git'           => 'test-githost',
			'requires'      => 'test-version',
			'requires_php'  => 'test-php-version',
			'download_link' => 'test-download-link',
			'tested'        => '10.0.0',
		);

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->set_property_value( $lite, 'api_data', $api_data );

		$actual = $lite->update_site_transient( new stdClass() );

		$this->assertIsObject(
			$actual,
			"The transient's value is not an object."
		);

		$this->assertObjectNotHasProperty(
			'response',
			$actual,
			"The transient object has the 'response' property."
		);

		$this->assertObjectHasProperty(
			'no_update',
			$actual,
			'The transient does not have a no_update property.'
		);

		$this->assertIsArray(
			$actual->no_update,
			"The transient's no_update value is not an array."
		);

		$this->assertArrayHasKey(
			$api_data->file,
			$actual->no_update,
			"The transient's no_update value does not have a '{$api_data->file}' key."
		);

		$this->assertIsObject(
			$actual->no_update[ $api_data->file ],
			"The transient's response value is not an object."
		);

		$this->assertObjectHasProperty(
			$property_name,
			$actual->no_update[ $api_data->file ],
			"The transient's response value does not have a '{$property_name}' property."
		);

		if ( is_array( $expected ) ) {
			$expected = implode(
				'-',
				array_map(
					static function ( $property ) use ( $api_data ) {
						return $api_data->$property;
					},
					$expected
				)
			);
		} elseif ( is_string( $expected ) ) {
			if ( 'icons' === $expected ) {
				$expected = (array) $api_data->$expected;
			} else {
				$expected = $api_data->$expected;
			}
		}

		$this->assertSame(
			$expected,
			$actual->no_update[ $api_data->file ]->$property_name,
			"The transient's response value for {$property_name} is incorrect."
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_plugin_no_update_properties() {
		return array(
			'slug'             => array(
				'property_name' => 'slug',
				'expected'      => 'slug',
			),
			'plugin'           => array(
				'property_name' => 'plugin',
				'expected'      => 'file',
			),
			'url'              => array(
				'property_name' => 'url',
				'expected'      => 'url',
			),
			'icons'            => array(
				'property_name' => 'icons',
				'expected'      => 'icons',
			),
			'banners'          => array(
				'property_name' => 'banners',
				'expected'      => 'banners',
			),
			'branch'           => array(
				'property_name' => 'branch',
				'expected'      => 'branch',
			),
			'type'             => array(
				'property_name' => 'type',
				'expected'      => array( 'git', 'type' ),
			),
			'update-supported' => array(
				'property_name' => 'update-supported',
				'expected'      => true,
			),
			'requires'         => array(
				'property_name' => 'requires',
				'expected'      => 'requires',
			),
			'requires_php'     => array(
				'property_name' => 'requires_php',
				'expected'      => 'requires_php',
			),
			'package'          => array(
				'property_name' => 'package',
				'expected'      => 'test-download-link',
			),
			'tested'           => array(
				'property_name' => 'tested',
				'expected'      => '10.0.0',
			),
		);
	}

	/**
	 * Tests that the theme response is added to no_update when no update is available.
	 *
	 * @dataProvider data_theme_no_update_properties
	 *
	 * @param string $key_name The name of the property.
	 * @param string $expected The expected value.
	 */
	public function test_should_add_theme_response_to_no_update_when_no_update_is_available( $key_name, $expected ) {
		$api_data = (object) array(
			'slug'         => 'test-theme-slug',
			'file'         => 'my-theme/style.css',
			'type'         => 'theme',
			'url'          => 'http://example.org',
			'version'      => '0.0.1',
			'icons'        => (object) array(
				'1x' => 'icon-1x.png',
				'2x' => 'icon-2x.png',
			),
			'banners'      => (object) array(
				'low'  => 'banner-low.png',
				'high' => 'banner-high.png',
			),
			'branch'       => 'test-branch',
			'git'          => 'test-githost',
			'requires'     => 'test-version',
			'requires_php' => 'test-php-version',
			'package'      => 'test-download-link',
			'tested'       => '10.0.0',
		);

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['theme'] );
		$this->set_property_value( $lite, 'api_data', $api_data );

		$actual = $lite->update_site_transient( new stdClass() );

		$this->assertIsObject(
			$actual,
			"The transient's value is not an object."
		);

		$this->assertObjectNotHasProperty(
			'response',
			$actual,
			"The transient object has the 'response' property."
		);

		$this->assertObjectHasProperty(
			'no_update',
			$actual,
			'The transient does not have a no_update property.'
		);

		$this->assertIsArray(
			$actual->no_update,
			"The transient's no_update value is not an array."
		);

		$this->assertArrayHasKey(
			$api_data->file,
			$actual->no_update,
			"The transient's no_update value does not have a '{$api_data->file}' key."
		);

		$this->assertIsArray(
			$actual->no_update[ $api_data->file ],
			"The transient's response value is not an array."
		);

		$this->assertArrayHasKey(
			$key_name,
			$actual->no_update[ $api_data->file ],
			"The transient's response value does not have a '{$key_name}' key."
		);

		if ( is_array( $expected ) ) {
			$expected = implode(
				'-',
				array_map(
					static function ( $key_name ) use ( $api_data ) {
						return $api_data->$key_name;
					},
					$expected
				)
			);
		} elseif ( is_string( $expected ) ) {
			if ( 'icons' === $expected ) {
				$expected = (array) $api_data->$expected;
			} else {
				$expected = $api_data->$expected;
			}
		}

		$this->assertSame(
			$expected,
			$actual->no_update[ $api_data->file ][ $key_name ],
			"The transient's response value for {$key_name} is incorrect."
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_theme_no_update_properties() {
		return array(
			'slug'             => array(
				'key_name' => 'slug',
				'expected' => 'slug',
			),
			'theme'            => array(
				'key_name' => 'theme',
				'expected' => 'slug',
			),
			'url'              => array(
				'key_name' => 'url',
				'expected' => 'url',
			),
			'icons'            => array(
				'key_name' => 'icons',
				'expected' => 'icons',
			),
			'banners'          => array(
				'key_name' => 'banners',
				'expected' => 'banners',
			),
			'branch'           => array(
				'key_name' => 'branch',
				'expected' => 'branch',
			),
			'type'             => array(
				'key_name' => 'type',
				'expected' => array( 'git', 'type' ),
			),
			'update-supported' => array(
				'key_name' => 'update-supported',
				'expected' => true,
			),
			'requires'         => array(
				'key_name' => 'requires',
				'expected' => 'requires',
			),
			'requires_php'     => array(
				'key_name' => 'requires_php',
				'expected' => 'requires_php',
			),
			'package'          => array(
				'property_name' => 'package',
				'expected'      => 'test-download-link',
			),
			'tested'           => array(
				'property_name' => 'tested',
				'expected'      => '10.0.0',
			),
		);
	}

	/**
	 * Tests that a Fatal Error is not thrown when the transient argument is not an object.
	 *
	 * The test will either pass, or error out.
	 *
	 * @doesNotPerformAssertions
	 */
	public function test_should_not_throw_error_when_transient_argument_is_not_an_object() {
		$api_data = (object) array(
			'slug'         => 'test-plugin-slug',
			'file'         => 'my-plugin/my-plugin.php',
			'type'         => 'plugin',
			'url'          => 'http://example.org',
			'version'      => '0.0.1',
			'icons'        => (object) array(
				'1x' => 'icon-1x.png',
				'2x' => 'icon-2x.png',
			),
			'banners'      => (object) array(
				'low'  => 'banner-low.png',
				'high' => 'banner-high.png',
			),
			'branch'       => 'test-branch',
			'git'          => 'test-githost',
			'requires'     => 'test-version',
			'requires_php' => 'test-php-version',
			'package'      => 'test-download-link',
			'tested'       => '10.0.0',
		);

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->set_property_value( $lite, 'api_data', $api_data );
		$lite->update_site_transient( false );
	}
}
