<?php

namespace SimpleJwtLoginTests\Unit\Routes\Handlers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\Settings\CorsSettings;
use SimpleJWTLogin\Routes\Handlers\CorsHandler;

class CorsHandlerTest extends TestCase
{
    const LIST_OF_ORIGINS = 'https://example.com, http://localhost:5173';

    public function testRegisterDoesNotSendHeadersImmediately()
    {
        $handler = $this->buildHandler(true, self::LIST_OF_ORIGINS, null, null, 'https://example.com');
        $handler->register();

        $this->assertSame([], $handler->sent);
        $this->assertSame([], $handler->removed);
    }

    public function testInvokeReturnsTheServedValueUnchanged()
    {
        $handler = $this->buildHandler(false, '');

        $this->assertTrue($handler(true));
        $this->assertFalse($handler(false));
    }

    /**
     * @return array<string, array{0: string, 1: string|null, 2: array<int, string>, 3: array<int, string>}>
     */
    public static function allowOriginProvider()
    {
        return [
            'listed origin is echoed' => [
                self::LIST_OF_ORIGINS,
                'http://localhost:5173',
                ['Access-Control-Allow-Origin: http://localhost:5173'],
                [],
            ],
            'unlisted origin removes the WordPress reflected header' => [
                self::LIST_OF_ORIGINS,
                'https://evil.example',
                [],
                ['Access-Control-Allow-Origin', 'Access-Control-Allow-Credentials'],
            ],
            'missing origin removes the header' => [
                self::LIST_OF_ORIGINS,
                null,
                [],
                ['Access-Control-Allow-Origin', 'Access-Control-Allow-Credentials'],
            ],
            'wildcard echoes the request origin' => [
                '*',
                'https://any.example',
                ['Access-Control-Allow-Origin: https://any.example'],
                [],
            ],
            'wildcard without request origin sends star' => [
                '*',
                null,
                ['Access-Control-Allow-Origin: *'],
                [],
            ],
            'empty configuration leaves the WordPress header untouched' => [
                '',
                'https://evil.example',
                [],
                [],
            ],
        ];
    }

    /**
     * @param string $allowOrigin
     * @param string|null $requestOrigin
     * @param array<int, string> $expectedSent
     * @param array<int, string> $expectedRemoved
     */
    #[DataProvider('allowOriginProvider')]
    public function testAllowOriginHeader($allowOrigin, $requestOrigin, $expectedSent, $expectedRemoved)
    {
        $handler = $this->buildHandler(true, $allowOrigin, null, null, $requestOrigin);
        $handler(false);

        $this->assertSame($expectedSent, $handler->sent);
        $this->assertSame($expectedRemoved, $handler->removed);
    }

    public function testAllowOriginDisabledSendsNothing()
    {
        $handler = $this->buildHandler(false, self::LIST_OF_ORIGINS, null, null, 'https://evil.example');
        $handler(false);

        $this->assertSame([], $handler->sent);
        $this->assertSame([], $handler->removed);
    }

    public function testCrlfIsStrippedFromHeaderValues()
    {
        $handler = $this->buildHandler(true, "https://example.com\r\nX-Injected: evil", "GET\r\nX-Injected: evil");
        $handler(false);

        $this->assertSame(['Access-Control-Allow-Methods: GETX-Injected: evil'], $handler->sent);
    }

    public function testMethodsAndHeadersAreSent()
    {
        $handler = $this->buildHandler(false, '', 'GET, POST', 'Authorization, Content-Type');
        $handler(false);

        $this->assertSame(
            [
                'Access-Control-Allow-Methods: GET, POST',
                'Access-Control-Allow-Headers: Authorization, Content-Type',
            ],
            $handler->sent
        );
    }

    /**
     * @param bool $originEnabled
     * @param string $allowOrigin
     * @param string|null $methods
     * @param string|null $headers
     * @param string|null $requestOrigin
     * @return RecordingCorsHandler
     */
    private function buildHandler(
        $originEnabled,
        $allowOrigin,
        $methods = null,
        $headers = null,
        $requestOrigin = null
    ) {
        $settings = (new CorsSettings())->withSettings([
            'cors' => [
                'enabled'               => 1,
                'allow_origin_enabled'  => $originEnabled,
                'allow_origin'          => $allowOrigin,
                'allow_methods_enabled' => $methods !== null,
                'allow_methods'         => (string) $methods,
                'allow_headers_enabled' => $headers !== null,
                'allow_headers'         => (string) $headers,
            ],
        ]);
        $server = $requestOrigin === null ? [] : ['HTTP_ORIGIN' => $requestOrigin];

        return new RecordingCorsHandler($settings, new ServerHelper($server));
    }
}

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
class RecordingCorsHandler extends CorsHandler
{
    /**
     * @var array<int, string>
     */
    public $sent = [];
    /**
     * @var array<int, string>
     */
    public $removed = [];

    protected function sendHeader($header)
    {
        $this->sent[] = $header;
    }

    protected function removeHeader($headerName)
    {
        $this->removed[] = $headerName;
    }
}
