<?php
/**
 * Class Lite_ForceCheckTest
 *
 * @package git-updater-lite
 */

/**
 * Tests for forcing a refetch from Dashboard > Updates > "Check again".
 *
 * @covers \Fragen\Git_Updater\Lite::run
 * @covers \Fragen\Git_Updater\Lite::force_check
 */
class Lite_ForceCheckTest extends GitUpdater_UnitTestCase {
	/**
	 * Number of requests made to the update server.
	 *
	 * @var int
	 */
	private $requests = 0;

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();
		$this->requests      = 0;
		$GLOBALS['pagenow']  = 'update-core.php';
		$_GET['force-check'] = '1';
	}

	/**
	 * Tear down.
	 */
	public function tear_down() {
		unset( $_GET['force-check'] );
		remove_all_actions( 'load-update-core.php' );
		delete_site_transient( 'git-updater-lite_my-plugin/my-plugin.php' );
		parent::tear_down();
	}

	/**
	 * Log in a user who can update plugins.
	 */
	private function log_in_admin() {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		if ( is_multisite() ) {
			grant_super_admin( $user_id );
		}
		wp_set_current_user( $user_id );
	}

	/**
	 * Mock the update server, counting requests.
	 *
	 * @param mixed  $res Response to return, or null for a valid response with $version.
	 * @param string $version Version for the valid response.
	 */
	private function mock_server( $res = null, $version = '2.0.0' ) {
		if ( null === $res ) {
			$res = array(
				'body'     => wp_json_encode(
					array(
						'name'    => 'My plugin',
						'slug'    => 'my-plugin',
						'type'    => 'plugin',
						'version' => $version,
					)
				),
				'response' => array( 'code' => 200 ),
			);
		}
		add_filter(
			'pre_http_request',
			function ( $response, $parsed_args, $url ) use ( $res ) {
				if ( ! str_contains( $url, 'https://my-plugin.com' ) ) {
					return $response;
				}
				++$this->requests;
				return $res;
			},
			10,
			3
		);
	}

	/**
	 * Cache API data for the test plugin.
	 *
	 * @param array $data API data.
	 */
	private function cache( array $data ) {
		set_site_transient( 'git-updater-lite_my-plugin/my-plugin.php', (object) $data, HOUR_IN_SECONDS );
	}

	/**
	 * Tests that "Check again" registers the forced refetch.
	 */
	public function test_should_register_force_check_on_check_again() {
		$this->cache(
			array(
				'slug'    => 'my-plugin',
				'type'    => 'plugin',
				'version' => '1.0.0',
			)
		);

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();

		$this->assertSame( 10, has_action( 'load-update-core.php', array( $lite, 'force_check' ) ) );
	}

	/**
	 * Tests that the forced refetch is not registered without "Check again".
	 *
	 * @dataProvider data_not_check_again
	 *
	 * @param string $page        The current page.
	 * @param bool   $force_check Whether force-check is requested.
	 */
	public function test_should_not_register_force_check( $page, $force_check ) {
		$GLOBALS['pagenow'] = $page;
		if ( ! $force_check ) {
			unset( $_GET['force-check'] );
		}
		$this->cache(
			array(
				'slug'    => 'my-plugin',
				'type'    => 'plugin',
				'version' => '1.0.0',
			)
		);

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();

		$this->assertFalse( has_action( 'load-update-core.php', array( $lite, 'force_check' ) ) );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_not_check_again() {
		return array(
			'update-core.php without force-check' => array( 'update-core.php', false ),
			'plugins.php with force-check'        => array( 'plugins.php', true ),
		);
	}

	/**
	 * Tests that "Check again" replaces cached data with fresh data.
	 */
	public function test_should_refetch_cached_data_for_admin() {
		$this->log_in_admin();
		$this->cache(
			array(
				'slug'    => 'my-plugin',
				'type'    => 'plugin',
				'version' => '1.0.0',
			)
		);
		$this->mock_server( null, '2.0.0' );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();
		$this->assertSame( 0, $this->requests, 'run() should use the cached data.' );

		$lite->force_check();

		$this->assertSame( 1, $this->requests );
		$this->assertSame( '2.0.0', $this->get_property_value( $lite, 'api_data' )->version );
		$this->assertSame( '2.0.0', get_site_transient( 'git-updater-lite_my-plugin/my-plugin.php' )->version );
	}

	/**
	 * Tests that users who cannot update cannot cause requests to the update server.
	 */
	public function test_should_not_refetch_for_user_without_update_capability() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->cache(
			array(
				'slug'    => 'my-plugin',
				'type'    => 'plugin',
				'version' => '1.0.0',
			)
		);
		$this->mock_server();

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();
		$lite->force_check();

		$this->assertSame( 0, $this->requests );
		$this->assertSame( '1.0.0', get_site_transient( 'git-updater-lite_my-plugin/my-plugin.php' )->version );
	}

	/**
	 * Tests that a request that already queried the update server does not query it again.
	 */
	public function test_should_not_refetch_when_already_fetched_this_request() {
		$this->log_in_admin();
		$this->mock_server();

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();
		$lite->force_check();

		$this->assertSame( 1, $this->requests );
	}

	/**
	 * Tests that a failed forced fetch keeps the cached data.
	 */
	public function test_should_keep_cached_data_when_refetch_fails() {
		$this->log_in_admin();
		$this->cache(
			array(
				'slug'    => 'my-plugin',
				'type'    => 'plugin',
				'version' => '1.0.0',
			)
		);
		$this->mock_server( new WP_Error( 'http_request_failed', 'Server down.' ) );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();
		$result = $lite->force_check();

		$this->assertWPError( $result );
		$this->assertSame( '1.0.0', $this->get_property_value( $lite, 'api_data' )->version );
		$this->assertSame( '1.0.0', get_site_transient( 'git-updater-lite_my-plugin/my-plugin.php' )->version );
	}

	/**
	 * Tests that hooks are loaded when the forced fetch recovers from a cached error.
	 */
	public function test_should_load_hooks_when_refetch_recovers_from_cached_error() {
		$this->log_in_admin();
		$this->cache( array( 'error' => true ) );
		$this->mock_server();

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->assertWPError( $lite->run() );
		$this->assertFalse( has_filter( 'site_transient_update_plugins', array( $lite, 'update_site_transient' ) ) );

		$lite->force_check();

		$this->assertSame( 20, has_filter( 'site_transient_update_plugins', array( $lite, 'update_site_transient' ) ) );
	}
}
