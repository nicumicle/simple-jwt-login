<?php

namespace SimpleJwtLoginTests\WP\Autologin;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

/**
 * Autologin with jwt_login_by = JWT_LOGIN_BY_WORDPRESS_USER_ID.
 *
 * The JWT payload must contain the WordPress numeric user ID.
 */
class AutologinByUserIdTest extends AutologinTestCase
{
    private const JWT_SECRET = 'autologin-by-id-secret';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::configurePlugin([
            'allow_autologin'         => true,
            'jwt_login_by'            => LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID,
            'jwt_login_by_parameter'  => 'id',
            'decryption_key'          => self::JWT_SECRET,
            'redirect'                => LoginSettings::NO_REDIRECT,
            'require_login_auth'      => false,
            'login_ip'                => '',
            'login_iss'               => '',
            'allow_authentication'    => true,
            'jwt_payload'             => ['id', 'exp'],
            'jwt_auth_ttl'            => 60,
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        delete_option(SimpleJWTLoginSettings::OPTIONS_KEY);
        parent::tearDownAfterClass();
    }

    private function makeJwt(int $userId): string
    {
        return JWT::encode(['id' => $userId], self::JWT_SECRET, 'HS256');
    }

    #[TestDox('Autologin returns user-not-found when the user ID in the JWT does not exist')]
    public function testUserNotFoundById(): void
    {
        $response = $this->request('GET', self::ROUTE, [
            'JWT' => $this->makeJwt(999999),
        ]);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND, $data['data']['errorCode']);
    }

    #[TestDox('Autologin succeeds when the JWT user ID matches a real WordPress user')]
    public function testSuccessLoginByUserId(): void
    {
        [, , $userId] = $this->createUser();

        $response = $this->request('GET', self::ROUTE, [
            'JWT' => $this->makeJwt($userId),
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertSame('User was logged in.', $data['message']);
    }
}
