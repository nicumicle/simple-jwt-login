<?php

namespace SimpleJWTLogin\Services\Integrations\WooCommerce;

class WooCommerceBridge
{
    /**
     * Detect if the WooCommerce plugin is installed & active.
     *
     * @return bool
     */
    public function isAvailable()
    {
        return class_exists('WooCommerce');
    }

    /**
     * True when the request URL targets a WooCommerce REST route.
     * Covers classic CRUD (/wc/v3, /wc/v2, /wc/v1) and the Store API
     * (/wc/store/v1), for both pretty (/wp-json/wc/...) and plain
     * (?rest_route=/wc/...) permalinks.
     *
     * @param string $url Full request URL or REQUEST_URI.
     * @return bool
     */
    public function isWooCommerceRequest($url)
    {
        if (!is_string($url) || $url === '') {
            return false;
        }

        $needles = array('/wp-json/wc/', 'rest_route=/wc/', 'rest_route=%2Fwc%2F');
        foreach ($needles as $needle) {
            if (strpos($url, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
