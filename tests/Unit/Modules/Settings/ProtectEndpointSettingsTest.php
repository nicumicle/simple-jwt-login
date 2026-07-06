<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\ProtectEndpointSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class ProtectEndpointSettingsTest extends TestCase
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
            ->willReturnCallback(function ($parameter) {
                return $parameter;
            });
        $this->wordPressData->method('roleExists')
            ->willReturnCallback(function ($role) {
                return in_array($role, ['administrator', 'editor', 'author', 'contributor', 'subscriber'], true);
            });
    }

    public function testAssignRulesFromPost()
    {
        $protectSettings = (new ProtectEndpointSettings())
            ->withSettings([])
            ->withPost([
                ProtectEndpointSettings::PROPERTY_GROUP => [
                    'enabled'        => '1',
                    'default_action' => ProtectEndpointSettings::DEFAULT_PROTECT_ALL,
                    'rules_url'      => ['123', '', '456'],
                    'rules_type'     => [
                        ProtectEndpointSettings::RULE_TYPE_PROTECTED,
                        ProtectEndpointSettings::RULE_TYPE_PROTECTED,
                        ProtectEndpointSettings::RULE_TYPE_PUBLIC,
                    ],
                ]
            ])
            ->withWordPressData($this->wordPressData);
        $protectSettings->initSettingsFromPost();

        $this->assertSame(true, $protectSettings->isEnabled());
        $this->assertSame(ProtectEndpointSettings::DEFAULT_PROTECT_ALL, $protectSettings->getDefaultAction());
        $this->assertSame(
            [
                ['url' => '123', 'method' => 'ALL', 'match' => 'STARTS_WITH', 'type' => 'protected', 'roles' => []],
                ['url' => '456', 'method' => 'ALL', 'match' => 'STARTS_WITH', 'type' => 'public',    'roles' => []],
            ],
            $protectSettings->getRules()
        );
    }

    public function testAssignRulesFromPostWithHTTPMethods()
    {
        $protectSettings = (new ProtectEndpointSettings())
            ->withSettings([])
            ->withPost([
                ProtectEndpointSettings::PROPERTY_GROUP => [
                    'enabled'        => '1',
                    'default_action' => ProtectEndpointSettings::DEFAULT_ALLOW_ALL,
                    'rules_url'      => ['/posts', '/users', '/comments'],
                    'rules_method'   => ['GET', 'ALL', 'PUT'],
                    'rules_type'     => [
                        ProtectEndpointSettings::RULE_TYPE_PROTECTED,
                        ProtectEndpointSettings::RULE_TYPE_PUBLIC,
                        ProtectEndpointSettings::RULE_TYPE_PROTECTED_ROLES,
                    ],
                    'rules_roles'    => ['', '', 'administrator'],
                ]
            ])
            ->withWordPressData($this->wordPressData);
        $protectSettings->initSettingsFromPost();

        $this->assertSame(true, $protectSettings->isEnabled());
        $this->assertSame(ProtectEndpointSettings::DEFAULT_ALLOW_ALL, $protectSettings->getDefaultAction());
        $this->assertSame(
            [
                ['url' => '/posts',    'method' => 'GET', 'match' => 'STARTS_WITH', 'type' => 'protected',       'roles' => []],
                ['url' => '/users',    'method' => 'ALL', 'match' => 'STARTS_WITH', 'type' => 'public',          'roles' => []],
                ['url' => '/comments', 'method' => 'PUT', 'match' => 'STARTS_WITH', 'type' => 'protected_roles', 'roles' => ['administrator']],
            ],
            $protectSettings->getRules()
        );
    }

    /**
     * Full round-trip: parse the admin POST, serialize it the way it is stored
     * in the DB option, then read it back through a fresh settings instance.
     * Guards against the view/settings POST-key drift that silently dropped rules.
     */
    public function testRulesArePersistedAndReloaded()
    {
        $writer = (new ProtectEndpointSettings())
            ->withSettings([])
            ->withPost([
                ProtectEndpointSettings::PROPERTY_GROUP => [
                    'enabled'        => '1',
                    'default_action' => ProtectEndpointSettings::DEFAULT_ALLOW_ALL,
                    'rules_url'      => ['/public/health', '/wp/v2/posts', '/wp/v2/users'],
                    'rules_method'   => ['GET', 'POST', 'ALL'],
                    'rules_match'    => [
                        ProtectEndpointSettings::ENDPOINT_MATCH_EXACT,
                        ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH,
                        ProtectEndpointSettings::ENDPOINT_MATCH_START_WITH,
                    ],
                    'rules_type'     => [
                        ProtectEndpointSettings::RULE_TYPE_PUBLIC,
                        ProtectEndpointSettings::RULE_TYPE_PROTECTED,
                        ProtectEndpointSettings::RULE_TYPE_PROTECTED_ROLES,
                    ],
                    'rules_roles'    => ['', '', 'administrator, editor'],
                ]
            ])
            ->withWordPressData($this->wordPressData);
        $writer->initSettingsFromPost();

        // Serialize exactly as SimpleJWTLoginSettings persists it into the DB option.
        $stored = $writer->getSettings();

        $reader = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings($stored);

        $expectedRules = [
            ['url' => '/public/health', 'method' => 'GET',  'match' => 'EXACT',       'type' => 'public',          'roles' => []],
            ['url' => '/wp/v2/posts',   'method' => 'POST', 'match' => 'STARTS_WITH', 'type' => 'protected',       'roles' => []],
            ['url' => '/wp/v2/users',   'method' => 'ALL',  'match' => 'STARTS_WITH', 'type' => 'protected_roles', 'roles' => ['administrator', 'editor']],
        ];

        $this->assertSame(true, $reader->isEnabled());
        $this->assertSame(ProtectEndpointSettings::DEFAULT_ALLOW_ALL, $reader->getDefaultAction());
        $this->assertSame($expectedRules, $reader->getRules());

        // The reloaded rules are what the admin UI renders in each card.
        $this->assertSame(
            [['url' => '/public/health', 'method' => 'GET', 'match' => 'EXACT', 'type' => 'public', 'roles' => []]],
            $reader->getWhitelistedDomains()
        );
        $this->assertSame(
            [
                ['url' => '/wp/v2/posts', 'method' => 'POST', 'match' => 'STARTS_WITH', 'type' => 'protected',       'roles' => []],
                ['url' => '/wp/v2/users', 'method' => 'ALL',  'match' => 'STARTS_WITH', 'type' => 'protected_roles', 'roles' => ['administrator', 'editor']],
            ],
            $reader->getProtectedEndpoints()
        );
    }

    public function testRolesAreParsedCorrectly()
    {
        $protectSettings = (new ProtectEndpointSettings())
            ->withSettings([])
            ->withPost([
                ProtectEndpointSettings::PROPERTY_GROUP => [
                    'enabled'        => '1',
                    'default_action' => ProtectEndpointSettings::DEFAULT_ALLOW_ALL,
                    'rules_url'      => ['/posts', '/users', '/comments'],
                    'rules_type'     => [
                        ProtectEndpointSettings::RULE_TYPE_PROTECTED_ROLES,
                        ProtectEndpointSettings::RULE_TYPE_PROTECTED,
                        ProtectEndpointSettings::RULE_TYPE_PROTECTED_ROLES,
                    ],
                    'rules_roles'    => ['administrator, editor', '', 'subscriber'],
                ]
            ])
            ->withWordPressData($this->wordPressData);
        $protectSettings->initSettingsFromPost();

        $this->assertSame(
            [
                ['url' => '/posts',    'method' => 'ALL', 'match' => 'STARTS_WITH', 'type' => 'protected_roles', 'roles' => ['administrator', 'editor']],
                ['url' => '/users',    'method' => 'ALL', 'match' => 'STARTS_WITH', 'type' => 'protected',       'roles' => []],
                ['url' => '/comments', 'method' => 'ALL', 'match' => 'STARTS_WITH', 'type' => 'protected_roles', 'roles' => ['subscriber']],
            ],
            $protectSettings->getRules()
        );
    }

    public function testNoErrorIsThrownWhenDisabled()
    {
        $protectSettings = (new ProtectEndpointSettings())
            ->withSettings([])
            ->withPost([
                ProtectEndpointSettings::PROPERTY_GROUP => [
                    'enabled'        => '0',
                    'default_action' => ProtectEndpointSettings::DEFAULT_ALLOW_ALL,
                ]
            ])
            ->withWordPressData($this->wordPressData);
        $protectSettings->initSettingsFromPost();
        $protectSettings->validateSettings();
        $this->assertFalse($protectSettings->isEnabled());
    }

    public function testNoExceptionThrownWhenDefaultIsProtectAll()
    {
        $protectSettings = (new ProtectEndpointSettings())
            ->withSettings([])
            ->withPost([
                ProtectEndpointSettings::PROPERTY_GROUP => [
                    'enabled'        => '1',
                    'default_action' => ProtectEndpointSettings::DEFAULT_PROTECT_ALL,
                    'rules_url'      => [],
                ]
            ])
            ->withWordPressData($this->wordPressData);
        $protectSettings->initSettingsFromPost();
        $this->expectNotToPerformAssertions();
        $protectSettings->validateSettings();
    }

    public function testGetDefaultValues()
    {
        $settings = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings([]);

        $this->assertSame(false, $settings->isEnabled());
        $this->assertSame(ProtectEndpointSettings::DEFAULT_ALLOW_ALL, $settings->getDefaultAction());
        $this->assertSame([], $settings->getRules());
        $this->assertSame([], $settings->getWhitelistedDomains());
        $this->assertSame([], $settings->getProtectedEndpoints());
    }

    public function testValidateWhenNotEnabled()
    {
        $settings = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings([
                'protect_endpoint' => [
                    'enabled' => false
                ]
            ]);
        $this->assertTrue($settings->validateSettings());
    }

    #[DataProvider('endpointsProvider')]
    /**
     * @param mixed $ruleLists
     * @throws Exception
     */
    public function testNoEndpointProvided($ruleLists)
    {
        $settings = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings([
                'protect_endpoint' => [
                    'enabled'        => true,
                    'default_action' => ProtectEndpointSettings::DEFAULT_ALLOW_ALL,
                    'rules_url'      => $ruleLists,
                    'rules_type'     => array_fill(0, count($ruleLists), ProtectEndpointSettings::RULE_TYPE_PROTECTED),
                ]
            ]);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You need to add at least one endpoint.');
        $settings->validateSettings();
    }

    public static function endpointsProvider()
    {
        return [
            'empty_array' => [
                'ruleLists' => ['']
            ],
            'array_with_empty_values' => [
                'ruleLists' => ['', '', '']
            ],
            'array_with_space' => [
                'ruleLists' => ['    ', '    ']
            ],
        ];
    }

    public function testValidationFailsWhenProtectedRolesRuleHasNoRoles()
    {
        $settings = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings([
                'protect_endpoint' => [
                    'enabled'        => true,
                    'default_action' => ProtectEndpointSettings::DEFAULT_PROTECT_ALL,
                    'rules_url'      => ['/wp/v2/posts'],
                    'rules_type'     => [ProtectEndpointSettings::RULE_TYPE_PROTECTED_ROLES],
                    'rules_roles'    => [''],
                ]
            ]);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('A "JWT + Roles" rule must have at least one role specified.');
        $settings->validateSettings();
    }

    public function testValidationPassesWhenProtectedRolesRuleHasRoles()
    {
        $settings = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings([
                'protect_endpoint' => [
                    'enabled'        => true,
                    'default_action' => ProtectEndpointSettings::DEFAULT_PROTECT_ALL,
                    'rules_url'      => ['/wp/v2/posts'],
                    'rules_type'     => [ProtectEndpointSettings::RULE_TYPE_PROTECTED_ROLES],
                    'rules_roles'    => ['administrator'],
                ]
            ]);
        $this->expectNotToPerformAssertions();
        $settings->validateSettings();
    }

    public function testValidationFailsWhenRoleDoesNotExistInWordPress()
    {
        $settings = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings([
                'protect_endpoint' => [
                    'enabled'        => true,
                    'default_action' => ProtectEndpointSettings::DEFAULT_PROTECT_ALL,
                    'rules_url'      => ['/wp/v2/posts'],
                    'rules_type'     => [ProtectEndpointSettings::RULE_TYPE_PROTECTED_ROLES],
                    'rules_roles'    => ['nonexistent_role'],
                ]
            ]);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Role "nonexistent_role" does not exist in WordPress.');
        $settings->validateSettings();
    }

    public function testOldFormatIsAutoMigratedOnRead()
    {
        $settings = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings([
                'protect_endpoint' => [
                    'enabled'          => true,
                    'action'           => ProtectEndpointSettings::ALL_ENDPOINTS,
                    'protect'          => ['/wp/v2/posts', '/wp/v2/users'],
                    'protect_method'   => ['GET', 'ALL'],
                    'protect_roles'    => ['administrator', ''],
                    'whitelist'        => ['/wp/v2/public'],
                    'whitelist_method' => ['ALL'],
                ]
            ]);

        $this->assertSame(ProtectEndpointSettings::DEFAULT_PROTECT_ALL, $settings->getDefaultAction());
        $this->assertSame(
            [
                ['url' => '/wp/v2/posts',  'method' => 'GET', 'match' => 'STARTS_WITH', 'type' => 'protected_roles', 'roles' => ['administrator']],
                ['url' => '/wp/v2/users',  'method' => 'ALL', 'match' => 'STARTS_WITH', 'type' => 'protected',       'roles' => []],
                ['url' => '/wp/v2/public', 'method' => 'ALL', 'match' => 'STARTS_WITH', 'type' => 'public',          'roles' => []],
            ],
            $settings->getRules()
        );
    }

    public function testGetActionLegacyAlias()
    {
        $settingsProtectAll = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings(['protect_endpoint' => ['default_action' => ProtectEndpointSettings::DEFAULT_PROTECT_ALL]]);

        $settingsAllowAll = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings(['protect_endpoint' => ['default_action' => ProtectEndpointSettings::DEFAULT_ALLOW_ALL]]);

        $this->assertSame(ProtectEndpointSettings::ALL_ENDPOINTS, $settingsProtectAll->getAction());
        $this->assertSame(ProtectEndpointSettings::SPECIFIC_ENDPOINTS, $settingsAllowAll->getAction());
    }

    public function testGetWhitelistedDomainsFiltersCorrectly()
    {
        $settings = (new ProtectEndpointSettings())
            ->withPost([])
            ->withWordPressData($this->wordPressData)
            ->withSettings([
                'protect_endpoint' => [
                    'enabled'        => true,
                    'default_action' => ProtectEndpointSettings::DEFAULT_PROTECT_ALL,
                    'rules_url'      => ['/public', '/protected'],
                    'rules_type'     => [ProtectEndpointSettings::RULE_TYPE_PUBLIC, ProtectEndpointSettings::RULE_TYPE_PROTECTED],
                ]
            ]);

        $this->assertSame(
            [['url' => '/public', 'method' => 'ALL', 'match' => 'STARTS_WITH', 'type' => 'public', 'roles' => []]],
            $settings->getWhitelistedDomains()
        );
        $this->assertSame(
            [['url' => '/protected', 'method' => 'ALL', 'match' => 'STARTS_WITH', 'type' => 'protected', 'roles' => []]],
            $settings->getProtectedEndpoints()
        );
    }
}
