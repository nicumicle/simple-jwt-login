<?php

namespace SimpleJwtLoginTests\Unit\Repositories\RevokedToken;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Repositories\RevokedToken\RevokedTokenRepository;
use stdClass;

class RevokedTokenRepositoryTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\wpdb
     */
    private $wpdbMock;

    /**
     * @var RevokedTokenRepository
     */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->wpdbMock         = $this->createStub(\wpdb::class);
        $this->wpdbMock->prefix = 'wp_';
        $this->repository       = new RevokedTokenRepository($this->wpdbMock);
    }

    public static function insertProvider(): array
    {
        return [
            'one row inserted'     => [1, true],
            'zero rows (not false)' => [0, true],
            'db error'              => [false, false],
        ];
    }

    #[DataProvider('insertProvider')]
    public function testInsert($dbResult, $expected)
    {
        $this->wpdbMock->method('insert')->willReturn($dbResult);

        $result = $this->repository->insert(1, hash('sha256', 'jwt'), '2027-01-01 00:00:00');

        $this->assertSame($expected, $result);
    }

    public static function existsForUserProvider(): array
    {
        return [
            'token found'     => ['1', true],
            'token not found' => [null, false],
        ];
    }

    #[DataProvider('existsForUserProvider')]
    public function testExistsForUser($dbResult, $expected)
    {
        $this->wpdbMock->method('prepare')->willReturnArgument(0);
        $this->wpdbMock->method('get_var')->willReturn($dbResult);

        $result = $this->repository->existsForUser(1, hash('sha256', 'jwt'));

        $this->assertSame($expected, $result);
    }

    public static function deleteByIdProvider(): array
    {
        return [
            'row deleted'         => [1, true],
            'no matching row'     => [0, false],
            'db error'            => [false, false],
        ];
    }

    #[DataProvider('deleteByIdProvider')]
    public function testDeleteById($dbResult, $expected)
    {
        $this->wpdbMock->method('delete')->willReturn($dbResult);

        $result = $this->repository->deleteById(7);

        $this->assertSame($expected, $result);
    }

    public static function existsByIdProvider(): array
    {
        return [
            'row found'     => ['1', true],
            'row not found' => [null, false],
        ];
    }

    #[DataProvider('existsByIdProvider')]
    public function testExistsById($dbResult, $expected)
    {
        $this->wpdbMock->method('prepare')->willReturnArgument(0);
        $this->wpdbMock->method('get_var')->willReturn($dbResult);

        $result = $this->repository->existsById(7);

        $this->assertSame($expected, $result);
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
    public function testDeleteByUserId($dbResult, $expected)
    {
        $this->wpdbMock->method('delete')->willReturn($dbResult);

        $result = $this->repository->deleteByUserId(42);

        $this->assertSame($expected, $result);
    }

    public function testFindAllReturnsItemsAndTotal()
    {
        $row             = new stdClass();
        $row->id         = 1;
        $row->user_id    = 42;
        $row->token_hash = hash('sha256', 'jwt');

        $this->wpdbMock->method('prepare')->willReturnArgument(0);
        $this->wpdbMock->method('get_var')->willReturn('3');
        $this->wpdbMock->method('get_results')->willReturn([$row]);

        $result = $this->repository->findAll(1, 20);

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertSame(3, $result['total']);
        $this->assertCount(1, $result['items']);
    }

    public function testFindAllReturnsEmptyArrayWhenGetResultsReturnsNull()
    {
        $this->wpdbMock->method('prepare')->willReturnArgument(0);
        $this->wpdbMock->method('get_var')->willReturn('0');
        $this->wpdbMock->method('get_results')->willReturn(null);

        $result = $this->repository->findAll(1, 20);

        $this->assertSame([], $result['items']);
        $this->assertSame(0, $result['total']);
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
    public function testDeleteExpired($dbResult, $expected)
    {
        $this->wpdbMock->method('query')->willReturn($dbResult);

        $result = $this->repository->deleteExpired();

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
