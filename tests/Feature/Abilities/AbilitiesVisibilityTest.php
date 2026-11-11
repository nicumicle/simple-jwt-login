<?php

namespace SimpleJwtLoginTests\Feature\Abilities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * End-to-end check of what the WordPress Abilities REST API
 * (/wp-abilities/v1, WordPress 6.9+) exposes about Simple JWT Login.
 *
 * Core lets any user with 'read' list and describe registered abilities.
 * A non-administrator must not learn which Simple JWT Login abilities exist
 * (that would reveal the AI access mode and permissions) nor get any
 * configured value: hidden abilities must answer exactly like abilities
 * that do not exist.
 */
class AbilitiesVisibilityTest extends FeatureTestCase
{
    private const ABILITIES = '/wp-abilities/v1/abilities';
    private const PREFIX    = 'simple-jwt-login/';
    private const SECRET    = 'abilities-visibility-secret-key';

    /**
     * @var array<string,string>|null
     */
    private static $tokens = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $fixture = json_decode(
            (string) file_get_contents(__DIR__ . '/../../Unit/Modules/Settings/fixtures/stored-settings.json'),
            true
        );
        self::updateSimpleJWTOption(array_replace_recursive($fixture, [
            'general'       => [
                'decryption_key' => self::SECRET,
                'api_middleware' => ['enabled' => true],
            ],
            'authorization' => ['enabled' => '1'],
            'register'      => ['enabled' => true, 'new_user_profile' => 'subscriber'],
            'integrations'  => [
                '3rdparty' => [
                    'ai_abilities' => [
                        'enabled'                     => true,
                        'access'                      => 'read_write',
                        'allow_view_secrets'          => true,
                        'allow_change_roles'          => true,
                        'allow_change_auth_providers' => true,
                        'allow_create_api_keys'       => true,
                        'allow_add_webhooks'          => true,
                    ],
                ],
            ],
        ]));
        self::$tokens = null;
    }

    public function setUp(): void
    {
        parent::setUp();
        $probe = $this->get(self::ABILITIES, null);
        if ($probe['status'] === 404) {
            $this->markTestSkipped('The WordPress Abilities API (WordPress 6.9+) is not available.');
        }
    }

    /**
     * @param string $role administrator | subscriber
     * @return string
     */
    private function tokenFor(string $role): string
    {
        if (self::$tokens === null) {
            [$adminEmail, $adminPassword] = $this->createAdminUser();
            [$userEmail, $userPassword, $status] = $this->createUser();
            $this->assertSame(200, $status, 'register failed');
            self::$tokens = [
                'administrator' => (string) $this->getJWTForUser($adminEmail, $adminPassword),
                'subscriber'    => (string) $this->getJWTForUser($userEmail, $userPassword),
            ];
        }

        return self::$tokens[$role];
    }

    /**
     * @param string               $route
     * @param string|null          $role  null = logged out
     * @param array<string,mixed>  $query
     * @return array{status: int, body: mixed, headers: array<string,string[]>}
     */
    private function get(string $route, ?string $role, array $query = []): array
    {
        $uri     = self::API_URL . '?' . http_build_query(array_merge(['rest_route' => $route], $query));
        $headers = $role === null ? [] : $this->authHeader($this->tokenFor($role));
        $response = $this->client->request('GET', $uri, ['http_errors' => false, 'headers' => $headers]);

        return [
            'status'  => $response->getStatusCode(),
            'body'    => json_decode($response->getBody()->getContents(), true),
            'headers' => $response->getHeaders(),
        ];
    }

    /**
     * @param string|null $role
     * @return string[]
     */
    private function listedPluginAbilities(?string $role): array
    {
        $response = $this->get(self::ABILITIES, $role, ['per_page' => 100]);
        $this->assertSame(200, $response['status'], (string) json_encode($response['body']));

        $names = [];
        foreach ((array) $response['body'] as $ability) {
            if (strpos((string) $ability['name'], self::PREFIX) === 0) {
                $names[] = $ability['name'];
            }
        }
        $this->assertSame(
            (string) count((array) $response['body']),
            $response['headers']['X-WP-Total'][0],
            'X-WP-Total must not count hidden abilities'
        );
        sort($names);

        return $names;
    }

    #[TestDox('An administrator sees every Simple JWT Login ability')]
    public function testAdministratorSeesEveryAbility(): void
    {
        $names = $this->listedPluginAbilities('administrator');

        $this->assertContains(self::PREFIX . 'get-settings', $names);
        $this->assertContains(self::PREFIX . 'update-settings', $names);
        $this->assertContains(self::PREFIX . 'create-api-key', $names);
        $this->assertContains(self::PREFIX . 'explain-error', $names);
    }

    #[TestDox('A subscriber only sees explain-error in the abilities list')]
    public function testSubscriberOnlySeesExplainError(): void
    {
        $this->assertSame([self::PREFIX . 'explain-error'], $this->listedPluginAbilities('subscriber'));
    }

    #[TestDox('A logged-out visitor cannot list abilities')]
    public function testLoggedOutCannotListAbilities(): void
    {
        $this->assertSame(401, $this->get(self::ABILITIES, null)['status']);
    }

    /**
     * @return array<string, array{0: string|null, 1: string}>
     */
    public static function hiddenAbilityProvider(): array
    {
        $cases = [];
        foreach (['subscriber' => 'subscriber', 'logged out' => null] as $label => $role) {
            foreach (['get-settings', 'update-settings', 'inspect-jwt', 'create-api-key'] as $slug) {
                $cases[$label . ' / ' . $slug] = [$role, $slug];
            }
        }
        $cases['logged out / explain-error'] = [null, 'explain-error'];

        return $cases;
    }

    #[DataProvider('hiddenAbilityProvider')]
    #[TestDox('Hidden ability $slug answers like a missing one')]
    public function testHiddenAbilityLooksLikeAMissingOne(?string $role, string $slug): void
    {
        foreach (['', '/run'] as $suffix) {
            $hidden  = $this->get(self::ABILITIES . '/' . self::PREFIX . $slug . $suffix, $role);
            $missing = $this->get(self::ABILITIES . '/' . self::PREFIX . 'does-not-exist' . $suffix, $role);

            $this->assertSame($missing['status'], $hidden['status'], $slug . $suffix);
            $this->assertSame($missing['body']['code'] ?? null, $hidden['body']['code'] ?? null, $slug . $suffix);
        }
    }

    #[TestDox('An administrator can describe and run get-settings, secrets masked')]
    public function testAdministratorCanRunGetSettings(): void
    {
        $describe = $this->get(self::ABILITIES . '/' . self::PREFIX . 'get-settings', 'administrator');
        $this->assertSame(200, $describe['status']);

        $run = $this->get(self::ABILITIES . '/' . self::PREFIX . 'get-settings/run', 'administrator');
        $this->assertSame(200, $run['status'], (string) json_encode($run['body']));
        $this->assertArrayHasKey('general', (array) $run['body']['settings']);
    }

    /**
     * @return array<string, array{0: int}>
     */
    public static function errorCodeProvider(): array
    {
        return [
            'wrong credentials'        => [48],
            'signature verification'   => [11],
            'settings: hs key missing' => [2005],
        ];
    }

    #[DataProvider('errorCodeProvider')]
    #[TestDox('A subscriber running explain-error gets no configured value')]
    public function testSubscriberExplainErrorHasNoSettings(int $code): void
    {
        $run = $this->get(
            self::ABILITIES . '/' . self::PREFIX . 'explain-error/run',
            'subscriber',
            ['input' => ['error_code' => $code]]
        );

        $this->assertSame(200, $run['status'], (string) json_encode($run['body']));
        $this->assertSame($code, $run['body']['error_code']);
        $this->assertEmpty($run['body']['settings']);
        $json = (string) json_encode($run['body']);
        $this->assertStringNotContainsString(self::SECRET, $json);
        $this->assertStringNotContainsString('read_write', $json);
    }

    #[TestDox('An administrator running explain-error gets the related values')]
    public function testAdministratorExplainErrorHasSettings(): void
    {
        $run = $this->get(
            self::ABILITIES . '/' . self::PREFIX . 'explain-error/run',
            'administrator',
            ['input' => ['error_code' => 11]]
        );

        $this->assertSame(200, $run['status'], (string) json_encode($run['body']));
        $this->assertNotEmpty($run['body']['settings']);
    }

    #[TestDox('A subscriber cannot discover the Simple JWT Login category in write-only mode')]
    public function testCategoryIsHiddenWhenNothingIsVisible(): void
    {
        $this->tokenFor('subscriber');
        $original = (string) $this->getWpOptionValue('simple_jwt_login_settings');
        $settings = json_decode($original, true);
        $settings['integrations']['3rdparty']['ai_abilities']['access'] = 'write';
        $this->updateWpOption('simple_jwt_login_settings', (string) json_encode($settings));

        try {
            $subscriber = $this->get('/wp-abilities/v1/categories', 'subscriber', ['per_page' => 100]);
            $admin      = $this->get('/wp-abilities/v1/categories', 'administrator', ['per_page' => 100]);
            $slugs      = function (array $response): array {
                return array_column((array) $response['body'], 'slug');
            };

            $this->assertNotContains('simple-jwt-login', $slugs($subscriber));
            $this->assertContains('simple-jwt-login', $slugs($admin));
            $this->assertSame([], $this->listedPluginAbilities('subscriber'));
        } finally {
            $this->updateWpOption('simple_jwt_login_settings', $original);
        }
    }
}
