<?php

namespace SimpleJwtLoginTests\WP\Authentication;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJwtLoginTests\WP\WPTestCase;

class AuthTest extends WPTestCase
{
    private const JWT_SECRET = 'test-secret-key';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::configurePlugin([
            'allow_authentication'    => true,
            'jwt_payload'             => ['email', 'exp', 'id', 'iss', 'site', 'username'],
            'jwt_auth_ttl'            => 60,
            'jwt_auth_refresh_ttl'    => '20160',
            'auth_ip'                 => '',
            'auth_requires_auth_code' => false,
            'auth_password_base64'    => false,
            'jwt_auth_iss'            => 'tests',
            'decryption_key'          => self::JWT_SECRET,
            'jwt_login_by'            => 0,
            'jwt_login_by_parameter'  => 'email',
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        delete_option(SimpleJWTLoginSettings::OPTIONS_KEY);
        parent::tearDownAfterClass();
    }

    #[TestDox('Auth endpoint returns a signed JWT for valid credentials')]
    public function testAuthReturnsJwt(): void
    {
        [$email, $password] = $this->createUser();

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
            'email'    => $email,
            'password' => $password,
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('jwt', $data['data']);
    }

    #[TestDox('Auth endpoint rejects wrong password')]
    public function testWrongPasswordIsRejected(): void
    {
        [$email] = $this->createUser();

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
            'email'    => $email,
            'password' => 'wrong-password',
        ]);

        // StatusCodeHelper maps AUTHENTICATION_WRONG_CREDENTIALS (code 48) to 400.
        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertSame(48, $data['data']['errorCode']);
    }

    #[TestDox('Validate endpoint confirms a freshly issued JWT')]
    public function testValidateAcceptsValidJwt(): void
    {
        [$email, $password] = $this->createUser();

        $authResponse = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
            'email'    => $email,
            'password' => $password,
        ]);

        $this->assertSame(200, $authResponse->get_status(), 'auth step failed');
        $jwt = $authResponse->get_data()['data']['jwt'];

        $validateResponse = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/validate', [
            'JWT' => $jwt,
        ]);

        $this->assertSame(200, $validateResponse->get_status());
        $data = $validateResponse->get_data();
        $this->assertTrue($data['success']);
        $this->assertSame($email, $data['data']['user']['user_email']);
    }

    #[TestDox('Validate endpoint rejects a garbage token')]
    public function testValidateRejectsInvalidJwt(): void
    {
        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/validate', [
            'JWT' => 'not.a.real.token',
        ]);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertFalse($data['success']);
    }
}
