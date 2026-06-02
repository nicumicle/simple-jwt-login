<?php

namespace SimpleJwtLoginTests\WP\ApiKeys;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use SimpleJwtLoginTests\WP\WPTestCase;

/**
 * Full CRUD integration tests for the API key endpoints.
 *
 * Routes:
 *   GET    /simple-jwt-login/v1/api-keys
 *   POST   /simple-jwt-login/v1/api-keys
 *   PUT    /simple-jwt-login/v1/api-keys/{id}
 *   DELETE /simple-jwt-login/v1/api-keys/{id}          (permanent delete)
 *   POST   /simple-jwt-login/v1/api-keys/{id}/revoke  (soft revoke)
 *
 * Error response shape (WP_Error returned by routes/api.php):
 *   {code: 'simple_jwt_login_api_key_error', message: '...', data: {status: N, errorCode: N}}
 */
class ApiKeyCrudTest extends WPTestCase
{
    private const ROUTE      = '/simple-jwt-login/v1/api-keys';
    private const ERROR_CODE = 'simple_jwt_login_api_key_error';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        global $wpdb;
        (new ApiKeyRepository($wpdb))->createTable();

        static::configurePlugin([
            'api_keys' => [
                'enabled'     => true,
                'header_name' => 'X-API-Key',
            ],
            'decryption_key'       => 'test-secret',
            'jwt_auth_iss'         => 'tests',
            'allow_authentication' => false,
            'allow_autologin'      => false,
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        global $wpdb;
        (new ApiKeyRepository($wpdb))->dropTable();

        delete_option(SimpleJWTLoginSettings::OPTIONS_KEY);
        parent::tearDownAfterClass();
    }

    protected function tearDown(): void
    {
        wp_set_current_user(0);
        parent::tearDown();
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    /**
     * Create an API key as $userId and return the new key ID.
     * Asserts that creation succeeds so callers can use the ID as a precondition.
     *
     * @param array<string,mixed> $overrides
     */
    private function createKey(int $userId, array $overrides = []): int
    {
        wp_set_current_user($userId);
        $body     = array_merge(['name' => 'test-key', 'permissions' => ['read']], $overrides);
        $response = $this->jsonRequest('POST', self::ROUTE, $body);
        $this->assertSame(200, $response->get_status(), 'Pre-condition: key creation must succeed');
        return (int) $response->get_data()['data']['id'];
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    #[TestDox('Unauthenticated POST to api-keys returns 401')]
    public function testCreateRequiresAuthentication(): void
    {
        wp_set_current_user(0);

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'name'        => 'any-key',
            'permissions' => ['read'],
        ]);

        $this->assertSame(401, $response->get_status());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function createValidationProvider(): array
    {
        return [
            'missing name field'          => [
                'body'         => ['permissions' => ['read']],
                'expectedCode' => ErrorCodes::ERR_API_KEY_MISSING_NAME,
            ],
            'blank name (whitespace only)' => [
                'body'         => ['name' => '   ', 'permissions' => ['read']],
                'expectedCode' => ErrorCodes::ERR_API_KEY_MISSING_NAME,
            ],
            'missing permissions field'   => [
                'body'         => ['name' => 'no-perms'],
                'expectedCode' => ErrorCodes::ERR_API_KEY_MISSING_PERMISSIONS,
            ],
            'empty permissions array'     => [
                'body'         => ['name' => 'empty-perms', 'permissions' => []],
                'expectedCode' => ErrorCodes::ERR_API_KEY_MISSING_PERMISSIONS,
            ],
            'unrecognised permission value' => [
                'body'         => ['name' => 'bad-perm', 'permissions' => ['fly']],
                'expectedCode' => ErrorCodes::ERR_API_KEY_INVALID_PERMISSION,
            ],
        ];
    }

    #[DataProvider('createValidationProvider')]
    #[TestDox('Create returns the expected validation error code')]
    public function testCreateValidationErrors(array $body, int $expectedCode): void
    {
        [, , $userId] = $this->createUser(['role' => 'administrator']);
        wp_set_current_user($userId);

        $response = $this->jsonRequest('POST', self::ROUTE, $body);

        $data = $response->get_data();
        $this->assertSame(self::ERROR_CODE, $data['code']);
        $this->assertSame($expectedCode, $data['data']['errorCode']);
    }

    #[TestDox('Admin creates a key and the response contains id, raw key, key_prefix, and permissions')]
    public function testAdminCreateKeyReturnsFullKeyData(): void
    {
        [, , $userId] = $this->createUser(['role' => 'administrator']);
        wp_set_current_user($userId);

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'name'        => 'admin-full',
            'permissions' => ['read', 'create', 'update', 'delete'],
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('id', $data['data']);
        $this->assertGreaterThan(0, $data['data']['id']);
        $this->assertArrayHasKey('key', $data['data']);
        $this->assertStringStartsWith('sjl_', $data['data']['key']);
        $this->assertArrayHasKey('key_prefix', $data['data']);
        $this->assertSame(['read', 'create', 'update', 'delete'], $data['data']['permissions']);
    }

    #[TestDox('Subscriber can create a read-only key (has "read" WP capability)')]
    public function testSubscriberCanCreateReadOnlyKey(): void
    {
        [, , $userId] = $this->createUser(['role' => 'subscriber']);
        wp_set_current_user($userId);

        $response = $this->jsonRequest('POST', self::ROUTE, [
            'name'        => 'sub-read',
            'permissions' => ['read'],
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertSame(['read'], $data['data']['permissions']);
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    #[TestDox('Unauthenticated GET to api-keys returns 401')]
    public function testListRequiresAuthentication(): void
    {
        wp_set_current_user(0);

        $response = $this->request('GET', self::ROUTE);

        $this->assertSame(401, $response->get_status());
    }

    #[TestDox('Subscriber only sees their own keys and the response omits user_id')]
    public function testSubscriberSeesOnlyOwnKeys(): void
    {
        [, , $userA] = $this->createUser(['role' => 'subscriber']);
        [, , $userB] = $this->createUser(['role' => 'subscriber']);

        $keyAId = $this->createKey($userA);
        $this->createKey($userB);

        wp_set_current_user($userA);
        $response = $this->request('GET', self::ROUTE);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertSame(1, $data['data']['total']);

        $ids = array_column($data['data']['items'], 'id');
        $this->assertContains($keyAId, $ids);

        foreach ($data['data']['items'] as $item) {
            $this->assertArrayNotHasKey('user_id', $item);
        }
    }

    #[TestDox('Admin sees all keys and every row includes user_id')]
    public function testAdminSeesAllKeys(): void
    {
        [, , $subId]   = $this->createUser(['role' => 'subscriber']);
        [, , $adminId] = $this->createUser(['role' => 'administrator']);

        $this->createKey($subId);
        $this->createKey($adminId);

        wp_set_current_user($adminId);
        $response = $this->request('GET', self::ROUTE);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertGreaterThanOrEqual(2, $data['data']['total']);

        foreach ($data['data']['items'] as $item) {
            $this->assertArrayHasKey('user_id', $item);
        }
    }

    #[TestDox('List respects per_page and returns matching pagination metadata')]
    public function testListPagination(): void
    {
        [, , $adminId] = $this->createUser(['role' => 'administrator']);

        $this->createKey($adminId);
        $this->createKey($adminId, ['name' => 'key-two']);
        $this->createKey($adminId, ['name' => 'key-three']);

        wp_set_current_user($adminId);
        $response = $this->request('GET', self::ROUTE, ['per_page' => 2, 'page' => 1]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['data']['items']);
        $this->assertSame(2, $data['data']['per_page']);
        $this->assertSame(1, $data['data']['page']);
        $this->assertGreaterThanOrEqual(3, $data['data']['total']);
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    #[TestDox('Unauthenticated PUT to api-keys/{id} returns 401')]
    public function testUpdateRequiresAuthentication(): void
    {
        [, , $userId] = $this->createUser(['role' => 'administrator']);
        $keyId = $this->createKey($userId);

        wp_set_current_user(0);

        $response = $this->jsonRequest('PUT', self::ROUTE . '/' . $keyId, [
            'name'        => 'new-name',
            'permissions' => ['read'],
        ]);

        $this->assertSame(401, $response->get_status());
    }

    #[TestDox('Key owner can update their own key name and permissions')]
    public function testOwnerCanUpdateOwnKey(): void
    {
        [, , $userId] = $this->createUser(['role' => 'subscriber']);
        $keyId = $this->createKey($userId);

        wp_set_current_user($userId);
        $response = $this->jsonRequest('PUT', self::ROUTE . '/' . $keyId, [
            'name'        => 'updated-name',
            'permissions' => ['read'],
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    #[TestDox('A subscriber cannot update another user\'s key')]
    public function testNonOwnerCannotUpdateKey(): void
    {
        [, , $ownerA] = $this->createUser(['role' => 'subscriber']);
        [, , $userB]  = $this->createUser(['role' => 'subscriber']);

        $keyId = $this->createKey($ownerA);

        wp_set_current_user($userB);
        $response = $this->jsonRequest('PUT', self::ROUTE . '/' . $keyId, [
            'name'        => 'stolen',
            'permissions' => ['read'],
        ]);

        $this->assertSame(403, $response->get_status());
        $data = $response->get_data();
        $this->assertSame(self::ERROR_CODE, $data['code']);
        $this->assertSame(ErrorCodes::ERR_API_KEY_UNAUTHORIZED, $data['data']['errorCode']);
    }

    #[TestDox('Administrator can update any user\'s key')]
    public function testAdminCanUpdateAnyKey(): void
    {
        [, , $ownerId] = $this->createUser(['role' => 'subscriber']);
        [, , $adminId] = $this->createUser(['role' => 'administrator']);

        $keyId = $this->createKey($ownerId);

        wp_set_current_user($adminId);
        $response = $this->jsonRequest('PUT', self::ROUTE . '/' . $keyId, [
            'name'        => 'admin-renamed',
            'permissions' => ['read'],
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    #[TestDox('Update with a missing name returns ERR_API_KEY_MISSING_NAME')]
    public function testUpdateMissingNameReturnsError(): void
    {
        [, , $userId] = $this->createUser(['role' => 'administrator']);
        $keyId = $this->createKey($userId);

        wp_set_current_user($userId);
        $response = $this->jsonRequest('PUT', self::ROUTE . '/' . $keyId, [
            'permissions' => ['read'],
        ]);

        $data = $response->get_data();
        $this->assertSame(self::ERROR_CODE, $data['code']);
        $this->assertSame(ErrorCodes::ERR_API_KEY_MISSING_NAME, $data['data']['errorCode']);
    }

    // ─── Revoke (soft-delete) ─────────────────────────────────────────────────

    #[TestDox('Unauthenticated POST to api-keys/{id}/revoke returns 401')]
    public function testRevokeRequiresAuthentication(): void
    {
        [, , $userId] = $this->createUser(['role' => 'administrator']);
        $keyId = $this->createKey($userId);

        wp_set_current_user(0);

        $response = $this->request('POST', self::ROUTE . '/' . $keyId . '/revoke');

        $this->assertSame(401, $response->get_status());
    }

    #[TestDox('Key owner can revoke their own key')]
    public function testOwnerCanRevokeOwnKey(): void
    {
        [, , $userId] = $this->createUser(['role' => 'subscriber']);
        $keyId = $this->createKey($userId);

        wp_set_current_user($userId);
        $response = $this->request('POST', self::ROUTE . '/' . $keyId . '/revoke');

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    #[TestDox('A subscriber cannot revoke another user\'s key')]
    public function testNonOwnerCannotRevokeKey(): void
    {
        [, , $ownerA] = $this->createUser(['role' => 'subscriber']);
        [, , $userB]  = $this->createUser(['role' => 'subscriber']);

        $keyId = $this->createKey($ownerA);

        wp_set_current_user($userB);
        $response = $this->request('POST', self::ROUTE . '/' . $keyId . '/revoke');

        $this->assertSame(403, $response->get_status());
        $data = $response->get_data();
        $this->assertSame(self::ERROR_CODE, $data['code']);
        $this->assertSame(ErrorCodes::ERR_API_KEY_UNAUTHORIZED, $data['data']['errorCode']);
    }

    #[TestDox('Administrator can revoke any user\'s key')]
    public function testAdminCanRevokeAnyKey(): void
    {
        [, , $ownerId] = $this->createUser(['role' => 'subscriber']);
        [, , $adminId] = $this->createUser(['role' => 'administrator']);

        $keyId = $this->createKey($ownerId);

        wp_set_current_user($adminId);
        $response = $this->request('POST', self::ROUTE . '/' . $keyId . '/revoke');

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    // ─── Hard delete ──────────────────────────────────────────────────────────

    #[TestDox('Unauthenticated DELETE to api-keys/{id} returns 401')]
    public function testHardDeleteRequiresAuthentication(): void
    {
        [, , $userId] = $this->createUser(['role' => 'administrator']);
        $keyId = $this->createKey($userId);

        wp_set_current_user(0);

        $response = $this->request('DELETE', self::ROUTE . '/' . $keyId);

        $this->assertSame(401, $response->get_status());
    }

    #[TestDox('Key owner can hard-delete their own key')]
    public function testOwnerCanHardDeleteOwnKey(): void
    {
        [, , $userId] = $this->createUser(['role' => 'subscriber']);
        $keyId = $this->createKey($userId);

        wp_set_current_user($userId);
        $response = $this->request('DELETE', self::ROUTE . '/' . $keyId);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    #[TestDox('A subscriber cannot hard-delete another user\'s key')]
    public function testNonOwnerCannotHardDeleteKey(): void
    {
        [, , $ownerA] = $this->createUser(['role' => 'subscriber']);
        [, , $userB]  = $this->createUser(['role' => 'subscriber']);

        $keyId = $this->createKey($ownerA);

        wp_set_current_user($userB);
        $response = $this->request('DELETE', self::ROUTE . '/' . $keyId);

        $this->assertSame(403, $response->get_status());
        $data = $response->get_data();
        $this->assertSame(self::ERROR_CODE, $data['code']);
        $this->assertSame(ErrorCodes::ERR_API_KEY_UNAUTHORIZED, $data['data']['errorCode']);
    }

    #[TestDox('Administrator can hard-delete any user\'s key')]
    public function testAdminCanHardDeleteAnyKey(): void
    {
        [, , $ownerId] = $this->createUser(['role' => 'subscriber']);
        [, , $adminId] = $this->createUser(['role' => 'administrator']);

        $keyId = $this->createKey($ownerId);

        wp_set_current_user($adminId);
        $response = $this->request('DELETE', self::ROUTE . '/' . $keyId);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }
}
