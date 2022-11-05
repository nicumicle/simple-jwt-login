<?php

namespace Services;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;
use SimpleJWTLogin\Services\RedirectService;
use WP_User;

class RedirectServiceTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface
     */
    private $wordPressDataMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressDataMock = $this
            ->getMockBuilder(WordPressDataInterface::class)
            ->getMock();
        $this->wordPressDataMock->method('sanitizeTextField')
            ->willReturnCallback(
                function ($parameter) {
                    return $parameter;
                }
            );

        $this->user = $this->getMockBuilder(WP_User::class)
            ->getMock();
    }

    public function testNoRedirect()
    {
        $settings = [
            'redirect' => LoginSettings::NO_REDIRECT,
            'enabled_hooks' => [
                SimpleJWTLoginHooks::NO_REDIRECT_RESPONSE
            ]
        ];
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressDataMock
            ->method('triggerFilter')
            ->willReturn(true);
        $this->wordPressDataMock
            ->method('createResponse')
            ->willReturn(true);
        $this->wordPressDataMock->method('getAdminUrl')
            ->willReturn('https://admin.com');

        $response = (new RedirectService())
            ->withRequest([])
            ->withCookies([])
            ->withUser($this->user)
            ->withSettings(
                new SimpleJWTLoginSettings(
                    $this->wordPressDataMock
                )
            )
            ->withSession([])
            ->makeAction();
        $this->assertTrue($response);
    }

    public function testRedirectCustomUrl()
    {
        $settings = [
            'redirect' => LoginSettings::REDIRECT_CUSTOM,
            'enabled_hooks' => [
                SimpleJWTLoginHooks::LOGIN_REDIRECT_NAME
            ],
            'include_login_request_parameters' => 1,
            'allow_usage_redirect_parameter' => 1,
            'redirect_url' => 'https://www.google.com',
        ];
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressDataMock
            ->method('triggerFilter')
            ->willReturn(true);
        $this->wordPressDataMock
            ->method('createResponse')
            ->willReturn(true);

        $response = (new RedirectService())
            ->withRequest(
                [
                    'redirectUrl' => 'http://www.test.com',
                    'email' => 'test@test.com',
                ]
            )
            ->withCookies([])
            ->withSettings(
                new SimpleJWTLoginSettings(
                    $this->wordPressDataMock
                )
            )
            ->withUser($this->user)
            ->withSession([])
            ->makeAction();
        $this->assertSame(null, $response);
    }

    public function testRedirectHomepage()
    {
        $settings = [
            'redirect' => LoginSettings::REDIRECT_HOMEPAGE,
        ];
        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressDataMock
            ->method('triggerFilter')
            ->willReturn(true);
        $this->wordPressDataMock
            ->method('createResponse')
            ->willReturn(true);
        $this->wordPressDataMock->method('getSiteUrl')
            ->willReturn('https://google.com');

        $response = (new RedirectService())
            ->withRequest([])
            ->withCookies([])
            ->withSettings(
                new SimpleJWTLoginSettings(
                    $this->wordPressDataMock
                )
            )
            ->withSession([])
            ->withUser($this->user)
            ->makeAction();
        $this->assertSame(null, $response);
    }
}
