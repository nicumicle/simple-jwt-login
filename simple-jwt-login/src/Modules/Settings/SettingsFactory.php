<?php

namespace SimpleJWTLogin\Modules\Settings;

use Exception;

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

    /**
     * @param int $type
     *
     * @return AuthCodesSettings|AuthenticationSettings|CorsSettings|DeleteUserSettings|GeneralSettings|HooksSettings|LoginSettings|RegisterSettings|ResetPasswordSettings|ProtectEndpointSettings
     * @throws Exception
     */
    public static function getFactory($type)
    {
        switch ($type) {
            case self::AUTH_CODES_SETTINGS:
                return new AuthCodesSettings();
            case self::AUTHENTICATION_SETTINGS:
                return new AuthenticationSettings();
            case self::CORS_SETTINGS:
                return new CorsSettings();
            case self::DELETE_USER_SETTINGS:
                return new DeleteUserSettings();
            case self::GENERAL_SETTINGS:
                return new GeneralSettings();
            case self::HOOKS_SETTINGS:
                return new HooksSettings();
            case self::LOGIN_SETTINGS:
                return new LoginSettings();
            case self::REGISTER_SETTINGS:
                return new RegisterSettings();
            case self::RESET_PASSWORD_SETTINGS:
                return new ResetPasswordSettings();
            case self::PROTECT_ENDPOINTS_SETTINGS:
                return new ProtectEndpointSettings();
            default:
                throw new Exception('Settings implementation not found.');
        }
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return [
            self::AUTHENTICATION_SETTINGS => new AuthenticationSettings(),
            self::CORS_SETTINGS => new CorsSettings(),
            self::DELETE_USER_SETTINGS => new DeleteUserSettings(),
            self::GENERAL_SETTINGS => new GeneralSettings(),
            self::HOOKS_SETTINGS => new HooksSettings(),
            self::LOGIN_SETTINGS => new LoginSettings(),
            self::REGISTER_SETTINGS => new RegisterSettings(),
            self::RESET_PASSWORD_SETTINGS => new ResetPasswordSettings(),
            self::PROTECT_ENDPOINTS_SETTINGS => new ProtectEndpointSettings(),

            //auth codes needs to be the last one
            self::AUTH_CODES_SETTINGS => new AuthCodesSettings(),
        ];
    }
}
