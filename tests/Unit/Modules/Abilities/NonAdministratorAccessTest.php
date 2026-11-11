<?php

namespace SimpleJwtLoginTests\Unit\Modules\Abilities;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ReflectionClass;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Abilities\AbilitiesRegistrar;
use SimpleJWTLogin\Modules\Abilities\AbilityCatalog;
use SimpleJWTLogin\Modules\Abilities\AbilitySettingsPolicy;
use SimpleJWTLogin\Modules\Abilities\Diagnostics\ExplainErrorAbility;
use SimpleJWTLogin\Modules\Abilities\Diagnostics\SettingsErrorCatalog;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Repositories\AuditLog\Repository as AuditLogRepositoryInterface;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\WebhookLog\Repository as WebhookLogRepositoryInterface;
use SimpleJWTLogin\Services\AuditLoggerService;
use stdClass;
use WP_Error;

/**
 * Administrators vs. everyone else.
 *
 * A non-administrator (editor, subscriber, logged-out visitor) must not be able
 * to run, list, describe or probe the Simple JWT Login abilities, and the one
 * ability open to them (explain-error) must never return a configured value.
 */
#[RunTestsInSeparateProcesses]
class NonAdministratorAccessTest extends AbilityTestCase
{
    const MARKER = 'LEAK-';

    const ALL_PERMISSIONS = [
        'allow_view_secrets'          => true,
        'allow_change_roles'          => true,
        'allow_change_auth_providers' => true,
        'allow_create_api_keys'       => true,
        'allow_add_webhooks'          => true,
    ];

    /**
     * @param mixed $wordPressData Stub or mock built for the user under test.
     * @return array{0: AbilitiesRegistrar, 1: AbilityCatalog, 2: mixed}
     */
    private function makeRegistrar($wordPressData)
    {
        $settings = $this->makeSettings($wordPressData);
        $catalog  = new AbilityCatalog(
            $settings,
            $this->createStub(ApiKeyRepositoryInterface::class),
            $this->createStub(AuditLogRepositoryInterface::class),
            $this->createStub(WebhookLogRepositoryInterface::class),
            $this->createStub(RevokedTokenRepositoryInterface::class)
        );
        $auditLogger = $this->createStub(AuditLoggerService::class);
        $auditLogger->method('withSettings')->willReturnSelf();

        return [new AbilitiesRegistrar($settings, $catalog, $auditLogger), $catalog, $wordPressData];
    }

    /**
     * Registrar for a user, WordPress data stubbed (no expectations).
     *
     * @param string     $access
     * @param array|null $capabilities
     * @return array{0: AbilitiesRegistrar, 1: AbilityCatalog, 2: mixed}
     */
    private function registrarForUser($access, $capabilities)
    {
        return $this->makeRegistrar($this->makeWordPressDataStub($this->settingsFor($access), null, $capabilities));
    }

    /**
     * Registrar for a user, WordPress data mocked (the test sets expectations).
     *
     * @param string     $access
     * @param array|null $capabilities
     * @return array{0: AbilitiesRegistrar, 1: AbilityCatalog, 2: mixed}
     */
    private function mockedRegistrarForUser($access, $capabilities)
    {
        return $this->makeRegistrar($this->makeWordPressDataMockForUser($this->settingsFor($access), $capabilities));
    }

    /**
     * @param string $access
     * @return array
     */
    private function settingsFor($access)
    {
        return $this->storedSettings(['integrations' => ['3rdparty' => ['ai_abilities' => array_merge(
            ['enabled' => true, 'access' => $access],
            self::ALL_PERMISSIONS
        )]]]);
    }

    /**
     * Stored settings where every string value is a unique marker, so any
     * configured value showing up in an output is detectable.
     *
     * @param array  $settings
     * @param string $path
     * @return array
     */
    private function markAllValues(array $settings, $path = '')
    {
        $marked = [];
        foreach ($settings as $key => $value) {
            $childPath = $path === '' ? (string) $key : $path . '.' . $key;
            if (is_array($value)) {
                $marked[$key] = $this->markAllValues($value, $childPath);
                continue;
            }
            $marked[$key] = is_string($value) && $value !== '' && $childPath !== 'integrations.3rdparty.ai_abilities.access'
                ? self::MARKER . $childPath
                : $value;
        }

        return $marked;
    }

    /**
     * @return array<string, array{0: string, 1: array}>
     */
    public static function nonAdministratorProvider()
    {
        $cases = [];
        foreach (self::userRoles() as $role => $capabilities) {
            if ($capabilities === null) {
                continue;
            }
            foreach (['read', 'write', 'read_write'] as $access) {
                $cases[$role . ' / ' . $access] = [$access, $capabilities];
            }
        }

        return $cases;
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function accessProvider()
    {
        return [
            'read'       => ['read'],
            'write'      => ['write'],
            'read write' => ['read_write'],
        ];
    }

    /**
     * The real WordPress gate: the permission_callback passed to
     * wp_register_ability() must refuse every admin ability.
     *
     * @param string $access
     * @param array  $capabilities
     */
    #[DataProvider('nonAdministratorProvider')]
    public function testNonAdministratorCannotRunAdminAbilities($access, array $capabilities)
    {
        list($registrar, $catalog) = $this->registrarForUser($access, $capabilities);

        $runnable = [];
        foreach ($catalog->all() as $ability) {
            $args = $registrar->buildArgs($ability);
            if (call_user_func($args['permission_callback'])) {
                $runnable[] = $ability->getName();
            }
        }

        $expected = $access !== 'write' && !empty($capabilities['read'])
            ? ['simple-jwt-login/explain-error']
            : [];
        $this->assertSame($expected, $runnable);
    }

    /**
     * @param string $access
     */
    #[DataProvider('accessProvider')]
    public function testAdministratorCanRunEveryRegisteredAbility($access)
    {
        list($registrar, $catalog) = $this->registrarForUser($access, null);

        foreach ($catalog->all() as $ability) {
            $args     = $registrar->buildArgs($ability);
            $expected = $ability->isReadOnly() ? $access !== 'write' : $access !== 'read';
            $this->assertSame($expected, call_user_func($args['permission_callback']), $ability->getName());
        }
    }

    /**
     * Abilities the user cannot run are removed from the registry, so the
     * core list / describe / run endpoints answer as if they did not exist.
     *
     * @param string $access
     * @param array  $capabilities
     */
    #[DataProvider('nonAdministratorProvider')]
    public function testHideFromCurrentUserUnregistersEveryAbilityTheUserCannotRun($access, array $capabilities)
    {
        list($registrar, $catalog, $wordPressData) = $this->mockedRegistrarForUser($access, $capabilities);
        $hidden = [];
        $wordPressData->method('unregisterAbility')->willReturnCallback(function ($name) use (&$hidden) {
            $hidden[] = $name;
        });
        $explainVisible = $access !== 'write' && !empty($capabilities['read']);
        $wordPressData->expects($this->never())->method('unregisterAbilityCategory');

        $registrar->hideFromCurrentUser();

        $expected = [];
        foreach ($catalog->all() as $ability) {
            if ($explainVisible && $ability->getName() === 'simple-jwt-login/explain-error') {
                continue;
            }
            $expected[] = $ability->getName();
        }
        $this->assertSame($expected, $hidden);
    }

    /**
     * The category is hidden when the user cannot run any ability; abilities
     * are then neither registered nor unregistered in that request (core
     * rejects abilities whose category is missing).
     *
     * @param string $access
     * @param array  $capabilities
     */
    #[DataProvider('nonAdministratorProvider')]
    public function testHideCategoryFromCurrentUserWhenNothingIsVisible($access, array $capabilities)
    {
        list($registrar, , $wordPressData) = $this->mockedRegistrarForUser($access, $capabilities);
        $explainVisible = $access !== 'write' && !empty($capabilities['read']);
        $wordPressData->expects($this->exactly($explainVisible ? 0 : 1))
            ->method('unregisterAbilityCategory')
            ->with(AbilitiesRegistrar::CATEGORY);
        $wordPressData->expects($explainVisible ? $this->atLeastOnce() : $this->never())
            ->method('registerAbility');
        $wordPressData->expects($explainVisible ? $this->atLeastOnce() : $this->never())
            ->method('unregisterAbility');

        $registrar->hideCategoryFromCurrentUser();
        $registrar->registerAbilities();
        $registrar->hideFromCurrentUser();
    }

    /**
     * @param string $access
     */
    #[DataProvider('accessProvider')]
    public function testHideCategoryFromCurrentUserKeepsItForAnAdministrator($access)
    {
        list($registrar, , $wordPressData) = $this->mockedRegistrarForUser($access, null);
        $wordPressData->expects($this->never())->method('unregisterAbilityCategory');

        $registrar->hideCategoryFromCurrentUser();
    }

    /**
     * @param string $access
     */
    #[DataProvider('accessProvider')]
    public function testHideFromCurrentUserKeepsWhatAnAdministratorMayRun($access)
    {
        list($registrar, $catalog, $wordPressData) = $this->mockedRegistrarForUser($access, null);
        $hidden = [];
        $wordPressData->method('unregisterAbility')->willReturnCallback(function ($name) use (&$hidden) {
            $hidden[] = $name;
        });
        $wordPressData->expects($this->never())->method('unregisterAbilityCategory');

        $registrar->hideFromCurrentUser();

        $expected = [];
        foreach ($catalog->all() as $ability) {
            // Not registered for this access mode anyway.
            if ($ability->isReadOnly() ? $access === 'write' : $access === 'read') {
                $expected[] = $ability->getName();
            }
        }
        $this->assertSame($expected, $hidden);
    }

    /**
     * @return array<string, array{0: array|null, 1: bool, 2: string, 3: int, 4: int, 5: bool, 6: bool}>
     */
    public static function preDispatchProvider()
    {
        $subscriber = ['read' => true];

        return [
            'administrator: nothing hidden'               => [null, true, 'read_write', 0, 0, false, false],
            'subscriber, registry built: hide now'        => [$subscriber, true, 'read_write', 1, 0, false, false],
            'subscriber, registry not built: hide on init' => [$subscriber, false, 'read_write', 0, 1, false, false],
            'logged out, registry built: hide now'        => [[], true, 'read', 1, 0, false, false],
            'AI access off: nothing to hide'              => [$subscriber, true, 'off', 0, 0, false, false],
            'WP-CLI: never hides'                         => [$subscriber, true, 'read_write', 0, 0, true, false],
            'internal request during rest_api_init'       => [$subscriber, true, 'read_write', 0, 0, false, true],
        ];
    }

    /**
     * @param array|null $capabilities
     * @param bool       $registryBuilt
     * @param string     $access
     * @param int        $hideNow       Expected unregisterAbility() batches run immediately.
     * @param int        $deferred      Expected deferred hook pairs (abilities + categories init).
     * @param bool       $cli
     * @param bool       $registeringRoutes True while rest_api_init runs.
     */
    #[DataProvider('preDispatchProvider')]
    public function testFilterRestPreDispatch(
        $capabilities,
        $registryBuilt,
        $access,
        $hideNow,
        $deferred,
        $cli,
        $registeringRoutes
    ) {
        list($registrar, , $wordPressData) = $this->mockedRegistrarForUser($access, $capabilities);
        $wordPressData->method('didAction')->willReturn($registryBuilt);
        $wordPressData->method('isCli')->willReturn($cli);
        $wordPressData->method('doingAction')->willReturn($registeringRoutes);
        $wordPressData->expects($hideNow > 0 ? $this->atLeastOnce() : $this->never())->method('unregisterAbility');
        $deferredHooks = [];
        $wordPressData->expects($this->exactly($deferred * 2))
            ->method('addAction')
            ->willReturnCallback(function ($hook, $callback, $priority) use (&$deferredHooks) {
                $deferredHooks[$hook] = [$callback, $priority];
            });

        $result = new stdClass();

        $this->assertSame($result, $registrar->filterRestPreDispatch($result));
        if ($deferred > 0) {
            $this->assertSame([
                'wp_abilities_api_init'            => [[$registrar, 'hideFromCurrentUser'], PHP_INT_MAX],
                'wp_abilities_api_categories_init' => [[$registrar, 'hideCategoryFromCurrentUser'], PHP_INT_MAX],
            ], $deferredHooks);
        }
    }

    /**
     * explain-error is the only ability a non-administrator can run. Whatever
     * code they ask about, no configured value may come back.
     *
     * @param string $access
     */
    #[DataProvider('accessProvider')]
    public function testExplainErrorNeverReturnsConfigurationToNonAdministrators($access)
    {
        $stored = $this->markAllValues($this->settingsFor($access));
        foreach (self::userRoles() as $role => $capabilities) {
            if ($capabilities === null) {
                continue;
            }
            $settings = $this->makeSettings($this->makeWordPressDataStub($stored, null, $capabilities));
            $ability  = new ExplainErrorAbility($settings, new AbilitySettingsPolicy());

            foreach ($this->allExplainableCodes() as $code) {
                $result = $ability->execute(['error_code' => $code]);

                $this->assertSame([], (array) $result['settings'], $role . ' / ' . $code);
                $this->assertStringNotContainsString(
                    self::MARKER,
                    (string) json_encode($result),
                    $role . ' / ' . $code
                );
            }
        }
    }

    /**
     * Same codes, administrator: the values are there (proves the marker check
     * above is not vacuous), but secrets stay masked.
     */
    public function testExplainErrorReturnsMaskedValuesToAdministrators()
    {
        $stored   = $this->markAllValues($this->settingsFor('read'));
        $settings = $this->makeSettings($this->makeWordPressDataStub($stored));
        $ability  = new ExplainErrorAbility($settings, new AbilitySettingsPolicy());

        $result = $ability->execute(['error_code' => ErrorCodes::ERR_SIGNATURE_VERIFICATION_FAILED]);
        $json   = (string) json_encode($result);

        $this->assertNotSame([], (array) $result['settings']);
        $this->assertStringContainsString(self::MARKER . 'general.jwt_algorithm', $json);
        $this->assertStringNotContainsString(self::MARKER . 'general.decryption_key"', $json);
    }

    /**
     * The explain-error definition is visible to every logged-in user (core
     * lists abilities to anyone with 'read'): it must not depend on settings.
     */
    public function testExplainErrorDefinitionDoesNotDependOnSettings()
    {
        $plain  = $this->makeSettings($this->makeWordPressDataStub($this->settingsFor('read'), null, ['read' => true]));
        $marked = $this->makeSettings(
            $this->makeWordPressDataStub($this->markAllValues($this->settingsFor('read')), null, ['read' => true])
        );

        $this->assertSame(
            (new ExplainErrorAbility($plain, new AbilitySettingsPolicy()))->getDefinition(),
            (new ExplainErrorAbility($marked, new AbilitySettingsPolicy()))->getDefinition()
        );
    }

    /**
     * Unknown codes fail with a static message, not with configuration.
     */
    public function testExplainErrorUnknownCodeMessageHasNoConfiguration()
    {
        $stored   = $this->markAllValues($this->settingsFor('read'));
        $settings = $this->makeSettings($this->makeWordPressDataStub($stored, null, ['read' => true]));
        list($registrar) = $this->makeRegistrar($this->makeWordPressDataStub($stored, null, ['read' => true]));

        $result = $registrar->executeAbility(
            new ExplainErrorAbility($settings, new AbilitySettingsPolicy()),
            ['error_code' => 99999]
        );

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertStringNotContainsString(self::MARKER, $result->get_error_message());
    }

    /**
     * @return int[]
     */
    private function allExplainableCodes()
    {
        $codes = [];
        foreach ((new ReflectionClass(ErrorCodes::class))->getConstants() as $name => $code) {
            if (strpos($name, 'ERR_') === 0) {
                $codes[] = $code;
            }
        }
        $settingsCatalog = new class extends SettingsErrorCatalog {
            public function codes()
            {
                return array_keys($this->entries());
            }
        };

        return array_merge($codes, $settingsCatalog->codes());
    }
}
