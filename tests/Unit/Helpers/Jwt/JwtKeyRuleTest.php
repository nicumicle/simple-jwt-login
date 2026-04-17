<?php

namespace SimpleJwtLoginTests\Unit\Helpers\Jwt;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyRule;

class JwtKeyRuleTest extends TestCase
{
    public static function providerHsKeys(): array
    {
        return [
            'plain_key' => [
                'config' => [
                    'algorithm'             => 'HS256',
                    'decryption_key'        => 'my-secret',
                    'decryption_key_base64' => false,
                ],
                'expectedPublic'  => 'my-secret',
                'expectedPrivate' => 'my-secret',
            ],
            'base64_key' => [
                'config' => [
                    'algorithm'             => 'HS512',
                    'decryption_key'        => base64_encode('decoded-secret'),
                    'decryption_key_base64' => true,
                ],
                'expectedPublic'  => 'decoded-secret',
                'expectedPrivate' => 'decoded-secret',
            ],
            'empty_key' => [
                'config' => [
                    'algorithm'      => 'HS256',
                    'decryption_key' => '',
                ],
                'expectedPublic'  => '',
                'expectedPrivate' => '',
            ],
        ];
    }

    #[DataProvider('providerHsKeys')]
    public function testHsKeyResolution(array $config, string $expectedPublic, string $expectedPrivate): void
    {
        $sut = new JwtKeyRule($config);

        $this->assertSame($expectedPublic, $sut->getPublicKey());
        $this->assertSame($expectedPrivate, $sut->getPrivateKey());
    }

    public static function providerRsKeys(): array
    {
        $pub  = '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkq\n-----END PUBLIC KEY-----';
        $priv = '-----BEGIN RSA PRIVATE KEY-----\nMIIEowIBAAK\n-----END RSA PRIVATE KEY-----';

        return [
            'rs256' => [
                'config' => [
                    'algorithm'              => 'RS256',
                    'decryption_key_public'  => base64_encode($pub),
                    'decryption_key_private' => base64_encode($priv),
                ],
                'expectedPublic'  => $pub,
                'expectedPrivate' => $priv,
            ],
            'rs512' => [
                'config' => [
                    'algorithm'              => 'RS512',
                    'decryption_key_public'  => base64_encode($pub),
                    'decryption_key_private' => base64_encode($priv),
                ],
                'expectedPublic'  => $pub,
                'expectedPrivate' => $priv,
            ],
            'rs256_empty_keys' => [
                'config' => [
                    'algorithm' => 'RS256',
                ],
                'expectedPublic'  => '',
                'expectedPrivate' => '',
            ],
        ];
    }

    #[DataProvider('providerRsKeys')]
    public function testRsKeyResolution(array $config, string $expectedPublic, string $expectedPrivate): void
    {
        $sut = new JwtKeyRule($config);

        $this->assertSame($expectedPublic, $sut->getPublicKey());
        $this->assertSame($expectedPrivate, $sut->getPrivateKey());
    }

    public function testMissingAlgorithmDefaultsToHsBehavior(): void
    {
        $config = ['decryption_key' => 'secret'];
        $sut    = new JwtKeyRule($config);

        $this->assertSame('secret', $sut->getPublicKey());
    }
}
