<?php

use SimpleJWTLogin\Helpers\RestRouteHelper;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressData;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

## Force Login
## Lets anonymous requests reach the plugin's own endpoints when the Force Login
## plugin blocks the REST API for logged-out users (error code `rest_unauthorized`).
## Security: never return `true` here, never touch errors for logged-in users
## (e.g. core's `rest_cookie_invalid_nonce`) and never clear errors raised by other
## plugins, otherwise cookie-authenticated requests skip WordPress' nonce check (CSRF).
add_filter(
    'rest_authentication_errors',
    function ($result) {
        if (!is_wp_error($result)
            || $result->get_error_code() !== 'rest_unauthorized'
            || is_user_logged_in()
        ) {
            return $result;
        }

        $restRoute = isset($GLOBALS['wp']->query_vars['rest_route'])
            ? $GLOBALS['wp']->query_vars['rest_route']
            : null;

        $jwtSettings = new SimpleJWTLoginSettings(new WordPressData());
        $namespace   = $jwtSettings->getGeneralSettings()->getRouteNamespace();
        if (RestRouteHelper::isNamespaceRoute($restRoute, $namespace)) {
            // No authentication decision: the route's permission_callback applies.
            return null;
        }

        return $result;
    },
    100
);
