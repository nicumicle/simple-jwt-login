<?php

use SimpleJWTLogin\Helpers\RestRouteHelper;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

/** @var \SimpleJWTLogin\Modules\SimpleJWTLoginSettings $simpleJwtLoginJwtSettings Built in simple-jwt-login.php */

## Force Login
## Lets anonymous requests reach the plugin's own endpoints when the Force Login
## plugin blocks the REST API for logged-out users (error code `rest_unauthorized`).
## Security: never return `true` here, never touch errors for logged-in users
## (e.g. core's `rest_cookie_invalid_nonce`) and never clear errors raised by other
## plugins, otherwise cookie-authenticated requests skip WordPress' nonce check (CSRF).
add_filter(
    'rest_authentication_errors',
    function ($result) use ($simpleJwtLoginJwtSettings) {
        $jwtSettings = $simpleJwtLoginJwtSettings;
        if (!$jwtSettings->getIntegrationsSettings()->forceLogin()->isEnabled()
            || !is_wp_error($result)
            || $result->get_error_code() !== 'rest_unauthorized'
        ) {
            return $result;
        }

        $wordPressRepo = WordPressRepository::getInstance();
        if ($wordPressRepo->isUserLoggedIn()) {
            return $result;
        }

        if (RestRouteHelper::isNamespaceRoute(
            $wordPressRepo->getResolvedRestRoute(),
            $jwtSettings->getGeneralSettings()->getRouteNamespace()
        )) {
            // No authentication decision: the route's permission_callback applies.
            return null;
        }

        return $result;
    },
    100
);
