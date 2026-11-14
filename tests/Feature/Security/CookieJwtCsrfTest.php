<?php

namespace SimpleJwtLoginTests\Feature\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * CSRF through a JWT stored in a cookie.
 *
 * With "JWT from cookie" and the API middleware enabled, the browser attaches the
 * JWT cookie to every request, including requests started by another site.
 * A cross-site page must not be able to act as the logged-in user, e.g. create
 * an administrator through POST /wp/v2/users. The proof is the wp_users table,
 * not the status code.
 */
class CookieJwtCsrfTest extends FeatureTestCase
{
    private const SECRET   = 'cookie-jwt-csrf-secret-key';
    private const COOKIE   = 'simple-jwt-login-token';
    private const ATTACKER = 'http://attacker.example';

    /**
     * @var string|null
     */
    private static $adminJwt = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $fixture = json_decode(
            (string) file_get_contents(__DIR__ . '/../../Unit/Modules/Settings/fixtures/stored-settings.json'),
            true
        );
        self::updateSimpleJWTOption(array_replace_recursive($fixture, [
            'general'       => [
                'decryption_key'     => self::SECRET,
                'request_jwt_cookie' => 1,
                'request_jwt_header' => 1,
                'api_middleware'     => ['enabled' => true],
            ],
            'authorization' => ['enabled' => '1'],
            'register'      => ['enabled' => true, 'new_user_profile' => 'subscriber'],
            'cors'          => ['enabled' => 0],
        ]));
        self::$adminJwt = null;
    }

    private function adminJwt(): string
    {
        if (self::$adminJwt === null) {
            [$email, $password] = $this->createAdminUser();
            self::$adminJwt = (string) $this->getJWTForUser($email, $password);
        }

        return self::$adminJwt;
    }

    private function userExists(string $login): bool
    {
        $table   = self::getTablePrefix() . 'users';
        $escaped = self::$dbCon->real_escape_string($login);
        $result  = self::$dbCon->query("SELECT ID FROM `{$table}` WHERE user_login = '{$escaped}' LIMIT 1");

        return $result->num_rows > 0;
    }

    /**
     * @param string $login
     * @return array<string,mixed>
     */
    private function newAdminFields(string $login): array
    {
        return [
            'username' => $login,
            'email'    => $login . '@example.invalid',
            'password' => 'Xk9!qz_' . bin2hex(random_bytes(6)),
            'roles'    => ['administrator'],
        ];
    }

    /**
     * POST form, like the auto-submitting form of the reported PoC.
     *
     * @param array<string,string> $headers
     * @return int
     */
    private function postCreateUser(string $login, array $headers): int
    {
        $response = $this->client->request('POST', self::API_URL . '?rest_route=/wp/v2/users', [
            'http_errors' => false,
            'headers'     => $headers,
            'form_params' => $this->newAdminFields($login),
        ]);

        return $response->getStatusCode();
    }

    /**
     * Top-level GET navigation turned into a POST through `_method`.
     *
     * @param array<string,string> $headers
     * @return int
     */
    private function navigateCreateUser(string $login, array $headers): int
    {
        $query = http_build_query(array_merge(
            ['rest_route' => '/wp/v2/users', '_method' => 'POST'],
            $this->newAdminFields($login)
        ));
        $response = $this->client->request('GET', self::API_URL . '/?' . $query, [
            'http_errors' => false,
            'headers'     => $headers,
        ]);

        return $response->getStatusCode();
    }

    private function cookieHeader(): string
    {
        return self::COOKIE . '=' . $this->adminJwt();
    }

    /**
     * @return array<string,array{string, array<string,string>}>
     */
    public static function crossSiteProvider(): array
    {
        return [
            'POST from attacker origin (reported PoC)' => ['post', [
                'Origin'  => self::ATTACKER,
                'Referer' => self::ATTACKER . '/csrf.html',
            ]],
            'POST with Origin null' => ['post', ['Origin' => 'null']],
            'POST cross-site fetch metadata only' => ['post', ['Sec-Fetch-Site' => 'cross-site']],
            'POST from attacker referer only' => ['post', ['Referer' => self::ATTACKER . '/csrf.html']],
            'GET navigation with _method=POST' => ['navigate', ['Sec-Fetch-Site' => 'cross-site']],
            'GET navigation with _method=POST and referer' => ['navigate', [
                'Sec-Fetch-Site' => 'cross-site',
                'Referer'        => self::ATTACKER . '/csrf.html',
            ]],
            'POST cross-site metadata with spoofed same Origin is still cross-site' => ['post', [
                'Sec-Fetch-Site' => 'cross-site',
                'Origin'         => self::ATTACKER,
            ]],
        ];
    }

    /**
     * @param string               $delivery post | navigate
     * @param array<string,string> $headers
     */
    #[DataProvider('crossSiteProvider')]
    #[TestDox('A cross-site request carrying the JWT cookie cannot create an administrator ($_dataName)')]
    public function testCrossSiteRequestWithJwtCookieCannotCreateAdministrator(string $delivery, array $headers): void
    {
        $login   = 'sjl_csrf_' . bin2hex(random_bytes(5));
        $headers = array_merge($headers, ['Cookie' => $this->cookieHeader()]);

        $status = $delivery === 'post'
            ? $this->postCreateUser($login, $headers)
            : $this->navigateCreateUser($login, $headers);

        $this->assertFalse($this->userExists($login), 'Cross-site request created a user (CSRF), status ' . $status);
        $this->assertNotSame(201, $status);
    }

    /**
     * Sites "next to" this one: same registrable domain, same host on another
     * port or scheme, or a look-alike host. The browser still attaches the JWT
     * cookie (cookies ignore ports, and SameSite=Lax allows same-site requests).
     *
     * @return array<string,array{string, string, array<string,string>}>
     */
    public static function nearbySiteProvider(): array
    {
        $deliveries = [
            'sibling subdomain POST' => ['post', [
                'Sec-Fetch-Site' => 'same-site',
                'Origin'         => 'http://evil.localhost',
                'Referer'        => 'http://evil.localhost/csrf.html',
            ]],
            'sibling subdomain GET navigation, Origin and Referer hidden' => ['navigate', [
                'Sec-Fetch-Site' => 'same-site',
            ]],
            'sibling subdomain, old browser (Referer only)' => ['post', ['Referer' => 'http://evil.localhost/csrf.html']],
            'same host, other port' => ['post', ['Sec-Fetch-Site' => 'same-site', 'Origin' => 'http://localhost:8080']],
            'same host, other port, GET navigation' => ['navigate', [
                'Sec-Fetch-Site' => 'same-site',
                'Referer'        => 'http://localhost:8080/csrf.html',
            ]],
            'same host, other scheme' => ['post', ['Sec-Fetch-Site' => 'same-site', 'Origin' => 'https://localhost']],
            'look-alike host' => ['post', ['Sec-Fetch-Site' => 'cross-site', 'Origin' => 'http://localhost.attacker.example']],
            'look-alike host in Referer only' => ['post', ['Referer' => 'http://attacker.example/localhost/csrf.html']],
        ];

        $cases = [];
        foreach (['cookie', 'header'] as $credential) {
            foreach ($deliveries as $name => [$delivery, $headers]) {
                $cases[$name . ' | JWT in ' . $credential] = [$credential, $delivery, $headers];
            }
        }

        return $cases;
    }

    /**
     * @param string               $credential cookie | header
     * @param string               $delivery   post | navigate
     * @param array<string,string> $headers
     */
    #[DataProvider('nearbySiteProvider')]
    #[TestDox('A request from a nearby site cannot create an administrator ($_dataName)')]
    public function testNearbySiteCannotCreateAdministrator(string $credential, string $delivery, array $headers): void
    {
        $login   = 'sjl_nearby_' . bin2hex(random_bytes(5));
        $headers = array_merge(
            $headers,
            $credential === 'header' ? $this->authHeader($this->adminJwt()) : ['Cookie' => $this->cookieHeader()]
        );

        $status = $delivery === 'post'
            ? $this->postCreateUser($login, $headers)
            : $this->navigateCreateUser($login, $headers);

        $this->assertFalse($this->userExists($login), 'Nearby site created a user (CSRF), status ' . $status);
        $this->assertNotSame(201, $status);
    }

    /**
     * @return array<string,array{array<string,string>}>
     */
    public static function sameSiteProvider(): array
    {
        return [
            'same-origin fetch metadata' => [['Sec-Fetch-Site' => 'same-origin']],
            'Origin equal to the request host' => [['Origin' => self::API_URL]],
            'Referer from the same host' => [['Referer' => self::API_URL . '/wp-admin/']],
            'non-browser client (no provenance headers)' => [[]],
        ];
    }

    /**
     * @param array<string,string> $headers
     */
    #[DataProvider('sameSiteProvider')]
    #[TestDox('A same-site request still authenticates with the JWT cookie ($_dataName)')]
    public function testSameSiteRequestWithJwtCookieStillWorks(array $headers): void
    {
        $login  = 'sjl_same_' . bin2hex(random_bytes(5));
        $status = $this->postCreateUser($login, array_merge($headers, ['Cookie' => $this->cookieHeader()]));

        $this->assertSame(201, $status);
        $this->assertTrue($this->userExists($login));
    }

    #[TestDox('A cookie named like the URL key cannot reach the URL source through $_REQUEST')]
    public function testCookieNamedLikeUrlKeyIsStillAmbient(): void
    {
        $jwt = $this->adminJwt();
        $option = json_decode((string) $this->getWpOptionValue('simple_jwt_login_settings'), true);
        $option['general']['request_keys']['cookie'] = 'JWT';
        $option['general']['request_keys']['url']    = 'JWT';
        self::updateSimpleJWTOption($option);

        try {
            $login  = 'sjl_urlkey_' . bin2hex(random_bytes(5));
            $status = $this->postCreateUser($login, [
                'Origin'         => self::ATTACKER,
                'Sec-Fetch-Site' => 'cross-site',
                'Cookie'         => 'JWT=' . $jwt,
            ]);

            $this->assertFalse($this->userExists($login), 'Cookie JWT read via $_REQUEST, status ' . $status);
        } finally {
            $option['general']['request_keys']['cookie'] = self::COOKIE;
            self::updateSimpleJWTOption($option);
        }
    }

    /**
     * @return array<string,array{string, array<string,string>, array<string,mixed>, bool}>
     */
    public static function corsProvider(): array
    {
        $attacker = ['Origin' => self::ATTACKER, 'Sec-Fetch-Site' => 'cross-site'];
        $app      = ['Origin' => 'https://app.example', 'Sec-Fetch-Site' => 'cross-site'];
        $cors     = function (string $origins): array {
            return ['enabled' => 1, 'allow_origin_enabled' => true, 'allow_origin' => $origins];
        };
        $off      = ['enabled' => 0];

        return [
            'header from attacker origin, CORS off' => ['header', $attacker, $off, false],
            'header from attacker origin, other origin listed' => ['header', $attacker, $cors('https://app.example'), false],
            'header from attacker referer only' => ['header', ['Referer' => self::ATTACKER . '/x'], $off, false],
            'header from listed origin' => ['header', $app, $cors('https://app.example'), true],
            'header from any origin, CORS "*"' => ['header', $attacker, $cors('*'), true],
            'header from non-browser client' => ['header', [], $off, true],
            'header from same origin' => ['header', ['Sec-Fetch-Site' => 'same-origin'], $off, true],
            'cookie from attacker origin, CORS "*"' => ['cookie', $attacker, $cors('*'), false],
            'cookie from attacker origin, "*" next to another origin' => [
                'cookie', $attacker, $cors('https://app.example, *'), false,
            ],
            'cookie from listed origin' => ['cookie', $app, $cors('https://app.example'), true],
        ];
    }

    /**
     * @param array<string,string> $headers
     * @param array<string,mixed>  $cors
     */
    #[DataProvider('corsProvider')]
    #[TestDox('Cross-site JWT and the CORS allowlist ($_dataName)')]
    public function testCrossSiteJwtFollowsCorsAllowlist(
        string $credential,
        array $headers,
        array $cors,
        bool $allowed
    ): void {
        $jwt    = $this->adminJwt();
        $option = json_decode((string) $this->getWpOptionValue('simple_jwt_login_settings'), true);
        $saved  = $option['cors'];
        $option['cors'] = array_merge($saved, $cors);
        self::updateSimpleJWTOption($option);

        try {
            $login  = 'sjl_cors_' . bin2hex(random_bytes(5));
            $status = $this->postCreateUser($login, array_merge(
                $headers,
                $credential === 'header' ? $this->authHeader($jwt) : ['Cookie' => self::COOKIE . '=' . $jwt]
            ));

            $this->assertSame($allowed, $this->userExists($login), 'status ' . $status);
        } finally {
            $option['cors'] = $saved;
            self::updateSimpleJWTOption($option);
        }
    }
}
