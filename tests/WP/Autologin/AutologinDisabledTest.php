<?php

namespace SimpleJwtLoginTests\WP\Autologin;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

/**
 * Verifies that every autologin request is rejected when the feature is disabled.
 */
class AutologinDisabledTest extends AutologinTestCase
{
    private const JWT_SECRET = 'autologin-disabled-secret';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::configurePlugin([
            'allow_autologin'  => false,
            'decryption_key'   => self::JWT_SECRET,
            'jwt_login_by'     => LoginSettings::JWT_LOGIN_BY_EMAIL,
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        delete_option(SimpleJWTLoginSettings::OPTIONS_KEY);
        parent::tearDownAfterClass();
    }

    #[TestDox('Autologin returns ERR_AUTO_LOGIN_NOT_ENABLED when the feature is turned off')]
    public function testAutologinDisabledReturnsError(): void
    {
        $jwt = JWT::encode(['email' => 'anyone@example.com'], self::JWT_SECRET, 'HS256');

        $response = $this->request('GET', self::ROUTE, ['JWT' => $jwt]);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_AUTO_LOGIN_NOT_ENABLED, $data['data']['error_code']);
    }

    #[TestDox('Autologin returns ERR_AUTO_LOGIN_NOT_ENABLED even when no JWT is sent')]
    public function testAutologinDisabledWithNoJwt(): void
    {
        $response = $this->request('GET', self::ROUTE, []);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_AUTO_LOGIN_NOT_ENABLED, $data['data']['error_code']);
    }
}
