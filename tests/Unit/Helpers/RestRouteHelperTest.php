<?php

namespace SimpleJwtLoginTests\Unit\Helpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\RestRouteHelper;

class RestRouteHelperTest extends TestCase
{
    /**
     * @return array<string, array{mixed, string, bool}>
     */
    public static function routeProvider()
    {
        return [
            'namespace root' => ['/simple-jwt-login/v1', 'simple-jwt-login/v1/', true],
            'namespace root with slash' => ['/simple-jwt-login/v1/', 'simple-jwt-login/v1/', true],
            'plugin endpoint' => ['/simple-jwt-login/v1/auth', 'simple-jwt-login/v1/', true],
            'route without leading slash' => ['simple-jwt-login/v1/auth', 'simple-jwt-login/v1/', true],
            'different case' => ['/Simple-JWT-Login/V1/auth', 'simple-jwt-login/v1/', true],
            'custom namespace' => ['/my-jwt/v2/users', '/my-jwt/v2', true],
            'core users route' => ['/wp/v2/users', 'simple-jwt-login/v1/', false],
            'namespace in a query-like suffix' => ['/wp/v2/users/simple-jwt-login/v1/', 'simple-jwt-login/v1/', false],
            'namespace as prefix of another namespace' => ['/simple-jwt-login/v10/auth', 'simple-jwt-login/v1/', false],
            'namespace without separator' => ['/simple-jwt-login/v1auth', 'simple-jwt-login/v1/', false],
            'empty route' => ['', 'simple-jwt-login/v1/', false],
            'null route' => [null, 'simple-jwt-login/v1/', false],
            'array route' => [['/simple-jwt-login/v1/auth'], 'simple-jwt-login/v1/', false],
            'empty namespace' => ['/wp/v2/users', '/', false],
        ];
    }

    #[DataProvider('routeProvider')]
    public function testIsNamespaceRoute($restRoute, $namespace, $expected)
    {
        $this->assertSame($expected, RestRouteHelper::isNamespaceRoute($restRoute, $namespace));
    }
}
