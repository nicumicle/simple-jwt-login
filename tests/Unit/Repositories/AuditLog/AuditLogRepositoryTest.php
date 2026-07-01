<?php

namespace SimpleJwtLoginTests\Unit\Repositories\AuditLog;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Repositories\AuditLog\AuditLogRepository;
use stdClass;

class AuditLogRepositoryTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\wpdb
     */
    private $wpdbMock;

    /**
     * @var AuditLogRepository
     */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->wpdbMock = $this->createStub(\wpdb::class);
        $this->wpdbMock->prefix = 'wp_';
        $this->repository       = new AuditLogRepository($this->wpdbMock);
    }

    public function testInsertReturnsTrue()
    {
        $wpdbMock = $this->createMock(\wpdb::class);
        $wpdbMock->prefix = 'wp_';
        $repository = new AuditLogRepository($wpdbMock);

        $wpdbMock->expects($this->once())
            ->method('insert')
            ->with(
                'wp_simple_jwt_login_audit_logs',
                [
                    'event_type' => 'auth.login.success',
                    'user_id'    => 5,
                    'ip_address' => '127.0.0.1',
                    'status'     => 'success',
                    'message'    => null,
                    'api_key_id' => null,
                ]
            )
            ->willReturn(1);

        $result = $repository->insert(
            'auth.login.success',
            5,
            '127.0.0.1',
            'success',
            null
        );

        $this->assertTrue($result);
    }

    public function testInsertWithApiKeyId()
    {
        $wpdbMock = $this->createMock(\wpdb::class);
        $wpdbMock->prefix = 'wp_';
        $repository = new AuditLogRepository($wpdbMock);

        $wpdbMock->expects($this->once())
            ->method('insert')
            ->with(
                'wp_simple_jwt_login_audit_logs',
                [
                    'event_type' => 'api_key.used',
                    'user_id'    => 3,
                    'ip_address' => '10.0.0.1',
                    'status'     => 'success',
                    'message'    => null,
                    'api_key_id' => 7,
                ]
            )
            ->willReturn(1);

        $result = $repository->insert(
            'api_key.used',
            3,
            '10.0.0.1',
            'success',
            null,
            7
        );

        $this->assertTrue($result);
    }

    public function testInsertReturnsFalseOnWpdbError()
    {
        $this->wpdbMock->method('insert')->willReturn(false);

        $result = $this->repository->insert(
            'auth.login.failed',
            null,
            '10.0.0.1',
            'failure',
            'Wrong credentials.',
            null
        );

        $this->assertFalse($result);
    }

    public function testDeleteOlderThanReturnsTrue()
    {
        $this->wpdbMock->method('prepare')
            ->willReturn('DELETE FROM wp_simple_jwt_login_audit_logs WHERE created_at < ?');
        $this->wpdbMock->method('query')->willReturn(3);

        $result = $this->repository->deleteOlderThan('2026-01-01 00:00:00');

        $this->assertTrue($result);
    }

    public function testDeleteOlderThanReturnsFalseOnError()
    {
        $this->wpdbMock->method('prepare')->willReturn('DELETE ...');
        $this->wpdbMock->method('query')->willReturn(false);

        $result = $this->repository->deleteOlderThan('2026-01-01 00:00:00');

        $this->assertFalse($result);
    }

    public function testDeleteAllReturnsTrue()
    {
        $this->wpdbMock->method('query')->willReturn(10);

        $result = $this->repository->deleteAll();

        $this->assertTrue($result);
    }

    public function testDeleteAllReturnsFalseOnError()
    {
        $this->wpdbMock->method('query')->willReturn(false);

        $result = $this->repository->deleteAll();

        $this->assertFalse($result);
    }

    public function testFindPaginatedReturnsStructuredResult()
    {
        $fakeRow        = new stdClass();
        $fakeRow->id    = 1;
        $fakeRow->event_type = 'auth.login.success';

        $this->wpdbMock->method('prepare')->willReturnArgument(0);
        $this->wpdbMock->method('get_var')->willReturn('5');
        $this->wpdbMock->method('get_results')->willReturn([$fakeRow]);

        $result = $this->repository->findPaginated([], 1, 20);

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertSame(5, $result['total']);
        $this->assertCount(1, $result['items']);
    }

    public function testDropTableReturnsTrue()
    {
        $this->wpdbMock->method('query')->willReturn(0);

        $result = $this->repository->dropTable();

        $this->assertTrue($result);
    }

    public function testDropTableReturnsFalseOnError()
    {
        $this->wpdbMock->method('query')->willReturn(false);

        $result = $this->repository->dropTable();

        $this->assertFalse($result);
    }

    public function testFindPaginatedWithUserEmailFilter()
    {
        $fakeRow             = new stdClass();
        $fakeRow->id         = 2;
        $fakeRow->user_email = 'alice@example.com';

        $this->wpdbMock->method('esc_like')->willReturnArgument(0);
        $this->wpdbMock->method('prepare')->willReturnArgument(0);
        $this->wpdbMock->method('get_var')->willReturn('1');
        $this->wpdbMock->method('get_results')->willReturn([$fakeRow]);

        $result = $this->repository->findPaginated(['user_email' => 'alice'], 1, 20);

        $this->assertSame(1, $result['total']);
        $this->assertCount(1, $result['items']);
        $this->assertSame('alice@example.com', $result['items'][0]->user_email);
    }
}
