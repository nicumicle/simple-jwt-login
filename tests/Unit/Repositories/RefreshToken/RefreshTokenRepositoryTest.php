<?php

namespace SimpleJwtLoginTests\Unit\Repositories\RefreshToken;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Repositories\RefreshToken\RefreshTokenRepository;

class RefreshTokenRepositoryTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\wpdb
     */
    private $wpdbMock;

    /**
     * @var RefreshTokenRepository
     */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->wpdbMock         = $this->createStub(\wpdb::class);
        $this->wpdbMock->prefix = 'wp_';
        $this->repository       = new RefreshTokenRepository($this->wpdbMock);
    }

    public static function insertProvider(): array
    {
        return [
            'one row inserted' => [1, true],
            'zero rows (not false)' => [0, true],
            'db error'         => [false, false],
        ];
    }

    #[DataProvider('insertProvider')]
    public function testInsert($dbResult, $expected)
    {
        $this->wpdbMock->method('insert')->willReturn($dbResult);

        $result = $this->repository->insert(1, 'token-abc', time() + 3600);

        $this->assertSame($expected, $result);
    }

    public static function getByTokenProvider(): array
    {
        return [
            'token found'     => [(object) ['id' => 1, 'user_id' => 42, 'refresh_token' => 'abc']],
            'token not found' => [null],
        ];
    }

    #[DataProvider('getByTokenProvider')]
    public function testGetByToken($dbResult)
    {
        $this->wpdbMock->method('prepare')->willReturn('SELECT ...');
        $this->wpdbMock->method('get_row')->willReturn($dbResult);

        $result = $this->repository->getByToken('abc');

        $this->assertSame($dbResult, $result);
    }

    public static function deleteProvider(): array
    {
        return [
            'rows deleted'          => [1, true],
            'zero rows (not error)' => [0, true],
            'db error'              => [false, false],
        ];
    }

    #[DataProvider('deleteProvider')]
    public function testDeleteByToken($dbResult, $expected)
    {
        $this->wpdbMock->method('delete')->willReturn($dbResult);

        $result = $this->repository->deleteByToken('token-abc');

        $this->assertSame($expected, $result);
    }

    #[DataProvider('deleteProvider')]
    public function testDeleteByUserId($dbResult, $expected)
    {
        $this->wpdbMock->method('delete')->willReturn($dbResult);

        $result = $this->repository->deleteByUserId(42);

        $this->assertSame($expected, $result);
    }

    public static function queryProvider(): array
    {
        return [
            'rows affected'        => [5, true],
            'zero rows (no error)' => [0, true],
            'db error'             => [false, false],
        ];
    }

    #[DataProvider('queryProvider')]
    public function testCleanupExpired($dbResult, $expected)
    {
        $this->wpdbMock->method('query')->willReturn($dbResult);

        $result = $this->repository->cleanupExpired();

        $this->assertSame($expected, $result);
    }

    #[DataProvider('queryProvider')]
    public function testDropTable($dbResult, $expected)
    {
        $this->wpdbMock->method('query')->willReturn($dbResult);

        $result = $this->repository->dropTable();

        $this->assertSame($expected, $result);
    }
}
