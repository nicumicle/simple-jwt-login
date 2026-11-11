<?php

namespace SimpleJWTLogin\Modules\Settings\ThirdParty;

use SimpleJWTLogin\Modules\Settings\BaseSettings;

/**
 * What AI agents may do through the WordPress Abilities API (and MCP).
 * Stored under integrations.3rdparty.ai_abilities; never writable by an AI agent.
 */
class AiAbilitiesSettings extends AbstractThirdPartySettings
{
    const ACCESS_OFF        = 'off';
    const ACCESS_READ       = 'read';
    const ACCESS_WRITE      = 'write';
    const ACCESS_READ_WRITE = 'read_write';

    /**
     * Access levels selectable once the integration is enabled.
     *
     * @var string[]
     */
    public static $accessLevels = array('read', 'write', 'read_write');

    /**
     * Opt-in permissions for the riskiest actions. All are denied unless an
     * administrator turns them on.
     */
    const PERMISSION_VIEW_SECRETS          = 'allow_view_secrets';
    const PERMISSION_CHANGE_ROLES          = 'allow_change_roles';
    const PERMISSION_CHANGE_AUTH_PROVIDERS = 'allow_change_auth_providers';
    const PERMISSION_CREATE_API_KEYS       = 'allow_create_api_keys';
    const PERMISSION_ADD_WEBHOOKS          = 'allow_add_webhooks';

    /**
     * @var string[]
     */
    public static $permissions = array(
        'allow_view_secrets',
        'allow_change_roles',
        'allow_change_auth_providers',
        'allow_create_api_keys',
        'allow_add_webhooks',
    );

    public function getGroup()
    {
        return 'ai_abilities';
    }

    protected function getExtraFields()
    {
        $fields = [
            ['access', BaseSettings::SETTINGS_TYPE_STRING],
        ];
        foreach (self::$permissions as $permission) {
            $fields[] = [$permission, BaseSettings::SETTINGS_TYPE_BOL];
        }

        return $fields;
    }

    /**
     * Admin-facing name of a permission (also used in errors shown to AI agents,
     * so they can tell the administrator which option to enable).
     *
     * @param string $permission
     * @return string
     */
    public static function getPermissionLabel($permission)
    {
        $labels = [
            self::PERMISSION_VIEW_SECRETS          => __('Allow viewing secrets', 'simple-jwt-login'),
            self::PERMISSION_CHANGE_ROLES          => __('Allow changing roles', 'simple-jwt-login'),
            self::PERMISSION_CHANGE_AUTH_PROVIDERS => __('Allow changing authentication providers', 'simple-jwt-login'),
            self::PERMISSION_CREATE_API_KEYS       => __('Allow creating API keys', 'simple-jwt-login'),
            self::PERMISSION_ADD_WEBHOOKS          => __('Allow adding webhooks', 'simple-jwt-login'),
        ];

        return isset($labels[$permission]) ? $labels[$permission] : (string) $permission;
    }

    /**
     * @param string $permission One of the PERMISSION_* constants.
     * @return bool False for unknown permissions.
     */
    public function isPermissionGranted($permission)
    {
        return in_array($permission, self::$permissions, true) && $this->isFieldEnabled($permission);
    }

    /**
     * @return array<string, bool> Permission => granted.
     */
    public function getPermissions()
    {
        $result = [];
        foreach (self::$permissions as $permission) {
            $result[$permission] = $this->isPermissionGranted($permission);
        }

        return $result;
    }

    /**
     * Effective access: "off" while the integration is disabled or the stored
     * level is unknown.
     *
     * @return string One of the ACCESS_* constants.
     */
    public function getAccess()
    {
        if (!$this->isEnabled()) {
            return self::ACCESS_OFF;
        }
        $access = $this->get('access', self::ACCESS_READ);

        return in_array($access, self::$accessLevels, true) ? $access : self::ACCESS_OFF;
    }

    /**
     * The level selected in the admin form, even while disabled.
     *
     * @return string
     */
    public function getSelectedAccess()
    {
        $access = $this->get('access', self::ACCESS_READ);

        return in_array($access, self::$accessLevels, true) ? $access : self::ACCESS_READ;
    }

    /**
     * @return bool
     */
    public function isReadAllowed()
    {
        return in_array($this->getAccess(), [self::ACCESS_READ, self::ACCESS_READ_WRITE], true);
    }

    /**
     * @return bool
     */
    public function isWriteAllowed()
    {
        return in_array($this->getAccess(), [self::ACCESS_WRITE, self::ACCESS_READ_WRITE], true);
    }
}
