<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\HooksSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class HooksSettingsTest extends TestCase
{
    /**
     * @var WordPressDataInterface
     */
    private $wordPressData;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressData = $this->createStub(WordPressDataInterface::class);
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
        $this->assertFalse($hooksSettings->isHookEnabled('my_hook'));
        $this->assertSame([], $hooksSettings->getEnabledHooks());
        $this->assertFalse($hooksSettings->isHookEnabled('tests'));
    }
}
