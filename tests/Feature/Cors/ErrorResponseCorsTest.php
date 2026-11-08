<?php

namespace SimpleJwtLoginTests\Feature\Cors;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * Error responses must be served through the WordPress REST pipeline, so the
 * CORS headers (WordPress core or the plugin's own) are present and browsers
 * can read the status code and body.
 */
class ErrorResponseCorsTest extends FeatureTestCase
{
    private const ORIGIN = 'http://localhost:5173';

    /**
     * @param array<string,mixed> $extra
     * @return array<string,mixed>
     */
    private static function settings(array $extra = []): array
    {
        return array_merge([
            'allow_authentication'    => true,
            'jwt_payload'             => ['email', 'exp', 'id', 'iss', 'site', 'username'],
            'jwt_auth_ttl'            => 60,
            'jwt_auth_refresh_ttl'    => '20160',
            'auth_ip'                 => '',
            'auth_requires_auth_code' => false,
            'jwt_auth_iss'            => 'tests',
            'decryption_key'          => 'test-secret',
            'allow_refresh_token'     => 1,
            'refresh_token_key'       => 'test_refresh_secret_key',
        ], $extra);
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption(self::settings());
    }

    #[TestDox('Invalid refresh token returns 401 with WordPress CORS headers when plugin CORS is disabled')]
    public function testRefreshErrorHasWordPressCorsHeaders(): void
    {
        $response = $this->jsonRequest(
            'POST',
            '/simple-jwt-login/v1/auth/refresh',
            ['refresh_token' => 'invalid-token'],
            ['Origin' => self::ORIGIN]
        );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(self::ORIGIN, $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertFalse($response->hasHeader('X-WP-DoingItWrong'));
        $this->assertSame(
            self::generateErrorJson('Invalid refresh token.', ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH),
            json_decode($response->getBody()->getContents(), true)
        );
    }

    #[TestDox('Protected endpoint 401 has WordPress CORS headers')]
    public function testProtectedEndpointErrorHasWordPressCorsHeaders(): void
    {
        self::updateSimpleJWTOption(self::settings([
            'protect_endpoints' => [
                'enabled'   => true,
                'action'    => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect'   => [],
                'whitelist' => [],
            ],
        ]));

        try {
            $response = $this->request('GET', '/wp/v2/users', [], ['Origin' => self::ORIGIN]);

            $this->assertSame(401, $response->getStatusCode());
            $this->assertSame(self::ORIGIN, $response->getHeaderLine('Access-Control-Allow-Origin'));
            $this->assertFalse($response->hasHeader('X-WP-DoingItWrong'));
            $this->assertSame(
                self::generateErrorJson(
                    'You are not authorized to access this endpoint.',
                    ErrorCodes::ERR_PROTECT_ENDPOINTS_MISSING_JWT
                ),
                json_decode($response->getBody()->getContents(), true)
            );
        } finally {
            self::updateSimpleJWTOption(self::settings());
        }
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function configuredOriginProvider(): array
    {
        return [
            'first listed origin'  => ['https://example.com', 'https://example.com'],
            'second listed origin' => [self::ORIGIN, self::ORIGIN],
            'third listed origin'  => ['capacitor://localhost', 'capacitor://localhost'],
            'unlisted origin'      => ['https://evil.example', ''],
        ];
    }

    #[TestDox('Plugin CORS allow-origin list is enforced on REST responses')]
    #[DataProvider('configuredOriginProvider')]
    public function testConfiguredOriginListIsEnforced(string $origin, string $expected): void
    {
        self::updateSimpleJWTOption(self::settings([
            'cors' => [
                'enabled'              => 1,
                'allow_origin_enabled' => true,
                'allow_origin'         => 'https://example.com, ' . self::ORIGIN . ', capacitor://localhost',
            ],
        ]));

        try {
            $response = $this->jsonRequest(
                'POST',
                '/simple-jwt-login/v1/auth/refresh',
                ['refresh_token' => 'invalid-token'],
                ['Origin' => $origin]
            );

            $this->assertSame(401, $response->getStatusCode());
            $this->assertSame($expected, $response->getHeaderLine('Access-Control-Allow-Origin'));
        } finally {
            self::updateSimpleJWTOption(self::settings());
        }
    }
}
