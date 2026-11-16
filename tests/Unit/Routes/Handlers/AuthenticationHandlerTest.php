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
use stdClass;
use WP_Error;
use WP_User;

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

    /**
     * Batch sub-requests may use any write method, so /batch/v1 needs every write permission.
     *
     * @return array<string, array{string, string, string[], bool}>
     */
    public static function apiKeyProvider()
    {
        return [
            'create-only key on batch is refused' => ['/batch/v1', 'POST', ['create'], false],
            'write key on batch is accepted' => ['/batch/v1', 'POST', ['create', 'update', 'delete'], true],
            'create-only key on a normal POST is accepted' => ['/wp/v2/posts', 'POST', ['create'], true],
            'read key on a normal GET is accepted' => ['/wp/v2/posts', 'GET', ['read'], true],
        ];
    }

    #[DataProvider('apiKeyProvider')]
    public function testApiKeyPermissionsForRoute($route, $method, $permissions, $accepted)
    {
        $user = new WP_User(5);
        $wordPressData = $this->apiKeyWordPressData($route);
        $wordPressData->method('getUserDetailsById')->willReturn($user);
        $wordPressData->method('isInstanceOfuser')->willReturn(true);
        $wordPressData->expects($accepted ? $this->once() : $this->never())
            ->method('setCurrentUser')
            ->with($user);

        $result = $this->apiKeyHandler($wordPressData, $method, $permissions)(null);

        if ($accepted) {
            $this->assertTrue($result);
            return;
        }
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    /**
     * Keys stay in the database when an admin deletes their owner in wp-admin.
     */
    public function testApiKeyOfDeletedUserIsRefused()
    {
        $wordPressData = $this->apiKeyWordPressData('/wp/v2/posts');
        $wordPressData->method('getUserDetailsById')->willReturn(false);
        $wordPressData->method('isInstanceOfuser')->willReturn(false);
        $wordPressData->expects($this->never())->method('setCurrentUser');

        $result = $this->apiKeyHandler($wordPressData, 'GET', ['read'])(null);

        $this->assertInstanceOf(WP_Error::class, $result);
    }

    /**
     * @param string $route
     * @return WordPressDataInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private function apiKeyWordPressData($route)
    {
        $wordPressData = $this->createMock(WordPressDataInterface::class);
        $wordPressData->method('getOptionFromDatabase')
            ->willReturn(json_encode([
                '_schema_version' => SimpleJWTLoginSettings::SCHEMA_VERSION,
                'api_keys' => ['enabled' => true],
            ]));
        $wordPressData->method('getResolvedRestRoute')->willReturn($route);

        return $wordPressData;
    }

    /**
     * @param WordPressDataInterface $wordPressData
     * @param string $method
     * @param string[] $permissions
     * @return AuthenticationHandler
     */
    private function apiKeyHandler($wordPressData, $method, $permissions)
    {
        $key = new stdClass();
        $key->id = 1;
        $key->user_id = 5;
        $key->permissions = json_encode($permissions);
        $key->last_used_at = gmdate('Y-m-d H:i:s');
        $repository = $this->createStub(ApiKeyRepositoryInterface::class);
        $repository->method('getByKeyHash')->willReturn($key);

        return new AuthenticationHandler(
            $this->createStub(RouteService::class),
            new SimpleJWTLoginSettings($wordPressData),
            $wordPressData,
            new ServerHelper([
                'HTTP_HOST'      => 'test.com',
                'REQUEST_URI'    => '/wp-json' . $wordPressData->getResolvedRestRoute(),
                'REQUEST_METHOD' => $method,
                'HTTP_X_API_KEY' => 'sjl_key',
            ]),
            $repository,
            $this->createStub(AuditLoggerService::class)
        );
    }
}
