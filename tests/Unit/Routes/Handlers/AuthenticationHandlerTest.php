<?php

namespace SimpleJwtLoginTests\Unit\Routes\Handlers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Routes\Handlers\AuthenticationHandler;
use SimpleJWTLogin\Services\AuditLoggerService;
use SimpleJWTLogin\Services\RouteService;

class AuthenticationHandlerTest extends TestCase
{
    /**
     * The middleware must be skipped only for the route WordPress dispatches,
     * never because the plugin namespace appears somewhere in the raw URL.
     *
     * @return array<string, array{string|null, string, bool}>
     */
    public static function routeProvider()
    {
        return [
            'plugin route is skipped' => [
                '/simple-jwt-login/v1/auth',
                '/wp-json/simple-jwt-login/v1/auth',
                false,
            ],
            'core route with namespace in query runs middleware' => [
                '/wp/v2/users',
                '/wp-json/wp/v2/users?x=simple-jwt-login/v1/',
                true,
            ],
            'duplicated rest_route dispatching a core route runs middleware' => [
                '/wp/v2/users',
                '/?rest_route=/simple-jwt-login/v1/&rest_route=/wp/v2/users',
                true,
            ],
            'unresolved route runs middleware' => [
                null,
                '/simple-jwt-login/v1/',
                true,
            ],
        ];
    }

    #[DataProvider('routeProvider')]
    public function testMiddlewareSkipUsesResolvedRoute($resolvedRoute, $requestUri, $middlewareRuns)
    {
        $wordPressData = $this->createStub(WordPressDataInterface::class);
        $wordPressData->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                '_schema_version' => SimpleJWTLoginSettings::SCHEMA_VERSION,
                'general' => [
                    'api_middleware' => ['enabled' => true],
                ],
            ]));
        $wordPressData->method('getResolvedRestRoute')->willReturn($resolvedRoute);

        $routeService = $this->createMock(RouteService::class);
        $routeService->expects($middlewareRuns ? $this->once() : $this->never())
            ->method('getJwtFromRequestHeaderOrCookie')
            ->willReturn('');

        $handler = new AuthenticationHandler(
            $routeService,
            new SimpleJWTLoginSettings($wordPressData),
            $wordPressData,
            new ServerHelper([
                'HTTP_HOST'      => 'test.com',
                'REQUEST_URI'    => $requestUri,
                'REQUEST_METHOD' => 'GET',
            ]),
            $this->createStub(ApiKeyRepositoryInterface::class),
            $this->createStub(AuditLoggerService::class)
        );

        $this->assertNull($handler(null));
    }
}
