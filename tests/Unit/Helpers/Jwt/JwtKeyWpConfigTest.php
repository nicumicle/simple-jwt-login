<?php

namespace SimpleJwtLoginTests\Unit\Helpers\Jwt;

use PHPUnit\Framework\Attributes\DataProvider;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyWpConfig;

class JwtKeyWpConfigTest extends JwtKeyBase
{
    /**
     * @var boolean $preserveGlobalState
     */
    protected $preserveGlobalState = true;

    #[DataProvider('settingsProvider')]
    /**
     * @param array $settings
     * @param string|null $expectedPublicKey
     * @param string|null $expectedPrivateKey
     */
    public function testPrivateAndPublicKey($settings, $expectedPublicKey, $expectedPrivateKey)
    {
        $settings = $this->getSettingsMock($settings);
        $jwtKeyWpConfig = new JwtKeyWpConfig($settings);

        define(JwtKeyWpConfig::SIMPLE_JWT_PUBLIC_KEY, $expectedPublicKey);
        define(JwtKeyWpConfig::SIMPLE_JWT_PRIVATE_KEY, $expectedPrivateKey);

        $this->assertSame(
            $expectedPublicKey,
            $jwtKeyWpConfig->getPublicKey(),
            'Public key issue'
        );
        $this->assertSame(
            $expectedPrivateKey,
            $jwtKeyWpConfig->getPrivateKey(),
            'Private key issue'
        );
    }

    public static function settingsProvider()
    {
        return [
            [
                'settings' => [],
                'expectedPublicKey' => null,
                'expectedPrivateKey' => null,
            ],
            [
                'settings' => [],
                'expectedPublicKey' => 'publicKeyValue',
                'expectedPrivateKey' => 'publicKeyValue',
            ],
            [
                'settings' => [
                    'jwt_algorithm' => 'RS512'
                ],
                'expectedPublicKey' => 'publicKeyValue',
                'expectedPrivateKey' => 'privateKeyValue',
            ],
        ];
    }
}
