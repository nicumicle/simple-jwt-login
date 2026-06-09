<?php

namespace SimpleJwtLoginTests\WP\Authentication;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RefreshToken\RefreshTokenRepository;
use SimpleJwtLoginTests\WP\WPTestCase;

/**
 * Comprehensive integration tests for the Authentication endpoint.
 *
 * Route: POST /simple-jwt-login/v1/auth
 *
 * Service flow:
 *   1. checkAuthenticationEnabled()
 *   2. checkAllowedIPAddress()
 *   3. validateAuthenticationAuthKey()
 *   4. authenticateUser() — credential lookup (email/username/login),
 *      password check, optional base64 decode, JWT generation,
 *      optional refresh token creation
 *
 * The existing AuthTest.php covers the basic success and wrong-password
 * scenarios. This class covers all remaining branches.
 */
class AuthEndpointTest extends WPTestCase
{
    private const JWT_SECRET = 'auth-endpoint-test-secret';
    private const ROUTE      = '/simple-jwt-login/v1/auth';
    private const AUTH_KEY   = 'AUTH_KEY';
    private const AUTH_CODE  = 'VALID_AUTH_CODE';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        global $wpdb;
        (new RefreshTokenRepository($wpdb))->createTable();

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
            'jwt_payload'             => ['email', 'exp', 'id', 'iss', 'site', 'username'],
            'jwt_auth_ttl'            => 60,
            'jwt_auth_refresh_ttl'    => 20160,
            'auth_ip'                 => '',
            'auth_requires_auth_code' => false,
            'auth_password_base64'    => false,
            'jwt_auth_iss'            => 'tests',
            'decryption_key'          => self::JWT_SECRET,
            'jwt_login_by'            => 0,
            'jwt_login_by_parameter'  => 'email',
        ], $overrides);
    }

    // ─── Feature disabled ─────────────────────────────────────────────────────

    #[TestDox('Returns AUTHENTICATION_IS_NOT_ENABLED when authentication is disabled')]
    public function testAuthenticationDisabledReturnsError(): void
    {
        static::configurePlugin(static::baseConfig(['allow_authentication' => false]));

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => 'user@example.com',
            'password' => 'password123',
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_AUTHENTICATION_IS_NOT_ENABLED, $data['data']['error_code']);
    }

    // ─── Missing credentials ──────────────────────────────────────────────────

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function missingCredentialsProvider(): array
    {
        return [
            'no params at all' => [
                'params'       => [],
                'expectedCode' => ErrorCodes::ERR_AUTHENTICATION_MISSING_EMAIL,
            ],
            'only password, no email/username/login' => [
                'params'       => ['password' => 'secret123'],
                'expectedCode' => ErrorCodes::ERR_AUTHENTICATION_MISSING_EMAIL,
            ],
            'only email, no password' => [
                'params'       => ['email' => 'user@example.com'],
                'expectedCode' => ErrorCodes::ERR_AUTHENTICATION_MISSING_PASSWORD,
            ],
            'only username, no password' => [
                'params'       => ['username' => 'someuser'],
                'expectedCode' => ErrorCodes::ERR_AUTHENTICATION_MISSING_PASSWORD,
            ],
            'only login, no password' => [
                'params'       => ['login' => 'user@example.com'],
                'expectedCode' => ErrorCodes::ERR_AUTHENTICATION_MISSING_PASSWORD,
            ],
        ];
    }

    #[DataProvider('missingCredentialsProvider')]
    #[TestDox('Returns the expected error code when required credentials are missing')]
    public function testMissingCredentialsReturnsError(array $params, int $expectedCode): void
    {
        static::configurePlugin(static::baseConfig());

        $response = $this->jsonRequest('POST', self::ROUTE, $params);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame($expectedCode, $data['data']['error_code']);
    }

    // ─── Login parameter variants ─────────────────────────────────────────────

    #[TestDox('Authenticates successfully using the username parameter (by user_login)')]
    public function testLoginByUsernameParameter(): void
    {
        static::configurePlugin(static::baseConfig());

        [, $password, $userId] = $this->createUser();
        $user = get_user_by('id', $userId);
        $this->assertNotFalse($user, 'Pre-condition: user must exist');

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'username' => $user->user_login,
            'password' => $password,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('jwt', $data['data']);
        $this->assertNotEmpty($data['data']['jwt']);
    }

    #[TestDox('Authenticates successfully using the login parameter when it is an email address')]
    public function testLoginByLoginParameterWithEmail(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email, $password] = $this->createUser();

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'login'    => $email,
            'password' => $password,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('jwt', $data['data']);
    }

    #[TestDox('Authenticates successfully using the login parameter when it is a user_login (no @ sign)')]
    public function testLoginByLoginParameterWithUsername(): void
    {
        static::configurePlugin(static::baseConfig());

        [, $password, $userId] = $this->createUser();
        $user = get_user_by('id', $userId);
        $this->assertNotFalse($user, 'Pre-condition: user must exist');

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'login'    => $user->user_login,
            'password' => $password,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('jwt', $data['data']);
    }

    // ─── Non-existent user ────────────────────────────────────────────────────

    #[TestDox('Returns ERR_AUTHENTICATION_WRONG_CREDENTIALS when the email does not match any user')]
    public function testNonExistentUserReturnsError(): void
    {
        static::configurePlugin(static::baseConfig());

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => 'ghost-' . time() . '@nowhere.invalid',
            'password' => 'anypassword',
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_AUTHENTICATION_WRONG_CREDENTIALS, $data['data']['error_code']);
    }

    // ─── IP restriction ───────────────────────────────────────────────────────

    #[TestDox('Returns ERR_DELETE_INVALID_CLIENT_IP when the request IP is not in the allowed list')]
    public function testIpRestrictionBlocksDisallowedIp(): void
    {
        static::configurePlugin(static::baseConfig(['auth_ip' => '192.0.2.1,192.0.2.2']));

        [$email, $password] = $this->createUser();

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $email,
            'password' => $password,
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_DELETE_INVALID_CLIENT_IP, $data['data']['error_code']);
    }

    // ─── Auth code validation ─────────────────────────────────────────────────

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function invalidAuthCodeProvider(): array
    {
        return [
            'auth required but no AUTH_KEY param' => [
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

    #[DataProvider('invalidAuthCodeProvider')]
    #[TestDox('Returns ERR_INVALID_AUTH_CODE_PROVIDED for invalid auth key scenarios')]
    public function testInvalidAuthKeyReturnsError(array $params, bool $useExpiredCode): void
    {
        $authCodes = $useExpiredCode
            ? [['code' => 'EXPIRED_CODE', 'role' => '', 'expiration_date' => '2000-01-01']]
            : [['code' => self::AUTH_CODE, 'role' => '', 'expiration_date' => '']];

        static::configurePlugin(static::baseConfig([
            'auth_requires_auth_code' => true,
            'auth_codes'              => $authCodes,
            'auth_code_key'           => self::AUTH_KEY,
        ]));

        [$email, $password] = $this->createUser();

        $response = $this->jsonRequest('POST', self::ROUTE, array_merge(
            ['email' => $email, 'password' => $password],
            $params
        ));

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_INVALID_AUTH_CODE_PROVIDED, $data['data']['error_code']);
    }

    #[TestDox('Proceeds past auth key check when a valid AUTH_KEY is provided')]
    public function testValidAuthKeyAllowsAuthentication(): void
    {
        static::configurePlugin(static::baseConfig([
            'auth_requires_auth_code' => true,
            'auth_codes'              => [
                ['code' => self::AUTH_CODE, 'role' => '', 'expiration_date' => ''],
            ],
            'auth_code_key' => self::AUTH_KEY,
        ]));

        [$email, $password] = $this->createUser();

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'        => $email,
            'password'     => $password,
            self::AUTH_KEY => self::AUTH_CODE,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('jwt', $data['data']);
    }

    // ─── Base64-encoded password ──────────────────────────────────────────────

    #[TestDox('Accepts a base64-encoded password when auth_password_base64 is enabled')]
    public function testBase64EncodedPasswordIsDecoded(): void
    {
        static::configurePlugin(static::baseConfig(['auth_password_base64' => true]));

        [$email, $password] = $this->createUser();

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $email,
            'password' => base64_encode($password),
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('jwt', $data['data']);
    }

    #[TestDox('Rejects a raw (non-base64) password when auth_password_base64 is enabled')]
    public function testRawPasswordRejectedWhenBase64Required(): void
    {
        static::configurePlugin(static::baseConfig(['auth_password_base64' => true]));

        [$email, $password] = $this->createUser();

        // Sending the raw password without base64 encoding causes base64_decode()
        // to return a different string, so the hash check fails.
        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $email,
            'password' => $password,
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_AUTHENTICATION_WRONG_CREDENTIALS, $data['data']['error_code']);
    }

    // ─── Refresh token in response ────────────────────────────────────────────

    #[TestDox('Response includes refresh_token when allow_refresh_token is enabled')]
    public function testRefreshTokenIncludedWhenEnabled(): void
    {
        static::configurePlugin(static::baseConfig([
            'allow_refresh_token'  => true,
            'jwt_auth_refresh_ttl' => 20160,
        ]));

        [$email, $password] = $this->createUser();

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $email,
            'password' => $password,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('jwt', $data['data']);
        $this->assertArrayHasKey('refresh_token', $data['data']);
        $this->assertNotEmpty($data['data']['refresh_token']);
    }

    #[TestDox('Response does not include refresh_token when allow_refresh_token is disabled')]
    public function testRefreshTokenAbsentWhenDisabled(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email, $password] = $this->createUser();

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $email,
            'password' => $password,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('jwt', $data['data']);
        $this->assertArrayNotHasKey('refresh_token', $data['data']);
    }

    // ─── Response shape ───────────────────────────────────────────────────────

    #[TestDox('JWT has three dot-separated segments')]
    public function testJwtIsWellFormed(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email, $password] = $this->createUser();

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $email,
            'password' => $password,
        ]);

        $this->assertSame(200, $response->get_status());
        $jwt = $response->get_data()['data']['jwt'];
        $this->assertCount(3, explode('.', $jwt), 'A JWT must have exactly three dot-separated segments');
    }

    #[TestDox('JWT payload contains only the fields listed in jwt_payload config')]
    public function testJwtPayloadContainsConfiguredFields(): void
    {
        static::configurePlugin(static::baseConfig([
            'jwt_payload' => ['email', 'exp', 'id'],
        ]));

        [$email, $password] = $this->createUser();

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $email,
            'password' => $password,
        ]);

        $this->assertSame(200, $response->get_status());
        $jwt = $response->get_data()['data']['jwt'];

        $decoded = (array) JWT::decode($jwt, self::JWT_SECRET, ['HS256']);
        $this->assertArrayHasKey('email', $decoded);
        $this->assertArrayHasKey('exp', $decoded);
        $this->assertArrayHasKey('id', $decoded);
        $this->assertSame($email, $decoded['email']);
        $this->assertGreaterThan(time(), $decoded['exp']);
        $this->assertArrayNotHasKey('username', $decoded);
        $this->assertArrayNotHasKey('site', $decoded);
    }

    #[TestDox('JWT exp is set to now + jwt_auth_ttl minutes')]
    public function testJwtExpiryReflectsTtlConfig(): void
    {
        $ttlMinutes = 30;
        static::configurePlugin(static::baseConfig([
            'jwt_auth_ttl' => $ttlMinutes,
            'jwt_payload'  => ['exp'],
        ]));

        [$email, $password] = $this->createUser();

        $before = time();
        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $email,
            'password' => $password,
        ]);
        $after = time();

        $this->assertSame(200, $response->get_status());
        $jwt     = $response->get_data()['data']['jwt'];
        $decoded = (array) JWT::decode($jwt, self::JWT_SECRET, ['HS256']);

        $expectedMin = $before + ($ttlMinutes * 60);
        $expectedMax = $after  + ($ttlMinutes * 60);

        $this->assertGreaterThanOrEqual($expectedMin, $decoded['exp']);
        $this->assertLessThanOrEqual($expectedMax, $decoded['exp']);
    }
}
