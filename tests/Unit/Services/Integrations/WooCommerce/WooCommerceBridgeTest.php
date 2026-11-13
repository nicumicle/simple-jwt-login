<?php

namespace SimpleJwtLoginTests\Unit\Services\Integrations\WooCommerce;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Services\Integrations\WooCommerce\WooCommerceBridge;

class WooCommerceBridgeTest extends TestCase
{
    #[DataProvider('routeProvider')]
    public function testIsWooCommerceRoute(?string $restRoute, bool $expected): void
    {
        $this->assertSame($expected, (new WooCommerceBridge())->isWooCommerceRoute($restRoute));
    }

    /**
     * Routes as WordPress resolves them (the `rest_route` query var), for both
     * pretty (/wp-json/...) and plain (?rest_route=...) permalinks.
     */
    public static function routeProvider(): array
    {
        return [
            'wc v3 orders'          => ['/wc/v3/orders', true],
            'wc v3 products'        => ['/wc/v3/products', true],
            'wc v2 orders'          => ['/wc/v2/orders', true],
            'wc v1 orders'          => ['/wc/v1/orders', true],
            'store cart'            => ['/wc/store/v1/cart', true],
            'store checkout'        => ['/wc/store/v1/checkout', true],
            'wc namespace index'    => ['/wc', true],
            'wp core users'         => ['/wp/v2/users', false],
            'wc analytics'          => ['/wc-analytics/reports', false],
            'sjl namespace'         => ['/simple-jwt-login/v1/auth', false],
            'wc inside another route' => ['/wp/v2/users/wc/v3', false],
            'root'                  => ['/', false],
            'empty'                 => ['', false],
            'not a REST request'    => [null, false],
        ];
    }

    public function testIsAvailableReturnsFalseWhenWooCommerceMissing(): void
    {
        $this->assertFalse((new WooCommerceBridge())->isAvailable());
    }
}
