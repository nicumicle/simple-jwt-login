<?php

namespace SimpleJwtLoginTests\Feature\Authentication;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * Feature tests for the /auth/refresh endpoint.
 *
 * Covers feature-flag, missing/invalid tokens, and rotation: after a
 * successful refresh the old token is revoked and the new one works.
 */
class RefreshTokenValidationTest extends FeatureTestCase
{
    private const JWT_SECRET_KEY     = 'test-secret';
    private const REFRESH_SECRET_KEY = 'test_refresh_secret_key';

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
            'allow_refresh_token'     => 1,
            'refresh_token_key'       => self::REFRESH_SECRET_KEY,
            'allow_revoke_token'      => true,
        ];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption(self::baseSettings());
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Register a user, authenticate, and return [jwt, refresh_token].
     *
     * @return array{string, string}
     */
    private function authNewUser(): array
    {
        [$email, $password, $status] = $this->createUser();
        $this->assertSame(200, $status, 'register failed');

        [$authStatus, $authBody] = $this->authUser($email, $password);
        $this->assertSame(200, $authStatus, 'auth failed');

        $data = json_decode($authBody, true);
        $this->assertArrayHasKey('refresh_token', $data['data'], 'refresh_token missing from auth response');

        return [$data['data']['jwt'], $data['data']['refresh_token']];
    }

    // ─── Feature flag ─────────────────────────────────────────────────────────

    #[TestDox('Refresh returns 403 when allow_refresh_token is disabled')]
    public function testRefreshDisabledReturns403(): void
    {
        self::updateSimpleJWTOption(array_merge(self::baseSettings(), ['allow_refresh_token' => 0]));

        try {
            $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/refresh', [
                'refresh_token' => 'any-token',
            ]);

            $this->assertSame(403, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertFalse($body['success']);
            $this->assertSame(ErrorCodes::ERR_REFRESH_TOKEN_NOT_ENABLED, $body['data']['errorCode']);
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    // ─── Validation ───────────────────────────────────────────────────────────

    #[TestDox('Refresh with missing refresh_token returns 422')]
    public function testMissingRefreshTokenReturns422(): void
    {
        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/refresh', []);

        $this->assertSame(422, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH, $body['data']['errorCode']);
        $this->assertSame('Refresh token is missing.', $body['data']['message']);
    }

    #[TestDox('Refresh with an invalid (non-existent) refresh_token returns 401')]
    public function testInvalidRefreshTokenReturns401(): void
    {
        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/refresh', [
            'refresh_token' => 'totally-made-up-token-that-does-not-exist',
        ]);

        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH, $body['data']['errorCode']);
    }

    // ─── Success and rotation ─────────────────────────────────────────────────

    #[TestDox('Refresh returns a new JWT and a new refresh_token')]
    public function testSuccessfulRefreshReturnsNewTokens(): void
    {
        [, $refreshToken] = $this->authNewUser();

        $response = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('jwt', $body['data']);
        $this->assertArrayHasKey('refresh_token', $body['data']);
        $this->assertNotEmpty($body['data']['jwt']);
        $this->assertNotEmpty($body['data']['refresh_token']);
    }

    #[TestDox('Old refresh_token is invalidated after a successful rotation')]
    public function testOldRefreshTokenIsInvalidatedAfterRotation(): void
    {
        [, $oldRefreshToken] = $this->authNewUser();

        // Use the old token once — succeeds
        $first = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/refresh', [
            'refresh_token' => $oldRefreshToken,
        ]);
        $this->assertSame(200, $first->getStatusCode(), 'first refresh must succeed');

        // Re-using the old token must now fail
        $second = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/refresh', [
            'refresh_token' => $oldRefreshToken,
        ]);

        $this->assertSame(401, $second->getStatusCode());
        $body = json_decode($second->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH, $body['data']['errorCode']);
    }

    #[TestDox('New refresh_token returned by rotation can be used for another refresh')]
    public function testNewRefreshTokenCanBeUsedAfterRotation(): void
    {
        [, $oldRefreshToken] = $this->authNewUser();

        $firstRefresh = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/refresh', [
            'refresh_token' => $oldRefreshToken,
        ]);
        $this->assertSame(200, $firstRefresh->getStatusCode());
        $firstBody     = json_decode($firstRefresh->getBody()->getContents(), true);
        $newRefreshToken = $firstBody['data']['refresh_token'];

        // The new token must also work
        $secondRefresh = $this->jsonRequest('POST', '/simple-jwt-login/v1/auth/refresh', [
            'refresh_token' => $newRefreshToken,
        ]);

        $this->assertSame(200, $secondRefresh->getStatusCode());
        $body = json_decode($secondRefresh->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('jwt', $body['data']);
    }

    // ─── Revoked JWT in refresh request ───────────────────────────────────────

    #[TestDox('Refresh is rejected when a revoked JWT is passed in the Authorization header')]
    public function testRevokedJwtInHeaderBlocksRefresh(): void
    {
        [$jwt, $refreshToken] = $this->authNewUser();

        // Revoke the JWT
        $revokeResp = $this->jsonRequest(
            'POST',
            '/simple-jwt-login/v1/auth/revoke',
            [],
            ['Authorization' => $jwt]
        );
        $this->assertSame(200, $revokeResp->getStatusCode(), 'revoke must succeed');

        // Trying to refresh with the revoked JWT in the header must fail
        $response = $this->jsonRequest(
            'POST',
            '/simple-jwt-login/v1/auth/refresh',
            ['refresh_token' => $refreshToken],
            ['Authorization' => $jwt]
        );

        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_REVOKED_TOKEN, $body['data']['errorCode']);
    }
}
