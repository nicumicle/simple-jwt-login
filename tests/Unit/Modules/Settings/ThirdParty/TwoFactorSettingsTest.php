<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings\ThirdParty;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\ThirdParty\TwoFactorSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class TwoFactorSettingsTest extends TestCase
{
    private function make(): TwoFactorSettings
    {
        return new TwoFactorSettings();
    }

    private function wpData(): WordPressDataInterface
    {
        $stub = $this->createStub(WordPressDataInterface::class);
        $stub->method('sanitizeTextField')->willReturnArgument(0);
        return $stub;
    }

    public function testGetGroup(): void
    {
        $this->assertSame('two_factor', $this->make()->getGroup());
    }

    public function testIsEnabledDefaultsFalse(): void
    {
        $this->assertFalse($this->make()->isEnabled());
    }

    public function testIsEnabledTrue(): void
    {
        $this->assertTrue($this->make()->withSettings(['enabled' => true])->isEnabled());
    }

    public function testIsEnabledFalseWhenZero(): void
    {
        $this->assertFalse($this->make()->withSettings(['enabled' => 0])->isEnabled());
    }

    public function testGetInterimTtlDefault(): void
    {
        $this->assertSame(TwoFactorSettings::INTERIM_TTL_DEFAULT, $this->make()->getInterimTtl());
    }

    public function testGetInterimTtlFromSettings(): void
    {
        $this->assertSame(10, $this->make()->withSettings(['interim_ttl' => 10])->getInterimTtl());
    }

    public function testGetInterimTtlZeroFallsBackToDefault(): void
    {
        $this->assertSame(
            TwoFactorSettings::INTERIM_TTL_DEFAULT,
            $this->make()->withSettings(['interim_ttl' => 0])->getInterimTtl()
        );
    }

    public function testGetInterimTtlNegativeFallsBackToDefault(): void
    {
        $this->assertSame(
            TwoFactorSettings::INTERIM_TTL_DEFAULT,
            $this->make()->withSettings(['interim_ttl' => -1])->getInterimTtl()
        );
    }

    public function testWithSettingsReturnsThis(): void
    {
        $settings = $this->make();
        $this->assertSame($settings, $settings->withSettings([]));
    }

    public function testProcessPostEnabled(): void
    {
        $settings = $this->make();
        $result   = $settings->processPost(['two_factor' => ['enabled' => '1', 'interim_ttl' => '8']], $this->wpData());

        $this->assertTrue($result['enabled']);
        $this->assertSame(8, $result['interim_ttl']);
    }

    public function testProcessPostDisabled(): void
    {
        $settings = $this->make();
        $result   = $settings->processPost(['two_factor' => []], $this->wpData());

        $this->assertFalse($result['enabled']);
        $this->assertSame(0, $result['interim_ttl']);
    }
}
