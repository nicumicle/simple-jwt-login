<?php

namespace SimpleJwtLoginTests\WP\Autologin;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJwtLoginTests\WP\WPTestCase;

/**
 * Auth-code (AUTH_KEY) enforcement on the autologin endpoint.
 *
 * Config: autologin enabled, require_login_auth = true, one valid auth code.
 */
class AutologinAuthCodeTest extends WPTestCase
{
    private const JWT_SECRET  = 'autologin-authcode-secret';
    private const VALID_CODE  = 'super-secret-auth-code';
    private const ROUTE       = '/simple-jwt-login/v1/autologin';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::configurePlugin([
            'allow_autologin'         => true,
            'jwt_login_by'            => LoginSettings::JWT_LOGIN_BY_EMAIL,
            'jwt_login_by_parameter'  => 'email',
            'decryption_key'          => self::JWT_SECRET,
            'redirect'                => LoginSettings::NO_REDIRECT,
            'require_login_auth'      => true,
            'login_ip'                => '',
            'login_iss'               => '',
            'allow_authentication'    => true,
            'jwt_payload'             => ['email', 'exp'],
            'jwt_auth_ttl'            => 60,
            'auth_codes' => [
                ['code' => self::VALID_CODE, 'role' => '', 'expiration_date' => ''],
            ],
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        delete_option(SimpleJWTLoginSettings::OPTIONS_KEY);
        parent::tearDownAfterClass();
    }

    private function makeJwt(string $email): string
    {
        return JWT::encode(['email' => $email], self::JWT_SECRET, 'HS256');
    }

    // ─── Invalid auth code scenarios ─────────────────────────────────────────

    #[DataProvider('invalidAuthCodeProvider')]
    #[TestDox('Autologin rejects requests with a missing or wrong auth code')]
    public function testInvalidAuthCode(array $params): void
    {
        $response = $this->request('GET', self::ROUTE, $params);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_INVALID_AUTH_CODE_PROVIDED, $data['data']['errorCode']);
    }

    public static function invalidAuthCodeProvider(): array
    {
        $jwt = JWT::encode(['email' => 'fixture@example.com'], self::JWT_SECRET, 'HS256');

        return [
            'auth code absent' => [
                'params' => ['JWT' => $jwt],
            ],
            'auth code is wrong' => [
                'params' => ['JWT' => $jwt, 'AUTH_KEY' => 'wrong-code'],
            ],
            'auth code is empty string' => [
                'params' => ['JWT' => $jwt, 'AUTH_KEY' => ''],
            ],
        ];
    }

    // ─── Valid auth code ──────────────────────────────────────────────────────

    #[TestDox('Autologin succeeds when the correct auth code accompanies the JWT')]
    public function testValidAuthCodeAllowsLogin(): void
    {
        [$email] = $this->createUser();

        $response = $this->request('GET', self::ROUTE, [
            'JWT'      => $this->makeJwt($email),
            'AUTH_KEY' => self::VALID_CODE,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertSame('User was logged in.', $data['message']);
    }
}
