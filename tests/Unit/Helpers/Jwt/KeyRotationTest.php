<?php

namespace SimpleJwtLoginTests\Unit\Helpers\Jwt;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\Jwt\KeyRotation;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\Settings\JwtRulesSettings;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

class KeyRotationTest extends TestCase
{
    #[DataProvider('keepProvider')]
    public function testKeepCurrentKey(array $general, array $expectedKeyFields): void
    {
        $settings = ['general' => $general, 'jwt_rules' => ['rules' => [['iss' => 'partner']]]];

        $result = KeyRotation::keepCurrentKey($settings, $this->makeJwtSettings(), 1700000000);

        $rules = $result['jwt_rules']['rules'];
        $this->assertSame(['iss' => 'partner'], $rules[0], 'Other rules are kept, first.');
        $this->assertSame([
            'condition_type'     => 'payload',
            'condition_key'      => 'iat',
            'condition_operator' => JwtRulesSettings::OPERATOR_LESS_THAN,
            'condition_value'    => '1700000000',
            'algorithm'          => $general['jwt_algorithm'],
            'login_by'           => 1,
            'login_by_parameter' => 'id',
            'audience'           => 'my-app',
        ] + $expectedKeyFields, $rules[1]);
    }

    public static function keepProvider(): array
    {
        return [
            'hmac' => [
                ['jwt_algorithm' => 'HS512', 'decryption_source' => '0', 'decryption_key' => 'b2xk', 'decryption_key_base64' => 1],
                ['decryption_key' => 'b2xk', 'decryption_key_base64' => true],
            ],
            'rsa keys stay base64' => [
                ['jwt_algorithm' => 'RS256', 'decryption_source' => '0', 'decryption_key_public' => 'UFVC', 'decryption_key_private' => 'UFJJVg=='],
                ['decryption_key_public' => 'UFVC', 'decryption_key_private' => 'UFJJVg=='],
            ],
        ];
    }

    #[DataProvider('nothingToKeepProvider')]
    public function testNothingIsKeptWithoutAStoredKey(array $general): void
    {
        $settings = ['general' => $general];

        $this->assertSame($settings, KeyRotation::keepCurrentKey($settings, $this->makeJwtSettings(), 1700000000));
    }

    public static function nothingToKeepProvider(): array
    {
        return [
            'no key'             => [['jwt_algorithm' => 'HS256', 'decryption_source' => '0', 'decryption_key' => '']],
            'key in wp-config'   => [['jwt_algorithm' => 'HS256', 'decryption_source' => '1', 'decryption_key' => 'ignored']],
            'rsa without a key'  => [['jwt_algorithm' => 'RS256', 'decryption_source' => '0', 'decryption_key_public' => '']],
        ];
    }

    #[DataProvider('refreshKeyProvider')]
    public function testRefreshTokensKeepTheirKey(array $authorization, string $expected): void
    {
        $settings = [
            'general'       => ['jwt_algorithm' => 'RS256', 'decryption_source' => '0', 'decryption_key' => 'hmac-key'],
            'authorization' => $authorization,
        ];

        $result = KeyRotation::keepCurrentKey($settings, $this->makeJwtSettings(), 1700000000);

        $this->assertSame($expected, $result['authorization']['refresh_token_key']);
    }

    public static function refreshKeyProvider(): array
    {
        return [
            'no refresh key: the signing key is kept' => [[], 'hmac-key'],
            'own refresh key is not changed'          => [['refresh_token_key' => 'own'], 'own'],
        ];
    }

    public function testRemoveExpiredAndRemoveAllOnlyTouchRotationRules(): void
    {
        $partner = ['iss' => 'partner'];
        $settings = ['jwt_rules' => ['rules' => [
            $this->rotationRule('1000'),
            $partner,
            $this->rotationRule('5000'),
        ]]];

        // Grace 3600s at 5000: the rule of 1000 ended at 4600, the one of 5000 ends at 8600.
        $this->assertSame(
            [$partner, $this->rotationRule('5000')],
            KeyRotation::removeExpired($settings, 3600, 5000)['jwt_rules']['rules']
        );
        $this->assertSame($settings, KeyRotation::removeExpired($settings, 3600, 4600), 'Kept until the grace has passed.');
        $this->assertSame([$partner], KeyRotation::removeAll($settings)['jwt_rules']['rules']);
    }

    public function testGraceIsTheJwtLifetimePlusTheLeeway(): void
    {
        $this->assertSame(30 * 60 + 60, KeyRotation::graceSeconds($this->makeJwtSettings()));
    }

    private function rotationRule(string $rotatedAt): array
    {
        return [
            'condition_type'     => 'payload',
            'condition_key'      => 'iat',
            'condition_operator' => JwtRulesSettings::OPERATOR_LESS_THAN,
            'condition_value'    => $rotatedAt,
        ];
    }

    private function makeJwtSettings(): SimpleJWTLoginSettings
    {
        $stored = [
            'login'         => ['login_by' => 1, 'login_by_parameter' => 'id'],
            'authorization' => ['aud' => 'my-app', 'ttl' => 30],
        ];
        $jwtSettings = $this->createStub(SimpleJWTLoginSettings::class);
        $jwtSettings->method('getLoginSettings')->willReturn((new LoginSettings())->withSettings($stored));
        $jwtSettings->method('getAuthenticationSettings')->willReturn((new AuthenticationSettings())->withSettings($stored));

        return $jwtSettings;
    }
}
