<?php

namespace SimpleJwtLoginTests\Unit\Services\RevokedTokens;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressDataInterface;
use SimpleJWTLogin\Services\RevokedTokens\ListRevokedTokensService;
use stdClass;
use WP_REST_Response;

class ListRevokedTokensServiceTest extends TestCase
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

        $service = (new ListRevokedTokensService())
            ->withRequest([])
            ->withSettings(new SimpleJWTLoginSettings($mock))
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);

        $service->makeAction();
    }

    public function testSuccessResponseContainsPaginationFields()
    {
        $item             = new stdClass();
        $item->id         = 1;
        $item->user_id    = 5;
        $item->token_hash = hash('sha256', 'jwt');
        $item->expires_at = null;
        $item->revoked_at = '2026-01-01 00:00:00';

        $this->revokedTokenRepoMock->method('findAll')->willReturn([
            'items' => [$item],
            'total' => 1,
        ]);

        $capturedData = null;
        $this->wordPressDataMock->method('createResponse')
            ->willReturnCallback(function ($data) use (&$capturedData) {
                $capturedData = $data;
                return new WP_REST_Response($data);
            });

        $service = (new ListRevokedTokensService())
            ->withRequest(['page' => 1, 'per_page' => 20])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);

        $service->makeAction();

        $this->assertArrayHasKey('data', $capturedData);
        $this->assertArrayHasKey('items', $capturedData['data']);
        $this->assertArrayHasKey('total', $capturedData['data']);
        $this->assertArrayHasKey('page', $capturedData['data']);
        $this->assertArrayHasKey('per_page', $capturedData['data']);
        $this->assertSame(1, $capturedData['data']['total']);
        $this->assertCount(1, $capturedData['data']['items']);
    }

    public function testItemsDoNotContainFullTokenHash()
    {
        $item             = new stdClass();
        $item->id         = 1;
        $item->user_id    = 5;
        $item->token_hash = hash('sha256', 'jwt');
        $item->expires_at = null;
        $item->revoked_at = '2026-01-01 00:00:00';

        $this->revokedTokenRepoMock->method('findAll')->willReturn([
            'items' => [$item],
            'total' => 1,
        ]);

        $capturedData = null;
        $this->wordPressDataMock->method('createResponse')
            ->willReturnCallback(function ($data) use (&$capturedData) {
                $capturedData = $data;
                return new WP_REST_Response($data);
            });

        $service = (new ListRevokedTokensService())
            ->withRequest([])
            ->withSettings(new SimpleJWTLoginSettings($this->wordPressDataMock))
            ->withRevokedTokenRepository($this->revokedTokenRepoMock);

        $service->makeAction();

        $returnedItem = $capturedData['data']['items'][0];
        $this->assertArrayNotHasKey('token_hash', $returnedItem);
        $this->assertArrayHasKey('token_hash_masked', $returnedItem);
        $this->assertNotSame($item->token_hash, $returnedItem['token_hash_masked']);
    }
}
