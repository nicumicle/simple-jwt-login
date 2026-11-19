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
    /**
     * Permissions an API key needs for a REST call. A /batch/v1 call can hold POST, PUT,
     * PATCH and DELETE sub-requests, so it needs every write permission.
     *
     * @param string $method Effective HTTP method.
     * @param string|null $restRoute Route WordPress dispatches.
     * @return string[] Empty when the method is not allowed for API keys.
     */
    public static function requiredForRequest($method, $restRoute)
    {
        if (RestRouteHelper::isNamespaceRoute($restRoute, 'batch/v1')) {
            return [self::CREATE, self::UPDATE, self::DELETE];
        }
        $permission = self::httpMethodToPermission($method);

        return $permission === null ? [] : [$permission];
    }

    public static function isValid($permission)
    {
        return in_array($permission, self::$all, true);
    }
}
