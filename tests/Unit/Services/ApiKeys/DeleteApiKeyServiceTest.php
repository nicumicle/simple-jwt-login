<?php

namespace SimpleJwtLoginTests\Unit\Services\ApiKeys;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\ApiKeys\DeleteApiKeyService;
use WP_REST_Response;

class DeleteApiKeyServiceTest extends TestCase
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
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_UNAUTHORIZED);

        $mock = $this->createStub(WordPressDataInterface::class);
        $mock->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $mock->method('isUserLoggedIn')->willReturn(false);

        $service = (new DeleteApiKeyService())
            ->withRequest(['id' => 1])
            ->withSettings(new SimpleJWTLoginSettings($mock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testThrowsWhenIdIsZero()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_NOT_FOUND);

        $service = (new DeleteApiKeyService())
            ->withRequest(['id' => 0])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testNonAdminCannotDeleteOtherUsersKey()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_UNAUTHORIZED);

        $mock = $this->createStub(WordPressDataInterface::class);
        $mock->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $mock->method('isUserLoggedIn')->willReturn(true);
        $mock->method('currentUserCan')->willReturn(false);
        $mock->method('getCurrentUserId')->willReturn(42);

        $key          = new \stdClass();
        $key->user_id = 99;
        $this->apiKeyRepositoryMock->method('findById')->willReturn($key);

        $service = (new DeleteApiKeyService())
            ->withRequest(['id' => 5])
            ->withSettings(new SimpleJWTLoginSettings($mock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testNonAdminCanDeleteOwnKey()
    {
        $mock = $this->createStub(WordPressDataInterface::class);
        $mock->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $mock->method('isUserLoggedIn')->willReturn(true);
        $mock->method('currentUserCan')->willReturn(false);
        $mock->method('getCurrentUserId')->willReturn(42);
        $mock->method('createResponse')->willReturn(new WP_REST_Response(['success' => true]));

        $key          = new \stdClass();
        $key->user_id = 42;

        $repoMock = $this->createStub(ApiKeyRepositoryInterface::class);
        $repoMock->method('findById')->willReturn($key);
        $repoMock->method('deleteById')->willReturn(true);

        $service = (new DeleteApiKeyService())
            ->withRequest(['id' => 5])
            ->withSettings(new SimpleJWTLoginSettings($mock))
            ->withApiKeyRepository($repoMock);

        $result = $service->makeAction();

        $this->assertInstanceOf(WP_REST_Response::class, $result);
    }

    public function testThrowsWhenDeleteFails()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_DELETE_FAILED);

        $this->apiKeyRepositoryMock->method('deleteById')->willReturn(false);

        $service = (new DeleteApiKeyService())
            ->withRequest(['id' => 5])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testSuccessReturnsResponse()
    {
        $this->apiKeyRepositoryMock->method('deleteById')->willReturn(true);
        $this->wordPressDataMock->method('createResponse')->willReturn(new WP_REST_Response(['success' => true]));

        $service = (new DeleteApiKeyService())
            ->withRequest(['id' => 5])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $result = $service->makeAction();

        $this->assertInstanceOf(WP_REST_Response::class, $result);
    }
}
