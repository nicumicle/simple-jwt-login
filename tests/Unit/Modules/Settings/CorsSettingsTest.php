<?php

namespace SimpleJwtLoginTests\Unit\Modules\Settings;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\CorsSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;

class CorsSettingsTest extends TestCase
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

    public function testGetAllowOriginDefaultsToEmptyString()
    {
        $corsSettings = (new CorsSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings([]);
        $this->assertSame('', $corsSettings->getAllowOrigin());
    }

    /**
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public static function allowedOriginsProvider()
    {
        return [
            'empty value' => ['', []],
            'single origin' => ['https://example.com', ['https://example.com']],
            'comma separated' => [
                'https://example.com, http://localhost:5173,capacitor://localhost',
                ['https://example.com', 'http://localhost:5173', 'capacitor://localhost'],
            ],
            'space and newline separated' => [
                "https://example.com https://app.example.com\nhttp://localhost",
                ['https://example.com', 'https://app.example.com', 'http://localhost'],
            ],
            'trailing slash removed' => ['https://example.com/', ['https://example.com']],
            'wildcard' => ['*', ['*']],
        ];
    }

    /**
     * @param string $allowOrigin
     * @param array<int, string> $expected
     */
    #[DataProvider('allowedOriginsProvider')]
    public function testGetAllowedOrigins($allowOrigin, $expected)
    {
        $corsSettings = (new CorsSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings(['cors' => ['allow_origin' => $allowOrigin]]);

        $this->assertSame($expected, $corsSettings->getAllowedOrigins());
    }

    /**
     * @return array<string, array{0: string, 1: string|null, 2: string|null}>
     */
    public static function resolveAllowOriginProvider()
    {
        $list = 'https://example.com, http://localhost:5173, capacitor://localhost';

        return [
            'listed origin is echoed' => [$list, 'http://localhost:5173', 'http://localhost:5173'],
            'another listed origin is echoed' => [$list, 'capacitor://localhost', 'capacitor://localhost'],
            'match is case insensitive' => [$list, 'HTTPS://EXAMPLE.COM', 'HTTPS://EXAMPLE.COM'],
            'unlisted origin is rejected' => [$list, 'https://evil.example', null],
            'prefix of a listed origin is rejected' => [$list, 'https://example.com.evil.test', null],
            'missing request origin is rejected' => [$list, null, null],
            'wildcard echoes the request origin' => ['*', 'http://localhost:5173', 'http://localhost:5173'],
            'wildcard without request origin' => ['*', null, '*'],
            'wildcard inside a list' => ['https://example.com, *', 'https://other.test', 'https://other.test'],
            'nothing configured' => ['', 'https://example.com', null],
        ];
    }

    /**
     * @param string $allowOrigin
     * @param string|null $requestOrigin
     * @param string|null $expected
     */
    #[DataProvider('resolveAllowOriginProvider')]
    public function testResolveAllowOrigin($allowOrigin, $requestOrigin, $expected)
    {
        $corsSettings = (new CorsSettings())
            ->withWordPressData($this->wordPressData)
            ->withSettings(['cors' => ['allow_origin' => $allowOrigin]]);

        $this->assertSame($expected, $corsSettings->resolveAllowOrigin($requestOrigin));
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
