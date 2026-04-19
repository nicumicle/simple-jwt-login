<?php

namespace SimpleJwtLoginTests\Unit\Repositories\WebhookLog;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Repositories\WebhookLog\WebhookLogRepository;

class WebhookLogRepositoryTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\wpdb
     */
    private $wpdbMock;

    /**
     * @var WebhookLogRepository
     */
    private $repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->wpdbMock = $this->getMockBuilder(\wpdb::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wpdbMock->prefix = 'wp_';
        $this->repository       = new WebhookLogRepository($this->wpdbMock);
    }

    public function testInsertReturnsTrue()
    {
        $this->wpdbMock->expects($this->once())
            ->method('insert')
            ->willReturn(1);

        $result = $this->repository->insert('https://example.com/hook', 'login', 'POST', 200, null);

        $this->assertTrue($result);
    }

    public function testInsertReturnsFalseOnWpdbError()
    {
        $this->wpdbMock->method('insert')->willReturn(false);

        $result = $this->repository->insert('https://example.com/hook', 'login', 'POST', null, 'Connection refused');

        $this->assertFalse($result);
    }

    public function testDeleteOlderThanReturnsTrue()
    {
        $this->wpdbMock->method('prepare')
            ->willReturn('DELETE FROM wp_simple_jwt_login_webhook_logs WHERE created_at < ?');
        $this->wpdbMock->method('query')->willReturn(5);

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

    public function testDropTableReturnsTrue()
    {
        $this->wpdbMock->method('query')->willReturn(1);

        $result = $this->repository->dropTable();

        $this->assertTrue($result);
    }

    public function testDropTableReturnsFalseOnError()
    {
        $this->wpdbMock->method('query')->willReturn(false);

        $result = $this->repository->dropTable();

        $this->assertFalse($result);
    }
}
