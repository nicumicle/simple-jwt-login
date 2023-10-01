<?php

namespace SimpleJwtLoginTests\Helpers\Jwt;

use SimpleJWTLogin\Helpers\Jwt\JwtKeyCertificate;

class JwtKeyCertificateTest extends JwtKeyBase
{
    /**
     * @dataProvider settingsProvider
     * @param array $settings
     * @param string $expectedPublicKey
     * @param string $expectedPrivateKey
     */
    public function testGetPublicKeyAndPrivateKey($settings, $expectedPublicKey, $expectedPrivateKey)
    {
        $jwtKeyCertificate = new JwtKeyCertificate($this->getSettingsMock($settings));
        $this->assertSame($expectedPrivateKey, $jwtKeyCertificate->getPrivateKey());
        $this->assertSame($expectedPublicKey, $jwtKeyCertificate->getPublicKey());
    }

    public static function settingsProvider()
    {
        return [
            [
                'settings' => [],
                'expectedPublicKey' => '',
                'expectedPrivateKey' => '',
            ],
            [
                'settings' => [
                    'decryption_key_public' => '',
                    'decryption_key_private' => ''
                ],
                'expectedPublicKey' => '',
                'expectedPrivateKey' => '',
            ],
            [
                'settings' => [
                    'decryption_key_public' => base64_encode('a'),
                    'decryption_key_private' => base64_encode('b')
                ],
                'expectedPublicKey' => 'a',
                'expectedPrivateKey' => 'b',
            ]
        ];
    }
}
