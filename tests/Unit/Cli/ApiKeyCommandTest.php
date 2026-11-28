<?php

namespace SimpleJwtLoginTests\Unit\Cli;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Cli\ApiKeyCommand;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use WP_CLI;

class ApiKeyCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        WP_CLI::reset();
        RecordingAuditLog::$records = [];
    }

    public function testListShowsOnlyTheKeyPrefix(): void
    {
        $repository = $this->createStub(ApiKeyRepositoryInterface::class);
        $repository->method('findAll')->willReturn(['total' => 1, 'items' => [(object) [
            'id' => '3', 'user_id' => '1', 'name' => 'ci', 'key_prefix' => 'sjl_abcd', 'key_hash' => 'HASH',
            'permissions' => '["read"]', 'expires_at' => null, 'last_used_at' => null, 'revoked_at' => null,
        ]]]);

        $this->makeCommand($repository)->listKeys([], ['format' => 'json', 'fields' => 'id,key_prefix,permissions,key_hash']);

        $formatted = WP_CLI::$formatted;
        $item = $formatted[0]['items'][0];
        $this->assertSame('json', $formatted[0]['format']);
        $this->assertSame('sjl_abcd****', $item['key_prefix']);
        $this->assertSame(['read'], $item['permissions']);
        // Asking for it by --fields must not print the hash.
        $this->assertNull($item['key_hash']);
    }

    public function testRevokeUnknownKey(): void
    {
        $repository = $this->createMock(ApiKeyRepositoryInterface::class);
        $repository->method('findById')->willReturn(null);
        $repository->expects($this->never())->method('revokeById');

        $this->makeCommand($repository)->revoke(['9']);

        $this->assertSame(['API key not found.'], WP_CLI::$errors);
    }

    public function testRevokeKey(): void
    {
        $repository = $this->createMock(ApiKeyRepositoryInterface::class);
        $repository->method('findById')->willReturn((object) ['id' => 9]);
        $repository->expects($this->once())->method('revokeById')->with(9)->willReturn(true);

        $this->makeCommand($repository)->revoke(['9']);

        $this->assertSame(['API key revoked successfully.'], WP_CLI::$successes);
        $this->assertSame([['command' => 'api-key revoke', 'status' => 'success', 'details' => ['id' => 9]]], RecordingAuditLog::$records);
    }

    private function makeCommand(ApiKeyRepositoryInterface $repository): ApiKeyCommand
    {
        return new class ($repository) extends ApiKeyCommand {
            protected function createAuditLog(): \SimpleJWTLogin\Cli\CliAuditLog
            {
                return new RecordingAuditLog();
            }

            /** @var mixed */
            private $repository;

            public function __construct($repository)
            {
                $this->repository = $repository;
            }

            protected function createApiKeyRepository(): ApiKeyRepositoryInterface
            {
                return $this->repository;
            }
        };
    }
}
