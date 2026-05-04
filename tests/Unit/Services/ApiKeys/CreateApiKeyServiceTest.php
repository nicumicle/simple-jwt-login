<?php

namespace SimpleJwtLoginTests\Unit\Services\ApiKeys;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\ApiKeys\CreateApiKeyService;
use WP_REST_Response;

class CreateApiKeyServiceTest extends TestCase
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
        $this->wordPressDataMock->method('getCurrentUserId')->willReturn(1);
    }

    public function testThrowsWhenNotLoggedIn()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_UNAUTHORIZED);

        $mock = $this->createStub(WordPressDataInterface::class);
        $mock->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $mock->method('isUserLoggedIn')->willReturn(false);

        $service = (new CreateApiKeyService())
            ->withRequest(['name' => 'Test', 'permissions' => ['read']])
            ->withSettings(new SimpleJWTLoginSettings($mock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testThrowsWhenNameIsMissing()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_MISSING_NAME);

        $service = (new CreateApiKeyService())
            ->withRequest(['permissions' => ['read']])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testThrowsWhenPermissionsAreMissing()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_MISSING_PERMISSIONS);

        $service = (new CreateApiKeyService())
            ->withRequest(['name' => 'My Key', 'permissions' => []])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testThrowsWhenPermissionIsInvalid()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_INVALID_PERMISSION);

        $service = (new CreateApiKeyService())
            ->withRequest(['name' => 'My Key', 'permissions' => ['not_a_valid_permission']])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testThrowsWhenInsertFails()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_CREATE_FAILED);

        $this->apiKeyRepositoryMock->method('insert')->willReturn(false);

        $service = (new CreateApiKeyService())
            ->withRequest(['name' => 'My Key', 'permissions' => ['read']])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testSuccessReturnsResponseWithSuccessTrue()
    {
        $this->apiKeyRepositoryMock->method('insert')->willReturn(5);
        $this->wordPressDataMock->method('createResponse')->willReturn(new WP_REST_Response(['success' => true]));

        $service = (new CreateApiKeyService())
            ->withRequest(['name' => 'My Key', 'permissions' => ['read']])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $result = $service->makeAction();

        $this->assertInstanceOf(WP_REST_Response::class, $result);
    }
}
