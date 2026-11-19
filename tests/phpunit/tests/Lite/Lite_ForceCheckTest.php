<?php
/**
 * Class Lite_ForceCheckTest
 *
 * @package git-updater-lite
 */

/**
 * Tests for Dashboard > Updates > "Check again" refreshing the cached API data.
 *
 * @covers \Fragen\Git_Updater\Lite::run
 * @covers \Fragen\Git_Updater\Lite::force_check
 * @covers \Fragen\Git_Updater\Lite::load_hooks
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
		remove_all_filters( 'pre_http_request' );
		delete_site_transient( 'git-updater-lite_my-plugin/my-plugin.php' );
		delete_site_transient( 'git-updater-lite-forcecheck_my-plugin/my-plugin.php' );
		delete_site_option( 'git_updater_lite_client_key' );
		delete_site_option( 'git_updater_lite_private_slugs' );
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Tests that "Check again" registers the forced refresh ahead of core's own.
	 */
	public function test_should_register_force_check_on_check_again() {
		$this->cache( array( 'version' => '1.0.0' ) );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();

		$this->assertSame( 5, has_action( 'load-update-core.php', array( $lite, 'force_check' ) ) );
	}

	/**
	 * Tests that the forced refresh is not registered otherwise.
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
		$this->cache( array( 'version' => '1.0.0' ) );

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
	 * Tests that an authorized user replaces the cached data.
	 */
	public function test_should_refresh_cached_data_for_admin() {
		$this->log_in_admin();
		$this->cache( array( 'version' => '1.0.0' ) );
		$this->mock_server( $this->api_response( '2.0.0' ) );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();
		$this->assertSame( 0, $this->requests, 'run() should use the cached data.' );

		$lite->force_check();

		$this->assertSame( 1, $this->requests );
		$this->assertSame( '2.0.0', $this->get_property_value( $lite, 'api_data' )->version );
		$this->assertSame( '2.0.0', get_site_transient( 'git-updater-lite_my-plugin/my-plugin.php' )->version );
	}

	/**
	 * Tests that a user without update capability cannot trigger a request.
	 */
	public function test_should_not_refresh_for_subscriber() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->cache( array( 'version' => '1.0.0' ) );
		$this->mock_server( $this->api_response( '2.0.0' ) );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();
		$lite->force_check();

		$this->assertSame( 0, $this->requests );
		$this->assertSame( '1.0.0', get_site_transient( 'git-updater-lite_my-plugin/my-plugin.php' )->version );
	}

	/**
	 * Tests that a logged-out request cannot trigger a request.
	 */
	public function test_should_not_refresh_when_logged_out() {
		wp_set_current_user( 0 );
		$this->cache( array( 'version' => '1.0.0' ) );
		$this->mock_server( $this->api_response( '2.0.0' ) );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();
		$lite->force_check();

		$this->assertSame( 0, $this->requests );
	}

	/**
	 * Tests that the capability is checked against the package type.
	 *
	 * @group ms-excluded
	 */
	public function test_should_require_the_capability_for_the_package_type() {
		$plugin_editor = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		get_user_by( 'id', $plugin_editor )->add_cap( 'update_plugins' );
		wp_set_current_user( $plugin_editor );
		$this->cache(
			array(
				'version' => '1.0.0',
				'type'    => 'theme',
			)
		);
		$this->mock_server( $this->api_response( '2.0.0', 'theme' ) );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();
		$lite->force_check();

		$this->assertSame( 0, $this->requests, 'An update_plugins user must not refresh a theme package.' );

		$theme_editor = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		get_user_by( 'id', $theme_editor )->add_cap( 'update_themes' );
		wp_set_current_user( $theme_editor );
		delete_site_transient( 'git-updater-lite-forcecheck_my-plugin/my-plugin.php' );

		$lite->force_check();

		$this->assertSame( 1, $this->requests, 'An update_themes user should refresh a theme package.' );
	}

	/**
	 * Tests that repeated checks within the throttle window do not refetch.
	 */
	public function test_should_throttle_repeated_force_checks() {
		$this->log_in_admin();
		$this->cache( array( 'version' => '1.0.0' ) );
		$this->mock_server( $this->api_response( '2.0.0' ) );

		$first = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$first->run();
		$first->force_check();

		$second = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$second->run();
		$second->force_check();

		$this->assertSame( 1, $this->requests );
	}

	/**
	 * Tests that a request already made by run() is not repeated.
	 */
	public function test_should_not_refetch_when_run_already_fetched() {
		$this->log_in_admin();
		$this->mock_server( $this->api_response() );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();
		$lite->force_check();

		$this->assertSame( 1, $this->requests );
	}

	/**
	 * Tests that a failed refresh leaves the cached data in place.
	 */
	public function test_should_leave_cache_untouched_when_refresh_fails() {
		$this->log_in_admin();
		$this->cache( array( 'version' => '1.0.0' ) );
		$this->mock_server( new WP_Error( 'http_request_failed', 'Server down.' ) );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();
		$lite->force_check();

		$this->assertSame( 1, $this->requests );
		$this->assertSame( '1.0.0', $this->get_property_value( $lite, 'api_data' )->version );
		$this->assertSame( '1.0.0', get_site_transient( 'git-updater-lite_my-plugin/my-plugin.php' )->version );
	}

	/**
	 * Tests that a newly-private response never leaves a stale entry behind.
	 */
	public function test_should_not_cache_newly_private_response() {
		$this->log_in_admin();
		$this->cache( array( 'version' => '1.0.0' ) );
		$this->mock_server( $this->api_response( '2.0.0', 'plugin', true ) );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();
		$lite->force_check();

		$this->assertFalse( get_site_transient( 'git-updater-lite_my-plugin/my-plugin.php' ) );
		$this->assertContains( 'my-plugin', (array) get_site_option( 'git_updater_lite_private_slugs', array() ) );
		$this->assertNotSame( '', (string) get_site_option( 'git_updater_lite_client_key', '' ) );
	}

	/**
	 * Tests that hooks are loaded when the refresh recovers from a cached error.
	 */
	public function test_should_load_hooks_when_refresh_recovers_from_cached_error() {
		$this->log_in_admin();
		$this->cache( array( 'error' => true ) );
		$this->mock_server( $this->api_response() );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$this->assertWPError( $lite->run() );
		$this->assertFalse( has_filter( 'site_transient_update_plugins', array( $lite, 'update_site_transient' ) ) );

		$lite->force_check();

		$this->assertSame( 20, has_filter( 'site_transient_update_plugins', array( $lite, 'update_site_transient' ) ) );
	}

	/**
	 * Tests that loading hooks twice does not register them twice.
	 */
	public function test_should_not_register_hooks_twice() {
		$this->log_in_admin();
		$this->cache( array( 'version' => '1.0.0' ) );
		$this->mock_server( $this->api_response( '2.0.0' ) );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();
		$lite->force_check();

		$before = count( $GLOBALS['wp_filter']['site_transient_update_plugins']->callbacks[20] ?? array() );
		$lite->load_hooks();
		$after = count( $GLOBALS['wp_filter']['site_transient_update_plugins']->callbacks[20] ?? array() );

		$this->assertSame( $before, $after );
	}

	/**
	 * Tests that a subsite administrator cannot flush the network-wide cache.
	 *
	 * @group ms-required
	 */
	public function test_should_not_refresh_for_subsite_admin() {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		$this->cache( array( 'version' => '1.0.0' ) );
		$this->mock_server( $this->api_response( '2.0.0' ) );

		$lite = new \Fragen\Git_Updater\Lite( $this->test_files['plugin'] );
		$lite->run();
		$lite->force_check();

		$this->assertSame( 0, $this->requests, 'A subsite administrator must not refresh the network cache.' );

		grant_super_admin( $user_id );
		wp_set_current_user( 0 );
		wp_set_current_user( $user_id );
		delete_site_transient( 'git-updater-lite-forcecheck_my-plugin/my-plugin.php' );

		$lite->force_check();

		$this->assertSame( 1, $this->requests, 'A super admin should refresh the network cache.' );
	}

	/**
	 * Log in an administrator, granting super admin on multisite.
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
	 * @param mixed $res Response to return.
	 */
	private function mock_server( $res ) {
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
	 * Build a successful API response.
	 *
	 * @param string    $version    The version to report.
	 * @param string    $type       The package type.
	 * @param bool|null $is_private Whether the package is private, or null to omit.
	 * @return array
	 */
	private function api_response( $version = '2.0.0', $type = 'plugin', $is_private = null ) {
		$data = array(
			'name'          => 'My plugin',
			'slug'          => 'my-plugin',
			'git'           => 'github',
			'type'          => $type,
			'version'       => $version,
			'download_link' => 'https://downloads.example.org/file.zip',
		);
		if ( null !== $is_private ) {
			$data['is_private'] = $is_private;
		}

		return array(
			'body'     => wp_json_encode( $data ),
			'response' => array( 'code' => 200 ),
		);
	}
}
