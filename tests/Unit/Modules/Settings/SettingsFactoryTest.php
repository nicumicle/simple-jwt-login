<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings;

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
use SimpleJWTLogin\Modules\Settings\SettingsFactory;

class SettingsFactoryTest extends TestCase
{
    /**
     * @dataProvider settingsFactoryProvider
     * @param int $type
     * @param string $expectedInstance
     *
     * @throws Exception
     */
    public function testInstances($type, $expectedInstance)
    {
        $factory = SettingsFactory::getFactory($type);

        $this->assertInstanceOf(
            $expectedInstance,
            $factory
        );
    }

    /**
     * @return array[]
     */
    public static function settingsFactoryProvider()
    {
        return [
            [
                SettingsFactory::AUTH_CODES_SETTINGS,
                AuthCodesSettings::class
            ],
            [
                SettingsFactory::AUTHENTICATION_SETTINGS,
                AuthenticationSettings::class
            ],
            [
                SettingsFactory::CORS_SETTINGS,
                CorsSettings::class
            ],
            [
                SettingsFactory::DELETE_USER_SETTINGS,
                DeleteUserSettings::class
            ],
            [
                SettingsFactory::GENERAL_SETTINGS,
                GeneralSettings::class
            ],
            [
                SettingsFactory::HOOKS_SETTINGS,
                HooksSettings::class
            ],
            [
                SettingsFactory::LOGIN_SETTINGS,
                LoginSettings::class
            ],
            [
                SettingsFactory::REGISTER_SETTINGS,
                RegisterSettings::class
            ]
        ];
    }

    public function testInvalidFactory()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Settings implementation not found.');

        SettingsFactory::getFactory(999999999);
    }
}
