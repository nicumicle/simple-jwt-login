<?php

namespace SimpleJwtLoginTests\Unit\Services\Oauth;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\Oauth\AbstractOauth;

/**
 * Concrete subclass used purely for testing handleOauth().
 * exchangeCode() is overridden to return whatever $stubbedExchangeResult holds,
 * avoiding any wp_remote_post global-function calls in unit tests.
 */
class ConcreteOauth extends AbstractOauth
{
    /** @var array{status_code: int, response: array|null} */
    public $stubbedExchangeResult = ['status_code' => 200, 'response' => []];

    public function exchangeCode($code, $redirectUri)
    {
        return $this->stubbedExchangeResult;
    }

    public function getAuthUrl()
    {
        return 'https://provider.example.com/oauth/authorize';
    }

    protected function getTokenEndpoint()
    {
        return 'https://provider.example.com/oauth/token';
    }

    protected function getClientId()
    {
        return 'test-client-id';
    }

    protected function getClientSecret()
    {
        return 'test-client-secret';
    }

    protected function getSavedRedirectUri()
    {
        return 'https://example.com/oauth/callback';
    }

    protected function getProviderSlug()
    {
        return 'testprovider';
    }

    protected function isCreateUserEnabled()
    {
        return false;
    }

    protected function getEmailFromTokenResponse($tokenResponse)
    {
        if (empty($tokenResponse['access_token'])) {
            throw new Exception('Provider did not return an access_token.');
        }
        return 'user@example.com';
    }

    protected function validateProviderToken($token)
    {
    }

    protected function getInvalidTokenErrorCode()
    {
        return 9001;
    }

    protected function getUserNotFoundErrorCode()
    {
        return 9002;
    }

    protected function getTokenParamName()
    {
        return 'access_token';
    }

    protected function getMissingParamErrorCode()
    {
        return 9003;
    }

    protected function getInvalidCodeErrorCode()
    {
        return 9004;
    }

    protected function getEmailFromDirectToken($token)
    {
        return 'user@example.com';
    }
}

class AbstractOauthHandleOauthTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface */
    private $wpData;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SimpleJWTLoginSettings */
    private $settings;

    public function setUp(): void
    {
        parent::setUp();

        $this->wpData = $this->createMock(WordPressDataInterface::class);

        $generalSettings = $this->createStub(GeneralSettings::class);
        $generalSettings->method('isSafeRedirectEnabled')->willReturn(false);

        $this->settings = $this->createStub(SimpleJWTLoginSettings::class);
        $this->settings->method('getGeneralSettings')->willReturn($generalSettings);
        $this->settings->method('generateExampleLink')
            ->willReturn('http://localhost/wp-json/simple-jwt-login/v1/oauth/token?provider=testprovider');
    }

    private function makeOauth($request = [], $method = 'GET')
    {
        return new ConcreteOauth($request, $method, $this->settings, $this->wpData);
    }

    public static function errorResponseProvider()
    {
        return [
            'non-200 status code' => [
                'exchangeResult' => [
                    'status_code' => 400,
                    'response'    => [
                        'error'             => 'invalid_grant',
                        'error_description' => 'Token has been expired or revoked.',
                    ],
                ],
                'expectedErrorFragment' => 'Token has been expired or revoked.',
            ],
            '200 with error body - bad code' => [
                'exchangeResult' => [
                    'status_code' => 200,
                    'response'    => [
                        'error'             => 'bad_verification_code',
                        'error_description' => 'The code passed is incorrect or expired.',
                    ],
                ],
                'expectedErrorFragment' => 'The code passed is incorrect or expired.',
            ],
            '200 with error body - redirect uri mismatch' => [
                'exchangeResult' => [
                    'status_code' => 200,
                    'response'    => [
                        'error'             => 'redirect_uri_mismatch',
                        'error_description' => 'The redirect_uri MUST match the registered callback URL.',
                    ],
                ],
                'expectedErrorFragment' => 'The redirect_uri MUST match the registered callback URL.',
            ],
            '200 with error body - incorrect credentials' => [
                'exchangeResult' => [
                    'status_code' => 200,
                    'response'    => [
                        'error'             => 'incorrect_client_credentials',
                        'error_description' => 'The client_id and/or client_secret passed are incorrect.',
                    ],
                ],
                'expectedErrorFragment' => 'The client_id and/or client_secret passed are incorrect.',
            ],
        ];
    }

    #[DataProvider('errorResponseProvider')]
    public function testHandleOauthRedirectsToLoginWithErrorOnFailedExchange(
        $exchangeResult,
        $expectedErrorFragment
    ) {
        $capturedUrl = null;

        $this->wpData->method('getLoginURL')
            ->willReturnCallback(function ($params) {
                return 'http://localhost/wp-login.php?' . http_build_query($params);
            });

        $this->wpData->expects($this->once())
            ->method('redirect')
            ->willReturnCallback(function ($url) use (&$capturedUrl) {
                $capturedUrl = $url;
            });

        $this->wpData->method('triggerAction')->willReturn(null);

        $oauth = $this->makeOauth(['code' => 'test-code-abc']);
        $oauth->stubbedExchangeResult = $exchangeResult;

        $oauth->handleOauth('test-code-abc');

        $this->assertNotNull($capturedUrl, 'redirect() should have been called');
        $this->assertStringContainsString(
            urlencode($expectedErrorFragment),
            $capturedUrl
        );
    }

    public function testHandleOauthProceedsToEmailFetchOnSuccess()
    {
        $user = new \stdClass();
        $user->ID = 1;
        $user->user_email = 'user@example.com';

        $this->wpData->method('getUserDetailsByEmail')->willReturn($user);
        $this->wpData->method('getUserProperty')
            ->willReturnCallback(function ($user, $prop) {
                return $user->$prop;
            });
        $this->wpData->method('triggerAction')->willReturn(null);
        $this->wpData->method('getAdminUrl')->willReturn('http://localhost/wp-admin/');
        $this->wpData->expects($this->once())->method('redirect')
            ->with('http://localhost/wp-admin/');

        $oauth = $this->makeOauth(['code' => 'valid-code']);
        $oauth->stubbedExchangeResult = [
            'status_code' => 200,
            'response'    => ['access_token' => 'ghp_valid_token'],
        ];

        $oauth->handleOauth('valid-code');
    }
}
