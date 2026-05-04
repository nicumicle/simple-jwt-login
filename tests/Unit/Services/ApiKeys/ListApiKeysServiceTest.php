<?php

namespace SimpleJwtLoginTests\Unit\Services\ApiKeys;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\ApiKeys\ListApiKeysService;
use WP_REST_Response;

class ListApiKeysServiceTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface
     */
    private $wordPressDataMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ApiKeyRepositoryInterface
     */
    private $apiKeyRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressDataMock    = $this->createStub(WordPressDataInterface::class);
        $this->apiKeyRepositoryMock = $this->createStub(ApiKeyRepositoryInterface::class);
        $this->wordPressDataMock->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $this->wordPressDataMock->method('isUserLoggedIn')->willReturn(true);
        $this->wordPressDataMock->method('currentUserCan')->willReturn(true);
    }

    public function testThrowsWhenNotLoggedIn()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(\SimpleJWTLogin\ErrorCodes::ERR_API_KEY_UNAUTHORIZED);

        $mock = $this->createStub(WordPressDataInterface::class);
        $mock->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $mock->method('isUserLoggedIn')->willReturn(false);

        $service = (new ListApiKeysService())
            ->withRequest([])
            ->withSettings(new SimpleJWTLoginSettings($mock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testNonAdminOnlySeesOwnKeys()
    {
        $mock = $this->createStub(WordPressDataInterface::class);
        $mock->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $mock->method('isUserLoggedIn')->willReturn(true);
        $mock->method('currentUserCan')->willReturn(false);
        $mock->method('getCurrentUserId')->willReturn(7);

        $item              = new \stdClass();
        $item->id          = 1;
        $item->user_id     = 7;
        $item->name        = 'My Key';
        $item->key_prefix  = 'sjl_abcd';
        $item->permissions = json_encode(['read']);
        $item->expires_at  = null;
        $item->last_used_at = null;
        $item->created_at  = '2026-01-01 00:00:00';
        $item->revoked_at  = null;

        $repoMock = $this->createStub(ApiKeyRepositoryInterface::class);
        $repoMock->method('findByUserId')->willReturn([
            'items' => [$item],
            'total' => 1,
        ]);

        $capturedData = null;
        $mock->method('createResponse')
            ->willReturnCallback(function ($data) use (&$capturedData) {
                $capturedData = $data;
                return new WP_REST_Response($data);
            });

        $service = (new ListApiKeysService())
            ->withRequest([])
            ->withSettings(new SimpleJWTLoginSettings($mock))
            ->withApiKeyRepository($repoMock);

        $service->makeAction();

        $returnedItem = $capturedData['data']['items'][0];
        $this->assertArrayNotHasKey('user_id', $returnedItem);
        $this->assertCount(1, $capturedData['data']['items']);
    }

    public function testAdminResponseIncludesUserId()
    {
        $item              = new \stdClass();
        $item->id          = 1;
        $item->user_id     = 5;
        $item->name        = 'Admin Key';
        $item->key_prefix  = 'sjl_efgh';
        $item->permissions = json_encode(['read']);
        $item->expires_at  = null;
        $item->last_used_at = null;
        $item->created_at  = '2026-01-01 00:00:00';
        $item->revoked_at  = null;

        $this->apiKeyRepositoryMock->method('findAll')->willReturn([
            'items' => [$item],
            'total' => 1,
        ]);

        $capturedData = null;
        $this->wordPressDataMock->method('createResponse')
            ->willReturnCallback(function ($data) use (&$capturedData) {
                $capturedData = $data;
                return new WP_REST_Response($data);
            });

        $service = (new ListApiKeysService())
            ->withRequest([])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();

        $returnedItem = $capturedData['data']['items'][0];
        $this->assertArrayHasKey('user_id', $returnedItem);
        $this->assertSame(5, $returnedItem['user_id']);
    }

    public function testSuccessResponseContainsPaginationFields()
    {
        $item              = new \stdClass();
        $item->id          = 1;
        $item->name        = 'Test Key';
        $item->key_prefix  = 'sjl_1234';
        $item->permissions = json_encode(['login']);
        $item->expires_at  = null;
        $item->last_used_at = null;
        $item->created_at  = '2026-01-01 00:00:00';
        $item->revoked_at  = null;

        $this->apiKeyRepositoryMock->method('findAll')->willReturn([
            'items' => [$item],
            'total' => 1,
        ]);

        $capturedData = null;
        $this->wordPressDataMock->method('createResponse')
            ->willReturnCallback(function ($data) use (&$capturedData) {
                $capturedData = $data;
                return new WP_REST_Response($data);
            });

        $service = (new ListApiKeysService())
            ->withRequest(['page' => 1, 'per_page' => 20])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();

        $this->assertArrayHasKey('data', $capturedData);
        $this->assertArrayHasKey('items', $capturedData['data']);
        $this->assertArrayHasKey('total', $capturedData['data']);
        $this->assertArrayHasKey('page', $capturedData['data']);
        $this->assertArrayHasKey('per_page', $capturedData['data']);
        $this->assertSame(1, $capturedData['data']['total']);
        $this->assertCount(1, $capturedData['data']['items']);
    }

    public function testItemsDoNotContainKeyHash()
    {
        $item              = new \stdClass();
        $item->id          = 2;
        $item->name        = 'Another Key';
        $item->key_prefix  = 'sjl_5678';
        $item->permissions = json_encode(['register']);
        $item->expires_at  = null;
        $item->last_used_at = null;
        $item->created_at  = '2026-01-01 00:00:00';
        $item->revoked_at  = null;

        $this->apiKeyRepositoryMock->method('findAll')->willReturn([
            'items' => [$item],
            'total' => 1,
        ]);

        $capturedData = null;
        $this->wordPressDataMock->method('createResponse')
            ->willReturnCallback(function ($data) use (&$capturedData) {
                $capturedData = $data;
                return new WP_REST_Response($data);
            });

        $service = (new ListApiKeysService())
            ->withRequest([])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();

        $returnedItem = $capturedData['data']['items'][0];
        $this->assertArrayNotHasKey('key_hash', $returnedItem);
    }
}
