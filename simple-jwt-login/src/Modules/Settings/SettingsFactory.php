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
     * @param int $type
     *
     * @return AuthCodesSettings|AuthenticationSettings|AuditLogSettings|CorsSettings|DeleteUserSettings|GeneralSettings|HooksSettings|JwtRulesSettings|LoginSettings|RegisterSettings|ResetPasswordSettings|ProtectEndpointSettings|IntegrationsSettings|WebhooksSettings|ApiKeysSettings
     * @throws Exception
     */
    public static function getFactory($type)
    {
        switch ($type) {
            case self::AUTH_CODES_SETTINGS:
                return new AuthCodesSettings();
            case self::AUTHENTICATION_SETTINGS:
                return new AuthenticationSettings();
            case self::AUDIT_LOG_SETTINGS:
                return new AuditLogSettings();
            case self::CORS_SETTINGS:
                return new CorsSettings();
            case self::DELETE_USER_SETTINGS:
                return new DeleteUserSettings();
            case self::GENERAL_SETTINGS:
                return new GeneralSettings();
            case self::HOOKS_SETTINGS:
                return new HooksSettings();
            case self::JWT_RULES_SETTINGS:
                return new JwtRulesSettings();
            case self::LOGIN_SETTINGS:
                return new LoginSettings();
            case self::REGISTER_SETTINGS:
                return new RegisterSettings();
            case self::RESET_PASSWORD_SETTINGS:
                return new ResetPasswordSettings();
            case self::PROTECT_ENDPOINTS_SETTINGS:
                return new ProtectEndpointSettings();
            case self::INTEGRATIONS_SETTINGS:
                return new IntegrationsSettings();
            case self::WEBHOOKS_SETTINGS:
                return new WebhooksSettings();
            case self::API_KEYS_SETTINGS:
                return new ApiKeysSettings();
            default:
                throw new Exception(__('Settings implementation not found.', 'simple-jwt-login'));
        }
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return [
            self::AUTHENTICATION_SETTINGS => new AuthenticationSettings(),
            self::AUDIT_LOG_SETTINGS => new AuditLogSettings(),
            self::CORS_SETTINGS => new CorsSettings(),
            self::DELETE_USER_SETTINGS => new DeleteUserSettings(),
            self::GENERAL_SETTINGS => new GeneralSettings(),
            self::HOOKS_SETTINGS => new HooksSettings(),
            self::JWT_RULES_SETTINGS => new JwtRulesSettings(),
            self::LOGIN_SETTINGS => new LoginSettings(),
            self::REGISTER_SETTINGS => new RegisterSettings(),
            self::RESET_PASSWORD_SETTINGS => new ResetPasswordSettings(),
            self::PROTECT_ENDPOINTS_SETTINGS => new ProtectEndpointSettings(),
            self::INTEGRATIONS_SETTINGS => new IntegrationsSettings(),
            self::WEBHOOKS_SETTINGS => new WebhooksSettings(),
            self::API_KEYS_SETTINGS => new ApiKeysSettings(),

            // auth codes must be last - validation depends on all other settings being loaded first
            self::AUTH_CODES_SETTINGS => new AuthCodesSettings(),
        ];
    }
}
