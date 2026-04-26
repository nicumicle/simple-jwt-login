<?php

namespace SimpleJwtLoginTests\WP\RefreshToken;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJwtLoginTests\WP\WPTestCase;

/**
 * Integration tests for the Refresh Token endpoint.
 *
 * Route: POST /simple-jwt-login/v1/auth/refresh
 *
 * The service flow tested here:
 *   1. checkAuthenticationEnabled()
 *   2. checkJwtNotRevoked()          — optional JWT in request; revoked JWTs are rejected
 *   3. checkRefreshTokenEnabled()
 *   4. checkAllowedIPAddress()
 *   5. validateAuthenticationAuthKey() with ERR_INVALID_AUTH_CODE_PROVIDED
 *   6. refreshJwt() — opaque refresh_token lookup, rotation, new JWT issue
 *
 * A valid refresh_token is obtained by calling the auth endpoint first.
 * Both auth and refresh use the same JWT_SECRET so that encryptRefreshToken()
 * produces the same HMAC on both sides.
 */
class RefreshTokenTest extends WPTestCase
{
    private const JWT_SECRET  = 'refresh-token-test-secret';
    private const ROUTE       = '/simple-jwt-login/v1/auth/refresh';
    private const AUTH_ROUTE  = '/simple-jwt-login/v1/auth';
    private const AUTH_KEY    = 'AUTH_KEY';
    private const AUTH_CODE   = 'VALID_REFRESH_CODE';

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
            'allow_authentication'        => true,
            'allow_refresh_token'         => true,
            'refresh_requires_auth_code'  => false,
            'auth_ip'                     => '',
            'decryption_key'              => self::JWT_SECRET,
            'jwt_auth_iss'                => 'tests',
            'jwt_login_by'                => LoginSettings::JWT_LOGIN_BY_EMAIL,
            'jwt_login_by_parameter'      => 'email',
            'jwt_payload'                 => ['email', 'exp', 'id', 'iss', 'site', 'username'],
            'jwt_auth_ttl'                => 60,
            'jwt_auth_refresh_ttl'        => 20160,
            'allow_autologin'             => false,
            'allow_register'              => false,
            'allow_delete'                => false,
        ], $overrides);
    }

    /**
     * Authenticate and return ['jwt' => ..., 'refresh_token' => ...].
     * Uses the same JWT_SECRET so encryptRefreshToken() is consistent.
     *
     * @return array<string,string>
     */
    private function authenticate(string $email, string $password): array
    {
        static::configurePlugin(static::baseConfig());

        $response = $this->jsonRequest('POST', self::AUTH_ROUTE, [
            'email'    => $email,
            'password' => $password,
        ]);

        $this->assertSame(200, $response->get_status(), 'Pre-condition: auth must succeed');
        $data = $response->get_data();
        $this->assertArrayHasKey('refresh_token', $data['data'], 'Pre-condition: refresh_token must be returned');

        return $data['data'];
    }

    // ─── Feature disabled ─────────────────────────────────────────────────────

    #[TestDox('Returns AUTHENTICATION_IS_NOT_ENABLED when authentication is disabled')]
    public function testAuthenticationDisabledReturnsError(): void
    {
        static::configurePlugin(static::baseConfig(['allow_authentication' => false]));

        $response = $this->request('POST', self::ROUTE, ['refresh_token' => 'any']);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::AUTHENTICATION_IS_NOT_ENABLED, $data['data']['errorCode']);
    }

    #[TestDox('Returns ERR_REFRESH_TOKEN_NOT_ENABLED when refresh endpoint is disabled')]
    public function testRefreshTokenDisabledReturnsError(): void
    {
        static::configurePlugin(static::baseConfig(['allow_refresh_token' => false]));

        $response = $this->request('POST', self::ROUTE, ['refresh_token' => 'any']);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_REFRESH_TOKEN_NOT_ENABLED, $data['data']['errorCode']);
    }

    // ─── IP restriction ───────────────────────────────────────────────────────

    #[TestDox('Returns ERR_DELETE_INVALID_CLIENT_IP when request IP is not in the allowed list')]
    public function testIpRestrictionBlocksDisallowedIp(): void
    {
        static::configurePlugin(static::baseConfig(['auth_ip' => '192.0.2.1,192.0.2.2']));

        $response = $this->request('POST', self::ROUTE, ['refresh_token' => 'any']);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_DELETE_INVALID_CLIENT_IP, $data['data']['errorCode']);
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
            'refresh_requires_auth_code' => true,
            'auth_codes'                 => $authCodes,
            'auth_code_key'              => self::AUTH_KEY,
        ]));

        $response = $this->request('POST', self::ROUTE, array_merge(
            ['refresh_token' => 'any'],
            $params
        ));

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_INVALID_AUTH_CODE_PROVIDED, $data['data']['errorCode']);
    }

    #[TestDox('Proceeds past auth key check when a valid AUTH_KEY is provided')]
    public function testValidAuthKeyAllowsRefresh(): void
    {
        [$email, $password] = $this->createUser();
        $tokens = $this->authenticate($email, $password);

        static::configurePlugin(static::baseConfig([
            'refresh_requires_auth_code' => true,
            'auth_codes'                 => [
                ['code' => self::AUTH_CODE, 'role' => '', 'expiration_date' => ''],
            ],
            'auth_code_key' => self::AUTH_KEY,
        ]));

        $response = $this->request('POST', self::ROUTE, [
            'refresh_token' => $tokens['refresh_token'],
            self::AUTH_KEY  => self::AUTH_CODE,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    #[TestDox('Auth key is not required when refresh_requires_auth_code is false')]
    public function testAuthKeyNotRequiredWhenFlagIsFalse(): void
    {
        [$email, $password] = $this->createUser();
        $tokens = $this->authenticate($email, $password);

        static::configurePlugin(static::baseConfig([
            'refresh_requires_auth_code' => false,
            'auth_codes'                 => [
                ['code' => self::AUTH_CODE, 'role' => '', 'expiration_date' => ''],
            ],
            'auth_code_key' => self::AUTH_KEY,
        ]));

        $response = $this->request('POST', self::ROUTE, [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    // ─── Missing / invalid refresh_token ─────────────────────────────────────

    #[TestDox('Returns ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH when refresh_token param is absent')]
    public function testMissingRefreshTokenParamReturnsError(): void
    {
        static::configurePlugin(static::baseConfig());

        $response = $this->request('POST', self::ROUTE, []);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH, $data['data']['errorCode']);
    }

    #[TestDox('Returns ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH when refresh_token is not in the database')]
    public function testUnknownRefreshTokenReturnsError(): void
    {
        static::configurePlugin(static::baseConfig());

        $response = $this->request('POST', self::ROUTE, [
            'refresh_token' => 'completely-unknown-token-' . bin2hex(random_bytes(8)),
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH, $data['data']['errorCode']);
    }

    // ─── Revoked JWT check ────────────────────────────────────────────────────

    #[TestDox('Returns ERR_REVOKED_TOKEN when a revoked JWT is passed alongside the refresh_token')]
    public function testRevokedJwtBlocksRefresh(): void
    {
        [$email, $password, $userId] = $this->createUser();
        $tokens = $this->authenticate($email, $password);
        $jwt    = $tokens['jwt'];

        add_user_meta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY, $jwt);

        try {
            static::configurePlugin(static::baseConfig());

            $response = $this->request('POST', self::ROUTE, [
                'refresh_token' => $tokens['refresh_token'],
                'JWT'           => $jwt,
            ]);

            $data = $response->get_data();
            $this->assertFalse($data['success']);
            $this->assertSame(ErrorCodes::ERR_REVOKED_TOKEN, $data['data']['errorCode']);
        } finally {
            delete_user_meta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY);
        }
    }

    #[TestDox('An expired JWT passed alongside the refresh_token does not block the refresh')]
    public function testExpiredJwtDoesNotBlockRefresh(): void
    {
        [$email, $password] = $this->createUser();
        $tokens = $this->authenticate($email, $password);

        $expiredJwt = JWT::encode(
            ['email' => $email, 'exp' => time() - 3600],
            self::JWT_SECRET,
            'HS256'
        );

        static::configurePlugin(static::baseConfig());

        $response = $this->request('POST', self::ROUTE, [
            'refresh_token' => $tokens['refresh_token'],
            'JWT'           => $expiredJwt,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    // ─── Success scenarios ────────────────────────────────────────────────────

    #[TestDox('Successfully issues a new JWT and a new refresh_token for a valid refresh_token')]
    public function testSuccessfulRefresh(): void
    {
        [$email, $password] = $this->createUser();
        $tokens = $this->authenticate($email, $password);

        static::configurePlugin(static::baseConfig());

        $response = $this->request('POST', self::ROUTE, [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('jwt', $data['data']);
        $this->assertArrayHasKey('refresh_token', $data['data']);
        $this->assertNotEmpty($data['data']['jwt']);
        $this->assertNotEmpty($data['data']['refresh_token']);
    }

    #[TestDox('Newly issued JWT is a valid signed token')]
    public function testNewJwtIsDecodable(): void
    {
        [$email, $password] = $this->createUser();
        $tokens = $this->authenticate($email, $password);

        static::configurePlugin(static::baseConfig());

        $response = $this->request('POST', self::ROUTE, [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $this->assertSame(200, $response->get_status());
        $newJwt = $response->get_data()['data']['jwt'];

        $payload = (array) JWT::decode($newJwt, self::JWT_SECRET, ['HS256']);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertGreaterThan(time(), $payload['exp']);
    }

    #[TestDox('Refresh_token is rotated: the old token cannot be reused after a successful refresh')]
    public function testRefreshTokenIsRotated(): void
    {
        [$email, $password] = $this->createUser();
        $tokens = $this->authenticate($email, $password);
        $originalRefreshToken = $tokens['refresh_token'];

        static::configurePlugin(static::baseConfig());

        $firstResponse = $this->request('POST', self::ROUTE, [
            'refresh_token' => $originalRefreshToken,
        ]);
        $this->assertSame(200, $firstResponse->get_status(), 'First refresh must succeed');

        $secondResponse = $this->request('POST', self::ROUTE, [
            'refresh_token' => $originalRefreshToken,
        ]);

        $data = $secondResponse->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH, $data['data']['errorCode']);
    }

    #[TestDox('New refresh_token returned by the first refresh can itself be used for another refresh')]
    public function testRotatedRefreshTokenIsUsable(): void
    {
        [$email, $password] = $this->createUser();
        $tokens = $this->authenticate($email, $password);

        static::configurePlugin(static::baseConfig());

        $firstResponse = $this->request('POST', self::ROUTE, [
            'refresh_token' => $tokens['refresh_token'],
        ]);
        $this->assertSame(200, $firstResponse->get_status(), 'First refresh must succeed');
        $newRefreshToken = $firstResponse->get_data()['data']['refresh_token'];

        $secondResponse = $this->request('POST', self::ROUTE, [
            'refresh_token' => $newRefreshToken,
        ]);

        $this->assertSame(200, $secondResponse->get_status());
        $data = $secondResponse->get_data();
        $this->assertTrue($data['success']);
    }

    #[TestDox('New refresh_token differs from the original refresh_token')]
    public function testNewRefreshTokenDiffersFromOriginal(): void
    {
        [$email, $password] = $this->createUser();
        $tokens = $this->authenticate($email, $password);

        static::configurePlugin(static::baseConfig());

        $response = $this->request('POST', self::ROUTE, [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $this->assertSame(200, $response->get_status());
        $newRefreshToken = $response->get_data()['data']['refresh_token'];
        $this->assertNotSame($tokens['refresh_token'], $newRefreshToken);
    }

    // ─── Response shape ───────────────────────────────────────────────────────

    #[TestDox('Success response contains success=true and data with jwt and refresh_token')]
    public function testSuccessResponseShape(): void
    {
        [$email, $password] = $this->createUser();
        $tokens = $this->authenticate($email, $password);

        static::configurePlugin(static::baseConfig());

        $response = $this->request('POST', self::ROUTE, [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('jwt', $data['data']);
        $this->assertArrayHasKey('refresh_token', $data['data']);
        $this->assertIsString($data['data']['jwt']);
        $this->assertIsString($data['data']['refresh_token']);
    }
}
