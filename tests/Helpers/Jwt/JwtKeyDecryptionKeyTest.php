<?php


namespace SimpleJwtLoginTests\Helpers\Jwt;


use SimpleJWTLogin\Helpers\Jwt\JwtKeyDecryptionKey;

class JwtKeyDecryptionKeyTest extends JwtKeyBase
{
    /**
     * @dataProvider settingProvider
     * @param array $settings
     * @param string $expectedPublicKey
     * @param string $expectedPrivateKey
     */
    public function testPrivateAndPublicKey($settings, $expectedPublicKey, $expectedPrivateKey){
        $settings = $this->getSettingsMock($settings);
        $jwtKeyDecryptionKey = new JwtKeyDecryptionKey($settings);
        $this->assertSame(
            $expectedPublicKey,
            $jwtKeyDecryptionKey->getPublicKey()
        );
        $this->assertSame(
            $expectedPrivateKey,
            $jwtKeyDecryptionKey->getPrivateKey()
        );
    }

    public function settingProvider()
    {
        return[
            [
                'settings' => [],
                'expectedPublicKey' => '',
                'expectedPrivateKey' => '',
            ],
            [
                'settings' => [
                    'decryption_key' => '123',
                ],
                'expectedPublicKey' => '123',
                'expectedPrivateKey' => '123',
            ],
            [
                'settings' => [
                    'decryption_key' => '123',
                    'decryption_key_base64' => false,
                ],
                'expectedPublicKey' => '123',
                'expectedPrivateKey' => '123',
            ],
            [
                'settings' => [
                    'decryption_key_base64' => true,
                    'decryption_key' => base64_encode('123'),
                ],
                'expectedPublicKey' => '123',
                'expectedPrivateKey' => '123',
            ]
        ];
    }
}