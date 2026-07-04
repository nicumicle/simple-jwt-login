<?php

namespace SimpleJwtLoginTests\WP\ValidateToken;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RevokedToken\RevokedTokenRepository;
use SimpleJwtLoginTests\WP\WPTestCase;

/**
 * Integration tests for the Validate Token endpoint.
 *
 * Route: GET|POST /simple-jwt-login/v1/auth/validate
 *
 * JWT transport: passed as URL/body parameter 'JWT' (captured in $_REQUEST before
 * rest_api_init fires) or via the Authorization: Bearer header.
 *
 * Service flow tested here:
 *   1. checkAuthenticationEnabled()
 *   2. checkValidateTokenEnabled()
 *   3. checkAllowedIPAddress()
 *   4. validateAuthenticationAuthKey()
 *   5. validateAuth() — JWT parsing, user lookup, revoke check, response shape
 */
class ValidateTokenTest extends WPTestCase
{
    private const JWT_SECRET = 'validate-token-test-secret';
    private const ROUTE      = '/simple-jwt-login/v1/auth/validate';
    private const AUTH_KEY   = 'AUTH_KEY';
    private const AUTH_CODE  = 'VALID_VALIDATE_CODE';

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
            'allow_validate_token'        => 1,
            'validate_requires_auth_code' => false,
            'auth_ip'                     => '',
            'decryption_key'              => self::JWT_SECRET,
            'jwt_auth_iss'                => 'tests',
            'jwt_login_by'                => LoginSettings::JWT_LOGIN_BY_EMAIL,
            'jwt_login_by_parameter'      => 'email',
            'jwt_payload'                 => ['email', 'exp', 'id', 'iss', 'site', 'username'],
            'jwt_auth_ttl'                => 60,
            'allow_autologin'             => false,
            'allow_register'              => false,
            'allow_delete'                => false,
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

    #[TestDox('Returns ERR_VALIDATE_TOKEN_NOT_ENABLED when validate endpoint is disabled')]
    public function testValidateTokenDisabledReturnsError(): void
    {
        static::configurePlugin(static::baseConfig(['allow_validate_token' => 0]));

        $response = $this->request('POST', self::ROUTE, ['JWT' => 'any.jwt.token']);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_VALIDATE_TOKEN_NOT_ENABLED, $data['data']['error_code']);
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
                'params'        => [],
                'useExpiredCode' => false,
            ],
            'auth required but wrong AUTH_KEY value' => [
                'params'        => [self::AUTH_KEY => 'WRONG_CODE'],
                'useExpiredCode' => false,
            ],
            'auth required but expired auth code' => [
                'params'        => [self::AUTH_KEY => 'EXPIRED_CODE'],
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
            'validate_requires_auth_code' => true,
            'auth_codes'                  => $authCodes,
            'auth_code_key'               => self::AUTH_KEY,
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
    public function testValidAuthKeyAllowsValidation(): void
    {
        static::configurePlugin(static::baseConfig([
            'validate_requires_auth_code' => true,
            'auth_codes'                  => [
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

    #[TestDox('Auth key not required when validate_requires_auth_code is false')]
    public function testAuthKeyNotRequiredWhenFlagIsFalse(): void
    {
        static::configurePlugin(static::baseConfig([
            'validate_requires_auth_code' => false,
            'auth_codes'                  => [
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

    // ─── Missing JWT ──────────────────────────────────────────────────────────

    #[TestDox('Returns ERR_MISSING_JWT_AUTH_VALIDATE when no JWT is provided')]
    public function testMissingJwtReturnsError(): void
    {
        static::configurePlugin(static::baseConfig());

        $response = $this->request('POST', self::ROUTE, []);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE, $data['data']['error_code']);
    }

    // ─── JWT validation edge cases ────────────────────────────────────────────

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

    #[TestDox('Returns error for expired JWT')]
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

    #[TestDox('Returns ERR_DO_LOGIN_USER_NOT_FOUND when JWT is valid but user does not exist')]
    public function testUserNotFoundReturnsError(): void
    {
        static::configurePlugin(static::baseConfig());

        $jwt = $this->jwtForEmail('ghost-' . time() . '@nowhere.invalid');

        $response = $this->request('POST', self::ROUTE, ['JWT' => $jwt]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND, $data['data']['error_code']);
    }

    // ─── Revoked JWT ──────────────────────────────────────────────────────────

    #[TestDox('Returns ERR_REVOKED_TOKEN when JWT has been revoked')]
    public function testRevokedJwtReturnsError(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email, , $userId] = $this->createUser();
        $jwt = $this->jwtForEmail($email);

        global $wpdb;
        $revokedTokenRepo = new RevokedTokenRepository($wpdb);
        $revokedTokenRepo->insert($userId, hash('sha256', $jwt), null);

        try {
            $response = $this->request('POST', self::ROUTE, ['JWT' => $jwt]);

            $data = $response->get_data();
            $this->assertFalse($data['success']);
            $this->assertSame(ErrorCodes::ERR_REVOKED_TOKEN, $data['data']['error_code']);
        } finally {
            $revokedTokenRepo->deleteByUserId($userId);
        }
    }

    // ─── Success scenarios ────────────────────────────────────────────────────

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function successValidateProvider(): array
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

    #[DataProvider('successValidateProvider')]
    #[TestDox('Successfully validates JWT when user is identified by different payload fields')]
    public function testSuccessfulValidation(
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

        $jwt = $this->makeJwt([$jwtPayloadKey => $payloadValue]);

        $response = $this->request('POST', self::ROUTE, ['JWT' => $jwt]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertSame($email, $data['data']['user']['user_email']);
    }

    #[TestDox('GET method also validates JWT successfully')]
    public function testGetMethodValidatesToken(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email] = $this->createUser();

        $response = $this->request('GET', self::ROUTE, ['JWT' => $this->jwtForEmail($email)]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertSame($email, $data['data']['user']['user_email']);
    }

    #[TestDox('JWT passed via Authorization header is accepted')]
    public function testJwtViaAuthorizationHeaderWorks(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email] = $this->createUser();
        $jwt     = $this->jwtForEmail($email);

        $response = $this->request('POST', self::ROUTE, [], $this->authHeader($jwt));

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertSame($email, $data['data']['user']['user_email']);
    }

    // ─── Response shape ───────────────────────────────────────────────────────

    #[TestDox('Response contains user, roles, and jwt sections')]
    public function testSuccessResponseShape(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email] = $this->createUser();

        $response = $this->request('POST', self::ROUTE, ['JWT' => $this->jwtForEmail($email)]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('user', $data['data']);
        $this->assertArrayHasKey('roles', $data['data']);
        $this->assertArrayHasKey('jwt', $data['data']);
    }

    #[TestDox('Response user object does not expose user_pass')]
    public function testResponseDoesNotExposePasswordHash(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email] = $this->createUser();

        $response = $this->request('POST', self::ROUTE, ['JWT' => $this->jwtForEmail($email)]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertArrayNotHasKey('user_pass', $data['data']['user']);
    }

    #[TestDox('JWT section in response contains token, header, payload, and expire_in fields')]
    public function testJwtSectionContainsExpectedFields(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email] = $this->createUser();
        $jwt     = $this->jwtForEmail($email);

        $response = $this->request('POST', self::ROUTE, ['JWT' => $jwt]);

        $this->assertSame(200, $response->get_status());
        $data    = $response->get_data();
        $jwtInfo = $data['data']['jwt'][0];

        $this->assertSame($jwt, $jwtInfo['token']);
        $this->assertArrayHasKey('header', $jwtInfo);
        $this->assertArrayHasKey('payload', $jwtInfo);
        $this->assertArrayHasKey('expire_in', $jwtInfo);
        $this->assertGreaterThan(0, $jwtInfo['expire_in']);
    }

    #[TestDox('Response email matches the authenticated user')]
    public function testResponseEmailMatchesUser(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email] = $this->createUser();

        $response = $this->request('POST', self::ROUTE, ['JWT' => $this->jwtForEmail($email)]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertSame($email, $data['data']['user']['user_email']);
    }
}
