<?php

namespace SimpleJwtLoginTests\Unit\Services\Integrations\WooCommerce;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Services\Integrations\WooCommerce\WooCommerceBridge;

class WooCommerceBridgeTest extends TestCase
{
    #[DataProvider('requestProvider')]
    public function testIsWooCommerceRequest(string $url, bool $expected): void
    {
        $this->assertSame($expected, (new WooCommerceBridge())->isWooCommerceRequest($url));
    }

    public static function requestProvider(): array
    {
        return [
            'pretty wc v3 orders'   => ['https://site.test/wp-json/wc/v3/orders', true],
            'pretty wc v3 products' => ['https://site.test/wp-json/wc/v3/products', true],
            'pretty wc v2 orders'   => ['https://site.test/wp-json/wc/v2/orders', true],
            'pretty wc v1 orders'   => ['https://site.test/wp-json/wc/v1/orders', true],
            'pretty store cart'     => ['https://site.test/wp-json/wc/store/v1/cart', true],
            'pretty store checkout' => ['https://site.test/wp-json/wc/store/v1/checkout', true],
            'plain rest_route'      => ['https://site.test/?rest_route=/wc/v3/orders', true],
            'plain encoded'         => ['https://site.test/?rest_route=%2Fwc%2Fv3%2Forders', true],
            'wp core users'         => ['https://site.test/wp-json/wp/v2/users', false],
            'wc analytics'          => ['https://site.test/wp-json/wc-analytics/reports', false],
            'sjl namespace'         => ['https://site.test/wp-json/simple-jwt-login/v1/auth', false],
            'root'                  => ['https://site.test/', false],
            'empty'                 => ['', false],
        ];
    }

    public function testIsAvailableReturnsFalseWhenWooCommerceMissing(): void
    {
        $this->assertFalse((new WooCommerceBridge())->isAvailable());
    }
}
