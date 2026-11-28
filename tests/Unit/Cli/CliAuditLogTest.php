<?php

namespace SimpleJwtLoginTests\Unit\Cli;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Cli\CliAuditLog;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\Settings\AuditLogSettings;
use SimpleJWTLogin\Repositories\AuditLog\Repository as AuditLogRepository;
use SimpleJWTLogin\Repositories\Wordpress\Repository;

class CliAuditLogTest extends TestCase
{
    #[DataProvider('recordProvider')]
    public function testRecord(bool $enabledBefore, bool $enabledAfter, string $status, ?string $expectedEvent): void
    {
        $repository = $this->createMock(AuditLogRepository::class);
        $repository->expects($expectedEvent === null ? $this->never() : $this->once())
            ->method('insert')
            ->with($expectedEvent, 5, $this->anything(), $status, $this->callback(function ($message) {
                $decoded = json_decode($message, true);
                return $decoded['source'] === 'cli' && $decoded['command'] === 'wp jwt config set' && $decoded['key'] === 'x';
            }));
        $wordPressData = $this->createStub(Repository::class);
        $wordPressData->method('getCurrentUserId')->willReturn(5);
        $wordPressData->method('canFinishRequest')->willReturn(false);

        $auditLog = new class ($repository, $wordPressData, [$enabledBefore, $enabledAfter]) extends CliAuditLog {
            /** @var array<int, bool> */
            private array $states;

            public function __construct($repository, $wordPressData, array $states)
            {
                parent::__construct($repository, $wordPressData);
                $this->states = $states;
            }

            protected function loadAuditSettings(): AuditLogSettings
            {
                $enabled = array_shift($this->states);

                return (new AuditLogSettings())->withSettings(['audit_log' => [
                    'enabled'        => $enabled,
                    'enabled_events' => AuditEvents::all(),
                ]]);
            }
        };

        $auditLog->start();
        $auditLog->record('config set', $status, ['key' => 'x']);
    }

    public static function recordProvider(): array
    {
        return [
            'enabled before and after'          => [true, true, 'success', AuditEvents::CLI_COMMAND_SUCCESS],
            'the command disables the log'      => [true, false, 'success', AuditEvents::CLI_COMMAND_SUCCESS],
            'the command enables the log'       => [false, true, 'success', AuditEvents::CLI_COMMAND_SUCCESS],
            'disabled before and after'         => [false, false, 'success', null],
            'failure'                           => [true, true, 'failure', AuditEvents::CLI_COMMAND_FAILED],
        ];
    }
}
