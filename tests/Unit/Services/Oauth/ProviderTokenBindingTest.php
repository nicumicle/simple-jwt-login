<?php

namespace SimpleJwtLoginTests\Unit\Services\Oauth;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\AuditLogSettings;
use SimpleJWTLogin\Modules\Settings\IntegrationsSettings;
use SimpleJWTLogin\Modules\Settings\Oauth\Auth0OauthSettings;
use SimpleJWTLogin\Modules\Settings\Oauth\FacebookOauthSettings;
use SimpleJWTLogin\Modules\Settings\Oauth\GithubOauthSettings;
use SimpleJWTLogin\Modules\Settings\Oauth\GoogleOauthSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\Oauth\Auth0Oauth;
use SimpleJWTLogin\Services\Oauth\FacebookOauth;
use SimpleJWTLogin\Services\Oauth\GithubOauth;
use SimpleJWTLogin\Services\Oauth\GoogleOauth;

/**
 * A provider access_token sent directly to the OAuth endpoint must have been
 * issued for the app configured on this website. Otherwise any other app that
 * obtained a token for a user (e.g. a quiz app) could log in as that user here.
 *
 * The providers are not reachable from the test environment, so remote calls
 * are faked through the wp_remote_request() stub in bootstrap.php.
 */
class ProviderTokenBindingTest extends TestCase
{
    const CLIENT_ID     = 'this-app-id';
    const CLIENT_SECRET = 'this-app-secret';

    /**
     * @var array<int, array{url: string, args: array}>
     */
    private $httpCalls = [];

    /** @var boolean Auth0 "Allow opaque access tokens" option used by makeSettings(). */
    private $allowOpaque = false;

    /** @var boolean Facebook, GitHub and Google "Allow tokens issued for other apps/clients" option. */
    private $allowOtherApps = false;

    public function tearDown(): void
    {
        unset($GLOBALS['sjl_test_http']);
        parent::tearDown();
    }

    /**
     * @param string $providerClass
     * @param string $clientId
     * @param array<string, array{0: int, 1: array}> $responses URL fragment => [status code, body]
     * @param int $expectedErrorCode
     */
    #[DataProvider('directTokenProvider')]
    public function testDirectTokenMustBelongToTheConfiguredApp(
        $providerClass,
        $clientId,
        $responses,
        $expectedErrorCode
    ) {
        $this->fakeHttp($responses);

        $this->expectException(Exception::class);
        $this->expectExceptionCode($expectedErrorCode);

        $oauth = new $providerClass(
            ['access_token' => 'provider-token'],
            'POST',
            $this->makeSettings($clientId),
            $this->createStub(WordPressDataInterface::class)
        );
        $oauth->call();
    }

    /**
     * @return array<string, array>
     */
    public static function directTokenProvider()
    {
        $facebookEmail = ['graph.facebook.com/me' => [200, ['email' => 'victim@example.com']]];
        $githubEmails = ['/user/emails' => [200, [
            ['email' => 'victim@example.com', 'primary' => true, 'verified' => true],
        ]]];

        return [
            'facebook token issued for another app' => [
                FacebookOauth::class,
                self::CLIENT_ID,
                ['/debug_token' => [200, ['data' => ['app_id' => 'other-app-id', 'is_valid' => true]]]]
                    + $facebookEmail,
                ErrorCodes::ERR_FACEBOOK_INVALID_TOKEN,
            ],
            'facebook token reported invalid' => [
                FacebookOauth::class,
                self::CLIENT_ID,
                ['/debug_token' => [200, ['data' => ['app_id' => self::CLIENT_ID, 'is_valid' => false]]]]
                    + $facebookEmail,
                ErrorCodes::ERR_FACEBOOK_INVALID_TOKEN,
            ],
            'facebook debug_token call fails' => [
                FacebookOauth::class,
                self::CLIENT_ID,
                ['/debug_token' => [400, ['error' => ['message' => 'Invalid OAuth access token']]]]
                    + $facebookEmail,
                ErrorCodes::ERR_FACEBOOK_INVALID_TOKEN,
            ],
            'facebook app id not configured' => [
                FacebookOauth::class,
                '',
                ['/debug_token' => [200, ['data' => ['app_id' => '', 'is_valid' => true]]]] + $facebookEmail,
                ErrorCodes::ERR_FACEBOOK_INVALID_TOKEN,
            ],
            'facebook token issued for this app passes validation' => [
                FacebookOauth::class,
                self::CLIENT_ID,
                ['/debug_token' => [200, ['data' => ['app_id' => self::CLIENT_ID, 'is_valid' => true]]]]
                    + $facebookEmail,
                ErrorCodes::ERR_FACEBOOK_USER_NOT_FOUND,
            ],
            'github token issued for another app' => [
                GithubOauth::class,
                self::CLIENT_ID,
                ['/applications/' => [404, ['message' => 'Not Found']]] + $githubEmails,
                ErrorCodes::ERR_GITHUB_INVALID_TOKEN,
            ],
            'github check answers for another client' => [
                GithubOauth::class,
                self::CLIENT_ID,
                ['/applications/' => [200, ['app' => ['client_id' => 'other-app-id']]]] + $githubEmails,
                ErrorCodes::ERR_GITHUB_INVALID_TOKEN,
            ],
            'github client id not configured' => [
                GithubOauth::class,
                '',
                ['/applications/' => [200, ['app' => ['client_id' => '']]]] + $githubEmails,
                ErrorCodes::ERR_GITHUB_INVALID_TOKEN,
            ],
            'github token issued for this app passes validation' => [
                GithubOauth::class,
                self::CLIENT_ID,
                ['/applications/' => [200, ['app' => ['client_id' => self::CLIENT_ID]]]] + $githubEmails,
                ErrorCodes::ERR_GITHUB_USER_NOT_FOUND,
            ],
        ];
    }

    public function testFacebookTokenIsInspectedWithTheAppCredentials()
    {
        $this->fakeHttp([
            '/debug_token' => [200, ['data' => ['app_id' => self::CLIENT_ID, 'is_valid' => true]]],
            'graph.facebook.com/me' => [200, ['email' => 'user@example.com']],
        ]);

        $this->callDirectToken(FacebookOauth::class);

        $this->assertSame(
            'https://graph.facebook.com/' . FacebookOauth::GRAPH_API_VERSION . '/debug_token'
                . '?input_token=provider-token'
                . '&access_token=' . rawurlencode(self::CLIENT_ID . '|' . self::CLIENT_SECRET),
            $this->httpCalls[0]['url']
        );
        $this->assertSame('GET', $this->httpCalls[0]['args']['method']);
        $this->assertStringNotContainsString(self::CLIENT_SECRET, $this->httpCalls[1]['url']);
    }

    /**
     * A token issued for another app or client passes only when the admin opted in.
     *
     * @param string $providerClass
     * @param string $tokenParam
     * @param array<string, array{0: int, 1: array}> $responses URL fragment => [status code, body]
     * @param bool $allowOtherApps
     * @param int $expectedErrorCode
     */
    #[DataProvider('otherAppTokenProvider')]
    public function testTokenFromAnotherAppNeedsTheOptIn(
        $providerClass,
        $tokenParam,
        $responses,
        $allowOtherApps,
        $expectedErrorCode
    ) {
        $this->fakeHttp($responses);
        $this->allowOtherApps = $allowOtherApps;
        $this->expectExceptionCode($expectedErrorCode);

        $oauth = new $providerClass(
            [$tokenParam => 'provider-token'],
            'POST',
            $this->makeSettings(self::CLIENT_ID),
            $this->createStub(WordPressDataInterface::class)
        );
        $oauth->call();
    }

    /**
     * @return array<string, array>
     */
    public static function otherAppTokenProvider()
    {
        $facebook = [
            '/debug_token' => [200, ['data' => ['app_id' => 'other-app-id', 'is_valid' => true]]],
            'graph.facebook.com/me' => [200, ['email' => 'user@example.com']],
        ];
        $github = [
            '/applications/' => [404, ['message' => 'Not Found']],
            '/user/emails' => [200, [['email' => 'user@example.com', 'primary' => true, 'verified' => true]]],
        ];
        $google = ['/tokeninfo' => [200, [
            'iss' => 'https://accounts.google.com',
            'aud' => 'other-client',
            'email' => 'user@example.com',
            'email_verified' => 'true',
        ]]];

        return [
            'facebook other app rejected by default' => [
                FacebookOauth::class, 'access_token', $facebook, false, ErrorCodes::ERR_FACEBOOK_INVALID_TOKEN,
            ],
            'facebook other app allowed reaches the user lookup' => [
                FacebookOauth::class, 'access_token', $facebook, true, ErrorCodes::ERR_FACEBOOK_USER_NOT_FOUND,
            ],
            'github other app rejected by default' => [
                GithubOauth::class, 'access_token', $github, false, ErrorCodes::ERR_GITHUB_INVALID_TOKEN,
            ],
            'github other app allowed reaches the user lookup' => [
                GithubOauth::class, 'access_token', $github, true, ErrorCodes::ERR_GITHUB_USER_NOT_FOUND,
            ],
            'google other client rejected by default' => [
                GoogleOauth::class, 'id_token', $google, false, ErrorCodes::ERR_GOOGLE_ID_TOKEN_INVALID_AUDIENCE,
            ],
            'google other client allowed reaches the user lookup' => [
                GoogleOauth::class, 'id_token', $google, true, ErrorCodes::ERR_GOOGLE_USER_NOT_FOUND,
            ],
        ];
    }

    public function testGithubTokenIsCheckedAgainstTheApplication()
    {
        $this->fakeHttp([
            '/applications/' => [200, ['app' => ['client_id' => self::CLIENT_ID]]],
            '/user/emails' => [200, [['email' => 'user@example.com', 'primary' => true, 'verified' => true]]],
        ]);

        $this->callDirectToken(GithubOauth::class);

        $check = $this->httpCalls[0];
        $this->assertSame('https://api.github.com/applications/' . self::CLIENT_ID . '/token', $check['url']);
        $this->assertSame('POST', $check['args']['method']);
        $this->assertSame(
            'Basic ' . base64_encode(self::CLIENT_ID . ':' . self::CLIENT_SECRET),
            $check['args']['headers']['Authorization']
        );
        $this->assertSame(['access_token' => 'provider-token'], json_decode($check['args']['body'], true));
        $this->assertSame(
            'Bearer provider-token',
            $this->httpCalls[1]['args']['headers']['Authorization']
        );
    }

    /**
     * @param string $clientId
     * @param array $payload
     * @param int|null $expectedErrorCode Null when the token is accepted.
     */
    #[DataProvider('auth0TokenProvider')]
    public function testAuth0TokenMustBelongToTheConfiguredClient($clientId, $payload, $expectedErrorCode)
    {
        $this->fakeHttp(['/userinfo' => [200, ['sub' => 'auth0|1', 'email' => 'user@example.com']]]);
        if ($expectedErrorCode === null) {
            $this->expectNotToPerformAssertions();
        }
        if ($expectedErrorCode !== null) {
            $this->expectException(Exception::class);
            $this->expectExceptionCode($expectedErrorCode);
        }

        Auth0Oauth::validateIdToken(self::auth0Token($payload), $this->makeSettings($clientId));
    }

    /**
     * POST /oauth/token with an Auth0 access_token: the client binding runs before the user lookup.
     *
     * @param array $payload
     * @param int $expectedErrorCode
     */
    #[DataProvider('auth0DirectTokenProvider')]
    public function testAuth0DirectTokenMustBelongToTheConfiguredClient($payload, $expectedErrorCode)
    {
        $this->fakeHttp(['/userinfo' => [200, ['email' => 'victim@example.com', 'email_verified' => true]]]);
        $this->expectExceptionCode($expectedErrorCode);

        $oauth = new Auth0Oauth(
            ['access_token' => self::auth0Token($payload)],
            'POST',
            $this->makeSettings(self::CLIENT_ID),
            $this->createStub(WordPressDataInterface::class)
        );
        $oauth->call();
    }

    /**
     * @return array<string, array>
     */
    public static function auth0DirectTokenProvider()
    {
        return [
            'token issued for another client' => [['azp' => 'other-client'], ErrorCodes::ERR_AUTH0_INVALID_TOKEN],
            'token without azp or aud' => [['sub' => 'auth0|1'], ErrorCodes::ERR_AUTH0_INVALID_TOKEN],
            'token issued for this client reaches the user lookup' => [
                ['azp' => self::CLIENT_ID],
                ErrorCodes::ERR_AUTH0_USER_NOT_FOUND,
            ],
        ];
    }

    /**
     * @param string $token
     * @param bool $allowOpaque
     * @param int $expectedErrorCode
     */
    #[DataProvider('auth0OpaqueTokenProvider')]
    public function testAuth0OpaqueTokenNeedsTheOptIn($token, $allowOpaque, $expectedErrorCode)
    {
        $this->fakeHttp(['/userinfo' => [200, ['email' => 'user@example.com', 'email_verified' => true]]]);
        $this->expectExceptionCode($expectedErrorCode);
        $this->allowOpaque = $allowOpaque;

        $oauth = new Auth0Oauth(
            ['access_token' => $token],
            'POST',
            $this->makeSettings(self::CLIENT_ID),
            $this->createStub(WordPressDataInterface::class)
        );
        $oauth->call();
    }

    /**
     * @return array<string, array>
     */
    public static function auth0OpaqueTokenProvider()
    {
        return [
            'opaque token rejected by default' => ['opaque-token', false, ErrorCodes::ERR_AUTH0_INVALID_TOKEN],
            'opaque token allowed reaches the user lookup' => [
                'opaque-token',
                true,
                ErrorCodes::ERR_AUTH0_USER_NOT_FOUND,
            ],
            'jwt for another client still rejected when opaque tokens are allowed' => [
                self::auth0Token(['azp' => 'other-client']),
                true,
                ErrorCodes::ERR_AUTH0_INVALID_TOKEN,
            ],
        ];
    }

    /**
     * @param array $payload
     * @return string An unsigned JWT: Auth0 /userinfo (faked) is what vouches for it.
     */
    private static function auth0Token(array $payload)
    {
        return 'header.' . rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=') . '.signature';
    }

    /**
     * @return array<string, array>
     */
    public static function auth0TokenProvider()
    {
        return [
            'client id not configured' => ['', ['azp' => 'any-client'], ErrorCodes::ERR_AUTH0_INVALID_TOKEN],
            'token issued for another client' => [
                self::CLIENT_ID,
                ['azp' => 'other-client'],
                ErrorCodes::ERR_AUTH0_INVALID_TOKEN,
            ],
            'token issued for this client' => [self::CLIENT_ID, ['azp' => self::CLIENT_ID], null],
        ];
    }

    /**
     * Auth0 userinfo without "email_verified" must not count as a verified email.
     *
     * @param array $userinfo
     * @param int $expectedErrorCode
     */
    #[DataProvider('auth0EmailVerifiedProvider')]
    public function testAuth0EmailMustBeVerified($userinfo, $expectedErrorCode)
    {
        $this->fakeHttp(['/userinfo' => [200, $userinfo]]);
        $this->expectExceptionCode($expectedErrorCode);

        $oauth = new Auth0Oauth(
            ['access_token' => self::auth0Token(['azp' => self::CLIENT_ID])],
            'POST',
            $this->makeSettings(self::CLIENT_ID),
            $this->createStub(WordPressDataInterface::class)
        );
        $oauth->call();
    }

    /**
     * @return array<string, array>
     */
    public static function auth0EmailVerifiedProvider()
    {
        return [
            'email_verified missing' => [['email' => 'user@example.com'], ErrorCodes::ERR_AUTH0_EMAIL_NOT_VERIFIED],
            'email_verified false' => [
                ['email' => 'user@example.com', 'email_verified' => false],
                ErrorCodes::ERR_AUTH0_EMAIL_NOT_VERIFIED,
            ],
            'email_verified true reaches the user lookup' => [
                ['email' => 'user@example.com', 'email_verified' => true],
                ErrorCodes::ERR_AUTH0_USER_NOT_FOUND,
            ],
        ];
    }

    /**
     * @param string $providerClass
     *
     * @return void
     */
    private function callDirectToken($providerClass)
    {
        $oauth = new $providerClass(
            ['access_token' => 'provider-token'],
            'POST',
            $this->makeSettings(self::CLIENT_ID),
            $this->createStub(WordPressDataInterface::class)
        );
        try {
            $oauth->call();
        } catch (Exception $exception) {
            // The user does not exist: the token checks already ran.
            $this->assertNotSame(0, $exception->getCode());
        }
    }

    /**
     * @param array<string, array{0: int, 1: array}> $responses URL fragment => [status code, body]
     *
     * @return void
     */
    private function fakeHttp($responses)
    {
        $this->httpCalls = [];
        $calls = &$this->httpCalls;
        $GLOBALS['sjl_test_http'] = function ($url, $args) use ($responses, &$calls) {
            $calls[] = ['url' => $url, 'args' => $args];
            foreach ($responses as $fragment => $response) {
                if (strpos($url, $fragment) !== false) {
                    return ['response' => ['code' => $response[0]], 'body' => json_encode($response[1])];
                }
            }

            return ['response' => ['code' => 404], 'body' => ''];
        };
    }

    /**
     * @param string $clientId
     *
     * @return SimpleJWTLoginSettings
     */
    private function makeSettings($clientId)
    {
        $facebook = $this->createStub(FacebookOauthSettings::class);
        $github = $this->createStub(GithubOauthSettings::class);
        $auth0 = $this->createStub(Auth0OauthSettings::class);
        foreach ([$facebook, $github, $auth0] as $providerSettings) {
            $providerSettings->method('getClientId')->willReturn($clientId);
            $providerSettings->method('getClientSecret')->willReturn(self::CLIENT_SECRET);
            $providerSettings->method('isCreateUserIfNotExistsEnabled')->willReturn(false);
        }
        $auth0->method('getDomain')->willReturn('tenant.auth0.com');
        $auth0->method('allowUnverifiedEmail')->willReturn(false);
        $auth0->method('allowOpaqueAccessToken')->willReturn($this->allowOpaque);
        $facebook->method('allowTokensFromOtherApps')->willReturn($this->allowOtherApps);
        $github->method('allowTokensFromOtherApps')->willReturn($this->allowOtherApps);
        $google = (new GoogleOauthSettings())->withSettings([
            'client_id' => $clientId,
            'client_secret' => self::CLIENT_SECRET,
            'allow_tokens_from_other_clients' => $this->allowOtherApps,
        ]);

        $integrations = $this->createStub(IntegrationsSettings::class);
        $integrations->method('google')->willReturn($google);
        $integrations->method('facebook')->willReturn($facebook);
        $integrations->method('github')->willReturn($github);
        $integrations->method('auth0')->willReturn($auth0);

        $auditLog = $this->createStub(AuditLogSettings::class);
        $auditLog->method('isAuditEventEnabled')->willReturn(false);

        $settings = $this->createStub(SimpleJWTLoginSettings::class);
        $settings->method('getIntegrationsSettings')->willReturn($integrations);
        $settings->method('getAuditLogSettings')->willReturn($auditLog);

        return $settings;
    }
}
