<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\SettingsDiff;

class SettingsDiffTest extends TestCase
{
    /**
     * @return array<string, array{0: array, 1: array, 2: string, 3: string[]}>
     */
    public static function secretChangeProvider()
    {
        return [
            'auth code value changed' => [
                ['auth_codes' => ['codes' => [['code' => 'OLD-CODE-1', 'role' => 'editor']]]],
                ['auth_codes' => ['codes' => [['code' => 'NEW-CODE-2', 'role' => 'editor']]]],
                'auth_codes.codes',
                ['OLD-CODE-1', 'NEW-CODE-2'],
            ],
            'jwt rule key changed' => [
                ['jwt_rules' => ['rules' => [['iss' => 'a', 'decryption_key' => 'OLD-RULE-KEY']]]],
                ['jwt_rules' => ['rules' => [['iss' => 'a', 'decryption_key' => 'NEW-RULE-KEY']]]],
                'jwt_rules.rules',
                ['OLD-RULE-KEY', 'NEW-RULE-KEY'],
            ],
            'webhook header value changed' => [
                ['webhooks' => ['items' => [['url' => 'https://a.b', 'headers' => [['key' => 'Authorization', 'value' => 'Bearer OLD']]]]]],
                ['webhooks' => ['items' => [['url' => 'https://a.b', 'headers' => [['key' => 'Authorization', 'value' => 'Bearer NEW']]]]]],
                'webhooks.items',
                ['Bearer OLD', 'Bearer NEW'],
            ],
            'general decryption key changed' => [
                ['general' => ['decryption_key' => 'OLD-MAIN-KEY']],
                ['general' => ['decryption_key' => 'NEW-MAIN-KEY']],
                'general.decryption_key',
                ['OLD-MAIN-KEY', 'NEW-MAIN-KEY'],
            ],
        ];
    }

    #[DataProvider('secretChangeProvider')]
    public function testSecretChangesAreReportedWithoutTheSecret($old, $new, $changedKey, $secrets)
    {
        $diff = (new SettingsDiff())->build($old, $new);
        $json = (string) json_encode($diff);

        $this->assertArrayHasKey($changedKey, $diff['changed']);
        foreach ($secrets as $secret) {
            $this->assertStringNotContainsString($secret, $json);
        }
    }

    public function testNonSecretChangesKeepTheirValues()
    {
        $diff = (new SettingsDiff())->build(
            ['cors' => ['allow_origin' => '*'], 'auth_codes' => ['codes' => [['code' => 'C', 'role' => 'editor']]]],
            ['cors' => ['allow_origin' => 'https://app.example.com'], 'auth_codes' => ['codes' => [['code' => 'C', 'role' => 'author']]]]
        );

        $this->assertSame(['from' => '*', 'to' => 'https://app.example.com'], $diff['changed']['cors.allow_origin']);
        $this->assertStringContainsString('author', $diff['changed']['auth_codes.codes']['to']);
        $this->assertStringNotContainsString('"C"', $diff['changed']['auth_codes.codes']['to']);
    }
}
