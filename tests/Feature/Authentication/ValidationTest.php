<?php

namespace SimpleJwtLoginTests\Feature\Authentication;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

class ValidationTest extends FeatureTestCase
{
    private const JWT_SECRET_KEY = 'test-secret';

    /**
     * @return array<string,mixed>
     */
    private static function baseSettings(): array
    {
        return [
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
        ];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption(self::baseSettings());
    }

    // ─── Data providers ───────────────────────────────────────────────────────

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function missingCredentialsProvider(): array
    {
        return [
            'no email no password' => [
                'payload'        => [],
                'expectedStatus' => 422,
                'expectedError'  => self::generateErrorJson(
                    'The email, username, or login parameter is missing from the request.',
                    ErrorCodes::ERR_AUTHENTICATION_MISSING_EMAIL
                ),
            ],
            'email present but no password' => [
                'payload'        => ['email' => 'test@example.com'],
                'expectedStatus' => 422,
                'expectedError'  => self::generateErrorJson(
                    'The password or password_hash parameter is missing from request.',
                    ErrorCodes::ERR_AUTHENTICATION_MISSING_PASSWORD
                ),
            ],
            'username present but no password' => [
                'payload'        => ['username' => 'someuser'],
                'expectedStatus' => 422,
                'expectedError'  => self::generateErrorJson(
                    'The password or password_hash parameter is missing from request.',
                    ErrorCodes::ERR_AUTHENTICATION_MISSING_PASSWORD
                ),
            ],
            'login present but no password' => [
                'payload'        => ['login' => 'someuser@example.com'],
                'expectedStatus' => 422,
                'expectedError'  => self::generateErrorJson(
                    'The password or password_hash parameter is missing from request.',
                    ErrorCodes::ERR_AUTHENTICATION_MISSING_PASSWORD
                ),
            ],
        ];
    }

    // ─── Tests: validation ────────────────────────────────────────────────────

    /**
     * @param array<string,mixed> $payload
     * @param int $expectedStatus
     * @param array<string,mixed> $expectedError
     */
    #[DataProvider('missingCredentialsProvider')]
    #[TestDox('Auth returns 422 for missing credentials')]
    public function testMissingCredentialsReturn422(
        array $payload,
        int $expectedStatus,
        array $expectedError
    ): void {
        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', $payload);

        $this->assertSame($expectedStatus, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertSame($expectedError, $body);
    }

    // ─── Tests: wrong credentials ─────────────────────────────────────────────

    #[TestDox('Auth with wrong password returns 401')]
    public function testWrongPasswordReturns401(): void
    {
        [$email, , $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
            'email'    => $email,
            'password' => 'definitely-wrong-password-xyz-' . uniqid(),
        ]);

        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_AUTHENTICATION_WRONG_CREDENTIALS, $body['data']['errorCode']);
    }

    #[TestDox('Auth with unknown email returns 401')]
    public function testUnknownEmailReturns401(): void
    {
        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
            'email'    => 'nonexistent_' . uniqid() . '@example.com',
            'password' => 'SomePass123!',
        ]);

        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_AUTHENTICATION_WRONG_CREDENTIALS, $body['data']['errorCode']);
    }

    #[TestDox('Auth with unknown username returns 401')]
    public function testUnknownUsernameReturns401(): void
    {
        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
            'username' => 'nonexistent_user_' . uniqid(),
            'password' => 'SomePass123!',
        ]);

        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_AUTHENTICATION_WRONG_CREDENTIALS, $body['data']['errorCode']);
    }

    // ─── Tests: feature disabled ──────────────────────────────────────────────

    #[TestDox('Auth returns 403 when authentication feature is disabled')]
    public function testAuthDisabledReturns403(): void
    {
        $settings = array_merge(self::baseSettings(), ['allow_authentication' => false]);
        self::updateSimpleJWTOption($settings);

        try {
            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
                'email'    => 'test@example.com',
                'password' => 'pass',
            ]);

            $this->assertSame(403, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(ErrorCodes::ERR_AUTHENTICATION_IS_NOT_ENABLED, $body['data']['errorCode']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    #[TestDox('Token refresh returns 403 when authentication feature is disabled')]
    public function testRefreshTokenDisabledReturns403(): void
    {
        $settings = array_merge(self::baseSettings(), ['allow_authentication' => false]);
        self::updateSimpleJWTOption($settings);

        try {
            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/refresh', [
                'refresh_token' => 'some-token',
            ]);

            $this->assertSame(403, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(ErrorCodes::ERR_AUTHENTICATION_IS_NOT_ENABLED, $body['data']['errorCode']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    // ─── Tests: auth code required ────────────────────────────────────────────

    #[TestDox('Auth returns 422 when auth code is required but not provided')]
    public function testAuthCodeRequiredButMissingReturns422(): void
    {
        $settings = array_merge(self::baseSettings(), [
            'auth_requires_auth_code' => true,
            'auth_codes'              => [
                ['code' => 'valid-code-123', 'role' => '', 'expiration_date' => ''],
            ],
        ]);
        self::updateSimpleJWTOption($settings);

        try {
            [$email, $password, $regStatus] = $this->createUser();
            $this->assertSame(200, $regStatus, 'register failed');

            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
                'email'    => $email,
                'password' => $password,
            ]);

            $this->assertSame(422, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(ErrorCodes::ERR_AUTH_CODE_REQUIRED, $body['data']['errorCode']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    #[TestDox('Auth succeeds when valid auth code is provided')]
    public function testAuthSucceedsWithValidAuthCode(): void
    {
        $settings = array_merge(self::baseSettings(), [
            'auth_requires_auth_code' => true,
            'auth_codes'              => [
                ['code' => 'valid-code-123', 'role' => '', 'expiration_date' => ''],
            ],
        ]);
        self::updateSimpleJWTOption($settings);

        try {
            [$email, $password, $regStatus] = $this->createUser();
            $this->assertSame(200, $regStatus, 'register failed');

            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
                'email'    => $email,
                'password' => $password,
                'AUTH_KEY' => 'valid-code-123',
            ]);

            $this->assertSame(200, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertTrue($body['success']);
            $this->assertArrayHasKey('jwt', $body['data']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }
}
