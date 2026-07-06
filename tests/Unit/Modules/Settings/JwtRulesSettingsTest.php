<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\JwtRulesSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use PHPUnit\Framework\MockObject\MockObject;

class JwtRulesSettingsTest extends TestCase
{
    /**
     * @var WordPressDataInterface|MockObject
     */
    private $wordPressData;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressData = $this->createStub(WordPressDataInterface::class);
        $this->wordPressData->method('sanitizeTextField')
            ->willReturnCallback(function ($value) {
                return $value;
            });
    }

    public static function providerGetRules(): array
    {
        return [
            'no_rules_key' => [
                'settings' => [],
                'expected' => [],
            ],
            'empty_rules' => [
                'settings' => ['jwt_rules' => ['rules' => []]],
                'expected' => [],
            ],
            'single_hs_rule' => [
                'settings' => [
                    'jwt_rules' => ['rules' => [
                        ['iss' => 'my-app', 'algorithm' => 'HS256', 'decryption_key' => 'secret'],
                    ]],
                ],
                'expected' => [
                    ['iss' => 'my-app', 'algorithm' => 'HS256', 'decryption_key' => 'secret'],
                ],
            ],
        ];
    }

    #[DataProvider('providerGetRules')]
    public function testGetRules(array $settings, array $expected): void
    {
        $sut = (new JwtRulesSettings())
            ->withSettings($settings)
            ->withWordPressData($this->wordPressData);

        $this->assertSame($expected, $sut->getRules());
    }

    public static function providerFindByIss(): array
    {
        $pubKeyEncoded  = base64_encode('-----BEGIN PUBLIC KEY-----');
        $privKeyEncoded = base64_encode('-----BEGIN RSA PRIVATE KEY-----');

        return [
            'found_hs256' => [
                'settings' => [
                    'jwt_rules' => ['rules' => [
                        ['iss' => 'my-app', 'algorithm' => 'HS256', 'decryption_key' => 'secret'],
                    ]],
                ],
                'iss'      => 'my-app',
                'expected' => ['iss' => 'my-app', 'algorithm' => 'HS256', 'decryption_key' => 'secret'],
            ],
            'found_rs256' => [
                'settings' => [
                    'jwt_rules' => ['rules' => [
                        [
                            'iss'                    => 'partner',
                            'algorithm'              => 'RS256',
                            'decryption_key_public'  => $pubKeyEncoded,
                            'decryption_key_private' => $privKeyEncoded,
                        ],
                    ]],
                ],
                'iss'      => 'partner',
                'expected' => [
                    'iss'                    => 'partner',
                    'algorithm'              => 'RS256',
                    'decryption_key_public'  => $pubKeyEncoded,
                    'decryption_key_private' => $privKeyEncoded,
                ],
            ],
            'not_found' => [
                'settings' => [
                    'jwt_rules' => ['rules' => [
                        ['iss' => 'my-app', 'algorithm' => 'HS256', 'decryption_key' => 'secret'],
                    ]],
                ],
                'iss'      => 'unknown',
                'expected' => null,
            ],
            'empty_list' => [
                'settings' => [],
                'iss'      => 'anything',
                'expected' => null,
            ],
        ];
    }

    #[DataProvider('providerFindByIss')]
    public function testFindByIss(array $settings, string $iss, $expected): void
    {
        $sut = (new JwtRulesSettings())
            ->withSettings($settings)
            ->withWordPressData($this->wordPressData);

        $this->assertSame($expected, $sut->findByIss($iss));
    }

    public static function providerFindMatchingRuleConfig(): array
    {
        return [
            'legacy_iss_rule' => [
                'settings' => [
                    ['iss' => 'my-app', 'algorithm' => 'HS256', 'decryption_key' => 'secret'],
                ],
                'jwtParts' => ['header' => ['alg' => 'HS256'], 'payload' => ['iss' => 'my-app']],
                'expected' => ['iss' => 'my-app', 'algorithm' => 'HS256', 'decryption_key' => 'secret'],
            ],
            'payload_equals_rule' => [
                'settings' => [
                    [
                        'condition_type'     => 'payload',
                        'condition_key'      => 'aud',
                        'condition_operator' => 'equals',
                        'condition_value'    => 'mobile-app',
                        'algorithm'          => 'HS256',
                        'decryption_key'      => 'secret',
                    ],
                ],
                'jwtParts' => ['header' => ['alg' => 'HS256'], 'payload' => ['aud' => 'mobile-app']],
                'expected' => [
                    'condition_type'     => 'payload',
                    'condition_key'      => 'aud',
                    'condition_operator' => 'equals',
                    'condition_value'    => 'mobile-app',
                    'algorithm'          => 'HS256',
                    'decryption_key'      => 'secret',
                ],
            ],
            'header_contains_rule' => [
                'settings' => [
                    [
                        'condition_type'     => 'header',
                        'condition_key'      => 'kid',
                        'condition_operator' => 'contains',
                        'condition_value'    => 'user-',
                        'algorithm'          => 'HS256',
                        'decryption_key'      => 'secret',
                    ],
                ],
                'jwtParts' => ['header' => ['kid' => 'user-12'], 'payload' => ['iss' => 'ignored']],
                'expected' => [
                    'condition_type'     => 'header',
                    'condition_key'      => 'kid',
                    'condition_operator' => 'contains',
                    'condition_value'    => 'user-',
                    'algorithm'          => 'HS256',
                    'decryption_key'      => 'secret',
                ],
            ],
            'no_match_rule' => [
                'settings' => [
                    [
                        'condition_type'     => 'payload',
                        'condition_key'      => 'aud',
                        'condition_operator' => 'equals',
                        'condition_value'    => 'web-app',
                        'algorithm'          => 'HS256',
                        'decryption_key'      => 'secret',
                    ],
                ],
                'jwtParts' => ['header' => ['alg' => 'HS256'], 'payload' => ['aud' => 'mobile-app']],
                'expected' => null,
            ],
        ];
    }

    #[DataProvider('providerFindMatchingRuleConfig')]
    public function testFindMatchingRuleConfig(array $settings, array $jwtParts, $expected): void
    {
        $sut = (new JwtRulesSettings())
            ->withSettings(['jwt_rules' => ['rules' => $settings]])
            ->withWordPressData($this->wordPressData);

        $this->assertSame($expected, $sut->findMatchingRuleConfig($jwtParts));
    }

    public static function providerInitAndRoundTrip(): array
    {
        return [
            'hs256_rule' => [
                'post' => [
                    'jwt_rules' => json_encode([
                        ['iss' => 'my-app', 'algorithm' => 'HS256', 'decryption_key' => 'secret123'],
                    ]),
                ],
                'expectedIss'      => 'my-app',
                'expectedAlg'      => 'HS256',
                'expectedKey'      => 'secret123',
                'expectedJwtParts' => null,
            ],
            'rs256_rule' => [
                'post' => [
                    'jwt_rules' => json_encode([
                        [
                            'iss'                    => 'partner',
                            'algorithm'              => 'RS256',
                            'decryption_key_public'  => '-----BEGIN PUBLIC KEY-----',
                            'decryption_key_private' => '-----BEGIN RSA PRIVATE KEY-----',
                        ],
                    ]),
                ],
                'expectedIss'      => 'partner',
                'expectedAlg'      => 'RS256',
                'expectedKey'      => null,
                'expectedJwtParts' => null,
            ],
            'payload_condition' => [
                'post' => [
                    'jwt_rules' => json_encode([
                        [
                            'condition_type'     => 'payload',
                            'condition_key'      => 'aud',
                            'condition_operator' => 'equals',
                            'condition_value'    => 'mobile-app',
                            'algorithm'          => 'HS256',
                            'decryption_key'      => 'secret123',
                        ],
                    ]),
                ],
                'expectedIss'      => null,
                'expectedAlg'      => 'HS256',
                'expectedKey'      => 'secret123',
                'expectedJwtParts' => ['header' => [], 'payload' => ['aud' => 'mobile-app']],
            ],
            'empty_post_field' => [
                'post'             => [],
                'expectedIss'      => null,
                'expectedAlg'      => null,
                'expectedKey'      => null,
                'expectedJwtParts' => null,
            ],
        ];
    }

    #[DataProvider('providerInitAndRoundTrip')]
    public function testInitSettingsFromPost(
        array $post,
        $expectedIss,
        $expectedAlg,
        $expectedKey,
        $expectedJwtParts = null
    ): void {
        $sut = (new JwtRulesSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost($post);

        $sut->initSettingsFromPost();

        if ($expectedIss === null && $expectedJwtParts === null) {
            $this->assertSame([], $sut->getRules());
            return;
        }

        $found = $sut->findByIss($expectedIss);
        if ($expectedJwtParts !== null) {
            $found = $sut->findMatchingRuleConfig($expectedJwtParts);
        }

        $this->assertNotNull($found);
        $this->assertSame($expectedAlg, $found['algorithm']);

        if ($expectedKey !== null) {
            $this->assertSame($expectedKey, $found['decryption_key']);
            return;
        }
        // RS*: verify keys are stored base64-encoded
        $this->assertNotEmpty($found['decryption_key_public']);
        $this->assertNotEmpty($found['decryption_key_private']);
    }

    public static function providerValidationFailures(): array
    {
        return [
            'empty_iss' => [
                'rules'           => [
                    ['iss' => '', 'algorithm' => 'HS256', 'decryption_key' => 'secret'],
                ],
                'expectedMessage' => 'cannot be empty',
            ],
            'duplicate_iss' => [
                'rules'           => [
                    ['iss' => 'same', 'algorithm' => 'HS256', 'decryption_key' => 'secret1', 'login_by_parameter' => 'email'],
                    ['iss' => 'same', 'algorithm' => 'HS256', 'decryption_key' => 'secret2', 'login_by_parameter' => 'email'],
                ],
                'expectedMessage' => 'duplicate condition',
            ],
            'payload_missing_key' => [
                'rules'           => [
                    [
                        'condition_type'     => 'payload',
                        'condition_key'      => '',
                        'condition_operator' => 'equals',
                        'condition_value'    => 'mobile-app',
                        'algorithm'          => 'HS256',
                        'decryption_key'      => 'secret',
                    ],
                ],
                'expectedMessage' => 'condition key cannot be empty',
            ],
            'payload_missing_value' => [
                'rules'           => [
                    [
                        'condition_type'     => 'payload',
                        'condition_key'      => 'aud',
                        'condition_operator' => 'equals',
                        'condition_value'    => '',
                        'algorithm'          => 'HS256',
                        'decryption_key'      => 'secret',
                    ],
                ],
                'expectedMessage' => 'condition value cannot be empty',
            ],
            'hs256_empty_key' => [
                'rules'           => [
                    ['iss' => 'app', 'algorithm' => 'HS256', 'decryption_key' => '', 'login_by_parameter' => 'email'],
                ],
                'expectedMessage' => 'decryption key is required',
            ],
            'rs256_missing_pub_key' => [
                'rules'           => [
                    [
                        'iss'                    => 'app',
                        'algorithm'              => 'RS256',
                        'decryption_key_public'  => base64_encode(''),
                        'decryption_key_private' => base64_encode('some-private'),
                        'login_by_parameter'     => 'email',
                    ],
                ],
                'expectedMessage' => 'public and private keys are required',
            ],
            'rs256_missing_priv_key' => [
                'rules'           => [
                    [
                        'iss'                    => 'app',
                        'algorithm'              => 'RS256',
                        'decryption_key_public'  => base64_encode('some-public'),
                        'decryption_key_private' => base64_encode(''),
                        'login_by_parameter'     => 'email',
                    ],
                ],
                'expectedMessage' => 'public and private keys are required',
            ],
            'empty_login_by_parameter' => [
                'rules'           => [
                    ['iss' => 'app', 'algorithm' => 'HS256', 'decryption_key' => 'secret', 'login_by_parameter' => ''],
                ],
                'expectedMessage' => 'JWT payload key is required',
            ],
        ];
    }

    #[DataProvider('providerValidationFailures')]
    public function testValidationFailures(array $rules, string $expectedMessage): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/' . preg_quote($expectedMessage, '/') . '/i');

        $sut = (new JwtRulesSettings())
            ->withSettings(['jwt_rules' => ['rules' => $rules]])
            ->withWordPressData($this->wordPressData)
            ->withPost([]);

        $sut->validateSettings();
    }

    public function testValidationPassesWithEmptyRules(): void
    {
        $sut = (new JwtRulesSettings())
            ->withSettings([])
            ->withWordPressData($this->wordPressData)
            ->withPost([]);

        $sut->validateSettings();
        $this->assertSame([], $sut->getRules());
    }

    public function testValidationPassesWithValidHsRule(): void
    {
        $sut = (new JwtRulesSettings())
            ->withSettings([
                'jwt_rules' => ['rules' => [
                    ['iss' => 'my-app', 'algorithm' => 'HS256', 'decryption_key' => 'secret', 'login_by_parameter' => 'email'],
                ]],
            ])
            ->withWordPressData($this->wordPressData)
            ->withPost([]);

        $sut->validateSettings();
        $this->assertCount(1, $sut->getRules());
    }

    public function testValidationPassesWithValidRsRule(): void
    {
        $sut = (new JwtRulesSettings())
            ->withSettings([
                'jwt_rules' => ['rules' => [
                    [
                        'iss'                    => 'partner',
                        'algorithm'              => 'RS256',
                        'decryption_key_public'  => base64_encode('-----BEGIN PUBLIC KEY-----'),
                        'decryption_key_private' => base64_encode('-----BEGIN RSA PRIVATE KEY-----'),
                        'login_by_parameter'     => 'email',
                    ],
                ]],
            ])
            ->withWordPressData($this->wordPressData)
            ->withPost([]);

        $sut->validateSettings();
        $this->assertCount(1, $sut->getRules());
    }
}
