<?php

namespace SimpleJwtLoginTests\Feature\Security;

use GuzzleHttp\Cookie\CookieJar;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * The original Patchstack report: a logged-in administrator (WordPress session
 * cookie) opens a cross-site link carrying two `rest_route` values (plugin
 * namespace first, core route last) and a present-but-invalid nonce.
 * The plugin must never overrule core's `rest_cookie_invalid_nonce`.
 */
class DuplicatedRestRouteCsrfTest extends FeatureTestCase
{
    private const SECRET = 'duplicated-rest-route-secret-key';

    private function loginJar(string $login, string $password): CookieJar
    {
        $jar = CookieJar::fromArray(['wordpress_test_cookie' => 'WP Cookie check'], 'localhost');
        $this->client->request('POST', self::API_URL . '/wp-login.php', [
            'http_errors'     => false,
            'allow_redirects' => false,
            'cookies'         => $jar,
            'form_params'     => ['log' => $login, 'pwd' => $password, 'testcookie' => '1'],
        ]);

        $names = array_map(function ($cookie) {
            return $cookie['Name'];
        }, $jar->toArray());
        $loggedIn = array_filter($names, function ($name) {
            return strpos($name, 'wordpress_logged_in_') === 0;
        });
        $this->assertNotEmpty($loggedIn, 'WordPress login failed: ' . implode(',', $names));

        return $jar;
    }

    /**
     * @return array<string,array{array<string,mixed>}>
     */
    public static function settingsProvider(): array
    {
        return [
            'default settings' => [[]],
            'force login integration on' => [['integrations' => ['3rdparty' => ['force_login' => ['enabled' => true]]]]],
            'force login, cookie source and middleware on' => [[
                'general'      => ['request_jwt_cookie' => 1, 'api_middleware' => ['enabled' => true]],
                'integrations' => ['3rdparty' => ['force_login' => ['enabled' => true]]],
            ]],
        ];
    }

    /**
     * @param array<string,mixed> $overrides
     */
    #[DataProvider('settingsProvider')]
    #[TestDox('A duplicated rest_route with a junk nonce cannot create an administrator ($_dataName)')]
    public function testDuplicatedRestRouteCannotBypassNonce(array $overrides): void
    {
        $fixture = json_decode(
            (string) file_get_contents(__DIR__ . '/../../Unit/Modules/Settings/fixtures/stored-settings.json'),
            true
        );
        self::updateSimpleJWTOption(array_replace_recursive($fixture, [
            'general'  => ['decryption_key' => self::SECRET],
            'register' => ['enabled' => true, 'new_user_profile' => 'subscriber'],
        ], $overrides));

        [$email, $password] = $this->createAdminUser();
        $jar    = $this->loginJar($email, $password);
        $marker = 'sjl_duproute_' . bin2hex(random_bytes(5));
        $query  = 'rest_route=/simple-jwt-login/v1/&rest_route=/wp/v2/users&_method=POST&_wpnonce=0'
            . '&' . http_build_query([
                'username' => $marker,
                'email'    => $marker . '@lab.invalid',
                'password' => 'Xk9!qz_' . bin2hex(random_bytes(6)),
                'roles'    => ['administrator'],
            ]);

        $response = $this->client->request('GET', self::API_URL . '/?' . $query, [
            'http_errors' => false,
            'cookies'     => $jar,
            'headers'     => ['Sec-Fetch-Site' => 'cross-site', 'Referer' => 'http://attacker.example/poc.html'],
        ]);

        $table  = self::getTablePrefix() . 'users';
        $result = self::$dbCon->query("SELECT ID FROM `{$table}` WHERE user_login = '{$marker}' LIMIT 1");

        $this->assertSame(0, $result->num_rows, 'Nonce bypass created a user, status ' . $response->getStatusCode());
        $this->assertSame(403, $response->getStatusCode(), (string) $response->getBody());
    }
}
