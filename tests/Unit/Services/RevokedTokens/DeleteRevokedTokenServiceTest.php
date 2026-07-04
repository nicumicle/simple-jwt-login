<?php

namespace SimpleJwtLoginTests\Unit\Services\RevokedTokens;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\RevokedTokens\DeleteRevokedTokenService;
use WP_REST_Response;

class DeleteRevokedTokenServiceTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WordPressDataInterface
     */
    private $wordPressDataMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RevokedTokenRepositoryInterface
     */
    private $revokedTokenRepoMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->wordPressDataMock = $this->createStub(WordPressDataInterface::class);
        $this->revokedTokenRepoMock = $this->createStub(RevokedTokenRepositoryInterface::class);
        $this->wordPressDataMock->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $this->wordPressDataMock->method('currentUserCan')->willReturn(true);
    }

    public function testThrowsWhenNotAdmin()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_REVOKED_TOKEN_UNAUTHORIZED);

        $mock = $this->createStub(WordPressDataInterface::class);
        $mock->method('getOptionFromDatabase')->willReturn(json_encode([]));
        $mock->method('currentUserCan')->willReturn(false);

        $service = (new DeleteRevokedTokenService())
            ->withRequest(['id' => 1])
            ->withSettings(new SimpleJWTLoginSettings($mock))
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);

        $service->makeAction();
    }

    public function testThrowsWhenIdIsZero()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_REVOKED_TOKEN_NOT_FOUND);

        $service = (new DeleteRevokedTokenService())
            ->withRequest(['id' => 0])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);

        $service->makeAction();
    }

    public function testThrowsWhenIdNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_REVOKED_TOKEN_NOT_FOUND);

        $this->revokedTokenRepoMock->method('existsById')->willReturn(false);

        $service = (new DeleteRevokedTokenService())
            ->withRequest(['id' => 5])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);

        $service->makeAction();
    }

    public function testThrowsWhenDeleteFails()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_REVOKED_TOKEN_DELETE_FAILED);

        $this->revokedTokenRepoMock->method('existsById')->willReturn(true);
        $this->revokedTokenRepoMock->method('deleteById')->willReturn(false);

        $service = (new DeleteRevokedTokenService())
            ->withRequest(['id' => 5])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);

        $service->makeAction();
    }

    public function testSuccessReturnsResponse()
    {
        $this->revokedTokenRepoMock->method('existsById')->willReturn(true);
        $this->revokedTokenRepoMock->method('deleteById')->willReturn(true);
        $this->wordPressDataMock->method('createResponse')->willReturn(new WP_REST_Response(['success' => true]));

        $service = (new DeleteRevokedTokenService())
            ->withRequest(['id' => 5])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);

        $result = $service->makeAction();

        $this->assertInstanceOf(WP_REST_Response::class, $result);
    }
}
