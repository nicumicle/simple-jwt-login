<?php

namespace SimpleJwtLoginTests\Feature\ResetPassword;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\ResetPasswordSettings;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

class ValidationTest extends FeatureTestCase
{
    const JWT_SECRET_KEY = 'test';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption([
            'allow_authentication'    => true,
            'jwt_payload'             => ['email', 'exp', 'id', 'iss', 'site', 'username'],
            'jwt_auth_ttl'            => 60,
            'jwt_auth_refresh_ttl'    => '20160',
            'auth_ip'                 => '',
            'auth_requires_auth_code' => false,
            'auth_password_base64'    => false,
            'jwt_auth_iss'            => 'tests',
            'decryption_key'          => self::JWT_SECRET_KEY,
            'allow_register'          => true,
            'new_user_profile'        => 'subscriber',
            'register_ip'             => '',
            'register_domain'         => '',
            'require_register_auth'   => false,
            'allow_delete'            => true,
            'require_delete_auth'     => false,
            'delete_ip'               => '',
            'delete_user_by'          => 0,
            'jwt_delete_by_parameter' => 'email',
            'jwt_login_by'            => 0,
            'jwt_login_by_parameter'  => 'email',
            'allow_reset_password'               => true,
            'reset_password_requires_auth_code'  => false,
            'jwt_reset_password_flow'            => ResetPasswordSettings::FLOW_JUST_SAVE_IN_DB,
            'reset_password_jwt'                 => true,
        ]);
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function sendResetPasswordValidationProvider(): array
    {
        return [
            'missing email' => [
                'payload'       => [],
                'expectedStatus' => 422,
                'expectedError'  => self::generateErrorJson(
                    'Missing email parameter.',
                    ErrorCodes::ERR_MISSING_NEW_PASSWORD_FOR_RESET_PASSWORD
                ),
            ],
            'invalid email format' => [
                'payload'        => ['email' => 'not-an-email'],
                'expectedStatus' => 422,
                'expectedError'  => self::generateErrorJson(
                    'Invalid email parameter.',
                    ErrorCodes::ERR_INVALID_EMAIL_FOR_RESET_PASSWORD
                ),
            ],
            'user does not exist' => [
                'payload'        => ['email' => 'nonexistent@example.com'],
                'expectedStatus' => 404,
                'expectedError'  => self::generateErrorJson(
                    'Wrong user.',
                    ErrorCodes::ERR_USER_NOT_FOUND_FOR_RESET_PASSWORD
                ),
            ],
        ];
    }

    #[DataProvider('sendResetPasswordValidationProvider')]
    #[TestDox('POST reset-password returns correct error')]
    /**
     * @param array<string,mixed> $payload
     * @param int $expectedStatus
     * @param array<string,mixed> $expectedError
     */
    public function testSendResetPasswordValidation(array $payload, int $expectedStatus, array $expectedError): void
    {
        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/user/reset_password', $payload);

        $this->assertSame($expectedStatus, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertSame($expectedError, $body);
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function changePasswordValidationProvider(): array
    {
        return [
            'missing email' => [
                'payload'        => ['new_password' => 'abc', 'JWT' => 'dummy'],
                'expectedStatus' => 422,
                'expectedError'  => self::generateErrorJson(
                    'Missing email parameter.',
                    ErrorCodes::ERR_MISSING_EMAIL_FOR_CHANGE_PASSWORD
                ),
            ],
            'missing new_password' => [
                'payload'        => ['email' => 'user@example.com', 'JWT' => 'dummy'],
                'expectedStatus' => 422,
                'expectedError'  => self::generateErrorJson(
                    'Missing new_password parameter.',
                    ErrorCodes::ERR_MISSING_NEW_PASSWORD_FOR_CHANGE_PASSWORD
                ),
            ],
            'missing jwt and code' => [
                'payload'        => ['email' => 'user@example.com', 'new_password' => 'abc'],
                'expectedStatus' => 422,
                'expectedError'  => self::generateErrorJson(
                    'Missing code or jwt parameter.',
                    ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE
                ),
            ],
            'invalid email format' => [
                'payload'        => ['email' => 'bad-email', 'new_password' => 'abc', 'JWT' => 'dummy'],
                'expectedStatus' => 422,
                'expectedError'  => self::generateErrorJson(
                    'Invalid email parameter.',
                    ErrorCodes::ERR_INVALID_EMAIL_FOR_CHANGE_PASSWORD
                ),
            ],
        ];
    }

    #[DataProvider('changePasswordValidationProvider')]
    #[TestDox('PUT reset-password returns correct validation error')]
    /**
     * @param array<string,mixed> $payload
     * @param int $expectedStatus
     * @param array<string,mixed> $expectedError
     */
    public function testChangePasswordValidation(array $payload, int $expectedStatus, array $expectedError): void
    {
        $response = $this->jsonRequest('PUT', '/simple-jwt-login/v1/user/reset_password', $payload);

        $this->assertSame($expectedStatus, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertSame($expectedError, $body);
    }
}
