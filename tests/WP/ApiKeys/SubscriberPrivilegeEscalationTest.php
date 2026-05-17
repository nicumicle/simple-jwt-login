<?php

namespace SimpleJwtLoginTests\WP\ApiKeys;

use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use SimpleJwtLoginTests\WP\WPTestCase;

/**
 * Security test: capability gate on API key creation and defence-in-depth.
 *
 * ## What is verified
 *
 * 1. Subscribers are DENIED creating a key that claims a permission they do
 *    not hold (e.g. "create" requires edit_posts). Response: HTTP 403.
 * 2. Subscribers CAN create a read-only key (they hold the "read" capability).
 * 3. Administrators can create keys with any permission.
 * 4. Defence-in-depth: even if a subscriber were somehow authenticated via an
 *    API key, WordPress capability checks still block POST /wp/v2/posts (403).
 *
 * ## Note on rest_authentication_errors
 *
 * rest_do_request() calls WP_REST_Server::dispatch() which skips
 * serve_request() and therefore never fires check_authentication() /
 * rest_authentication_errors. Tests 2 and 4 simulate the middleware
 * (wp_set_current_user) and assert WordPress capability enforcement directly.
 */
class SubscriberPrivilegeEscalationTest extends WPTestCase
{
    private const API_KEYS_ROUTE = '/simple-jwt-login/v1/api-keys';
    private const WP_POSTS_ROUTE = '/wp/v2/posts';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

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

        global $wpdb;
        (new ApiKeyRepository($wpdb))->createTable();
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

    // ─── Capability gate: creating a key ─────────────────────────────────────

    #[TestDox('Subscriber creating a key with "create" permission is denied with HTTP 403')]
    public function testSubscriberCannotCreateApiKeyWithCreatePermission(): void
    {
        [, , $userId] = $this->createUser(['role' => 'subscriber']);
        wp_set_current_user($userId);

        $response = $this->jsonRequest('POST', self::API_KEYS_ROUTE, [
            'name'        => 'subscriber-create-key',
            'permissions' => ['create'],
        ]);

        // rest_do_request() calls dispatch(), not serve_request(), so the
        // rest_post_dispatch filter (which wraps errors in our success envelope)
        // never fires. The raw WP_Error shape is: {code, message, data:{status, errorCode}}.
        $this->assertSame(403, $response->get_status());
        $data = $response->get_data();
        $this->assertSame('simple_jwt_login_api_key_error', $data['code']);
        $this->assertSame(ErrorCodes::ERR_API_KEY_UNAUTHORIZED, $data['data']['errorCode']);
    }

    #[TestDox('Subscriber can create a read-only API key')]
    public function testSubscriberCanCreateReadOnlyApiKey(): void
    {
        [, , $userId] = $this->createUser(['role' => 'subscriber']);
        wp_set_current_user($userId);

        $response = $this->jsonRequest('POST', self::API_KEYS_ROUTE, [
            'name'        => 'subscriber-read-key',
            'permissions' => ['read'],
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertContains('read', $data['data']['permissions']);
    }

    #[TestDox('Administrator can create a key with any permission')]
    public function testAdminCanCreateApiKeyWithAnyPermission(): void
    {
        [, , $userId] = $this->createUser(['role' => 'administrator']);
        wp_set_current_user($userId);

        $response = $this->jsonRequest('POST', self::API_KEYS_ROUTE, [
            'name'        => 'admin-full-key',
            'permissions' => ['read', 'create', 'update', 'delete'],
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertSame(['read', 'create', 'update', 'delete'], $data['data']['permissions']);
    }

    // ─── Defence-in-depth: subscriber authenticated via key still blocked ─────

    #[TestDox('When authenticated as a subscriber (simulating API key auth), POST /wp/v2/posts is denied')]
    public function testSubscriberAuthenticatedViaApiKeyCannotCreatePost(): void
    {
        [, , $subscriberId] = $this->createUser(['role' => 'subscriber']);

        // Simulate what the API key middleware does: authenticate the request as
        // the subscriber. Even if the subscriber somehow held a create-permissioned
        // key, WordPress capability checks must still deny post creation.
        wp_set_current_user($subscriberId);

        $postResponse = $this->jsonRequest(
            'POST',
            self::WP_POSTS_ROUTE,
            ['title' => 'injected post', 'status' => 'publish']
        );

        $this->assertSame(403, $postResponse->get_status());
    }

    #[TestDox('When authenticated as an administrator (simulating API key auth), POST /wp/v2/posts succeeds')]
    public function testAdminAuthenticatedViaApiKeyCanCreatePost(): void
    {
        [, , $adminId] = $this->createUser(['role' => 'administrator']);
        wp_set_current_user($adminId);

        $postResponse = $this->jsonRequest(
            'POST',
            self::WP_POSTS_ROUTE,
            ['title' => 'admin post via api key', 'status' => 'draft']
        );

        $this->assertSame(201, $postResponse->get_status());
    }
}
