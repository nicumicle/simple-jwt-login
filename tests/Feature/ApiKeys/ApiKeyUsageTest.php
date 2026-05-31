<?php

namespace SimpleJwtLoginTests\Feature\ApiKeys;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use SimpleJwtLoginTests\Feature\FeatureTestCase;
use SimpleJwtLoginTests\Feature\MysqliWpdb;

/**
 * Integration tests for API key authentication against WordPress REST endpoints.
 *
 * These tests exercise the full HTTP stack:
 *   valid key present  → AuthenticationHandler logs in the key owner
 *   bad key present    → 401 returned by our plugin
 *   no key header      → WP's own auth runs (unauthenticated → WP-native 401)
 *
 * The API keys table is expected to exist before this suite runs.
 * API keys are managed via ApiKeyRepository to keep tests free of raw SQL.
 */
class ApiKeyUsageTest extends FeatureTestCase
{
    private const API_KEY_HEADER = 'X-API-Key';
    private const POSTS_ROUTE    = '/wp/v2/posts';

    /**
     * @var ApiKeyRepository
     */
    private $keyRepository;

    // ─── Lifecycle ────────────────────────────────────────────────────────────

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
            'allow_register'        => true,
            'new_user_profile'      => 'subscriber',
            'require_register_auth' => false,
            'register_ip'           => '',
            'register_domain'       => '',
            'decryption_key'        => 'test-secret',
            'jwt_auth_iss'          => 'tests',
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

    // ─── Settings helper ──────────────────────────────────────────────────────

    /**
     * Runs $test under a temporary plugin configuration, then restores the
     * baseline settings even if the test throws.
     *
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

    // ─── API key helpers ──────────────────────────────────────────────────────

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
            'test-key',
            $keyHash,
            $keyPrefix,
            (string) json_encode($permissions),
            $expiresAt,
            gmdate('Y-m-d H:i:s')
        );

        $this->assertNotFalse($keyId, 'API key insert must succeed');

        return [(int) $keyId, $rawKey];
    }

    private function revokeApiKey(int $keyId): void
    {
        $this->keyRepository->revokeById($keyId, gmdate('Y-m-d H:i:s'));
    }

    /**
     * Creates a WP post via the REST API using an API key and returns the post ID.
     * Asserts creation succeeds so callers can rely on the returned ID.
     */
    private function createPost(string $apiKey, string $title = 'Test Post'): int
    {
        $response = $this->jsonRequest(
            'POST',
            self::POSTS_ROUTE,
            ['title' => $title, 'status' => 'draft'],
            [self::API_KEY_HEADER => $apiKey]
        );
        $this->assertSame(201, $response->getStatusCode(), 'Pre-condition: post creation must succeed');

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('id', $data, 'WP post response must include an id');

        return (int) $data['id'];
    }

    // ─── Helper assertions ────────────────────────────────────────────────────

    /**
     * Asserts the response carries the plugin's ERR_API_KEY_UNAUTHORIZED error.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    private function assertPluginApiKeyError($response): void
    {
        $this->assertSame(401, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_API_KEY_UNAUTHORIZED, $body['data']['errorCode']);
    }

    // ─── Tests: happy paths ───────────────────────────────────────────────────

    #[TestDox('Admin with "create" permission can create a post via POST')]
    public function testAdminWithCreatePermissionCanCreatePost(): void
    {
        [, , $userId] = $this->createAdminUser();
        [, $rawKey]   = $this->createApiKey($userId, ['create']);

        $response = $this->jsonRequest(
            'POST',
            self::POSTS_ROUTE,
            ['title' => 'API Key Create Test', 'status' => 'draft'],
            [self::API_KEY_HEADER => $rawKey]
        );

        $this->assertSame(201, $response->getStatusCode());
    }

    #[TestDox('Admin with "read" permission can GET the posts list')]
    public function testAdminWithReadPermissionCanGetPosts(): void
    {
        [, , $userId] = $this->createAdminUser();
        [, $rawKey]   = $this->createApiKey($userId, ['read']);

        $response = $this->request('GET', self::POSTS_ROUTE, [], [self::API_KEY_HEADER => $rawKey]);

        $this->assertSame(200, $response->getStatusCode());
    }

    #[TestDox('Admin with "update" permission can update an existing post via PUT')]
    public function testAdminWithUpdatePermissionCanUpdatePost(): void
    {
        [, , $userId]  = $this->createAdminUser();
        [, $createKey] = $this->createApiKey($userId, ['create']);
        [, $updateKey] = $this->createApiKey($userId, ['update']);

        $postId = $this->createPost($createKey, 'Original Title');

        $response = $this->jsonRequest(
            'PUT',
            self::POSTS_ROUTE . '/' . $postId,
            ['title' => 'Updated Title'],
            [self::API_KEY_HEADER => $updateKey]
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[TestDox('Admin with "delete" permission can delete an existing post')]
    public function testAdminWithDeletePermissionCanDeletePost(): void
    {
        [, , $userId]  = $this->createAdminUser();
        [, $createKey] = $this->createApiKey($userId, ['create']);
        [, $deleteKey] = $this->createApiKey($userId, ['delete']);

        $postId = $this->createPost($createKey, 'Post to Delete');

        $response = $this->request(
            'DELETE',
            self::POSTS_ROUTE . '/' . $postId,
            [],
            [self::API_KEY_HEADER => $deleteKey]
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    #[TestDox('Subscriber with "read" permission can GET posts (has WP read capability)')]
    public function testSubscriberWithReadKeyCanGetPosts(): void
    {
        [$email, , $statusCode] = $this->createUser();
        $this->assertSame(200, $statusCode);
        $userId     = $this->lookupUserId($email);
        [, $rawKey] = $this->createApiKey($userId, ['read']);

        $response = $this->request('GET', self::POSTS_ROUTE, [], [self::API_KEY_HEADER => $rawKey]);

        $this->assertSame(200, $response->getStatusCode());
    }

    // ─── Tests: invalid / rejected keys ──────────────────────────────────────

    #[TestDox('Invalid API key value returns 401 with plugin error code')]
    public function testInvalidApiKeyReturns401(): void
    {
        $response = $this->jsonRequest(
            'POST',
            self::POSTS_ROUTE,
            ['title' => 'Unauthorized', 'status' => 'draft'],
            [self::API_KEY_HEADER => 'sjl_totally_invalid_key_that_does_not_exist']
        );

        $this->assertPluginApiKeyError($response);
    }

    #[TestDox('Expired API key returns 401')]
    public function testExpiredApiKeyReturns401(): void
    {
        [, , $userId] = $this->createAdminUser();
        [, $rawKey]   = $this->createApiKey($userId, ['create'], '2000-01-01 00:00:00');

        $response = $this->jsonRequest(
            'POST',
            self::POSTS_ROUTE,
            ['title' => 'Expired Key', 'status' => 'draft'],
            [self::API_KEY_HEADER => $rawKey]
        );

        $this->assertPluginApiKeyError($response);
    }

    #[TestDox('Revoked API key returns 401')]
    public function testRevokedApiKeyReturns401(): void
    {
        [, , $userId] = $this->createAdminUser();
        [$keyId, $rawKey] = $this->createApiKey($userId, ['create']);
        $this->revokeApiKey($keyId);

        $response = $this->jsonRequest(
            'POST',
            self::POSTS_ROUTE,
            ['title' => 'Revoked Key', 'status' => 'draft'],
            [self::API_KEY_HEADER => $rawKey]
        );

        $this->assertPluginApiKeyError($response);
    }

    // ─── Tests: method / permission mismatch ──────────────────────────────────

    /**
     * @return array<string, array{array<string>, string, array<string,mixed>}>
     */
    public static function methodPermissionMismatchProvider(): array
    {
        return [
            'read-only key on POST'      => [['read'],   'POST', ['title' => 'X', 'status' => 'draft']],
            'create-only key on GET'     => [['create'], 'GET',  []],
            'delete-only key on POST'    => [['delete'], 'POST', ['title' => 'X', 'status' => 'draft']],
            'update-only key on GET'     => [['update'], 'GET',  []],
            'update-only key on DELETE'  => [['update'], 'DELETE', []],
        ];
    }

    /**
     * @param array<string>        $permissions
     * @param array<string,mixed>  $body
     */
    #[DataProvider('methodPermissionMismatchProvider')]
    #[TestDox('Key with mismatched HTTP-method permission returns 401')]
    public function testKeyWithMismatchedPermissionReturns401(array $permissions, string $method, array $body): void
    {
        [, , $userId] = $this->createAdminUser();
        [, $rawKey]   = $this->createApiKey($userId, $permissions);

        $response = empty($body)
            ? $this->request($method, self::POSTS_ROUTE, [], [self::API_KEY_HEADER => $rawKey])
            : $this->jsonRequest($method, self::POSTS_ROUTE, $body, [self::API_KEY_HEADER => $rawKey]);

        $this->assertPluginApiKeyError($response);
    }

    // ─── Tests: WP capability check ───────────────────────────────────────────

    #[TestDox('Subscriber with "create" key is denied by WordPress (lacks edit_posts)')]
    public function testSubscriberWithCreateKeyCannotCreatePost(): void
    {
        [$email, , $statusCode] = $this->createUser();
        $this->assertSame(200, $statusCode);
        $userId     = $this->lookupUserId($email);
        [, $rawKey] = $this->createApiKey($userId, ['create']);

        $response = $this->jsonRequest(
            'POST',
            self::POSTS_ROUTE,
            ['title' => 'Subscriber attempt', 'status' => 'draft'],
            [self::API_KEY_HEADER => $rawKey]
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    // ─── Tests: no API key header ─────────────────────────────────────────────

    #[TestDox('POST without any API key header gets a WP-native 401, not the plugin error')]
    public function testNoApiKeyHeaderGetsWordPressNative401(): void
    {
        $response = $this->jsonRequest(
            'POST',
            self::POSTS_ROUTE,
            ['title' => 'No Key Post', 'status' => 'draft']
        );

        $this->assertSame(401, $response->getStatusCode());

        $body      = json_decode($response->getBody()->getContents(), true);
        $errorCode = isset($body['data']['errorCode']) ? (int) $body['data']['errorCode'] : 0;
        $this->assertNotSame(
            ErrorCodes::ERR_API_KEY_UNAUTHORIZED,
            $errorCode,
            'The 401 must come from WordPress, not from the plugin middleware'
        );
    }

    // ─── Tests: feature flag ──────────────────────────────────────────────────

    #[TestDox('When API keys feature is disabled the key header is ignored')]
    public function testApiKeysDisabledKeyIsIgnored(): void
    {
        [, , $userId] = $this->createAdminUser();
        [, $rawKey]   = $this->createApiKey($userId, ['create']);

        $disabledSettings = array_merge(self::baseSettings(), [
            'api_keys' => ['enabled' => false],
        ]);

        $this->withSettings($disabledSettings, function () use ($rawKey) {
            $response = $this->jsonRequest(
                'POST',
                self::POSTS_ROUTE,
                ['title' => 'Disabled feature', 'status' => 'draft'],
                [self::API_KEY_HEADER => $rawKey]
            );

            // WP's own auth runs and rejects the unauthenticated request.
            // The body must NOT carry our plugin's error code.
            $this->assertSame(401, $response->getStatusCode());

            $body      = json_decode($response->getBody()->getContents(), true);
            $errorCode = isset($body['data']['errorCode']) ? (int) $body['data']['errorCode'] : 0;
            $this->assertNotSame(ErrorCodes::ERR_API_KEY_UNAUTHORIZED, $errorCode);
        });
    }

    // ─── Tests: custom header name ────────────────────────────────────────────

    #[TestDox('Key sent in the default header fails when a custom header name is configured')]
    public function testKeyInDefaultHeaderFailsWhenCustomHeaderConfigured(): void
    {
        [, , $userId] = $this->createAdminUser();
        [, $rawKey]   = $this->createApiKey($userId, ['create']);

        $customSettings = array_merge(self::baseSettings(), [
            'api_keys' => ['enabled' => true, 'header_name' => 'X-Custom-Auth'],
        ]);

        $this->withSettings($customSettings, function () use ($rawKey) {
            $response = $this->jsonRequest(
                'POST',
                self::POSTS_ROUTE,
                ['title' => 'Wrong header', 'status' => 'draft'],
                [self::API_KEY_HEADER => $rawKey]   // default header, not the configured one
            );

            // The plugin sees no key in the configured header and falls through;
            // WP returns its own 401.
            $this->assertSame(401, $response->getStatusCode());

            $body      = json_decode($response->getBody()->getContents(), true);
            $errorCode = isset($body['data']['errorCode']) ? (int) $body['data']['errorCode'] : 0;
            $this->assertNotSame(ErrorCodes::ERR_API_KEY_UNAUTHORIZED, $errorCode);
        });
    }

    #[TestDox('Key sent in the configured custom header authenticates successfully')]
    public function testKeyInCustomHeaderAuthenticatesSuccessfully(): void
    {
        [, , $userId] = $this->createAdminUser();
        [, $rawKey]   = $this->createApiKey($userId, ['create']);

        $customSettings = array_merge(self::baseSettings(), [
            'api_keys' => ['enabled' => true, 'header_name' => 'X-Custom-Auth'],
        ]);

        $this->withSettings($customSettings, function () use ($rawKey) {
            $response = $this->jsonRequest(
                'POST',
                self::POSTS_ROUTE,
                ['title' => 'Custom header post', 'status' => 'draft'],
                ['X-Custom-Auth' => $rawKey]
            );

            $this->assertSame(201, $response->getStatusCode());
        });
    }
}
