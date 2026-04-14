<?php

namespace SimpleJwtLoginTests\Unit\Repositories\RefreshToken;

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
        $this->wpdbMock = $this->getMockBuilder(\wpdb::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wpdbMock->prefix = 'wp_';
        $this->repository       = new RefreshTokenRepository($this->wpdbMock);
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
}
