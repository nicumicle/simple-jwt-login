<?php

namespace SimpleJwtLoginTests\Feature\Authentication;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

class ValidateTokenTest extends FeatureTestCase
{
    const JWT_SECRET_KEY = '123';

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
        self::updateSimpleJWTOption(self::baseSettings());
    }

    #[TestDox('Authenticated user receives a JWT')]
    public function testAuthReturnsJwt(): void
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth', [
            'email'    => $email,
            'password' => $password,
        ]);

        $this->assertSame(200, $response->getStatusCode(), 'auth failed');
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('jwt', $body['data']);

        [$deleteStatus] = $this->deleteUser($body['data']['jwt']);
        $this->assertSame(200, $deleteStatus, 'cleanup failed');
    }

    #[TestDox('Valid JWT passes token validation')]
    public function testValidateAcceptsValidJwt(): void
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');

        $jwt = $this->getJWTForUser($email, $password);
        $this->assertNotNull($jwt);

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/validate', [
            'JWT' => $jwt,
        ]);

        $this->assertSame(200, $response->getStatusCode(), 'validate failed');
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('user', $body['data']);
        $this->assertArrayHasKey('jwt', $body['data']);
        $this->assertSame($email, $body['data']['user']['user_email']);

        [$deleteStatus] = $this->deleteUser($jwt);
        $this->assertSame(200, $deleteStatus, 'cleanup failed');
    }

    #[TestDox('Invalid JWT is rejected by the validation endpoint')]
    public function testValidateRejectsInvalidJwt(): void
    {
        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/validate', [
            'JWT' => 'not.a.valid.jwt',
        ]);

        $this->assertSame(400, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
    }

    #[TestDox('Validate: bare JWT in Authorization header is ignored when Bearer prefix is required')]
    public function testBearerRequiredRejectsBareJwtInHeader(): void
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');
        $jwt = $this->getJWTForUser($email, $password);

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'request_jwt_header_require_bearer' => true,
        ]));
        try {
            $response = $this->jsonRequest(
                'POST',
                '/simple-jwt-login/v1/auth/validate',
                [],
                ['Authorization' => $jwt]
            );

            $this->assertSame(422, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE, $body['data']['error_code']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
            [$deleteStatus] = $this->deleteUser($jwt);
            $this->assertSame(200, $deleteStatus, 'cleanup failed');
        }
    }

    #[TestDox('Validate: Bearer-prefixed JWT in header is accepted when Bearer prefix is required')]
    public function testBearerRequiredAcceptsBearerJwtInHeader(): void
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');
        $jwt = $this->getJWTForUser($email, $password);

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'request_jwt_header_require_bearer' => true,
        ]));
        try {
            $response = $this->jsonRequest(
                'POST',
                '/simple-jwt-login/v1/auth/validate',
                [],
                $this->authHeader($jwt)
            );

            $this->assertSame(200, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertTrue($body['success']);
            $this->assertSame($email, $body['data']['user']['user_email']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
            [$deleteStatus] = $this->deleteUser($jwt);
            $this->assertSame(200, $deleteStatus, 'cleanup failed');
        }
    }
}
