<?php

namespace SimpleJwtLoginTests\Unit\Plugin;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\IntegrationsSettings;
use SimpleJWTLogin\Modules\Settings\Oauth\GoogleOauthSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Plugin\Shortcodes;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class ShortcodesTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (!defined('SIMPLE_JWT_LOGIN_PLUGIN_FILE')) {
            define(
                'SIMPLE_JWT_LOGIN_PLUGIN_FILE',
                __DIR__ . '/../../../simple-jwt-login/simple-jwt-login.php'
            );
        }
    }

    #[DataProvider('requestShortcodeProvider')]
    /**
     * @param array $request
     * @param mixed $parameter
     * @param string $expected
     */
    public function testHandleRequestShortcode($request, $parameter, $expected)
    {
        $settings = $this->createStub(SimpleJWTLoginSettings::class);
        $shortcodes = new Shortcodes($request, $settings);

        $this->assertSame($expected, $shortcodes->handleRequestShortcode($parameter));
    }

    /**
     * @return array[]
     */
    public static function requestShortcodeProvider()
    {
        return [
            'null parameter returns empty' => [
                'request' => ['email' => 'test@example.com'],
                'parameter' => null,
                'expected' => '',
            ],
            'parameter without key returns empty' => [
                'request' => ['email' => 'test@example.com'],
                'parameter' => ['other' => 'value'],
                'expected' => '',
            ],
            'key not present in request returns empty' => [
                'request' => ['email' => 'test@example.com'],
                'parameter' => ['key' => 'missing'],
                'expected' => '',
            ],
            'key present in request returns escaped value' => [
                'request' => ['email' => 'test@example.com'],
                'parameter' => ['key' => 'email'],
                'expected' => 'test@example.com',
            ],
        ];
    }

    public function testHandleOauthShortcodeReturnsEmptyWhenProviderMissing()
    {
        $settings = $this->createStub(SimpleJWTLoginSettings::class);
        $shortcodes = new Shortcodes([], $settings);

        $this->assertSame('', $shortcodes->handleOauthShortcode(['background' => '#fff']));
    }

    public function testHandleOauthShortcodeReturnsEmptyWhenUserLoggedIn()
    {
        $wpData = $this->createStub(WordPressDataInterface::class);
        $wpData->method('isUserLoggedIn')->willReturn(true);

        $settings = $this->createStub(SimpleJWTLoginSettings::class);
        $settings->method('getWordPressData')->willReturn($wpData);

        $shortcodes = new Shortcodes([], $settings);

        $this->assertSame('', $shortcodes->handleOauthShortcode(['provider' => 'google']));
    }

    public function testHandleOauthShortcodeReturnsEmptyForUnknownProvider()
    {
        $shortcodes = new Shortcodes([], $this->makeSettings(false));

        $this->assertSame('', $shortcodes->handleOauthShortcode(['provider' => 'does-not-exist']));
    }

    public function testHandleOauthShortcodeReturnsEmptyWhenProviderDisabled()
    {
        $shortcodes = new Shortcodes([], $this->makeSettings(false));

        $this->assertSame('', $shortcodes->handleOauthShortcode(['provider' => 'google']));
    }

    public function testHandleOauthShortcodeRendersFormWhenProviderEnabled()
    {
        $shortcodes = new Shortcodes([], $this->makeSettings(true));

        $result = $shortcodes->handleOauthShortcode([
            'provider'   => 'google',
            'background' => '#000000',
            'color'      => 'white',
            'width'      => '40px',
            'height'     => '40px',
            'border'     => '2px solid #000',
        ]);

        $this->assertStringContainsString('simple-jwt-login-oauth-code', $result);
        $this->assertStringContainsString('<form', $result);
        $this->assertStringContainsString('background-color: #000000', $result);
        $this->assertStringContainsString('width: 40px', $result);
    }

    public function testHandleOauthShortcodeUsesDefaultsForInvalidStyleParameters()
    {
        $shortcodes = new Shortcodes([], $this->makeSettings(true));

        $result = $shortcodes->handleOauthShortcode([
            'provider'   => 'google',
            'background' => 'not-a-color',
            'color'      => 'javascript:alert(1)',
            'width'      => '40',
            'height'     => 'abc',
            'border'     => 'bad-border',
        ]);

        $this->assertStringContainsString('background-color: #fff', $result);
        $this->assertStringContainsString('color: #000', $result);
        $this->assertStringContainsString('width: 30px', $result);
        $this->assertStringContainsString('border: 1px solid #ccc', $result);
    }

    /**
     * Build a SimpleJWTLoginSettings stub wired for the OAuth shortcode flow.
     *
     * @param bool $providerEnabled
     * @return \PHPUnit\Framework\MockObject\Stub|SimpleJWTLoginSettings
     */
    private function makeSettings($providerEnabled)
    {
        $wpData = $this->createStub(WordPressDataInterface::class);
        $wpData->method('isUserLoggedIn')->willReturn(false);

        $provider = $this->createStub(GoogleOauthSettings::class);
        $provider->method('isEnabled')->willReturn($providerEnabled);
        $provider->method('isOauthEnabled')->willReturn($providerEnabled);
        $provider->method('getClientId')->willReturn('test-client-id');

        $integrations = $this->createStub(IntegrationsSettings::class);
        $integrations->method('getProvider')->willReturn($provider);
        $integrations->method('google')->willReturn($provider);

        $settings = $this->createStub(SimpleJWTLoginSettings::class);
        $settings->method('getWordPressData')->willReturn($wpData);
        $settings->method('getIntegrationsSettings')->willReturn($integrations);
        $settings->method('generateExampleLink')->willReturn('http://localhost/oauth/token');

        return $settings;
    }
}
