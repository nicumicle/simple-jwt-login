<?php

namespace SimpleJwtLoginTests\Unit\Helpers\Jwt;

use SimpleJWTLogin\Helpers\Jwt\JwtKeyCertificate;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyDecryptionKey;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyWpConfig;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;

class JwtKeyFactoryTest extends JwtKeyBase
{
    /**
     * @dataProvider settingsProvider
     * @param array $settingsArray
     * @param string $expected
     */
    public function testInstanceOfJwtKeyWpConfig($settingsArray, $expected)
    {
        $settingsMock = $this->getSettingsMock($settingsArray);
        $factory = JwtKeyFactory::getFactory($settingsMock);
        $this->assertInstanceOf($expected, $factory);
    }

    public static function settingsProvider()
    {
        return [
            [
                'settings' => [
                    'decryption_source' => GeneralSettings::DECRYPTION_SOURCE_CODE
                ],
                'expected' => JwtKeyWpConfig::class
            ],
            [
                'settings' => [
                    'jwt_algorithm' => 'RS512'
                ],
                'expected' => JwtKeyCertificate::class
            ],
            [
                'settings' => [
                    'jwt_algorithm' => 'HS512'
                ],
                'expected' => JwtKeyDecryptionKey::class
            ],
            [
                'settings' => [],
                'expected' => JwtKeyDecryptionKey::class
            ]
        ];
    }
}
