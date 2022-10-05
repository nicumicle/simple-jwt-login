<?php

use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressData;

## Force Login
add_filter(
    'rest_authentication_errors',
    function ($bypass) {
        $currentURL  =
            "http"
            . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "s" : "")
            . "://" . esc_html($_SERVER['HTTP_HOST'])
            . esc_html($_SERVER['REQUEST_URI']);

        $jwtSettings = new SimpleJWTLoginSettings(new WordPressData());

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
