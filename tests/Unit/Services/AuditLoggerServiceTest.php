<?php

namespace SimpleJwtLoginTests\Unit\Services;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\Settings\AuditLogSettings;
use SimpleJWTLogin\Repositories\AuditLog\Repository as AuditLogRepositoryInterface;
use SimpleJWTLogin\Services\AuditLoggerService;

class AuditLoggerServiceTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|AuditLogRepositoryInterface
     */
    private $repositoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|AuditLogSettings
     */
    private $settingsMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ServerHelper
     */
    private $serverHelperMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->repositoryMock   = $this->getMockBuilder(AuditLogRepositoryInterface::class)->getMock();
        $this->settingsMock     = $this->createStub(AuditLogSettings::class);
        $this->serverHelperMock = $this->createStub(ServerHelper::class);
    }

    private function makeLogger()
    {
        return new AuditLoggerService(
            $this->repositoryMock,
            $this->settingsMock,
            $this->serverHelperMock
        );
    }

    public function testLogSkipsWhenAuditDisabled()
    {
        $this->settingsMock->method('isEnabled')->willReturn(false);

        $this->repositoryMock->expects($this->never())->method('insert');

        $this->makeLogger()->log(AuditEvents::AUTH_LOGIN_SUCCESS, 1, 'test@example.com', 'success');
    }

    public function testLogSkipsWhenEventNotEnabled()
    {
        $this->settingsMock->method('isEnabled')->willReturn(true);
        $this->settingsMock->method('isEventEnabled')->willReturn(false);

        $this->repositoryMock->expects($this->never())->method('insert');

        $this->makeLogger()->log(AuditEvents::AUTH_LOGIN_SUCCESS, 1, 'test@example.com', 'success');
    }

    public function testLogWritesWhenEnabledAndEventEnabled()
    {
        $this->settingsMock->method('isEnabled')->willReturn(true);
        $this->settingsMock->method('isEventEnabled')->willReturn(true);
        $this->serverHelperMock->method('getClientIP')->willReturn('1.2.3.4');

        $this->repositoryMock->expects($this->once())
            ->method('insert')
            ->with(
                AuditEvents::AUTH_LOGIN_SUCCESS,
                5,
                'test@example.com',
                '1.2.3.4',
                'success',
                null
            );

        $this->makeLogger()->log(AuditEvents::AUTH_LOGIN_SUCCESS, 5, 'test@example.com', 'success');
    }

    public function testLogPassesMessageAndStatusCorrectly()
    {
        $this->settingsMock->method('isEnabled')->willReturn(true);
        $this->settingsMock->method('isEventEnabled')->willReturn(true);
        $this->serverHelperMock->method('getClientIP')->willReturn('10.0.0.1');

        $this->repositoryMock->expects($this->once())
            ->method('insert')
            ->with(
                AuditEvents::AUTH_LOGIN_FAILED,
                null,
                'bad@example.com',
                '10.0.0.1',
                'failure',
                'Wrong user credentials.'
            );

        $this->makeLogger()->log(
            AuditEvents::AUTH_LOGIN_FAILED,
            null,
            'bad@example.com',
            'failure',
            'Wrong user credentials.'
        );
    }

    public function testLogPassesIpFromServerHelper()
    {
        $this->settingsMock->method('isEnabled')->willReturn(true);
        $this->settingsMock->method('isEventEnabled')->willReturn(true);
        $this->serverHelperMock->method('getClientIP')->willReturn('192.168.1.100');

        $this->repositoryMock->expects($this->once())
            ->method('insert')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                '192.168.1.100',
                $this->anything(),
                $this->anything()
            );

        $this->makeLogger()->log(AuditEvents::AUTH_REGISTER_SUCCESS, 3, 'new@example.com', 'success');
    }
}
