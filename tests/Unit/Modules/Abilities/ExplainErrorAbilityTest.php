<?php

namespace SimpleJwtLoginTests\Unit\Modules\Abilities;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ReflectionClass;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Abilities\AbilitySettingsPolicy;
use SimpleJWTLogin\Modules\Abilities\Diagnostics\ExplainErrorAbility;
use SimpleJWTLogin\Modules\Abilities\Diagnostics\RestErrorCatalog;
use SimpleJWTLogin\Modules\Abilities\Diagnostics\SettingsErrorCatalog;
use SimpleJWTLogin\Modules\Abilities\SettingsPath;
use SimpleJWTLogin\Modules\Abilities\SettingsRedactor;

#[RunTestsInSeparateProcesses]
class ExplainErrorAbilityTest extends AbilityTestCase
{
    /**
     * @param int $code
     * @return array
     */
    private function explain($code)
    {
        $settings = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));

        return (new ExplainErrorAbility($settings, new AbilitySettingsPolicy()))->execute(['error_code' => $code]);
    }

    /**
     * @return array<string, array{0: int, 1: string, 2: string, 3: int|null, 4: string}>
     */
    public static function knownCodeProvider()
    {
        return [
            'wrong credentials' => [
                ErrorCodes::ERR_AUTHENTICATION_WRONG_CREDENTIALS, ExplainErrorAbility::KIND_REST,
                'ERR_AUTHENTICATION_WRONG_CREDENTIALS', 401, 'authorization.password_base64',
            ],
            'jwt library signature error' => [
                ErrorCodes::ERR_SIGNATURE_VERIFICATION_FAILED, ExplainErrorAbility::KIND_REST,
                'ERR_SIGNATURE_VERIFICATION_FAILED', 400, 'general.jwt_algorithm',
            ],
            'auth disabled' => [
                ErrorCodes::ERR_AUTHENTICATION_IS_NOT_ENABLED, ExplainErrorAbility::KIND_REST,
                'ERR_AUTHENTICATION_IS_NOT_ENABLED', null, 'authorization.enabled',
            ],
            'settings: hs key required' => [
                2005, ExplainErrorAbility::KIND_SETTINGS, 'PREFIX_GENERAL', null, 'general.jwt_algorithm',
            ],
            'settings: login payload key uses general prefix' => [
                2009, ExplainErrorAbility::KIND_SETTINGS, 'PREFIX_GENERAL', null, 'login.login_by_parameter',
            ],
            'settings: refresh key uses refresh prefix' => [
                15004, ExplainErrorAbility::KIND_SETTINGS, 'PREFIX_REFRESH_TOKEN', null, 'authorization.refresh_token_enabled',
            ],
            'settings: webhook retention' => [
                14002, ExplainErrorAbility::KIND_SETTINGS, 'PREFIX_WEBHOOKS', null, 'webhooks.logs.retention',
            ],
            'settings: google client id' => [
                12002, ExplainErrorAbility::KIND_SETTINGS, 'PREFIX_APPLICATIONS', null, 'integrations.oauth.google.client_id',
            ],
        ];
    }

    #[DataProvider('knownCodeProvider')]
    public function testExplainKnownCode($code, $kind, $constant, $httpStatus, $settingPath)
    {
        $result = $this->explain($code);

        $this->assertSame($code, $result['error_code']);
        $this->assertSame($kind, $result['kind']);
        $this->assertSame($constant, $result['constant']);
        if ($httpStatus !== null) {
            $this->assertSame($httpStatus, $result['http_status']);
        }
        $this->assertNotSame('', $result['explanation']);
        $this->assertArrayHasKey($settingPath, (array) $result['settings']);
    }

    public function testExplainNeverReturnsSecrets()
    {
        $stored = $this->storedSettings([
            'integrations' => ['oauth' => ['google' => ['client_secret' => 'google-secret-value']]],
        ]);
        $settings = $this->makeSettings($this->makeWordPressDataStub($stored));
        $ability  = new ExplainErrorAbility($settings, new AbilitySettingsPolicy());

        $result = $ability->execute(['error_code' => ErrorCodes::ERR_OAUTH_PROVIDER_NOT_ACTIVE]);

        $this->assertSame(
            SettingsRedactor::MASK,
            ((array) $result['settings'])['integrations.oauth']['google']['client_secret']
        );
        $this->assertStringNotContainsString('google-secret-value', (string) json_encode($result));
    }

    public function testNonAdministratorGetsExplanationWithoutSettingValues()
    {
        $wordPressData = $this->createStub(\SimpleJWTLogin\Repositories\Wordpress\Repository::class);
        $wordPressData->method('getOptionFromDatabase')->willReturn(json_encode($this->storedSettings()));
        $wordPressData->method('currentUserCan')->willReturnCallback(function ($capability) {
            return $capability === 'read';
        });
        $ability = new ExplainErrorAbility($this->makeSettings($wordPressData), new AbilitySettingsPolicy());

        $result = $ability->execute(['error_code' => ErrorCodes::ERR_AUTHENTICATION_WRONG_CREDENTIALS]);

        $this->assertSame('read', $ability->getRequiredCapability());
        $this->assertSame('ERR_AUTHENTICATION_WRONG_CREDENTIALS', $result['constant']);
        $this->assertNotSame('', $result['explanation']);
        $this->assertContains('authorization.password_base64', $result['related_settings']);
        $this->assertSame([], (array) $result['settings']);
    }

    public function testAdministratorGetsRelatedSettingPathsAndValues()
    {
        $result = $this->explain(ErrorCodes::ERR_AUTHENTICATION_WRONG_CREDENTIALS);

        $this->assertSame($result['related_settings'], array_keys((array) $result['settings']));
    }

    /**
     * @return array<string, array{0: int}>
     */
    public static function unknownCodeProvider()
    {
        return [
            'unused rest code'     => [999],
            'unused settings code' => [99999],
        ];
    }

    #[DataProvider('unknownCodeProvider')]
    public function testUnknownCodeThrows($code)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_ABILITY_UNKNOWN_ERROR_CODE);

        $this->explain($code);
    }

    public function testEveryRestErrorCodeHasGuidance()
    {
        $catalog = new RestErrorCatalog();
        $missing = [];
        foreach ((new ReflectionClass(ErrorCodes::class))->getConstants() as $name => $code) {
            if (strpos($name, 'ERR_') === 0 && $catalog->find($code) === null) {
                $missing[] = $name;
            }
        }

        $this->assertSame([], $missing);
    }

    public function testEveryCatalogPathExistsInTheStoredSettings()
    {
        $stored  = $this->storedSettings();
        $entries = array_merge(
            array_values((new class extends RestErrorCatalog {
                public function all()
                {
                    return $this->entries();
                }
            })->all()),
            array_values((new class extends SettingsErrorCatalog {
                public function all()
                {
                    return $this->entries();
                }
            })->all())
        );

        $missing = [];
        foreach ($entries as $entry) {
            foreach ($entry[1] as $path) {
                if (!SettingsPath::exists($stored, $path)) {
                    $missing[] = $path;
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)));
    }
}
