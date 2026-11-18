<?php

namespace SimpleJwtLoginTests\Unit\Services\ApiKeys;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testThrowsWhenUserLacksCapabilityForRequestedPermission()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_UNAUTHORIZED);

        $mock = $this->createStub(WordPressDataInterface::class);
        $mock->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $mock->method('isUserLoggedIn')->willReturn(true);
        $mock->method('currentUserCan')->willReturnCallback(static function ($cap) {
            // Subscriber: has 'read' but not 'edit_posts', 'delete_posts', 'manage_options'
            return $cap === 'read';
        });

        $service = (new CreateApiKeyService())
            ->withRequest(['name' => 'My Key', 'permissions' => ['create']])
            ->withSettings(new SimpleJWTLoginSettings($mock))
            ->withApiKeyRepository($this->apiKeyRepositoryMock);

        $service->makeAction();
    }

    public function testSubscriberCanCreateReadOnlyKey()
    {
        $mock = $this->createStub(WordPressDataInterface::class);
        $mock->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $mock->method('isUserLoggedIn')->willReturn(true);
        $mock->method('currentUserCan')->willReturnCallback(static function ($cap) {
            return $cap === 'read';
        });
        $mock->method('getCurrentUserId')->willReturn(5);
        $mock->method('createResponse')->willReturn(new WP_REST_Response(['success' => true]));

        $repoMock = $this->createStub(ApiKeyRepositoryInterface::class);
        $repoMock->method('insert')->willReturn(1);

        $service = (new CreateApiKeyService())
            ->withRequest(['name' => 'Read-only key', 'permissions' => ['read']])
            ->withSettings(new SimpleJWTLoginSettings($mock))
            ->withApiKeyRepository($repoMock);

        $result = $service->makeAction();

        $this->assertInstanceOf(WP_REST_Response::class, $result);
    }

    public function testAdminCanCreateKeyWithAnyPermission()
    {
        $mock = $this->createStub(WordPressDataInterface::class);
        $mock->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $mock->method('isUserLoggedIn')->willReturn(true);
        $mock->method('currentUserCan')->willReturn(true); // manage_options = true
        $mock->method('getCurrentUserId')->willReturn(1);
        $mock->method('createResponse')->willReturn(new WP_REST_Response(['success' => true]));

        $repoMock = $this->createStub(ApiKeyRepositoryInterface::class);
        $repoMock->method('insert')->willReturn(2);

        $service = (new CreateApiKeyService())
            ->withRequest(['name' => 'Admin key', 'permissions' => ['create', 'update', 'delete']])
            ->withSettings(new SimpleJWTLoginSettings($mock))
            ->withApiKeyRepository($repoMock);

        $result = $service->makeAction();

        $this->assertInstanceOf(WP_REST_Response::class, $result);
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

    /**
     * Expiry dates are stored and compared in UTC. A value without a zone is read as UTC.
     *
     * @return array<string, array{string|null, string|null}>
     */
    public static function validExpiryProvider()
    {
        return [
            'no expiry'               => [null, null],
            'utc date time'           => ['2030-01-02 03:04:05', '2030-01-02 03:04:05'],
            'date only'               => ['2030-01-02', '2030-01-02 00:00:00'],
            'browser iso string'      => ['2030-01-02T03:04:05.000Z', '2030-01-02 03:04:05'],
            'datetime-local value'    => ['2030-01-02T03:04', '2030-01-02 03:04:00'],
            'with an offset'          => ['2030-01-02T05:04:05+02:00', '2030-01-02 03:04:05'],
        ];
    }

    #[DataProvider('validExpiryProvider')]
    public function testExpiryIsStoredInUtc($sent, $stored)
    {
        $repository = $this->createMock(ApiKeyRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('insert')
            ->with(1, 'My Key', $this->isString(), $this->isString(), '["read"]', $stored, $this->isString())
            ->willReturn(5);

        (new CreateApiKeyService())
            ->withRequest(['name' => 'My Key', 'permissions' => ['read'], 'expires_at' => $sent])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($repository)
            ->makeAction();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidExpiryProvider()
    {
        return [
            'relative text'  => ['tomorrow'],
            'impossible day' => ['2030-02-30 10:00:00'],
            'month 13'       => ['2030-13-01'],
            'garbage'        => ['2030-01-02 xx'],
            'hour 24'        => ['2030-01-31 24:30:00'],
            'minute 60'      => ['2030-01-31 23:60:00'],
            'second 60'      => ['2030-01-31 23:59:60'],
        ];
    }

    #[DataProvider('invalidExpiryProvider')]
    public function testInvalidExpiryIsRejected($sent)
    {
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_INVALID_EXPIRY);
        $repository = $this->createMock(ApiKeyRepositoryInterface::class);
        $repository->expects($this->never())->method('insert');

        (new CreateApiKeyService())
            ->withRequest(['name' => 'My Key', 'permissions' => ['read'], 'expires_at' => $sent])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withApiKeyRepository($repository)
            ->makeAction();
    }
}
