<?php

namespace SimpleJwtLoginTests\Unit\Modules\Abilities;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Abilities\AbilitySettingsPolicy;
use SimpleJWTLogin\Modules\Abilities\SettingsPatcher;
use SimpleJWTLogin\Modules\Settings\ThirdParty\AiAbilitiesSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class SettingsPatcherTest extends TestCase
{
    /**
     * @param array $aiSettings Stored AI Abilities settings (permission toggles).
     * @return SettingsPatcher
     */
    private function makePatcher(array $aiSettings = [])
    {
        $wordPressData = $this->createStub(WordPressDataInterface::class);
        $wordPressData->method('sanitizeTextField')->willReturnCallback(function ($value) {
            return trim(strip_tags((string) $value));
        });
        $wordPressData->method('ksesPost')->willReturnCallback(function ($value) {
            return str_replace(['<script>', '</script>'], '', (string) $value);
        });
        $wordPressData->method('getRoleCapabilities')->willReturnCallback(function ($role) {
            $roles = [
                'administrator' => ['manage_options' => true],
                'site_boss'     => ['read' => true, 'manage_options' => true],
                'user_manager'  => ['read' => true, 'promote_users' => true],
                'editor'        => ['read' => true, 'edit_posts' => true, 'unfiltered_html' => true],
                'author'        => ['read' => true, 'edit_posts' => true],
            ];

            return isset($roles[$role]) ? $roles[$role] : [];
        });

        $policy = (new AbilitySettingsPolicy())->withPermissions(
            (new AiAbilitiesSettings())->withSettings($aiSettings)
        );

        return new SettingsPatcher($policy, $wordPressData);
    }

    /**
     * @return array
     */
    private function settingsFixture()
    {
        return [
            '_schema_version' => 2,
            'general' => [
                'decryption_key'  => 'secret',
                'jwt_algorithm'   => 'HS256',
                'route_namespace' => 'simple-jwt-login/v1/',
                'request_jwt_url' => 1,
                'security'        => ['safe_redirect' => false],
                'api_middleware'  => ['enabled' => false],
            ],
            'register' => [
                'allowed_user_meta' => '',
            ],
            'login' => [
                'enabled'             => false,
                'login_by_parameter'  => 'email',
                'redirect'            => 0,
            ],
            'authorization' => [
                'enabled'       => '1',
                'jwt_payload'   => ['email', 'exp'],
                'custom_claims' => ['payload' => [], 'header' => []],
            ],
            'protect_endpoint' => [
                'enabled'      => 0,
                'rules_url'    => ['/wp/v2/users'],
                'rules_method' => ['ALL'],
                'rules_match'  => ['STARTS_WITH'],
                'rules_type'   => ['protected'],
                'rules_roles'  => [''],
            ],
            'integrations' => [
                'oauth' => [
                    'google' => ['client_id' => '', 'client_secret' => 'gsecret'],
                ],
                '3rdparty' => [
                    'wpgraphql'    => ['enabled' => false],
                    'ai_abilities' => ['enabled' => true, 'access' => 'read_write'],
                ],
            ],
            'auth_codes' => ['codes' => [
                ['code' => 'c0de-one', 'role' => 'subscriber', 'expiration_date' => ''],
                ['code' => 'c0de-two', 'role' => 'editor', 'expiration_date' => '2030-01-01 00:00:00'],
            ], 'key' => 'AUTH_KEY'],
            'jwt_rules' => ['rules' => [
                [
                    'condition_type' => 'iss', 'condition_key' => 'iss', 'condition_operator' => 'equals',
                    'condition_value' => 'https://old.example.com', 'algorithm' => 'HS256', 'login_by' => 0,
                    'login_by_parameter' => 'email', 'iss' => 'https://old.example.com',
                    'decryption_key' => 'rule-secret', 'decryption_key_base64' => false,
                ],
            ]],
            'reset_password' => ['email_subject' => 'Reset', 'email_body' => base64_encode('<p>Code: {{CODE}}</p>')],
            'webhooks' => ['enabled' => false, 'items' => [['url' => 'https://a.b']]],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: array, 2: string[], 3: mixed}>
     */
    public static function validPatchProvider()
    {
        return [
            'bool from bool'               => ['login', ['enabled' => true], ['login', 'enabled'], true],
            'bool from string one'         => ['login', ['enabled' => '1'], ['login', 'enabled'], true],
            'bool from string false'       => ['login', ['enabled' => 'false'], ['login', 'enabled'], false],
            'int from numeric string'      => ['login', ['redirect' => '2'], ['login', 'redirect'], 2],
            'int from bool'                => ['general', ['request_jwt_url' => false], ['general', 'request_jwt_url'], 0],
            'string sanitized'             => ['login', ['login_by_parameter' => ' <b>id</b> '], ['login', 'login_by_parameter'], 'id'],
            'string flag from bool'        => ['authorization', ['enabled' => false], ['authorization', 'enabled'], '0'],
            'nested group'                 => ['general', ['api_middleware' => ['enabled' => true]], ['general', 'api_middleware', 'enabled'], true],
            'list replaced and sanitized'  => ['authorization', ['jwt_payload' => ['id', '<i>email</i>']], ['authorization', 'jwt_payload'], ['id', 'email']],
            'deep integrations path'       => ['integrations', ['oauth' => ['google' => ['client_id' => 'abc']]], ['integrations', 'oauth', 'google', 'client_id'], 'abc'],
            'other 3rd-party app writable' => ['integrations', ['3rdparty' => ['wpgraphql' => ['enabled' => true]]], ['integrations', '3rdparty', 'wpgraphql', 'enabled'], true],
            'jwt algorithm now writable'   => ['general', ['jwt_algorithm' => 'HS512'], ['general', 'jwt_algorithm'], 'HS512'],
            'auth code key now writable'   => ['auth_codes', ['key' => 'CODE'], ['auth_codes', 'key'], 'CODE'],
            'email body stored as base64'  => ['reset_password', ['email_body' => '<b>{{CODE}}</b><script>x</script>'], ['reset_password', 'email_body'], base64_encode('<b>{{CODE}}</b>x')],
            'auth code role edited'        => ['auth_codes', ['codes' => [['index' => 1, 'match' => ['role' => 'editor'], 'set' => ['role' => 'author']]]], ['auth_codes', 'codes', 1, 'role'], 'author'],
            'auth code secret kept'        => ['auth_codes', ['codes' => [['index' => 1, 'match' => ['role' => 'editor'], 'set' => ['role' => 'author']]]], ['auth_codes', 'codes', 1, 'code'], 'c0de-two'],
            'auth code removed'            => ['auth_codes', ['codes' => [['index' => 0, 'match' => ['role' => 'subscriber'], 'remove' => true]]], ['auth_codes', 'codes', 0, 'code'], 'c0de-two'],
            'jwt rule iss kept in sync'    => ['jwt_rules', ['rules' => [['index' => 0, 'match' => ['condition_value' => 'https://old.example.com'], 'set' => ['condition_value' => 'https://new.example.com']]]], ['jwt_rules', 'rules', 0, 'iss'], 'https://new.example.com'],
            'first custom claim added'     => ['authorization', ['custom_claims' => ['payload' => ['key' => ['tenant'], 'value' => ['<b>acme</b>']]]], ['authorization', 'custom_claims', 'payload'], ['key' => ['tenant'], 'value' => ['acme']]],
            'protect rule added in step'   => ['protect_endpoint', ['rules_url' => ['/wp/v2/users', '/wp/v2/posts'], 'rules_method' => ['ALL', 'GET'], 'rules_match' => ['STARTS_WITH', 'EXACT'], 'rules_type' => ['protected', 'protected_roles'], 'rules_roles' => ['', 'editor']], ['protect_endpoint', 'rules_roles'], ['', 'editor']],
            'jwt rule key kept'            => ['jwt_rules', ['rules' => [['index' => 0, 'match' => ['algorithm' => 'HS256'], 'set' => ['login_by' => '1']]]], ['jwt_rules', 'rules', 0, 'decryption_key'], 'rule-secret'],
        ];
    }

    #[DataProvider('validPatchProvider')]
    public function testApplyValidPatch($section, $values, $path, $expected)
    {
        $result = $this->makePatcher(['allow_change_roles' => true, 'allow_change_auth_providers' => true])->apply($this->settingsFixture(), $section, $values);

        $value = $result;
        foreach ($path as $segment) {
            $value = $value[$segment];
        }
        $this->assertSame($expected, $value);
    }

    public function testApplyKeepsUntouchedSettingsIdentical()
    {
        $original = $this->settingsFixture();
        $result   = $this->makePatcher()->apply($original, 'login', ['enabled' => true]);

        $expected = $original;
        $expected['login']['enabled'] = true;
        $this->assertSame($expected, $result);
        $this->assertSame($this->settingsFixture(), $original);
    }

    /**
     * @return array<string, array{0: mixed, 1: mixed, 2: int}>
     */
    public static function invalidPatchProvider()
    {
        return [
            'unknown section'              => ['nope', ['enabled' => true], ErrorCodes::ERR_ABILITY_UNKNOWN_SECTION],
            'non string section'           => [['login'], ['enabled' => true], ErrorCodes::ERR_ABILITY_UNKNOWN_SECTION],
            'empty values'                 => ['login', [], ErrorCodes::ERR_ABILITY_INVALID_VALUE],
            'values not an array'          => ['login', 'enabled', ErrorCodes::ERR_ABILITY_INVALID_VALUE],
            'unknown key'                  => ['login', ['made_up' => true], ErrorCodes::ERR_ABILITY_UNKNOWN_SETTING],
            'decryption key'               => ['general', ['decryption_key' => 'x'], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE],
            'ai abilities toggle'          => ['integrations', ['3rdparty' => ['ai_abilities' => ['access' => 'off']]], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE],
            'oauth client secret'          => ['integrations', ['oauth' => ['google' => ['client_secret' => 'x']]], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE],
            'auth code value'              => ['auth_codes', ['codes' => [['index' => 0, 'match' => ['role' => 'subscriber'], 'set' => ['code' => 'x']]]], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE],
            'match on a secret'            => ['auth_codes', ['codes' => [['index' => 0, 'match' => ['code' => 'c0de-one'], 'remove' => true]]], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE],
            'jwt rule secret'              => ['jwt_rules', ['rules' => [['index' => 0, 'match' => ['algorithm' => 'HS256'], 'set' => ['decryption_key' => 'x']]]], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE],
            'stale index'                  => ['auth_codes', ['codes' => [['index' => 0, 'match' => ['role' => 'editor'], 'remove' => true]]], ErrorCodes::ERR_ABILITY_LIST_ITEM_MISMATCH],
            'index out of range'           => ['auth_codes', ['codes' => [['index' => 5, 'match' => ['role' => 'editor'], 'remove' => true]]], ErrorCodes::ERR_ABILITY_LIST_ITEM_MISMATCH],
            'missing match'                => ['auth_codes', ['codes' => [['index' => 0, 'remove' => true]]], ErrorCodes::ERR_ABILITY_INVALID_VALUE],
            'neither set nor remove'       => ['auth_codes', ['codes' => [['index' => 0, 'match' => ['role' => 'subscriber']]]], ErrorCodes::ERR_ABILITY_INVALID_VALUE],
            'both set and remove'          => ['auth_codes', ['codes' => [['index' => 0, 'match' => ['role' => 'subscriber'], 'set' => ['role' => 'author'], 'remove' => true]]], ErrorCodes::ERR_ABILITY_INVALID_VALUE],
            'duplicate index'              => ['auth_codes', ['codes' => [['index' => 0, 'match' => ['role' => 'subscriber'], 'remove' => true], ['index' => 0, 'match' => ['role' => 'subscriber'], 'remove' => true]]], ErrorCodes::ERR_ABILITY_INVALID_VALUE],
            'adding an item'               => ['auth_codes', ['codes' => [['role' => 'author', 'expiration_date' => '']]], ErrorCodes::ERR_ABILITY_INVALID_VALUE],
            'unknown key in list item'     => ['auth_codes', ['codes' => [['index' => 0, 'match' => ['role' => 'subscriber'], 'set' => ['made_up' => 1]]]], ErrorCodes::ERR_ABILITY_UNKNOWN_SETTING],
            'parallel lists misaligned'    => ['protect_endpoint', ['rules_url' => ['/a', '/b']], ErrorCodes::ERR_ABILITY_INVALID_VALUE],
            'claims keys without values'   => ['authorization', ['custom_claims' => ['payload' => ['key' => ['a', 'b'], 'value' => ['x']]]], ErrorCodes::ERR_ABILITY_INVALID_VALUE],
            'secret inside new structure'  => ['authorization', ['custom_claims' => ['payload' => ['client_secret' => ['x']]]], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE],
            'email body not a string'      => ['reset_password', ['email_body' => ['x']], ErrorCodes::ERR_ABILITY_INVALID_VALUE],
            'webhook items'                => ['webhooks', ['items' => []], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE],
            'schema version as section'    => ['_schema_version', ['x' => 1], ErrorCodes::ERR_ABILITY_UNKNOWN_SECTION],
            'bool from garbage string'     => ['login', ['enabled' => 'maybe'], ErrorCodes::ERR_ABILITY_INVALID_VALUE],
            'int from non numeric'         => ['login', ['redirect' => 'abc'], ErrorCodes::ERR_ABILITY_INVALID_VALUE],
            'string from array'            => ['login', ['login_by_parameter' => ['x']], ErrorCodes::ERR_ABILITY_INVALID_VALUE],
            'group from scalar'            => ['general', ['api_middleware' => true], ErrorCodes::ERR_ABILITY_INVALID_VALUE],
            'security is human-only'       => ['general', ['security' => ['safe_redirect' => true]], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE],
            'allowed user meta needs role' => ['register', ['allowed_user_meta' => 'wp_capabilities'], ErrorCodes::ERR_ABILITY_PERMISSION_DENIED],
            'list from scalar'             => ['authorization', ['jwt_payload' => 'email'], ErrorCodes::ERR_ABILITY_INVALID_VALUE],
            'list with nested arrays'      => ['authorization', ['jwt_payload' => [['email']]], ErrorCodes::ERR_ABILITY_INVALID_VALUE],
        ];
    }

    #[DataProvider('invalidPatchProvider')]
    public function testApplyInvalidPatchThrows($section, $values, $expectedCode)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode($expectedCode);

        // Granted so these cases test validation, not the provider permission.
        $this->makePatcher(['allow_change_auth_providers' => true])->apply($this->settingsFixture(), $section, $values);
    }

    /**
     * @return array<string, array{0: string, 1: array}>
     */
    public static function rolePatchProvider()
    {
        return [
            'register default role' => ['register', ['new_user_profile' => 'editor']],
            'auth code role'        => ['auth_codes', ['codes' => [['index' => 0, 'match' => ['role' => 'subscriber'], 'set' => ['role' => 'editor']]]]],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: array}>
     */
    public static function privilegedRolePatchProvider()
    {
        $authCodeRole = function ($role) {
            return ['codes' => [['index' => 0, 'match' => ['role' => 'subscriber'], 'set' => ['role' => $role]]]];
        };

        return [
            'auth code administrator'        => ['auth_codes', $authCodeRole('administrator')],
            'auth code administrator casing' => ['auth_codes', $authCodeRole(' Administrator ')],
            'auth code custom admin role'    => ['auth_codes', $authCodeRole('site_boss')],
            'auth code user manager role'    => ['auth_codes', $authCodeRole('user_manager')],
            'register administrator'         => ['register', ['new_user_profile' => 'administrator']],
            'register custom admin role'     => ['register', ['new_user_profile' => 'site_boss']],
        ];
    }

    #[DataProvider('privilegedRolePatchProvider')]
    public function testPrivilegedRolesAreRefusedEvenWithChangeRolesPermission($section, $values)
    {
        $settings = $this->settingsFixture();
        $settings['register'] = ['new_user_profile' => 'subscriber'];

        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_ABILITY_PERMISSION_DENIED);
        $this->expectExceptionMessage('administrator capabilities');

        $this->makePatcher(['allow_change_roles' => true, 'allow_view_secrets' => true])
            ->apply($settings, $section, $values);
    }

    public function testProtectEndpointRulesDoNotNeedTheChangeRolesPermission()
    {
        $result = $this->makePatcher()->apply($this->settingsFixture(), 'protect_endpoint', [
            'rules_url'    => ['/wp/v2/users', '/wp/v2/posts'],
            'rules_method' => ['ALL', 'GET'],
            'rules_match'  => ['STARTS_WITH', 'EXACT'],
            'rules_type'   => ['protected', 'protected_roles'],
            'rules_roles'  => ['', 'editor'],
        ]);

        $this->assertSame(['', 'editor'], $result['protect_endpoint']['rules_roles']);
    }

    #[DataProvider('rolePatchProvider')]
    public function testRoleSettingsNeedTheChangeRolesPermission($section, $values)
    {
        $settings = $this->settingsFixture();
        $settings['register'] = ['new_user_profile' => 'subscriber'];

        try {
            $this->makePatcher()->apply($settings, $section, $values);
            $this->fail('Role change was accepted without the permission.');
        } catch (Exception $exception) {
            $this->assertSame(ErrorCodes::ERR_ABILITY_PERMISSION_DENIED, $exception->getCode());
            $this->assertStringContainsString('Allow changing roles', $exception->getMessage());
        }

        $result = $this->makePatcher(['allow_change_roles' => true])->apply($settings, $section, $values);
        $this->assertNotSame($settings[$section], $result[$section]);
    }

    /**
     * @return array<string, array{0: string, 1: array}>
     */
    public static function authProviderPatchProvider()
    {
        return [
            'auth0 domain'        => ['integrations', ['oauth' => ['auth0' => ['domain' => 'attacker.example.com']]]],
            'google client id'    => ['integrations', ['oauth' => ['google' => ['client_id' => 'attacker-client']]]],
            'jwt rule issuer'     => ['jwt_rules', ['rules' => [['index' => 0, 'match' => ['algorithm' => 'HS256'], 'set' => ['login_by_parameter' => 'id']]]]],
            'jwt rule removal'    => ['jwt_rules', ['rules' => [['index' => 0, 'match' => ['algorithm' => 'HS256'], 'remove' => true]]]],
            'login parameter'     => ['login', ['login_by_parameter' => 'id']],
            'login by'            => ['login', ['login_by' => 1]],
            'login issuers'       => ['login', ['iss_whitelist' => 'https://attacker.example.com']],
            'two factor disabled' => ['integrations', ['3rdparty' => ['two_factor' => ['enabled' => false]]]],
        ];
    }

    #[DataProvider('authProviderPatchProvider')]
    public function testAuthProviderSettingsNeedTheChangeAuthProvidersPermission($section, $values)
    {
        $settings = $this->settingsFixture();
        $settings['login'] += ['login_by' => 0, 'iss_whitelist' => ''];
        $settings['integrations']['oauth']['auth0'] = ['domain' => '', 'client_secret' => 'asecret'];
        $settings['integrations']['3rdparty']['two_factor'] = ['enabled' => true, 'interim_ttl' => 5];

        try {
            $this->makePatcher(['allow_change_roles' => true])->apply($settings, $section, $values);
            $this->fail('Authentication provider change was accepted without the permission.');
        } catch (Exception $exception) {
            $this->assertSame(ErrorCodes::ERR_ABILITY_PERMISSION_DENIED, $exception->getCode());
            $this->assertStringContainsString('Allow changing authentication providers', $exception->getMessage());
        }

        $result = $this->makePatcher(['allow_change_auth_providers' => true])->apply($settings, $section, $values);
        $this->assertNotSame($settings[$section], $result[$section]);
    }

    public function testOauthButtonLayoutDoesNotNeedTheChangeAuthProvidersPermission()
    {
        $settings = $this->settingsFixture();
        $settings['integrations']['oauth']['login_button_layout'] = 'stacked';

        $result = $this->makePatcher()->apply($settings, 'integrations', [
            'oauth' => ['login_button_layout' => 'inline'],
        ]);

        $this->assertSame('inline', $result['integrations']['oauth']['login_button_layout']);
    }

    public function testRemovingAnAuthCodeDoesNotNeedTheChangeRolesPermission()
    {
        $result = $this->makePatcher()->apply($this->settingsFixture(), 'auth_codes', [
            'codes' => [['index' => 0, 'match' => ['role' => 'subscriber'], 'remove' => true]],
        ]);

        $this->assertCount(1, $result['auth_codes']['codes']);
    }

    /**
     * An auth code stored without "code" is read back as the literal code
     * "Array" by AuthCodeBuilder, so items must never be added to an empty
     * secret-bearing list either.
     *
     * @return array<string, array{0: string, 1: string, 2: array}>
     */
    public static function emptySecretListProvider()
    {
        return [
            'auth code without code'     => ['auth_codes', 'codes', [['role' => 'administrator', 'expiration_date' => '']]],
            'auth code as operation'     => ['auth_codes', 'codes', [['index' => 0, 'match' => ['role' => 'x'], 'set' => ['role' => 'administrator']]]],
            'jwt rule without key'       => ['jwt_rules', 'rules', [['condition_type' => 'iss', 'algorithm' => 'HS256']]],
        ];
    }

    #[DataProvider('emptySecretListProvider')]
    public function testItemsCannotBeAddedToEmptySecretList($section, $listKey, $value)
    {
        $settings = $this->settingsFixture();
        $settings[$section][$listKey] = [];

        $this->expectException(Exception::class);

        $this->makePatcher()->apply($settings, $section, [$listKey => $value]);
    }
}
