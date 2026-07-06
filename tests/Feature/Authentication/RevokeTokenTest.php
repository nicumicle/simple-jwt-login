<?php

namespace SimpleJwtLoginTests\Feature\Authentication;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * Feature tests for the /auth/revoke endpoint.
 *
 * Covers: feature flag, missing JWT, success, double-revoke,
 * and verifying that a revoked JWT is subsequently rejected
 * by validation and autologin.
 */
class RevokeTokenTest extends FeatureTestCase
{
    private const JWT_SECRET_KEY = 'test-secret';

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
            'allow_revoke_token'      => true,
            'allow_validate_token'    => true,
            'allow_autologin'         => true,
        ];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption(self::baseSettings());
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Register + authenticate and return the JWT.
     */
    private function freshJwt(): string
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');
        $jwt = $this->getJWTForUser($email, $password);
        $this->assertNotNull($jwt);
        return $jwt;
    }

    /**
     * Revoke a JWT via the plugin's revoke endpoint.
     */
    private function revokeJwt(string $jwt): void
    {
        $response = $this->jsonRequest(
            'POST',
            '/simple-jwt-login/v1/auth/revoke',
            [],
            $this->authHeader($jwt)
        );
        $this->assertSame(200, $response->getStatusCode(), 'revoke pre-condition failed');
    }

    // ─── Feature flag ─────────────────────────────────────────────────────────

    #[TestDox('Revoke returns 403 when allow_revoke_token is disabled')]
    public function testRevokeDisabledReturns403(): void
    {
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), ['allow_revoke_token' => false]));

        try {
            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/revoke', []);

            $this->assertSame(403, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(ErrorCodes::ERR_REVOKE_TOKEN_NOT_ENABLED, $body['data']['error_code']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    // ─── Validation ───────────────────────────────────────────────────────────

    #[TestDox('Revoke with no JWT returns 422')]
    public function testRevokeWithMissingJwtReturns422(): void
    {
        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/revoke', []);

        $this->assertSame(422, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE, $body['data']['error_code']);
        $this->assertSame('The `jwt` parameter is missing.', $body['data']['message']);
    }

    #[TestDox('Revoke with a structurally invalid JWT returns 400')]
    public function testRevokeWithInvalidJwtStructure(): void
    {
        $response = $this->jsonRequest(
            'POST',
            '/simple-jwt-login/v1/auth/revoke',
            [],
            $this->authHeader('not.a.real.jwt.string')
        );

        $this->assertSame(400, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
    }

    // ─── Success ──────────────────────────────────────────────────────────────

    #[TestDox('Revoke succeeds and returns the revoked JWT in the response')]
    public function testRevokeSuccessReturnsRevokedJwt(): void
    {
        $jwt = $this->freshJwt();

        $response = $this->jsonRequest(
            'POST',
            '/simple-jwt-login/v1/auth/revoke',
            [],
            $this->authHeader($jwt)
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertSame('Token was revoked.', $body['message']);
        $this->assertContains($jwt, $body['data']['jwt']);
    }

    // ─── Double revoke ────────────────────────────────────────────────────────

    #[TestDox('Revoking an already-revoked JWT returns 401')]
    public function testDoubleRevokeReturns401(): void
    {
        $jwt = $this->freshJwt();
        $this->revokeJwt($jwt);

        $response = $this->jsonRequest(
            'POST',
            '/simple-jwt-login/v1/auth/revoke',
            [],
            $this->authHeader($jwt)
        );

        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_REVOKED_TOKEN, $body['data']['error_code']);
    }

    // ─── Post-revoke endpoint rejections ─────────────────────────────────────

    #[TestDox('Revoked JWT is rejected by the validate endpoint')]
    public function testRevokedJwtIsRejectedByValidate(): void
    {
        $jwt = $this->freshJwt();
        $this->revokeJwt($jwt);

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/validate', [
            'JWT' => $jwt,
        ]);

        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_REVOKED_TOKEN, $body['data']['error_code']);
    }

    #[TestDox('Revoked JWT is rejected by the autologin endpoint')]
    public function testRevokedJwtIsRejectedByAutologin(): void
    {
        $jwt = $this->freshJwt();
        $this->revokeJwt($jwt);

        $response = $this->request(
            'GET',
            '/simple-jwt-login/v1/autologin',
            [],
            $this->authHeader($jwt)
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_REVOKED_TOKEN, $body['data']['error_code']);
    }

    #[TestDox('Revoked JWT is rejected by the delete endpoint')]
    public function testRevokedJwtIsRejectedByDelete(): void
    {
        $jwt = $this->freshJwt();
        $this->revokeJwt($jwt);

        $response = $this->request(
            'DELETE',
            '/simple-jwt-login/v1/users',
            [],
            ['Authorization' => $jwt]
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_REVOKED_TOKEN, $body['data']['error_code']);
    }

    // ─── JWT passed as URL param ──────────────────────────────────────────────

    #[TestDox('Revoke accepts the JWT as a URL query parameter (JWT=...)')]
    public function testRevokeAcceptsJwtInUrlParam(): void
    {
        $jwt = $this->freshJwt();

        $uri     = self::API_URL . '?rest_route=/simple-jwt-login/v1/auth/revoke&JWT=' . $jwt;
        $options = ['http_errors' => false, 'headers' => ['Content-Type' => 'application/json']];
        $response = $this->client->post($uri, $options);

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
    }

    // ─── Bearer prefix requirement ────────────────────────────────────────────

    #[TestDox('Revoke: bare JWT in Authorization header is ignored when Bearer prefix is required')]
    public function testBearerRequiredRejectsBareJwtInHeader(): void
    {
        $jwt = $this->freshJwt();

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'request_jwt_header_require_bearer' => true,
        ]));
        try {
            $response = $this->jsonRequest(
                'POST',
                '/simple-jwt-login/v1/auth/revoke',
                [],
                ['Authorization' => $jwt]
            );

            $this->assertSame(422, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE, $body['data']['error_code']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
            // Clean up: revoke via URL param (header source is irrelevant here)
            $uri = self::API_URL . '?rest_route=/simple-jwt-login/v1/auth/revoke&JWT=' . $jwt;
            $this->client->post($uri, ['http_errors' => false]);
        }
    }

    #[TestDox('Revoke: Bearer-prefixed JWT in header is accepted when Bearer prefix is required')]
    public function testBearerRequiredAcceptsBearerJwtInHeader(): void
    {
        $jwt = $this->freshJwt();

        self::updateSimpleJWTOption(array_merge(self::baseSettings(), [
            'request_jwt_header_require_bearer' => true,
        ]));
        try {
            $response = $this->jsonRequest(
                'POST',
                '/simple-jwt-login/v1/auth/revoke',
                [],
                $this->authHeader($jwt)
            );

            $this->assertSame(200, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertTrue($body['success']);
            $this->assertSame('Token was revoked.', $body['message']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }
}
