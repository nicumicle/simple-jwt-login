<?php

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;

## Force Login
add_filter(
    'rest_authentication_errors',
    function ($bypass) {
        $currentURL  =
            "http"
            . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "s" : "")
            . "://" . sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST']))
            . sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));

        $jwtSettings = new SimpleJWTLoginSettings(new WordPressRepository());

        $simpleJwtLoginUrl = $jwtSettings->getWordPressData()->getSiteUrl()
               . '/?rest_route=/'
               . $jwtSettings->getGeneralSettings()->getRouteNamespace();
        if (strpos($currentURL, $simpleJwtLoginUrl) === 0) {
            return true;
        }

        return $bypass;
    },
    100
);
