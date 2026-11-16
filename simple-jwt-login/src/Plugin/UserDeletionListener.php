<?php

namespace SimpleJWTLogin\Plugin;

use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Repositories\RefreshToken\Repository as RefreshTokenRepositoryInterface;

/**
 * Removes the credentials of a user deleted outside this plugin (wp-admin, WP-CLI, other plugins),
 * so their API keys and refresh tokens do not stay in the database.
 */
class UserDeletionListener
{
    /**
     * @var ApiKeyRepositoryInterface
     */
    private $apiKeyRepo;

    /**
     * @var RefreshTokenRepositoryInterface
     */
    private $refreshTokenRepo;

    public function __construct(
        ApiKeyRepositoryInterface $apiKeyRepo,
        RefreshTokenRepositoryInterface $refreshTokenRepo
    ) {
        $this->apiKeyRepo       = $apiKeyRepo;
        $this->refreshTokenRepo = $refreshTokenRepo;
    }

    /**
     * Hooked on "deleted_user".
     *
     * @param int $userId
     * @return void
     */
    public function onUserDeleted($userId)
    {
        $this->apiKeyRepo->deleteByUserId((int) $userId);
        $this->refreshTokenRepo->deleteByUserId((int) $userId);
    }
}
