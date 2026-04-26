<?php

namespace SimpleJwtLoginTests\WP\Autologin;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJwtLoginTests\WP\WPTestCase;

/**
 * IP allowlist enforcement on the autologin endpoint.
 *
 * Config: login_ip = '10.0.0.1,10.0.0.2' — only those IPs are permitted.
 * In the test CLI environment REMOTE_ADDR / HTTP_CLIENT_IP are not set,
 * so getClientIP() returns null, which is not in the allowlist.
 */
class AutologinIpRestrictionTest extends WPTestCase
{
    private const JWT_SECRET = 'autologin-ip-secret';
    private const ROUTE      = '/simple-jwt-login/v1/autologin';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::configurePlugin([
            'allow_autologin'         => true,
            'jwt_login_by'            => LoginSettings::JWT_LOGIN_BY_EMAIL,
            'jwt_login_by_parameter'  => 'email',
            'decryption_key'          => self::JWT_SECRET,
            'redirect'                => LoginSettings::NO_REDIRECT,
            'require_login_auth'      => false,
            'login_ip'                => '10.0.0.1,10.0.0.2',
            'login_iss'               => '',
            'allow_authentication'    => true,
            'jwt_payload'             => ['email', 'exp'],
            'jwt_auth_ttl'            => 60,
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        delete_option(SimpleJWTLoginSettings::OPTIONS_KEY);
        parent::tearDownAfterClass();
    }

    #[TestDox('Autologin rejects a request when the client IP is not in the allowlist')]
    public function testIpNotInAllowlistIsBlocked(): void
    {
        // In CLI test context REMOTE_ADDR is not set → getClientIP() returns null,
        // which is not in '10.0.0.1,10.0.0.2', so the request is blocked.
        $jwt = JWT::encode(['email' => 'anyone@example.com'], self::JWT_SECRET, 'HS256');

        $response = $this->request('GET', self::ROUTE, ['JWT' => $jwt]);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(ErrorCodes::ERR_IP_IS_NOT_ALLOWED_TO_LOGIN, $data['data']['errorCode']);
    }
}
