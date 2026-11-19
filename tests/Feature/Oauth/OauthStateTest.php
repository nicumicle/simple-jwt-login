<?php

namespace SimpleJwtLoginTests\Feature\Oauth;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Http\Message\ResponseInterface;
use SimpleJwtLoginTests\Feature\FeatureTestCase;

/**
 * The browser OAuth flow must carry a "state" bound to the visitor's browser (login CSRF).
 *
 * The login button starts at GET /oauth/token?provider=x: the plugin stores a random state in a
 * short-lived HttpOnly cookie and redirects to the provider with it. The provider callback
 * (GET with ?code=) is only accepted when its state matches that cookie.
 */
class OauthStateTest extends FeatureTestCase
{
    private const ROUTE = '/simple-jwt-login/v1/oauth/token';

    private const STATE_ERROR = 'The OAuth state is invalid';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $fixture = json_decode(
            (string) file_get_contents(__DIR__ . '/../../Unit/Modules/Settings/fixtures/stored-settings.json'),
            true
        );
        $provider = [
            'enabled'       => 1,
            'enable_oauth'  => true,
            'client_id'     => 'test-client-id',
            'client_secret' => 'test-client-secret',
        ];
        self::updateSimpleJWTOption(array_replace_recursive($fixture, [
            'general'      => ['decryption_key' => 'test-secret'],
            'integrations' => [
                'oauth' => [
                    'google'   => $provider,
                    'github'   => $provider,
                    'facebook' => $provider,
                    'auth0'    => $provider + ['domain' => 'sjl-test.auth0.com'],
                ],
            ],
        ]));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function providerProvider(): array
    {
        return [
            'google'   => ['google', 'accounts.google.com'],
            'github'   => ['github', 'github.com'],
            'facebook' => ['facebook', 'www.facebook.com'],
            'auth0'    => ['auth0', 'sjl-test.auth0.com'],
        ];
    }

    #[DataProvider('providerProvider')]
    #[TestDox('The login button start step sets a state cookie and sends the same state to $provider')]
    public function testStartRedirectsToProviderWithStateCookie(string $provider, string $providerHost): void
    {
        $response = $this->browserGet(['provider' => $provider]);

        $this->assertSame(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertSame($providerHost, parse_url($location, PHP_URL_HOST));
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('test-client-id', $query['client_id'] ?? null);
        $this->assertStringContainsString('provider=' . $provider, (string) ($query['redirect_uri'] ?? ''));
        $this->assertNotEmpty($query['state'] ?? '');

        $cookie = $this->findStateCookie($response, $provider);
        $this->assertNotNull($cookie, 'The state cookie was not set.');
        $this->assertSame($query['state'], $cookie['value']);
        $this->assertStringContainsStringIgnoringCase('httponly', $cookie['raw']);
    }

    /**
     * @return array<string, array{string, string|null, string|null}>
     */
    public static function badStateProvider(): array
    {
        $rows = [];
        foreach (array_keys(self::providerProvider()) as $provider) {
            $rows[$provider . ': no state, no cookie']      = [$provider, null, null];
            $rows[$provider . ': attacker state, no cookie'] = [$provider, 'attacker-state', null];
            $rows[$provider . ': state differs from cookie'] = [$provider, 'attacker-state', 'victim-state'];
            $rows[$provider . ': cookie, no state']          = [$provider, null, 'victim-state'];
        }

        return $rows;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function providerSlugProvider(): array
    {
        return array_map(static fn (array $row): array => [$row[0]], self::providerProvider());
    }

    #[DataProvider('badStateProvider')]
    #[TestDox('A $provider callback without a matching state is rejected before the code is exchanged')]
    public function testCallbackWithoutMatchingStateIsRejected(string $provider, ?string $state, ?string $cookie): void
    {
        $params = ['provider' => $provider, 'code' => 'attacker-code'];
        if ($state !== null) {
            $params['state'] = $state;
        }
        $cookies = $cookie !== null ? ['sjl_oauth_state_' . $provider => $cookie] : [];

        $response = $this->browserGet($params, $cookies);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString(self::STATE_ERROR, urldecode($response->getHeaderLine('Location')));
    }

    #[DataProvider('providerSlugProvider')]
    #[TestDox('A $provider callback with the state from the cookie goes on to the code exchange')]
    public function testCallbackWithMatchingStateReachesCodeExchange(string $provider): void
    {
        $response = $this->browserGet(
            ['provider' => $provider, 'code' => 'some-code', 'state' => 'browser-state'],
            ['sjl_oauth_state_' . $provider => 'browser-state']
        );

        // The code exchange itself fails (fake credentials), but not because of the state.
        $this->assertSame(302, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringNotContainsString(self::STATE_ERROR, urldecode($response->getHeaderLine('Location')));
    }

    #[DataProvider('providerSlugProvider')]
    #[TestDox('The state and cookie of a real $provider login start are accepted by its callback')]
    public function testRealStartStateIsAcceptedByTheCallback(string $provider): void
    {
        $start = $this->browserGet(['provider' => $provider]);
        parse_str((string) parse_url($start->getHeaderLine('Location'), PHP_URL_QUERY), $query);
        $cookie = $this->findStateCookie($start, $provider);
        $this->assertNotNull($cookie);

        $response = $this->browserGet(
            ['provider' => $provider, 'code' => 'some-code', 'state' => (string) $query['state']],
            ['sjl_oauth_state_' . $provider => $cookie['value']]
        );

        $this->assertSame(302, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringNotContainsString(self::STATE_ERROR, urldecode($response->getHeaderLine('Location')));
    }

    #[DataProvider('providerProvider')]
    #[TestDox('Submitting the $provider button rendered on wp-login.php starts the login')]
    public function testLoginPageButtonStartsTheLogin(string $provider, string $providerHost): void
    {
        $html = (string) $this->client->request('GET', self::API_URL . '/wp-login.php')->getBody();
        $pattern = '#<form method="GET" action="([^"]+)"\s+class="simple-jwt-login-oauth-app '
            . $provider . '">(.*?)</form>#s';
        $this->assertSame(1, preg_match($pattern, $html, $form), 'The ' . $provider . ' button is not rendered.');
        preg_match_all('#<(?:input|button)[^>]*name="([^"]+)"(?:[^>]*value="([^"]*)")?#', $form[2], $inputs, PREG_SET_ORDER);
        $fields = [];
        foreach ($inputs as $input) {
            $fields[html_entity_decode($input[1])] = html_entity_decode($input[2] ?? '');
        }

        // The action uses the site URL (host port); inside the container only its path is reachable.
        $action = (string) parse_url(html_entity_decode($form[1]), PHP_URL_PATH);
        $response = $this->client->request(
            'GET',
            self::API_URL . $action . '?' . http_build_query($fields),
            ['allow_redirects' => false]
        );

        $this->assertSame(302, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame($providerHost, parse_url($response->getHeaderLine('Location'), PHP_URL_HOST));
    }

    #[DataProvider('providerSlugProvider')]
    #[TestDox('A $provider state cannot be replayed on another provider')]
    public function testStateIsBoundToTheProvider(string $provider): void
    {
        $other = $provider === 'google' ? 'github' : 'google';
        $response = $this->browserGet(
            ['provider' => $provider, 'code' => 'some-code', 'state' => 'browser-state'],
            ['sjl_oauth_state_' . $other => 'browser-state']
        );

        $this->assertStringContainsString(self::STATE_ERROR, urldecode($response->getHeaderLine('Location')));
    }

    /**
     * @param array<string, string> $params
     * @param array<string, string> $cookies
     */
    private function browserGet(array $params, array $cookies = []): ResponseInterface
    {
        $cookieHeader = [];
        foreach ($cookies as $name => $value) {
            $cookieHeader[] = $name . '=' . rawurlencode($value);
        }

        return $this->client->request(
            'GET',
            self::API_URL . '?rest_route=' . self::ROUTE . '&' . http_build_query($params),
            [
                'allow_redirects' => false,
                'headers'         => $cookieHeader !== [] ? ['Cookie' => implode('; ', $cookieHeader)] : [],
            ]
        );
    }

    /**
     * @return array{value: string, raw: string}|null
     */
    private function findStateCookie(ResponseInterface $response, string $provider): ?array
    {
        foreach ($response->getHeader('Set-Cookie') as $raw) {
            $pair = explode(';', $raw, 2)[0];
            [$name, $value] = array_pad(explode('=', $pair, 2), 2, '');
            if ($name === 'sjl_oauth_state_' . $provider && $value !== '' && $value !== 'deleted') {
                return ['value' => urldecode($value), 'raw' => $raw];
            }
        }

        return null;
    }
}
