<?php

namespace SimpleJwtLoginTests\Feature\ApiKeys;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use SimpleJwtLoginTests\Feature\FeatureTestCase;
use SimpleJwtLoginTests\Feature\MysqliWpdb;

/**
 * Integration tests: API key authentication with the Protect Endpoints feature.
 *
 * ProtectEndpointService tries JWT first, then falls through to tryApiKeyAuth().
 * These tests verify each combination against GET /wp/v2/users/me (requires auth):
 *
 *   protect disabled + valid API key → WP REST auth logs in key owner → 200
 *   protect enabled  + valid API key → tryApiKeyAuth logs in key owner → 200
 *   protect enabled  + no key, no JWT → guard denies with plugin error 401
 *   protect enabled  + api_keys disabled + key header → guard ignores key, denies 401
 *   protect specific endpoint + valid API key → tryApiKeyAuth logs in key owner → 200
 */
class ApiKeyWithProtectEndpointsTest extends FeatureTestCase
{
    private const API_KEY_HEADER = 'X-API-Key';
    private const USERS_ME_ROUTE = '/wp/v2/users/me';

    /**
     * @var ApiKeyRepository
     */
    private $keyRepository;

    // ─── Lifecycle ─────────────────────────────────────────────────────────────

    /**
     * @return array<string,mixed>
     */
    private static function baseSettings(): array
    {
        return [
            'api_keys' => [
                'enabled'     => true,
                'header_name' => self::API_KEY_HEADER,
            ],
            'allow_authentication'    => true,
            'jwt_payload'             => ['email', 'exp', 'id', 'iss', 'site', 'username'],
            'jwt_auth_ttl'            => 60,
            'jwt_auth_refresh_ttl'    => '20160',
            'auth_ip'                 => '',
            'auth_requires_auth_code' => false,
            'auth_password_base64'    => false,
            'allow_register'          => true,
            'new_user_profile'        => 'subscriber',
            'require_register_auth'   => false,
            'register_ip'             => '',
            'register_domain'         => '',
            'decryption_key'          => 'test-secret',
            'jwt_auth_iss'            => 'tests',
        ];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::updateSimpleJWTOption(self::baseSettings());
        self::ensureApiKeyTable();
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->keyRepository = new ApiKeyRepository(
            new MysqliWpdb(self::$dbCon, self::getTablePrefix())
        );
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param array<string,mixed> $settings
     */
    private function withSettings(array $settings, callable $test): void
    {
        try {
            self::updateSimpleJWTOption($settings);
            $test();
        } finally {
            self::updateSimpleJWTOption(self::baseSettings());
        }
    }

    /**
     * @param array<string> $permissions
     * @param string|null   $expiresAt
     * @return array{int, string}
     */
    private function createApiKey(int $userId, array $permissions, $expiresAt = null): array
    {
        $rawKey    = 'sjl_' . bin2hex(openssl_random_pseudo_bytes(16));
        $keyHash   = hash('sha256', $rawKey);
        $keyPrefix = substr($rawKey, 0, 8);

        $keyId = $this->keyRepository->insert(
            $userId,
            'protect-test-key',
            $keyHash,
            $keyPrefix,
            (string) json_encode($permissions),
            $expiresAt,
            gmdate('Y-m-d H:i:s')
        );

        $this->assertNotFalse($keyId, 'API key insert must succeed');

        return [(int) $keyId, $rawKey];
    }

    /**
     * Assert that the protect-endpoint guard denied the request (plugin 401 with plugin error code).
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    private function assertGuardDenied($response): void
    {
        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(
            ErrorCodes::ERR_PROTECT_ENDPOINTS_MISSING_JWT,
            $body['data']['error_code']
        );
    }

    // ─── Tests: protect endpoints disabled ────────────────────────────────────

    #[TestDox('Protect endpoints disabled: valid API key authenticates via WP REST auth and /users/me returns 200')]
    public function testProtectEndpointsDisabledApiKeyAuthenticates(): void
    {
        [, , $userId] = $this->createAdminUser();
        [, $rawKey]   = $this->createApiKey($userId, ['read']);

        $settings = array_merge(self::baseSettings(), [
            'protect_endpoints' => [
                'enabled'   => false,
                'action'    => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect'   => [],
                'whitelist' => [],
            ],
        ]);

        $this->withSettings($settings, function () use ($rawKey, $userId) {
            $response = $this->request('GET', self::USERS_ME_ROUTE, [], [self::API_KEY_HEADER => $rawKey]);

            $this->assertSame(200, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertArrayHasKey('id', $body);
            $this->assertSame($userId, $body['id']);
        });
    }

    // ─── Tests: protect ALL endpoints ─────────────────────────────────────────

    #[TestDox('Protect ALL endpoints enabled: valid API key satisfies the guard and /users/me returns 200')]
    public function testProtectAllEndpointsEnabledApiKeyGrantsAccess(): void
    {
        [, , $userId] = $this->createAdminUser();
        [, $rawKey]   = $this->createApiKey($userId, ['read']);

        $settings = array_merge(self::baseSettings(), [
            'protect_endpoints' => [
                'enabled'   => true,
                'action'    => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect'   => [],
                'whitelist' => [],
            ],
        ]);

        $this->withSettings($settings, function () use ($rawKey, $userId) {
            $response = $this->request('GET', self::USERS_ME_ROUTE, [], [self::API_KEY_HEADER => $rawKey]);

            $this->assertSame(200, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertArrayHasKey('id', $body);
            $this->assertSame($userId, $body['id']);
        });
    }

    #[TestDox('Protect ALL endpoints enabled: no API key and no JWT returns plugin 401')]
    public function testProtectAllEndpointsEnabledNoCredentialsIsBlocked(): void
    {
        $settings = array_merge(self::baseSettings(), [
            'protect_endpoints' => [
                'enabled'   => true,
                'action'    => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect'   => [],
                'whitelist' => [],
            ],
        ]);

        $this->withSettings($settings, function () {
            $response = $this->request('GET', self::USERS_ME_ROUTE);

            $this->assertGuardDenied($response);
        });
    }

    #[TestDox('Protect ALL endpoints enabled: API keys feature disabled makes key header ineffective and guard blocks')]
    public function testProtectAllEndpointsEnabledApiKeysFeatureDisabledBlocksAccess(): void
    {
        [, , $userId] = $this->createAdminUser();
        [, $rawKey]   = $this->createApiKey($userId, ['read']);

        $settings = array_merge(self::baseSettings(), [
            'api_keys' => ['enabled' => false, 'header_name' => self::API_KEY_HEADER],
            'protect_endpoints' => [
                'enabled'   => true,
                'action'    => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect'   => [],
                'whitelist' => [],
            ],
        ]);

        $this->withSettings($settings, function () use ($rawKey) {
            // The guard tries JWT (none) then tries API key auth (skipped: feature disabled) → denies.
            $response = $this->request('GET', self::USERS_ME_ROUTE, [], [self::API_KEY_HEADER => $rawKey]);

            $this->assertGuardDenied($response);
        });
    }

    // ─── Tests: protect SPECIFIC endpoints ────────────────────────────────────

    #[TestDox('Protect SPECIFIC endpoint /wp/v2/users: valid API key satisfies the guard and /users/me returns 200')]
    public function testProtectSpecificEndpointApiKeyGrantsAccess(): void
    {
        [, , $userId] = $this->createAdminUser();
        [, $rawKey]   = $this->createApiKey($userId, ['read']);

        $settings = array_merge(self::baseSettings(), [
            'protect_endpoints' => [
                'enabled'        => true,
                'action'         => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                'protect'        => ['/wp/v2/users'],
                'protect_method' => ['ALL'],
                'protect_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH],
                'whitelist'      => [],
            ],
        ]);

        $this->withSettings($settings, function () use ($rawKey, $userId) {
            $response = $this->request('GET', self::USERS_ME_ROUTE, [], [self::API_KEY_HEADER => $rawKey]);

            $this->assertSame(200, $response->getStatusCode());
            $body = json_decode($response->getBody()->getContents(), true);
            $this->assertArrayHasKey('id', $body);
            $this->assertSame($userId, $body['id']);
        });
    }

    #[TestDox('Protect SPECIFIC endpoint /wp/v2/users: no API key and no JWT returns plugin 401')]
    public function testProtectSpecificEndpointNoCredentialsIsBlocked(): void
    {
        $settings = array_merge(self::baseSettings(), [
            'protect_endpoints' => [
                'enabled'        => true,
                'action'         => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                'protect'        => ['/wp/v2/users'],
                'protect_method' => ['ALL'],
                'protect_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH],
                'whitelist'      => [],
            ],
        ]);

        $this->withSettings($settings, function () {
            $response = $this->request('GET', self::USERS_ME_ROUTE);

            $this->assertGuardDenied($response);
        });
    }
}
