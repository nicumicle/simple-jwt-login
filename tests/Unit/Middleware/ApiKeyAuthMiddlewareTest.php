<?php

namespace SimpleJwtLoginTests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use stdClass;
use SimpleJWTLogin\Middleware\ApiKeyAuthMiddleware;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;

class ApiKeyAuthMiddlewareTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ApiKeyRepositoryInterface
     */
    private $repositoryMock;

    /**
     * @var ApiKeyAuthMiddleware
     */
    private $middleware;

    public function setUp(): void
    {
        parent::setUp();
        $this->repositoryMock = $this->createStub(ApiKeyRepositoryInterface::class);
        $this->middleware     = new ApiKeyAuthMiddleware($this->repositoryMock);
    }

    public function testReturnsNullWhenXApiKeyHeaderIsAbsent()
    {
        $serverHelper = new ServerHelper([]);

        $result = $this->middleware->validate($serverHelper, 'read');

        $this->assertNull($result);
    }

    public function testReturnsNullWhenKeyNotFoundInRepository()
    {
        $serverHelper = new ServerHelper(['HTTP_X_API_KEY' => 'raw-key-value']);
        $this->repositoryMock->method('getByKeyHash')->willReturn(null);

        $result = $this->middleware->validate($serverHelper, 'read');

        $this->assertNull($result);
    }

    public function testReturnsNullWhenKeyDoesNotHaveRequiredPermission()
    {
        $serverHelper = new ServerHelper(['HTTP_X_API_KEY' => 'raw-key-value']);

        $key              = new stdClass();
        $key->id          = 1;
        $key->permissions = json_encode(['create']);

        $this->repositoryMock->method('getByKeyHash')->willReturn($key);

        $result = $this->middleware->validate($serverHelper, 'read');

        $this->assertNull($result);
    }

    public function testReturnsNullWhenCustomHeaderIsAbsent()
    {
        $serverHelper = new ServerHelper(['HTTP_X_API_KEY' => 'raw-key-value']);

        $result = $this->middleware->validate($serverHelper, 'read', 'x-custom-key');

        $this->assertNull($result);
    }

    public function testReturnsKeyArrayAndCallsTouchLastUsedWhenPermissionMatches()
    {
        $rawKey       = 'raw-key-value';
        $serverHelper = new ServerHelper(['HTTP_X_API_KEY' => $rawKey]);

        $key              = new stdClass();
        $key->id          = 7;
        $key->permissions = json_encode(['read']);

        $repositoryMock = $this->createMock(ApiKeyRepositoryInterface::class);
        $repositoryMock->method('getByKeyHash')->willReturn($key);
        $repositoryMock->expects($this->once())
            ->method('touchLastUsed')
            ->with(7, $this->isType('string'));

        $middleware = new ApiKeyAuthMiddleware($repositoryMock);
        $result     = $middleware->validate($serverHelper, 'read');

        $this->assertIsArray($result);
        $this->assertSame(7, $result['id']);
    }
}
