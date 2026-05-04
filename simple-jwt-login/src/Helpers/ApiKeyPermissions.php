<?php

namespace SimpleJWTLogin\Helpers;

class ApiKeyPermissions
{
    const READ   = 'read';
    const CREATE = 'create';
    const UPDATE = 'update';
    const DELETE = 'delete';

    const ALL = [
        self::READ,
        self::CREATE,
        self::UPDATE,
        self::DELETE,
    ];

    /**
     * Maps an HTTP method to the required WordPress endpoint permission.
     *
     * @param string $method
     * @return string|null
     */
    public static function httpMethodToPermission(string $method): ?string
    {
        $map = [
            'GET'    => self::READ,
            'POST'   => self::CREATE,
            'PUT'    => self::UPDATE,
            'PATCH'  => self::UPDATE,
            'DELETE' => self::DELETE,
        ];

        return $map[strtoupper($method)] ?? null;
    }

    public static function isValid(string $permission): bool
    {
        return in_array($permission, self::ALL, true);
    }
}
