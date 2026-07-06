<?php

namespace SimpleJwtLoginTests\Feature\ApiKeys;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * Integration tests for the API key management REST endpoints.
 *
 * Covers: create, list, update, revoke (soft-delete), hard-delete,
 * unauthenticated access, ownership checks, and validation errors.
 *
 * Authentication for these endpoints is done via JWT passed in the
 * Authorization header (Bearer <token>), which the plugin's
 * buildPermissionCallback resolves to a WordPress user.
 */
class ApiKeyCrudTest extends FeatureTestCase
{
    private const API_KEYS_BASE = '/simple-jwt-login/v1/api-keys';

    // ─── Settings ─────────────────────────────────────────────────────────────

    /**
     * @return array<string,mixed>
     */
    private static function baseSettings(): array
    {
        return [
            'api_keys' => [
                'enabled'     => true,
                'header_name' => 'X-API-Key',
            ],
            'allow_authentication'    => true,
            'jwt_payload'             => ['email', 'exp', 'id', 'iss', 'site', 'username'],
            'jwt_auth_ttl'            => 60,
            'jwt_auth_refresh_ttl'    => '20160',
            'auth_ip'                 => '',
            'auth_requires_auth_code' => false,
            'auth_password_base64'    => false,
            'jwt_auth_iss'            => 'tests',
            'decryption_key'          => 'test-secret',
            'allow_register'          => true,
            'new_user_profile'        => 'subscriber',
            'require_register_auth'   => false,
            'register_ip'             => '',
            'register_domain'         => '',
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
        self::ensureApiKeyTable();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Create an API key via the management REST endpoint and return the data payload.
     *
     * @param string        $jwt
     * @param string        $name
     * @param array<string> $permissions
     * @return array<string,mixed>
     */
    private function createKeyViaApi(string $jwt, string $name, array $permissions): array
    {
        $response = $this->jsonRequest(
            'POST',
            self::API_KEYS_BASE,
            ['name' => $name, 'permissions' => $permissions],
            $this->authHeader($jwt)
        );
        $this->assertSame(200, $response->getStatusCode(), 'pre-condition: create key must succeed');
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        return $body['data'];
    }

    // ─── Tests: create ────────────────────────────────────────────────────────

    #[TestDox('Admin can create an API key via the management endpoint')]
    public function testAdminCanCreateApiKey(): void
    {
        [$email, $password] = $this->createAdminUser();
        $jwt = $this->getJWTForUser($email, $password);

        $response = $this->jsonRequest(
            'POST',
            self::API_KEYS_BASE,
            ['name' => 'my-key', 'permissions' => ['read', 'create']],
            $this->authHeader($jwt)
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $data = $body['data'];
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('key', $data);
        $this->assertStringStartsWith('sjl_', $data['key']);
        $this->assertSame('my-key', $data['name']);
        $this->assertContains('read', $data['permissions']);
        $this->assertContains('create', $data['permissions']);
    }

    #[TestDox('Creating an API key with an expiry date persists expires_at')]
    public function testCreateApiKeyWithExpiryDate(): void
    {
        [$email, $password] = $this->createAdminUser();
        $jwt      = $this->getJWTForUser($email, $password);
        $expiresAt = '2099-12-31 23:59:59';

        $response = $this->jsonRequest(
            'POST',
            self::API_KEYS_BASE,
            ['name' => 'expiring-key', 'permissions' => ['read'], 'expires_at' => $expiresAt],
            $this->authHeader($jwt)
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertSame($expiresAt, $body['data']['expires_at']);
    }

    #[TestDox('Creating an API key without a name returns an error')]
    public function testCreateApiKeyWithoutNameReturnsError(): void
    {
        [$email, $password] = $this->createAdminUser();
        $jwt = $this->getJWTForUser($email, $password);

        $response = $this->jsonRequest(
            'POST',
            self::API_KEYS_BASE,
            ['permissions' => ['read']],
            $this->authHeader($jwt)
        );

        $this->assertNotSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_API_KEY_MISSING_NAME, $body['data']['error_code']);
    }

    #[TestDox('Creating an API key without permissions returns an error')]
    public function testCreateApiKeyWithoutPermissionsReturnsError(): void
    {
        [$email, $password] = $this->createAdminUser();
        $jwt = $this->getJWTForUser($email, $password);

        $response = $this->jsonRequest(
            'POST',
            self::API_KEYS_BASE,
            ['name' => 'no-perms-key'],
            $this->authHeader($jwt)
        );

        $this->assertNotSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_API_KEY_MISSING_PERMISSIONS, $body['data']['error_code']);
    }

    #[TestDox('Creating an API key with an invalid permission string returns an error')]
    public function testCreateApiKeyWithInvalidPermissionReturnsError(): void
    {
        [$email, $password] = $this->createAdminUser();
        $jwt = $this->getJWTForUser($email, $password);

        $response = $this->jsonRequest(
            'POST',
            self::API_KEYS_BASE,
            ['name' => 'bad-perm-key', 'permissions' => ['superpower']],
            $this->authHeader($jwt)
        );

        $this->assertNotSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_API_KEY_INVALID_PERMISSION, $body['data']['error_code']);
    }

    #[TestDox('Unauthenticated POST to api-keys is rejected with 401')]
    public function testUnauthenticatedCreateIsRejectedWith401(): void
    {
        $response = $this->jsonRequest(
            'POST',
            self::API_KEYS_BASE,
            ['name' => 'test', 'permissions' => ['read']]
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    // ─── Tests: list ──────────────────────────────────────────────────────────

    #[TestDox('Admin can list API keys and the response includes user_id')]
    public function testAdminCanListApiKeysWithUserIdField(): void
    {
        [$email, $password] = $this->createAdminUser();
        $jwt = $this->getJWTForUser($email, $password);

        $this->createKeyViaApi($jwt, 'list-test-key', ['read']);

        $response = $this->request('GET', self::API_KEYS_BASE, [], $this->authHeader($jwt));

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('items', $body['data']);
        $this->assertArrayHasKey('total', $body['data']);
        $this->assertGreaterThan(0, $body['data']['total']);
        $this->assertArrayHasKey('user_id', $body['data']['items'][0]);
    }

    #[TestDox('Subscriber listing keys does not see user_id field')]
    public function testSubscriberListDoesNotIncludeUserIdField(): void
    {
        [$subEmail, $subPassword, $subStatus] = $this->createUser();
        $this->assertSame(200, $subStatus, 'register failed');
        $subJwt = $this->getJWTForUser($subEmail, $subPassword);

        // Create a key as subscriber
        $this->createKeyViaApi($subJwt, 'sub-list-key', ['read']);

        $response = $this->request('GET', self::API_KEYS_BASE, [], $this->authHeader($subJwt));

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('items', $body['data']);
        if (!empty($body['data']['items'])) {
            $this->assertArrayNotHasKey('user_id', $body['data']['items'][0]);
        }
    }

    #[TestDox('List response always includes page and per_page metadata')]
    public function testListResponseIncludesPaginationMetadata(): void
    {
        [$email, $password] = $this->createAdminUser();
        $jwt = $this->getJWTForUser($email, $password);

        $response = $this->request('GET', self::API_KEYS_BASE, [], $this->authHeader($jwt));

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('page', $body['data']);
        $this->assertArrayHasKey('per_page', $body['data']);
        $this->assertArrayHasKey('total', $body['data']);
        $this->assertIsInt($body['data']['page']);
        $this->assertIsInt($body['data']['per_page']);
    }

    // ─── Tests: update ────────────────────────────────────────────────────────

    #[TestDox('Admin can update an API key name and permissions')]
    public function testAdminCanUpdateApiKey(): void
    {
        [$email, $password] = $this->createAdminUser();
        $jwt     = $this->getJWTForUser($email, $password);
        $keyData = $this->createKeyViaApi($jwt, 'original-name', ['read']);
        $keyId   = $keyData['id'];

        $response = $this->jsonRequest(
            'PUT',
            self::API_KEYS_BASE . '/' . $keyId,
            ['name' => 'updated-name', 'permissions' => ['read', 'create']],
            $this->authHeader($jwt)
        );

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
    }

    #[TestDox('Updating with ID 0 returns 404 invalid key ID')]
    public function testUpdateWithZeroIdReturns404(): void
    {
        [$email, $password] = $this->createAdminUser();
        $jwt = $this->getJWTForUser($email, $password);

        $response = $this->jsonRequest(
            'PUT',
            self::API_KEYS_BASE . '/0',
            ['name' => 'whatever', 'permissions' => ['read']],
            $this->authHeader($jwt)
        );

        $this->assertSame(404, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_API_KEY_NOT_FOUND, $body['data']['error_code']);
    }

    // ─── Tests: revoke (soft delete) ──────────────────────────────────────────

    #[TestDox('Admin can revoke an API key (soft delete)')]
    public function testAdminCanRevokeApiKey(): void
    {
        [$email, $password] = $this->createAdminUser();
        $jwt     = $this->getJWTForUser($email, $password);
        $keyData = $this->createKeyViaApi($jwt, 'revoke-test', ['create']);
        $keyId   = $keyData['id'];
        $rawKey  = $keyData['key'];

        $revokeResponse = $this->request(
            'POST',
            self::API_KEYS_BASE . '/' . $keyId . '/revoke',
            [],
            $this->authHeader($jwt)
        );

        $this->assertSame(200, $revokeResponse->getStatusCode());
        $body = json_decode($revokeResponse->getBody()->getContents(), true);
        $this->assertTrue($body['success']);

        // Revoked key can no longer create posts
        $postAttempt = $this->jsonRequest(
            'POST',
            '/wp/v2/posts',
            ['title' => 'Revoked key post', 'status' => 'draft'],
            ['X-API-Key' => $rawKey]
        );
        $this->assertSame(401, $postAttempt->getStatusCode());
        $postBody = json_decode($postAttempt->getBody()->getContents(), true);
        $this->assertSame(ErrorCodes::ERR_API_KEY_UNAUTHORIZED, $postBody['data']['error_code']);
    }

    // ─── Tests: hard delete ───────────────────────────────────────────────────

    #[TestDox('Admin can hard-delete an API key')]
    public function testAdminCanHardDeleteApiKey(): void
    {
        [$email, $password] = $this->createAdminUser();
        $jwt     = $this->getJWTForUser($email, $password);
        $keyData = $this->createKeyViaApi($jwt, 'hard-delete-test', ['read']);
        $keyId   = $keyData['id'];

        $deleteResponse = $this->request(
            'DELETE',
            self::API_KEYS_BASE . '/' . $keyId,
            [],
            $this->authHeader($jwt)
        );

        $this->assertSame(200, $deleteResponse->getStatusCode());
        $body = json_decode($deleteResponse->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
    }

    #[TestDox('Hard-deleting with ID 0 returns 404 invalid key ID')]
    public function testHardDeleteWithZeroIdReturns404(): void
    {
        [$email, $password] = $this->createAdminUser();
        $jwt = $this->getJWTForUser($email, $password);

        $deleteResponse = $this->request(
            'DELETE',
            self::API_KEYS_BASE . '/0',
            [],
            $this->authHeader($jwt)
        );

        $this->assertSame(404, $deleteResponse->getStatusCode());
        $body = json_decode($deleteResponse->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_API_KEY_NOT_FOUND, $body['data']['error_code']);
    }

    // ─── Tests: ownership ─────────────────────────────────────────────────────

    #[TestDox('Subscriber cannot revoke an API key owned by another user')]
    public function testSubscriberCannotRevokeOtherUsersKey(): void
    {
        // Admin creates a key
        [$adminEmail, $adminPassword] = $this->createAdminUser();
        $adminJwt = $this->getJWTForUser($adminEmail, $adminPassword);
        $keyData  = $this->createKeyViaApi($adminJwt, 'admin-owned-key', ['read']);
        $keyId    = $keyData['id'];

        // Subscriber tries to revoke it
        [$subEmail, $subPassword, $subStatus] = $this->createUser();
        $this->assertSame(200, $subStatus, 'register failed');
        $subJwt = $this->getJWTForUser($subEmail, $subPassword);

        $revokeResponse = $this->request(
            'POST',
            self::API_KEYS_BASE . '/' . $keyId . '/revoke',
            [],
            $this->authHeader($subJwt)
        );

        $this->assertSame(403, $revokeResponse->getStatusCode());
        $body = json_decode($revokeResponse->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_API_KEY_UNAUTHORIZED, $body['data']['error_code']);
    }

    #[TestDox('Subscriber cannot update an API key owned by another user')]
    public function testSubscriberCannotUpdateOtherUsersKey(): void
    {
        // Admin creates a key
        [$adminEmail, $adminPassword] = $this->createAdminUser();
        $adminJwt = $this->getJWTForUser($adminEmail, $adminPassword);
        $keyData  = $this->createKeyViaApi($adminJwt, 'admin-key-for-update', ['read']);
        $keyId    = $keyData['id'];

        // Subscriber tries to update it
        [$subEmail, $subPassword, $subStatus] = $this->createUser();
        $this->assertSame(200, $subStatus, 'register failed');
        $subJwt = $this->getJWTForUser($subEmail, $subPassword);

        $updateResponse = $this->jsonRequest(
            'PUT',
            self::API_KEYS_BASE . '/' . $keyId,
            ['name' => 'hijacked', 'permissions' => ['read']],
            $this->authHeader($subJwt)
        );

        $this->assertSame(403, $updateResponse->getStatusCode());
        $body = json_decode($updateResponse->getBody()->getContents(), true);
        $this->assertFalse($body['success']);
        $this->assertSame(ErrorCodes::ERR_API_KEY_UNAUTHORIZED, $body['data']['error_code']);
    }

    #[TestDox('Subscriber can manage their own API key')]
    public function testSubscriberCanManageOwnKey(): void
    {
        [$subEmail, $subPassword, $subStatus] = $this->createUser();
        $this->assertSame(200, $subStatus, 'register failed');
        $subJwt  = $this->getJWTForUser($subEmail, $subPassword);
        $keyData = $this->createKeyViaApi($subJwt, 'my-own-key', ['read']);
        $keyId   = $keyData['id'];

        $revokeResponse = $this->request(
            'POST',
            self::API_KEYS_BASE . '/' . $keyId . '/revoke',
            [],
            $this->authHeader($subJwt)
        );

        $this->assertSame(200, $revokeResponse->getStatusCode());
        $body = json_decode($revokeResponse->getBody()->getContents(), true);
        $this->assertTrue($body['success']);
    }
}
