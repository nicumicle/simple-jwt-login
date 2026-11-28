<?php

namespace SimpleJwtLoginTests\Unit\Cli;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Cli\CliRegistrar;
use SimpleJWTLogin\Modules\Settings\GeneralSettings;
use WP_CLI;

class CliRegistrarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        WP_CLI::reset();
    }

    #[DataProvider('registerProvider')]
    public function testRegister(bool $isWpCli, ?bool $enabled, bool $addonActive, array $expected): void
    {
        $this->makeRegistrar($isWpCli, $enabled, $addonActive)->register();

        $this->assertSame($expected, WP_CLI::$commands);
    }

    /** @return array<string,array{bool,bool|null,bool,array<string,string>}> */
    public static function registerProvider(): array
    {
        $commands = CliRegistrar::commands();

        return [
            'never saved'                => [true, null, false, $commands],
            'enabled'                    => [true, true, false, $commands],
            'disabled'                   => [true, false, false, []],
            'never saved, add-on active' => [true, null, true, []],
            'enabled, add-on active'     => [true, true, true, []],
            'not a WP-CLI call'          => [false, true, false, []],
        ];
    }

    #[DataProvider('isEnabledProvider')]
    public function testIsEnabled(?bool $enabled, bool $addonActive, bool $expected): void
    {
        $this->assertSame($expected, $this->makeRegistrar(true, $enabled, $addonActive)->isEnabled());
    }

    /** @return array<string,array{bool|null,bool,bool}> */
    public static function isEnabledProvider(): array
    {
        return [
            'never saved'                => [null, false, true],
            'never saved, add-on active' => [null, true, false],
            'enabled, add-on active'     => [true, true, true],
            'disabled'                   => [false, false, false],
        ];
    }

    private function makeRegistrar(bool $isWpCli, ?bool $enabled, bool $addonActive): CliRegistrar
    {
        $stored = $enabled === null ? [] : ['general' => ['cli' => ['enabled' => $enabled]]];
        $settings = (new GeneralSettings())->withSettings($stored);

        return new class ($settings, $isWpCli, $addonActive) extends CliRegistrar {
            /** @var boolean */
            private $wpCli;
            /** @var boolean */
            private $addon;

            public function __construct(GeneralSettings $settings, bool $wpCli, bool $addon)
            {
                parent::__construct($settings);
                $this->wpCli = $wpCli;
                $this->addon = $addon;
            }

            public function isAddonActive()
            {
                return $this->addon;
            }

            protected function isWpCli()
            {
                return $this->wpCli;
            }
        };
    }

    public function testAddonIsNotActiveByDefault(): void
    {
        $this->assertFalse((new CliRegistrar(new GeneralSettings()))->isAddonActive());
    }

    public function testEveryCommandClassExists(): void
    {
        foreach (CliRegistrar::commands() as $name => $class) {
            $this->assertTrue(class_exists($class), $name . ' => ' . $class);
        }
    }
}
