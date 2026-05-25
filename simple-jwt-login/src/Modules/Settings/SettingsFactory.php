<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SettingsFactory
{
    const AUTH_CODES_SETTINGS = 0;
    const AUTHENTICATION_SETTINGS = 1;
    const CORS_SETTINGS = 2;
    const DELETE_USER_SETTINGS = 3;
    const GENERAL_SETTINGS = 4;
    const HOOKS_SETTINGS = 5;
    const LOGIN_SETTINGS = 6;
    const REGISTER_SETTINGS = 7;
    const RESET_PASSWORD_SETTINGS = 8;
    const PROTECT_ENDPOINTS_SETTINGS = 9;
    const INTEGRATIONS_SETTINGS = 10;
    const AUDIT_LOG_SETTINGS = 11;
    const JWT_RULES_SETTINGS = 12;
    const WEBHOOKS_SETTINGS  = 13;
    const API_KEYS_SETTINGS  = 14;

    /**
     * @return array
     */
    protected static function getClassMap()
    {
        return array(
            self::AUTH_CODES_SETTINGS       => 'SimpleJWTLogin\Modules\Settings\AuthCodesSettings',
            self::AUTHENTICATION_SETTINGS   => 'SimpleJWTLogin\Modules\Settings\AuthenticationSettings',
            self::AUDIT_LOG_SETTINGS        => 'SimpleJWTLogin\Modules\Settings\AuditLogSettings',
            self::CORS_SETTINGS             => 'SimpleJWTLogin\Modules\Settings\CorsSettings',
            self::DELETE_USER_SETTINGS      => 'SimpleJWTLogin\Modules\Settings\DeleteUserSettings',
            self::GENERAL_SETTINGS          => 'SimpleJWTLogin\Modules\Settings\GeneralSettings',
            self::HOOKS_SETTINGS            => 'SimpleJWTLogin\Modules\Settings\HooksSettings',
            self::JWT_RULES_SETTINGS        => 'SimpleJWTLogin\Modules\Settings\JwtRulesSettings',
            self::LOGIN_SETTINGS            => 'SimpleJWTLogin\Modules\Settings\LoginSettings',
            self::REGISTER_SETTINGS         => 'SimpleJWTLogin\Modules\Settings\RegisterSettings',
            self::RESET_PASSWORD_SETTINGS   => 'SimpleJWTLogin\Modules\Settings\ResetPasswordSettings',
            self::PROTECT_ENDPOINTS_SETTINGS => 'SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings',
            self::INTEGRATIONS_SETTINGS     => 'SimpleJWTLogin\Modules\Settings\IntegrationsSettings',
            self::WEBHOOKS_SETTINGS         => 'SimpleJWTLogin\Modules\Settings\WebhooksSettings',
            self::API_KEYS_SETTINGS         => 'SimpleJWTLogin\Modules\Settings\ApiKeysSettings',
        );
    }

    /**
     * @param int $type
     *
     * @return AuthCodesSettings|AuthenticationSettings|AuditLogSettings|CorsSettings|DeleteUserSettings|GeneralSettings|HooksSettings|JwtRulesSettings|LoginSettings|RegisterSettings|ResetPasswordSettings|ProtectEndpointSettings|IntegrationsSettings|WebhooksSettings|ApiKeysSettings
     * @throws Exception
     */
    public static function getFactory($type)
    {
        $classMap = static::getClassMap();
        if (!isset($classMap[$type])) {
            throw new Exception(__('Settings implementation not found.', 'simple-jwt-login'));
        }
        $className = $classMap[$type];

        return new $className();
    }

    /**
     * @return array
     */
    public function getAll()
    {
        $classMap = static::getClassMap();
        $result = array();

        foreach ($classMap as $type => $className) {
            if ($type === self::AUTH_CODES_SETTINGS) {
                continue;
            }
            $result[$type] = new $className();
        }

        // auth codes must be last - validation depends on all other settings being loaded first
        $authCodesClass = $classMap[self::AUTH_CODES_SETTINGS];
        $result[self::AUTH_CODES_SETTINGS] = new $authCodesClass();

        return $result;
    }
}
