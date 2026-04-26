<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class AuthenticationSettingsTest extends TestCase
{
    /**
     * @var WordPressDataInterface
     */
    private $wordPressData;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressData = $this->createStub(WordPressDataInterface::class);
        $this->wordPressData->method('sanitizeTextField')
            ->willReturnCallback(
                function ($parameter) {
                    return $parameter;
                }
            );
    }

    private function buildWithSettings(array $settings): AuthenticationSettings
    {
        return (new AuthenticationSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings($settings)
            ->withPost([]);
    }

    private function buildFromPost(array $post): AuthenticationSettings
    {
        $s = (new AuthenticationSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost($post);
        $s->initSettingsFromPost();
        return $s;
    }

    // ─── isAuthenticationEnabled ─────────────────────────────────────────────

    #[DataProvider('authEnabledProvider')]
    public function testIsAuthenticationEnabled(array $settings, bool $expected): void
    {
        $this->assertSame($expected, $this->buildWithSettings($settings)->isAuthenticationEnabled());
    }

    public static function authEnabledProvider(): array
    {
        return [
            'not set defaults to disabled' => [[], false],
            'empty string is disabled'     => [['allow_authentication' => ''], false],
            'string 0 is disabled'         => [['allow_authentication' => '0'], false],
            'string 1 is enabled'          => [['allow_authentication' => '1'], true],
            'int 1 is enabled'             => [['allow_authentication' => 1], true],
        ];
    }

    // ─── isPayloadDataEnabled ────────────────────────────────────────────────

    #[DataProvider('payloadDataEnabledProvider')]
    public function testIsPayloadDataEnabled(array $settings, string $param, bool $expected): void
    {
        $this->assertSame($expected, $this->buildWithSettings($settings)->isPayloadDataEnabled($param));
    }

    public static function payloadDataEnabledProvider(): array
    {
        $allParams = [
            AuthenticationSettings::JWT_PAYLOAD_PARAM_IAT,
            AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP,
            AuthenticationSettings::JWT_PAYLOAD_PARAM_EMAIL,
            AuthenticationSettings::JWT_PAYLOAD_PARAM_ID,
            AuthenticationSettings::JWT_PAYLOAD_PARAM_SITE,
            AuthenticationSettings::JWT_PAYLOAD_PARAM_USERNAME,
            AuthenticationSettings::JWT_PAYLOAD_PARAM_ISS,
        ];

        $cases = ['no payload set returns false' => [[], 'email', false]];
        foreach ($allParams as $param) {
            $cases["$param present in payload"] = [['jwt_payload' => $allParams], $param, true];
            $cases["$param absent from payload"] = [['jwt_payload' => []], $param, false];
        }
        $cases['unknown param not in payload'] = [['jwt_payload' => $allParams], 'unknown_param', false];

        return $cases;
    }

    // ─── getJwtPayloadParameters ─────────────────────────────────────────────

    public function testGetJwtPayloadParametersReturnsAllParams(): void
    {
        $params = $this->buildWithSettings([])->getJwtPayloadParameters();
        $this->assertSame(
            [
                AuthenticationSettings::JWT_PAYLOAD_PARAM_IAT,
                AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP,
                AuthenticationSettings::JWT_PAYLOAD_PARAM_EMAIL,
                AuthenticationSettings::JWT_PAYLOAD_PARAM_ID,
                AuthenticationSettings::JWT_PAYLOAD_PARAM_SITE,
                AuthenticationSettings::JWT_PAYLOAD_PARAM_USERNAME,
                AuthenticationSettings::JWT_PAYLOAD_PARAM_ISS,
            ],
            $params
        );
    }

    // ─── getAuthJwtTtl ───────────────────────────────────────────────────────

    #[DataProvider('authJwtTtlProvider')]
    public function testGetAuthJwtTtl(array $settings, int $expected): void
    {
        $this->assertSame($expected, $this->buildWithSettings($settings)->getAuthJwtTtl());
    }

    public static function authJwtTtlProvider(): array
    {
        return [
            'defaults to 60 when not set' => [[], 60],
            'returns string value as int'  => [['jwt_auth_ttl' => '120'], 120],
            'returns int value'            => [['jwt_auth_ttl' => 45], 45],
        ];
    }

    // ─── getAuthJwtRefreshTtl ────────────────────────────────────────────────

    #[DataProvider('authJwtRefreshTtlProvider')]
    public function testGetAuthJwtRefreshTtl(array $settings, int $expected): void
    {
        $this->assertSame($expected, $this->buildWithSettings($settings)->getAuthJwtRefreshTtl());
    }

    public static function authJwtRefreshTtlProvider(): array
    {
        return [
            'defaults to 20160 when not set' => [[], 20160],
            'returns string value as int'     => [['jwt_auth_refresh_ttl' => '100'], 100],
            'returns int value'               => [['jwt_auth_refresh_ttl' => 9999], 9999],
        ];
    }

    // ─── getAuthIss ──────────────────────────────────────────────────────────

    public function testGetAuthIssReturnsConfiguredValue(): void
    {
        $s = $this->buildWithSettings(['jwt_auth_iss' => 'https://example.com']);
        $this->assertSame('https://example.com', $s->getAuthIss());
    }

    public function testGetAuthIssDefaultsToSiteUrl(): void
    {
        $this->wordPressData->method('getSiteUrl')->willReturn('https://site.test');
        $this->assertSame('https://site.test', $this->buildWithSettings([])->getAuthIss());
    }

    // ─── getAllowedIps ───────────────────────────────────────────────────────

    #[DataProvider('allowedIpsProvider')]
    public function testGetAllowedIps(array $settings, string $expected): void
    {
        $this->assertSame($expected, $this->buildWithSettings($settings)->getAllowedIps());
    }

    public static function allowedIpsProvider(): array
    {
        return [
            'defaults to empty string'   => [[], ''],
            'returns single IP'          => [['auth_ip' => '192.168.1.1'], '192.168.1.1'],
            'returns comma-separated IPs' => [['auth_ip' => '10.0.0.1,10.0.0.2'], '10.0.0.1,10.0.0.2'],
        ];
    }

    // ─── Boolean flag getters ────────────────────────────────────────────────

    #[DataProvider('booleanFlagProvider')]
    public function testBooleanFlags(string $method, array $settings, bool $expected): void
    {
        $this->assertSame($expected, $this->buildWithSettings($settings)->$method());
    }

    public static function booleanFlagProvider(): array
    {
        return [
            // isAuthKeyRequired
            'isAuthKeyRequired: default false' => ['isAuthKeyRequired', [], false],
            'isAuthKeyRequired: true'          => ['isAuthKeyRequired', ['auth_requires_auth_code' => true], true],
            'isAuthKeyRequired: false'         => ['isAuthKeyRequired', ['auth_requires_auth_code' => false], false],

            // isAuthPasswordBase64Encoded
            'isAuthPasswordBase64Encoded: default false' => ['isAuthPasswordBase64Encoded', [], false],
            'isAuthPasswordBase64Encoded: true'          => ['isAuthPasswordBase64Encoded', ['auth_password_base64' => true], true],
            'isAuthPasswordBase64Encoded: false'         => ['isAuthPasswordBase64Encoded', ['auth_password_base64' => false], false],

            // isRefreshAuthKeyRequired
            'isRefreshAuthKeyRequired: default false' => ['isRefreshAuthKeyRequired', [], false],
            'isRefreshAuthKeyRequired: true'          => ['isRefreshAuthKeyRequired', ['refresh_requires_auth_code' => true], true],
            'isRefreshAuthKeyRequired: false'         => ['isRefreshAuthKeyRequired', ['refresh_requires_auth_code' => false], false],

            // isValidateAuthKeyRequired
            'isValidateAuthKeyRequired: default false' => ['isValidateAuthKeyRequired', [], false],
            'isValidateAuthKeyRequired: true'          => ['isValidateAuthKeyRequired', ['validate_requires_auth_code' => true], true],
            'isValidateAuthKeyRequired: false'         => ['isValidateAuthKeyRequired', ['validate_requires_auth_code' => false], false],

            // isRevokeAuthKeyRequired
            'isRevokeAuthKeyRequired: default false' => ['isRevokeAuthKeyRequired', [], false],
            'isRevokeAuthKeyRequired: true'          => ['isRevokeAuthKeyRequired', ['revoke_requires_auth_code' => true], true],
            'isRevokeAuthKeyRequired: false'         => ['isRevokeAuthKeyRequired', ['revoke_requires_auth_code' => false], false],
        ];
    }

    // ─── isRefreshTokenEnabled ───────────────────────────────────────────────

    #[DataProvider('refreshTokenEnabledProvider')]
    public function testIsRefreshTokenEnabled(array $settings, bool $expected): void
    {
        $this->assertSame($expected, $this->buildWithSettings($settings)->isRefreshTokenEnabled());
    }

    public static function refreshTokenEnabledProvider(): array
    {
        return [
            'not set defaults to disabled' => [[], false],
            'empty string is disabled'     => [['allow_refresh_token' => ''], false],
            'string 1 is enabled'          => [['allow_refresh_token' => '1'], true],
            'int 1 is enabled'             => [['allow_refresh_token' => 1], true],
        ];
    }

    // ─── getRefreshTokenKey ──────────────────────────────────────────────────

    #[DataProvider('refreshTokenKeyProvider')]
    public function testGetRefreshTokenKey(array $settings, string $expected): void
    {
        $this->assertSame($expected, $this->buildWithSettings($settings)->getRefreshTokenKey());
    }

    public static function refreshTokenKeyProvider(): array
    {
        return [
            'defaults to empty string' => [[], ''],
            'returns configured key'   => [['refresh_token_key' => 'my-secret'], 'my-secret'],
        ];
    }

    // ─── isValidateTokenEnabled ──────────────────────────────────────────────

    #[DataProvider('validateTokenEnabledProvider')]
    public function testIsValidateTokenEnabled(array $settings, bool $expected): void
    {
        $this->assertSame($expected, $this->buildWithSettings($settings)->isValidateTokenEnabled());
    }

    public static function validateTokenEnabledProvider(): array
    {
        return [
            'not set defaults to true' => [[], true],
            'string 1 is enabled'      => [['allow_validate_token' => '1'], true],
            'int 0 is disabled'        => [['allow_validate_token' => 0], false],
            'empty string is disabled' => [['allow_validate_token' => ''], false],
        ];
    }

    // ─── isRevokeTokenEnabled ────────────────────────────────────────────────

    #[DataProvider('revokeTokenEnabledProvider')]
    public function testIsRevokeTokenEnabled(array $settings, bool $expected): void
    {
        $this->assertSame($expected, $this->buildWithSettings($settings)->isRevokeTokenEnabled());
    }

    public static function revokeTokenEnabledProvider(): array
    {
        return [
            'not set defaults to true' => [[], true],
            'string 1 is enabled'      => [['allow_revoke_token' => '1'], true],
            'int 0 is disabled'        => [['allow_revoke_token' => 0], false],
            'empty string is disabled' => [['allow_revoke_token' => ''], false],
        ];
    }

    // ─── initSettingsFromPost ────────────────────────────────────────────────

    #[DataProvider('initFromPostProvider')]
    public function testInitSettingsFromPost(array $post, string $method, $expected): void
    {
        $this->assertSame($expected, $this->buildFromPost($post)->$method());
    }

    public static function initFromPostProvider(): array
    {
        return [
            'allow_authentication persisted'         => [['allow_authentication' => '1'], 'isAuthenticationEnabled', true],
            'auth disabled when not in post'         => [[], 'isAuthenticationEnabled', false],
            'auth_ip persisted'                      => [['auth_ip' => '10.0.0.1'], 'getAllowedIps', '10.0.0.1'],
            'jwt_auth_ttl persisted as int'          => [['jwt_auth_ttl' => '90'], 'getAuthJwtTtl', 90],
            'jwt_auth_ttl default when missing'      => [[], 'getAuthJwtTtl', 60],
            'jwt_auth_refresh_ttl persisted as int'  => [['jwt_auth_refresh_ttl' => '500'], 'getAuthJwtRefreshTtl', 500],
            'jwt_auth_refresh_ttl default missing'   => [[], 'getAuthJwtRefreshTtl', 20160],
            'auth_requires_auth_code true'           => [['auth_requires_auth_code' => '1'], 'isAuthKeyRequired', true],
            'auth_requires_auth_code false missing'  => [[], 'isAuthKeyRequired', false],
            'auth_password_base64 true'              => [['auth_password_base64' => '1'], 'isAuthPasswordBase64Encoded', true],
            'auth_password_base64 false missing'     => [[], 'isAuthPasswordBase64Encoded', false],
            'allow_refresh_token persisted'          => [['allow_refresh_token' => '1'], 'isRefreshTokenEnabled', true],
            'refresh_token_key persisted'            => [['refresh_token_key' => 'key123'], 'getRefreshTokenKey', 'key123'],
            'refresh_requires_auth_code true'        => [['refresh_requires_auth_code' => '1'], 'isRefreshAuthKeyRequired', true],
            'validate_requires_auth_code true'       => [['validate_requires_auth_code' => '1'], 'isValidateAuthKeyRequired', true],
            'revoke_requires_auth_code true'         => [['revoke_requires_auth_code' => '1'], 'isRevokeAuthKeyRequired', true],
        ];
    }

    // ─── validateSettings – invalid cases ────────────────────────────────────

    #[DataProvider('invalidSettingsProvider')]
    public function testValidateSettingsThrows(array $post, string $exceptionMessage): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exceptionMessage);

        $s = (new AuthenticationSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost($post);
        $s->validateSettings();
    }

    public static function invalidSettingsProvider(): array
    {
        return [
            'empty payload when auth enabled' => [
                ['allow_authentication' => 1],
                'Authentication payload data can not be empty.',
            ],
            'zero TTL' => [
                ['allow_authentication' => 1, 'jwt_payload' => ['exp'], 'jwt_auth_ttl' => 0],
                'Authentication JWT time to live should be greater than zero.',
            ],
            'negative TTL' => [
                ['allow_authentication' => 1, 'jwt_payload' => ['exp'], 'jwt_auth_ttl' => -1],
                'Authentication JWT time to live should be greater than zero.',
            ],
            'missing TTL field' => [
                ['allow_authentication' => 1, 'jwt_payload' => ['exp']],
                'Authentication JWT time to live should be greater than zero.',
            ],
            'refresh enabled, zero refresh TTL' => [
                [
                    'allow_authentication' => 1, 'jwt_payload' => ['exp'], 'jwt_auth_ttl' => 60,
                    'allow_refresh_token' => 1, 'jwt_auth_refresh_ttl' => 0,
                ],
                'Authentication JWT Refresh time to live should be greater than zero.',
            ],
            'refresh enabled, negative refresh TTL' => [
                [
                    'allow_authentication' => 1, 'jwt_payload' => ['exp'], 'jwt_auth_ttl' => 60,
                    'allow_refresh_token' => 1, 'jwt_auth_refresh_ttl' => -1,
                ],
                'Authentication JWT Refresh time to live should be greater than zero.',
            ],
            'refresh enabled, missing refresh TTL' => [
                [
                    'allow_authentication' => 1, 'jwt_payload' => ['exp'], 'jwt_auth_ttl' => 60,
                    'allow_refresh_token' => 1,
                ],
                'Authentication JWT Refresh time to live should be greater than zero.',
            ],
            'refresh enabled, empty refresh key' => [
                [
                    'allow_authentication' => 1, 'jwt_payload' => ['exp'], 'jwt_auth_ttl' => 60,
                    'allow_refresh_token' => 1, 'jwt_auth_refresh_ttl' => 60, 'refresh_token_key' => '',
                ],
                'Refresh Token Secret Key is required.',
            ],
            'refresh enabled, whitespace-only refresh key' => [
                [
                    'allow_authentication' => 1, 'jwt_payload' => ['exp'], 'jwt_auth_ttl' => 60,
                    'allow_refresh_token' => 1, 'jwt_auth_refresh_ttl' => 60, 'refresh_token_key' => '   ',
                ],
                'Refresh Token Secret Key is required.',
            ],
            'refresh enabled, missing refresh key field' => [
                [
                    'allow_authentication' => 1, 'jwt_payload' => ['exp'], 'jwt_auth_ttl' => 60,
                    'allow_refresh_token' => 1, 'jwt_auth_refresh_ttl' => 60,
                ],
                'Refresh Token Secret Key is required.',
            ],
        ];
    }

    // ─── validateSettings – valid cases ──────────────────────────────────────

    #[DataProvider('validSettingsProvider')]
    public function testValidateSettingsPasses(array $post): void
    {
        $s = (new AuthenticationSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost($post);
        $s->validateSettings();
        $this->addToAssertionCount(1);
    }

    public static function validSettingsProvider(): array
    {
        return [
            'allow_authentication not set – no validation triggered' => [[]],
            'allow_authentication disabled with valid ttl' => [['allow_authentication' => 0, 'jwt_auth_ttl' => 60]],
            'auth enabled with payload and positive ttl' => [
                ['allow_authentication' => 1, 'jwt_payload' => ['exp', 'id'], 'jwt_auth_ttl' => 60],
            ],
            'refresh disabled – refresh fields not validated' => [
                ['allow_authentication' => 1, 'jwt_payload' => ['exp'], 'jwt_auth_ttl' => 60, 'allow_refresh_token' => 0],
            ],
            'refresh enabled with valid key and positive ttl' => [
                [
                    'allow_authentication' => 1, 'jwt_payload' => ['exp'], 'jwt_auth_ttl' => 60,
                    'allow_refresh_token' => 1, 'jwt_auth_refresh_ttl' => 20160, 'refresh_token_key' => 'secret',
                ],
            ],
        ];
    }
}
