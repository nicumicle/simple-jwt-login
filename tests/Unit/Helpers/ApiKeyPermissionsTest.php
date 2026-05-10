<?php

namespace SimpleJwtLoginTests\Unit\Helpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ApiKeyPermissions;

class ApiKeyPermissionsTest extends TestCase
{
    public function testAllConstantHasExactlyFourItems()
    {
        $this->assertCount(4, ApiKeyPermissions::$all);
    }

    #[DataProvider('validPermissionsProvider')]
    public function testIsValidReturnsTrueForEachValidPermission(string $permission)
    {
        $this->assertTrue(ApiKeyPermissions::isValid($permission));
    }

    public function testIsValidReturnsFalseForInvalidPermission()
    {
        $this->assertFalse(ApiKeyPermissions::isValid('invalid'));
    }

    public function testIsValidReturnsFalseForEmptyString()
    {
        $this->assertFalse(ApiKeyPermissions::isValid(''));
    }

    #[DataProvider('httpMethodToPermissionProvider')]
    public function testHttpMethodToPermission(string $method, ?string $expected)
    {
        $this->assertSame($expected, ApiKeyPermissions::httpMethodToPermission($method));
    }

    public static function validPermissionsProvider(): array
    {
        return array_map(
            fn($perm) => [$perm],
            ApiKeyPermissions::$all
        );
    }

    public static function httpMethodToPermissionProvider(): array
    {
        return [
            'GET'        => ['GET',    'read'],
            'POST'       => ['POST',   'create'],
            'PUT'        => ['PUT',    'update'],
            'PATCH'      => ['PATCH',  'update'],
            'DELETE'     => ['DELETE', 'delete'],
            'lowercase'  => ['get',    'read'],
            'HEAD'       => ['HEAD',   null],
            'unknown'    => ['FOOBAR', null],
        ];
    }
}
