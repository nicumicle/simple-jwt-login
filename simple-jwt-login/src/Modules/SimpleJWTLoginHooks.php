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

    public static function getHooksDetails()
    {
        return [
            [
                'name' => self::LOGIN_ACTION_NAME,
                'type' => self::HOOK_TYPE_ACTION,
                'parameters' => [
                    'Wp_User $user'
                ],
                'description' => __('This hook is called after the user is logged in.', 'simple-jwt-login'),
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
                        'This hook is called before the user is redirected to the page' .
                        'that he specified in the login section.',
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
                'description' => __('This hook is called after a new user is created.', 'simple-jwt-login'),
            ],
            [
                'name' => self::DELETE_USER_ACTION_NAME,
                'type' => self::HOOK_TYPE_ACTION,
                'parameters' => [
                    'Wp_User $user'
                ],
                'description' => __('This hook is called right after the user was deleted.', 'simple-jwt-login')
            ],
            [
                'name' => self::JWT_PAYLOAD_ACTION_NAME,
                'type' => self::HOOK_TYPE_FILTER,
                'parameters' => [
                    'array $payload',
                    'array $request'
                ],
                'return' => 'array $payload',
                'description' =>
                    __(
                        'This hook is called on /auth endpoint.'
                        . 'Here you can modify payload parameters.',
                        'simple-jwt-login'
                    )
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
                    'This hook is called on /autologin endpoint when the option'
                    . '`No Redirect` is selected. You can customize the message and add parameters.',
                    'simple-jwt-login'
                )
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
                    'This is executed when POST /user/reset_password is called.'
                    . ' It will replace the email template that has been added in Reset Password settings',
                    'simple-jwt-login'
                )
            ],
        ];
    }
}
