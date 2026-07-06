<?php

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Helpers\StatusCodeHelper;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;
use SimpleJWTLogin\Routes\SessionService;
use SimpleJWTLogin\Services\Integrations\WooCommerce\WooCommerceBridge;
use SimpleJWTLogin\Services\RouteService;

/**
 * Build a RouteService wired to the current request for a given settings object.
 *
 * @param SimpleJWTLoginSettings $jwtSettings
 * @return RouteService
 */
function simpleJwtLoginWooCommerceRouteService($jwtSettings)
{
    $serverHelper = $jwtSettings->getGeneralSettings()->isTrustIpHeadersEnabled()
        ? ServerHelper::withTrustedProxyHeaders($_SERVER)
        : new ServerHelper($_SERVER);

    $routeService = (new RouteService())
        ->withSettings($jwtSettings)
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ->withRequest($_REQUEST)
        ->withCookies($_COOKIE)
        ->withServerHelper($serverHelper);

    if ($jwtSettings->getGeneralSettings()->isJwtFromSessionEnabled()) {
        $routeService->withSession(SessionService::init());
    }

    return $routeService;
}

// Authenticate a JWT on WooCommerce REST routes (/wc/*), including the Store API
// cart & checkout, even when the global middleware is disabled. Runs on
// rest_authentication_errors at an early priority so the user is set before
// WooCommerce's own Store API authentication (nonce / cart-token) runs.
add_filter('rest_authentication_errors', function ($errors) {
    // Respect a result already produced by another authentication handler.
    if (!empty($errors)) {
        return $errors;
    }

    $wordPressRepo = WordPressRepository::getInstance();
    $jwtSettings   = new SimpleJWTLoginSettings($wordPressRepo);
    if (!$jwtSettings->getIntegrationsSettings()->woocommerce()->isEnabled()) {
        return $errors;
    }

    $serverHelper = $jwtSettings->getGeneralSettings()->isTrustIpHeadersEnabled()
        ? ServerHelper::withTrustedProxyHeaders($_SERVER)
        : new ServerHelper($_SERVER);
    if (!(new WooCommerceBridge())->isWooCommerceRequest($serverHelper->getCurrentURL())) {
        return $errors;
    }

    $routeService = simpleJwtLoginWooCommerceRouteService($jwtSettings);
    $jwt          = $routeService->getJwtFromRequestHeaderOrCookie();
    if (empty($jwt)) {
        return $errors;
    }

    try {
        $user = $routeService->getUserFromJwt($jwt);
    } catch (\Exception $exception) {
        return new \WP_Error(
            'simple-jwt-login-woocommerce',
            $exception->getMessage(),
            ['status' => StatusCodeHelper::getStatusCodeFromException($exception)]
        );
    }

    if (!is_user_logged_in()) {
        $wordPressRepo->loginUser($user, null);
    }

    return $errors;
}, 5);

// Skip the WooCommerce Store API nonce (CSRF) check when:
//  - the WooCommerce integration is enabled, and
//  - the admin opted in via the "Store API cart & checkout" toggle, and
//  - the request carries a Bearer JWT in the Authorization header.
// Header tokens are not auto-sent by browsers, so they are not subject to CSRF.
// This is self-contained (evaluates the request directly) so it does not depend
// on the order in which authentication filters run. Cookie/URL tokens always
// keep their nonce protection.
add_filter('woocommerce_store_api_disable_nonce_check', function ($disabled) {
    $jwtSettings = new SimpleJWTLoginSettings(WordPressRepository::getInstance());
    $wooSettings = $jwtSettings->getIntegrationsSettings()->woocommerce();
    if (!$wooSettings->isEnabled() || !$wooSettings->isStoreApiNonceDisabled()) {
        return $disabled;
    }

    if (!empty(simpleJwtLoginWooCommerceRouteService($jwtSettings)->getJwtFromHeader())) {
        return true;
    }

    return $disabled;
});
