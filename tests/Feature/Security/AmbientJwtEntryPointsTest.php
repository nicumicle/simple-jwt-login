<?php

namespace SimpleJwtLoginTests\Feature\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Http\Message\ResponseInterface;

/**
 * CSRF matrix: every place where Simple JWT Login reads a JWT (cookie, PHP
 * session, Authorization header) x every way a cross-site page can deliver a
 * request. With the API middleware ("allow JWT usage on all endpoints") this
 * covers core, WooCommerce and WPGraphQL routes, not only the plugin routes.
 *
 * For each entry point the same-origin control MUST produce the side effect
 * (proves the scenario really reaches the code path), and every cross-site
 * delivery MUST NOT. Side effects are read back from the database or from the
 * response, never inferred from a status code alone.
 */
class AmbientJwtEntryPointsTest extends AmbientCredentialTestCase
{
    private const SESSION_DIR = '/tmp';

    /**
     * Entry points (keys of scenario()).
     *
     * @var string[]
     */
    private static $entryPoints = [
        'core REST via API middleware: create administrator',
        'core REST via API middleware: read own private profile',
        'plugin route DELETE /users: delete own account',
        'plugin route POST /auth/revoke: revoke own JWT',
        'plugin route GET /auth/validate: read JWT details',
        'plugin route PUT /user/reset_password: change password with JWT',
        'plugin route GET /autologin: start a WordPress session',
        'plugin route POST /api-keys: create an API key',
        'plugin route GET /revoked-tokens: list revoked tokens',
        'Protect Endpoints: reach a protected endpoint',
        'WooCommerce REST bridge: create a coupon',
        'WPGraphQL bridge: create administrator',
        'session JWT via API middleware: create administrator',
        'session JWT on plugin route DELETE /users: delete own account',
        'Authorization header via API middleware: create administrator',
        'Authorization header on plugin route DELETE /users: delete own account',
        'Authorization header on Protect Endpoints: reach a protected endpoint',
        'Authorization header via WooCommerce bridge: create a coupon',
        'Authorization header via WPGraphQL bridge: create administrator',
        // Not listed: POST /auth/refresh only checks the JWT for revocation, minting
        // needs the opaque refresh token from the request body (not ambient).
        // Not listed: a cookie named like the URL key cannot leak into $_REQUEST,
        // WordPress rebuilds $_REQUEST from $_GET + $_POST (wp_magic_quotes) before
        // rest_api_init. The code guard is covered by AmbientJwtCsrfTest (unit).
    ];

    /**
     * Cross-site deliveries: [headers, navigate].
     *
     * @return array<string,array{array<string,string>, bool}>
     */
    private static function crossSiteDeliveries(): array
    {
        return [
            'attacker Origin + Referer (form / fetch)' => [[
                'Origin'  => self::ATTACKER,
                'Referer' => self::ATTACKER . '/csrf.html',
            ], false],
            'Origin null (sandboxed iframe)' => [['Origin' => 'null'], false],
            'Sec-Fetch-Site cross-site only' => [['Sec-Fetch-Site' => 'cross-site', 'Sec-Fetch-Mode' => 'cors'], false],
            'Referer only (old browser)' => [['Referer' => self::ATTACKER . '/csrf.html'], false],
            'top-level GET navigation with _method' => [[
                'Sec-Fetch-Site' => 'cross-site',
                'Sec-Fetch-Mode' => 'navigate',
            ], true],
        ];
    }

    /**
     * @return array<string,array{string, array<string,string>, bool}>
     */
    public static function crossSiteProvider(): array
    {
        $cases = [];
        foreach (self::$entryPoints as $entryPoint) {
            foreach (self::crossSiteDeliveries() as $delivery => [$headers, $navigate]) {
                $cases[$entryPoint . ' | ' . $delivery] = [$entryPoint, $headers, $navigate];
            }
        }

        return $cases;
    }

    /**
     * @return array<string,array{string}>
     */
    public static function entryPointProvider(): array
    {
        $cases = [];
        foreach (self::$entryPoints as $entryPoint) {
            $cases[$entryPoint] = [$entryPoint];
        }

        return $cases;
    }

    /**
     * @param array<string,string> $headers
     */
    #[DataProvider('crossSiteProvider')]
    #[TestDox('Cross-site: $_dataName has no effect')]
    public function testCrossSiteRequestHasNoEffect(string $entryPoint, array $headers, bool $navigate): void
    {
        [$happened, $response] = $this->attack($entryPoint, $headers, $navigate);

        $this->assertFalse(
            $happened,
            sprintf(
                'CSRF: "%s" succeeded cross-site (HTTP %d): %s',
                $entryPoint,
                $response->getStatusCode(),
                substr((string) $response->getBody(), 0, 300)
            )
        );
    }

    #[DataProvider('entryPointProvider')]
    #[TestDox('Same-origin control: $_dataName still works')]
    public function testSameOriginControlHasEffect(string $entryPoint): void
    {
        [$happened, $response] = $this->attack(
            $entryPoint,
            ['Sec-Fetch-Site' => 'same-origin', 'Origin' => self::API_URL],
            false
        );

        $this->assertTrue(
            $happened,
            sprintf(
                'Control failed, the scenario does not reach "%s" (HTTP %d): %s',
                $entryPoint,
                $response->getStatusCode(),
                substr((string) $response->getBody(), 0, 300)
            )
        );
    }

    /**
     * @param array<string,string> $headers
     * @return array{bool, ResponseInterface}
     */
    private function attack(string $entryPoint, array $headers, bool $navigate): array
    {
        $scenario = $this->scenario($entryPoint);
        $this->applySettings($scenario['settings']);
        if (!empty($scenario['api_keys'])) {
            self::ensureApiKeyTable();
        }

        $actor   = $this->newUser($scenario['actor'] === 'administrator');
        $marker  = 'sjl_csrf_' . bin2hex(random_bytes(5));
        $context = $actor + ['marker' => $marker, 'new_password' => 'Np_' . bin2hex(random_bytes(6))];

        [$credentialHeaders, $cleanup] = $this->deliverCredential($scenario['credential'], $actor['jwt']);
        try {
            $response = $this->send(
                $scenario['method'],
                $scenario['path'],
                $scenario['params']($context),
                $scenario['encoding'],
                $navigate,
                array_merge($headers, $credentialHeaders)
            );
        } finally {
            $cleanup();
        }

        return [(bool) $scenario['effect']($context + ['response' => $response]), $response];
    }

    /**
     * How the browser would hold the JWT: request headers + cleanup.
     *
     * @return array{array<string,string>, callable}
     */
    private function deliverCredential(string $credential, string $jwt): array
    {
        $noop = function () {
        };

        if ($credential === 'header') {
            return [['Authorization' => 'Bearer ' . $jwt], $noop];
        }

        if ($credential === 'session') {
            $sessionId = bin2hex(random_bytes(16));
            $file      = self::SESSION_DIR . '/sess_' . $sessionId;
            file_put_contents($file, 'simple-jwt-login-token|' . serialize($jwt));
            chmod($file, 0666);

            return [['Cookie' => 'PHPSESSID=' . $sessionId], function () use ($file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }];
        }

        return [['Cookie' => 'simple-jwt-login-token=' . $jwt], $noop];
    }

    /**
     * @return array{
     *     settings: array<string,mixed>,
     *     actor: string,
     *     credential: string,
     *     method: string,
     *     path: string,
     *     encoding: string,
     *     params: callable,
     *     effect: callable,
     *     api_keys?: bool
     * }
     */
    private function scenario(string $entryPoint): array
    {
        $cookie     = ['general' => ['request_jwt_cookie' => 1]];
        $middleware = ['general' => ['request_jwt_cookie' => 1, 'api_middleware' => ['enabled' => true]]];
        $session    = ['general' => ['request_jwt_session' => 1]];
        $header     = ['general' => ['request_jwt_header' => 1, 'api_middleware' => ['enabled' => true]]];
        $protectAll = ['protect_endpoint' => [
            'enabled'        => 1,
            'default_action' => 'protect_all',
            'rules_url'      => [],
            'rules_method'   => [],
            'rules_match'    => [],
            'rules_type'     => [],
            'rules_roles'    => [],
        ]];
        $none       = function () {
            return [];
        };
        $isOk       = function (array $outcome) {
            return $outcome['response']->getStatusCode() === 200;
        };
        $createAdmin = function (array $outcome) {
            return [
                'username' => $outcome['marker'],
                'email'    => $outcome['marker'] . '@example.invalid',
                'password' => 'Xk9!qz_' . $outcome['marker'],
                'roles'    => ['administrator'],
            ];
        };
        $adminCreated = function (array $outcome) {
            return $this->userLoginExists($outcome['marker']);
        };
        $base = [
            'actor'      => 'administrator',
            'credential' => 'cookie',
            'encoding'   => 'form',
            'params'     => $none,
            'effect'     => $isOk,
        ];

        $scenarios = [
            'core REST via API middleware: create administrator' => [
                'settings' => $middleware,
                'method'   => 'POST',
                'path'     => '/?rest_route=/wp/v2/users',
                'params'   => $createAdmin,
                'effect'   => $adminCreated,
            ],
            'core REST via API middleware: read own private profile' => [
                'settings' => $middleware,
                'method'   => 'GET',
                'path'     => '/?rest_route=/wp/v2/users/me',
                'params'   => function () {
                    return ['context' => 'edit'];
                },
                'effect'   => function (array $outcome) {
                    $body = $this->json($outcome['response']);
                    return isset($body['email']) && $body['email'] === $outcome['email'];
                },
            ],
            'plugin route DELETE /users: delete own account' => [
                'settings' => $cookie + ['delete_user' => ['enabled' => true]],
                'actor'    => 'subscriber',
                'method'   => 'DELETE',
                'path'     => '/?rest_route=/simple-jwt-login/v1/users',
                'effect'   => function (array $context) {
                    return !$this->userIdExists($context['id']);
                },
            ],
            'plugin route POST /auth/revoke: revoke own JWT' => [
                'settings' => $cookie + ['authorization' => ['revoke_token_enabled' => '1']],
                'actor'    => 'subscriber',
                'method'   => 'POST',
                'path'     => '/?rest_route=/simple-jwt-login/v1/auth/revoke',
            ],
            'plugin route GET /auth/validate: read JWT details' => [
                'settings' => $cookie + ['authorization' => ['validate_token_enabled' => '1']],
                'method'   => 'GET',
                'path'     => '/?rest_route=/simple-jwt-login/v1/auth/validate',
                'effect'   => function (array $outcome) {
                    $body = $this->json($outcome['response']);
                    return !empty($body['success']);
                },
            ],
            'plugin route PUT /user/reset_password: change password with JWT' => [
                'settings' => $cookie + ['reset_password' => ['enabled' => true, 'return_jwt' => true]],
                'actor'    => 'subscriber',
                'method'   => 'PUT',
                'path'     => '/?rest_route=/simple-jwt-login/v1/user/reset_password',
                'params'   => function (array $context) {
                    return ['email' => $context['email'], 'new_password' => $context['new_password']];
                },
                'effect'   => function (array $context) {
                    return $this->authUser($context['email'], $context['new_password'])[0] === 200;
                },
            ],
            'plugin route GET /autologin: start a WordPress session' => [
                'settings' => $cookie + ['login' => ['enabled' => true]],
                'actor'    => 'subscriber',
                'method'   => 'GET',
                'path'     => '/?rest_route=/simple-jwt-login/v1/autologin',
                'effect'   => function (array $outcome) {
                    foreach ($outcome['response']->getHeader('Set-Cookie') as $setCookie) {
                        if (strpos($setCookie, 'wordpress_logged_in_') === 0) {
                            return true;
                        }
                    }
                    return false;
                },
            ],
            'plugin route POST /api-keys: create an API key' => [
                'settings' => $cookie + ['api_keys' => ['enabled' => true]],
                'api_keys' => true,
                'method'   => 'POST',
                'path'     => '/?rest_route=/simple-jwt-login/v1/api-keys',
                'params'   => function (array $context) {
                    return ['name' => $context['marker'], 'permissions' => ['read']];
                },
                'effect'   => function (array $outcome) {
                    $body = $this->json($outcome['response']);
                    return isset($body['data']['key']);
                },
            ],
            'plugin route GET /revoked-tokens: list revoked tokens' => [
                'settings' => $cookie,
                'method'   => 'GET',
                'path'     => '/?rest_route=/simple-jwt-login/v1/revoked-tokens',
            ],
            // EndpointProtectionHandler never receives cookies, only the session.
            'Protect Endpoints: reach a protected endpoint' => [
                'credential' => 'session',
                'settings' => $session + $protectAll,
                'method'   => 'GET',
                'path'     => '/?rest_route=/wp/v2/posts',
            ],
            'WooCommerce REST bridge: create a coupon' => [
                'settings' => $cookie + ['integrations' => ['3rdparty' => ['woocommerce' => ['enabled' => true]]]],
                'method'   => 'POST',
                'path'     => '/?rest_route=/wc/v3/coupons',
                'params'   => function (array $context) {
                    return ['code' => $context['marker'], 'amount' => '100', 'discount_type' => 'percent'];
                },
                'effect'   => function (array $context) {
                    return $this->postExists($context['marker'], 'shop_coupon');
                },
            ],
            'WPGraphQL bridge: create administrator' => [
                'settings' => $cookie + ['integrations' => ['3rdparty' => ['wpgraphql' => ['enabled' => true]]]],
                'method'   => 'POST',
                'path'     => '/?graphql',
                'encoding' => 'json',
                'params'   => function (array $context) {
                    return ['query' => sprintf(
                        'mutation { createUser(input: {username: "%1$s", email: "%1$s@example.invalid", '
                        . 'password: "Xk9!qz_%1$s", roles: ["administrator"]}) { user { databaseId } } }',
                        $context['marker']
                    )];
                },
                'effect'   => $adminCreated,
            ],
            'session JWT via API middleware: create administrator' => [
                'settings'   => ['general' => [
                    'request_jwt_session' => 1,
                    'api_middleware'      => ['enabled' => true],
                ]],
                'credential' => 'session',
                'method'     => 'POST',
                'path'       => '/?rest_route=/wp/v2/users',
                'params'     => $createAdmin,
                'effect'     => $adminCreated,
            ],
            'session JWT on plugin route DELETE /users: delete own account' => [
                'settings'   => $session + ['delete_user' => ['enabled' => true]],
                'credential' => 'session',
                'actor'      => 'subscriber',
                'method'     => 'DELETE',
                'path'       => '/?rest_route=/simple-jwt-login/v1/users',
                'effect'     => function (array $context) {
                    return !$this->userIdExists($context['id']);
                },
            ],
        ];
        $scenarios += [
            'Authorization header via API middleware: create administrator' => [
                'settings'   => $header,
                'credential' => 'header',
                'method'     => 'POST',
                'path'       => '/?rest_route=/wp/v2/users',
                'params'     => $createAdmin,
                'effect'     => $adminCreated,
            ],
            'Authorization header on plugin route DELETE /users: delete own account' => array_merge(
                $scenarios['plugin route DELETE /users: delete own account'],
                ['settings' => $header + ['delete_user' => ['enabled' => true]], 'credential' => 'header']
            ),
            'Authorization header on Protect Endpoints: reach a protected endpoint' => array_merge(
                $scenarios['Protect Endpoints: reach a protected endpoint'],
                ['settings' => $header + $protectAll, 'credential' => 'header']
            ),
            'Authorization header via WooCommerce bridge: create a coupon' => array_merge(
                $scenarios['WooCommerce REST bridge: create a coupon'],
                ['settings' => $header + ['integrations' => ['3rdparty' => ['woocommerce' => ['enabled' => true]]]],
                    'credential' => 'header']
            ),
            'Authorization header via WPGraphQL bridge: create administrator' => array_merge(
                $scenarios['WPGraphQL bridge: create administrator'],
                ['settings' => $header + ['integrations' => ['3rdparty' => ['wpgraphql' => ['enabled' => true]]]],
                    'credential' => 'header']
            ),
        ];

        return array_merge($base, $scenarios[$entryPoint]);
    }
}
