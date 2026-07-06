<?php

namespace SimpleJwtLoginTests\Feature\RegisterUsers;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

class RegisterWithJwtTest extends FeatureTestCase
{
    const JWT_SECRET_KEY = 'test';

    /**
     * @return array<string,mixed>
     */
    private static function baseSettings(): array
    {
        return [
            'allow_authentication'    => true,
            'jwt_payload'             => ['email', 'exp', 'id', 'iss', 'site', 'username'],
            'jwt_auth_ttl'            => 60,
            'jwt_auth_refresh_ttl'    => '20160',
            'auth_ip'                 => '',
            'auth_requires_auth_code' => false,
            'auth_password_base64'    => false,
            'jwt_auth_iss'            => 'tests',
            'decryption_key'          => self::JWT_SECRET_KEY,
            'allow_register'          => true,
            'new_user_profile'        => 'subscriber',
            'register_ip'             => '',
            'register_domain'         => '',
            'require_register_auth'   => false,
            'register_force_login'    => false,
            'random_password'         => false,
            'random_password_length'  => 10,
            'allowed_user_meta'       => '',
            'allow_delete'            => true,
            'require_delete_auth'     => false,
            'delete_ip'               => '',
            'delete_user_by'          => 0,
            'jwt_delete_by_parameter' => 'email',
            'jwt_login_by'            => 0,
            'jwt_login_by_parameter'  => 'email',
        ];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'register_jwt' => true,
        ]));
    }

    #[TestDox('Register response includes a JWT when register_jwt is enabled')]
    public function testRegisterReturnsJwt(): void
    {
        $email = 'jwttest_' . uniqid() . '@example.com';

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/users', [
            'email'    => $email,
            'password' => 'SomePass123!',
        ]);

        $this->assertSame(200, $response->getStatusCode(), 'register failed');
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('jwt', $body['data'], 'jwt missing from register response');
        $this->assertNotEmpty($body['data']['jwt']);
        $this->assertArrayHasKey('email', $body['data']);
        $this->assertSame($email, $body['data']['email']);

        [$deleteStatus] = $this->deleteUser($body['data']['jwt']);
        $this->assertSame(200, $deleteStatus, 'cleanup failed');
    }

    #[TestDox('Register succeeds without a password when random_password is enabled')]
    public function testRegisterWithRandomPassword(): void
    {
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'register_jwt'    => true,
            'random_password' => true,
        ]));

        $email = 'randpass_' . uniqid() . '@example.com';

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/users', [
            'email' => $email,
        ]);

        $this->assertSame(200, $response->getStatusCode(), 'register with random password failed');
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('jwt', $body['data'], 'jwt missing from register response');
        $this->assertNotEmpty($body['data']['jwt']);

        [$deleteStatus] = $this->deleteUser($body['data']['jwt']);
        $this->assertSame(200, $deleteStatus, 'cleanup failed');

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'register_jwt' => true,
        ]));
    }
}
