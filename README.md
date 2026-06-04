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

Version 3.0.0 works better with repositories requiring authentication headers using a 2-step process and not exposing the authentication header. It can also be set in Git Updater to use domain validation.

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

FWIW, I test by decreasing the version number locally to see an update.
