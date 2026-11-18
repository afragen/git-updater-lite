<?php
/**
 * Tests for the legacy client key shim.
 *
 * @package git-updater-lite
 */

/**
 * Tests for mu/git-updater-lite-key-shim.php.
 */
class Lite_ShimTest extends WP_UnitTestCase {

	/**
	 * Load the shim for each case.
	 *
	 * WordPress restores registered hooks in tear_down(), so the shim's filter
	 * is removed after every test; re-require the file to register it again.
	 * It declares no functions or classes, so re-including is safe.
	 */
	public function set_up(): void {
		parent::set_up();
		require dirname( __DIR__, 4 ) . '/mu/git-updater-lite-key-shim.php';
	}

	/**
	 * Remove the test key filter between cases.
	 */
	public function tear_down(): void {
		remove_all_filters( 'git_updater_lite_shim_key' );
		parent::tear_down();
	}

	/**
	 * Without a provisioned key the shim must not add any header.
	 */
	public function test_does_not_add_headers_without_a_key() {
		$args = $this->apply_request_args( 'https://example.com/wp-json/git-updater/v1/update-api/?slug=my-plugin' );

		$this->assertArrayNotHasKey( 'X-GU-Lite-Key', $args['headers'] );
		$this->assertArrayNotHasKey( 'X-GU-Client-Label', $args['headers'] );
	}

	/**
	 * The shim adds the key and label to the update-api request.
	 */
	public function test_adds_headers_for_update_api() {
		add_filter( 'git_updater_lite_shim_key', fn() => 'test-client-key' );

		$args = $this->apply_request_args( 'https://example.com/wp-json/git-updater/v1/update-api/?slug=my-plugin' );

		$this->assertSame( 'test-client-key', $args['headers']['X-GU-Lite-Key'] );
		$this->assertSame( home_url(), $args['headers']['X-GU-Client-Label'] );
	}

	/**
	 * The shim also covers the two-step download-token request.
	 */
	public function test_adds_headers_for_download_token() {
		add_filter( 'git_updater_lite_shim_key', fn() => 'test-client-key' );

		$args = $this->apply_request_args( 'https://example.com/wp-json/git-updater/v1/download-token/my-plugin' );

		$this->assertSame( 'test-client-key', $args['headers']['X-GU-Lite-Key'] );
	}

	/**
	 * Requests outside the git-updater namespace are left alone.
	 */
	public function test_does_not_add_headers_for_unrelated_urls() {
		add_filter( 'git_updater_lite_shim_key', fn() => 'test-client-key' );

		$args = $this->apply_request_args( 'https://example.com/wp-json/wp/v2/posts' );

		$this->assertArrayNotHasKey( 'X-GU-Lite-Key', $args['headers'] );
	}

	/**
	 * Run the http_request_args filter chain with empty headers.
	 *
	 * @param string $url Request URL.
	 * @return array<string, mixed>
	 */
	private function apply_request_args( string $url ): array {
		return apply_filters( 'http_request_args', [ 'headers' => [] ], $url );
	}
}
