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
     * Maps a permission to the minimum WordPress capability required to mint a
     * key with that permission. Admins (manage_options) always bypass this.
     *
     * @param string $permission
     * @return string|null  null when the permission is unknown
     */
    public static function permissionToCapability($permission)
    {
        $map = [
            self::READ   => 'read',
            self::CREATE => 'edit_posts',
            self::UPDATE => 'edit_posts',
            self::DELETE => 'delete_posts',
        ];

        return isset($map[$permission]) ? $map[$permission] : null;
    }

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
