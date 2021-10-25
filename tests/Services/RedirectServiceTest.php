<?php

namespace Services;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;
use SimpleJWTLogin\Services\RedirectService;

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

        $response = (new RedirectService())
            ->withRequest([])
            ->withCookies([])
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

        $response = (new RedirectService())
            ->withRequest([])
            ->withCookies([])
            ->withSettings(
                new SimpleJWTLoginSettings(
                    $this->wordPressDataMock
                )
            )
            ->withSession([])
            ->makeAction();
        $this->assertSame(null, $response);
    }
}
