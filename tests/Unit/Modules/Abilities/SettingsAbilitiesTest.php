<?php

namespace SimpleJwtLoginTests\Unit\Modules\Abilities;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Abilities\AbilitySettingsPolicy;
use SimpleJWTLogin\Modules\Abilities\Settings\GetSettingsAbility;
use SimpleJWTLogin\Modules\Abilities\Settings\UpdateSettingsAbility;
use SimpleJWTLogin\Modules\Abilities\SettingsRedactor;
use SimpleJWTLogin\Modules\Settings\ResetPasswordSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

#[RunTestsInSeparateProcesses]
class SettingsAbilitiesTest extends AbilityTestCase
{
    public function testGetSettingsMasksSecretsAndHidesSchemaVersion()
    {
        $settings = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));

        $result = (new GetSettingsAbility($settings, new AbilitySettingsPolicy()))->execute([]);
        $all    = (array) $result['settings'];

        $this->assertArrayNotHasKey('_schema_version', $all);
        $this->assertSame(SettingsRedactor::MASK, $all['general']['decryption_key']);
        $this->assertSame('simple-jwt-login/v1/', $all['general']['route_namespace']);
        $this->assertContains('integrations.3rdparty.ai_abilities', $result['read_only_paths']);
        $this->assertNotContains('general.jwt_algorithm', $result['read_only_paths']);
        $this->assertContains('client_secret', $result['secret_keys']);
        $this->assertContains('auth_codes.codes.*.code', $result['secret_paths']);
    }

    public function testGetSettingsReturnsSingleSection()
    {
        $settings = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));

        $result = (new GetSettingsAbility($settings, new AbilitySettingsPolicy()))->execute(['section' => 'cors']);

        $this->assertSame(['cors'], array_keys((array) $result['settings']));
    }

    public function testGetSettingsRejectsUnknownSection()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_ABILITY_UNKNOWN_SECTION);

        $settings = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));
        (new GetSettingsAbility($settings, new AbilitySettingsPolicy()))->execute(['section' => 'nope']);
    }

    public function testGetSettingsDefinitionListsEverySection()
    {
        $settings   = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));
        $definition = (new GetSettingsAbility($settings, new AbilitySettingsPolicy()))->getDefinition();

        $sections = $definition['input_schema']['properties']['section']['enum'];
        $this->assertContains('general', $sections);
        $this->assertContains('webhooks', $sections);
        $this->assertTrue($definition['annotations']['readonly']);
    }

    public function testUpdateSettingsDefinitionExcludesReadOnlySections()
    {
        $settings   = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));
        $definition = (new UpdateSettingsAbility($settings, new AbilitySettingsPolicy()))->getDefinition();

        $sections = $definition['input_schema']['properties']['section']['enum'];
        $this->assertContains('login', $sections);
        $this->assertContains('auth_codes', $sections);
        $this->assertContains('jwt_rules', $sections);
        $this->assertTrue($definition['annotations']['destructive']);
    }

    public function testUpdateSettingsSavesOnlyTheChangedKey()
    {
        $stored        = $this->storedSettings();
        $wordPressData = $this->makeWordPressDataMock($stored);

        $expected = $stored;
        $expected['cors']['allow_origin'] = 'https://app.example.com';
        $wordPressData->expects($this->once())
            ->method('updateOption')
            ->with(SimpleJWTLoginSettings::OPTIONS_KEY, json_encode($expected))
            ->willReturn(true);

        $settings = $this->makeSettings($wordPressData);
        $result   = (new UpdateSettingsAbility($settings, new AbilitySettingsPolicy()))->execute([
            'section' => 'cors',
            'values'  => ['allow_origin' => 'https://app.example.com'],
        ]);

        $this->assertSame('cors', $result['section']);
        $this->assertSame(
            ['changed' => ['cors.allow_origin' => ['from' => '*', 'to' => 'https://app.example.com']]],
            (array) $result['diff']
        );
        $this->assertSame('https://app.example.com', ((array) $result['settings'])['allow_origin']);
    }

    public function testGetSettingsReportsPermissionsAllDeniedByDefault()
    {
        $settings = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));

        $result = (new GetSettingsAbility($settings, new AbilitySettingsPolicy()))->execute([]);

        $this->assertSame([
            'allow_view_secrets'    => false,
            'allow_change_roles'    => false,
            'allow_change_auth_providers' => false,
            'allow_create_api_keys' => false,
            'allow_add_webhooks'    => false,
        ], $result['ai_permissions']);
        $this->assertContains('register.new_user_profile', $result['read_only_paths']);
        $this->assertContains('jwt_rules', $result['read_only_paths']);
        $this->assertContains('login.login_by_parameter', $result['read_only_paths']);
        $this->assertContains('integrations.oauth.auth0', $result['read_only_paths']);
        $this->assertNotContains('integrations.oauth.login_button_layout', $result['read_only_paths']);
    }

    public function testGetSettingsShowsSecretsOnlyWithViewSecretsPermission()
    {
        $stored = $this->withAiPermissions($this->storedSettings(), ['allow_view_secrets' => true]);
        $settings = $this->makeSettings($this->makeWordPressDataStub($stored));

        $result = (new GetSettingsAbility($settings, new AbilitySettingsPolicy()))->execute([]);
        $all    = (array) $result['settings'];

        $this->assertSame($stored['general']['decryption_key'], $all['general']['decryption_key']);
        $this->assertTrue($result['ai_permissions']['allow_view_secrets']);
    }

    public function testRoleSettingsAreNotReadOnlyWithChangeRolesPermission()
    {
        $stored   = $this->withAiPermissions($this->storedSettings(), ['allow_change_roles' => true]);
        $settings = $this->makeSettings($this->makeWordPressDataStub($stored));

        $result = (new GetSettingsAbility($settings, new AbilitySettingsPolicy()))->execute([]);

        $this->assertNotContains('register.new_user_profile', $result['read_only_paths']);
        $this->assertSame(SettingsRedactor::MASK, ((array) $result['settings'])['general']['decryption_key']);
    }

    /**
     * @param array $stored
     * @param array $permissions
     * @return array
     */
    private function withAiPermissions(array $stored, array $permissions)
    {
        $stored['integrations']['3rdparty']['ai_abilities'] = array_merge(
            ['enabled' => true, 'access' => 'read_write'],
            $permissions
        );

        return $stored;
    }

    public function testUpdateSettingsDiffStaysMaskedEvenWithViewSecretsPermission()
    {
        $stored = $this->withAiPermissions($this->storedSettings(), ['allow_view_secrets' => true]);
        $stored['auth_codes']['codes'] = [['code' => 'TOP-SECRET-CODE', 'role' => 'subscriber', 'expiration_date' => '']];
        $wordPressData = $this->makeWordPressDataMock($stored);
        $wordPressData->expects($this->once())->method('updateOption')->willReturn(true);

        $result = (new UpdateSettingsAbility($this->makeSettings($wordPressData), new AbilitySettingsPolicy()))->execute([
            'section' => 'auth_codes',
            'values'  => ['codes' => [['index' => 0, 'match' => ['role' => 'subscriber'], 'remove' => true]]],
        ]);

        // The diff goes to the audit log, so it is always masked; the returned settings follow the permission.
        $this->assertStringNotContainsString('TOP-SECRET-CODE', (string) json_encode($result['diff']));
    }

    public function testUpdateAuthCodeRoleKeepsCodeAndNeverReturnsIt()
    {
        $stored = $this->withAiPermissions($this->storedSettings(), ['allow_change_roles' => true]);
        $stored['auth_codes']['codes'] = [
            ['code' => 'TOP-SECRET-CODE', 'role' => 'subscriber', 'expiration_date' => ''],
        ];
        $wordPressData = $this->makeWordPressDataMock($stored);

        $expected = $stored;
        $expected['auth_codes']['codes'][0]['role'] = 'editor';
        $wordPressData->expects($this->once())
            ->method('updateOption')
            ->with(SimpleJWTLoginSettings::OPTIONS_KEY, json_encode($expected))
            ->willReturn(true);

        $result = (new UpdateSettingsAbility($this->makeSettings($wordPressData), new AbilitySettingsPolicy()))->execute([
            'section' => 'auth_codes',
            'values'  => ['codes' => [['index' => 0, 'match' => ['role' => 'subscriber'], 'set' => ['role' => 'editor']]]],
        ]);

        $this->assertStringNotContainsString('TOP-SECRET-CODE', (string) json_encode($result));
        $this->assertNotEmpty((array) $result['diff']);
    }

    public function testUpdateEmailBodyIsStoredAsBase64AndReturnedAsPlainText()
    {
        $stored        = $this->storedSettings();
        $wordPressData = $this->makeWordPressDataMock($stored);

        $expected = $stored;
        $expected['reset_password']['email_body'] = base64_encode('<p>Code: {{CODE}}</p>');
        $wordPressData->expects($this->once())
            ->method('updateOption')
            ->with(SimpleJWTLoginSettings::OPTIONS_KEY, json_encode($expected))
            ->willReturn(true);

        $result = (new UpdateSettingsAbility($this->makeSettings($wordPressData), new AbilitySettingsPolicy()))->execute([
            'section' => 'reset_password',
            'values'  => ['email_body' => '<p>Code: {{CODE}}</p>'],
        ]);

        $this->assertSame('<p>Code: {{CODE}}</p>', ((array) $result['settings'])['email_body']);
        $this->assertArrayHasKey('reset_password.email_body', $result['diff']->changed);
    }

    public function testSwitchingAnRsJwtRuleToHsIsRejectedWithoutAKey()
    {
        $stored = $this->storedSettings();
        $stored['jwt_rules']['rules'] = [[
            'condition_type' => 'iss', 'condition_key' => 'iss', 'condition_operator' => 'equals',
            'condition_value' => 'https://idp.example.com', 'iss' => 'https://idp.example.com',
            'algorithm' => 'RS256', 'login_by' => 0, 'login_by_parameter' => 'email',
            'decryption_key_public' => base64_encode('PUBLIC-PEM'), 'decryption_key_private' => base64_encode('PRIVATE-PEM'),
        ]];
        $stored        = $this->withAiPermissions($stored, ['allow_change_auth_providers' => true]);
        $wordPressData = $this->makeWordPressDataMock($stored);
        $wordPressData->expects($this->never())->method('updateOption');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('decryption key is required');

        (new UpdateSettingsAbility($this->makeSettings($wordPressData), new AbilitySettingsPolicy()))->execute([
            'section' => 'jwt_rules',
            'values'  => ['rules' => [['index' => 0, 'match' => ['algorithm' => 'RS256'], 'set' => ['algorithm' => 'HS256']]]],
        ]);
    }

    /**
     * @return array<string, array{0: string, 1: array, 2: int|null, 3: string|null}>
     */
    public static function rejectedUpdateProvider()
    {
        return [
            'secret key' => [
                'general', ['decryption_key' => 'attacker'], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE, null,
            ],
            'ai access mode' => [
                'integrations', ['3rdparty' => ['ai_abilities' => ['access' => 'read_write']]], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE, null,
            ],
            'ai integration toggle' => [
                'integrations', ['3rdparty' => ['ai_abilities' => ['enabled' => false]]], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE, null,
            ],
            'ai grants itself view secrets' => [
                'integrations', ['3rdparty' => ['ai_abilities' => ['allow_view_secrets' => true]]], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE, null,
            ],
            'ai grants itself change roles' => [
                'integrations', ['3rdparty' => ['ai_abilities' => ['allow_change_roles' => true]]], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE, null,
            ],
            'ai grants itself api keys' => [
                'integrations', ['3rdparty' => ['ai_abilities' => ['allow_create_api_keys' => true]]], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE, null,
            ],
            'ai grants itself auth providers' => [
                'integrations', ['3rdparty' => ['ai_abilities' => ['allow_change_auth_providers' => true]]], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE, null,
            ],
            'ai grants itself webhooks' => [
                'integrations', ['3rdparty' => ['ai_abilities' => ['allow_add_webhooks' => true]]], ErrorCodes::ERR_ABILITY_SETTING_NOT_WRITABLE, null,
            ],
            'default role without permission' => [
                'register', ['new_user_profile' => 'administrator'], ErrorCodes::ERR_ABILITY_PERMISSION_DENIED, null,
            ],
            'unknown key' => [
                'login', ['made_up' => true], ErrorCodes::ERR_ABILITY_UNKNOWN_SETTING, null,
            ],
            'fails plugin validation' => [
                'general', ['route_namespace' => ''], null, 'Route namespace could not be empty.',
            ],
            'email body without code placeholder' => [
                'reset_password',
                ['enabled' => true, 'flow' => ResetPasswordSettings::FLOW_SEND_CUSTOM_EMAIL, 'email_subject' => 'Reset', 'email_body' => '<p>No placeholder</p>'],
                null,
                'You need to add the {{CODE}} variable in email body.',
            ],
            'switch to RS256 without keys' => [
                'general', ['jwt_algorithm' => 'RS256'], null, 'Public Key and Private Key are required.',
            ],
            'login enabled without parameter' => [
                'login', ['enabled' => true, 'login_by_parameter' => ''], null, 'JWT Payload key is missing.',
            ],
        ];
    }

    /**
     * @return array<string, array{0: array}>
     */
    public static function aiSelfEscalationProvider()
    {
        return [
            'nothing stored yet'   => [[]],
            'already stored'       => [['ai_abilities' => ['enabled' => true, 'access' => 'read']]],
            'other app stored only' => [['wpgraphql' => ['enabled' => false]]],
        ];
    }

    #[DataProvider('aiSelfEscalationProvider')]
    public function testAiCannotEnableOrRaiseItsOwnAccess($storedThirdParty)
    {
        $stored = $this->storedSettings();
        $stored['integrations']['3rdparty'] = $storedThirdParty;
        $wordPressData = $this->makeWordPressDataMock($stored);
        $wordPressData->expects($this->never())->method('updateOption');

        $this->expectException(Exception::class);

        (new UpdateSettingsAbility($this->makeSettings($wordPressData), new AbilitySettingsPolicy()))->execute([
            'section' => 'integrations',
            'values'  => ['3rdparty' => ['ai_abilities' => ['enabled' => true, 'access' => 'read_write']]],
        ]);
    }

    #[DataProvider('rejectedUpdateProvider')]
    public function testUpdateSettingsRejectsAndSavesNothing($section, $values, $expectedCode, $expectedMessage)
    {
        // Granted so these cases test validation, not the provider permission.
        $stored        = $this->withAiPermissions($this->storedSettings(), ['allow_change_auth_providers' => true]);
        $wordPressData = $this->makeWordPressDataMock($stored);
        $wordPressData->expects($this->never())->method('updateOption');
        $wordPressData->expects($this->never())->method('addOption');

        $this->expectException(Exception::class);
        if ($expectedCode !== null) {
            $this->expectExceptionCode($expectedCode);
        }
        if ($expectedMessage !== null) {
            $this->expectExceptionMessage($expectedMessage);
        }

        $settings = $this->makeSettings($wordPressData);
        (new UpdateSettingsAbility($settings, new AbilitySettingsPolicy()))->execute([
            'section' => $section,
            'values'  => $values,
        ]);
    }
}
