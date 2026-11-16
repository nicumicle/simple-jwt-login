<?php

namespace SimpleJwtLoginTests\Feature\Authentication;

use PHPUnit\Framework\Attributes\DataProvider;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * With "password is base64" on, /auth must accept every password a user can set,
 * including quotes and backslashes.
 */
class Base64PasswordTest extends FeatureTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption([
            'allow_authentication'    => true,
            'jwt_payload'             => ['email', 'exp', 'id', 'iat'],
            'jwt_auth_ttl'            => 60,
            'auth_ip'                 => '',
            'auth_requires_auth_code' => false,
            'auth_password_base64'    => true,
            'decryption_key'          => 'test',
            'allow_register'          => true,
            'new_user_profile'        => 'subscriber',
            'register_ip'             => '',
            'register_domain'         => '',
            'require_register_auth'   => false,
            'random_password'         => false,
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function passwordProvider()
    {
        return [
            'single quote'   => ["it's-a-pass"],
            'double quote'   => ['say "hi" 123'],
            'backslash'      => ['back\\slash\\pw'],
            'plain password' => ['PlainPass123'],
        ];
    }

    #[DataProvider('passwordProvider')]
    public function testRegisteredPasswordWorksAsBase64($password)
    {
        $email = 'b64_' . uniqid() . '@example.com';
        $register = $this->jsonRequest('POST', '/simple-jwt-login/v1/users', [
            'email'    => $email,
            'password' => $password,
        ]);
        $this->assertSame(200, $register->getStatusCode(), 'register failed');

        $auth = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
            'email'    => $email,
            'password' => base64_encode($password),
        ]);
        $body = json_decode($auth->getBody()->getContents(), true);

        $this->assertSame(200, $auth->getStatusCode(), json_encode($body));
        $this->assertNotEmpty($body['data']['jwt']);
    }

    public function testInvalidBase64IsWrongCredentials()
    {
        $email = 'b64_' . uniqid() . '@example.com';
        $this->jsonRequest('POST', '/simple-jwt-login/v1/users', [
            'email'    => $email,
            'password' => 'PlainPass123',
        ]);

        $auth = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
            'email'    => $email,
            'password' => 'not*base64!',
        ]);
        $body = json_decode($auth->getBody()->getContents(), true);

        $this->assertSame(\SimpleJWTLogin\ErrorCodes::ERR_AUTHENTICATION_WRONG_CREDENTIALS, $body['data']['error_code']);
    }
}
