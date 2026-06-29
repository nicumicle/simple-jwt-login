<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings\ThirdParty;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\ThirdParty\WooCommerceSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class WooCommerceSettingsTest extends TestCase
{
    private function make(): WooCommerceSettings
    {
        return new WooCommerceSettings();
    }

    private function wpData(): WordPressDataInterface
    {
        $stub = $this->createStub(WordPressDataInterface::class);
        $stub->method('sanitizeTextField')->willReturnArgument(0);
        return $stub;
    }

    public function testGetGroup(): void
    {
        $this->assertSame('woocommerce', $this->make()->getGroup());
    }

    #[DataProvider('enabledProvider')]
    public function testIsEnabled(array $settings, bool $expected): void
    {
        $this->assertSame($expected, $this->make()->withSettings($settings)->isEnabled());
    }

    public static function enabledProvider(): array
    {
        return [
            'default empty' => [[], false],
            'enabled true'  => [['enabled' => true], true],
            'enabled one'   => [['enabled' => 1], true],
            'enabled zero'  => [['enabled' => 0], false],
            'enabled false' => [['enabled' => false], false],
        ];
    }

    #[DataProvider('nonceProvider')]
    public function testIsStoreApiNonceDisabled(array $settings, bool $expected): void
    {
        $this->assertSame($expected, $this->make()->withSettings($settings)->isStoreApiNonceDisabled());
    }

    public static function nonceProvider(): array
    {
        return [
            'default empty'  => [[], false],
            'disabled true'  => [['store_api_disable_nonce' => true], true],
            'disabled one'   => [['store_api_disable_nonce' => 1], true],
            'disabled zero'  => [['store_api_disable_nonce' => 0], false],
            'only enabled'   => [['enabled' => true], false],
        ];
    }

    public function testWithSettingsReturnsThis(): void
    {
        $settings = $this->make();
        $this->assertSame($settings, $settings->withSettings([]));
    }

    #[DataProvider('processPostProvider')]
    public function testProcessPost(array $post, bool $expectedEnabled, bool $expectedNonce): void
    {
        $result = $this->make()->processPost($post, $this->wpData());
        $this->assertSame($expectedEnabled, $result['enabled']);
        $this->assertSame($expectedNonce, $result['store_api_disable_nonce']);
    }

    public static function processPostProvider(): array
    {
        return [
            'enabled only'        => [['woocommerce' => ['enabled' => '1']], true, false],
            'enabled with nonce'  => [['woocommerce' => ['enabled' => '1', 'store_api_disable_nonce' => '1']], true, true],
            'disabled'            => [['woocommerce' => ['enabled' => '0']], false, false],
            'missing slice'       => [[], false, false],
        ];
    }
}
