<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\HooksSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;

class HooksSettingsTest extends TestCase
{
    /**
     * @var WordPressDataInterface
     */
    private $wordPressData;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressData = $this->getMockBuilder(WordPressDataInterface::class)
            ->getMock();
        $this->wordPressData->method('sanitizeTextField')
            ->willReturnCallback(
                function ($parameter) {
                    return $parameter;
                }
            );
    }

    public function testGetProperties()
    {
        $post = [
            'enabled_hooks' => []
        ];
        $hooksSettings = (new HooksSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost($post);
        $hooksSettings->initSettingsFromPost();
        $hooksSettings->validateSettings();
        $this->assertSame(true, true);
        $this->assertSame(
            false,
            $hooksSettings->isHookEnable('my_hook')
        );
        $this->assertEquals([], $hooksSettings->getEnabledHooks());
        $this->assertSame(false, $hooksSettings->isHookEnable('tests'));
    }
}
