<?php

namespace SimpleJwtLoginTests\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Plugin\UserDeletionListener;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Repositories\RefreshToken\Repository as RefreshTokenRepositoryInterface;

class UserDeletionListenerTest extends TestCase
{
    /**
     * A user deleted in wp-admin must not leave API keys or refresh tokens behind.
     */
    public function testDeletedUserLosesApiKeysAndRefreshTokens(): void
    {
        $apiKeys = $this->createMock(ApiKeyRepositoryInterface::class);
        $apiKeys->expects($this->once())->method('deleteByUserId')->with(12);
        $refreshTokens = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokens->expects($this->once())->method('deleteByUserId')->with(12);

        (new UserDeletionListener($apiKeys, $refreshTokens))->onUserDeleted('12');
    }
}
