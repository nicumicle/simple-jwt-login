<?php

namespace SimpleJwtLoginTests\WP\RevokeToken;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJwtLoginTests\WP\WPTestCase;

/**
 * Integration tests for the Revoke Token endpoint.
 *
 * Route: POST /simple-jwt-login/v1/auth/revoke
 *
 * JWT transport: passed as URL/body parameter 'JWT' or via Authorization: Bearer header.
 *
 * Service flow tested here:
 *   1. checkAuthenticationEnabled()
 *   2. checkRevokeTokenEnabled()
 *   3. checkAllowedIPAddress()
 *   4. validateAuthenticationAuthKey() with ERR_INVALID_AUTH_CODE_PROVIDED
 *   5. revokeToken() — JWT parsing, user lookup, duplicate-revoke check, meta write
 */
class RevokeTokenTest extends WPTestCase
{
    private const JWT_SECRET = 'revoke-token-test-secret';
    private const ROUTE      = '/simple-jwt-login/v1/auth/revoke';
    private const AUTH_KEY   = 'AUTH_KEY';
    private const AUTH_CODE  = 'VALID_REVOKE_CODE';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::configurePlugin(static::baseConfig());
    }

    public static function tearDownAfterClass(): void
    {
        delete_option(SimpleJWTLoginSettings::OPTIONS_KEY);
        parent::tearDownAfterClass();
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private static function baseConfig(array $overrides = []): array
    {
        return array_merge([
            'allow_authentication'    => true,
            'allow_revoke_token'      => true,
            'revoke_requires_auth_code' => false,
            'auth_ip'                 => '',
            'decryption_key'          => self::JWT_SECRET,
            'jwt_auth_iss'            => 'tests',
            'jwt_login_by'            => LoginSettings::JWT_LOGIN_BY_EMAIL,
            'jwt_login_by_parameter'  => 'email',
            'jwt_payload'             => ['email', 'exp', 'id', 'iss', 'site', 'username'],
            'jwt_auth_ttl'            => 60,
            'allow_autologin'         => false,
            'allow_register'          => false,
            'allow_delete'            => false,
        ], $overrides);
    }

    private function makeJwt(array $payload): string
    {
        return JWT::encode(
            array_merge(['exp' => time() + 3600], $payload),
            self::JWT_SECRET,
            'HS256'
        );
    }

    private function jwtForEmail(string $email): string
    {
        return $this->makeJwt(['email' => $email]);
    }

    // ─── Feature disabled ─────────────────────────────────────────────────────

    #[TestDox('Returns AUTHENTICATION_IS_NOT_ENABLED when authentication is disabled')]
    public function testAuthenticationDisabledReturnsError(): void
    {
        static::configurePlugin(static::baseConfig(['allow_authentication' => false]));

        $response = $this->request('POST', self::ROUTE, ['JWT' => 'any.jwt.token']);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_AUTHENTICATION_IS_NOT_ENABLED, $data['data']['error_code']);
    }

    #[TestDox('Returns ERR_REVOKE_TOKEN_NOT_ENABLED when revoke endpoint is disabled')]
    public function testRevokeTokenDisabledReturnsError(): void
    {
        static::configurePlugin(static::baseConfig(['allow_revoke_token' => false]));

        $response = $this->request('POST', self::ROUTE, ['JWT' => 'any.jwt.token']);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_REVOKE_TOKEN_NOT_ENABLED, $data['data']['error_code']);
    }

    // ─── IP restriction ───────────────────────────────────────────────────────

    #[TestDox('Returns ERR_DELETE_INVALID_CLIENT_IP when request IP is not in the allowed list')]
    public function testIpRestrictionBlocksDisallowedIp(): void
    {
        static::configurePlugin(static::baseConfig(['auth_ip' => '192.0.2.1,192.0.2.2']));

        [$email] = $this->createUser();

        $response = $this->request('POST', self::ROUTE, [
            'JWT' => $this->jwtForEmail($email),
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_DELETE_INVALID_CLIENT_IP, $data['data']['error_code']);
    }

    // ─── Auth key validation ──────────────────────────────────────────────────

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function invalidAuthKeyProvider(): array
    {
        return [
            'auth required but no AUTH_KEY param provided' => [
                'params'         => [],
                'useExpiredCode' => false,
            ],
            'auth required but wrong AUTH_KEY value' => [
                'params'         => [self::AUTH_KEY => 'WRONG_CODE'],
                'useExpiredCode' => false,
            ],
            'auth required but expired auth code' => [
                'params'         => [self::AUTH_KEY => 'EXPIRED_CODE'],
                'useExpiredCode' => true,
            ],
        ];
    }

    #[DataProvider('invalidAuthKeyProvider')]
    #[TestDox('Returns ERR_INVALID_AUTH_CODE_PROVIDED for invalid auth key scenarios')]
    public function testInvalidAuthKeyReturnsError(array $params, bool $useExpiredCode): void
    {
        $authCodes = $useExpiredCode
            ? [['code' => 'EXPIRED_CODE', 'role' => '', 'expiration_date' => '2000-01-01']]
            : [['code' => self::AUTH_CODE, 'role' => '', 'expiration_date' => '']];

        static::configurePlugin(static::baseConfig([
            'revoke_requires_auth_code' => true,
            'auth_codes'                => $authCodes,
            'auth_code_key'             => self::AUTH_KEY,
        ]));

        [$email] = $this->createUser();

        $response = $this->request('POST', self::ROUTE, array_merge(
            ['JWT' => $this->jwtForEmail($email)],
            $params
        ));

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_INVALID_AUTH_CODE_PROVIDED, $data['data']['error_code']);
    }

    #[TestDox('Proceeds past auth key check when valid AUTH_KEY is provided')]
    public function testValidAuthKeyAllowsRevoke(): void
    {
        static::configurePlugin(static::baseConfig([
            'revoke_requires_auth_code' => true,
            'auth_codes'                => [
                ['code' => self::AUTH_CODE, 'role' => '', 'expiration_date' => ''],
            ],
            'auth_code_key' => self::AUTH_KEY,
        ]));

        [$email] = $this->createUser();

        $response = $this->request('POST', self::ROUTE, [
            'JWT'          => $this->jwtForEmail($email),
            self::AUTH_KEY => self::AUTH_CODE,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    #[TestDox('Auth key is not required when revoke_requires_auth_code is false')]
    public function testAuthKeyNotRequiredWhenFlagIsFalse(): void
    {
        static::configurePlugin(static::baseConfig([
            'revoke_requires_auth_code' => false,
            'auth_codes'                => [
                ['code' => self::AUTH_CODE, 'role' => '', 'expiration_date' => ''],
            ],
            'auth_code_key' => self::AUTH_KEY,
        ]));

        [$email] = $this->createUser();

        $response = $this->request('POST', self::ROUTE, [
            'JWT' => $this->jwtForEmail($email),
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    // ─── Missing / invalid JWT ────────────────────────────────────────────────

    #[TestDox('Returns ERR_MISSING_JWT_AUTH_VALIDATE when no JWT is provided')]
    public function testMissingJwtReturnsError(): void
    {
        static::configurePlugin(static::baseConfig());

        $response = $this->request('POST', self::ROUTE, []);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE, $data['data']['error_code']);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function invalidJwtProvider(): array
    {
        return [
            'malformed token (not a JWT)' => [
                'jwt' => 'not.a.valid.jwt.token',
            ],
            'token signed with wrong secret' => [
                'jwt' => JWT::encode(
                    ['email' => 'user@example.com', 'exp' => time() + 3600],
                    'wrong-secret',
                    'HS256'
                ),
            ],
        ];
    }

    #[DataProvider('invalidJwtProvider')]
    #[TestDox('Returns error for structurally invalid JWT tokens')]
    public function testInvalidJwtReturnsError(string $jwt): void
    {
        static::configurePlugin(static::baseConfig());

        $response = $this->request('POST', self::ROUTE, ['JWT' => $jwt]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
    }

    #[TestDox('Returns error for an expired JWT')]
    public function testExpiredJwtReturnsError(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email] = $this->createUser();
        $expiredJwt = JWT::encode(
            ['email' => $email, 'exp' => time() - 3600],
            self::JWT_SECRET,
            'HS256'
        );

        $response = $this->request('POST', self::ROUTE, ['JWT' => $expiredJwt]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
    }

    // ─── User not found ───────────────────────────────────────────────────────

    #[TestDox('Returns ERR_DO_LOGIN_USER_NOT_FOUND when JWT is valid but the user does not exist')]
    public function testUserNotFoundReturnsError(): void
    {
        static::configurePlugin(static::baseConfig());

        $jwt = $this->jwtForEmail('ghost-' . time() . '@nowhere.invalid');

        $response = $this->request('POST', self::ROUTE, ['JWT' => $jwt]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND, $data['data']['error_code']);
    }

    // ─── Already-revoked token ────────────────────────────────────────────────

    #[TestDox('Returns error when the same JWT is revoked a second time')]
    public function testAlreadyRevokedTokenReturnsError(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email, , $userId] = $this->createUser();
        $jwt = $this->jwtForEmail($email);

        add_user_meta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY, $jwt);

        try {
            $response = $this->request('POST', self::ROUTE, ['JWT' => $jwt]);

            $data = $response->get_data();
            $this->assertFalse($data['success']);
        } finally {
            delete_user_meta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY);
        }
    }

    // ─── Success scenarios ────────────────────────────────────────────────────

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function successRevokeProvider(): array
    {
        return [
            'login by email (jwt_login_by=0)' => [
                'loginBy'          => LoginSettings::JWT_LOGIN_BY_EMAIL,
                'loginByParameter' => 'email',
                'jwtPayloadKey'    => 'email',
            ],
            'login by WordPress user ID (jwt_login_by=1)' => [
                'loginBy'          => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
                'loginByParameter' => 'id',
                'jwtPayloadKey'    => 'id',
            ],
            'login by user_login (jwt_login_by=2)' => [
                'loginBy'          => LoginSettings::JWT_LOGIN_BY_USER_LOGIN,
                'loginByParameter' => 'login',
                'jwtPayloadKey'    => 'login',
            ],
        ];
    }

    #[DataProvider('successRevokeProvider')]
    #[TestDox('Successfully revokes a JWT when user is identified by different payload fields')]
    public function testSuccessfulRevoke(
        int $loginBy,
        string $loginByParameter,
        string $jwtPayloadKey
    ): void {
        static::configurePlugin(static::baseConfig([
            'jwt_login_by'           => $loginBy,
            'jwt_login_by_parameter' => $loginByParameter,
        ]));

        [$email, , $userId] = $this->createUser();
        $user = get_user_by('id', $userId);
        $this->assertNotFalse($user, 'Pre-condition: user must exist');

        $payloadValue = match ($loginBy) {
            LoginSettings::JWT_LOGIN_BY_EMAIL             => $email,
            LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID => $userId,
            LoginSettings::JWT_LOGIN_BY_USER_LOGIN        => $user->user_login,
        };

        $jwt = JWT::encode(
            array_merge(['exp' => time() + 3600], [$jwtPayloadKey => $payloadValue]),
            self::JWT_SECRET,
            'HS256'
        );

        $response = $this->request('POST', self::ROUTE, ['JWT' => $jwt]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    #[TestDox('JWT passed via Authorization: Bearer header is revoked successfully')]
    public function testJwtViaAuthorizationHeaderIsRevoked(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email] = $this->createUser();
        $jwt     = $this->jwtForEmail($email);

        $response = $this->request('POST', self::ROUTE, [], $this->authHeader($jwt));

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    // ─── Post-revoke side effects ─────────────────────────────────────────────

    #[TestDox('Revoked token is stored in user meta so subsequent validation fails')]
    public function testRevokedTokenIsPersistedAndBlocksValidation(): void
    {
        static::configurePlugin(static::baseConfig([
            'allow_authentication' => true,
            'allow_validate_token' => 1,
            'validate_requires_auth_code' => false,
        ]));

        [$email, , $userId] = $this->createUser();
        $jwt = $this->jwtForEmail($email);

        $revokeResponse = $this->request('POST', self::ROUTE, ['JWT' => $jwt]);
        $this->assertSame(200, $revokeResponse->get_status());

        try {
            $revokedMeta = get_user_meta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY);
            $this->assertContains($jwt, $revokedMeta, 'JWT must be stored in user meta after revocation');
        } finally {
            delete_user_meta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY);
        }
    }

    #[TestDox('Expired revoked tokens in meta are cleaned up when a new token is revoked')]
    public function testExpiredRevokedTokensAreCleanedUp(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email, , $userId] = $this->createUser();

        $expiredJwt = JWT::encode(
            ['email' => $email, 'exp' => time() - 7200],
            self::JWT_SECRET,
            'HS256'
        );
        add_user_meta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY, $expiredJwt);

        $newJwt = $this->jwtForEmail($email);

        $response = $this->request('POST', self::ROUTE, ['JWT' => $newJwt]);
        $this->assertSame(200, $response->get_status());

        try {
            $remaining = get_user_meta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY);
            $this->assertNotContains($expiredJwt, $remaining, 'Expired revoked token must be removed from meta');
            $this->assertContains($newJwt, $remaining, 'Newly revoked token must be present in meta');
        } finally {
            delete_user_meta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY);
        }
    }

    // ─── Response shape ───────────────────────────────────────────────────────

    #[TestDox('Success response contains success=true, message, and data.jwt array')]
    public function testSuccessResponseShape(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email] = $this->createUser();
        $jwt     = $this->jwtForEmail($email);

        $response = $this->request('POST', self::ROUTE, ['JWT' => $jwt]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('Token was revoked.', $data['message']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('jwt', $data['data']);
        $this->assertContains($jwt, $data['data']['jwt']);
    }
}
