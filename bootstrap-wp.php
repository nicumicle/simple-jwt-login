<?php

/**
 * Bootstrap for WP_UnitTestCase-based integration tests.
 *
 * Loads the WordPress test framework, activates the plugin, then boots
 * WordPress so that WP_REST_Request / rest_do_request() are available.
 */

// Force wp_send_json() to call wp_die() instead of raw `die;` everywhere.
// This ensures WP_UnitTestCase's die handler can convert error responses
// into catchable WPDieException rather than killing the test process.
if (!defined('DOING_AJAX')) {
    define('DOING_AJAX', true);
}

$_wp_tests_dir = __DIR__ . '/vendor/wp-phpunit/wp-phpunit';

require_once $_wp_tests_dir . '/includes/functions.php';

// Activate the plugin before WordPress finishes loading.
tests_add_filter('muplugins_loaded', static function (): void {
    require __DIR__ . '/simple-jwt-login/simple-jwt-login.php';
});

require_once $_wp_tests_dir . '/includes/bootstrap.php';

// Load the project's own autoloader so test namespaces resolve.
require_once __DIR__ . '/vendor/autoload.php';
