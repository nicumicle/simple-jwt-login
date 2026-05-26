<?php

use SimpleJWTLogin\Routes\RouteRegistrar;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

require_once(ABSPATH . 'wp-admin/includes/user.php');

add_action('rest_api_init', function () {
    $registrar = new RouteRegistrar($_SERVER, $_REQUEST, $_COOKIE);
    $registrar->register();
});
