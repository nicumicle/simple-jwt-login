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
            ->withSettings(['authorization' => $settings])
            ->withPost([]);
    }

    private function buildFromPost(array $post): AuthenticationSettings
    {
        $authSettings = (new AuthenticationSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost($post);
        $authSettings->initSettingsFromPost();
        return $authSettings;
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
            'empty string is disabled'     => [['enabled' => ''], false],
            'string 0 is disabled'         => [['enabled' => '0'], false],
            'string 1 is enabled'          => [['enabled' => '1'], true],
            'int 1 is enabled'             => [['enabled' => 1], true],
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
            'returns string value as int'  => [['ttl' => '120'], 120],
            'returns int value'            => [['ttl' => 45], 45],
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
            'returns string value as int'     => [['refresh_ttl' => '100'], 100],
            'returns int value'               => [['refresh_ttl' => 9999], 9999],
        ];
    }

    // ─── getAuthIss ──────────────────────────────────────────────────────────

    public function testGetAuthIssReturnsConfiguredValue(): void
    {
        $authSettings = $this->buildWithSettings(['iss' => 'https://example.com']);
        $this->assertSame('https://example.com', $authSettings->getAuthIss());
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
            'returns single IP'          => [['ip_whitelist' => '192.168.1.1'], '192.168.1.1'],
            'returns comma-separated IPs' => [['ip_whitelist' => '10.0.0.1,10.0.0.2'], '10.0.0.1,10.0.0.2'],
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
            'isAuthKeyRequired: true'          => ['isAuthKeyRequired', ['auth_code' => true], true],
            'isAuthKeyRequired: false'         => ['isAuthKeyRequired', ['auth_code' => false], false],

            // isAuthPasswordBase64Encoded
            'isAuthPasswordBase64Encoded: default false' => ['isAuthPasswordBase64Encoded', [], false],
            'isAuthPasswordBase64Encoded: true'          => ['isAuthPasswordBase64Encoded', ['password_base64' => true], true],
            'isAuthPasswordBase64Encoded: false'         => ['isAuthPasswordBase64Encoded', ['password_base64' => false], false],

            // isRefreshAuthKeyRequired
            'isRefreshAuthKeyRequired: default false' => ['isRefreshAuthKeyRequired', [], false],
            'isRefreshAuthKeyRequired: true'          => ['isRefreshAuthKeyRequired', ['refresh_auth_code' => true], true],
            'isRefreshAuthKeyRequired: false'         => ['isRefreshAuthKeyRequired', ['refresh_auth_code' => false], false],

            // isValidateAuthKeyRequired
            'isValidateAuthKeyRequired: default false' => ['isValidateAuthKeyRequired', [], false],
            'isValidateAuthKeyRequired: true'          => ['isValidateAuthKeyRequired', ['validate_auth_code' => true], true],
            'isValidateAuthKeyRequired: false'         => ['isValidateAuthKeyRequired', ['validate_auth_code' => false], false],

            // isRevokeAuthKeyRequired
            'isRevokeAuthKeyRequired: default false' => ['isRevokeAuthKeyRequired', [], false],
            'isRevokeAuthKeyRequired: true'          => ['isRevokeAuthKeyRequired', ['revoke_auth_code' => true], true],
            'isRevokeAuthKeyRequired: false'         => ['isRevokeAuthKeyRequired', ['revoke_auth_code' => false], false],
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
            'empty string is disabled'     => [['refresh_token_enabled' => ''], false],
            'string 1 is enabled'          => [['refresh_token_enabled' => '1'], true],
            'int 1 is enabled'             => [['refresh_token_enabled' => 1], true],
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
            'string 1 is enabled'      => [['validate_token_enabled' => '1'], true],
            'int 0 is disabled'        => [['validate_token_enabled' => 0], false],
            'empty string is disabled' => [['validate_token_enabled' => ''], false],
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
            'string 1 is enabled'      => [['revoke_token_enabled' => '1'], true],
            'int 0 is disabled'        => [['revoke_token_enabled' => 0], false],
            'empty string is disabled' => [['revoke_token_enabled' => ''], false],
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

        $authSettings = (new AuthenticationSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost($post);
        $authSettings->validateSettings();
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
        $authSettings = (new AuthenticationSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost($post);
        $authSettings->validateSettings();
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

    // ─── getCustomPayloadClaims ──────────────────────────────────────────────

    public function testGetCustomPayloadClaimsReturnsEmptyWhenNotSet(): void
    {
        $this->assertSame([], $this->buildWithSettings([])->getCustomPayloadClaims());
    }

    #[DataProvider('customPayloadClaimsProvider')]
    public function testGetCustomPayloadClaims(array $settings, array $expected): void
    {
        $this->assertSame($expected, $this->buildWithSettings($settings)->getCustomPayloadClaims());
    }

    public static function customPayloadClaimsProvider(): array
    {
        return [
            'single claim' => [
                ['custom_claims' => ['payload' => ['key' => ['department'], 'value' => ['engineering']]]],
                ['department' => 'engineering'],
            ],
            'multiple claims' => [
                ['custom_claims' => ['payload' => ['key' => ['dept', 'region'], 'value' => ['eng', 'eu']]]],
                ['dept' => 'eng', 'region' => 'eu'],
            ],
            'empty key is skipped' => [
                ['custom_claims' => ['payload' => ['key' => ['', 'valid'], 'value' => ['skip', 'kept']]]],
                ['valid' => 'kept'],
            ],
            'missing value defaults to empty string' => [
                ['custom_claims' => ['payload' => ['key' => ['k1'], 'value' => []]]],
                ['k1' => ''],
            ],
        ];
    }

    // ─── getCustomHeaderClaims ───────────────────────────────────────────────

    public function testGetCustomHeaderClaimsReturnsEmptyWhenNotSet(): void
    {
        $this->assertSame([], $this->buildWithSettings([])->getCustomHeaderClaims());
    }

    #[DataProvider('customHeaderClaimsProvider')]
    public function testGetCustomHeaderClaims(array $settings, array $expected): void
    {
        $this->assertSame($expected, $this->buildWithSettings($settings)->getCustomHeaderClaims());
    }

    public static function customHeaderClaimsProvider(): array
    {
        return [
            'single header claim' => [
                ['custom_claims' => ['header' => ['key' => ['x-app-id'], 'value' => ['my-app']]]],
                ['x-app-id' => 'my-app'],
            ],
            'multiple header claims' => [
                ['custom_claims' => ['header' => ['key' => ['x-app-id', 'x-version'], 'value' => ['app', 'v2']]]],
                ['x-app-id' => 'app', 'x-version' => 'v2'],
            ],
            'empty key is skipped in header' => [
                ['custom_claims' => ['header' => ['key' => ['', 'x-app'], 'value' => ['skip', 'val']]]],
                ['x-app' => 'val'],
            ],
        ];
    }

    // ─── validateSettings – custom claims ────────────────────────────────────

    #[DataProvider('invalidCustomClaimsProvider')]
    public function testValidateSettingsRejectsInvalidCustomClaims(array $post, string $message): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($message);

        (new AuthenticationSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost($post)
            ->validateSettings();
    }

    public static function invalidCustomClaimsProvider(): array
    {
        return [
            'protected payload key iat' => [
                ['custom_claims_payload' => ['key' => ['iat'], 'value' => ['1234']]],
                'reserved JWT claim',
            ],
            'protected payload key exp' => [
                ['custom_claims_payload' => ['key' => ['exp'], 'value' => ['9999']]],
                'reserved JWT claim',
            ],
            'protected payload key email' => [
                ['custom_claims_payload' => ['key' => ['email'], 'value' => ['x']]],
                'reserved JWT claim',
            ],
            'protected header key typ' => [
                ['custom_claims_header' => ['key' => ['typ'], 'value' => ['JWT']]],
                'reserved JWT claim',
            ],
            'protected header key alg' => [
                ['custom_claims_header' => ['key' => ['alg'], 'value' => ['HS256']]],
                'reserved JWT claim',
            ],
            'protected header key kid' => [
                ['custom_claims_header' => ['key' => ['kid'], 'value' => ['key1']]],
                'reserved JWT claim',
            ],
            'empty payload claim key' => [
                ['custom_claims_payload' => ['key' => [''], 'value' => ['val']]],
                'Custom claim key cannot be empty.',
            ],
            'empty header claim key' => [
                ['custom_claims_header' => ['key' => [''], 'value' => ['val']]],
                'Custom claim key cannot be empty.',
            ],
        ];
    }

    public function testValidateSettingsPassesWithValidCustomClaims(): void
    {
        $this->expectNotToPerformAssertions();

        (new AuthenticationSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost([
                'custom_claims_payload' => ['key' => ['department', 'region'], 'value' => ['eng', 'eu']],
                'custom_claims_header'  => ['key' => ['x-app-id'], 'value' => ['my-app']],
            ])
            ->validateSettings();
    }

    // ─── initSettingsFromPost – custom claims ────────────────────────────────

    public function testInitSettingsFromPostPersistsCustomPayloadClaims(): void
    {
        $authSettings = $this->buildFromPost([
            'custom_claims_payload' => ['key' => ['dept'], 'value' => ['eng']],
        ]);
        $this->assertSame(['dept' => 'eng'], $authSettings->getCustomPayloadClaims());
    }

    public function testInitSettingsFromPostPersistsCustomHeaderClaims(): void
    {
        $authSettings = $this->buildFromPost([
            'custom_claims_header' => ['key' => ['x-app'], 'value' => ['v1']],
        ]);
        $this->assertSame(['x-app' => 'v1'], $authSettings->getCustomHeaderClaims());
    }

    public function testInitSettingsFromPostEmptyWhenCustomClaimsNotInPost(): void
    {
        $authSettings = $this->buildFromPost([]);
        $this->assertSame([], $authSettings->getCustomPayloadClaims());
        $this->assertSame([], $authSettings->getCustomHeaderClaims());
    }
}
