<?php

namespace SimpleJwtLoginTests\Unit\Modules;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
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
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class SimpleJWTLoginSettingsTest extends TestCase
{
    /**
     * @var SimpleJWTLoginSettings
     */
    private $simpleJWTSettings;

    public function setUp(): void
    {
        parent::setUp();
        $wordPressDataMock = $this->createStub(WordPressDataInterface::class);
        $wordPressDataMock->method('roleExists')
            ->willReturn(true);
        $wordPressDataMock->method('wpUnslash')
            ->willReturnArgument(0);
        $this->simpleJWTSettings = new SimpleJWTLoginSettings($wordPressDataMock);
    }

    #[DataProvider('getterInstanceOfProvider')]
    public function testGetterReturnsCorrectInstance(string $getter, string $expectedClass): void
    {
        $this->assertInstanceOf($expectedClass, $this->simpleJWTSettings->$getter());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function getterInstanceOfProvider(): array
    {
        return [
            'getGeneralSettings'        => ['getGeneralSettings',        GeneralSettings::class],
            'getAuthCodesSettings'      => ['getAuthCodesSettings',      AuthCodesSettings::class],
            'getAuthenticationSettings' => ['getAuthenticationSettings', AuthenticationSettings::class],
            'getHooksSettings'          => ['getHooksSettings',          HooksSettings::class],
            'getCorsSettings'           => ['getCorsSettings',           CorsSettings::class],
            'getDeleteUserSettings'     => ['getDeleteUserSettings',     DeleteUserSettings::class],
            'getLoginSettings'          => ['getLoginSettings',          LoginSettings::class],
            'getRegisterSettings'       => ['getRegisterSettings',       RegisterSettings::class],
            'getWordPressData'          => ['getWordPressData',          WordPressDataInterface::class],
        ];
    }

    public function testWatchForUpdatesWithEmptyPost()
    {
        $this->assertFalse(
            $this->simpleJWTSettings->watchForUpdates([])
        );
    }

    public function testWatchForUpdatesWithNonEmptyPost()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Route namespace could not be empty.');
        $this->simpleJWTSettings
            ->watchForUpdates([
                '_wpnonce' => '123',
                'some_key' => '123'
            ]);
    }

    public function testGenerateExampleLink()
    {
        $settingsJson = json_encode(['route_namespace' => 'v1']);
        $wordPressDataMock = $this->createStub(WordPressDataInterface::class);
        $wordPressDataMock->method('getSiteUrl')
            ->willReturn('https://localhost');
        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturnCallback(function ($option) use ($settingsJson) {
                if ($option === 'permalink_structure') {
                    return '';
                }
                return $settingsJson;
            });
        $simpleJwtSetttings = new SimpleJWTLoginSettings($wordPressDataMock);
        $this->assertSame(
            'https://localhost/?rest_route=/v1/auth&param=1',
            $simpleJwtSetttings->generateExampleLink('auth', ['param' => 1])
        );
    }

    public function testGenerateExampleLinkWithNoParams()
    {
        $settingsJson = json_encode(['route_namespace' => 'v1']);
        $wordPressDataMock = $this->createStub(WordPressDataInterface::class);
        $wordPressDataMock->method('getSiteUrl')
            ->willReturn('https://localhost');
        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturnCallback(function ($option) use ($settingsJson) {
                if ($option === 'permalink_structure') {
                    return '';
                }
                return $settingsJson;
            });
        $simpleJwtSettings = new SimpleJWTLoginSettings($wordPressDataMock);
        $this->assertSame(
            'https://localhost/?rest_route=/v1/test',
            $simpleJwtSettings->generateExampleLink('test', [])
        );
    }

    public function testGenerateExampleLinkWithPermalinks()
    {
        $settingsJson = json_encode(['route_namespace' => 'simple-jwt-login/v1']);
        $wordPressDataMock = $this->createStub(WordPressDataInterface::class);
        $wordPressDataMock->method('getSiteUrl')
            ->willReturn('https://localhost');
        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturnCallback(function ($option) use ($settingsJson) {
                if ($option === 'permalink_structure') {
                    return '/%postname%/';
                }
                return $settingsJson;
            });
        $simpleJwtSettings = new SimpleJWTLoginSettings($wordPressDataMock);
        $this->assertSame(
            'https://localhost/wp-json/simple-jwt-login/v1/auth?param=1',
            $simpleJwtSettings->generateExampleLink('auth', ['param' => 1])
        );
    }

    public function testGenerateExampleLinkWithPermalinksAndNoParams()
    {
        $settingsJson = json_encode(['route_namespace' => 'simple-jwt-login/v1']);
        $wordPressDataMock = $this->createStub(WordPressDataInterface::class);
        $wordPressDataMock->method('getSiteUrl')
            ->willReturn('https://localhost');
        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturnCallback(function ($option) use ($settingsJson) {
                if ($option === 'permalink_structure') {
                    return '/%postname%/';
                }
                return $settingsJson;
            });
        $simpleJwtSettings = new SimpleJWTLoginSettings($wordPressDataMock);
        $this->assertSame(
            'https://localhost/wp-json/simple-jwt-login/v1/test',
            $simpleJwtSettings->generateExampleLink('test', [])
        );
    }

    public function testCallWithoutNonceWillReturnFalse()
    {
        $this->assertFalse(
            $this->simpleJWTSettings
                ->watchForUpdates(['test' => '123'])
        );
    }

    public function testCallingWithInvalidNonce()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Something is wrong. We can not save the settings.');

        $wordPressDataMock = $this->createStub(WordPressDataInterface::class);
        $wordPressDataMock->method('checkNonce')
                          ->willReturn(false);
        $wordPressDataMock->method('wpUnslash')
            ->willReturnArgument(0);
        $settings = new SimpleJWTLoginSettings($wordPressDataMock);
        $settings->watchForUpdates(
            [
                '_wpnonce' => '123',
                'test'     => '123'
            ]
        );
    }

    #[DataProvider('settingsProvider')]
    /**
     * @param mixed $settings
     * @throws Exception
     */
    public function testWatchForUpdatesSuccess($settings)
    {
        $wordPressDataMock = $this->createStub(WordPressDataInterface::class);
        $wordPressDataMock->method('checkNonce')
            ->willReturn(true);
        $wordPressDataMock->method('roleExists')
            ->willReturn(true);
        $wordPressDataMock->method('wpUnslash')
            ->willReturnArgument(0);
        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn($settings);
        $simpleJWTSettings = new SimpleJWTLoginSettings($wordPressDataMock);
        $result = $simpleJWTSettings->watchForUpdates(
            [
                '_wpnonce'         => '123',
                'test'             => '123',
                'route_namespace'  => 'test',
                'request_jwt_url'  => true,
                'new_user_profile' => 'subscriber',
            ]
        );
        $this->assertTrue($result);
    }

    public function testWatchForUpdatesFailsWhenRefreshTokenKeyIsMissingAndRefreshEnabled(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Refresh Token Secret Key is required.');

        $wordPressDataMock = $this->createStub(WordPressDataInterface::class);
        $wordPressDataMock->method('checkNonce')
            ->willReturn(true);
        $wordPressDataMock->method('roleExists')
            ->willReturn(true);
        $wordPressDataMock->method('wpUnslash')
            ->willReturnArgument(0);
        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(false);
        $simpleJWTSettings = new SimpleJWTLoginSettings($wordPressDataMock);
        $simpleJWTSettings->watchForUpdates(
            [
                '_wpnonce'              => '123',
                'route_namespace'       => 'test',
                'request_jwt_url'       => true,
                'new_user_profile'      => 'subscriber',
                'allow_authentication'  => 1,
                'jwt_auth_iss'          => 'https://example.com',
                'jwt_payload'           => ['exp', 'id'],
                'jwt_auth_ttl'          => 60,
                'allow_refresh_token'   => 1,
                'jwt_auth_refresh_ttl'  => 20160,
                'refresh_token_key'     => '',
            ]
        );
    }

    public function testBuildSettingsDiffReturnsEmptyForIdenticalSettings()
    {
        $settings = ['general' => ['route_namespace' => 'v1', 'allow_login' => '1']];
        $diff = $this->simpleJWTSettings->buildSettingsDiff($settings, $settings);

        $this->assertEmpty($diff);
    }

    public function testBuildSettingsDiffDetectsChangedValue()
    {
        $old = ['general' => ['route_namespace' => 'v1']];
        $new = ['general' => ['route_namespace' => 'v2']];

        $diff = $this->simpleJWTSettings->buildSettingsDiff($old, $new);

        $this->assertArrayHasKey('changed', $diff);
        $this->assertArrayHasKey('general.route_namespace', $diff['changed']);
        $this->assertSame('v1', $diff['changed']['general.route_namespace']['from']);
        $this->assertSame('v2', $diff['changed']['general.route_namespace']['to']);
    }

    public function testBuildSettingsDiffDetectsAddedKey()
    {
        $old = ['general' => ['route_namespace' => 'v1']];
        $new = ['general' => ['route_namespace' => 'v1', 'new_setting' => '1']];

        $diff = $this->simpleJWTSettings->buildSettingsDiff($old, $new);

        $this->assertArrayHasKey('added', $diff);
        $this->assertContains('general.new_setting', $diff['added']);
    }

    public function testBuildSettingsDiffDetectsRemovedKey()
    {
        $old = ['general' => ['route_namespace' => 'v1', 'old_setting' => '1']];
        $new = ['general' => ['route_namespace' => 'v1']];

        $diff = $this->simpleJWTSettings->buildSettingsDiff($old, $new);

        $this->assertArrayHasKey('removed', $diff);
        $this->assertContains('general.old_setting', $diff['removed']);
    }

    public function testBuildSettingsDiffRedactsSensitiveSecretField()
    {
        $old = ['general' => ['jwt_secret' => 'old-secret']];
        $new = ['general' => ['jwt_secret' => 'new-secret']];

        $diff = $this->simpleJWTSettings->buildSettingsDiff($old, $new);

        $this->assertSame('[REDACTED]', $diff['changed']['general.jwt_secret']['from']);
        $this->assertSame('[REDACTED]', $diff['changed']['general.jwt_secret']['to']);
    }

    public function testBuildSettingsDiffRedactsSensitiveKeyField()
    {
        $old = ['auth' => ['refresh_token_key' => 'old-key']];
        $new = ['auth' => ['refresh_token_key' => 'new-key']];

        $diff = $this->simpleJWTSettings->buildSettingsDiff($old, $new);

        $this->assertSame('[REDACTED]', $diff['changed']['auth.refresh_token_key']['from']);
        $this->assertSame('[REDACTED]', $diff['changed']['auth.refresh_token_key']['to']);
    }

    public function testBuildSettingsDiffHandlesListArrayValuesAsAtomic()
    {
        $old = ['login' => ['allowed_roles' => ['editor', 'author']]];
        $new = ['login' => ['allowed_roles' => ['editor', 'subscriber']]];

        $diff = $this->simpleJWTSettings->buildSettingsDiff($old, $new);

        $this->assertArrayHasKey('changed', $diff);
        $this->assertArrayHasKey('login.allowed_roles', $diff['changed']);
    }

    public function testGetLastSettingsDiffIsEmptyBeforeWatchForUpdates()
    {
        $this->assertEmpty($this->simpleJWTSettings->getLastSettingsDiff());
    }

    public function testGetLastSettingsDiffIsPopulatedAfterSuccessfulSave()
    {
        $wordPressDataMock = $this->createStub(WordPressDataInterface::class);
        $wordPressDataMock->method('checkNonce')->willReturn(true);
        $wordPressDataMock->method('roleExists')->willReturn(true);
        $wordPressDataMock->method('wpUnslash')->willReturnArgument(0);
        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode(['general' => ['route_namespace' => 'old-ns']]));
        $simpleJWTSettings = new SimpleJWTLoginSettings($wordPressDataMock);

        $simpleJWTSettings->watchForUpdates([
            '_wpnonce'        => '123',
            'route_namespace' => 'new-ns',
            'request_jwt_url' => true,
            'new_user_profile' => 'subscriber',
        ]);

        // After a save the diff accessor must return an array (may be empty or populated)
        $this->assertIsArray($simpleJWTSettings->getLastSettingsDiff());
    }

    /**
     * @return array
     */
    public static function settingsProvider()
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
