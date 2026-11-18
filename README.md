# Git Updater Lite

* Contributors: [Andy Fragen](https://github.com/afragen)
* Tags: plugin, theme, updater, git-updater
* Requires at least: 6.6
* Requires PHP: 7.4
* Donate link: <https://thefragens.com/git-updater-donate>
* License: MIT

A simple standalone library to enable automatic updates to your git hosted WordPress plugins or themes.

## Description

**This is version 3.x and contains a breaking changes.**

This library was designed to be added to your git hosted plugin or theme to enable standalone updates. 

You must have a publicly reachable site that will be used for dynamically retrieving the update API data.

* [Git Updater](https://git-updater.com) is required on a site where all of the release versions of your plugins and themes are installed.
* All of your plugins/themes **must** be integrated with Git Updater.
* You must be using Git Updater v13.1.x or better. 

Git Updater is capable of returning a [REST endpoint](https://git-updater.com/knowledge-base/remote-management-restful-endpoints/#articleTOC_3/) containing the `plugins_api()` or `themes_api()` data for your plugin/theme. You will pass this endpoint during the integration.

The REST endpoint format is as follows.

* plugins - `https://my-site.com/wp-json/git-updater/v1/update-api/?slug=my-plugin`
* themes - `https://my-site.com/wp-json/git-updater/v1/update-api/?slug=my-theme`

Version 3.0.0 works better with repositories requiring authentication headers using a 2-step process and not exposing the authentication header.

Version 3.2.0 replaces the earlier `X-GU-Site-Domain` header with a per-site **client key**. When the client first needs a **private** package it generates a key locally and sends it as `X-GU-Lite-Key` (with a display-only `X-GU-Client-Label`). The update server lists the client as pending; an administrator approves it once on the server's **Lite Clients** tab. Public packages never send a key and need no configuration. Older clients that send `X-GU-Site-Domain` are no longer recognized by a current server; see the Git Updater docs for the rollout order.

Version 3.3.0 detects `is_private` on a successful response and generates the key proactively, instead of waiting for a 403. This lets a site identify itself for approval before enforcement.

## Installation

Add via composer. `composer require afragen/git-updater-lite:^3`

* Add the `Update URI: <update server URI>` header to your plugin or theme headers. Where `<update server URI>` is the domain to the update server, eg `https://git-updater.com`.

* Add the following code to your plugin file or theme's functions.php file.

```php
require_once __DIR__ . '/vendor/afragen/git-updater-lite/Lite.php';
( new \Fragen\Git_Updater\Lite( __FILE__ ) )->run();
```

An example integrated plugin is here, https://github.com/afragen/test-plugin-gu-lite

```php
<?php
/**
 * Plugin Name: Test Plugin Git Updater Lite
 * Plugin URI: https://github.com/afragen/test-plugin-gu-lite/
 * Description: This plugin is used for testing functionality of Github updating of plugins.
 * Version: 0.2.0
 * Author: Andy Fragen
 * License: MIT
 * Requires WP: 6.6
 * Requires PHP: 7.4
 * Update URI: https://git-updater.com
 */

 /**
 * Exit if called directly.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/vendor/afragen/git-updater-lite/Lite.php';
( new \Fragen\Git_Updater\Lite( __FILE__ ) )->run();
```

## Private packages and legacy clients

A Lite client that sends `X-GU-Lite-Key` keeps working with private packages once its key is
approved. If a consuming plugin or theme cannot be updated to Lite 3.2.0 or newer, deploy the
shim instead of disabling the server gate:

1. Generate a random 256-bit key for the site.
2. Copy `mu/git-updater-lite-key-shim.php` to the site's `wp-content/mu-plugins/` directory.
3. Define the key in `wp-config.php`:

   ```php
   define( 'GIT_UPDATER_LITE_SHIM_KEY', 'the-per-site-key' );
   ```

4. Approve the pending client on the update server's **Lite Clients** tab (or pre-approve the
   key with the server-side `git_updater_lite_client_key` filter).
5. Verify a private update succeeds.

The shim adds `X-GU-Lite-Key` and `X-GU-Client-Label` to both the `update-api` and
`download-token` requests. The key is filterable, so a per-site provisioning layer can set it
without a constant:

```php
add_filter( 'git_updater_lite_shim_key', fn() => 'the-per-site-key' );
```

Do not authorize a legacy client by domain or IP instead — those are self-asserted or brittle
and reopen the anonymous-download vulnerability. See the Git Updater
`docs/private-package-rollout.md` for the full rollout order.

FWIW, I test by decreasing the version number locally to see an update.
