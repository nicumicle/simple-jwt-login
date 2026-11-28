<?php

namespace SimpleJwtLoginTests\Unit\Cli;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Cli\AuthCodeCommand;
use SimpleJWTLogin\Cli\KeyCommand;
use SimpleJWTLogin\Cli\SigningKeys;
use SimpleJWTLogin\Modules\Settings\AuthCodesSettings;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use WP_CLI;

/**
 * Commands that change settings: wp jwt key, wp jwt auth-code.
 */
class SettingsCommandsTest extends TestCase
{
    /** @var array<int, array> Arrays passed to saveSettingsArray() */
    private array $saved = [];

    protected function setUp(): void
    {
        parent::setUp();
        WP_CLI::reset();
        RecordingAuditLog::$records = [];
        $this->saved = [];
    }

    public function testRotateRefusesKeysFromWpConfig(): void
    {
        $jwtSettings = $this->makeSettings(['general' => ['decryption_source' => GeneralSettings::DECRYPTION_SOURCE_CODE]]);

        $this->makeKeyCommand($jwtSettings)->rotate([], ['yes' => true]);

        $this->assertSame(['The signing key is defined in wp-config.php. Change it there.'], WP_CLI::$errors);
        $this->assertSame([], $this->saved);
    }

    #[DataProvider('rotateProvider')]
    public function testRotateKeepsTheOldKeyForIssuedTokens(array $assocArgs, string $expectedAlgorithm): void
    {
        $jwtSettings = $this->makeSettings([
            'general'       => ['jwt_algorithm' => 'HS256', 'decryption_source' => '0', 'decryption_key' => 'old'],
            'authorization' => ['ttl' => 30],
            'login'         => ['login_by' => 0, 'login_by_parameter' => 'email'],
            'jwt_rules'     => ['rules' => [$this->rotationRule('1000'), $this->rotationRule('1700000000')]],
        ]);

        $this->makeKeyCommand($jwtSettings, 1700001000)->rotate([], $assocArgs + ['yes' => true]);

        $this->assertSame([], WP_CLI::$errors);
        $this->assertSame(['confirm:Tokens signed with the current key keep working for 31 more minutes, until they expire. Continue?'], WP_CLI::$lines);
        $general = $this->saved[0]['general'];
        $this->assertSame($expectedAlgorithm, $general['jwt_algorithm']);
        $this->assertNotSame('old', $general['decryption_key']);
        $rules = $this->saved[0]['jwt_rules']['rules'];
        // The expired rule of 1000 is removed, the one still in its grace period stays, the old key is appended.
        $this->assertSame(['1700000000', '1700001000'], array_column($rules, 'condition_value'));
        $this->assertSame(['HS256', 'old'], [$rules[1]['algorithm'], $rules[1]['decryption_key']]);
        $this->assertSame(
            [['command' => 'key rotate', 'status' => 'success', 'details' => ['algorithm' => $expectedAlgorithm, 'immediate' => false]]],
            RecordingAuditLog::$records
        );
    }

    public static function rotateProvider(): array
    {
        return [
            'keeps the algorithm'   => [[], 'HS256'],
            'changes the algorithm' => [['algorithm' => 'hs512'], 'HS512'],
        ];
    }

    public function testRotateImmediatelyDropsEveryOldKey(): void
    {
        $partner = ['iss' => 'partner'];
        $jwtSettings = $this->makeSettings([
            'general'   => ['jwt_algorithm' => 'HS256', 'decryption_source' => '0', 'decryption_key' => 'old'],
            'jwt_rules' => ['rules' => [$this->rotationRule('1700000000'), $partner]],
        ]);

        $this->makeKeyCommand($jwtSettings, 1700001000)->rotate([], ['immediate' => true, 'yes' => true]);

        $this->assertSame(['confirm:Every JWT signed with the current or an earlier key stops working now. Continue?'], WP_CLI::$lines);
        $this->assertSame([$partner], $this->saved[0]['jwt_rules']['rules']);
        $this->assertNotSame('old', $this->saved[0]['general']['decryption_key']);
    }

    private function rotationRule(string $rotatedAt): array
    {
        return [
            'condition_type' => 'payload', 'condition_key' => 'iat', 'condition_operator' => 'less_than',
            'condition_value' => $rotatedAt, 'algorithm' => 'HS256', 'decryption_key' => 'older',
        ];
    }

    #[DataProvider('maskProvider')]
    public function testAuthCodeListHidesCodesUnlessShown(string $code, string $masked): void
    {
        $jwtSettings = $this->makeSettings(['auth_codes' => ['codes' => [['code' => $code, 'role' => '', 'expiration_date' => '']]]]);

        $this->makeAuthCodeCommand($jwtSettings)->listCodes([], []);
        $this->makeAuthCodeCommand($jwtSettings)->listCodes([], ['show' => true]);

        $formatted = WP_CLI::$formatted;
        $this->assertSame($masked, $formatted[0]['items'][0]['code']);
        $this->assertSame($code, $formatted[1]['items'][0]['code']);
    }

    public static function maskProvider(): array
    {
        return [
            'long code shows a prefix'   => ['SECRET_CODE_1', 'SEC****'],
            'short code is fully hidden' => ['SECRET1', '****'],
        ];
    }

    public function testRotateWithRandomAlgorithmSavesAMatchingKey(): void
    {
        $jwtSettings = $this->makeSettings(['general' => ['jwt_algorithm' => 'HS256', 'decryption_source' => '0']]);

        $this->makeKeyCommand($jwtSettings)->rotate([], ['algorithm' => 'random', 'yes' => true]);

        $general = $this->saved[0]['general'];
        $this->assertContains($general['jwt_algorithm'], SigningKeys::algorithms());
        $keyField = strpos($general['jwt_algorithm'], 'RS') === 0 ? 'decryption_key_private' : 'decryption_key';
        $this->assertNotEmpty($general[$keyField]);
        $this->assertSame(
            ['The signing key has been rotated (' . $general['jwt_algorithm'] . ').'],
            WP_CLI::$successes
        );
    }

    public function testRotateRejectsAnUnknownAlgorithmBeforeAsking(): void
    {
        $jwtSettings = $this->makeSettings(['general' => ['decryption_source' => '0']]);

        $this->makeKeyCommand($jwtSettings)->rotate([], ['algorithm' => 'none']);

        $this->assertSame(['Unsupported algorithm: NONE'], WP_CLI::$errors);
        $this->assertSame([], WP_CLI::$lines);
        $this->assertSame([], $this->saved);
    }

    #[DataProvider('addProvider')]
    public function testAuthCodeAdd(array $args, array $assocArgs, ?array $expectedCode, array $expectedErrors): void
    {
        $jwtSettings = $this->makeSettings(['auth_codes' => ['codes' => [['code' => 'OLD', 'role' => '', 'expiration_date' => '']]]]);

        $this->makeAuthCodeCommand($jwtSettings)->add($args, $assocArgs);

        $this->assertSame($expectedErrors, WP_CLI::$errors);
        if ($expectedCode === null) {
            $this->assertSame([], $this->saved);
            return;
        }
        $codes = $this->saved[0]['auth_codes']['codes'];
        $this->assertSame('OLD', $codes[0]['code']);
        $this->assertSame($expectedCode, array_intersect_key($codes[1], $expectedCode));
    }

    public static function addProvider(): array
    {
        return [
            'given code' => [['MINE'], ['role' => 'editor'], ['code' => 'MINE', 'role' => 'editor', 'expiration_date' => ''], []],
            'expiration' => [['MINE'], ['expires' => '2030-01-01 00:00:00'], ['expiration_date' => '2030-01-01 00:00:00'], []],
            'bad date'   => [['MINE'], ['expires' => 'not a date'], null, ['--expires is not a valid date.']],
            'empty code' => [['  '], [], null, ['The code can not be empty.']],
        ];
    }

    public function testAuthCodeAddGeneratesAndPrintsARandomCode(): void
    {
        $this->makeAuthCodeCommand($this->makeSettings([]))->add([], []);

        $code = $this->saved[0]['auth_codes']['codes'][0]['code'];
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $code);
        $this->assertSame([$code], WP_CLI::$lines);
    }

    public function testAuthCodeAddReportsValidationErrors(): void
    {
        $jwtSettings = $this->makeSettings([], new Exception('Invalid role provided.'));

        $this->makeAuthCodeCommand($jwtSettings)->add(['MINE'], ['role' => 'nope']);

        $this->assertSame(['Invalid role provided.'], WP_CLI::$errors);
        $this->assertSame(['failure'], array_column(RecordingAuditLog::$records, 'status'));
    }

    #[DataProvider('removeProvider')]
    public function testAuthCodeRemove(array $args, array $expectedCodes, array $expectedErrors): void
    {
        $jwtSettings = $this->makeSettings(['auth_codes' => ['codes' => [
            ['code' => 'A', 'role' => '', 'expiration_date' => ''],
            ['code' => 'B', 'role' => '', 'expiration_date' => ''],
        ]]]);

        $this->makeAuthCodeCommand($jwtSettings)->remove($args, ['yes' => true]);

        $this->assertSame($expectedErrors, WP_CLI::$errors);
        $this->assertSame($expectedCodes, array_column(isset($this->saved[0]) ? $this->saved[0]['auth_codes']['codes'] : [], 'code'));
    }

    public static function removeProvider(): array
    {
        return [
            'first'   => [['0'], ['B'], []],
            'missing' => [['5'], [], ['Auth code not found. See wp jwt auth-code list.']],
            'invalid' => [['x'], [], ['Auth code not found. See wp jwt auth-code list.']],
        ];
    }

    private function makeSettings(array $stored, ?Exception $saveException = null): SimpleJWTLoginSettings
    {
        $jwtSettings = $this->createStub(SimpleJWTLoginSettings::class);
        $jwtSettings->method('getStoredSettings')->willReturn($stored);
        $jwtSettings->method('getGeneralSettings')->willReturn((new GeneralSettings())->withSettings($stored));
        $jwtSettings->method('getAuthenticationSettings')->willReturn((new AuthenticationSettings())->withSettings($stored));
        $jwtSettings->method('getAuthCodesSettings')->willReturn((new AuthCodesSettings())->withSettings($stored));
        $jwtSettings->method('getLoginSettings')->willReturn((new LoginSettings())->withSettings($stored));
        $jwtSettings->method('saveSettingsArray')->willReturnCallback(function (array $settings) use ($saveException) {
            if ($saveException !== null) {
                throw $saveException;
            }
            $this->saved[] = $settings;
            return [];
        });

        return $jwtSettings;
    }

    private function makeKeyCommand(SimpleJWTLoginSettings $jwtSettings, int $now = 1700000000): KeyCommand
    {
        return new class ($jwtSettings, $now) extends KeyCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            /** @var mixed */
            private $jwtSettings;
            /** @var integer */
            private $clock;

            public function __construct($jwtSettings, int $clock)
            {
                $this->jwtSettings = $jwtSettings;
                $this->clock = $clock;
            }

            protected function createSettings(): SimpleJWTLoginSettings
            {
                return $this->jwtSettings;
            }

            protected function now(): int
            {
                return $this->clock;
            }
        };
    }

    private function makeAuthCodeCommand(SimpleJWTLoginSettings $jwtSettings): AuthCodeCommand
    {
        return new class ($jwtSettings) extends AuthCodeCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            /** @var mixed */
            private $jwtSettings;

            public function __construct($jwtSettings)
            {
                $this->jwtSettings = $jwtSettings;
            }

            protected function createSettings(): SimpleJWTLoginSettings
            {
                return $this->jwtSettings;
            }
        };
    }
}
