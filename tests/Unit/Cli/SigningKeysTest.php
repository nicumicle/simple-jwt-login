<?php

namespace SimpleJwtLoginTests\Unit\Cli;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Cli\SigningKeys;

class SigningKeysTest extends TestCase
{
    #[DataProvider('hmacProvider')]
    public function testGenerateHmacKey(string $algorithm, int $length): void
    {
        $keys = (new SigningKeys())->generate($algorithm);

        $this->assertSame(['decryption_key'], array_keys($keys));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{' . $length . '}$/', $keys['decryption_key']);
    }

    public static function hmacProvider(): array
    {
        return [
            'HS256' => ['HS256', 64],
            'HS384' => ['HS384', 96],
            'HS512' => ['HS512', 128],
        ];
    }

    public function testGenerateRsaKeyPairThatSignsAndVerifies(): void
    {
        $keys = (new SigningKeys())->generate('RS256');

        $this->assertTrue(openssl_sign('data', $signature, $keys['decryption_key_private'], OPENSSL_ALGO_SHA256));
        $this->assertSame(1, openssl_verify('data', $signature, $keys['decryption_key_public'], OPENSSL_ALGO_SHA256));
    }

    public function testGenerateRejectsUnknownAlgorithm(): void
    {
        $this->expectException(Exception::class);
        (new SigningKeys())->generate('none');
    }

    #[DataProvider('applyProvider')]
    public function testApply(string $algorithm, array $keys, array $expected): void
    {
        $settings = ['general' => [
            'jwt_algorithm'          => 'HS256',
            'decryption_source'      => '1',
            'decryption_key'         => 'old',
            'decryption_key_base64'  => true,
            'route_namespace'        => 'kept/v1/',
        ], 'jwt_rules' => ['rules' => [['key' => 'rule-key']]]];

        $result = (new SigningKeys())->apply($settings, $algorithm, $keys);

        $this->assertSame($expected, array_intersect_key($result['general'], $expected));
        $this->assertSame('0', $result['general']['decryption_source']);
        $this->assertSame('kept/v1/', $result['general']['route_namespace']);
        $this->assertSame($settings['jwt_rules'], $result['jwt_rules']);
    }

    public static function applyProvider(): array
    {
        return [
            'hmac'  => ['HS512', ['decryption_key' => 'new'], [
                'jwt_algorithm' => 'HS512', 'decryption_key' => 'new', 'decryption_key_base64' => false,
            ]],
            'rsa is stored base64' => ['RS256', ['decryption_key_private' => 'PRIV', 'decryption_key_public' => 'PUB'], [
                'jwt_algorithm'          => 'RS256',
                'decryption_key'         => 'old',
                'decryption_key_private' => base64_encode('PRIV'),
                'decryption_key_public'  => base64_encode('PUB'),
            ]],
        ];
    }

    #[DataProvider('resolveProvider')]
    public function testResolveAlgorithm(?string $requested, string $expected): void
    {
        $this->assertSame($expected, (new SigningKeys())->resolveAlgorithm($requested, 'HS384'));
    }

    public static function resolveProvider(): array
    {
        return [
            'not given'  => [null, 'HS384'],
            'lower case' => ['rs256', 'RS256'],
            'unknown is returned to be rejected' => ['none', 'NONE'],
        ];
    }

    public function testRandomPicksEverySupportedAlgorithm(): void
    {
        $picked = [];
        for ($attempt = 0; $attempt < 300; $attempt++) {
            $picked[(new SigningKeys())->resolveAlgorithm('Random', 'HS256')] = true;
        }
        ksort($picked);
        $expected = SigningKeys::algorithms();
        sort($expected);

        $this->assertSame($expected, array_keys($picked));
    }
}
