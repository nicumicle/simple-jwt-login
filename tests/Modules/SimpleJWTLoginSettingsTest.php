<?php

namespace SimpleJwtLoginTests\Modules;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\AuthCodesSettings;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\Settings\CorsSettings;
use SimpleJWTLogin\Modules\Settings\DeleteUserSettings;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\Settings\HooksSettings;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\Settings\RegisterSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;

class SimpleJWTLoginSettingsTest extends TestCase
{
    /**
     * @var SimpleJWTLoginSettings
     */
    private $simpleJwtLoginSettings;

    public function setUp(): void
    {
        parent::setUp();
        $wordPressDataMock            = $this->getMockBuilder(WordPressDataInterface::class)->getMock();
        $this->simpleJwtLoginSettings = new SimpleJWTLoginSettings($wordPressDataMock);
    }

    public function testGetGeneralSettings()
    {
        $this->assertInstanceOf(
            GeneralSettings::class,
            $this->simpleJwtLoginSettings->getGeneralSettings()
        );
    }

    public function testGetAuthCodesSettings()
    {
        $this->assertInstanceOf(
            AuthCodesSettings::class,
            $this->simpleJwtLoginSettings->getAuthCodesSettings()
        );
    }

    public function testGetAuthenticationSettings()
    {
        $this->assertInstanceOf(
            AuthenticationSettings::class,
            $this->simpleJwtLoginSettings->getAuthenticationSettings()
        );
    }

    public function testGetHooksSettings()
    {
        $this->assertInstanceOf(
            HooksSettings::class,
            $this->simpleJwtLoginSettings->getHooksSettings()
        );
    }

    public function testGetCorsSettings()
    {
        $this->assertInstanceOf(
            CorsSettings::class,
            $this->simpleJwtLoginSettings->getCorsSettings()
        );
    }

    public function testGetDeleteUserSettings()
    {
        $this->assertInstanceOf(
            DeleteUserSettings::class,
            $this->simpleJwtLoginSettings->getDeleteUserSettings()
        );
    }

    public function testGetLoginSettings()
    {
        $this->assertInstanceOf(
            LoginSettings::class,
            $this->simpleJwtLoginSettings->getLoginSettings()
        );
    }

    public function testGetRegisterSettings()
    {
        $this->assertInstanceOf(
            RegisterSettings::class,
            $this->simpleJwtLoginSettings->getRegisterSettings()
        );
    }

    public function testGetWordPressData()
    {
        $this->assertInstanceOf(
            WordPressDataInterface::class,
            $this->simpleJwtLoginSettings->getWordPressData()
        );
    }

    public function testWatchForUpdatesWithEmptyPost()
    {
        $this->assertFalse(
            $this->simpleJwtLoginSettings->watchForUpdates([])
        );
    }

    public function testWatchForUpdatesWithNonEmptyPost()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Route namespace could not be empty.');
        $this->simpleJwtLoginSettings
            ->watchForUpdates([
                '_wpnonce' => '123',
                'some_key' => '123'
            ]);
    }

    public function testGenerateExampleLink()
    {
        $wordPressDataMock = $this->getMockBuilder(WordPressDataInterface::class)
            ->getMock();
        $wordPressDataMock->method('getSiteUrl')
            ->willReturn('https://localhost');
        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode(['route_namespace' => 'v1']));
        $simpleJwtLoginSettings = new SimpleJWTLoginSettings($wordPressDataMock);
        $this->assertSame(
            'https://localhost/?rest_route=/v1/auth&amp;param=1',
            $simpleJwtLoginSettings->generateExampleLink('auth',['param' => 1])
        );
    }

    public function testGenerateExampleLinkWithNoParams()
    {
        $wordPressDataMock = $this->getMockBuilder(WordPressDataInterface::class)
            ->getMock();
        $wordPressDataMock->method('getSiteUrl')
            ->willReturn('https://localhost');
        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode(['route_namespace' => 'v1']));
        $simpleJwtLoginSettings = new SimpleJWTLoginSettings($wordPressDataMock);
        $this->assertSame(
            'https://localhost/?rest_route=/v1/test',
            $simpleJwtLoginSettings->generateExampleLink('test', [])
        );
    }

    public function testCallWithoutNonceWillReturnFalse()
    {
        $this->assertFalse(
            $this->simpleJwtLoginSettings
                ->watchForUpdates(['test' => '123'])
        );
    }

    public function testCallingWithInvalidNonce()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Something is wrong. We can not save the settings.');

        $wordPressDataMock = $this->getMockBuilder(WordPressDataInterface::class)
                                  ->getMock();
        $wordPressDataMock->method('checkNonce')
                          ->willReturn(false);
        $settings = new SimpleJWTLoginSettings($wordPressDataMock);
        $settings->watchForUpdates(
            [
                '_wpnonce' => '123',
                'test'     => '123'
            ]
        );
    }

    /**
     * @dataProvider settingsProvider
     * @param mixed $settings
     * @throws Exception
     */
    public function testWatchForUpdatesSuccess($settings)
    {
        $wordPressDataMock = $this->getMockBuilder(WordPressDataInterface::class)
            ->getMock();
        $wordPressDataMock->method('checkNonce')
            ->willReturn(true);
        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn($settings);
        $simpleJwtLoginSettings = new SimpleJWTLoginSettings($wordPressDataMock);
        $result = $simpleJwtLoginSettings->watchForUpdates(
            [
                '_wpnonce' => '123',
                'test'     => '123',
                'route_namespace' => 'test',
                'request_jwt_url' => true,
                'new_user_profile' => 'subscriber',
            ]
        );
        $this->assertTrue($result);
    }

    /**
     * @return array
     */
    public function settingsProvider()
    {
        return [
            'empty_settings' => [
                'settings' => false,
            ],
            'has_settings' => [
                'settings' => json_encode([
                    'test' => 1,
                ])
            ]
        ];
    }
}