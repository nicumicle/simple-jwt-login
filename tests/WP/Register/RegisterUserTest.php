<?php

namespace SimpleJwtLoginTests\WP\Register;

use Faker\Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJwtLoginTests\WP\WPTestCase;

class RegisterUserTest extends WPTestCase
{
    private const JWT_SECRET = 'register-test-secret';
    private const ROUTE      = '/simple-jwt-login/v1/users';

    private static function baseConfig(array $overrides = []): array
    {
        return array_merge([
            'allow_register'         => true,
            'new_user_profile'       => 'subscriber',
            'register_ip'            => '',
            'register_domain'        => '',
            'require_register_auth'  => false,
            'random_password'        => false,
            'random_password_length' => 10,
            'register_force_login'   => false,
            'register_jwt'           => false,
            'allowed_user_meta'      => '',
            'decryption_key'         => self::JWT_SECRET,
            'jwt_auth_iss'           => 'tests',
            'allow_autologin'        => false,
            'allow_authentication'   => false,
        ], $overrides);
    }

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

    // ─── Success scenarios ───────────────────────────────────────────────────

    #[TestDox('Registers a new user and returns id, user data, and roles')]
    public function testRegisterSuccess(): void
    {
        static::configurePlugin(static::baseConfig());

        $faker    = Factory::create();
        $email    = $faker->randomNumber(6) . $faker->email();
        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $email,
            'password' => 'password123',
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data['data']);
        $this->assertArrayHasKey('roles', $data['data']);
        $this->assertNotEmpty($data['data']['id']);
        $this->assertSame($email, $data['data']['email']);
        $this->assertContains('subscriber', $data['data']['roles']);
    }

    #[TestDox('Response never exposes the user password')]
    public function testResponseOmitsPassword(): void
    {
        static::configurePlugin(static::baseConfig());

        $faker    = Factory::create();
        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $faker->randomNumber(6) . $faker->email(),
            'password' => 'secret123',
        ]);

        $this->assertSame(200, $response->get_status());
        $this->assertArrayNotHasKey('pass', $response->get_data()['data']);
    }

    #[TestDox('New user receives the role configured in new_user_profile')]
    public function testRegisterWithCustomRole(): void
    {
        static::configurePlugin(static::baseConfig(['new_user_profile' => 'editor']));

        $faker    = Factory::create();
        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $faker->randomNumber(6) . $faker->email(),
            'password' => 'password123',
        ]);

        $this->assertSame(200, $response->get_status());
        $this->assertContains('editor', $response->get_data()['data']['roles']);
    }

    #[TestDox('Registration succeeds without a password when random_password is enabled')]
    public function testRegisterWithRandomPassword(): void
    {
        static::configurePlugin(static::baseConfig([
            'random_password'        => true,
            'random_password_length' => 12,
        ]));

        $faker    = Factory::create();
        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email' => $faker->randomNumber(6) . $faker->email(),
        ]);

        $this->assertSame(200, $response->get_status());
        $this->assertTrue($response->get_data()['success']);
    }

    #[TestDox('Response includes a JWT token when register_jwt is enabled')]
    public function testRegisterWithJwtEnabled(): void
    {
        static::configurePlugin(static::baseConfig(['register_jwt' => true]));

        $faker    = Factory::create();
        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $faker->randomNumber(6) . $faker->email(),
            'password' => 'password123',
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('jwt', $data['data']);
        $this->assertNotEmpty($data['data']['jwt']);
    }

    #[TestDox('Allowed user_meta keys are persisted on the new user')]
    public function testRegisterWithAllowedUserMeta(): void
    {
        static::configurePlugin(static::baseConfig(['allowed_user_meta' => 'first_name, last_name']));

        $faker     = Factory::create();
        $firstName = $faker->firstName();
        $lastName  = $faker->lastName();
        $response  = $this->jsonRequest('POST', self::ROUTE, [
            'email'     => $faker->randomNumber(6) . $faker->email(),
            'password'  => 'password123',
            'user_meta' => [
                'first_name' => $firstName,
                'last_name'  => $lastName,
            ],
        ]);

        $this->assertSame(200, $response->get_status());
        $userId = $response->get_data()['data']['id'];

        clean_user_cache($userId);

        $this->assertSame($firstName, get_user_meta($userId, 'first_name', true));
        $this->assertSame($lastName, get_user_meta($userId, 'last_name', true));
    }

    #[TestDox('user_meta keys absent from the allowed list are silently dropped')]
    public function testRegisterSkipsDisallowedUserMeta(): void
    {
        static::configurePlugin(static::baseConfig(['allowed_user_meta' => 'first_name']));

        $faker    = Factory::create();
        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'     => $faker->randomNumber(6) . $faker->email(),
            'password'  => 'password123',
            'user_meta' => [
                'first_name' => 'Allowed',
                'last_name'  => 'ShouldBeDropped',
            ],
        ]);

        $this->assertSame(200, $response->get_status());
        $userId = $response->get_data()['data']['id'];

        clean_user_cache($userId);

        $this->assertSame('Allowed', get_user_meta($userId, 'first_name', true));
        $this->assertEmpty(get_user_meta($userId, 'last_name', true));
    }

    #[TestDox('Auth code with a role overrides the default new_user_profile')]
    public function testAuthCodeOverridesUserRole(): void
    {
        static::configurePlugin(static::baseConfig([
            'require_register_auth' => false,
            'auth_codes'            => [
                ['code' => 'EDITOR_CODE', 'role' => 'editor', 'expiration_date' => ''],
            ],
            'auth_code_key'         => 'AUTH_KEY',
        ]));

        $faker    = Factory::create();
        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $faker->randomNumber(6) . $faker->email(),
            'password' => 'password123',
            'AUTH_KEY' => 'EDITOR_CODE',
        ]);

        $this->assertSame(200, $response->get_status());
        $this->assertContains('editor', $response->get_data()['data']['roles']);
    }

    #[TestDox('Email from an allowed domain is accepted')]
    public function testAllowedDomainAcceptsMatchingEmail(): void
    {
        static::configurePlugin(static::baseConfig(['register_domain' => 'example.com']));

        $faker    = Factory::create();
        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => 'user' . $faker->randomNumber(5) . '@example.com',
            'password' => 'password123',
        ]);

        $this->assertSame(200, $response->get_status());
        $this->assertTrue($response->get_data()['success']);
    }

    #[TestDox('Valid auth code grants access when auth is required')]
    public function testValidAuthCodeGrantsAccess(): void
    {
        static::configurePlugin(static::baseConfig([
            'require_register_auth' => true,
            'auth_codes'            => [
                ['code' => 'VALID_CODE', 'role' => '', 'expiration_date' => ''],
            ],
            'auth_code_key'         => 'AUTH_KEY',
        ]));

        $faker    = Factory::create();
        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $faker->randomNumber(6) . $faker->email(),
            'password' => 'password123',
            'AUTH_KEY' => 'VALID_CODE',
        ]);

        $this->assertSame(200, $response->get_status());
        $this->assertTrue($response->get_data()['success']);
    }

    // ─── Validation / error scenarios ────────────────────────────────────────

    #[TestDox('Returns ERR_REGISTER_IS_NOT_ALLOWED when register is disabled')]
    public function testRegisterNotAllowed(): void
    {
        static::configurePlugin(static::baseConfig(['allow_register' => false]));

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => 'user@example.com',
            'password' => 'password123',
        ]);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_REGISTER_IS_NOT_ALLOWED, $data['data']['error_code']);
    }

    public static function missingCredentialsProvider(): array
    {
        return [
            'no email, no password' => [
                'params'       => [],
                'expectedCode' => ErrorCodes::ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD,
            ],
            'password only' => [
                'params'       => ['password' => 'pass123'],
                'expectedCode' => ErrorCodes::ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD,
            ],
            'email only (no password, random_password disabled)' => [
                'params'       => ['email' => 'user@example.com'],
                'expectedCode' => ErrorCodes::ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD,
            ],
        ];
    }

    #[DataProvider('missingCredentialsProvider')]
    #[TestDox('Returns ERR_REGISTER_MISSING_EMAIL_OR_PASSWORD for missing credentials')]
    public function testMissingCredentials(array $params, int $expectedCode): void
    {
        static::configurePlugin(static::baseConfig());

        $response = $this->jsonRequest('POST', self::ROUTE, $params);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame($expectedCode, $data['data']['error_code']);
    }

    public static function invalidEmailProvider(): array
    {
        return [
            'plain string'    => ['notanemail'],
            'missing domain'  => ['user@'],
            'missing at-sign' => ['userdomain.com'],
        ];
    }

    #[DataProvider('invalidEmailProvider')]
    #[TestDox('Returns ERR_REGISTER_INVALID_EMAIL_ADDRESS for a malformed email')]
    public function testInvalidEmail(string $email): void
    {
        static::configurePlugin(static::baseConfig());

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $email,
            'password' => 'pass123',
        ]);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_REGISTER_INVALID_EMAIL_ADDRESS, $data['data']['error_code']);
    }

    #[TestDox('Returns ERR_REGISTER_DOMAIN_FOR_USER when email domain is not in the allowlist')]
    public function testBlockedDomain(): void
    {
        static::configurePlugin(static::baseConfig(['register_domain' => 'allowed.com']));

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => 'user@notallowed.com',
            'password' => 'password123',
        ]);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_REGISTER_DOMAIN_FOR_USER, $data['data']['error_code']);
    }

    #[TestDox('Returns ERR_REGISTER_INVALID_AUTH_KEY when auth is required but not provided')]
    public function testAuthCodeRequiredButMissing(): void
    {
        static::configurePlugin(static::baseConfig([
            'require_register_auth' => true,
            'auth_codes'            => [
                ['code' => 'SECRET', 'role' => '', 'expiration_date' => ''],
            ],
            'auth_code_key'         => 'AUTH_KEY',
        ]));

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => 'user@example.com',
            'password' => 'password123',
        ]);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_REGISTER_INVALID_AUTH_KEY, $data['data']['error_code']);
    }

    #[TestDox('Returns ERR_REGISTER_INVALID_AUTH_KEY when a wrong auth code is provided')]
    public function testInvalidAuthCode(): void
    {
        static::configurePlugin(static::baseConfig([
            'require_register_auth' => true,
            'auth_codes'            => [
                ['code' => 'CORRECT_CODE', 'role' => '', 'expiration_date' => ''],
            ],
            'auth_code_key'         => 'AUTH_KEY',
        ]));

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => 'user@example.com',
            'password' => 'password123',
            'AUTH_KEY' => 'WRONG_CODE',
        ]);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_REGISTER_INVALID_AUTH_KEY, $data['data']['error_code']);
    }

    #[TestDox('Returns ERR_REGISTER_INVALID_AUTH_KEY when the auth code is past its expiration date')]
    public function testExpiredAuthCode(): void
    {
        static::configurePlugin(static::baseConfig([
            'require_register_auth' => true,
            'auth_codes'            => [
                ['code' => 'OLD_CODE', 'role' => '', 'expiration_date' => '2000-01-01'],
            ],
            'auth_code_key'         => 'AUTH_KEY',
        ]));

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => 'user@example.com',
            'password' => 'password123',
            'AUTH_KEY' => 'OLD_CODE',
        ]);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_REGISTER_INVALID_AUTH_KEY, $data['data']['error_code']);
    }

    #[TestDox('Returns ERR_REGISTER_USER_ALREADY_EXISTS when the email is already registered')]
    public function testUserAlreadyExists(): void
    {
        static::configurePlugin(static::baseConfig());

        [$email] = $this->createUser();

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => $email,
            'password' => 'newpassword123',
        ]);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_REGISTER_USER_ALREADY_EXISTS, $data['data']['error_code']);
    }

    #[TestDox('Returns ERR_REGISTER_IP_IS_NOT_ALLOWED when the client IP is not in the allowlist')]
    public function testBlockedIp(): void
    {
        static::configurePlugin(static::baseConfig(['register_ip' => '10.0.0.1']));

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'email'    => 'user@example.com',
            'password' => 'password123',
        ]);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_REGISTER_IP_IS_NOT_ALLOWED, $data['data']['error_code']);
    }
}
