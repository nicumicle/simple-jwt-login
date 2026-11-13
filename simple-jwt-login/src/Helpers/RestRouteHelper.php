<?php

namespace SimpleJWTLogin\Helpers;

class RestRouteHelper
{
    /**
     * Checks if the REST route resolved by WordPress (the `rest_route` query var)
     * belongs to the given namespace.
     *
     * Never decide this from the raw REQUEST_URI: a duplicated `rest_route` key
     * makes the raw URI and the dispatched route disagree.
     *
     * @param mixed $restRoute
     * @param string $namespace
     * @return bool
     */
    public static function isNamespaceRoute($restRoute, $namespace)
    {
        $namespace = strtolower(trim((string) $namespace, '/'));
        if (!is_string($restRoute) || $restRoute === '' || $namespace === '') {
            return false;
        }

        $route = '/' . ltrim(strtolower($restRoute), '/');

        return $route === '/' . $namespace
            || strpos($route, '/' . $namespace . '/') === 0;
    }
}
