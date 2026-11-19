<?php

namespace SimpleJwtLoginTests\Feature\Authentication;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * /auth must go through the WordPress login checks, so brute-force and user-block
 * plugins (hooked on "authenticate", "wp_authenticate_user" and "wp_login_failed")
 * protect it like wp-login.php.
 *
 * tests/Feature/fixtures/sjl-test-login-guard.php plays such a plugin: it is copied
 * to mu-plugins for this class and only acts while the "sjl_test_login_guard" option is set.
 */
class LoginProtectionTest extends FeatureTestCase
{
    private const MU_PLUGINS_DIR = '/var/www/html/wp-content/mu-plugins';
    private const GUARD_FILE     = 'sjl-test-login-guard.php';
    private const GUARD_OPTION   = 'sjl_test_login_guard';
    private const FAILED_OPTION  = 'sjl_test_login_failed';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption([
            'allow_authentication'       => true,
            'auth_password_hash_enabled' => true,
            'jwt_payload'                => ['email', 'exp', 'id', 'iss', 'site', 'username'],
            'jwt_auth_ttl'               => 60,
            'jwt_auth_iss'               => 'tests',
            'decryption_key'             => 'test-secret',
            'allow_register'             => true,
            'new_user_profile'           => 'subscriber',
            'require_register_auth'      => false,
        ]);
        if (!is_dir(self::MU_PLUGINS_DIR)) {
            mkdir(self::MU_PLUGINS_DIR, 0777, true);
        }
        copy(__DIR__ . '/../fixtures/' . self::GUARD_FILE, self::MU_PLUGINS_DIR . '/' . self::GUARD_FILE);
    }

    public static function tearDownAfterClass(): void
    {
        // With process isolation this also runs in every test process.
        if (is_file(self::MU_PLUGINS_DIR . '/' . self::GUARD_FILE)) {
            unlink(self::MU_PLUGINS_DIR . '/' . self::GUARD_FILE);
        }
        self::deleteWpOption(self::GUARD_OPTION);
        self::deleteWpOption(self::FAILED_OPTION);
        parent::tearDownAfterClass();
    }

    public function setUp(): void
    {
        parent::setUp();
        self::setWpOption(self::FAILED_OPTION, '[]');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function lockingHookProvider(): array
    {
        return [
            'lockout plugin on authenticate'        => ['authenticate'],
            'user-block plugin on wp_authenticate_user' => ['wp_authenticate_user'],
        ];
    }

    #[DataProvider('lockingHookProvider')]
    #[TestDox('A user blocked by a login protection plugin can not get a JWT, even with the right password')]
    public function testBlockedUserCanNotAuthenticate(string $hook): void
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');
        self::setWpOption(self::GUARD_OPTION, json_encode([$hook => [md5($email)]]));

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
            'email'    => $email,
            'password' => $password,
        ]);

        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertSame(ErrorCodes::ERR_AUTHENTICATION_WRONG_CREDENTIALS, $body['data']['error_code']);
    }

    #[TestDox('A user that is not blocked still gets a JWT while a login protection plugin is active')]
    public function testUserThatIsNotBlockedCanAuthenticate(): void
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');
        self::setWpOption(self::GUARD_OPTION, json_encode(['authenticate' => ['someone-else']]));

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
            'email'    => $email,
            'password' => $password,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], json_decode((string) $this->getWpOptionValue(self::FAILED_OPTION), true));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function failedLoginProvider(): array
    {
        return [
            'wrong password'      => ['password'],
            'wrong password_hash' => ['password_hash'],
            'unknown user'        => ['unknown'],
        ];
    }

    #[DataProvider('failedLoginProvider')]
    #[TestDox('A failed /auth attempt is reported on wp_login_failed, so lockout plugins can count it')]
    public function testFailedLoginIsReported(string $case): void
    {
        [$email, , $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');
        self::setWpOption(self::GUARD_OPTION, json_encode(['authenticate' => []]));

        $username = $case === 'unknown' ? 'unknown_' . uniqid() : md5($email);
        $credential = $case === 'password_hash' ? 'password_hash' : 'password';
        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
            'username'  => $username,
            $credential => 'wrong-' . uniqid(),
        ]);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(
            [$username],
            json_decode((string) $this->getWpOptionValue(self::FAILED_OPTION), true)
        );
    }

    /**
     * @param string $optionKey
     * @param string $optionValue
     * @return void
     */
    private static function setWpOption(string $optionKey, string $optionValue): void
    {
        $value = self::$dbCon->real_escape_string($optionValue);
        self::$dbCon->query(sprintf(
            "INSERT INTO %soptions (option_name, option_value, autoload) VALUES ('%s', '%s', 'off')"
                . " ON DUPLICATE KEY UPDATE option_value = '%s';",
            self::getTablePrefix(),
            $optionKey,
            $value,
            $value
        ));
    }

    /**
     * @param string $optionKey
     * @return void
     */
    private static function deleteWpOption(string $optionKey): void
    {
        self::$dbCon->query(sprintf(
            "DELETE FROM %soptions WHERE option_name = '%s';",
            self::getTablePrefix(),
            $optionKey
        ));
    }
}
