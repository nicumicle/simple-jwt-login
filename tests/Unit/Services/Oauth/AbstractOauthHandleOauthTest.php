<?php

namespace SimpleJwtLoginTests\Unit\Services\Oauth;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\Settings\IntegrationsSettings;
use SimpleJWTLogin\Modules\Settings\ThirdParty\TwoFactorSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\Integrations\TwoFactor\TwoFactorBridge;
use stdClass;

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

        $tfaSettings = $this->createStub(TwoFactorSettings::class);
        $tfaSettings->method('isEnabled')->willReturn(false);

        $integrationsSettings = $this->createStub(IntegrationsSettings::class);
        $integrationsSettings->method('twoFactor')->willReturn($tfaSettings);

        $this->settings = $this->createStub(SimpleJWTLoginSettings::class);
        $this->settings->method('getGeneralSettings')->willReturn($generalSettings);
        $this->settings->method('getIntegrationsSettings')->willReturn($integrationsSettings);
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
                'errorFragment' => 'Token has been expired or revoked.',
            ],
            '200 with error body - bad code' => [
                'exchangeResult' => [
                    'status_code' => 200,
                    'response'    => [
                        'error'             => 'bad_verification_code',
                        'error_description' => 'The code passed is incorrect or expired.',
                    ],
                ],
                'errorFragment' => 'The code passed is incorrect or expired.',
            ],
            '200 with error body - redirect uri mismatch' => [
                'exchangeResult' => [
                    'status_code' => 200,
                    'response'    => [
                        'error'             => 'redirect_uri_mismatch',
                        'error_description' => 'The redirect_uri MUST match the registered callback URL.',
                    ],
                ],
                'errorFragment' => 'The redirect_uri MUST match the registered callback URL.',
            ],
            '200 with error body - incorrect credentials' => [
                'exchangeResult' => [
                    'status_code' => 200,
                    'response'    => [
                        'error'             => 'incorrect_client_credentials',
                        'error_description' => 'The client_id and/or client_secret passed are incorrect.',
                    ],
                ],
                'errorFragment' => 'The client_id and/or client_secret passed are incorrect.',
            ],
        ];
    }

    #[DataProvider('errorResponseProvider')]
    public function testHandleOauthRedirectsToLoginWithErrorOnFailedExchange(
        $exchangeResult,
        $errorFragment
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

        $this->wpData->method('doAction')->willReturn(null);

        $oauth = $this->makeOauth(['code' => 'test-code-abc']);
        $oauth->exchangeStub = $exchangeResult;

        $oauth->handleOauth('test-code-abc');

        $this->assertNotNull($capturedUrl, 'redirect() should have been called');
        $this->assertStringContainsString(
            urlencode($errorFragment),
            $capturedUrl
        );
    }

    public function testHandleOauthProceedsToEmailFetchOnSuccess()
    {
        $user = new stdClass();
        $user->ID = 1;
        $user->user_email = 'user@example.com';

        $this->wpData->method('getUserDetailsByEmail')->willReturn($user);
        $this->wpData->method('getUserProperty')
            ->willReturnCallback(function ($user, $prop) {
                return $user->$prop;
            });
        $this->wpData->method('doAction')->willReturn(null);
        $this->wpData->method('getAdminUrl')->willReturn('http://localhost/wp-admin/');
        $this->wpData->expects($this->once())->method('redirect')
            ->with('http://localhost/wp-admin/');

        $oauth = $this->makeOauth(['code' => 'valid-code']);
        $oauth->exchangeStub = [
            'status_code' => 200,
            'response'    => ['access_token' => 'ghp_valid_token'],
        ];

        $oauth->handleOauth('valid-code');
    }

    private function makeSettingsWith2FAEnabled()
    {
        $generalSettings = $this->createStub(GeneralSettings::class);
        $generalSettings->method('isSafeRedirectEnabled')->willReturn(false);
        $generalSettings->method('getJWTDecryptAlgorithm')->willReturn('HS256');
        $generalSettings->method('getDecryptionKey')->willReturn('test-secret');

        $tfaSettings = $this->createStub(TwoFactorSettings::class);
        $tfaSettings->method('isEnabled')->willReturn(true);
        $tfaSettings->method('getInterimTtl')->willReturn(5);

        $integrationsSettings = $this->createStub(IntegrationsSettings::class);
        $integrationsSettings->method('twoFactor')->willReturn($tfaSettings);

        $settings = $this->createStub(SimpleJWTLoginSettings::class);
        $settings->method('getGeneralSettings')->willReturn($generalSettings);
        $settings->method('getIntegrationsSettings')->willReturn($integrationsSettings);
        $settings->method('generateExampleLink')
            ->willReturn('http://localhost/wp-json/simple-jwt-login/v1/oauth/token?provider=testprovider');

        return $settings;
    }

    public function testHandleOauthRedirectsToTwoFactorPageWhenUserHas2FA()
    {
        $user = new stdClass();
        $user->ID = 42;
        $user->user_email = 'user@example.com';

        $this->wpData->method('getUserDetailsByEmail')->willReturn($user);
        $this->wpData->method('getUserProperty')
            ->willReturnCallback(function ($u, $prop) {
                return $u->$prop;
            });
        $this->wpData->method('sanitizeTextField')->willReturnArgument(0);
        $this->wpData->method('doAction')->willReturn(null);
        $this->wpData->method('getAdminUrl')->willReturn('http://localhost/wp-admin/');
        $this->wpData->method('getLoginURL')
            ->willReturnCallback(function ($params) {
                return 'http://localhost/wp-login.php?' . http_build_query($params);
            });

        $this->wpData->expects($this->never())->method('redirect');
        $this->wpData->expects($this->never())->method('loginUser');

        $bridge = $this->createStub(TwoFactorBridge::class);
        $bridge->method('isAvailable')->willReturn(true);
        $bridge->method('isUserUsing2FA')->willReturn(true);
        $bridge->method('createNonce')->willReturn(['key' => 'test-nonce-key', 'expiration' => time() + 300]);
        $bridge->method('getPrimaryProvider')->willReturn(null);

        $oauth = new ConcreteOauth(
            ['code' => 'valid-code'],
            'GET',
            $this->settings,
            $this->wpData
        );
        $oauth->withTwoFactorBridge($bridge);
        $oauth->exchangeStub = [
            'status_code' => 200,
            'response'    => ['access_token' => 'ghp_valid_token'],
        ];

        $oauth->handleOauth('valid-code');

        $capturedUrl = $oauth->htmlRedirectUrl;
        $this->assertNotNull($capturedUrl, 'doHtmlRedirect() should have been called');
        $this->assertStringContainsString('action=validate_2fa', $capturedUrl);
        $this->assertStringContainsString('wp-auth-id=42', $capturedUrl);
        $this->assertStringContainsString('wp-auth-nonce=test-nonce-key', $capturedUrl);
    }

    public function testHandleOauthLogsInDirectlyWhenUserHasNo2FA()
    {
        $user = new stdClass();
        $user->ID = 7;
        $user->user_email = 'user@example.com';

        $this->wpData->method('getUserDetailsByEmail')->willReturn($user);
        $this->wpData->method('getUserProperty')
            ->willReturnCallback(function ($u, $prop) {
                return $u->$prop;
            });
        $this->wpData->method('doAction')->willReturn(null);
        $this->wpData->method('getAdminUrl')->willReturn('http://localhost/wp-admin/');
        $this->wpData->expects($this->once())->method('loginUser');
        $this->wpData->expects($this->once())->method('redirect')->with('http://localhost/wp-admin/');

        $bridge = $this->createStub(TwoFactorBridge::class);
        $bridge->method('isAvailable')->willReturn(true);
        $bridge->method('isUserUsing2FA')->willReturn(false);

        $oauth = new ConcreteOauth(
            ['code' => 'valid-code'],
            'GET',
            $this->makeSettingsWith2FAEnabled(),
            $this->wpData
        );
        $oauth->withTwoFactorBridge($bridge);
        $oauth->exchangeStub = [
            'status_code' => 200,
            'response'    => ['access_token' => 'ghp_valid_token'],
        ];

        $oauth->handleOauth('valid-code');
    }

    public function testCreateWpJwtForEmailReturnsTwoFactorChallengeWhenUserHas2FA()
    {
        $user = new stdClass();
        $user->ID = 55;
        $user->user_email = 'user@example.com';

        $this->wpData->method('getUserDetailsByEmail')->willReturn($user);
        $this->wpData->method('getUserProperty')
            ->willReturnCallback(function ($u, $prop) {
                return $u->$prop;
            });
        $this->wpData->method('sanitizeTextField')->willReturnArgument(0);
        $this->wpData->expects($this->never())->method('loginUser');

        $bridge = $this->createStub(TwoFactorBridge::class);
        $bridge->method('isAvailable')->willReturn(true);
        $bridge->method('isUserUsing2FA')->willReturn(true);
        $bridge->method('createNonce')->willReturn(['key' => 'nonce-abc', 'expiration' => time() + 300]);
        $bridge->method('getPrimaryProvider')->willReturn(null);

        $oauth = new ConcreteOauth(
            ['access_token' => 'some-token'],
            'POST',
            $this->makeSettingsWith2FAEnabled(),
            $this->wpData
        );
        $oauth->withTwoFactorBridge($bridge);

        $result = $oauth->call();

        $this->assertTrue($result['success']);
        $this->assertTrue($result['data']['two_factor_required']);
        $this->assertArrayHasKey('jwt', $result['data']);
    }
}
