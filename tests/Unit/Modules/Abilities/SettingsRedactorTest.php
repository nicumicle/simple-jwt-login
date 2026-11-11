<?php

namespace SimpleJwtLoginTests\Unit\Modules\Abilities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Abilities\AbilitySettingsPolicy;
use SimpleJWTLogin\Modules\Abilities\SettingsRedactor;

class SettingsRedactorTest extends TestCase
{
    /**
     * @return array
     */
    private function settingsFixture()
    {
        return [
            'general' => [
                'decryption_key'         => 'top-secret',
                'decryption_key_public'  => '',
                'decryption_key_private' => 'cHJpdmF0ZQ==',
                'route_namespace'        => 'simple-jwt-login/v1/',
                'request_keys'           => ['header' => 'Authorization'],
            ],
            'authorization' => [
                'refresh_token_key' => 'refresh-secret',
                'ttl'               => '60',
            ],
            'integrations' => [
                'oauth' => [
                    'google' => ['client_id' => 'gid', 'client_secret' => 'gsecret'],
                ],
            ],
            'auth_codes' => [
                'codes' => [
                    ['code' => 'MY-AUTH-CODE', 'role' => 'admin', 'expiration_date' => ''],
                ],
                'key' => 'AUTH_KEY',
            ],
            'jwt_rules' => [
                'rules' => [
                    ['name' => 'rule', 'decryption_key' => 'rule-secret'],
                ],
            ],
            'reset_password' => [
                'email_body' => base64_encode('<p>Your code: {{CODE}}</p>'),
            ],
            'webhooks' => [
                'enabled' => true,
                'items'   => [
                    [
                        'url'     => 'https://example.com/hook',
                        'headers' => [
                            ['key' => 'Authorization', 'value' => 'Bearer abc'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{0: string[], 1: mixed}>
     */
    public static function redactedPathProvider()
    {
        return [
            'decryption key masked'          => [['general', 'decryption_key'], SettingsRedactor::MASK],
            'empty secret stays empty'       => [['general', 'decryption_key_public'], ''],
            'private key masked'             => [['general', 'decryption_key_private'], SettingsRedactor::MASK],
            'refresh token key masked'       => [['authorization', 'refresh_token_key'], SettingsRedactor::MASK],
            'oauth client secret masked'     => [['integrations', 'oauth', 'google', 'client_secret'], SettingsRedactor::MASK],
            'auth code masked'               => [['auth_codes', 'codes', 0, 'code'], SettingsRedactor::MASK],
            'jwt rule key masked'            => [['jwt_rules', 'rules', 0, 'decryption_key'], SettingsRedactor::MASK],
            'webhook header value masked'    => [['webhooks', 'items', 0, 'headers', 0, 'value'], SettingsRedactor::MASK],
            'webhook header key kept'        => [['webhooks', 'items', 0, 'headers', 0, 'key'], 'Authorization'],
            'route namespace kept'           => [['general', 'route_namespace'], 'simple-jwt-login/v1/'],
            'request key header kept'        => [['general', 'request_keys', 'header'], 'Authorization'],
            'oauth client id kept'           => [['integrations', 'oauth', 'google', 'client_id'], 'gid'],
            'auth code role kept'            => [['auth_codes', 'codes', 0, 'role'], 'admin'],
            'auth codes request key kept'    => [['auth_codes', 'key'], 'AUTH_KEY'],
            'ttl kept'                       => [['authorization', 'ttl'], '60'],
            'email body decoded'             => [['reset_password', 'email_body'], '<p>Your code: {{CODE}}</p>'],
        ];
    }

    #[DataProvider('redactedPathProvider')]
    public function testRedact($path, $expected)
    {
        $redacted = (new SettingsRedactor(new AbilitySettingsPolicy()))->redact($this->settingsFixture());

        $value = $redacted;
        foreach ($path as $segment) {
            $this->assertArrayHasKey($segment, $value);
            $value = $value[$segment];
        }
        $this->assertSame($expected, $value);
    }

    public function testRedactDoesNotMutateInput()
    {
        $settings = $this->settingsFixture();
        (new SettingsRedactor(new AbilitySettingsPolicy()))->redact($settings);

        $this->assertSame($this->settingsFixture(), $settings);
    }

    public function testRedactWebhookMasksOnlyHeaderValues()
    {
        $webhook = [
            'url'     => 'https://example.com',
            'headers' => [['key' => 'X-Token', 'value' => 'abc'], ['key' => 'X-Empty', 'value' => '']],
        ];

        $result = (new SettingsRedactor(new AbilitySettingsPolicy()))->redactWebhook($webhook);

        $this->assertSame(
            [
                'url'     => 'https://example.com',
                'headers' => [
                    ['key' => 'X-Token', 'value' => SettingsRedactor::MASK],
                    ['key' => 'X-Empty', 'value' => ''],
                ],
            ],
            $result
        );
    }

    public function testRedactHandlesNonArrayInput()
    {
        $this->assertSame([], (new SettingsRedactor(new AbilitySettingsPolicy()))->redact(null));
    }
}
