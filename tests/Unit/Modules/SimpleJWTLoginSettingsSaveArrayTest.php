<?php

namespace SimpleJwtLoginTests\Unit\Modules;

use Exception;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

#[RunTestsInSeparateProcesses]
class SimpleJWTLoginSettingsSaveArrayTest extends TestCase
{
    /**
     * @return array
     */
    private function storedSettings()
    {
        return [
            '_schema_version' => 2,
            'general' => [
                'route_namespace' => 'simple-jwt-login/v1/',
                'request_jwt_url' => 1,
                'decryption_key'  => 'secret',
            ],
            'login'    => ['enabled' => false, 'login_by_parameter' => 'email'],
            'register' => ['new_user_profile' => 'subscriber'],
            'auth_codes' => ['codes' => [], 'key' => 'AUTH_KEY'],
            'cors'     => ['enabled' => 1, 'allow_origin_enabled' => true, 'allow_origin' => '*'],
            'integrations' => [
                'oauth' => [
                    'google' => ['enabled' => 0, 'client_id' => '', 'enable_oauth' => false],
                ],
            ],
        ];
    }

    /**
     * @param Stub|\PHPUnit\Framework\MockObject\MockObject $wordPressData
     * @param mixed                                         $storedOption
     * @return void
     */
    private function configureWordPressData($wordPressData, $storedOption)
    {
        $wordPressData->method('getOptionFromDatabase')->willReturn($storedOption);
        $wordPressData->method('roleExists')->willReturn(true);
        $wordPressData->method('sanitizeTextField')->willReturnArgument(0);
    }

    public function testGetStoredSettingsReturnsLoadedSettings()
    {
        $wordPressData = $this->createStub(WordPressDataInterface::class);
        $this->configureWordPressData($wordPressData, json_encode($this->storedSettings()));

        $settings = new SimpleJWTLoginSettings($wordPressData);

        $this->assertSame($this->storedSettings(), $settings->getStoredSettings());
    }

    public function testSaveSettingsArrayPersistsAndReturnsDiff()
    {
        $newSettings = $this->storedSettings();
        $newSettings['login']['enabled'] = true;

        $wordPressData = $this->createMock(WordPressDataInterface::class);
        $this->configureWordPressData($wordPressData, json_encode($this->storedSettings()));
        $wordPressData->expects($this->once())
            ->method('updateOption')
            ->with(SimpleJWTLoginSettings::OPTIONS_KEY, json_encode($newSettings))
            ->willReturn(true);
        $wordPressData->expects($this->never())->method('addOption');

        $settings = new SimpleJWTLoginSettings($wordPressData);
        $settings->getLoginSettings();

        $diff = $settings->saveSettingsArray($newSettings);

        $this->assertSame(['changed' => ['login.enabled' => ['from' => '', 'to' => '1']]], $diff);
        $this->assertSame($newSettings, $settings->getStoredSettings());
        $this->assertTrue($settings->getLoginSettings()->isAutologinEnabled());
    }

    public function testSaveSettingsArrayUsesAddOptionWhenOptionDoesNotExist()
    {
        $wordPressData = $this->createMock(WordPressDataInterface::class);
        $this->configureWordPressData($wordPressData, false);
        $wordPressData->expects($this->never())->method('updateOption');
        $wordPressData->expects($this->once())->method('addOption')->willReturn(true);

        $settings = new SimpleJWTLoginSettings($wordPressData);
        $settings->saveSettingsArray($this->storedSettings());
    }

    /**
     * @return array<string, array{0: string[], 1: mixed, 2: string}>
     */
    public static function invalidSettingsProvider()
    {
        return [
            'empty route namespace' => [
                ['general', 'route_namespace'],
                '',
                'Route namespace could not be empty.',
            ],
            'no jwt input source' => [
                ['general', 'request_jwt_url'],
                0,
                'You have to have at least one option enabled in \'JWT Input Sources\'',
            ],
            'empty default role' => [
                ['register', 'new_user_profile'],
                '',
                'Default User Roles can not be empty.',
            ],
            'google enabled without features' => [
                ['integrations', 'oauth', 'google', 'enabled'],
                1,
                'You need to enable at least one Google option in order to enable the Google App.',
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidSettingsProvider')]
    public function testSaveSettingsArrayKeepsOldSettingsWhenValidationFails($path, $value, $expectedMessage)
    {
        $newSettings = $this->storedSettings();
        $node = &$newSettings;
        foreach ($path as $segment) {
            $node = &$node[$segment];
        }
        $node = $value;
        unset($node);

        $wordPressData = $this->createMock(WordPressDataInterface::class);
        $this->configureWordPressData($wordPressData, json_encode($this->storedSettings()));
        $wordPressData->expects($this->never())->method('updateOption');
        $wordPressData->expects($this->never())->method('addOption');

        $settings = new SimpleJWTLoginSettings($wordPressData);

        try {
            $settings->saveSettingsArray($newSettings);
            $this->fail('Expected validation exception.');
        } catch (Exception $exception) {
            $this->assertSame($expectedMessage, $exception->getMessage());
        }

        $this->assertSame($this->storedSettings(), $settings->getStoredSettings());
    }

    public function testSaveSettingsArrayAcceptsRealStoredSettingsUnchanged()
    {
        $stored = (string) file_get_contents(__DIR__ . '/Settings/fixtures/stored-settings.json');

        $wordPressData = $this->createMock(WordPressDataInterface::class);
        $this->configureWordPressData($wordPressData, $stored);
        $wordPressData->method('parseUrl')->willReturnCallback('parse_url');
        $wordPressData->expects($this->once())->method('updateOption')->willReturn(true);

        $settings = new SimpleJWTLoginSettings($wordPressData);

        $this->assertSame([], $settings->saveSettingsArray(json_decode($stored, true)));
    }

    public function testSaveSettingsArrayForcesCurrentSchemaVersion()
    {
        $newSettings = $this->storedSettings();
        unset($newSettings['_schema_version']);

        $wordPressData = $this->createStub(WordPressDataInterface::class);
        $this->configureWordPressData($wordPressData, json_encode($this->storedSettings()));
        $wordPressData->method('updateOption')->willReturn(true);

        $settings = new SimpleJWTLoginSettings($wordPressData);
        $settings->saveSettingsArray($newSettings);

        $stored = $settings->getStoredSettings();
        $this->assertSame(SimpleJWTLoginSettings::SCHEMA_VERSION, $stored['_schema_version']);
    }
}
