<?php

namespace SimpleJwtLoginTests\Feature\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * Force Login integration: anonymous requests must not skip Force Login.
 *
 * tests/Feature/fixtures/sjl-test-force-login.php plays the Force Login plugin.
 */
class ForceLoginDuplicatedRestRouteTest extends FeatureTestCase
{
    private const MU_PLUGINS_DIR = '/var/www/html/wp-content/mu-plugins';
    private const FIXTURE_FILE   = 'sjl-test-force-login.php';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (!is_dir(self::MU_PLUGINS_DIR)) {
            mkdir(self::MU_PLUGINS_DIR, 0777, true);
        }
        copy(__DIR__ . '/../fixtures/' . self::FIXTURE_FILE, self::MU_PLUGINS_DIR . '/' . self::FIXTURE_FILE);
    }

    public static function tearDownAfterClass(): void
    {
        // With process isolation this also runs in every test process.
        if (is_file(self::MU_PLUGINS_DIR . '/' . self::FIXTURE_FILE)) {
            unlink(self::MU_PLUGINS_DIR . '/' . self::FIXTURE_FILE);
        }
        parent::tearDownAfterClass();
    }

    /**
     * @return array<string, array{bool, string, bool}>
     */
    public static function requestProvider(): array
    {
        return [
            'baseline core route is blocked' => [
                true, '/?rest_route=/wp/v2/users', true,
            ],
            'duplicated rest_route, plugin auth route first' => [
                true, '/?rest_route=/simple-jwt-login/v1/auth&rest_route=/wp/v2/users', true,
            ],
            'duplicated rest_route, plugin namespace first' => [
                true, '/?rest_route=/simple-jwt-login/v1/&rest_route=/wp/v2/users', true,
            ],
            'pretty plugin URL with a core rest_route query var' => [
                true, '/wp-json/simple-jwt-login/v1/auth?rest_route=/wp/v2/users', true,
            ],
            'integration off, duplicated rest_route is blocked' => [
                false, '/?rest_route=/simple-jwt-login/v1/auth&rest_route=/wp/v2/users', true,
            ],
            'control: plugin route stays reachable' => [
                true, '/?rest_route=/simple-jwt-login/v1/auth', false,
            ],
        ];
    }

    #[DataProvider('requestProvider')]
    #[TestDox('An anonymous request cannot use a plugin route to skip Force Login ($_dataName)')]
    public function testAnonymousRequestCannotBypassForceLogin(
        bool $integrationEnabled,
        string $path,
        bool $expectBlocked
    ): void {
        self::updateSimpleJWTOption([
            '_schema_version' => 2,
            'general'         => ['decryption_key' => 'force-login-duplicated-route-secret'],
            'integrations'    => ['3rdparty' => ['force_login' => ['enabled' => $integrationEnabled]]],
        ]);

        // Send the real Host, like a browser would.
        $response = $this->client->request('GET', self::API_URL . $path, [
            'http_errors' => false,
            'headers'     => ['X-Sjl-Test-Force-Login' => '1', 'Host' => self::siteHost()],
        ]);
        $body = json_decode((string) $response->getBody(), true);
        $code = is_array($body) && isset($body['code']) ? $body['code'] : null;

        if (!$expectBlocked) {
            $this->assertNotSame('rest_unauthorized', $code, (string) $response->getBody());
            return;
        }
        $this->assertSame(401, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('rest_unauthorized', $code);
    }

    private static function siteHost(): string
    {
        $table  = self::getTablePrefix() . 'options';
        $result = self::$dbCon->query("SELECT option_value FROM `{$table}` WHERE option_name = 'siteurl' LIMIT 1");
        $parts  = parse_url((string) $result->fetch_row()[0]);

        return $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }
}
