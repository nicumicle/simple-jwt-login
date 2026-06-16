<?php

namespace SimpleJWTLogin\Modules;

class SimpleJWTLoginHooks
{
    const LOGIN_ACTION_NAME = 'simple_jwt_login_login_hook';
    const LOGIN_REDIRECT_NAME = 'simple_jwt_login_redirect_hook';
    const REGISTER_ACTION_NAME = 'simple_jwt_login_register_hook';
    const DELETE_USER_ACTION_NAME = 'simple_jwt_login_delete_user_hook';
    const JWT_PAYLOAD_ACTION_NAME  = 'simple_jwt_login_jwt_payload_auth';
    const NO_REDIRECT_RESPONSE = 'simple_jwt_login_no_redirect_message';
    const RESET_PASSWORD_CUSTOM_EMAIL_TEMPLATE = 'simple_jwt_login_reset_password_custom_email_template';

    const HOOK_TYPE_ACTION = 'action';
    const HOOK_TYPE_FILTER = 'filter';

    const HOOK_RESPONSE_AUTH_USER = 'simple_jwt_login_response_auth_user';
    const HOOK_RESPONSE_DELETE_USER = 'simple_jwt_login_response_delete_user';
    const HOOK_RESPONSE_REFRESH_TOKEN = 'simple_jwt_login_response_refresh_token';
    const HOOK_RESPONSE_REGISTER_USER = 'simple_jwt_login_response_register_user';
    const HOOK_RESPONSE_SEND_RESET_PASSWORD = 'simple_jwt_login_response_send_reset_password';
    const HOOK_RESPONSE_CHANGE_USER_PASSWORD = 'simple_jwt_login_response_change_user_password';
    const HOOK_RESPONSE_REVOKE_TOKEN = 'simple_jwt_login_response_revoke_token';
    const HOOK_RESPONSE_VALIDATE_TOKEN = 'simple_jwt_login_response_validate_token';
    const HOOK_GENERATE_PAYLOAD = 'simple_jwt_login_generate_payload';
    const HOOK_BEFORE_ENDPOINT = 'simple_jwt_login_before_endpoint';

    const AUDIT_AUTH_LOGIN_SUCCESS          = 'simple_jwt_login_audit_auth_login_success';
    const AUDIT_AUTH_LOGIN_FAILED           = 'simple_jwt_login_audit_auth_login_failed';
    const AUDIT_AUTH_LOGOUT_SUCCESS         = 'simple_jwt_login_audit_auth_logout_success';
    const AUDIT_AUTH_LOGOUT_FAILED          = 'simple_jwt_login_audit_auth_logout_failed';
    const AUDIT_AUTH_REGISTER_SUCCESS       = 'simple_jwt_login_audit_auth_register_success';
    const AUDIT_AUTH_REGISTER_FAILED        = 'simple_jwt_login_audit_auth_register_failed';
    const AUDIT_AUTH_PASSWORD_RESET_REQUEST = 'simple_jwt_login_audit_password_reset_request';
    const AUDIT_AUTH_PASSWORD_RESET_SUCCESS = 'simple_jwt_login_audit_password_reset_success';
    const AUDIT_AUTH_PASSWORD_RESET_FAILED  = 'simple_jwt_login_audit_password_reset_failed';
    const AUDIT_AUTH_DELETE_USER_SUCCESS    = 'simple_jwt_login_audit_delete_user_success';
    const AUDIT_AUTH_DELETE_USER_FAILED     = 'simple_jwt_login_audit_delete_user_failed';
    const AUDIT_AUTH_LOGIN_SESSION_SUCCESS  = 'simple_jwt_login_audit_login_session_success';
    const AUDIT_AUTH_LOGIN_SESSION_FAILED   = 'simple_jwt_login_audit_login_session_failed';
    const AUDIT_AUTH_REFRESH_TOKEN_SUCCESS  = 'simple_jwt_login_audit_refresh_token_success';
    const AUDIT_AUTH_REFRESH_TOKEN_FAILED   = 'simple_jwt_login_audit_refresh_token_failed';
    const AUDIT_AUTH_OAUTH_SUCCESS          = 'simple_jwt_login_audit_oauth_success';
    const AUDIT_AUTH_OAUTH_FAILED           = 'simple_jwt_login_audit_oauth_failed';

    const AUDIT_2FA_CHALLENGE_ISSUED  = 'simple_jwt_login_audit_2fa_challenge_issued';
    const AUDIT_2FA_VERIFY_SUCCESS    = 'simple_jwt_login_audit_2fa_verify_success';
    const AUDIT_2FA_VERIFY_FAILED     = 'simple_jwt_login_audit_2fa_verify_failed';
    const HOOK_RESPONSE_2FA_CHALLENGE = 'simple_jwt_login_response_2fa_challenge';
    const HOOK_RESPONSE_2FA_VERIFY    = 'simple_jwt_login_response_2fa_verify';

    /**
     * @return array[]
     */
    public static function getHooksDetails()
    {
        return [
            [
                'name' => self::LOGIN_ACTION_NAME,
                'type' => self::HOOK_TYPE_ACTION,
                'parameters' => [
                    'Wp_User $user'
                ],
                'description' => __('Triggered after a successful user login via JWT.', 'simple-jwt-login'),
            ],
            [
                'name' => self::LOGIN_REDIRECT_NAME,
                'type' => self::HOOK_TYPE_ACTION,
                'parameters' => [
                    'string $url',
                    'array $request'
                ],
                'description' =>
                    __(
                        'Called before redirecting the user to their specified login destination.',
                        'simple-jwt-login'
                    ),
            ],
            [
                'name' => self::REGISTER_ACTION_NAME,
                'type' => self::HOOK_TYPE_ACTION,
                'parameters' => [
                    'Wp_User $user',
                    'string $password'
                ],
                'description' => __(
                    'Triggered after a new user account is successfully created.',
                    'simple-jwt-login'
                ),
            ],
            [
                'name' => self::DELETE_USER_ACTION_NAME,
                'type' => self::HOOK_TYPE_ACTION,
                'parameters' => [
                    'Wp_User $user'
                ],
                'description' => __(
                    'Executed immediately after a user account is deleted.',
                    'simple-jwt-login'
                ),
            ],
            [
                'name' => self::JWT_PAYLOAD_ACTION_NAME,
                'type' => self::HOOK_TYPE_FILTER,
                'parameters' => [
                    'array $payload',
                    'array $request'
                ],
                'return' => 'array $payload',
                'description' => __(
                    'Allows modification of JWT payload parameters during authentication.',
                    'simple-jwt-login'
                ),
            ],
            [
                'name' => self::NO_REDIRECT_RESPONSE,
                'type' => self::HOOK_TYPE_FILTER,
                'parameters' => [
                    'array $response',
                    'array $request'
                ],
                'return' => 'array $response',
                'description' => __(
                    'Customizes the response for autologin when no redirect is configured.',
                    'simple-jwt-login'
                ),
            ],
            [
                'name' => self::RESET_PASSWORD_CUSTOM_EMAIL_TEMPLATE,
                'type' => self::HOOK_TYPE_FILTER,
                'parameters' => [
                    'string $template',
                    'array $request'
                ],
                'return' => 'string $template',
                'description' => __(
                    'Replaces the default reset password email template with a custom one.',
                    'simple-jwt-login'
                ),
            ],
            [
                'name' => self::HOOK_RESPONSE_AUTH_USER,
                'type' => self::HOOK_TYPE_FILTER,
                'parameters' => [
                    'array $response',
                    'WP_User $user'
                ],
                'return' => 'array $response',
                'description' => __(
                    'Allows customization of the authentication endpoint response.',
                    'simple-jwt-login'
                ),
            ],
            [
                'name' => self::HOOK_RESPONSE_DELETE_USER,
                'type' => self::HOOK_TYPE_FILTER,
                'parameters' => [
                    'array $response',
                    'WP_User $user'
                ],
                'return' => 'array $response',
                'description' => __(
                    'Allows customization of the delete user endpoint response.',
                    'simple-jwt-login'
                ),
            ],
            [
                'name' => self::HOOK_RESPONSE_REFRESH_TOKEN,
                'type' => self::HOOK_TYPE_FILTER,
                'parameters' => [
                    'array $response',
                    'WP_User $user'
                ],
                'return' => 'array $response',
                'description' => __(
                    'Allows customization of the refresh token endpoint response.',
                    'simple-jwt-login'
                ),
            ],
            [
                'name' => self::HOOK_RESPONSE_REGISTER_USER,
                'type' => self::HOOK_TYPE_FILTER,
                'parameters' => [
                    'array $response',
                    'WP_User $user'
                ],
                'return' => 'array $response',
                'description' => __(
                    'Allows customization of the register user endpoint response.',
                    'simple-jwt-login'
                ),
            ],
            [
                'name' => self::HOOK_RESPONSE_SEND_RESET_PASSWORD,
                'type' => self::HOOK_TYPE_FILTER,
                'parameters' => [
                    'array $response',
                    'WP_User $user'
                ],
                'return' => 'array $response',
                'description' => __(
                    'Allows customization of the send reset password endpoint response.',
                    'simple-jwt-login'
                ),
            ],
            [
                'name' => self::HOOK_RESPONSE_CHANGE_USER_PASSWORD,
                'type' => self::HOOK_TYPE_FILTER,
                'parameters' => [
                    'array $response',
                    'WP_User $user'
                ],
                'return' => 'array $response',
                'description' => __(
                    'Allows customization of the change user password endpoint response.',
                    'simple-jwt-login'
                ),
            ],
            [
                'name' => self::HOOK_RESPONSE_REVOKE_TOKEN,
                'type' => self::HOOK_TYPE_FILTER,
                'parameters' => [
                    'array $response',
                    'WP_User $user'
                ],
                'return' => 'array $response',
                'description' => __(
                    'Allows customization of the revoke token endpoint response.',
                    'simple-jwt-login'
                ),
            ],
            [
                'name' => self::HOOK_RESPONSE_VALIDATE_TOKEN,
                'type' => self::HOOK_TYPE_FILTER,
                'parameters' => [
                    'array $response',
                    'WP_User $user'
                ],
                'return' => 'array $response',
                'description' => __(
                    'Allows customization of the validate token endpoint response.',
                    'simple-jwt-login'
                ),
            ],
            [
                'name' => self::HOOK_GENERATE_PAYLOAD,
                'type' => self::HOOK_TYPE_FILTER,
                'parameters' => [
                    'array $payload',
                    'WP_User $user'
                ],
                'return' => 'array $payload',
                'description' => __(
                    'This is executed before generating the JWT payload.',
                    'simple-jwt-login'
                ) .
                    __(
                        'This will allow you to append extra properties in JWT on authentication.',
                        'simple-jwt-login'
                    )
                ,
            ],
            [
                'name' => self::HOOK_BEFORE_ENDPOINT,
                'type' => self::HOOK_TYPE_ACTION,
                'parameters' => [
                    'string $method',
                    'string $endpoint',
                    'array $request'
                ],
                'description' => __(
                    'Runs before any Simple JWT Login REST endpoint is processed.',
                    'simple-jwt-login'
                ),
            ],
            [
                'name' => self::HOOK_RESPONSE_2FA_CHALLENGE,
                'type' => self::HOOK_TYPE_FILTER,
                'parameters' => [
                    'array $response',
                    'WP_User $user'
                ],
                'return' => 'array $response',
                'description' => __(
                    'Allows customization of the two-factor challenge response.',
                    'simple-jwt-login'
                ),
            ],
            [
                'name' => self::HOOK_RESPONSE_2FA_VERIFY,
                'type' => self::HOOK_TYPE_FILTER,
                'parameters' => [
                    'array $response',
                    'WP_User $user'
                ],
                'return' => 'array $response',
                'description' => __(
                    'Allows customization of the two-factor verify endpoint response.',
                    'simple-jwt-login'
                ),
            ],
        ];
    }
}
