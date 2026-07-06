<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\ApiKeysSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class ApiKeysSettingsTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface
     */
    private $wordPressDataMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressDataMock = $this->createStub(WordPressDataInterface::class);
        $this->wordPressDataMock->method('sanitizeTextField')->willReturnArgument(0);
    }

    private function buildSettings(array $settings = [])
    {
        return (new ApiKeysSettings())
            ->withWordPressData($this->wordPressDataMock)
            ->withSettings($settings);
    }

    public function testDefaultsWhenNoSettings()
    {
        $settings = $this->buildSettings();

        $this->assertFalse($settings->isEnabled());
        $this->assertSame(ApiKeysSettings::DEFAULT_HEADER_NAME, $settings->getHeaderName());
        $this->assertFalse($settings->isUserApiKeysEnabled());
    }

    public function testIsEnabledReturnsTrueWhenSet()
    {
        $settings = $this->buildSettings([
            'api_keys' => ['enabled' => true],
        ]);

        $this->assertTrue($settings->isEnabled());
    }

    public function testIsEnabledReturnsFalseWhenExplicitlyFalse()
    {
        $settings = $this->buildSettings([
            'api_keys' => ['enabled' => false],
        ]);

        $this->assertFalse($settings->isEnabled());
    }

    public function testGetHeaderNameReturnsStoredValue()
    {
        $settings = $this->buildSettings([
            'api_keys' => ['header_name' => 'X-Custom-Key'],
        ]);

        $this->assertSame('X-Custom-Key', $settings->getHeaderName());
    }

    public function testGetHeaderNameFallsBackToDefault()
    {
        $settings = $this->buildSettings([
            'api_keys' => ['header_name' => '   '],
        ]);

        $this->assertSame(ApiKeysSettings::DEFAULT_HEADER_NAME, $settings->getHeaderName());
    }

    public function testIsUserApiKeysEnabledReturnsTrueWhenSet()
    {
        $settings = $this->buildSettings([
            'api_keys' => ['allow_user_api_keys' => true],
        ]);

        $this->assertTrue($settings->isUserApiKeysEnabled());
    }

    public function testIsUserApiKeysEnabledReturnsFalseByDefault()
    {
        $settings = $this->buildSettings([
            'api_keys' => [],
        ]);

        $this->assertFalse($settings->isUserApiKeysEnabled());
    }

    public function testIsUserApiKeysEnabledReturnsFalseWhenExplicitlyFalse()
    {
        $settings = $this->buildSettings([
            'api_keys' => ['allow_user_api_keys' => false],
        ]);

        $this->assertFalse($settings->isUserApiKeysEnabled());
    }

    public function testInitSettingsFromPostSetsAllFields()
    {
        $post = [
            'api_keys' => [
                'enabled'            => '1',
                'header_name'        => 'X-My-Key',
                'allow_user_api_keys' => '1',
            ],
        ];

        $settings = (new ApiKeysSettings())
            ->withWordPressData($this->wordPressDataMock)
            ->withSettings([])
            ->withPost($post);

        $settings->initSettingsFromPost();
        $result = $settings->getSettings();

        $this->assertTrue((bool) $result['api_keys']['enabled']);
        $this->assertSame('X-My-Key', $result['api_keys']['header_name']);
        $this->assertTrue((bool) $result['api_keys']['allow_user_api_keys']);
    }

    public function testInitSettingsFromPostDefaultsUserApiKeysToFalseWhenMissing()
    {
        $post = [
            'api_keys' => [
                'enabled' => '1',
            ],
        ];

        $settings = (new ApiKeysSettings())
            ->withWordPressData($this->wordPressDataMock)
            ->withSettings([])
            ->withPost($post);

        $settings->initSettingsFromPost();
        $result = $settings->getSettings();

        $this->assertFalse((bool) $result['api_keys']['allow_user_api_keys']);
    }
}
