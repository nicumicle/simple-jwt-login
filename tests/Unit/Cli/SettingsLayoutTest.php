<?php

namespace SimpleJwtLoginTests\Unit\Cli;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Cli\SettingsLayout;

class SettingsLayoutTest extends TestCase
{
    #[DataProvider('rejectedKeyProvider')]
    public function testAssertKeyRejects(string $key, string $message): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($message);
        (new SettingsLayout())->assertKey($key);
    }

    /** @return array<string,array{string,string}> */
    public static function rejectedKeyProvider(): array
    {
        return [
            'old flat key'   => ['allow_authentication', 'is now authorization.enabled'],
            'old flat group' => ['google.enabled', 'is now integrations.oauth.google.enabled'],
        ];
    }

    #[DataProvider('acceptedKeyProvider')]
    public function testAssertKeyAccepts(string $key): void
    {
        $this->expectNotToPerformAssertions();
        (new SettingsLayout())->assertKey($key);
    }

    /** @return array<string,array{string}> */
    public static function acceptedKeyProvider(): array
    {
        return [
            'section key'        => ['authorization.enabled'],
            'nested section key' => ['general.cli.enabled'],
            'unchanged group'    => ['cors.enabled'],
            'section with items' => ['webhooks.enabled'],
        ];
    }

    #[DataProvider('notWritableProvider')]
    public function testAssertWritableRejects(string $key, array $settings): void
    {
        $this->expectException(Exception::class);
        (new SettingsLayout())->assertWritable($key, $settings);
    }

    /** @return array<string,array{string,array<string,mixed>}> */
    public static function notWritableProvider(): array
    {
        return [
            'unknown flat key' => ['typo', ['typo' => '1']],
            'list'             => ['webhooks', ['webhooks' => ['a']]],
            'schema version'   => ['_schema_version', ['_schema_version' => 1]],
        ];
    }

    #[DataProvider('rejectedImportProvider')]
    public function testPrepareImportRejects(bool $merge, array $imported): void
    {
        $this->expectException(Exception::class);
        (new SettingsLayout())->prepareImport($imported, $merge);
    }

    /** @return array<string,array{bool,array<string,mixed>}> */
    public static function rejectedImportProvider(): array
    {
        return [
            'sections without version' => [false, ['authorization' => ['enabled' => '1']]],
            'merge of a flat file'     => [true, ['allow_authentication' => '1']],
        ];
    }

    #[DataProvider('keptImportProvider')]
    public function testPrepareImportKeepsSectionedSettings(bool $merge, array $imported): void
    {
        $this->assertSame($imported, (new SettingsLayout())->prepareImport($imported, $merge));
    }

    /** @return array<string,array{bool,array<string,mixed>}> */
    public static function keptImportProvider(): array
    {
        return [
            'file with version'  => [false, ['_schema_version' => 2, 'authorization' => ['enabled' => '1']]],
            'merge partial file' => [true, ['authorization' => ['enabled' => '1']]],
        ];
    }

    public function testPrepareImportMigratesFlatFile(): void
    {
        $settings = (new SettingsLayout())->prepareImport(['allow_authentication' => '1'], false);

        $this->assertArrayHasKey(SettingsLayout::VERSION_KEY, $settings);
        $this->assertSame('1', $settings['authorization']['enabled']);
    }

    public function testNormalizeMigratesFlatSettings(): void
    {
        $settings = (new SettingsLayout())->normalize(['allow_authentication' => '1']);

        $this->assertSame('1', $settings['authorization']['enabled']);
    }
}
