<?php

namespace SimpleJwtLoginTests\Unit\Cli;

use SimpleJWTLogin\Cli\CliAuditLog;

/**
 * Records audit calls instead of writing them.
 */
class RecordingAuditLog extends CliAuditLog
{
    /** @var array<int, array{command: string, status: string, details: array}> */
    public static array $records = [];

    public function __construct()
    {
    }

    public function start(): void
    {
    }

    public function record($command, $status, array $details = array()): void
    {
        self::$records[] = ['command' => $command, 'status' => $status, 'details' => $details];
    }
}
