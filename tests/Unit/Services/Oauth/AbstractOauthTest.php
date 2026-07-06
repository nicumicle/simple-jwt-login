<?php

namespace SimpleJwtLoginTests\Unit\Services\Oauth;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\AuditLogSettings;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\Settings\IntegrationsSettings;
use SimpleJWTLogin\Modules\Settings\RegisterSettings;
use SimpleJWTLogin\Modules\Settings\ThirdParty\TwoFactorSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class AbstractOauthTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\Stub|WordPressDataInterface */
    private $wpData;

    /** @var \PHPUnit\Framework\MockObject\Stub|SimpleJWTLoginSettings */
    private $settings;

    public function setUp(): void
    {
        parent::setUp();

        $this->wpData = $this->createStub(WordPressDataInterface::class);

        $generalSettings = $this->createStub(GeneralSettings::class);
        $generalSettings->method('isSafeRedirectEnabled')->willReturn(false);

        $tfaSettings = $this->createStub(TwoFactorSettings::class);
        $tfaSettings->method('isEnabled')->willReturn(false);

        $integrationsSettings = $this->createStub(IntegrationsSettings::class);
        $integrationsSettings->method('twoFactor')->willReturn($tfaSettings);

        $auditLogSettings = $this->createStub(AuditLogSettings::class);
        $auditLogSettings->method('isAuditEventEnabled')->willReturn(false);

        $registerSettings = $this->createStub(RegisterSettings::class);
        $registerSettings->method('getRandomPasswordLength')->willReturn(10);
        $registerSettings->method('getNewUserRoles')->willReturn(['subscriber']);

        $this->settings = $this->createStub(SimpleJWTLoginSettings::class);
        $this->settings->method('getGeneralSettings')->willReturn($generalSettings);
        $this->settings->method('getIntegrationsSettings')->willReturn($integrationsSettings);
        $this->settings->method('getAuditLogSettings')->willReturn($auditLogSettings);
        $this->settings->method('getRegisterSettings')->willReturn($registerSettings);
    }

    private function makeOauth($request = [], $method = 'GET')
    {
        return new ConcreteOauth($request, $method, $this->settings, $this->wpData);
    }

    public function testValidatePassesWhenCodeIsPresent()
    {
        $this->expectNotToPerformAssertions();
        $this->makeOauth(['code' => 'abc'])->validate();
    }

    public function testValidatePassesWhenTokenParamIsPresent()
    {
        $this->expectNotToPerformAssertions();
        $this->makeOauth(['access_token' => 'tok'])->validate();
    }

    public function testValidateThrowsWhenBothCodeAndTokenAreMissing()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(9003);
        $this->makeOauth([])->validate();
    }

    public function testCallWithPostCodeReturnsSuccessOn200()
    {
        $oauth = $this->makeOauth(['code' => 'good-code'], 'POST');
        $oauth->exchangeStub = [
            'status_code' => 200,
            'response'    => ['access_token' => 'token-value'],
        ];

        $result = $oauth->call();

        $this->assertSame(
            ['success' => true, 'data' => ['access_token' => 'token-value']],
            $result
        );
        $this->assertSame('good-code', $oauth->lastExchangeCall['code']);
        $this->assertSame('https://example.com/oauth/callback', $oauth->lastExchangeCall['redirectUri']);
    }

    public function testCallWithPostCodeThrowsOnNon200()
    {
        $oauth = $this->makeOauth(['code' => 'bad-code'], 'POST');
        $oauth->exchangeStub = [
            'status_code' => 400,
            'response'    => ['error' => 'invalid_grant'],
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionCode(9004);
        $oauth->call();
    }

    public function testCallReturnsEmptyArrayWhenNoMatchingBranch()
    {
        $oauth = $this->makeOauth(['code' => ''], 'POST');

        $this->assertSame([], $oauth->call());
    }

    public function testCallWithTokenThrowsWhenUserNotFoundAndCreateDisabled()
    {
        $this->wpData->method('getUserDetailsByEmail')->willReturn(null);
        $this->wpData->method('sanitizeTextField')->willReturnArgument(0);

        $oauth = $this->makeOauth(['access_token' => 'tok'], 'POST');
        $oauth->createUserEnabled = false;

        $this->expectException(Exception::class);
        $this->expectExceptionCode(9002);
        $oauth->call();
    }

    public function testDoRedirectUsesSafeRedirectWhenEnabled()
    {
        $generalSettings = $this->createStub(GeneralSettings::class);
        $generalSettings->method('isSafeRedirectEnabled')->willReturn(true);

        $auditLogSettings = $this->createStub(AuditLogSettings::class);
        $auditLogSettings->method('isAuditEventEnabled')->willReturn(false);

        $settings = $this->createStub(SimpleJWTLoginSettings::class);
        $settings->method('getGeneralSettings')->willReturn($generalSettings);
        $settings->method('getAuditLogSettings')->willReturn($auditLogSettings);
        $settings->method('generateExampleLink')->willReturn('http://localhost/token');

        $wpData = $this->createMock(WordPressDataInterface::class);
        $wpData->method('getLoginURL')->willReturn('http://localhost/wp-login.php?error=x');
        $wpData->expects($this->once())->method('redirectSafe');
        $wpData->expects($this->never())->method('redirect');

        $oauth = new ConcreteOauth(['code' => 'c'], 'GET', $settings, $wpData);
        // Non-200 exchange forces the error redirect path through doRedirect().
        $oauth->exchangeStub = ['status_code' => 500, 'response' => ['error' => 'server_error']];

        $oauth->handleOauth('c');

        $this->addToAssertionCount(1);
    }

    public function testExchangeCodeUsesWordPressHttpStubs()
    {
        // ConcreteOauth overrides exchangeCode; this anonymous subclass exercises
        // the real AbstractOauth::exchangeCode against the bootstrap WP HTTP stubs.
        $oauth = new ConcreteOauthRealExchange([], 'POST', $this->settings, $this->wpData);

        $result = $oauth->exchangeCode('the-code', 'https://example.com/cb');

        $this->assertArrayHasKey('status_code', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertSame(0, $result['status_code']);
    }
}
