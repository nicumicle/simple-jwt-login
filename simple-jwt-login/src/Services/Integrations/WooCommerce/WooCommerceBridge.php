<?php

namespace SimpleJWTLogin\Services\Integrations\WooCommerce;

use SimpleJWTLogin\Helpers\RestRouteHelper;

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
     * True when the REST route resolved by WordPress is a WooCommerce route.
     * Covers classic CRUD (/wc/v3, /wc/v2, /wc/v1) and the Store API
     * (/wc/store/v1). WordPress sets the route for both pretty (/wp-json/wc/...)
     * and plain (?rest_route=/wc/...) permalinks.
     *
     * Never decide this from the raw URL: a duplicated `rest_route` key or a
     * "/wc/" string in the query makes the URL and the dispatched route disagree.
     *
     * @param string|null $restRoute Route from Repository::getResolvedRestRoute().
     * @return bool
     */
    public function isWooCommerceRoute($restRoute)
    {
        return RestRouteHelper::isNamespaceRoute($restRoute, 'wc');
    }
}
