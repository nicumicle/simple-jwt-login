<?php

namespace SimpleJwtLoginTests\Unit\Services\ApiKeys;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\ApiKeys\UpdateApiKeyService;
use stdClass;
use WP_REST_Response;

class UpdateApiKeyServiceTest extends TestCase
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

        $service = (new UpdateApiKeyService())
            ->withRequest(['id' => 1, 'name' => 'My Key', 'permissions' => ['read']])
            ->withSettings(new SimpleJWTLoginSettings($mock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testNonAdminCannotUpdateOtherUsersKey()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_UNAUTHORIZED);

        $mock = $this->createStub(WordPressDataInterface::class);
        $mock->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $mock->method('isUserLoggedIn')->willReturn(true);
        $mock->method('currentUserCan')->willReturn(false);
        $mock->method('getCurrentUserId')->willReturn(42);

        $key          = new stdClass();
        $key->user_id = 99;
        $this->apiKeyRepositoryMock->method('findById')->willReturn($key);

        $service = (new UpdateApiKeyService())
            ->withRequest(['id' => 5, 'name' => 'My Key', 'permissions' => ['read']])
            ->withSettings(new SimpleJWTLoginSettings($mock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testNonAdminCanUpdateOwnKey()
    {
        $mock = $this->createStub(WordPressDataInterface::class);
        $mock->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $mock->method('isUserLoggedIn')->willReturn(true);
        $mock->method('currentUserCan')->willReturnCallback(static function ($cap) {
            // Non-admin user: has 'read' but not 'manage_options'
            return $cap === 'read';
        });
        $mock->method('getCurrentUserId')->willReturn(42);
        $mock->method('createResponse')->willReturn(new WP_REST_Response(['success' => true]));

        $key          = new stdClass();
        $key->user_id = 42;

        $repoMock = $this->createStub(ApiKeyRepositoryInterface::class);
        $repoMock->method('findById')->willReturn($key);
        $repoMock->method('updateById')->willReturn(true);

        $service = (new UpdateApiKeyService())
            ->withRequest(['id' => 5, 'name' => 'My Key', 'permissions' => ['read']])
            ->withSettings(new SimpleJWTLoginSettings($mock))
            ->withApiKeyRepository($repoMock);

        $result = $service->makeAction();

        $this->assertInstanceOf(WP_REST_Response::class, $result);
    }

    public function testThrowsWhenIdIsZero()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_NOT_FOUND);

        $service = (new UpdateApiKeyService())
            ->withRequest(['id' => 0, 'name' => 'My Key', 'permissions' => ['read']])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testThrowsWhenNameIsMissing()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_MISSING_NAME);

        $service = (new UpdateApiKeyService())
            ->withRequest(['id' => 1, 'name' => '', 'permissions' => ['read']])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testThrowsWhenPermissionsAreMissing()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_MISSING_PERMISSIONS);

        $service = (new UpdateApiKeyService())
            ->withRequest(['id' => 1, 'name' => 'My Key', 'permissions' => []])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testThrowsWhenPermissionIsInvalid()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_INVALID_PERMISSION);

        $service = (new UpdateApiKeyService())
            ->withRequest(['id' => 1, 'name' => 'My Key', 'permissions' => ['bad_permission']])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testThrowsWhenUserLacksCapabilityForRequestedPermission()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_UNAUTHORIZED);

        $mock = $this->createStub(WordPressDataInterface::class);
        $mock->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $mock->method('isUserLoggedIn')->willReturn(true);
        $mock->method('currentUserCan')->willReturnCallback(static function ($cap) {
            // Subscriber: only 'read'; no 'edit_posts', 'manage_options'
            return $cap === 'read';
        });
        $mock->method('getCurrentUserId')->willReturn(5);

        $key          = new stdClass();
        $key->user_id = 5;
        $this->apiKeyRepositoryMock->method('findById')->willReturn($key);

        $service = (new UpdateApiKeyService())
            ->withRequest(['id' => 1, 'name' => 'My Key', 'permissions' => ['create']])
            ->withSettings(new SimpleJWTLoginSettings($mock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testThrowsWhenUpdateFails()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_UPDATE_FAILED);

        $this->apiKeyRepositoryMock->method('updateById')->willReturn(false);

        $service = (new UpdateApiKeyService())
            ->withRequest(['id' => 1, 'name' => 'My Key', 'permissions' => ['read']])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testSuccessReturnsResponse()
    {
        $this->apiKeyRepositoryMock->method('updateById')->willReturn(true);
        $this->wordPressDataMock->method('createResponse')->willReturn(new WP_REST_Response(['success' => true]));

        $service = (new UpdateApiKeyService())
            ->withRequest(['id' => 1, 'name' => 'My Key', 'permissions' => ['read']])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $result = $service->makeAction();

        $this->assertInstanceOf(WP_REST_Response::class, $result);
    }
}
