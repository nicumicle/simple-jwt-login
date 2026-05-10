<?php

namespace SimpleJWTLogin\Helpers;

class ApiKeyPermissions
{
    const READ   = 'read';
    const CREATE = 'create';
    const UPDATE = 'update';
    const DELETE = 'delete';

    /**
     * @var string[]
     */
    public static $all = ['read', 'create', 'update', 'delete'];

    /**
     * Maps an HTTP method to the required WordPress endpoint permission.
     *
     * @param string $method
     * @return string|null
     */
    public static function httpMethodToPermission($method)
    {
        $map = [
            'GET'    => self::READ,
            'POST'   => self::CREATE,
            'PUT'    => self::UPDATE,
            'PATCH'  => self::UPDATE,
            'DELETE' => self::DELETE,
        ];
        $upper = strtoupper($method);

        return isset($map[$upper]) ? $map[$upper] : null;
    }

    /**
     * @param string $permission
     * @return bool
     */
    public static function isValid($permission)
    {
        return in_array($permission, self::$all, true);
    }
}
