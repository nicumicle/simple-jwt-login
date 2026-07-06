<?php

namespace SimpleJwtLoginTests\Unit\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\RouteService;

class GetJwtFromHeaderTest extends TestCase
{
    #[DataProvider('headerProvider')]
    public function testGetJwtFromHeader(array $settings, array $headers, ?string $expected): void
    {
        $wpData = $this->createStub(WordPressDataInterface::class);
        $wpData->method('getOptionFromDatabase')->willReturn(json_encode($settings));

        $serverHelper = $this->createStub(ServerHelper::class);
        $serverHelper->method('getHeaders')->willReturn($headers);

        $routeService = (new RouteService())
            ->withSettings(new SimpleJWTLoginSettings($wpData))
            ->withServerHelper($serverHelper)
            ->withRequest([])
            ->withCookies([]);

        $this->assertSame($expected, $routeService->getJwtFromHeader());
    }

    public static function headerProvider(): array
    {
        return [
            'bearer token default' => [
                [],
                ['Authorization' => 'Bearer abc.def.ghi'],
                'abc.def.ghi',
            ],
            'no prefix allowed by default' => [
                [],
                ['Authorization' => 'abc.def.ghi'],
                'abc.def.ghi',
            ],
            'header source disabled' => [
                ['request_jwt_header' => false],
                ['Authorization' => 'Bearer abc.def.ghi'],
                null,
            ],
            'missing header' => [
                [],
                [],
                null,
            ],
            'bearer required but missing prefix' => [
                ['request_jwt_header_require_bearer' => true],
                ['Authorization' => 'abc.def.ghi'],
                null,
            ],
            'bearer required with prefix' => [
                ['request_jwt_header_require_bearer' => true],
                ['Authorization' => 'Bearer abc.def.ghi'],
                'abc.def.ghi',
            ],
            'custom header key' => [
                ['request_keys' => ['header' => 'X-Auth']],
                ['X-Auth' => 'Bearer abc.def.ghi'],
                'abc.def.ghi',
            ],
        ];
    }
}
