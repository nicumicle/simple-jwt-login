<?php

/**
 * Plugin Name: Simple JWT Login - test Force Login
 * Description: Feature test fixture copied to mu-plugins by ForceLoginDuplicatedRestRouteTest. Blocks the
 *              REST API for logged-out users like the Force Login plugin (v_forcelogin_rest_access).
 *              Inert unless the request carries the X-Sjl-Test-Force-Login header.
 */

add_filter('rest_authentication_errors', function ($result) {
    if (empty($_SERVER['HTTP_X_SJL_TEST_FORCE_LOGIN'])) {
        return $result;
    }
    if (null === $result && !is_user_logged_in()) {
        return new WP_Error(
            'rest_unauthorized',
            'Only authenticated users can access the REST API.',
            ['status' => rest_authorization_required_code()]
        );
    }

    return $result;
}, 99);
