<?php

namespace SimpleJwtLoginTests\Unit\Cli;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Cli\CliAuditLog;
use SimpleJWTLogin\Cli\SetupCommand;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository;
use WP_CLI;

class SetupCommandTest extends TestCase
{
    /** @var array<int, array> */
    private array $saved = [];

    protected function setUp(): void
    {
        parent::setUp();
        WP_CLI::reset();
        RecordingAuditLog::$records = [];
        $this->saved = [];
    }

    #[DataProvider('setupProvider')]
    public function testSetup(array $general, array $assocArgs, ?string $expectedKey, string $expectedAlgorithm, array $expectedErrors): void
    {
        $this->makeCommand($general)->__invoke([], $assocArgs);

        $this->assertSame($expectedErrors, WP_CLI::$errors);
        if ($expectedErrors !== []) {
            $this->assertSame([], $this->saved);
            return;
        }
        $saved = $this->saved[0];
        $this->assertSame('1', $saved['authorization']['enabled']);
        // A replaced key is kept for the tokens it signed.
        $replaced = $expectedKey === null && isset($general['decryption_key']) && $general['decryption_source'] === '0';
        $this->assertSame(
            $replaced ? [$general['decryption_key']] : [],
            array_column(isset($saved['jwt_rules']['rules']) ? $saved['jwt_rules']['rules'] : [], 'decryption_key')
        );
        $this->assertSame($expectedAlgorithm, $saved['general']['jwt_algorithm']);
        if ($expectedKey === null) {
            $this->assertNotSame(isset($general['decryption_key']) ? $general['decryption_key'] : '', $saved['general']['decryption_key']);
            return;
        }
        $this->assertSame($expectedKey, $saved['general']['decryption_key']);
    }

    public static function setupProvider(): array
    {
        $configured = ['jwt_algorithm' => 'HS256', 'decryption_source' => '0', 'decryption_key' => 'existing'];

        return [
            'keeps an existing key'      => [$configured, [], 'existing', 'HS256', []],
            'generates a missing key'    => [['jwt_algorithm' => 'HS256', 'decryption_source' => '0'], [], null, 'HS256', []],
            'force replaces the key'     => [$configured, ['force' => true], null, 'HS256', []],
            'new algorithm gets new key' => [$configured, ['algorithm' => 'HS512'], null, 'HS512', []],
            'algorithm of a wp-config key' => [
                ['jwt_algorithm' => 'HS256', 'decryption_source' => GeneralSettings::DECRYPTION_SOURCE_CODE],
                ['algorithm' => 'HS512'],
                null,
                'HS256',
                ['The signing key is defined in wp-config.php. Change the algorithm there.'],
            ],
            'unknown algorithm'          => [$configured, ['algorithm' => 'none'], 'existing', 'HS256', ['Unsupported algorithm: NONE']],
            'key in wp-config is kept'   => [
                ['jwt_algorithm' => 'HS256', 'decryption_source' => GeneralSettings::DECRYPTION_SOURCE_CODE, 'decryption_key' => 'unused'],
                ['force' => true],
                'unused',
                'HS256',
                [],
            ],
        ];
    }

    private function makeCommand(array $general): SetupCommand
    {
        $stored = ['general' => $general];
        $jwtSettings = $this->createStub(SimpleJWTLoginSettings::class);
        $jwtSettings->method('getStoredSettings')->willReturn($stored);
        $jwtSettings->method('getGeneralSettings')->willReturn((new GeneralSettings())->withSettings($stored));
        $jwtSettings->method('getLoginSettings')->willReturn((new LoginSettings())->withSettings(['login' => ['login_by_parameter' => 'email']]));
        $jwtSettings->method('getAuthenticationSettings')->willReturn((new AuthenticationSettings())->withSettings([]));
        $jwtSettings->method('saveSettingsArray')->willReturnCallback(function (array $settings) {
            $this->saved[] = $settings;
            return [];
        });
        $wordPressData = $this->createStub(Repository::class);
        $wordPressData->method('getSiteUrl')->willReturn('https://example.com');

        return new class ($jwtSettings, $wordPressData) extends SetupCommand {
            /** @var mixed */
            private $jwtSettings;
            /** @var mixed */
            private $wordPressData;

            public function __construct($jwtSettings, $wordPressData)
            {
                $this->jwtSettings = $jwtSettings;
                $this->wordPressData = $wordPressData;
            }

            protected function createAuditLog(): CliAuditLog
            {
                return new RecordingAuditLog();
            }

            protected function createSettings(): SimpleJWTLoginSettings
            {
                return $this->jwtSettings;
            }

            protected function createWordPressData()
            {
                return $this->wordPressData;
            }
        };
    }
}
