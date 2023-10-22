<?php

namespace SimpleJwtLoginTests\Unit\Services;

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

    /**
     * @var WP_User|null
     */
    private $user = null;

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

    /**
     * @dataProvider redirectCustomURLProvider
     * @param array $extraSettings
     * @param string $expectedUrl
     * @return void
     * @throws \Exception
     */
    public function testRedirectCustomUrl($extraSettings, $request, $expectedUrl)
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Correct URL');
        $settings = [
            'redirect' => LoginSettings::REDIRECT_CUSTOM,
            'enabled_hooks' => [
                SimpleJWTLoginHooks::LOGIN_REDIRECT_NAME
            ],
            'include_login_request_parameters' => 1,
            'allow_usage_redirect_parameter' => 1,
        ];
        $settings = array_merge($settings, $extraSettings);

        $this->wordPressDataMock
            ->method('getOptionFromDatabase')
            ->willReturn(json_encode($settings));
        $this->wordPressDataMock
            ->method('triggerFilter')
            ->willReturn(true);
        $this->wordPressDataMock
            ->method('createResponse')
            ->willReturn(true);

        $this->wordPressDataMock->method('redirect')
            ->with($expectedUrl)
            ->willThrowException(new \Exception('Correct URL'));

        $response = (new RedirectService())
            ->withRequest($request)
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

    public static function redirectCustomURLProvider()
    {
        return [
            'simple-redirect' => [
                'settings' => [
                    'redirect_url' => 'https://www.google.com',
                ],
                'request' =>  [
                    'redirectUrl' => 'http://www.test.com',
                    'email' => 'test@test.com',
                ],
                'expected_url' => 'http://www.test.com',
            ],
            'redirect_parameters_are_added_to_redirect_url' => [
                'settings' => [
                    'redirect_url' => 'https://www.google.com',
                    'login_remove_request_parameters' => 'jwt',
                ],
                'request' =>  [
                    'redirectUrl' => 'http://www.test.com',
                    'email' => 'test@test.com',
                ],
                'expected_url' => 'http://www.test.com?redirectUrl='
                    . urlencode('http://www.test.com?email=test@test.com'),
            ],
            'redirect_parameters_outside_redirect_url' => [
                'settings' => [
                    'redirect_url' => 'https://www.google.com',
                    'login_remove_request_parameters' => 'jwt, JWT,password',
                ],
                'request' =>  [
                    'redirectUrl' => 'http://www.test.com?test=1',
                    'email' => 'test@test.com',
                    'JWT' => '123',
                    'password' => 'my-super-secret-password',
                ],
                'expected_url' => 'http://www.test.com?test=1&redirectUrl='
                    . urlencode('http://www.test.com?test=1&email=test@test.com'),
            ]
        ];
    }
}
