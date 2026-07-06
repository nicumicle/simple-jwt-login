<?php

if (! defined('ABSPATH')) {
    /** @phpstan-ignore-next-line  */
    exit;
} // Exit if accessed directly

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;

## Force Login
add_filter(
    'rest_authentication_errors',
    function ($bypass) {
        $jwtSettings = new SimpleJWTLoginSettings(WordPressRepository::getInstance());
        if (!$jwtSettings->getIntegrationsSettings()->forceLogin()->isEnabled()) {
            return $bypass;
        }

        $host       = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $requestUri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        if (empty($host) || empty($requestUri)) {
            return $bypass;
        }

        $currentURL =
            "http"
            . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "s" : "")
            . "://" . $host
            . $requestUri;

        $simpleJWTLoginURLS = array(
            "%s/?rest_route=/%s",
            "%s/wp-json/%s",
        );
        foreach ($simpleJWTLoginURLS as $url) {
            $check = sprintf(
                $url,
                $jwtSettings->getWordPressData()->getSiteUrl(),
                $jwtSettings->getGeneralSettings()->getRouteNamespace()
            );
            if (strpos($currentURL, $check) === 0) {
                return true;
            }
        }

        return $bypass;
    },
    100
);
