<?php

namespace SimpleJwtLoginTests\Unit\Repositories\ApiKey;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepository;
use stdClass;

class ApiKeyRepositoryTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\wpdb
     */
    private $wpdbMock;

    /**
     * @var ApiKeyRepository
     */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->wpdbMock         = $this->createStub(\wpdb::class);
        $this->wpdbMock->prefix = 'wp_';
        $this->repository       = new ApiKeyRepository($this->wpdbMock);
    }

    public function testInsertReturnsFalseWhenWpdbInsertFails()
    {
        $wpdbMock         = $this->createStub(\wpdb::class);
        $wpdbMock->prefix = 'wp_';
        $wpdbMock->method('insert')->willReturn(false);
        $repo = new ApiKeyRepository($wpdbMock);

        $result = $repo->insert(1, 'My Key', 'hash123', 'sjl_1234', '["login"]', null, '2026-01-01 00:00:00');

        $this->assertFalse($result);
    }

    public function testInsertReturnsInsertIdOnSuccess()
    {
        $wpdbMock              = $this->createStub(\wpdb::class);
        $wpdbMock->prefix      = 'wp_';
        $wpdbMock->insert_id   = 42;
        $wpdbMock->method('insert')->willReturn(1);
        $repo = new ApiKeyRepository($wpdbMock);

        $result = $repo->insert(1, 'My Key', 'hash123', 'sjl_1234', '["login"]', null, '2026-01-01 00:00:00');

        $this->assertSame(42, $result);
    }

    public function testGetByKeyHashReturnsNullWhenNotFound()
    {
        $this->wpdbMock->method('prepare')->willReturn('SELECT ...');
        $this->wpdbMock->method('get_row')->willReturn(null);

        $result = $this->repository->getByKeyHash('nonexistenthash');

        $this->assertNull($result);
    }

    public function testGetByKeyHashReturnsObjectWhenFound()
    {
        $row           = new stdClass();
        $row->id       = 1;
        $row->key_hash = 'abc123';

        $this->wpdbMock->method('prepare')->willReturn('SELECT ...');
        $this->wpdbMock->method('get_row')->willReturn($row);

        $result = $this->repository->getByKeyHash('abc123');

        $this->assertSame($row, $result);
    }

    public function testFindAllReturnsItemsAndTotal()
    {
        $row       = new stdClass();
        $row->id   = 1;
        $row->name = 'Test Key';

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

    public function testUpdateByIdReturnsFalseWhenWpdbUpdateFails()
    {
        $this->wpdbMock->method('update')->willReturn(false);

        $result = $this->repository->updateById(1, 'New Name', '["login"]', null);

        $this->assertFalse($result);
    }

    public function testUpdateByIdReturnsTrueOnSuccess()
    {
        $this->wpdbMock->method('update')->willReturn(1);

        $result = $this->repository->updateById(1, 'New Name', '["login"]', null);

        $this->assertTrue($result);
    }

    public function testRevokeByIdReturnsFalseWhenWpdbUpdateFails()
    {
        $this->wpdbMock->method('update')->willReturn(false);

        $result = $this->repository->revokeById(1, '2026-01-01 00:00:00');

        $this->assertFalse($result);
    }

    public function testRevokeByIdReturnsTrueOnSuccess()
    {
        $this->wpdbMock->method('update')->willReturn(1);

        $result = $this->repository->revokeById(1, '2026-01-01 00:00:00');

        $this->assertTrue($result);
    }

    public function testTouchLastUsedReturnsFalseWhenWpdbUpdateFails()
    {
        $this->wpdbMock->method('update')->willReturn(false);

        $result = $this->repository->touchLastUsed(1, '2026-01-01 00:00:00');

        $this->assertFalse($result);
    }

    public function testTouchLastUsedReturnsTrueOnSuccess()
    {
        $this->wpdbMock->method('update')->willReturn(1);

        $result = $this->repository->touchLastUsed(1, '2026-01-01 00:00:00');

        $this->assertTrue($result);
    }
}
