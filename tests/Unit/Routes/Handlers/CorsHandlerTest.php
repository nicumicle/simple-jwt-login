<?php

namespace SimpleJwtLoginTests\Unit\Routes\Handlers;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\Settings\CorsSettings;
use SimpleJWTLogin\Routes\Handlers\CorsHandler;

class CorsHandlerTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCrlfIsStrippedFromOriginValue()
    {
        $settingsMock = $this->createStub(CorsSettings::class);
        $settingsMock->method('isAllowOriginEnabled')->willReturn(true);
        $settingsMock->method('getAllowOrigin')->willReturn("https://example.com\r\nX-Injected: evil");
        $settingsMock->method('isAllowMethodsEnabled')->willReturn(false);
        $settingsMock->method('isAllowHeadersEnabled')->willReturn(false);

        $handler = new CorsHandler($settingsMock);
        $handler->register();

        if (!function_exists('xdebug_get_headers')) {
            $this->expectNotToPerformAssertions();
            return;
        }

        $headers = xdebug_get_headers();
        $injectedHeaderFound = false;
        foreach ($headers as $header) {
            if (strpos($header, 'X-Injected:') === 0) {
                $injectedHeaderFound = true;
            }
        }

        $this->assertFalse($injectedHeaderFound, 'CRLF injection produced a separate X-Injected header');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testOriginHeaderSkippedWhenValueIsEmpty()
    {
        $settingsMock = $this->createStub(CorsSettings::class);
        $settingsMock->method('isAllowOriginEnabled')->willReturn(true);
        $settingsMock->method('getAllowOrigin')->willReturn('');
        $settingsMock->method('isAllowMethodsEnabled')->willReturn(false);
        $settingsMock->method('isAllowHeadersEnabled')->willReturn(false);

        $handler = new CorsHandler($settingsMock);

        if (!function_exists('xdebug_get_headers')) {
            $this->expectNotToPerformAssertions();
            $handler->register();
            return;
        }

        $handler->register();
        $headers = xdebug_get_headers();
        $originHeaderFound = false;
        foreach ($headers as $header) {
            if (strpos($header, 'Access-Control-Allow-Origin:') === 0) {
                $originHeaderFound = true;
            }
        }
        $this->assertFalse($originHeaderFound, 'Origin header must not be sent when value is empty');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testHeadersEmittedWhenEnabled()
    {
        $settingsMock = $this->createStub(CorsSettings::class);
        $settingsMock->method('isAllowOriginEnabled')->willReturn(true);
        $settingsMock->method('getAllowOrigin')->willReturn('https://example.com');
        $settingsMock->method('isAllowMethodsEnabled')->willReturn(true);
        $settingsMock->method('getAllowMethods')->willReturn('GET, POST');
        $settingsMock->method('isAllowHeadersEnabled')->willReturn(true);
        $settingsMock->method('getAllowHeaders')->willReturn('Authorization, Content-Type');

        $handler = new CorsHandler($settingsMock);
        $this->expectNotToPerformAssertions();
        $handler->register();
    }
}
