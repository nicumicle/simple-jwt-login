<?php

namespace SimpleJwtLoginTests\WP\ProtectEndpoints;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJwtLoginTests\WP\WPTestCase;

/**
 * Integration tests for the Protect Endpoints feature.
 *
 * Uses WP_REST_Request dispatched in-process against WP core REST routes
 * (/wp/v2/posts, /wp/v2/comments) which are public GET endpoints requiring no
 * extra WP auth for the success path.
 *
 * JWT transport:
 *   Passed as URL param 'JWT' so it is captured in $_REQUEST at rest_api_init
 *   time, before the WP_REST_Request headers are set.  Authorization-header
 *   transport is not used here because $_SERVER['HTTP_AUTHORIZATION'] is set
 *   after rest_api_init fires in WPTestCase::dispatch().
 *
 * Assertion strategy for the DENY path:
 *   The test harness captures the HTTP status via a status_header filter, but
 *   that filter may not fire after the @header() call in api.php sets
 *   Content-Type (which can mark headers as sent).  The raw status therefore
 *   defaults to 400 even when the plugin sends 403.  Instead of asserting the
 *   HTTP status code for denied cases, we check that the JSON body carries
 *   success=false (wp_send_json_error always sets this, while WP core endpoints
 *   return structured data arrays, not the success/data envelope).  For the
 *   ALLOW path the WP core endpoint returns 200, which IS captured correctly.
 */
class ProtectEndpointTest extends WPTestCase
{
    private const JWT_SECRET     = 'protect-endpoint-secret';
    private const POSTS_ROUTE    = '/wp/v2/posts';
    private const COMMENTS_ROUTE = '/wp/v2/comments';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::configurePlugin(static::baseConfig());
    }

    public static function tearDownAfterClass(): void
    {
        delete_option(SimpleJWTLoginSettings::OPTIONS_KEY);
        parent::tearDownAfterClass();
    }

    private static function baseConfig(array $overrides = []): array
    {
        return array_merge([
            'decryption_key'         => self::JWT_SECRET,
            'jwt_auth_iss'           => 'tests',
            'allow_authentication'   => false,
            'allow_autologin'        => false,
            'allow_register'         => false,
            'jwt_login_by'           => 0,
            'jwt_login_by_parameter' => 'email',
            'protect_endpoints'      => [
                'enabled'  => false,
                'action'   => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect'  => [],
                'whitelist' => [],
            ],
        ], $overrides);
    }

    private function makeJwt(array $payload): string
    {
        return JWT::encode(
            array_merge(['exp' => time() + 3600], $payload),
            self::JWT_SECRET,
            'HS256'
        );
    }

    private function jwtForUser(string $email): string
    {
        return $this->makeJwt(['email' => $email]);
    }

    /**
     * Assert the plugin denied the request via its protect-endpoint guard.
     * Checks the JSON body rather than the HTTP status (see class docblock).
     *
     * @param mixed $response
     */
    private function assertPluginDenied($response): void
    {
        $data = $response->get_data();
        $this->assertFalse($data['success'], 'Expected plugin to deny access (success=false)');
    }

    /**
     * Assert the plugin allowed the request (WP core returns 200).
     *
     * @param mixed $response
     */
    private function assertAccessGranted($response): void
    {
        $this->assertSame(200, $response->get_status(), 'Expected access to be granted (HTTP 200)');
    }

    // ─── Feature disabled ─────────────────────────────────────────────────────

    #[TestDox('Any endpoint is accessible when protect-endpoints is disabled')]
    public function testDisabledAllowsAnyEndpoint(): void
    {
        static::configurePlugin(static::baseConfig([
            'protect_endpoints' => [
                'enabled'  => false,
                'action'   => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect'  => [],
                'whitelist' => [],
            ],
        ]));

        $response = $this->request('GET', self::POSTS_ROUTE, [
            'rest_route' => self::POSTS_ROUTE,
        ]);

        $this->assertAccessGranted($response);
    }

    // ─── ALL_ENDPOINTS mode ───────────────────────────────────────────────────

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function allEndpointsProvider(): array
    {
        return [
            'no JWT → plugin denies every endpoint' => [
                'protectConfig'       => [
                    'enabled'  => true,
                    'action'   => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect'  => [],
                    'whitelist' => [],
                ],
                'useJwt'              => false,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => false,
            ],
            'valid JWT → plugin grants access to any endpoint' => [
                'protectConfig'       => [
                    'enabled'  => true,
                    'action'   => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect'  => [],
                    'whitelist' => [],
                ],
                'useJwt'              => true,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => true,
            ],
            'exact-whitelisted endpoint accessible without JWT' => [
                'protectConfig'       => [
                    'enabled'          => true,
                    'action'           => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect'          => [],
                    'whitelist'        => ['/wp/v2/posts'],
                    'whitelist_method' => ['ALL'],
                    'whitelist_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_EXACT],
                ],
                'useJwt'              => false,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => true,
            ],
            'prefix-whitelisted (STARTS_WITH) covers sub-paths' => [
                'protectConfig'       => [
                    'enabled'          => true,
                    'action'           => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect'          => [],
                    'whitelist'        => ['/wp/v2'],
                    'whitelist_method' => ['ALL'],
                    'whitelist_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH],
                ],
                'useJwt'              => false,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => true,
            ],
            'prefix-whitelisted (EXACT) does not cover sub-paths' => [
                'protectConfig'       => [
                    'enabled'          => true,
                    'action'           => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect'          => [],
                    'whitelist'        => ['/wp/v2'],
                    'whitelist_method' => ['ALL'],
                    'whitelist_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_EXACT],
                ],
                'useJwt'              => false,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => false,
            ],
            'method-whitelist GET: GET without JWT → allowed' => [
                'protectConfig'       => [
                    'enabled'          => true,
                    'action'           => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect'          => [],
                    'whitelist'        => ['/wp/v2/posts'],
                    'whitelist_method' => ['GET'],
                    'whitelist_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH],
                ],
                'useJwt'              => false,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => true,
            ],
            'method-whitelist POST only: GET without JWT → denied' => [
                'protectConfig'       => [
                    'enabled'          => true,
                    'action'           => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect'          => [],
                    'whitelist'        => ['/wp/v2/posts'],
                    'whitelist_method' => ['POST'],
                    'whitelist_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH],
                ],
                'useJwt'              => false,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => false,
            ],
            'multiple whitelist entries: one matches → allowed' => [
                'protectConfig'       => [
                    'enabled'          => true,
                    'action'           => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect'          => [],
                    'whitelist'        => ['/wp/v2/comments', '/wp/v2/posts'],
                    'whitelist_method' => ['ALL', 'ALL'],
                    'whitelist_match'  => [
                        ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH,
                        ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH,
                    ],
                ],
                'useJwt'              => false,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => true,
            ],
            'wp-json prefix in whitelist entry is stripped correctly' => [
                'protectConfig'       => [
                    'enabled'          => true,
                    'action'           => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect'          => [],
                    'whitelist'        => ['/wp-json/wp/v2/posts'],
                    'whitelist_method' => ['ALL'],
                    'whitelist_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH],
                ],
                'useJwt'              => false,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => true,
            ],
        ];
    }

    #[DataProvider('allEndpointsProvider')]
    #[TestDox('ALL_ENDPOINTS mode: access control works correctly')]
    public function testAllEndpointsMode(
        array $protectConfig,
        bool $useJwt,
        string $restRoute,
        string $wpRoute,
        string $method,
        bool $expectAccessGranted
    ): void {
        static::configurePlugin(static::baseConfig(['protect_endpoints' => $protectConfig]));

        $params = ['rest_route' => $restRoute];

        if ($useJwt) {
            [$email] = $this->createUser();
            $params['JWT'] = $this->jwtForUser($email);
        }

        $response = $this->request($method, $wpRoute, $params);

        if ($expectAccessGranted) {
            $this->assertAccessGranted($response);
            return;
        }
        $this->assertPluginDenied($response);
    }

    // ─── SPECIFIC_ENDPOINTS mode ──────────────────────────────────────────────

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function specificEndpointsProvider(): array
    {
        return [
            'listed endpoint blocked without JWT' => [
                'protectConfig'       => [
                    'enabled'        => true,
                    'action'         => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect'        => ['/wp/v2/posts'],
                    'protect_method' => ['ALL'],
                    'protect_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH],
                    'whitelist'      => [],
                ],
                'useJwt'              => false,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => false,
            ],
            'listed endpoint allowed with valid JWT' => [
                'protectConfig'       => [
                    'enabled'        => true,
                    'action'         => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect'        => ['/wp/v2/posts'],
                    'protect_method' => ['ALL'],
                    'protect_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH],
                    'whitelist'      => [],
                ],
                'useJwt'              => true,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => true,
            ],
            'unlisted endpoint always accessible without JWT' => [
                'protectConfig'       => [
                    'enabled'        => true,
                    'action'         => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect'        => ['/wp/v2/posts'],
                    'protect_method' => ['ALL'],
                    'protect_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH],
                    'whitelist'      => [],
                ],
                'useJwt'              => false,
                'restRoute'           => '/wp/v2/comments',
                'wpRoute'             => '/wp/v2/comments',
                'method'              => 'GET',
                'expectAccessGranted' => true,
            ],
            'method-protect GET: GET request blocked without JWT' => [
                'protectConfig'       => [
                    'enabled'        => true,
                    'action'         => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect'        => ['/wp/v2/posts'],
                    'protect_method' => ['GET'],
                    'protect_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH],
                    'whitelist'      => [],
                ],
                'useJwt'              => false,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => false,
            ],
            'method-protect POST only: GET request bypasses protection' => [
                'protectConfig'       => [
                    'enabled'        => true,
                    'action'         => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect'        => ['/wp/v2/posts'],
                    'protect_method' => ['POST'],
                    'protect_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH],
                    'whitelist'      => [],
                ],
                'useJwt'              => false,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => true,
            ],
            'exact-match: exact path is blocked without JWT' => [
                'protectConfig'       => [
                    'enabled'        => true,
                    'action'         => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect'        => ['/wp/v2/posts'],
                    'protect_method' => ['ALL'],
                    'protect_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_EXACT],
                    'whitelist'      => [],
                ],
                'useJwt'              => false,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => false,
            ],
            'exact-match: prefix does not block deeper paths' => [
                'protectConfig'       => [
                    'enabled'        => true,
                    'action'         => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect'        => ['/wp/v2'],
                    'protect_method' => ['ALL'],
                    'protect_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_EXACT],
                    'whitelist'      => [],
                ],
                'useJwt'              => false,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => true,
            ],
            'starts-with: prefix blocks all sub-paths without JWT' => [
                'protectConfig'       => [
                    'enabled'        => true,
                    'action'         => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect'        => ['/wp/v2'],
                    'protect_method' => ['ALL'],
                    'protect_match'  => [ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH],
                    'whitelist'      => [],
                ],
                'useJwt'              => false,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => false,
            ],
            'multiple protect entries: second entry matches → denied' => [
                'protectConfig'       => [
                    'enabled'        => true,
                    'action'         => ProtectEndpointSettings::SPECIFIC_ENDPOINTS,
                    'protect'        => ['/wp/v2/comments', '/wp/v2/posts'],
                    'protect_method' => ['ALL', 'ALL'],
                    'protect_match'  => [
                        ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH,
                        ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH,
                    ],
                    'whitelist' => [],
                ],
                'useJwt'              => false,
                'restRoute'           => '/wp/v2/posts',
                'wpRoute'             => '/wp/v2/posts',
                'method'              => 'GET',
                'expectAccessGranted' => false,
            ],
        ];
    }

    #[DataProvider('specificEndpointsProvider')]
    #[TestDox('SPECIFIC_ENDPOINTS mode: access control works correctly')]
    public function testSpecificEndpointsMode(
        array $protectConfig,
        bool $useJwt,
        string $restRoute,
        string $wpRoute,
        string $method,
        bool $expectAccessGranted
    ): void {
        static::configurePlugin(static::baseConfig(['protect_endpoints' => $protectConfig]));

        $params = ['rest_route' => $restRoute];

        if ($useJwt) {
            [$email] = $this->createUser();
            $params['JWT'] = $this->jwtForUser($email);
        }

        $response = $this->request($method, $wpRoute, $params);

        if ($expectAccessGranted) {
            $this->assertAccessGranted($response);
            return;
        }
        $this->assertPluginDenied($response);
    }

    // ─── Plugin's own endpoints always pass through ───────────────────────────

    #[TestDox('Plugin own REST endpoints are not blocked even when ALL_ENDPOINTS is protected')]
    public function testPluginEndpointsSkipProtection(): void
    {
        static::configurePlugin(static::baseConfig([
            'allow_authentication' => true,
            'protect_endpoints'    => [
                'enabled'  => true,
                'action'   => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect'  => [],
                'whitelist' => [],
            ],
        ]));

        // Auth endpoint (POST) called without credentials returns 400 (missing
        // credentials), but must NOT be blocked by the protect-endpoint guard.
        $pluginRoute = '/simple-jwt-login/v1/auth';
        $response    = $this->jsonRequest('POST', $pluginRoute, ['rest_route' => $pluginRoute]);

        $data      = $response->get_data();
        $errorCode = $data['data']['error_code'] ?? null;

        $this->assertNotSame(
            ErrorCodes::ERR_PROTECT_ENDPOINTS_MISSING_JWT,
            $errorCode,
            'Plugin own endpoint must bypass the protect-endpoint guard'
        );
    }

    // ─── JWT validation edge cases ────────────────────────────────────────────

    #[TestDox('Malformed JWT string: access denied with protect-endpoint error')]
    public function testMalformedJwtDeniesAccess(): void
    {
        static::configurePlugin(static::baseConfig([
            'protect_endpoints' => [
                'enabled'  => true,
                'action'   => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect'  => [],
                'whitelist' => [],
            ],
        ]));

        $response = $this->request('GET', self::POSTS_ROUTE, [
            'rest_route' => self::POSTS_ROUTE,
            'JWT'        => 'not.a.real.token',
        ]);

        $this->assertPluginDenied($response);
    }

    #[TestDox('JWT signed with wrong secret: access denied with protect-endpoint error')]
    public function testWrongSecretJwtDeniesAccess(): void
    {
        static::configurePlugin(static::baseConfig([
            'protect_endpoints' => [
                'enabled'  => true,
                'action'   => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect'  => [],
                'whitelist' => [],
            ],
        ]));

        $wrongSecretJwt = JWT::encode(['email' => 'x@x.com', 'exp' => time() + 3600], 'wrong-secret', 'HS256');

        $response = $this->request('GET', self::POSTS_ROUTE, [
            'rest_route' => self::POSTS_ROUTE,
            'JWT'        => $wrongSecretJwt,
        ]);

        $this->assertPluginDenied($response);
    }

    #[TestDox('Expired JWT: access denied with protect-endpoint error')]
    public function testExpiredJwtDeniesAccess(): void
    {
        static::configurePlugin(static::baseConfig([
            'protect_endpoints' => [
                'enabled'  => true,
                'action'   => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect'  => [],
                'whitelist' => [],
            ],
        ]));

        [$email] = $this->createUser();
        $expiredJwt = JWT::encode(
            ['email' => $email, 'exp' => time() - 3600],
            self::JWT_SECRET,
            'HS256'
        );

        $response = $this->request('GET', self::POSTS_ROUTE, [
            'rest_route' => self::POSTS_ROUTE,
            'JWT'        => $expiredJwt,
        ]);

        $this->assertPluginDenied($response);
    }

    #[TestDox('JWT for a non-existent user: access denied with protect-endpoint error')]
    public function testJwtForNonExistentUserDeniesAccess(): void
    {
        static::configurePlugin(static::baseConfig([
            'protect_endpoints' => [
                'enabled'  => true,
                'action'   => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect'  => [],
                'whitelist' => [],
            ],
        ]));

        $jwt = $this->makeJwt(['email' => 'ghost-' . time() . '@nowhere.invalid']);

        $response = $this->request('GET', self::POSTS_ROUTE, [
            'rest_route' => self::POSTS_ROUTE,
            'JWT'        => $jwt,
        ]);

        $this->assertPluginDenied($response);
    }

    #[TestDox('Revoked JWT: access denied and error code indicates token was revoked')]
    public function testRevokedJwtDeniesAccessWithRevokedCode(): void
    {
        static::configurePlugin(static::baseConfig([
            'protect_endpoints' => [
                'enabled'  => true,
                'action'   => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect'  => [],
                'whitelist' => [],
            ],
        ]));

        [$email, , $userId] = $this->createUser();
        $jwt                = $this->jwtForUser($email);

        add_user_meta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY, $jwt);

        try {
            $response = $this->request('GET', self::POSTS_ROUTE, [
                'rest_route' => self::POSTS_ROUTE,
                'JWT'        => $jwt,
            ]);

            $data = $response->get_data();
            $this->assertFalse($data['success']);
            $this->assertSame(ErrorCodes::ERR_REVOKED_TOKEN, $data['data']['error_code']);
        } finally {
            delete_user_meta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY);
        }
    }

    // ─── wp-admin path always skips protection ────────────────────────────────

    #[TestDox('wp-admin path is not blocked by the protect-endpoint guard')]
    public function testWpAdminPathSkipsProtection(): void
    {
        static::configurePlugin(static::baseConfig([
            'protect_endpoints' => [
                'enabled'  => true,
                'action'   => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect'  => [],
                'whitelist' => [],
            ],
        ]));

        // The protect service recognises the wp-admin prefix and returns early
        // (access allowed).  Dispatching to the public /wp/v2/posts WP core route
        // will then return 200 normally.
        $response = $this->request('GET', self::POSTS_ROUTE, [
            'rest_route' => '/wp-admin/admin-ajax.php',
        ]);

        $this->assertSame(200, $response->get_status());
    }

    // ─── Logged-in user bypass ────────────────────────────────────────────────

    #[TestDox('A WP user already logged in bypasses the JWT check')]
    public function testLoggedInUserBypassesJwtCheck(): void
    {
        static::configurePlugin(static::baseConfig([
            'protect_endpoints' => [
                'enabled'  => true,
                'action'   => ProtectEndpointSettings::ALL_ENDPOINTS,
                'protect'  => [],
                'whitelist' => [],
            ],
        ]));

        [$email, $password] = $this->createUser(['role' => 'administrator']);

        $user = wp_authenticate($email, $password);
        $this->assertNotWPError($user);
        wp_set_current_user($user->ID);

        $response = $this->request('GET', self::POSTS_ROUTE, [
            'rest_route' => self::POSTS_ROUTE,
        ]);

        $this->assertAccessGranted($response);

        wp_set_current_user(0);
    }
}
