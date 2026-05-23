<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings\Migrations;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\Migrations\V1ToV2Migration;

class V1ToV2MigrationTest extends TestCase
{
    private function make(): V1ToV2Migration
    {
        return new V1ToV2Migration();
    }

    public function testVersionNumbers(): void
    {
        $migration = $this->make();
        $this->assertSame(1, $migration->getSourceVersion());
        $this->assertSame(2, $migration->getTargetVersion());
    }

    public function testEmptyInputProducesVersionedSections(): void
    {
        $result = $this->make()->migrate([]);
        $this->assertSame(2, $result['_schema_version']);
        $this->assertArrayHasKey('login', $result);
        $this->assertArrayHasKey('register', $result);
        $this->assertArrayHasKey('authorization', $result);
        $this->assertArrayHasKey('cors', $result);
        $this->assertArrayHasKey('delete_user', $result);
        $this->assertArrayHasKey('hooks', $result);
        $this->assertArrayHasKey('reset_password', $result);
        $this->assertArrayHasKey('protect_endpoint', $result);
        $this->assertArrayHasKey('auth_codes', $result);
        $this->assertArrayHasKey('integrations', $result);
        $this->assertArrayHasKey('audit_log', $result);
        $this->assertArrayHasKey('jwt_rules', $result);
        $this->assertArrayHasKey('webhooks', $result);
        $this->assertArrayHasKey('api_keys', $result);
        $this->assertArrayHasKey('general', $result);
    }

    public function testLoginKeysMapped(): void
    {
        $v1Settings = [
            'allow_autologin'                  => '1',
            'require_login_auth'               => '1',
            'jwt_login_by'                     => 0,
            'jwt_login_by_parameter'           => 'email',
            'login_fail_redirect'              => '1',
            'include_login_request_parameters' => '1',
            'allow_usage_redirect_parameter'   => '1',
            'login_remove_request_parameters'  => 'jwt,test',
            'login_ip'                         => '127.0.0.1',
            'login_iss'                        => 'https://example.com',
        ];

        $result = $this->make()->migrate($v1Settings);
        $login  = $result['login'];

        $this->assertSame('1', $login['enabled']);
        $this->assertSame('1', $login['auth_code']);
        $this->assertSame(0, $login['login_by']);
        $this->assertSame('email', $login['login_by_parameter']);
        $this->assertSame('1', $login['fail_redirect']);
        $this->assertSame('1', $login['include_request_parameters']);
        $this->assertSame('1', $login['allow_redirect_parameter']);
        $this->assertSame('jwt,test', $login['remove_request_parameters']);
        $this->assertSame('127.0.0.1', $login['ip_whitelist']);
        $this->assertSame('https://example.com', $login['iss_whitelist']);
    }

    public function testLegacyJwtEmailParameterFallback(): void
    {
        $v1Settings = ['jwt_email_parameter' => 'legacy_email'];
        $result = $this->make()->migrate($v1Settings);
        $this->assertSame('legacy_email', $result['login']['login_by_parameter']);
    }

    public function testLegacyEmailParameterNotOverridesExplicitValue(): void
    {
        $v1Settings = [
            'jwt_login_by_parameter' => 'explicit',
            'jwt_email_parameter'    => 'should_be_ignored',
        ];
        $result = $this->make()->migrate($v1Settings);
        $this->assertSame('explicit', $result['login']['login_by_parameter']);
    }

    public function testAuthorizationKeysMapped(): void
    {
        $v1Settings = [
            'allow_authentication'        => '1',
            'auth_requires_auth_code'     => '1',
            'auth_ip'                     => '10.0.0.1',
            'auth_password_base64'        => true,
            'jwt_auth_ttl'                => 60,
            'jwt_auth_refresh_ttl'        => 20160,
            'jwt_auth_iss'                => 'https://site.test',
            'allow_refresh_token'         => '1',
            'refresh_token_key'           => 'secret',
            'allow_validate_token'        => '1',
            'allow_revoke_token'          => '1',
            'refresh_requires_auth_code'  => '1',
            'validate_requires_auth_code' => '1',
            'revoke_requires_auth_code'   => '1',
        ];

        $result = $this->make()->migrate($v1Settings);
        $auth   = $result['authorization'];

        $this->assertSame('1', $auth['enabled']);
        $this->assertSame('1', $auth['auth_code']);
        $this->assertSame('10.0.0.1', $auth['ip_whitelist']);
        $this->assertTrue($auth['password_base64']);
        $this->assertSame(60, $auth['ttl']);
        $this->assertSame(20160, $auth['refresh_ttl']);
        $this->assertSame('https://site.test', $auth['iss']);
        $this->assertSame('1', $auth['refresh_token_enabled']);
        $this->assertSame('secret', $auth['refresh_token_key']);
        $this->assertSame('1', $auth['validate_token_enabled']);
        $this->assertSame('1', $auth['revoke_token_enabled']);
        $this->assertSame('1', $auth['refresh_auth_code']);
        $this->assertSame('1', $auth['validate_auth_code']);
        $this->assertSame('1', $auth['revoke_auth_code']);
    }

    public function testAuthCodesKeysMapped(): void
    {
        $codes = [['code' => 'abc', 'role' => 'subscriber', 'expiration_date' => '']];
        $v1Settings = ['auth_codes' => $codes, 'auth_code_key' => 'AUTH_KEY'];
        $result = $this->make()->migrate($v1Settings);

        $this->assertSame($codes, $result['auth_codes']['codes']);
        $this->assertSame('AUTH_KEY', $result['auth_codes']['key']);
    }

    public function testJwtRulesWrappedUnderRulesKey(): void
    {
        $rules = [['iss' => 'app', 'algorithm' => 'HS256', 'decryption_key' => 'secret']];
        $v1Settings = ['jwt_rules' => $rules];
        $result = $this->make()->migrate($v1Settings);

        $this->assertSame($rules, $result['jwt_rules']['rules']);
    }

    public function testWebhooksItemsWrapped(): void
    {
        $items = [['url' => 'https://hook.test', 'enabled' => true, 'events' => ['login']]];
        $v1Settings = [
            'webhooks'                    => $items,
            'webhook_logs_enabled'        => true,
            'webhook_logs_retention_days' => 30,
        ];
        $result = $this->make()->migrate($v1Settings);

        $this->assertTrue($result['webhooks']['enabled']);
        $this->assertSame($items, $result['webhooks']['items']);
        $this->assertTrue($result['webhooks']['logs']['enabled']);
        $this->assertSame(30, $result['webhooks']['logs']['retention']);
    }

    public function testWebhooksDefaultsWhenMissing(): void
    {
        $result = $this->make()->migrate([]);
        $this->assertTrue($result['webhooks']['enabled']);
        $this->assertSame([], $result['webhooks']['items']);
        $this->assertTrue($result['webhooks']['logs']['enabled']);
        $this->assertSame(90, $result['webhooks']['logs']['retention']);
    }

    public function testCorsPreservesNestedStructure(): void
    {
        $cors = ['enabled' => true, 'allow_origin' => 'https://example.com'];
        $v1Settings = ['cors' => $cors];
        $result = $this->make()->migrate($v1Settings);
        $this->assertSame($cors, $result['cors']);
    }

    public function testProtectEndpointMigratedFromPluralKey(): void
    {
        $protectEndpoint = ['enabled' => true, 'action' => 1, 'protect' => ['test']];
        $v1Settings = ['protect_endpoints' => $protectEndpoint];
        $result = $this->make()->migrate($v1Settings);
        $this->assertSame($protectEndpoint, $result['protect_endpoint']);
    }

    public function testAuditLogPreservesNestedStructure(): void
    {
        $auditLog = ['enabled' => true, 'enabled_events' => ['login'], 'retention_days' => 30];
        $v1Settings = ['audit_log' => $auditLog];
        $result = $this->make()->migrate($v1Settings);
        $this->assertSame($auditLog, $result['audit_log']);
    }

    public function testDeleteUserKeysMapped(): void
    {
        $v1Settings = [
            'allow_delete'        => '1',
            'require_delete_auth' => '1',
            'delete_ip'           => '192.168.0.1',
        ];
        $result = $this->make()->migrate($v1Settings);
        $this->assertSame('1', $result['delete_user']['enabled']);
        $this->assertSame('1', $result['delete_user']['auth_code']);
        $this->assertSame('192.168.0.1', $result['delete_user']['ip_whitelist']);
    }

    public function testNonArrayInputTreatedAsEmpty(): void
    {
        $result = $this->make()->migrate(null);
        $this->assertSame(2, $result['_schema_version']);
        $this->assertSame([], $result['login']);
    }

    public function testApplicationsOauthProvidersNestedUnderOauthKey(): void
    {
        $v1Settings = [
            'google' => ['enabled' => 1, 'client_id' => 'gid', 'client_secret' => 'gsecret'],
            'auth0'  => ['enabled' => 0, 'domain' => 'example.auth0.com'],
        ];

        $result = $this->make()->migrate($v1Settings);

        $this->assertArrayHasKey('oauth', $result['integrations']);
        $this->assertSame(
            ['enabled' => 1, 'client_id' => 'gid', 'client_secret' => 'gsecret'],
            $result['integrations']['oauth']['google']
        );
        $this->assertSame(
            ['enabled' => 0, 'domain' => 'example.auth0.com'],
            $result['integrations']['oauth']['auth0']
        );
    }

    public function testApplicationsProvidersNotStoredFlat(): void
    {
        $v1Settings = ['google' => ['enabled' => 1]];
        $result = $this->make()->migrate($v1Settings);

        $this->assertArrayNotHasKey('google', $result['integrations']);
        $this->assertArrayNotHasKey('auth0', $result['integrations']);
    }

    public function testWpGraphqlMovedToApplicationsThirdParty(): void
    {
        $v1Settings = ['wp_graphql' => ['enabled' => true]];
        $result = $this->make()->migrate($v1Settings);

        $this->assertSame(['enabled' => true], $result['integrations']['3rdparty']['wpgraphql']);
        $this->assertArrayNotHasKey('wp_graphql', $result['general']);
    }

    public function testMissingWpGraphqlProducesEmptyThirdParty(): void
    {
        $result = $this->make()->migrate([]);
        $this->assertSame([], $result['integrations']['3rdparty']);
    }

    public function testEmptyApplicationsHaveOauthAndThirdPartyKeys(): void
    {
        $result = $this->make()->migrate([]);
        $this->assertArrayHasKey('oauth', $result['integrations']);
        $this->assertArrayHasKey('3rdparty', $result['integrations']);
        $this->assertSame([], $result['integrations']['oauth']);
        $this->assertSame([], $result['integrations']['3rdparty']);
    }
}
