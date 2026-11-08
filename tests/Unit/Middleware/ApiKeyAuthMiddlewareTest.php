<?php

namespace SimpleJwtLoginTests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use stdClass;
use SimpleJWTLogin\Middleware\ApiKeyAuthMiddleware;
use SimpleJWTLogin\Modules\Settings\ApiKeysSettings;
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

    /**
     * @return array<string,array{0:string|null,1:bool,2:bool}>
     */
    public static function lastUsedProvider()
    {
        $recently = gmdate('Y-m-d H:i:s', time() - 10);

        return [
            'never used'                         => [null, true, true],
            'used 2 minutes ago'                 => [gmdate('Y-m-d H:i:s', time() - 120), true, true],
            'used 10 seconds ago'                => [$recently, true, false],
            'invalid last used at'               => ['not-a-date', true, true],
            'used 10 seconds ago, every request' => [$recently, false, true],
            'never used, every request'          => [null, false, true],
        ];
    }

    /**
     * @param string|null $lastUsedAt
     * @param bool $throttleLastUsed
     * @param bool $expectTouch
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('lastUsedProvider')]
    public function testReturnsKeyArrayAndThrottlesTouchLastUsed($lastUsedAt, $throttleLastUsed, $expectTouch)
    {
        $rawKey       = 'raw-key-value';
        $serverHelper = new ServerHelper(['HTTP_X_API_KEY' => $rawKey]);

        $key               = new stdClass();
        $key->id           = 7;
        $key->permissions  = json_encode(['read']);
        $key->last_used_at = $lastUsedAt;

        $repositoryMock = $this->createMock(ApiKeyRepositoryInterface::class);
        $repositoryMock->method('getByKeyHash')->willReturn($key);
        $repositoryMock->expects($expectTouch ? $this->once() : $this->never())
            ->method('touchLastUsed')
            ->with(7, $this->isString());

        $apiKeysSettings = (new ApiKeysSettings())->withSettings([
            'api_keys' => ['last_used_every_request' => !$throttleLastUsed],
        ]);

        $middleware = new ApiKeyAuthMiddleware($repositoryMock, $apiKeysSettings);
        $result     = $middleware->validate($serverHelper, 'read');

        $this->assertIsArray($result);
        $this->assertSame(7, $result['id']);
    }
}
