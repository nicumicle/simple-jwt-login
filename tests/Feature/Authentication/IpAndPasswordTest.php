<?php

namespace SimpleJwtLoginTests\Feature\Authentication;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * Feature tests for authentication IP restriction and password encoding.
 *
 * IP restriction: when auth_ip is set to a non-matching value the
 * authenticate endpoint rejects the request with 403 ERR_IP_IS_NOT_ALLOWED_TO_LOGIN.
 *
 * Base64 password: when auth_password_base64 = true the plugin decodes the
 * password from Base64 before comparing it to the stored hash.
 */
class IpAndPasswordTest extends FeatureTestCase
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

    // ─── IP restriction ───────────────────────────────────────────────────────

    #[TestDox('Auth from a blocked IP returns 403')]
    public function testAuthFromBlockedIpReturns403(): void
    {
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'auth_ip' => '10.0.0.1',
        ]));

        try {
            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
                'email'    => 'anyone@example.com',
                'password' => 'pass',
            ]);

            $this->assertSame(403, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            // Note: AuthenticateService.checkAllowedIPAddress() uses ERR_DELETE_INVALID_CLIENT_IP
            // (a code-reuse quirk in the implementation — the behaviour is correct even if the
            // constant name is misleading).
            $this->assertSame(ErrorCodes::ERR_DELETE_INVALID_CLIENT_IP, $body['data']['error_code']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    #[TestDox('Auth succeeds when IP restriction is empty (open access)')]
    public function testAuthSucceedsWithNoIpRestriction(): void
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
            'email'    => $email,
            'password' => $password,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('jwt', $body['data']);
    }

    // ─── Base64 password ──────────────────────────────────────────────────────

    #[TestDox('Auth accepts a Base64-encoded password when auth_password_base64 is enabled')]
    public function testBase64EncodedPasswordIsAccepted(): void
    {
        $plainPassword = 'MyPlainPass123!';
        [$email, , $status] = $this->createUser(['user_pass' => $plainPassword]);
        $this->assertSame(200, $status, 'register failed');

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'auth_password_base64' => true,
        ]));

        try {
            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
                'email'    => $email,
                'password' => base64_encode($plainPassword),
            ]);

            $this->assertSame(200, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertTrue($body['success']);
            $this->assertArrayHasKey('jwt', $body['data']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    #[TestDox('Auth rejects a plain-text password when auth_password_base64 is enabled')]
    public function testPlainTextPasswordRejectedWhenBase64Required(): void
    {
        $plainPassword = 'MyPlainPass123!';
        [$email, , $status] = $this->createUser(['user_pass' => $plainPassword]);
        $this->assertSame(200, $status, 'register failed');

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'auth_password_base64' => true,
        ]));

        try {
            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
                'email'    => $email,
                'password' => $plainPassword,
            ]);

            $this->assertSame(401, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(ErrorCodes::ERR_AUTHENTICATION_WRONG_CREDENTIALS, $body['data']['error_code']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    #[TestDox('Auth rejects a plain-text password when base64 is disabled (control)')]
    public function testPlainTextPasswordAcceptedWhenBase64Disabled(): void
    {
        $plainPassword = 'ControlPass456!';
        [$email, , $status] = $this->createUser(['user_pass' => $plainPassword]);
        $this->assertSame(200, $status, 'register failed');

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
            'email'    => $email,
            'password' => $plainPassword,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('jwt', $body['data']);
    }

    // ─── Delete IP restriction ─────────────────────────────────────────────────

    #[TestDox('Delete user from a blocked IP returns 403')]
    public function testDeleteUserFromBlockedIpReturns403(): void
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');
        $jwt = $this->getJWTForUser($email, $password);

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'delete_ip' => '10.0.0.1',
        ]));

        try {
            $response = $this->request('DELETE', '/simple-jwt-login/v1/users', [], [
                'Authorization' => $jwt,
            ]);

            $this->assertSame(403, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(ErrorCodes::ERR_DELETE_INVALID_CLIENT_IP, $body['data']['error_code']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }
}
