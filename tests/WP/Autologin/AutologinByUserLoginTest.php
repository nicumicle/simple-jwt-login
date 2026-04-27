<?php

namespace SimpleJwtLoginTests\WP\Autologin;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

/**
 * Autologin with jwt_login_by = JWT_LOGIN_BY_USER_LOGIN.
 *
 * The JWT payload must contain the user's user_login (WordPress username).
 */
class AutologinByUserLoginTest extends AutologinTestCase
{
    private const JWT_SECRET = 'autologin-by-login-secret';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::configurePlugin([
            'allow_autologin'         => true,
            'jwt_login_by'            => LoginSettings::JWT_LOGIN_BY_USER_LOGIN,
            'jwt_login_by_parameter'  => 'username',
            'decryption_key'          => self::JWT_SECRET,
            'redirect'                => LoginSettings::NO_REDIRECT,
            'require_login_auth'      => false,
            'login_ip'                => '',
            'login_iss'               => '',
            'allow_authentication'    => true,
            'jwt_payload'             => ['username', 'exp'],
            'jwt_auth_ttl'            => 60,
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        delete_option(SimpleJWTLoginSettings::OPTIONS_KEY);
        parent::tearDownAfterClass();
    }

    private function makeJwt(string $username): string
    {
        return JWT::encode(['username' => $username], self::JWT_SECRET, 'HS256');
    }

    #[TestDox('Autologin returns user-not-found when the username in the JWT does not exist')]
    public function testUserNotFoundByLogin(): void
    {
        $response = $this->request('GET', self::ROUTE, [
            'JWT' => $this->makeJwt('ghost_user_that_does_not_exist'),
        ]);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND, $data['data']['errorCode']);
    }

    #[TestDox('Autologin succeeds when the JWT username matches a real WordPress user_login')]
    public function testSuccessLoginByUserLogin(): void
    {
        [, , $userId] = $this->createUser();
        $login = get_userdata($userId)->user_login;

        $response = $this->request('GET', self::ROUTE, [
            'JWT' => $this->makeJwt($login),
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertSame('User was logged in.', $data['message']);
    }
}
