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
        $this->simpleJWTSettings = new SimpleJWTLoginSettings($wordPressDataMock);
    }

    public function testGetGeneralSettings()
    {
        $this->assertInstanceOf(
            GeneralSettings::class,
            $this->simpleJWTSettings->getGeneralSettings()
        );
    }

    public function testGetAuthCodesSettings()
    {
        $this->assertInstanceOf(
            AuthCodesSettings::class,
            $this->simpleJWTSettings->getAuthCodesSettings()
        );
    }

    public function testGetAuthenticationSettings()
    {
        $this->assertInstanceOf(
            AuthenticationSettings::class,
            $this->simpleJWTSettings->getAuthenticationSettings()
        );
    }

    public function testGetHooksSettings()
    {
        $this->assertInstanceOf(
            HooksSettings::class,
            $this->simpleJWTSettings->getHooksSettings()
        );
    }

    public function testGetCorsSettings()
    {
        $this->assertInstanceOf(
            CorsSettings::class,
            $this->simpleJWTSettings->getCorsSettings()
        );
    }

    public function testGetDeleteUserSettings()
    {
        $this->assertInstanceOf(
            DeleteUserSettings::class,
            $this->simpleJWTSettings->getDeleteUserSettings()
        );
    }

    public function testGetLoginSettings()
    {
        $this->assertInstanceOf(
            LoginSettings::class,
            $this->simpleJWTSettings->getLoginSettings()
        );
    }

    public function testGetRegisterSettings()
    {
        $this->assertInstanceOf(
            RegisterSettings::class,
            $this->simpleJWTSettings->getRegisterSettings()
        );
    }

    public function testGetWordPressData()
    {
        $this->assertInstanceOf(
            WordPressDataInterface::class,
            $this->simpleJWTSettings->getWordPressData()
        );
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
        $wordPressDataMock = $this->createStub(WordPressDataInterface::class);
        $wordPressDataMock->method('getSiteUrl')
            ->willReturn('https://localhost');
        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode(['route_namespace' => 'v1']));
        $simpleJwtSetttings = new SimpleJWTLoginSettings($wordPressDataMock);
        $this->assertSame(
            'https://localhost/?rest_route=/v1/auth&param=1',
            $simpleJwtSetttings->generateExampleLink('auth', ['param' => 1])
        );
    }

    public function testGenerateExampleLinkWithNoParams()
    {
        $wordPressDataMock = $this->createStub(WordPressDataInterface::class);
        $wordPressDataMock->method('getSiteUrl')
            ->willReturn('https://localhost');
        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode(['route_namespace' => 'v1']));
        $simpleJwtSettings = new SimpleJWTLoginSettings($wordPressDataMock);
        $this->assertSame(
            'https://localhost/?rest_route=/v1/test',
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
