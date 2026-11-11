<?php

namespace SimpleJwtLoginTests\Unit\Modules\Abilities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Abilities\AbilitySettingsPolicy;
use SimpleJWTLogin\Modules\Settings\ThirdParty\AiAbilitiesSettings;

class AbilitySettingsPolicyTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function secretPathProvider()
    {
        return [
            'general decryption key'         => ['general.decryption_key', true],
            'general public key'             => ['general.decryption_key_public', true],
            'general private key'            => ['general.decryption_key_private', true],
            'oauth client secret'            => ['integrations.oauth.google.client_secret', true],
            'refresh token key'              => ['authorization.refresh_token_key', true],
            'jwt rule nested decryption key' => ['jwt_rules.rules.0.decryption_key', true],
            'auth code value'                => ['auth_codes.codes.0.code', true],
            'auth code value at any index'   => ['auth_codes.codes.12.code', true],
            'auth code role'                 => ['auth_codes.codes.0.role', false],
            'code key outside auth codes'    => ['reset_password.code', false],
            'oauth client id'                => ['integrations.oauth.google.client_id', false],
            'request keys are not secrets'   => ['general.request_keys.header', false],
            'api key header name'            => ['api_keys.header_name', false],
            'password hash flag'             => ['authorization.password_hash_enabled', false],
        ];
    }

    #[DataProvider('secretPathProvider')]
    public function testIsSecret($path, $expected)
    {
        $this->assertSame($expected, (new AbilitySettingsPolicy())->isSecret($path));
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function writablePathProvider()
    {
        return [
            'login enabled'                 => ['login.enabled', true],
            'cors allow origin'             => ['cors.allow_origin', true],
            'oauth client id needs permission' => ['integrations.oauth.google.client_id', false],
            'webhooks enabled flag'         => ['webhooks.enabled', true],
            'webhook logs retention'        => ['webhooks.logs.retention', true],
            'decryption key'                => ['general.decryption_key', false],
            'oauth client secret'           => ['integrations.oauth.github.client_secret', false],
            'jwt algorithm'                 => ['general.jwt_algorithm', true],
            'decryption source'             => ['general.decryption_source', true],
            'decryption key base64 flag'    => ['general.decryption_key_base64', true],
            'auth codes section'            => ['auth_codes', true],
            'auth codes list'               => ['auth_codes.codes', true],
            'auth code role needs permission' => ['auth_codes.codes.0.role', false],
            'auth code expiration'          => ['auth_codes.codes.0.expiration_date', true],
            'auth code value'               => ['auth_codes.codes.0.code', false],
            'jwt rules need permission'     => ['jwt_rules.rules', false],
            'jwt rule key'                  => ['jwt_rules.rules.1.decryption_key_private', false],
            'email body'                    => ['reset_password.email_body', true],
            'ai abilities toggle'           => ['integrations.3rdparty.ai_abilities.access', false],
            'ai abilities group'            => ['integrations.3rdparty.ai_abilities', false],
            'webhook items'                 => ['webhooks.items', false],
            'schema version'                => ['_schema_version', false],
            'prefix must match full segment' => ['webhooks.items_extra', true],
            'security group'                => ['general.security', false],
            'trust ip headers'              => ['general.security.trust_ip_headers', false],
            'safe redirect'                 => ['general.security.safe_redirect', false],
            'allowed user meta needs permission' => ['register.allowed_user_meta', false],
        ];
    }

    #[DataProvider('writablePathProvider')]
    public function testIsWritable($path, $expected)
    {
        $this->assertSame($expected, (new AbilitySettingsPolicy())->isWritable($path));
    }

    /**
     * @return array<string, array{0: array, 1: string, 2: bool, 3: string}>
     */
    public static function permissionPathProvider()
    {
        return [
            'role denied by default'       => [[], 'register.new_user_profile', false, 'allow_change_roles'],
            'role granted'                 => [['allow_change_roles' => true], 'register.new_user_profile', true, ''],
            'auth code role granted'       => [['allow_change_roles' => true], 'auth_codes.codes.3.role', true, ''],
            'endpoint roles need nothing'  => [[], 'protect_endpoint.rules_roles', true, ''],
            'other permission not enough'  => [['allow_view_secrets' => true], 'auth_codes.codes.0.role', false, 'allow_change_roles'],
            'secret stays read-only'       => [['allow_change_roles' => true, 'allow_view_secrets' => true], 'general.decryption_key', false, ''],
            'plain setting needs nothing'  => [[], 'login.enabled', true, ''],
            'oauth provider denied'        => [[], 'integrations.oauth.auth0.domain', false, 'allow_change_auth_providers'],
            'oauth provider object denied' => [[], 'integrations.oauth.google', false, 'allow_change_auth_providers'],
            'oauth provider granted'       => [['allow_change_auth_providers' => true], 'integrations.oauth.auth0.domain', true, ''],
            'oauth layout needs nothing'   => [[], 'integrations.oauth.login_button_layout', true, ''],
            'oauth parent needs nothing'   => [[], 'integrations.oauth', true, ''],
            'oauth secret stays secret'    => [['allow_change_auth_providers' => true], 'integrations.oauth.google.client_secret', false, ''],
            'jwt rules section denied'     => [[], 'jwt_rules', false, 'allow_change_auth_providers'],
            'jwt rule issuer denied'       => [[], 'jwt_rules.rules.0.iss', false, 'allow_change_auth_providers'],
            'jwt rule issuer granted'      => [['allow_change_auth_providers' => true], 'jwt_rules.rules.0.iss', true, ''],
            'jwt rule key stays secret'    => [['allow_change_auth_providers' => true], 'jwt_rules.rules.0.decryption_key', false, ''],
            'login parameter denied'       => [[], 'login.login_by_parameter', false, 'allow_change_auth_providers'],
            'login by denied'              => [[], 'login.login_by', false, 'allow_change_auth_providers'],
            'login issuers denied'         => [[], 'login.iss_whitelist', false, 'allow_change_auth_providers'],
            'roles do not grant providers' => [['allow_change_roles' => true], 'login.login_by_parameter', false, 'allow_change_auth_providers'],
            'prefix lookalike not gated'   => [[], 'login.login_by_parameter_x', true, ''],
            'two factor denied'            => [[], 'integrations.3rdparty.two_factor.enabled', false, 'allow_change_auth_providers'],
            'two factor ttl denied'        => [[], 'integrations.3rdparty.two_factor.interim_ttl', false, 'allow_change_auth_providers'],
            'two factor granted'           => [['allow_change_auth_providers' => true], 'integrations.3rdparty.two_factor.enabled', true, ''],
            'other 3rd-party app free'     => [[], 'integrations.3rdparty.wpgraphql.enabled', true, ''],
            'user meta denied by default'  => [[], 'register.allowed_user_meta', false, 'allow_change_roles'],
            'user meta granted'            => [['allow_change_roles' => true], 'register.allowed_user_meta', true, ''],
            'security stays human-only'    => [['allow_change_roles' => true, 'allow_change_auth_providers' => true], 'general.security.trust_ip_headers', false, ''],
        ];
    }

    #[DataProvider('permissionPathProvider')]
    public function testPermissionsGateRolePaths($aiSettings, $path, $writable, $missing)
    {
        $policy = (new AbilitySettingsPolicy())->withPermissions((new AiAbilitiesSettings())->withSettings($aiSettings));

        $this->assertSame($writable, $policy->isWritable($path));
        $this->assertSame($missing, $policy->missingPermission($path));
    }

    public function testWithPermissionsDoesNotChangeTheOriginalPolicy()
    {
        $policy  = new AbilitySettingsPolicy();
        $granted = $policy->withPermissions((new AiAbilitiesSettings())->withSettings(['allow_view_secrets' => true]));

        $this->assertTrue($granted->canViewSecrets());
        $this->assertFalse($policy->canViewSecrets());
        $this->assertSame(array_fill_keys(AiAbilitiesSettings::$permissions, false), $policy->getPermissions());
    }

    public function testAiAccessLevelAndSchemaVersionStayHumanOnly()
    {
        $this->assertContains('integrations.3rdparty.ai_abilities', AbilitySettingsPolicy::$readOnlyPaths);
        $this->assertContains('_schema_version', AbilitySettingsPolicy::$readOnlyPaths);
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function base64PathProvider()
    {
        return [
            'email body'   => ['reset_password.email_body', true],
            'email subject' => ['reset_password.email_subject', false],
        ];
    }

    #[DataProvider('base64PathProvider')]
    public function testIsBase64($path, $expected)
    {
        $this->assertSame($expected, (new AbilitySettingsPolicy())->isBase64($path));
    }
}
