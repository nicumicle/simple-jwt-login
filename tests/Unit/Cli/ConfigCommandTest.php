<?php

namespace SimpleJwtLoginTests\Unit\Cli;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Cli\ConfigCommand;
use SimpleJWTLogin\Cli\SettingsLayout;
use WP_CLI;

class ConfigCommandTest extends TestCase
{
    /** @var array<string,mixed> */
    private $sampleSettings = [
        'allow_authentication' => '1',
        'jwt_algorithm'        => 'HS256',
        'decryption_key'       => 'secret',
        'jwt_auth_ttl'         => 60,
        'auth_password_base64' => false,
        'jwt_payload'          => ['iat', 'exp', 'id'],
        'cors'                 => [
            'enabled'      => '1',
            'allow_origin' => '*',
        ],
        'protect_endpoints'    => [
            'enabled' => '0',
            'protect' => [],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        WP_CLI::reset();
    }

    // -------------------------------------------------------------------------
    // getNestedValue
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('getNestedValueFoundProvider')]
    public function testGetNestedValueFound(string $key, string $expected): void
    {
        /** @var object $cmd */
        $cmd = $this->makeInspectable();
        $this->assertSame($expected, $cmd->getNestedValue($this->sampleSettings, $key));
    }

    /** @return array<string,array{string,string}> */
    public static function getNestedValueFoundProvider(): array
    {
        return [
            'top level'    => ['allow_authentication', '1'],
            'dot notation' => ['cors.enabled', '1'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getNestedValueNullProvider')]
    public function testGetNestedValueReturnsNull(string $key): void
    {
        /** @var object $cmd */
        $cmd = $this->makeInspectable();
        $this->assertNull($cmd->getNestedValue($this->sampleSettings, $key));
    }

    /** @return array<string,array{string}> */
    public static function getNestedValueNullProvider(): array
    {
        return [
            'missing top level' => ['nonexistent'],
            'missing group'     => ['nonexistent.key'],
            'missing sub key'   => ['cors.nonexistent'],
        ];
    }

    // -------------------------------------------------------------------------
    // setNestedValue
    // -------------------------------------------------------------------------

    public function testSetNestedValueTopLevel(): void
    {
        /** @var object $cmd */
        $cmd     = $this->makeInspectable();
        $updated = $cmd->setNestedValue($this->sampleSettings, 'allow_authentication', '0');
        $this->assertSame('0', $updated['allow_authentication']);
        // Original unchanged
        $this->assertSame('1', $this->sampleSettings['allow_authentication']);
    }

    public function testSetNestedValueDotNotation(): void
    {
        /** @var object $cmd */
        $cmd     = $this->makeInspectable();
        $updated = $cmd->setNestedValue($this->sampleSettings, 'cors.enabled', '0');
        $this->assertSame('0', $updated['cors']['enabled']);
        $this->assertSame('*', $updated['cors']['allow_origin']);
    }

    public function testSetNestedValueCreatesNewGroup(): void
    {
        /** @var object $cmd */
        $cmd     = $this->makeInspectable();
        $updated = $cmd->setNestedValue($this->sampleSettings, 'newgroup.newkey', 'val');
        $this->assertSame('val', $updated['newgroup']['newkey']);
    }

    public function testSetNestedValueOverwritesScalarGroupWithArray(): void
    {
        $settings = ['key' => 'scalar'];
        /** @var object $cmd */
        $cmd      = $this->makeInspectable();
        $updated  = $cmd->setNestedValue($settings, 'key.sub', 'val');
        $this->assertSame('val', $updated['key']['sub']);
    }

    // -------------------------------------------------------------------------
    // castValue
    // -------------------------------------------------------------------------

    /**
     * @param mixed $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('castValueExplicitProvider')]
    public function testCastValueExplicit(string $raw, string $type, $expected): void
    {
        /** @var object $cmd */
        $cmd = $this->makeInspectable();
        $this->assertSame($expected, $cmd->castValue($raw, $type, null));
    }

    /** @return array<string,array{string,string,mixed}> */
    public static function castValueExplicitProvider(): array
    {
        return [
            'int'    => ['42', 'int', 42],
            'string' => ['hello', 'string', 'hello'],
            'json'   => ['["iat","exp"]', 'json', ['iat', 'exp']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('boolTrueProvider')]
    public function testCastValueExplicitBoolTrue(string $raw): void
    {
        /** @var object $cmd */
        $cmd = $this->makeInspectable();
        $this->assertTrue($cmd->castValue($raw, 'bool', null));
    }

    /** @return array<array<string>> */
    public static function boolTrueProvider(): array
    {
        return [['1'], ['true'], ['TRUE'], ['yes'], ['YES'], ['on'], ['ON']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('boolFalseProvider')]
    public function testCastValueExplicitBoolFalse(string $raw): void
    {
        /** @var object $cmd */
        $cmd = $this->makeInspectable();
        $this->assertFalse($cmd->castValue($raw, 'bool', null));
    }

    /** @return array<array<string>> */
    public static function boolFalseProvider(): array
    {
        return [['0'], ['false'], ['no'], ['off'], ['anything']];
    }

    public function testCastValueExplicitJsonThrowsOnInvalidJson(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid JSON value');

        /** @var object $cmd */
        $cmd = $this->makeInspectable();
        $cmd->castValue('{not-valid', 'json', null);
    }

    /**
     * @param mixed $current
     * @param mixed $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('castValueAutoProvider')]
    public function testCastValueAuto(string $raw, $current, $expected): void
    {
        /** @var object $cmd */
        $cmd = $this->makeInspectable();
        $this->assertSame($expected, $cmd->castValue($raw, 'auto', $current));
    }

    /** @return array<string,array{string,mixed,mixed}> */
    public static function castValueAutoProvider(): array
    {
        return [
            'infers bool true'    => ['yes', false, true],
            'infers bool false'   => ['no', true, false],
            'infers int'          => ['99', 60, 99],
            'infers array (json)' => ['["iat","exp"]', ['iat'], ['iat', 'exp']],
            'null current → string' => ['myval', null, 'myval'],
        ];
    }

    public function testCastValueAutoThrowsForArrayCurrentWhenNotJson(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The current value is an array');

        /** @var object $cmd */
        $cmd = $this->makeInspectable();
        $cmd->castValue('not-json', 'auto', ['iat']);
    }

    // -------------------------------------------------------------------------
    // flattenSettings
    // -------------------------------------------------------------------------

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('flattenSettingsProvider')]
    public function testFlattenSettings(array $input, array $expected): void
    {
        /** @var object $cmd */
        $cmd = $this->makeInspectable();
        $this->assertSame($expected, $cmd->flattenSettings($input));
    }

    /** @return array<string,array{array<string,mixed>,array<string,mixed>}> */
    public static function flattenSettingsProvider(): array
    {
        return [
            'top level scalars'              => [
                ['foo' => 'bar', 'num' => 1],
                ['foo' => 'bar', 'num' => 1],
            ],
            'associative sub-array expanded' => [
                ['cors' => ['enabled' => '1', 'allow_origin' => '*']],
                ['cors.enabled' => '1', 'cors.allow_origin' => '*'],
            ],
            'indexed array serialised'       => [
                ['jwt_payload' => ['iat', 'exp']],
                ['jwt_payload' => '["iat","exp"]'],
            ],
            'empty array serialised'         => [
                ['items' => []],
                ['items' => '[]'],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // get command
    // -------------------------------------------------------------------------

    /**
     * @param string[] $args
     * @param array<string,string> $assocArgs
     * @param string[] $expectedLines
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getCommandSuccessProvider')]
    public function testGetCommandSuccess(array $args, array $assocArgs, array $expectedLines): void
    {
        $cmd = $this->makeTestable($this->sampleSettings);
        $cmd->get($args, $assocArgs);
        $this->assertSame($expectedLines, WP_CLI::$lines);
    }

    /** @return array<string,array{string[],array<string,string>,string[]}> */
    public static function getCommandSuccessProvider(): array
    {
        return [
            'top level key'   => [['allow_authentication'], [], ['1']],
            'dot notation'    => [['cors.enabled'], [], ['1']],
            'array as json'   => [['jwt_payload'], ['format' => 'json'], ['["iat","exp","id"]']],
        ];
    }

    /**
     * @param string[] $args
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getCommandErrorProvider')]
    public function testGetCommandError(array $args, string $expectedError): void
    {
        $cmd = $this->makeTestable($this->sampleSettings);
        $cmd->get($args, []);
        $this->assertContains($expectedError, WP_CLI::$errors);
    }

    /** @return array<string,array{string[],string}> */
    public static function getCommandErrorProvider(): array
    {
        return [
            'missing key' => [['nonexistent'], 'Setting "nonexistent" not found.'],
            'no args'     => [[], 'Please provide a setting key.'],
        ];
    }

    // -------------------------------------------------------------------------
    // set command
    // -------------------------------------------------------------------------

    public function testSetCommandUpdatesTopLevelValue(): void
    {
        $cmd = $this->makeTestable($this->sampleSettings);
        $cmd->set(['allow_authentication', '0'], []);

        $this->assertSame(['allow_authentication' => '0'] + $this->sampleSettings, $cmd->lastSaved);
        $this->assertContains('Updated "allow_authentication".', WP_CLI::$successes);
    }

    public function testSetCommandUpdatesNestedValue(): void
    {
        $cmd = $this->makeTestable($this->sampleSettings);
        $cmd->set(['cors.enabled', '0'], []);

        $this->assertSame('0', $cmd->lastSaved['cors']['enabled']);
    }

    public function testSetCommandRespectsBoolType(): void
    {
        $cmd = $this->makeTestable($this->sampleSettings);
        $cmd->set(['cors.enabled', 'true'], ['type' => 'bool']);

        $this->assertTrue($cmd->lastSaved['cors']['enabled']);
    }

    /**
     * @param string[] $args
     * @param array<string,string> $assocArgs
     * @param mixed $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('setCommandTopLevelTypeProvider')]
    public function testSetCommandRespectsTopLevelType(array $args, array $assocArgs, string $key, $expected): void
    {
        $cmd = $this->makeTestable($this->sampleSettings);
        $cmd->set($args, $assocArgs);
        $this->assertSame($expected, $cmd->lastSaved[$key]);
    }

    /** @return array<string,array{string[],array<string,string>,string,mixed}> */
    public static function setCommandTopLevelTypeProvider(): array
    {
        return [
            'int type'  => [['jwt_auth_ttl', '120'], ['type' => 'int'], 'jwt_auth_ttl', 120],
            'json type' => [
                ['jwt_payload', '["iat","exp","email"]'],
                ['type' => 'json'],
                'jwt_payload',
                ['iat', 'exp', 'email'],
            ],
        ];
    }

    public function testSetCommandErrorOnMissingArgs(): void
    {
        $cmd = $this->makeTestable($this->sampleSettings);
        $cmd->set(['onlykey'], []);

        $this->assertContains(
            'Please provide both a key and a value: wp jwt config set <key> <value>',
            WP_CLI::$errors
        );
        $this->assertNull($cmd->lastSaved);
    }

    public function testSetCommandErrorOnInvalidJson(): void
    {
        $cmd = $this->makeTestable($this->sampleSettings);
        $cmd->set(['jwt_payload', '{bad'], ['type' => 'json']);

        $this->assertNotEmpty(WP_CLI::$errors);
        $this->assertNull($cmd->lastSaved);
    }

    // -------------------------------------------------------------------------
    // list command
    // -------------------------------------------------------------------------

    public function testListCommandJsonFormat(): void
    {
        $cmd = $this->makeTestable($this->sampleSettings);
        $cmd->listSettings([], ['format' => 'json']);

        $this->assertCount(1, WP_CLI::$lines);
        $decoded = json_decode(WP_CLI::$lines[0], true);
        $this->assertSame($this->sampleSettings, $decoded);
    }

    public function testListCommandTextFormatOutputsLines(): void
    {
        $cmd = $this->makeTestable($this->sampleSettings);
        $cmd->listSettings([], []);

        $output = implode("\n", WP_CLI::$lines);
        $this->assertStringContainsString('allow_authentication', $output);
        $this->assertStringContainsString('cors.enabled', $output);
        $this->assertStringContainsString('jwt_algorithm', $output);
    }

    public function testListCommandInvalidFormatOutputsError(): void
    {
        $cmd = $this->makeTestable($this->sampleSettings);
        $cmd->listSettings([], ['format' => 'xml']);

        $this->assertNotEmpty(WP_CLI::$errors);
        $this->assertStringContainsString('xml', WP_CLI::$errors[0]);
    }

    public function testGetCommandInvalidFormatOutputsError(): void
    {
        $cmd = $this->makeTestable($this->sampleSettings);
        $cmd->get(['allow_authentication'], ['format' => 'table']);

        $this->assertNotEmpty(WP_CLI::$errors);
        $this->assertStringContainsString('table', WP_CLI::$errors[0]);
    }

    public function testListCommandEmptySettings(): void
    {
        $cmd = $this->makeTestable([]);
        $cmd->listSettings([], []);

        $this->assertEmpty(WP_CLI::$lines);
    }

    // -------------------------------------------------------------------------
    // Concrete I/O methods (no WordPress DB needed)
    // -------------------------------------------------------------------------

    public function testReadFileReturnsFalseForNonExistentPath(): void
    {
        /** @var object $cmd */
        $cmd    = $this->makeConcreteProxy();
        $result = $cmd->readFilePublic('/nonexistent/path/that/does/not/exist.json');
        $this->assertFalse($result);
    }

    public function testReadFileReturnsContentsForExistingFile(): void
    {
        $tmp = (string) tempnam(sys_get_temp_dir(), 'sjl_test_');
        file_put_contents($tmp, '{"key":"value"}');

        /** @var object $cmd */
        $cmd    = $this->makeConcreteProxy();
        $result = $cmd->readFilePublic($tmp);

        unlink($tmp);

        $this->assertSame('{"key":"value"}', $result);
    }

    public function testWriteFileCreatesFileWithContent(): void
    {
        $tmp = (string) tempnam(sys_get_temp_dir(), 'sjl_test_');

        /** @var object $cmd */
        $cmd = $this->makeConcreteProxy();
        $cmd->writeFilePublic($tmp, '{"written":true}');
        $content = (string) file_get_contents($tmp);

        unlink($tmp);

        $this->assertSame('{"written":true}', $content);
    }

    public function testConfirmActionCallsWpCliConfirm(): void
    {
        /** @var object $cmd */
        $cmd = $this->makeConcreteProxy();
        $cmd->confirmActionPublic('Are you sure?');

        $this->assertContains('confirm:Are you sure?', WP_CLI::$lines);
    }

    public function testLoadSettingsReturnsEmptyArrayWhenNothingStored(): void
    {
        /** @var object $cmd */
        $cmd    = $this->makeTestable([]);
        $result = $cmd->loadSettingsPublic();
        $this->assertSame([], $result);
    }

    public function testSaveSettingsCallsUpdateOrAddOption(): void
    {
        /** @var object $cmd */
        $cmd = $this->makeConcreteProxy();
        $cmd->saveSettingsPublic(['allow_authentication' => '1']);
        $this->assertTrue(true); // reached without exception
    }

    // -------------------------------------------------------------------------
    // performValidation / --force flag
    // -------------------------------------------------------------------------

    public function testSetCommandCallsValidationAndFailsWithError(): void
    {
        $cmd = $this->makeTestableWithValidation(
            $this->sampleSettings,
            new Exception('Validation failed: Route namespace could not be empty.')
        );
        $cmd->set(['allow_authentication', '0'], []);

        $this->assertContains(
            'Validation failed: Validation failed: Route namespace could not be empty.',
            WP_CLI::$errors
        );
        $this->assertNull($cmd->lastSaved);
    }

    public function testSetCommandForceSkipsValidation(): void
    {
        $cmd = $this->makeTestableWithValidation(
            $this->sampleSettings,
            new Exception('Validation would fail')
        );
        $cmd->set(['allow_authentication', '0'], ['force' => true]);

        $this->assertNotNull($cmd->lastSaved);
        $this->assertSame('0', $cmd->lastSaved['allow_authentication']);
    }

    public function testImportCommandCallsValidationAndFailsWithError(): void
    {
        $json = (string) json_encode(['jwt_algorithm' => 'RS256']);
        $cmd  = $this->makeTestableWithValidationAndFile(
            $this->sampleSettings,
            $json,
            new Exception('Validation failed: Route namespace could not be empty.')
        );
        $cmd->import(['/cfg.json'], ['yes' => true]);

        $this->assertContains(
            'Validation failed: Validation failed: Route namespace could not be empty.',
            WP_CLI::$errors
        );
        $this->assertNull($cmd->lastSaved);
    }

    public function testImportCommandForceSkipsValidation(): void
    {
        $json = (string) json_encode(['jwt_algorithm' => 'RS256']);
        $cmd  = $this->makeTestableWithValidationAndFile(
            $this->sampleSettings,
            $json,
            new Exception('Validation would fail')
        );
        $cmd->import(['/cfg.json'], ['force' => true, 'yes' => true]);

        $this->assertNotNull($cmd->lastSaved);
        $this->assertSame('RS256', $cmd->lastSaved['jwt_algorithm']);
    }

    public function testPerformValidationProxyCallsMethod(): void
    {
        $cmd = new class extends ConfigCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            /** @var array<string,mixed>|null */
            public $validated = null;

            public function __construct()
            {
            }

            protected function performValidation(array $settings): void
            {
                $this->validated = $settings;
            }

            protected function createSettingsLayout(): SettingsLayout
            {
                return new FlatSettingsLayout();
            }

            protected function loadSettings(): array
            {
                return ['allow_authentication' => '1'];
            }

            protected function saveSettings(array $settings): void
            {
            }
        };

        $cmd->set(['allow_authentication', '0'], []);
        $this->assertSame(['allow_authentication' => '0'], $cmd->validated);
    }

    // -------------------------------------------------------------------------
    // computeDiff
    // -------------------------------------------------------------------------

    /**
     * @param array<string,mixed> $old
     * @param array<string,mixed> $new
     * @param mixed               $expectedOld
     * @param mixed               $expectedNew
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('computeDiffStatusProvider')]
    public function testComputeDiffDetectsStatus(
        array $old,
        array $new,
        string $expectedStatus,
        string $expectedKey,
        $expectedOld,
        $expectedNew
    ): void {
        /** @var object $cmd */
        $cmd  = $this->makeInspectable();
        $diff = $cmd->computeDiff($old, $new);

        $this->assertCount(1, $diff);
        $this->assertSame($expectedStatus, $diff[0]['status']);
        $this->assertSame($expectedKey, $diff[0]['key']);
        $this->assertSame($expectedOld, $diff[0]['old']);
        $this->assertSame($expectedNew, $diff[0]['new']);
    }

    /** @return array<string,array{array<string,mixed>,array<string,mixed>,string,string,mixed,mixed}> */
    public static function computeDiffStatusProvider(): array
    {
        return [
            'added key'     => [
                [], ['allow_authentication' => '1'],
                'added', 'allow_authentication', null, '1',
            ],
            'removed key'   => [
                ['allow_authentication' => '1'], [],
                'removed', 'allow_authentication', '1', null,
            ],
            'changed key'   => [
                ['jwt_algorithm' => 'HS256'], ['jwt_algorithm' => 'RS256'],
                'changed', 'jwt_algorithm', 'HS256', 'RS256',
            ],
            'nested changed' => [
                ['cors' => ['enabled' => '0']], ['cors' => ['enabled' => '1']],
                'changed', 'cors.enabled', '0', '1',
            ],
        ];
    }

    public function testComputeDiffReturnsEmptyWhenIdentical(): void
    {
        /** @var object $cmd */
        $cmd  = $this->makeInspectable();
        $diff = $cmd->computeDiff($this->sampleSettings, $this->sampleSettings);

        $this->assertEmpty($diff);
    }

    // -------------------------------------------------------------------------
    // export command
    // -------------------------------------------------------------------------

    public function testExportPrintsToStdout(): void
    {
        $cmd = $this->makeTestable($this->sampleSettings);
        $cmd->export([], []);

        $this->assertCount(1, WP_CLI::$lines);
        $decoded = json_decode(WP_CLI::$lines[0], true);
        $this->assertSame($this->sampleSettings, $decoded);
    }

    public function testExportWritesToFile(): void
    {
        $written = null;
        $cmd     = $this->makeTestableWithFile(
            $this->sampleSettings,
            null,
            function (string $p, string $c) use (&$written) {
                $written = ['path' => $p, 'content' => $c];
            }
        );

        $cmd->export([], ['file' => '/tmp/jwt-export.json']);

        $this->assertNotNull($written);
        $this->assertSame('/tmp/jwt-export.json', $written['path']);
        $decoded = json_decode($written['content'], true);
        $this->assertSame($this->sampleSettings, $decoded);
        $this->assertStringContainsString('Configuration exported to', implode('', WP_CLI::$successes));
    }

    // -------------------------------------------------------------------------
    // import command
    // -------------------------------------------------------------------------

    public function testImportErrorOnNoArgs(): void
    {
        $cmd = $this->makeTestable($this->sampleSettings);
        $cmd->import([], []);

        $this->assertContains(
            'Please provide a file path: wp jwt config import <file>',
            WP_CLI::$errors
        );
    }

    public function testImportErrorWhenFileNotReadable(): void
    {
        $cmd = $this->makeTestableWithFile($this->sampleSettings, false);
        $cmd->import(['/nonexistent.json'], []);

        $this->assertContains('Cannot read file: /nonexistent.json', WP_CLI::$errors);
    }

    public function testImportErrorOnInvalidJson(): void
    {
        $cmd = $this->makeTestableWithFile($this->sampleSettings, '{not-json');
        $cmd->import(['/cfg.json'], []);

        $this->assertNotEmpty(WP_CLI::$errors);
        $this->assertStringContainsString('Invalid JSON', WP_CLI::$errors[0]);
    }

    public function testImportReportsNoChangesWhenIdentical(): void
    {
        $json = (string) json_encode($this->sampleSettings);
        $cmd  = $this->makeTestableWithFile($this->sampleSettings, $json);
        $cmd->import(['/cfg.json'], ['yes' => true]);

        $this->assertContains('No changes detected.', WP_CLI::$lines);
        $this->assertNull($cmd->lastSaved);
    }

    public function testImportDryRunShowsDiffWithoutSaving(): void
    {
        $modified                        = $this->sampleSettings;
        $modified['allow_authentication'] = '0';
        $json                            = (string) json_encode($modified);

        $cmd = $this->makeTestableWithFile($this->sampleSettings, $json);
        $cmd->import(['/cfg.json'], ['dry-run' => true]);

        $output = implode("\n", WP_CLI::$lines);
        $this->assertStringContainsString('allow_authentication', $output);
        $this->assertStringContainsString('Dry run', $output);
        $this->assertNull($cmd->lastSaved);
    }

    public function testImportReplacesSettings(): void
    {
        $newSettings = ['jwt_algorithm' => 'RS256', 'allow_authentication' => '1'];
        $json        = (string) json_encode($newSettings);

        $cmd = $this->makeTestableWithFile($this->sampleSettings, $json);
        $cmd->import(['/cfg.json'], ['yes' => true]);

        $this->assertSame($newSettings, $cmd->lastSaved);
        $this->assertStringContainsString('imported successfully', implode('', WP_CLI::$successes));
    }

    public function testImportMergePreservesExistingKeys(): void
    {
        $patch = ['jwt_algorithm' => 'RS256'];
        $json  = (string) json_encode($patch);

        $cmd = $this->makeTestableWithFile($this->sampleSettings, $json);
        $cmd->import(['/cfg.json'], ['merge' => true, 'yes' => true]);

        $this->assertSame('1', $cmd->lastSaved['allow_authentication']);
        $this->assertSame('RS256', $cmd->lastSaved['jwt_algorithm']);
    }

    public function testImportMergeReplacesLists(): void
    {
        $current = ['jwt_payload' => ['iat', 'exp', 'id'], 'ips' => ['a'], 'cors' => ['enabled' => true, 'x' => 1]];
        $patch = ['jwt_payload' => ['id'], 'ips' => [], 'cors' => ['x' => 2]];

        $cmd = $this->makeTestableWithFile($current, (string) json_encode($patch));
        $cmd->import(['/cfg.json'], ['merge' => true, 'yes' => true]);

        $this->assertSame(
            ['jwt_payload' => ['id'], 'ips' => [], 'cors' => ['enabled' => true, 'x' => 2]],
            $cmd->lastSaved
        );
    }

    #[\PHPUnit\Framework\Attributes\TestWith(['null'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['"text"'])]
    public function testImportRejectsNonObjectJson(string $json): void
    {
        $cmd = $this->makeTestableWithFile($this->sampleSettings, $json);
        $cmd->import(['/cfg.json'], ['yes' => true]);

        $this->assertSame(['The import file must contain a JSON object.'], WP_CLI::$errors);
    }

    public function testImportShowsDiffLinesForAddedChangedRemoved(): void
    {
        $target = [
            'jwt_algorithm' => 'RS256',  // changed
            'brand_new_key' => 'value',  // added
            // allow_authentication removed
        ];
        $json = (string) json_encode($target);

        $cmd = $this->makeTestableWithFile($this->sampleSettings, $json);
        $cmd->import(['/cfg.json'], ['yes' => true]);

        $output = implode("\n", WP_CLI::$lines);
        $this->assertStringContainsString('[~]', $output); // changed
        $this->assertStringContainsString('[+]', $output); // added
        $this->assertStringContainsString('[-]', $output); // removed
    }

    public function testImportDiffMasksSecrets(): void
    {
        $target = array_merge($this->sampleSettings, ['decryption_key' => 'new-signing-key']);

        $cmd = $this->makeTestableWithFile($this->sampleSettings, (string) json_encode($target));
        $cmd->import(['/cfg.json'], ['yes' => true, 'dry-run' => true]);

        $output = implode("\n", WP_CLI::$lines);
        $this->assertStringContainsString('[~] decryption_key', $output);
        $this->assertStringNotContainsString('new-signing-key', $output);
        $this->assertStringNotContainsString('secret ->', $output);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Subclass that exposes all protected pure-logic methods as public so they
     * can be called directly from test code without a WordPress environment.
     */
    private function makeInspectable(): ConfigCommand
    {
        return new class () extends ConfigCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            /** @param array<string,mixed> $settings */
            public function getNestedValue(array $settings, $key)
            {
                return parent::getNestedValue($settings, $key);
            }

            /** @param array<string,mixed> $settings */
            public function setNestedValue(array $settings, $key, $value)
            {
                return parent::setNestedValue($settings, $key, $value);
            }

            public function castValue($rawValue, $type, $current)
            {
                return parent::castValue($rawValue, $type, $current);
            }

            /** @param array<string,mixed> $settings */
            public function flattenSettings(array $settings)
            {
                return parent::flattenSettings($settings);
            }

            /**
             * @param array<string,mixed> $current
             * @param array<string,mixed> $target
             */
            public function computeDiff(array $current, array $target)
            {
                return parent::computeDiff($current, $target);
            }
        };
    }

    /**
     * Thin subclass that promotes protected I/O methods to public so they can
     * be called directly without a WordPress environment.
     *
     * @return object&ConfigCommand
     */
    private function makeConcreteProxy()
    {
        return new class () extends ConfigCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            /** @return string|false */
            public function readFilePublic(string $path)
            {
                return $this->readFile($path);
            }

            public function writeFilePublic(string $path, string $content): void
            {
                $this->writeFile($path, $content);
            }

            public function confirmActionPublic(string $question): void
            {
                $this->confirmAction($question);
            }

            public function loadSettingsPublic(): array
            {
                return $this->loadSettings();
            }

            public function saveSettingsPublic(array $settings): void
            {
                $this->saveSettings($settings);
            }
        };
    }

    /**
     * Subclass whose performValidation() throws, simulating a validation
     * failure. Used to test that --force skips validation and errors surface.
     *
     * @param array<string,mixed> $settings
     * @return ConfigCommand&object{lastSaved: array<string,mixed>|null}
     */
    private function makeTestableWithValidation(
        array $settings,
        Exception $validationException
    ): ConfigCommand {
        return new class ($settings, $validationException) extends ConfigCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            /** @var array<string,mixed> */
            private $mockSettings;
            /** @var Exception */
            private $validationException;
            /** @var array<string,mixed>|null */
            public $lastSaved = null;

            public function __construct(array $settings, Exception $exception)
            {
                $this->mockSettings       = $settings;
                $this->validationException = $exception;
            }

            protected function createSettingsLayout(): SettingsLayout
            {
                return new FlatSettingsLayout();
            }

            protected function loadSettings(): array
            {
                return $this->mockSettings;
            }

            protected function saveSettings(array $settings): void
            {
                $this->lastSaved = $settings;
            }

            //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
            protected function performValidation(array $settings): void
            {
                throw $this->validationException;
            }
        };
    }

    /**
     * Subclass with mocked file I/O and a throwing performValidation().
     *
     * @param array<string,mixed> $settings
     * @return ConfigCommand&object{lastSaved: array<string,mixed>|null}
     */
    private function makeTestableWithValidationAndFile(
        array $settings,
        string $fileContent,
        Exception $validationException
    ): ConfigCommand {
        return new class ($settings, $fileContent, $validationException) extends ConfigCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            /** @var array<string,mixed> */
            private $mockSettings;
            /** @var string */
            private $mockFileContent;
            /** @var Exception */
            private $validationException;
            /** @var array<string,mixed>|null */
            public $lastSaved = null;
            /** @var string */
            public $lastPath = '';

            public function __construct(array $settings, string $fileContent, Exception $exception)
            {
                $this->mockSettings       = $settings;
                $this->mockFileContent    = $fileContent;
                $this->validationException = $exception;
            }

            protected function createSettingsLayout(): SettingsLayout
            {
                return new FlatSettingsLayout();
            }

            protected function loadSettings(): array
            {
                return $this->mockSettings;
            }

            protected function saveSettings(array $settings): void
            {
                $this->lastSaved = $settings;
            }

            /** @return string|false */
            protected function readFile($path)
            {
                $this->lastPath = $path;
                return $this->mockFileContent;
            }

            //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
            protected function performValidation(array $settings): void
            {
                throw $this->validationException;
            }
        };
    }

    /**
     * Subclass with overridden loadSettings/saveSettings.
     *
     * @param array<string,mixed> $settings
     */
    private function makeTestable(array $settings)
    {
        return new class ($settings) extends ConfigCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            /** @var array<string,mixed> */
            private $mockSettings;
            /** @var array<string,mixed>|null */
            public $lastSaved = null;

            public function __construct(array $settings)
            {
                $this->mockSettings = $settings;
            }

            protected function createSettingsLayout(): SettingsLayout
            {
                return new FlatSettingsLayout();
            }

            protected function loadSettings(): array
            {
                return $this->mockSettings;
            }

            protected function saveSettings(array $settings): void
            {
                $this->lastSaved = $settings;
            }

            protected function performValidation(array $settings): void
            {
                // Validation is tested separately; auto-pass here
            }

            public function loadSettingsPublic(): array
            {
                return $this->loadSettings();
            }

            public function saveSettingsPublic(array $settings): void
            {
                $this->saveSettings($settings);
            }
        };
    }

    /**
     * Subclass that also mocks file I/O.
     *
     * @param array<string,mixed>  $settings
     * @param string|false|null    $fileContent Content returned by readFile (false = unreadable).
     * @param callable|null        $onWrite     Invoked as onWrite(path, content) when writeFile is called.
     * @return ConfigCommand&object{lastSaved: array<string,mixed>|null}
     */
    private function makeTestableWithFile(
        array $settings,
        $fileContent,
        ?callable $onWrite = null
    ): ConfigCommand {
        return new class ($settings, $fileContent, $onWrite) extends ConfigCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            /** @var array<string,mixed> */
            private $mockSettings;
            /** @var string|false|null */
            private $mockFileContent;
            /** @var callable|null */
            private $onWrite;
            /** @var array<string,mixed>|null */
            public $lastSaved = null;

            /** @param string|false|null $fileContent */
            public function __construct(array $settings, $fileContent, ?callable $onWrite)
            {
                $this->mockSettings    = $settings;
                $this->mockFileContent = $fileContent;
                $this->onWrite         = $onWrite;
            }

            protected function createSettingsLayout(): SettingsLayout
            {
                return new FlatSettingsLayout();
            }

            protected function loadSettings(): array
            {
                return $this->mockSettings;
            }

            protected function saveSettings(array $settings): void
            {
                $this->lastSaved = $settings;
            }

            /** @return string|false */
            //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
            protected function readFile($path)
            {
                return $this->mockFileContent;
            }

            protected function writeFile($path, $content)
            {
                if ($this->onWrite !== null) {
                    ($this->onWrite)($path, $content);
                }
            }

            protected function performValidation(array $settings): void
            {
                // Validation is tested separately; auto-pass here
            }
        };
    }
}
