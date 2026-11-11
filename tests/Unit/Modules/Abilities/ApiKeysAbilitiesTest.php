<?php

namespace SimpleJwtLoginTests\Unit\Modules\Abilities;

use Exception;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Abilities\ApiKeys\CreateApiKeyAbility;
use SimpleJWTLogin\Modules\Abilities\ApiKeys\ListApiKeysAbility;
use SimpleJWTLogin\Modules\Abilities\ApiKeys\RevokeApiKeyAbility;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;

#[RunTestsInSeparateProcesses]
class ApiKeysAbilitiesTest extends AbilityTestCase
{
    public function testListApiKeysMasksKeysAndPaginates()
    {
        $repository = $this->createMock(ApiKeyRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findAll')
            ->with(2, 5)
            ->willReturn([
                'items' => [(object) [
                    'id' => '7', 'user_id' => '1', 'name' => 'CI', 'key_prefix' => 'sjl_abcd',
                    'permissions' => '["read"]', 'expires_at' => null, 'last_used_at' => null,
                    'created_at' => '2026-11-01 00:00:00', 'revoked_at' => null,
                ]],
                'total' => 6,
            ]);

        $settings = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));
        $result   = (new ListApiKeysAbility($settings, $repository))->execute(['page' => 2, 'per_page' => 5]);

        $this->assertSame(6, $result['total']);
        $this->assertSame(2, $result['page']);
        $this->assertSame('sjl_abcd****', $result['items'][0]['key_prefix']);
        $this->assertSame(7, $result['items'][0]['id']);
        $this->assertArrayNotHasKey('key', $result['items'][0]);
    }

    public function testCreateApiKeyReturnsRawKeyOnce()
    {
        $repository = $this->createMock(ApiKeyRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('insert')
            ->with(1, 'Deploy bot', $this->isString(), $this->isString(), '["read"]', null, $this->isString())
            ->willReturn(12);

        $settings = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));
        $result   = (new CreateApiKeyAbility($settings, $repository))->execute([
            'name'        => 'Deploy bot',
            'permissions' => ['read'],
        ]);

        $this->assertSame(12, $result['id']);
        $this->assertStringStartsWith('sjl_', $result['key']);
        $this->assertSame(['read'], $result['permissions']);

        $ability = new CreateApiKeyAbility($settings, $repository);
        $details = $ability->auditDetails(['name' => 'Deploy bot', 'permissions' => ['read']], $result);
        $this->assertSame(['api_key_id' => 12, 'name' => 'Deploy bot', 'permissions' => ['read']], $details);
        $this->assertStringNotContainsString($result['key'], (string) json_encode($details));
    }

    public function testCreateApiKeyValidatesPermissions()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_INVALID_PERMISSION);

        $settings = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));
        (new CreateApiKeyAbility($settings, $this->createStub(ApiKeyRepositoryInterface::class)))->execute([
            'name'        => 'Bad',
            'permissions' => ['root'],
        ]);
    }

    public function testRevokeApiKey()
    {
        $repository = $this->createMock(ApiKeyRepositoryInterface::class);
        $repository->expects($this->once())->method('revokeById')->with(3, $this->isString())->willReturn(true);

        $settings = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));
        $result   = (new RevokeApiKeyAbility($settings, $repository))->execute(['id' => 3]);

        $this->assertSame(['id' => 3, 'revoked' => true], $result);
    }

    public function testRevokeApiKeyFailureThrows()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(ErrorCodes::ERR_API_KEY_REVOKE_FAILED);

        $repository = $this->createStub(ApiKeyRepositoryInterface::class);
        $repository->method('revokeById')->willReturn(false);

        $settings = $this->makeSettings($this->makeWordPressDataStub($this->storedSettings()));
        (new RevokeApiKeyAbility($settings, $repository))->execute(['id' => 3]);
    }
}
