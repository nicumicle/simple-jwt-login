<?php

namespace SimpleJwtLoginTests\Unit\Modules\Abilities;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use SimpleJWTLogin\Modules\Abilities\AbilitiesRegistrar;
use SimpleJWTLogin\Modules\Abilities\AbilityCatalog;
use SimpleJWTLogin\Modules\Abilities\AbilityInterface;
use SimpleJWTLogin\Modules\Abilities\AuditableAbilityInterface;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\Settings\AuditLogSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Repositories\AuditLog\Repository as AuditLogRepositoryInterface;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\WebhookLog\Repository as WebhookLogRepositoryInterface;
use SimpleJWTLogin\Services\AuditLoggerService;
use WP_Error;

#[RunTestsInSeparateProcesses]
class AbilitiesRegistrarTest extends AbilityTestCase
{
    const READ_ABILITIES = [
        'simple-jwt-login/get-settings',
        'simple-jwt-login/list-api-keys',
        'simple-jwt-login/get-audit-log',
        'simple-jwt-login/analyze-audit-log',
        'simple-jwt-login/diagnose',
        'simple-jwt-login/security-report',
        'simple-jwt-login/explain-error',
        'simple-jwt-login/inspect-jwt',
        'simple-jwt-login/list-webhooks',
        'simple-jwt-login/get-webhook-logs',
    ];

    /**
     * Write abilities available without any extra AI permission.
     */
    const WRITE_ABILITIES = [
        'simple-jwt-login/update-settings',
        'simple-jwt-login/revoke-api-key',
        'simple-jwt-login/remove-webhook',
    ];

    const ALL_PERMISSIONS = [
        'allow_view_secrets'    => true,
        'allow_change_roles'    => true,
        'allow_change_auth_providers' => true,
        'allow_create_api_keys' => true,
        'allow_add_webhooks'    => true,
    ];

    /**
     * @param string $access
     * @param array  $permissions
     * @return array
     */
    private function settingsFor($access, array $permissions = [])
    {
        return $this->storedSettings(['integrations' => ['3rdparty' => ['ai_abilities' => array_merge(
            ['enabled' => true, 'access' => $access],
            $permissions
        )]]]);
    }

    /**
     * @param string                  $access
     * @param mixed                   $wordPressData Stub or mock; a stub is created when null.
     * @param AuditLoggerService|null $auditLogger   Stub or mock; a stub is created when null.
     * @return array{0: AbilitiesRegistrar, 1: mixed, 2: mixed}
     */
    private function makeRegistrar($access, $wordPressData = null, $auditLogger = null, array $permissions = [])
    {
        if ($wordPressData === null) {
            $wordPressData = $this->makeWordPressDataStub($this->settingsFor($access, $permissions));
        }
        if ($auditLogger === null) {
            $auditLogger = $this->createStub(AuditLoggerService::class);
        }
        $auditLogger->method('withSettings')->willReturnSelf();
        $settings = $this->makeSettings($wordPressData);
        $catalog  = new AbilityCatalog(
            $settings,
            $this->createStub(ApiKeyRepositoryInterface::class),
            $this->createStub(AuditLogRepositoryInterface::class),
            $this->createStub(WebhookLogRepositoryInterface::class),
            $this->createStub(RevokedTokenRepositoryInterface::class)
        );

        return [new AbilitiesRegistrar($settings, $catalog, $auditLogger), $wordPressData, $auditLogger];
    }

    /**
     * @return array<string, array{0: string, 1: string[]}>
     */
    public static function accessModeProvider()
    {
        $gated = [
            'simple-jwt-login/create-api-key',
            'simple-jwt-login/add-webhook',
        ];

        return [
            'off registers nothing'      => ['off', [], []],
            'read registers reads'       => ['read', [], self::READ_ABILITIES],
            'write registers writes'     => ['write', [], self::WRITE_ABILITIES],
            'read write registers all'   => ['read_write', [], array_merge(self::READ_ABILITIES, self::WRITE_ABILITIES)],
            'unknown mode registers none' => ['admin', [], []],
            'permissions add gated writes' => ['write', self::ALL_PERMISSIONS, array_merge(self::WRITE_ABILITIES, $gated)],
            'only api keys permission'   => ['write', ['allow_create_api_keys' => true], array_merge(self::WRITE_ABILITIES, ['simple-jwt-login/create-api-key'])],
            'permissions need write access' => ['read', self::ALL_PERMISSIONS, self::READ_ABILITIES],
            'permissions alone register nothing' => ['off', self::ALL_PERMISSIONS, []],
        ];
    }

    #[DataProvider('accessModeProvider')]
    public function testRegisterAbilitiesHonoursAccessMode($access, $permissions, $expectedNames)
    {
        list($registrar, $wordPressData) = $this->makeRegistrar($access, null, null, $permissions);

        $registered = [];
        $wordPressData->method('registerAbility')->willReturnCallback(
            function ($name, $args) use (&$registered) {
                $registered[$name] = $args;
                return null;
            }
        );

        $registrar->registerAbilities();

        $names = array_keys($registered);
        sort($names);
        sort($expectedNames);
        $this->assertSame($expectedNames, $names);
        foreach ($registered as $args) {
            $this->assertSame(AbilitiesRegistrar::CATEGORY, $args['category']);
            $this->assertTrue($args['meta']['show_in_rest']);
            $this->assertTrue($args['meta']['mcp']['public']);
            $this->assertIsCallable($args['permission_callback']);
            $this->assertIsCallable($args['execute_callback']);
        }
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function categoryProvider()
    {
        return [
            'off'        => ['off', 0],
            'read'       => ['read', 1],
            'write'      => ['write', 1],
            'read write' => ['read_write', 1],
        ];
    }

    #[DataProvider('categoryProvider')]
    public function testRegisterCategoryOnlyWhenAccessAllowed($access, $expectedCalls)
    {
        list($registrar, $wordPressData) = $this->makeRegistrar(
            $access,
            $this->makeWordPressDataMock($this->settingsFor($access))
        );
        $wordPressData->expects($this->exactly($expectedCalls))
            ->method('registerAbilityCategory')
            ->with(AbilitiesRegistrar::CATEGORY, $this->isArray());

        $registrar->registerCategory();
    }

    /**
     * @return array<string, array{0: string, 1: bool, 2: bool}>
     */
    public static function canExecuteProvider()
    {
        return [
            'read ability with read access'   => ['read', true, true],
            'write ability with read access'  => ['read', false, false],
            'write ability with write access' => ['write', false, true],
            'read ability with write access'  => ['write', true, false],
        ];
    }

    #[DataProvider('canExecuteProvider')]
    public function testCanExecuteChecksAccessMode($access, $readOnly, $expected)
    {
        list($registrar) = $this->makeRegistrar($access);

        $this->assertSame($expected, $registrar->canExecute($this->abilityStub($readOnly)));
    }

    /**
     * @param bool   $readOnly
     * @param string $capability
     * @return AbilityInterface
     */
    private function abilityStub($readOnly, $capability = 'manage_options')
    {
        $ability = $this->createStub(AbilityInterface::class);
        $ability->method('isReadOnly')->willReturn($readOnly);
        $ability->method('getRequiredCapability')->willReturn($capability);

        return $ability;
    }

    /**
     * @param array $capabilities Capability => granted to the current user.
     * @return AbilitiesRegistrar
     */
    private function registrarForUser(array $capabilities)
    {
        $wordPressData = $this->createStub(\SimpleJWTLogin\Repositories\Wordpress\Repository::class);
        $wordPressData->method('getOptionFromDatabase')->willReturn(json_encode($this->settingsFor('read_write')));
        $wordPressData->method('getFreshOption')->willReturn(json_encode($this->settingsFor('read_write')));
        $wordPressData->method('currentUserCan')->willReturnCallback(function ($capability) use ($capabilities) {
            return !empty($capabilities[$capability]);
        });
        list($registrar) = $this->makeRegistrar('read_write', $wordPressData);

        return $registrar;
    }

    /**
     * @return array<string, array{0: array, 1: string, 2: bool}>
     */
    public static function capabilityProvider()
    {
        $admin      = ['manage_options' => true, 'read' => true];
        $subscriber = ['read' => true];

        return [
            'admin runs admin ability'           => [$admin, 'manage_options', true],
            'admin runs user ability'            => [$admin, 'read', true],
            'subscriber cannot run admin ability' => [$subscriber, 'manage_options', false],
            'subscriber runs user ability'       => [$subscriber, 'read', true],
            'logged out cannot run user ability' => [[], 'read', false],
            'logged out cannot run admin ability' => [[], 'manage_options', false],
            'empty capability is refused'        => [$admin + ['' => true], '', false],
            'exist capability is refused'        => [$admin + ['exist' => true], 'exist', false],
        ];
    }

    #[DataProvider('capabilityProvider')]
    public function testCanExecuteChecksRequiredCapability(array $capabilities, $capability, $expected)
    {
        $registrar = $this->registrarForUser($capabilities);

        $this->assertSame($expected, $registrar->canExecute($this->abilityStub(true, $capability)));
    }

    /**
     * Only explain-error may be run by users without manage_options.
     */
    public function testOnlyExplainErrorIsOpenToNonAdministrators()
    {
        $settings = $this->makeSettings($this->makeWordPressDataStub($this->settingsFor('read_write')));
        $catalog  = new AbilityCatalog(
            $settings,
            $this->createStub(ApiKeyRepositoryInterface::class),
            $this->createStub(AuditLogRepositoryInterface::class),
            $this->createStub(WebhookLogRepositoryInterface::class),
            $this->createStub(RevokedTokenRepositoryInterface::class)
        );

        $open = [];
        foreach ($catalog->all() as $ability) {
            if ($ability->getRequiredCapability() !== 'manage_options') {
                $open[$ability->getName()] = $ability->getRequiredCapability();
            }
        }

        $this->assertSame(['simple-jwt-login/explain-error' => 'read'], $open);
    }

    /**
     * Revoking a permission in wp-admin must block an ability that was
     * registered earlier in a long-running MCP process.
     */
    public function testCanExecuteRechecksRequiredPermission()
    {
        $granted = $this->settingsFor('write', ['allow_create_api_keys' => true]);
        $wordPressData = $this->makeWordPressDataStub($granted, [$granted, $this->settingsFor('write')]);
        list($registrar) = $this->makeRegistrar('write', $wordPressData);
        $ability = $this->createStub(AbilityInterface::class);
        $ability->method('isReadOnly')->willReturn(false);
        $ability->method('getRequiredCapability')->willReturn('manage_options');
        $ability->method('getRequiredPermission')->willReturn('allow_create_api_keys');

        $this->assertTrue($registrar->canExecute($ability));
        $this->assertFalse($registrar->canExecute($ability));
    }

    /**
     * A long-running MCP stdio process must see access changes made in wp-admin.
     */
    public function testCanExecuteReloadsSettingsOnEveryCall()
    {
        $wordPressData = $this->makeWordPressDataStub(
            $this->settingsFor('read_write'),
            [$this->settingsFor('read_write'), $this->settingsFor('off')]
        );
        list($registrar) = $this->makeRegistrar('read_write', $wordPressData);
        $ability = $this->abilityStub(true);

        $this->assertTrue($registrar->canExecute($ability));
        $this->assertFalse($registrar->canExecute($ability));
    }

    public function testExecuteAbilityWorksOnFreshSettings()
    {
        $fresh = $this->settingsFor('read_write');
        $fresh['cors']['allow_origin'] = 'https://changed-in-admin.example.com';

        $wordPressData = $this->makeWordPressDataStub($this->settingsFor('read_write'), [$fresh]);

        $settings = $this->makeSettings($wordPressData);
        $catalog  = new AbilityCatalog(
            $settings,
            $this->createStub(ApiKeyRepositoryInterface::class),
            $this->createStub(AuditLogRepositoryInterface::class),
            $this->createStub(WebhookLogRepositoryInterface::class),
            $this->createStub(RevokedTokenRepositoryInterface::class)
        );
        $registrar   = new AbilitiesRegistrar($settings, $catalog, $this->createStub(AuditLoggerService::class));
        $getSettings = $catalog->all()[0];

        $result = $registrar->executeAbility($getSettings, ['section' => 'cors']);

        $this->assertSame('https://changed-in-admin.example.com', ((array) $result['settings'])['cors']['allow_origin']);
    }

    public function testExecuteWriteAbilityAuditsSuccessWithDiff()
    {
        list($registrar, , $auditLogger) = $this->makeRegistrar('read_write', null, $this->createMock(AuditLoggerService::class));
        $ability = $this->createStub(AuditableAbilityInterface::class);
        $ability->method('isReadOnly')->willReturn(false);
        $ability->method('getName')->willReturn('simple-jwt-login/update-settings');
        $ability->method('execute')->willReturn(['diff' => ['changed' => ['login.enabled' => []]], 'key' => 'raw']);
        $ability->method('auditDetails')->willReturnCallback(function ($input, $result) {
            return ['section' => $input['section'], 'diff' => $result['diff']];
        });

        $auditLogger->expects($this->once())
            ->method('log')
            ->with(
                AuditEvents::AI_ABILITY_SUCCESS,
                1,
                null,
                'success',
                json_encode([
                    'source'  => 'ability',
                    'ability' => 'simple-jwt-login/update-settings',
                    'section' => 'login',
                    'diff'    => ['changed' => ['login.enabled' => []]],
                ])
            );

        $result = $registrar->executeAbility($ability, ['section' => 'login']);

        $this->assertSame('raw', $result['key']);
    }

    /**
     * A long-running MCP process must log with the audit settings reloaded
     * from the database, not the ones captured when the plugin booted.
     */
    public function testExecuteWriteAbilityAuditsWithReloadedAuditSettings()
    {
        $auditLogger = $this->createMock(AuditLoggerService::class);
        list($registrar, , $auditLogger) = $this->makeRegistrar('read_write', null, $auditLogger);
        $ability = $this->createStub(AbilityInterface::class);
        $ability->method('isReadOnly')->willReturn(false);
        $ability->method('execute')->willReturn([]);

        $auditLogger->expects($this->once())
            ->method('withSettings')
            ->with($this->isInstanceOf(AuditLogSettings::class))
            ->willReturnSelf();

        $registrar->executeAbility($ability, []);
    }

    public function testExecuteReadAbilityIsNotAudited()
    {
        list($registrar, , $auditLogger) = $this->makeRegistrar('read', null, $this->createMock(AuditLoggerService::class));
        $ability = $this->createStub(AbilityInterface::class);
        $ability->method('isReadOnly')->willReturn(true);
        $ability->method('execute')->willReturn(['settings' => []]);

        $auditLogger->expects($this->never())->method('log');

        $this->assertSame(['settings' => []], $registrar->executeAbility($ability, null));
    }

    public function testExecuteFailureReturnsWpErrorAndAuditsFailure()
    {
        list($registrar, , $auditLogger) = $this->makeRegistrar('write', null, $this->createMock(AuditLoggerService::class));
        $ability = $this->createStub(AuditableAbilityInterface::class);
        $ability->method('isReadOnly')->willReturn(false);
        $ability->method('getName')->willReturn('simple-jwt-login/add-webhook');
        $ability->method('execute')->willThrowException(new Exception('Only HTTPS URLs are allowed.', 42));
        $ability->method('auditDetails')->willReturn(['url' => 'http://x.example.com']);

        $auditLogger->expects($this->once())
            ->method('log')
            ->with(AuditEvents::AI_ABILITY_FAILED, 1, null, 'failure', json_encode([
                'source'  => 'ability',
                'ability' => 'simple-jwt-login/add-webhook',
                'url'     => 'http://x.example.com',
                'error'   => 'Only HTTPS URLs are allowed.',
            ]));

        $result = $registrar->executeAbility($ability, []);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame(AbilitiesRegistrar::ERROR_CODE, $result->get_error_code());
        $this->assertSame('Only HTTPS URLs are allowed.', $result->get_error_message());
    }

    public function testExecuteFailureMessageIsPlainText()
    {
        list($registrar) = $this->makeRegistrar('write');
        $ability = $this->createStub(AbilityInterface::class);
        $ability->method('isReadOnly')->willReturn(false);
        $ability->method('execute')->willThrowException(new Exception('The setting &quot;x&quot; is read-only.'));

        $result = $registrar->executeAbility($ability, []);

        $this->assertSame('The setting "x" is read-only.', $result->get_error_message());
    }

    public function testExecuteCallbackPassesInputToAbility()
    {
        list($registrar) = $this->makeRegistrar('read');
        $ability = $this->createMock(AbilityInterface::class);
        $ability->method('isReadOnly')->willReturn(true);
        $ability->method('getDefinition')->willReturn([
            'label' => 'l', 'description' => 'd', 'input_schema' => [], 'output_schema' => [], 'annotations' => [],
        ]);
        $ability->expects($this->once())->method('execute')->with(['section' => 'cors'])->willReturn([]);

        $args = $registrar->buildArgs($ability);
        $args['execute_callback'](['section' => 'cors']);
    }
}
