<?php
/**
 * Plugin Name: Git Updater Lite Legacy Client Key Shim
 * Description: Sends a provisioned X-GU-Lite-Key for Lite clients that predate the built-in key support.
 * Version:     1.0.0
 *
 * Copy this file to wp-content/mu-plugins/ on a site that still runs
 * git-updater-lite < 3.2.0 and needs private packages. Define
 * GIT_UPDATER_LITE_SHIM_KEY in wp-config.php with the per-site key approved
 * on the update server's Lite Clients tab.
 *
 * @package Git_Updater_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'http_request_args',
	function ( $args, $url ) {
		if ( false === strpos( $url, 'wp-json/git-updater/v1/' ) ) {
			return $args;
		}

		$key = defined( 'GIT_UPDATER_LITE_SHIM_KEY' ) ? (string) GIT_UPDATER_LITE_SHIM_KEY : '';

		/**
		 * Filter the provisioned key used by the shim.
		 *
		 * @param string $key Provisioned client key.
		 * @param string $url Request URL.
		 */
		$key = (string) apply_filters( 'git_updater_lite_shim_key', $key, $url );
		if ( '' === $key ) {
			return $args;
		}

		$args['headers']['X-GU-Lite-Key']     = $key;
		$args['headers']['X-GU-Client-Label'] = home_url();
		return $args;
	},
	10,
	2
);
