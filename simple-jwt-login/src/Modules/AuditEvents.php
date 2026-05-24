<?php

namespace SimpleJWTLogin\Modules;

class AuditEvents
{
    const AUTH_LOGIN_SUCCESS          = 'auth.login.success';
    const AUTH_LOGIN_FAILED           = 'auth.login.failed';
    const AUTH_LOGOUT_SUCCESS         = 'auth.logout.success';
    const AUTH_LOGOUT_FAILED          = 'auth.logout.failed';
    const AUTH_REGISTER_SUCCESS       = 'auth.register.success';
    const AUTH_REGISTER_FAILED        = 'auth.register.failed';
    const AUTH_PASSWORD_RESET_REQUEST = 'auth.password_reset.request';
    const AUTH_PASSWORD_RESET_SUCCESS = 'auth.password_reset.success';
    const AUTH_PASSWORD_RESET_FAILED  = 'auth.password_reset.failed';
    const AUTH_DELETE_USER_SUCCESS    = 'auth.delete_user.success';
    const AUTH_DELETE_USER_FAILED     = 'auth.delete_user.failed';
    const AUTH_LOGIN_SESSION_SUCCESS  = 'auth.login_session.success';
    const AUTH_LOGIN_SESSION_FAILED   = 'auth.login_session.failed';
    const AUTH_REFRESH_TOKEN_SUCCESS  = 'auth.refresh_token.success';
    const AUTH_REFRESH_TOKEN_FAILED   = 'auth.refresh_token.failed';
    const AUTH_OAUTH_SUCCESS          = 'auth.oauth.success';
    const AUTH_OAUTH_FAILED           = 'auth.oauth.failed';
    const AUTH_2FA_CHALLENGE_ISSUED   = 'auth.2fa.challenge_issued';
    const AUTH_2FA_VERIFY_SUCCESS     = 'auth.2fa.verify_success';
    const AUTH_2FA_VERIFY_FAILED      = 'auth.2fa.verify_failed';
    const SETTINGS_SAVE_SUCCESS       = 'settings.save.success';

    const API_KEY_CREATE_SUCCESS = 'api_key.create.success';
    const API_KEY_CREATE_FAILED  = 'api_key.create.failed';
    const API_KEY_UPDATE_SUCCESS = 'api_key.update.success';
    const API_KEY_UPDATE_FAILED  = 'api_key.update.failed';
    const API_KEY_REVOKE_SUCCESS = 'api_key.revoke.success';
    const API_KEY_REVOKE_FAILED  = 'api_key.revoke.failed';
    const API_KEY_DELETE_SUCCESS = 'api_key.delete.success';
    const API_KEY_DELETE_FAILED  = 'api_key.delete.failed';
    const API_KEY_USED           = 'api_key.used';

    /**
     * @return string[]
     */
    public static function all()
    {
        return [
            self::AUTH_LOGIN_SUCCESS,
            self::AUTH_LOGIN_FAILED,
            self::AUTH_LOGOUT_SUCCESS,
            self::AUTH_LOGOUT_FAILED,
            self::AUTH_REGISTER_SUCCESS,
            self::AUTH_REGISTER_FAILED,
            self::AUTH_PASSWORD_RESET_REQUEST,
            self::AUTH_PASSWORD_RESET_SUCCESS,
            self::AUTH_PASSWORD_RESET_FAILED,
            self::AUTH_DELETE_USER_SUCCESS,
            self::AUTH_DELETE_USER_FAILED,
            self::AUTH_LOGIN_SESSION_SUCCESS,
            self::AUTH_LOGIN_SESSION_FAILED,
            self::AUTH_REFRESH_TOKEN_SUCCESS,
            self::AUTH_REFRESH_TOKEN_FAILED,
            self::AUTH_OAUTH_SUCCESS,
            self::AUTH_OAUTH_FAILED,
            self::AUTH_2FA_CHALLENGE_ISSUED,
            self::AUTH_2FA_VERIFY_SUCCESS,
            self::AUTH_2FA_VERIFY_FAILED,
            self::SETTINGS_SAVE_SUCCESS,
            self::API_KEY_CREATE_SUCCESS,
            self::API_KEY_CREATE_FAILED,
            self::API_KEY_UPDATE_SUCCESS,
            self::API_KEY_UPDATE_FAILED,
            self::API_KEY_REVOKE_SUCCESS,
            self::API_KEY_REVOKE_FAILED,
            self::API_KEY_DELETE_SUCCESS,
            self::API_KEY_DELETE_FAILED,
            self::API_KEY_USED,
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function labels()
    {
        return [
            self::AUTH_LOGIN_SUCCESS          => __('Login Success', 'simple-jwt-login'),
            self::AUTH_LOGIN_FAILED           => __('Login Failed', 'simple-jwt-login'),
            self::AUTH_LOGOUT_SUCCESS         => __('Logout (Token Revoked)', 'simple-jwt-login'),
            self::AUTH_LOGOUT_FAILED          => __('Logout Failed', 'simple-jwt-login'),
            self::AUTH_REGISTER_SUCCESS       => __('Register Success', 'simple-jwt-login'),
            self::AUTH_REGISTER_FAILED        => __('Register Failed', 'simple-jwt-login'),
            self::AUTH_PASSWORD_RESET_REQUEST => __('Password Reset Request', 'simple-jwt-login'),
            self::AUTH_PASSWORD_RESET_SUCCESS => __('Password Reset Success', 'simple-jwt-login'),
            self::AUTH_PASSWORD_RESET_FAILED  => __('Password Reset Failed', 'simple-jwt-login'),
            self::AUTH_DELETE_USER_SUCCESS    => __('Delete User Success', 'simple-jwt-login'),
            self::AUTH_DELETE_USER_FAILED     => __('Delete User Failed', 'simple-jwt-login'),
            self::AUTH_LOGIN_SESSION_SUCCESS  => __('Auto-Login Session Success', 'simple-jwt-login'),
            self::AUTH_LOGIN_SESSION_FAILED   => __('Auto-Login Session Failed', 'simple-jwt-login'),
            self::AUTH_REFRESH_TOKEN_SUCCESS  => __('Refresh Token Success', 'simple-jwt-login'),
            self::AUTH_REFRESH_TOKEN_FAILED   => __('Refresh Token Failed', 'simple-jwt-login'),
            self::AUTH_OAUTH_SUCCESS          => __('OAuth Login Success', 'simple-jwt-login'),
            self::AUTH_OAUTH_FAILED           => __('OAuth Login Failed', 'simple-jwt-login'),
            self::AUTH_2FA_CHALLENGE_ISSUED   => __('2FA Challenge Issued', 'simple-jwt-login'),
            self::AUTH_2FA_VERIFY_SUCCESS     => __('2FA Verification Success', 'simple-jwt-login'),
            self::AUTH_2FA_VERIFY_FAILED      => __('2FA Verification Failed', 'simple-jwt-login'),
            self::SETTINGS_SAVE_SUCCESS       => __('Settings Saved', 'simple-jwt-login'),
            self::API_KEY_CREATE_SUCCESS      => __('API Key Created', 'simple-jwt-login'),
            self::API_KEY_CREATE_FAILED       => __('API Key Create Failed', 'simple-jwt-login'),
            self::API_KEY_UPDATE_SUCCESS      => __('API Key Updated', 'simple-jwt-login'),
            self::API_KEY_UPDATE_FAILED       => __('API Key Update Failed', 'simple-jwt-login'),
            self::API_KEY_REVOKE_SUCCESS      => __('API Key Revoked', 'simple-jwt-login'),
            self::API_KEY_REVOKE_FAILED       => __('API Key Revoke Failed', 'simple-jwt-login'),
            self::API_KEY_DELETE_SUCCESS      => __('API Key Deleted', 'simple-jwt-login'),
            self::API_KEY_DELETE_FAILED       => __('API Key Delete Failed', 'simple-jwt-login'),
            self::API_KEY_USED                => __('API Key Used', 'simple-jwt-login'),
        ];
    }
}
