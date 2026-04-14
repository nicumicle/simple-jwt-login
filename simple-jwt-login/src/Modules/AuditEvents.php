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
    const SETTINGS_SAVE_SUCCESS       = 'settings.save.success';

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
            self::SETTINGS_SAVE_SUCCESS,
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
            self::SETTINGS_SAVE_SUCCESS       => __('Settings Saved', 'simple-jwt-login'),
        ];
    }
}
