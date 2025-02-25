<?php
/**
 * Class Lite_RunTest
 *
 * @package git-updater-lite
 */

/**
 * Tests for Lite::run()
 *
 * @covers \Fragen\Git_Updater\Lite::run
 */
class Lite_RunTest extends GitUpdater_UnitTestCase {
	/**
	 * Tests that a WP_Error object is returned when no update server exists.
	 */
	public function test_should_return_wp_error_when_no_update_server_exists() {
		$GLOBALS['pagenow'] = 'update-core.php';

		$actual = ( new \Fragen\Git_Updater\Lite( $this->test_files['plugin_no_server'] ) )->run();

		$this->assertWPError(
			$actual,
			'A WP_Error object was not returned.'
		);

		$this->assertSame(
			'invalid_domain',
			$actual->get_error_code(),
			'The wrong error code was returned.'
		);
	}

	/**
	 * Tests that a WP_Error response is returned as-is.
	 */
	public function test_should_return_wp_error_response_as_is() {
		$GLOBALS['pagenow'] = 'update-core.php';

		$this->filter_http_request(
			'https://my-plugin.com',
			new WP_Error( 'wp_error_as_is', 'A WP_Error object.' )
		);

		$actual = ( new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] ) )->run();

		$this->assertWPError(
			$actual,
			'A WP_Error object was not returned.'
		);

		$this->assertSame(
			'wp_error_as_is',
			$actual->get_error_code(),
			'The wrong error code was returned.'
		);
	}

	/**
	 * Tests that a WP_Error object is returned for an invalid JSON response.
	 *
	 * @dataProvider data_invalid_json_responses
	 *
	 * @param string $res A JSON response.
	 */
	public function test_should_return_wp_error_for_an_invalid_json_response( $res ) {
		$GLOBALS['pagenow'] = 'update-core.php';

		$this->filter_http_request(
			'https://my-plugin.com',
			$res
		);

		$actual = ( new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] ) )->run();

		$this->assertWPError(
			$actual,
			'A WP_Error object was not returned.'
		);

		$this->assertSame(
			'non_json_api_response',
			$actual->get_error_code(),
			'The wrong error code was returned.'
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_invalid_json_responses() {
		return array(
			'a malformed JSON response'           => array(
				'response' => array( 'body' => '{[}]' ),
			),
			'an empty JSON response'              => array(
				'response' => array( 'body' => '' ),
			),
			'an empty array JSON response'        => array(
				'response' => array( 'body' => '[]' ),
			),
			'an empty object JSON response'       => array(
				'response' => array( 'body' => '{}' ),
			),
			'a JSON response with an "error" key' => array(
				'response' => array( 'body' => '{"error": true}' ),
			),
		);
	}

	/**
	 * Tests that a WP_Error object is returned when the transient value has an error.
	 */
	public function test_should_return_wp_error_when_transient_value_has_error() {
		$GLOBALS['pagenow'] = 'update-core.php';

		set_site_transient(
			'git-updater-lite_my-plugin/my-plugin.php',
			(object) array( 'error' => true )
		);

		$actual = ( new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] ) )->run();

		$this->assertWPError(
			$actual,
			'A WP_Error object was not returned.'
		);

		$this->assertSame(
			'repo-no-exist',
			$actual->get_error_code(),
			'The wrong error code was returned.'
		);
	}

	/**
	 * Tests that the 'api_data' property is set to the transient value
	 * when it exists.
	 */
	public function test_should_set_api_data_to_transient_when_set() {
		$GLOBALS['pagenow'] = 'update-core.php';

		$transient_name = 'git-updater-lite_my-plugin/my-plugin.php';
		$body           = '{"name":"My plugin","slug":"my-plugin","git":"github","type":"plugin","url":"https:\/\/github.com\/afragen\/git-updater","is_private":false,"dot_org":false,"release_asset":false,"version":"12.12.1","author":"Andy Fragen","contributors":{"afragen":{"display_name":"afragen","profile":"\/\/profiles.wordpress.org\/afragen","avatar":"https:\/\/wordpress.org\/grav-redirect.php?user=afragen"}},"requires":"5.9","tested":"6.8.2","requires_php":"8.0","requires_plugins":[],"sections":{"description":"<p>This is a description.<\/p>"},"short_description":"This is a short description.","primary_branch":"main","branch":"main","download_link":"https:\/\/downloads.example.org\/file.zip","tags":{},"donate_link":"","banners":{},"icons":{"default":"https:\/\/s.w.org\/plugins\/geopattern-icon\/git-updater.svg","svg":"https:\/\/raw.githubusercontent.com\/afragen\/git-updater\/master\/assets\/icon.svg"},"last_updated":"2025-02-12T17:58:02Z","num_ratings":0,"rating":0,"active_installs":0,"homepage":"https:\/\/git-updater.com","external":"xxx"}';
		$expected       = (object) json_decode( $body, true );
		set_site_transient( $transient_name, $expected );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();

		$api_data = $this->get_property_value( $lite, 'api_data' );

		$this->assertInstanceOf(
			get_class( $expected ),
			$api_data,
			'The "api_data" value is not the expected type.'
		);

		$this->assertSame(
			(array) $expected,
			(array) $api_data,
			'The transient and "api_data" values not match.'
		);
	}

	/**
	 * Tests that null is returned on a disallowed page.
	 */
	public function test_should_return_null_on_disallowed_page() {
		$GLOBALS['pagenow'] = 'edit.php';

		$this->assertNull( ( new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] ) )->run() );
	}

	/**
	 * Tests that null is returned when the 'pagenow' global is not set and
	 * the 'PHP_SELF' server variable is set to a disallowed page.
	 */
	public function test_should_return_null_when_pagenow_global_is_not_set_and_phpself_server_variable_is_a_disallowed_page() {
		unset( $GLOBALS['pagenow'] );
		$_SERVER['PHP_SELF'] = 'edit.php';

		$this->assertNull( ( new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] ) )->run() );
	}

	/**
	 * Tests that null is returned when the 'pagenow' global and 'PHP_SELF' server variable are not set.
	 */
	public function test_should_return_null_when_pagenow_global_and_phpself_server_variable_are_not_set() {
		unset( $GLOBALS['pagenow'], $_SERVER['PHP_SELF'] );
		$this->assertNull( ( new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] ) )->run() );
	}

	/**
	 * Tests that null is returned when no error occurs.
	 *
	 * @dataProvider data_allowed_pages
	 *
	 * @param string $page An allowed page.
	 */
	public function test_should_return_null_when_no_error_occurs( $page ) {
		$GLOBALS['pagenow'] = $page;

		$this->filter_http_request(
			'https://my-plugin.com',
			array( 'body' => '{"name":"My plugin","slug":"my-plugin","git":"github","type":"plugin","url":"https:\/\/github.com\/afragen\/git-updater","is_private":false,"dot_org":false,"release_asset":false,"version":"12.12.1","author":"Andy Fragen","contributors":{"afragen":{"display_name":"afragen","profile":"\/\/profiles.wordpress.org\/afragen","avatar":"https:\/\/wordpress.org\/grav-redirect.php?user=afragen"}},"requires":"5.9","tested":"6.8.2","requires_php":"8.0","requires_plugins":[],"sections":{"description":"<p>This is a description.<\/p>"},"short_description":"This is a short description.","primary_branch":"main","branch":"main","download_link":"https:\/\/downloads.example.org\/file.zip","tags":{},"donate_link":"","banners":{},"icons":{"default":"https:\/\/s.w.org\/plugins\/geopattern-icon\/git-updater.svg","svg":"https:\/\/raw.githubusercontent.com\/afragen\/git-updater\/master\/assets\/icon.svg"},"last_updated":"2025-02-12T17:58:02Z","num_ratings":0,"rating":0,"active_installs":0,"homepage":"https:\/\/git-updater.com","external":"xxx"}' )
		);

		$this->assertNull( ( new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] ) )->run() );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_allowed_pages() {
		return self::text_array_to_dataprovider(
			array(
				'update-core.php',
				'update.php',
				'plugins.php',
				'themes.php',
				'plugin-install.php',
				'theme-install.php',
				'admin-ajax.php',
				'index.php',
				'wp-cron.php',
			)
		);
	}

	/**
	 * Tests that the 'api_data' property is set when no error occurs.
	 */
	public function test_should_set_api_data_when_no_error_occurs() {
		$GLOBALS['pagenow'] = 'update-core.php';

		$response = '{"name":"My plugin","slug":"my-plugin","git":"github","type":"plugin","url":"https:\/\/github.com\/afragen\/git-updater","is_private":false,"dot_org":false,"release_asset":false,"version":"12.12.1","author":"Andy Fragen","contributors":{"afragen":{"display_name":"afragen","profile":"\/\/profiles.wordpress.org\/afragen","avatar":"https:\/\/wordpress.org\/grav-redirect.php?user=afragen"}},"requires":"5.9","tested":"6.8.2","requires_php":"8.0","requires_plugins":[],"sections":{"description":"<p>This is a description.<\/p>"},"short_description":"This is a short description.","primary_branch":"main","branch":"main","download_link":"https:\/\/downloads.example.org\/file.zip","tags":{},"donate_link":"","banners":{},"icons":{"default":"https:\/\/s.w.org\/plugins\/geopattern-icon\/git-updater.svg","svg":"https:\/\/raw.githubusercontent.com\/afragen\/git-updater\/master\/assets\/icon.svg"},"last_updated":"2025-02-12T17:58:02Z","num_ratings":0,"rating":0,"active_installs":0,"homepage":"https:\/\/git-updater.com","external":"xxx"}';
		$this->filter_http_request(
			'https://my-plugin.com',
			array( 'body' => $response )
		);

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();

		$expected         = json_decode( $response, true );
		$expected['file'] = 'my-plugin/my-plugin.php';
		$actual           = (array) $this->get_property_value( $lite, 'api_data' );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Tests that the transient is set when no error occurs.
	 */
	public function test_should_set_transient_when_no_error_occurs() {
		$GLOBALS['pagenow'] = 'update-core.php';

		$response = '{"name":"My plugin","slug":"my-plugin","git":"github","type":"plugin","url":"https:\/\/github.com\/afragen\/git-updater","is_private":false,"dot_org":false,"release_asset":false,"version":"12.12.1","author":"Andy Fragen","contributors":{"afragen":{"display_name":"afragen","profile":"\/\/profiles.wordpress.org\/afragen","avatar":"https:\/\/wordpress.org\/grav-redirect.php?user=afragen"}},"requires":"5.9","tested":"6.8.2","requires_php":"8.0","requires_plugins":[],"sections":{"description":"<p>This is a description.<\/p>"},"short_description":"This is a short description.","primary_branch":"main","branch":"main","download_link":"https:\/\/downloads.example.org\/file.zip","tags":{},"donate_link":"","banners":{},"icons":{"default":"https:\/\/s.w.org\/plugins\/geopattern-icon\/git-updater.svg","svg":"https:\/\/raw.githubusercontent.com\/afragen\/git-updater\/master\/assets\/icon.svg"},"last_updated":"2025-02-12T17:58:02Z","num_ratings":0,"rating":0,"active_installs":0,"homepage":"https:\/\/git-updater.com","external":"xxx"}';
		$this->filter_http_request(
			'https://my-plugin.com',
			array( 'body' => $response )
		);

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();

		$file      = $this->get_property_value( $lite, 'file' );
		$api_data  = $this->get_property_value( $lite, 'api_data' );
		$transient = get_site_transient( "git-updater-lite_{$file}" );

		$this->assertInstanceOf(
			get_class( $api_data ),
			$transient,
			'The transient is not the expected type.'
		);

		$this->assertSame(
			(array) $api_data,
			(array) $transient,
			'The "api_data" and transient values not match.'
		);
	}

	/**
	 * Tests that the transient is set when the 'pagenow' global is not set
	 * but the 'PHP_SELF' server variable matches an allowed page.
	 */
	public function test_should_set_transient_when_pagenow_global_is_not_set_but_phpself_server_variable_is_allowed() {
		unset( $GLOBALS['pagenow'] );
		$_SERVER['PHP_SELF'] = 'update-core.php';

		$response = '{"name":"My plugin","slug":"my-plugin","git":"github","type":"plugin","url":"https:\/\/github.com\/afragen\/git-updater","is_private":false,"dot_org":false,"release_asset":false,"version":"12.12.1","author":"Andy Fragen","contributors":{"afragen":{"display_name":"afragen","profile":"\/\/profiles.wordpress.org\/afragen","avatar":"https:\/\/wordpress.org\/grav-redirect.php?user=afragen"}},"requires":"5.9","tested":"6.8.2","requires_php":"8.0","requires_plugins":[],"sections":{"description":"<p>This is a description.<\/p>"},"short_description":"This is a short description.","primary_branch":"main","branch":"main","download_link":"https:\/\/downloads.example.org\/file.zip","tags":{},"donate_link":"","banners":{},"icons":{"default":"https:\/\/s.w.org\/plugins\/geopattern-icon\/git-updater.svg","svg":"https:\/\/raw.githubusercontent.com\/afragen\/git-updater\/master\/assets\/icon.svg"},"last_updated":"2025-02-12T17:58:02Z","num_ratings":0,"rating":0,"active_installs":0,"homepage":"https:\/\/git-updater.com","external":"xxx"}';
		$this->filter_http_request(
			'https://my-plugin.com',
			array( 'body' => $response )
		);

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();

		$file      = $this->get_property_value( $lite, 'file' );
		$api_data  = $this->get_property_value( $lite, 'api_data' );
		$transient = get_site_transient( "git-updater-lite_{$file}" );

		$this->assertInstanceOf(
			get_class( $api_data ),
			$transient,
			'The transient is not the expected type.'
		);

		$this->assertSame(
			(array) $api_data,
			(array) $transient,
			'The "api_data" and transient values not match.'
		);
	}
}
