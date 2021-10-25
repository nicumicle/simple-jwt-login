<?php
namespace SimpleJWTLoginTests\Modules\Settings;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\CorsSettings;
use SimpleJWTLogin\Modules\WordPressDataInterface;

class CorsSettingsTest extends TestCase
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

    public function testProperties()
    {
        $post = [
            'cors' => [
                'enabled' => '1',
                'allow_origin_enabled' => '1',
                'allow_origin' => 'test',
                'allow_methods_enabled' => '1',
                'allow_methods' => 'POST',
                'allow_headers_enabled' => '1',
                'allow_headers' => 'Header'
            ]
        ];

        $corsSettings = (new CorsSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost($post);
        $corsSettings->initSettingsFromPost();
        $corsSettings->validateSettings();
        $this->assertSame(
            true,
            $corsSettings->isCorsEnabled()
        );
        $this->assertSame(
            true,
            $corsSettings->isAllowOriginEnabled()
        );
        $this->assertSame(
            'test',
            $corsSettings->getAllowOrigin()
        );
        $this->assertSame(
            true,
            $corsSettings->isAllowHeadersEnabled()
        );
        $this->assertSame(
            'Header',
            $corsSettings->getAllowHeaders()
        );
        $this->assertSame(
            true,
            $corsSettings->isAllowMethodsEnabled()
        );
        $this->assertSame(
            'POST',
            $corsSettings->getAllowMethods()
        );
    }

    public function testValidation()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cors is enabled but no option is checked. Please check at least one option.');
        $corsSettings = (new CorsSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([])
            ->withPost(['cors' => [
                'enabled' => 1
            ]]);
        $corsSettings->initSettingsFromPost();
        $corsSettings->validateSettings();
    }
}
