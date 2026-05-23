<?php

namespace SimpleJwtLoginTests\Unit\Repositories\WebhookLog;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;

class WebhookLogRepositoryTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|\wpdb
     */
    private $wpdbMock;

    /**
     * @var WebhookLogRepository
     */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->wpdbMock         = $this->createStub(\wpdb::class);
        $this->wpdbMock->prefix = 'wp_';
        $this->repository       = new WebhookLogRepository($this->wpdbMock);
    }

    public static function insertProvider(): array
    {
        return [
            'one row inserted'      => [1, true],
            'zero rows (not false)' => [0, true],
            'db error'              => [false, false],
        ];
    }

    #[DataProvider('insertProvider')]
    public function testInsert($dbResult, bool $expected): void
    {
        $this->wpdbMock->method('insert')->willReturn($dbResult);

        $result = $this->repository->insert('https://example.com/hook', 'login', 'POST', 200, null);

        $this->assertSame($expected, $result);
    }

    public function testFindPaginatedReturnsItemsAndTotal(): void
    {
        $items = [(object) ['id' => 1, 'event' => 'login', 'status_code' => 200]];
        $this->wpdbMock->method('prepare')->willReturn('SELECT ...');
        $this->wpdbMock->method('get_var')->willReturn('3');
        $this->wpdbMock->method('get_results')->willReturn($items);

        $result = $this->repository->findPaginated([], 1, 10);

        $this->assertSame($items, $result['items']);
        $this->assertSame(3, $result['total']);
    }

    public function testFindPaginatedReturnsEmptyArrayWhenGetResultsReturnsNull(): void
    {
        $this->wpdbMock->method('prepare')->willReturn('SELECT ...');
        $this->wpdbMock->method('get_var')->willReturn('0');
        $this->wpdbMock->method('get_results')->willReturn(null);

        $result = $this->repository->findPaginated([], 1, 10);

        $this->assertSame([], $result['items']);
        $this->assertSame(0, $result['total']);
    }

    public function testFindPaginatedCastsTotalToInt(): void
    {
        $this->wpdbMock->method('prepare')->willReturn('SELECT ...');
        $this->wpdbMock->method('get_var')->willReturn('42');
        $this->wpdbMock->method('get_results')->willReturn([]);

        $result = $this->repository->findPaginated([], 1, 10);

        $this->assertSame(42, $result['total']);
        $this->assertIsInt($result['total']);
    }

    public static function filterProvider(): array
    {
        return [
            'no filters'         => [[]],
            'event filter'       => [['event' => 'login']],
            'status success'     => [['status' => 'success']],
            'status failure'     => [['status' => 'failure']],
            'valid date_from'    => [['date_from' => '2026-01-01']],
            'invalid date_from'  => [['date_from' => 'not-a-date']],
            'valid date_to'      => [['date_to' => '2026-12-31']],
            'invalid date_to'    => [['date_to' => '31/12/2026']],
            'all valid filters'  => [[
                'event'     => 'register',
                'status'    => 'success',
                'date_from' => '2026-01-01',
                'date_to'   => '2026-12-31',
            ]],
        ];
    }

    #[DataProvider('filterProvider')]
    public function testFindPaginatedWithFiltersReturnsExpectedStructure(array $filters): void
    {
        $this->wpdbMock->method('prepare')->willReturn('SELECT ...');
        $this->wpdbMock->method('get_var')->willReturn('0');
        $this->wpdbMock->method('get_results')->willReturn([]);

        $result = $this->repository->findPaginated($filters, 1, 10);

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
    }

    public function testFindPaginatedPageOffsetIsApplied(): void
    {
        $this->wpdbMock->method('prepare')->willReturn('SELECT ...');
        $this->wpdbMock->method('get_var')->willReturn('20');
        $this->wpdbMock->method('get_results')->willReturn([]);

        $result = $this->repository->findPaginated([], 3, 5);

        $this->assertSame(20, $result['total']);
        $this->assertSame([], $result['items']);
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
    public function testDeleteOlderThan($dbResult, bool $expected): void
    {
        $this->wpdbMock->method('prepare')->willReturn('DELETE ...');
        $this->wpdbMock->method('query')->willReturn($dbResult);

        $result = $this->repository->deleteOlderThan('2026-01-01 00:00:00');

        $this->assertSame($expected, $result);
    }

    #[DataProvider('queryProvider')]
    public function testDeleteAll($dbResult, bool $expected): void
    {
        $this->wpdbMock->method('query')->willReturn($dbResult);

        $this->assertSame($expected, $this->repository->deleteAll());
    }

    #[DataProvider('queryProvider')]
    public function testDropTable($dbResult, bool $expected): void
    {
        $this->wpdbMock->method('query')->willReturn($dbResult);

        $this->assertSame($expected, $this->repository->dropTable());
    }
}
