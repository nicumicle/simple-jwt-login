<?php

namespace SimpleJwtLoginTests\WP\DeleteUser;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJwtLoginTests\WP\WPTestCase;

/**
 * Integration tests for the Delete User endpoint.
 *
 * Route: DELETE /simple-jwt-login/v1/users
 *
 * JWT transport: passed as URL parameter 'JWT' (captured in $_REQUEST before
 * rest_api_init fires). Header transport is tested separately for the success
 * path.
 *
 * The delete endpoint is destructive — each success scenario creates a fresh
 * user so the teardown rollback has nothing extra to clean up.
 */
class DeleteUserTest extends WPTestCase
{
    private const JWT_SECRET = 'delete-user-test-secret';
    private const ROUTE      = '/simple-jwt-login/v1/users';
    private const AUTH_KEY   = 'AUTH_KEY';
    private const AUTH_CODE  = 'VALID_DELETE_CODE';

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

    private static function baseConfig(array $overrides = []): array
    {
        return array_merge([
            'allow_delete'           => true,
            'require_delete_auth'    => false,
            'delete_ip'              => '',
            'decryption_key'         => self::JWT_SECRET,
            'jwt_auth_iss'           => 'tests',
            'jwt_login_by'           => LoginSettings::JWT_LOGIN_BY_EMAIL,
            'jwt_login_by_parameter' => 'email',
            'allow_autologin'        => false,
            'allow_authentication'   => false,
            'allow_register'         => false,
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

    private function jwtForUserId(int $userId): string
    {
        return $this->makeJwt(['id' => $userId]);
    }

    private function jwtForUserLogin(string $login): string
    {
        return $this->makeJwt(['login' => $login]);
    }

    // ─── Feature disabled ─────────────────────────────────────────────────────

    #[TestDox('Returns ERR_DELETE_IS_NOT_ENABLED when delete is disabled')]
    public function testDeleteDisabledReturnsError(): void
    {
        static::configurePlugin(static::baseConfig(['allow_delete' => false]));

        [$email] = $this->createUser();

        $response = $this->request('DELETE', self::ROUTE, [
            'JWT' => $this->jwtForEmail($email),
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_DELETE_IS_NOT_ENABLED, $data['data']['errorCode']);
    }

    // ─── Missing JWT ──────────────────────────────────────────────────────────

    #[TestDox('Returns ERR_DELETE_MISSING_JWT when no JWT is provided')]
    public function testMissingJwtReturnsError(): void
    {
        static::configurePlugin(static::baseConfig());

        $response = $this->request('DELETE', self::ROUTE, []);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_DELETE_MISSING_JWT, $data['data']['errorCode']);
    }

    // ─── Auth key validation ──────────────────────────────────────────────────

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function authKeyProvider(): array
    {
        return [
            'auth required but no AUTH_KEY param provided' => [
                'params'       => [],
                'expectedCode' => ErrorCodes::ERR_DELETE_MISSING_AUTH_KEY,
            ],
            'auth required but wrong AUTH_KEY value' => [
                'params'       => [self::AUTH_KEY => 'WRONG_CODE'],
                'expectedCode' => ErrorCodes::ERR_DELETE_MISSING_AUTH_KEY,
            ],
            'auth required and expired code' => [
                'params'       => [self::AUTH_KEY => 'EXPIRED_CODE'],
                'expectedCode' => ErrorCodes::ERR_DELETE_MISSING_AUTH_KEY,
                'useExpiredCode' => true,
            ],
        ];
    }

    #[DataProvider('authKeyProvider')]
    #[TestDox('Returns ERR_DELETE_MISSING_AUTH_KEY for invalid auth key scenarios')]
    public function testAuthKeyValidation(array $params, int $expectedCode, bool $useExpiredCode = false): void
    {
        $authCodes = $useExpiredCode
            ? [['code' => 'EXPIRED_CODE', 'role' => '', 'expiration_date' => '2000-01-01']]
            : [['code' => self::AUTH_CODE, 'role' => '', 'expiration_date' => '']];

        static::configurePlugin(static::baseConfig([
            'require_delete_auth' => true,
            'auth_codes'          => $authCodes,
            'auth_code_key'       => self::AUTH_KEY,
        ]));

        [$email] = $this->createUser();

        $response = $this->request('DELETE', self::ROUTE, array_merge(
            ['JWT' => $this->jwtForEmail($email)],
            $params
        ));

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame($expectedCode, $data['data']['errorCode']);
    }

    #[TestDox('Proceeds past auth key check when valid AUTH_KEY is provided')]
    public function testValidAuthKeyAllowsDeletion(): void
    {
        static::configurePlugin(static::baseConfig([
            'require_delete_auth' => true,
            'auth_codes'          => [
                ['code' => self::AUTH_CODE, 'role' => '', 'expiration_date' => ''],
            ],
            'auth_code_key' => self::AUTH_KEY,
        ]));

        [$email] = $this->createUser();

        $response = $this->request('DELETE', self::ROUTE, [
            'JWT'          => $this->jwtForEmail($email),
            self::AUTH_KEY => self::AUTH_CODE,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertSame('User was successfully deleted.', $data['message']);
    }

    // ─── IP restriction ───────────────────────────────────────────────────────

    #[TestDox('Returns ERR_DELETE_INVALID_CLIENT_IP when client IP is not in allowed list')]
    public function testIpRestrictionBlocksDisallowedIp(): void
    {
        static::configurePlugin(static::baseConfig([
            'delete_ip' => '192.0.2.1,192.0.2.2',
        ]));

        [$email] = $this->createUser();

        $response = $this->request('DELETE', self::ROUTE, [
            'JWT' => $this->jwtForEmail($email),
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_DELETE_INVALID_CLIENT_IP, $data['data']['errorCode']);
    }

    // ─── JWT validation edge cases ────────────────────────────────────────────

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function invalidJwtProvider(): array
    {
        return [
            'malformed token (not a JWT)' => [
                'jwt'      => 'not.a.valid.jwt.token',
            ],
            'token signed with wrong secret' => [
                'jwt' => JWT::encode(['email' => 'user@example.com', 'exp' => time() + 3600], 'wrong-secret', 'HS256'),
            ],
        ];
    }

    #[DataProvider('invalidJwtProvider')]
    #[TestDox('Returns error for invalid JWT tokens')]
    public function testInvalidJwtReturnsError(string $jwt): void
    {
        static::configurePlugin(static::baseConfig());

        $response = $this->request('DELETE', self::ROUTE, [
            'JWT' => $jwt,
        ]);

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

        $response = $this->request('DELETE', self::ROUTE, [
            'JWT' => $expiredJwt,
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
    }

    // ─── User not found ───────────────────────────────────────────────────────

    #[TestDox('Returns ERR_DO_LOGIN_USER_NOT_FOUND when JWT is valid but user does not exist')]
    public function testUserNotFoundReturnsError(): void
    {
        static::configurePlugin(static::baseConfig());

        $jwt = $this->jwtForEmail('ghost-' . time() . '@nowhere.invalid');

        $response = $this->request('DELETE', self::ROUTE, [
            'JWT' => $jwt,
        ]);

        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND, $data['data']['errorCode']);
    }

    // ─── Revoked JWT ──────────────────────────────────────────────────────────

    #[TestDox('Returns ERR_REVOKED_TOKEN when JWT has been revoked')]
    public function testRevokedJwtReturnsError(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email, , $userId] = $this->createUser();
        $jwt = $this->jwtForEmail($email);

        add_user_meta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY, $jwt);

        try {
            $response = $this->request('DELETE', self::ROUTE, [
                'JWT' => $jwt,
            ]);

            $data = $response->get_data();
            $this->assertFalse($data['success']);
            $this->assertSame(ErrorCodes::ERR_REVOKED_TOKEN, $data['data']['errorCode']);
        } finally {
            delete_user_meta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY);
        }
    }

    // ─── Success scenarios ────────────────────────────────────────────────────

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function successDeleteProvider(): array
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

    #[DataProvider('successDeleteProvider')]
    #[TestDox('Successfully deletes user when identified by different JWT payload fields')]
    public function testSuccessfulDeletion(
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
        $this->assertNotFalse($user, 'Pre-condition: user must exist before deletion');

        $payloadValue = match ($loginBy) {
            LoginSettings::JWT_LOGIN_BY_EMAIL          => $email,
            LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID => $userId,
            LoginSettings::JWT_LOGIN_BY_USER_LOGIN     => $user->user_login,
        };

        $jwt = $this->makeJwt([$jwtPayloadKey => $payloadValue]);

        $response = $this->request('DELETE', self::ROUTE, [
            'JWT' => $jwt,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertSame('User was successfully deleted.', $data['message']);
        $this->assertNotEmpty($data['id']);

        $this->assertFalse(
            get_user_by('id', $userId),
            'User must no longer exist in the database after deletion'
        );
    }

    #[TestDox('Deletion response contains message and id fields')]
    public function testSuccessResponseShape(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email] = $this->createUser();

        $response = $this->request('DELETE', self::ROUTE, [
            'JWT' => $this->jwtForEmail($email),
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('User was successfully deleted.', $data['message']);
    }

    #[TestDox('JWT passed via Authorization header also works for deletion')]
    public function testJwtViaAuthorizationHeaderWorks(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email] = $this->createUser();
        $jwt     = $this->jwtForEmail($email);

        $response = $this->request(
            'DELETE',
            self::ROUTE,
            [],
            $this->authHeader($jwt)
        );

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertSame('User was successfully deleted.', $data['message']);
    }

    #[TestDox('Auth not required when require_delete_auth is false, even without auth key')]
    public function testAuthNotRequiredWhenFlagIsFalse(): void
    {
        static::configurePlugin(static::baseConfig([
            'require_delete_auth' => false,
            'auth_codes'          => [
                ['code' => self::AUTH_CODE, 'role' => '', 'expiration_date' => ''],
            ],
            'auth_code_key' => self::AUTH_KEY,
        ]));

        [$email] = $this->createUser();

        $response = $this->request('DELETE', self::ROUTE, [
            'JWT' => $this->jwtForEmail($email),
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertSame('User was successfully deleted.', $data['message']);
    }
}
