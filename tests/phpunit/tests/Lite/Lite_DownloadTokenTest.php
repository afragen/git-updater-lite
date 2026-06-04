<?php
/**
 * Class Lite_DownloadTokenTest
 *
 * @package git-updater-lite
 */

/**
 * Tests for Lite download token flow.
 *
 * @covers \Fragen\Git_Updater\Lite::load_hooks
 * @covers \Fragen\Git_Updater\Lite::get_site_domain
 */
class Lite_DownloadTokenTest extends GitUpdater_UnitTestCase {
	/**
	 * Tests that get_site_domain() returns the expected domain.
	 */
	public function test_get_site_domain_returns_expected_domain() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );

		$method = new ReflectionMethod( $lite, 'get_site_domain' );
		$method->setAccessible( true );

		$actual   = $method->invoke( $lite );
		$expected = parse_url( home_url(), PHP_URL_HOST );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Tests that http_request_args filter is added for non-token packages.
	 */
	public function test_upgrader_pre_download_adds_auth_header_filter_for_non_token_packages() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->set_property_value( $lite, 'api_data', (object) array( 'type' => 'plugin' ) );
		$lite->load_hooks();

		// Apply the filter with a non-token package URL.
		$result = apply_filters( 'upgrader_pre_download', false, 'https://example.com/package.zip', new Plugin_Upgrader() );

		// Should return false to proceed with normal download.
		$this->assertFalse( $result );

		// Should have added http_request_args filter.
		$this->assertNotFalse( has_filter( 'http_request_args', array( $lite, 'add_auth_header' ) ) );

		remove_all_filters( 'http_request_args' );
	}

	/**
	 * Tests that X-GU-Site-Domain header is sent for token packages.
	 */
	public function test_upgrader_pre_download_sends_site_domain_header_for_token_packages() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->set_property_value( $lite, 'api_data', (object) array( 'type' => 'plugin', 'slug' => 'my-plugin' ) );
		$lite->load_hooks();

		$captured_args = null;

		// Mock the token endpoint response.
		add_filter(
			'pre_http_request',
			function ( $response, $parsed_args, $url ) use ( &$captured_args ) {
				if ( str_contains( $url, 'download-token/' ) ) {
					$captured_args = $parsed_args;
					return array(
						'body'     => wp_json_encode( array( 'download_link' => 'https://example.com/fresh.zip' ) ),
						'response' => array( 'code' => 200 ),
					);
				}
				return $response;
			},
			10,
			3
		);

		// Mock download_url to return a temp file.
		add_filter(
			'pre_http_request',
			function ( $response, $parsed_args, $url ) {
				if ( str_contains( $url, 'fresh.zip' ) ) {
					$tmp = tempnam( sys_get_temp_dir(), 'gu_test_' );
					file_put_contents( $tmp, 'test content' );
					return array(
						'body'     => $tmp,
						'response' => array( 'code' => 200 ),
					);
				}
				return $response;
			},
			20,
			3
		);

		apply_filters( 'upgrader_pre_download', false, 'https://my-plugin.com/download-token/abc123', new Plugin_Upgrader() );

		$this->assertNotNull( $captured_args, 'Token request was not made.' );
		$this->assertArrayHasKey( 'headers', $captured_args );
		$this->assertArrayHasKey( 'X-GU-Site-Domain', $captured_args['headers'] );

		remove_all_filters( 'pre_http_request' );
	}

	/**
	 * Tests that 403 error returns gu_token_fetch_failed WP_Error.
	 */
	public function test_upgrader_pre_download_returns_error_on_403() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->set_property_value( $lite, 'api_data', (object) array( 'type' => 'plugin', 'slug' => 'my-plugin' ) );
		$lite->load_hooks();

		// Mock 403 response.
		add_filter(
			'pre_http_request',
			function ( $response, $parsed_args, $url ) {
				if ( str_contains( $url, 'download-token/' ) ) {
					return array(
						'body'     => wp_json_encode( array( 'message' => 'Access denied.' ) ),
						'response' => array( 'code' => 403 ),
					);
				}
				return $response;
			},
			10,
			3
		);

		$result = apply_filters( 'upgrader_pre_download', false, 'https://my-plugin.com/download-token/abc123', new Plugin_Upgrader() );

		$this->assertWPError( $result );
		$this->assertSame( 'gu_token_fetch_failed', $result->get_error_code() );
		$this->assertStringContainsString( 'Access denied', $result->get_error_message() );

		remove_all_filters( 'pre_http_request' );
	}

	/**
	 * Tests that non-200 status returns gu_token_fetch_failed WP_Error.
	 */
	public function test_upgrader_pre_download_returns_error_on_non_200_status() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->set_property_value( $lite, 'api_data', (object) array( 'type' => 'plugin', 'slug' => 'my-plugin' ) );
		$lite->load_hooks();

		// Mock 500 response.
		add_filter(
			'pre_http_request',
			function ( $response, $parsed_args, $url ) {
				if ( str_contains( $url, 'download-token/' ) ) {
					return array(
						'body'     => 'Internal Server Error',
						'response' => array( 'code' => 500 ),
					);
				}
				return $response;
			},
			10,
			3
		);

		$result = apply_filters( 'upgrader_pre_download', false, 'https://my-plugin.com/download-token/abc123', new Plugin_Upgrader() );

		$this->assertWPError( $result );
		$this->assertSame( 'gu_token_fetch_failed', $result->get_error_code() );

		remove_all_filters( 'pre_http_request' );
	}

	/**
	 * Tests that missing download_link returns gu_no_fresh_url WP_Error.
	 */
	public function test_upgrader_pre_download_returns_error_when_no_download_link() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->set_property_value( $lite, 'api_data', (object) array( 'type' => 'plugin', 'slug' => 'my-plugin' ) );
		$lite->load_hooks();

		// Mock response without download_link.
		add_filter(
			'pre_http_request',
			function ( $response, $parsed_args, $url ) {
				if ( str_contains( $url, 'download-token/' ) ) {
					return array(
						'body'     => wp_json_encode( array( 'other_data' => 'value' ) ),
						'response' => array( 'code' => 200 ),
					);
				}
				return $response;
			},
			10,
			3
		);

		$result = apply_filters( 'upgrader_pre_download', false, 'https://my-plugin.com/download-token/abc123', new Plugin_Upgrader() );

		$this->assertWPError( $result );
		$this->assertSame( 'gu_no_fresh_url', $result->get_error_code() );

		remove_all_filters( 'pre_http_request' );
	}

	/**
	 * Tests that download_url failure returns the WP_Error.
	 */
	public function test_upgrader_pre_download_returns_download_url_error() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->set_property_value( $lite, 'api_data', (object) array( 'type' => 'plugin', 'slug' => 'my-plugin' ) );
		$lite->load_hooks();

		// Mock token endpoint response.
		add_filter(
			'pre_http_request',
			function ( $response, $parsed_args, $url ) {
				if ( str_contains( $url, 'download-token/' ) ) {
					return array(
						'body'     => wp_json_encode( array( 'download_link' => 'https://example.com/fresh.zip' ) ),
						'response' => array( 'code' => 200 ),
					);
				}
				return $response;
			},
			10,
			3
		);

		// Mock download_url to return a WP_Error.
		add_filter(
			'pre_http_request',
			function ( $response, $parsed_args, $url ) {
				if ( str_contains( $url, 'fresh.zip' ) ) {
					return new WP_Error( 'download_failed', 'Download failed.' );
				}
				return $response;
			},
			20,
			3
		);

		$result = apply_filters( 'upgrader_pre_download', false, 'https://my-plugin.com/download-token/abc123', new Plugin_Upgrader() );

		$this->assertWPError( $result );
		$this->assertSame( 'download_failed', $result->get_error_code() );

		remove_all_filters( 'pre_http_request' );
	}

	/**
	 * Tests that successful token flow returns temp file path.
	 */
	public function test_upgrader_pre_download_returns_temp_file_on_success() {
		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->set_property_value( $lite, 'api_data', (object) array( 'type' => 'plugin', 'slug' => 'my-plugin' ) );
		$lite->load_hooks();

		$fresh_url = 'https://downloads.example.org/fresh-package.zip';

		// Mock the token endpoint response.
		add_filter(
			'pre_http_request',
			function ( $response, $parsed_args, $url ) use ( $fresh_url ) {
				if ( str_contains( $url, 'download-token/' ) ) {
					return array(
						'body'     => wp_json_encode( array( 'download_link' => $fresh_url ) ),
						'response' => array( 'code' => 200 ),
					);
				}
				return $response;
			},
			10,
			3
		);

		// Mock download_url to return a temp file.
		add_filter(
			'pre_http_request',
			function ( $response, $parsed_args, $url ) use ( $fresh_url ) {
				if ( str_contains( $url, $fresh_url ) ) {
					$tmp = tempnam( sys_get_temp_dir(), 'gu_test_' );
					file_put_contents( $tmp, 'test content' );
					return array(
						'body'     => $tmp,
						'response' => array( 'code' => 200 ),
					);
				}
				return $response;
			},
			20,
			3
		);

		$result = apply_filters( 'upgrader_pre_download', false, 'https://my-plugin.com/download-token/abc123', new Plugin_Upgrader() );

		$this->assertIsString( $result );
		$this->assertFileExists( $result );

		// Clean up.
		wp_delete_file( $result );

		remove_all_filters( 'pre_http_request' );
	}
}