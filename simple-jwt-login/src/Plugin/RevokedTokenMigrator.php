<?php

namespace SimpleJWTLogin\Plugin;

use SimpleJWTLogin\Helpers\JwtPayloadHelper;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\RevokedToken\Repository as RevokedTokenRepositoryInterface;
use SimpleJWTLogin\Repositories\Wordpress\Repository as WordPressRepositoryInterface;

class RevokedTokenMigrator
{
    /**
     * @var WordPressRepositoryInterface
     */
    private $wordPressData;

    /**
     * @var RevokedTokenRepositoryInterface
     */
    private $revokedTokenRepo;

    public function __construct(
        WordPressRepositoryInterface $wordPressData,
        RevokedTokenRepositoryInterface $revokedTokenRepo
    ) {
        $this->wordPressData = $wordPressData;
        $this->revokedTokenRepo = $revokedTokenRepo;
    }

    /**
     * Migrate legacy revoked JWTs stored as usermeta into the revoked tokens table,
     * then delete the usermeta rows for each migrated user.
     */
    public function migrate()
    {
        $userIds = $this->wordPressData->getUserIdsWithMeta(SimpleJWTLoginSettings::REVOKE_TOKEN_KEY);
        foreach ((array) $userIds as $userId) {
            $this->migrateUser((int) $userId);
        }
    }

    /**
     * @param int $userId
     */
    protected function migrateUser($userId)
    {
        $tokens = $this->wordPressData->getUserMeta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY);
        if (empty($tokens)) {
            return;
        }

        foreach ((array) $tokens as $jwt) {
            $this->migrateToken($userId, $jwt);
        }

        $this->wordPressData->deleteUserMeta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY, '');
    }

    /**
     * @param int    $userId
     * @param string $jwt
     */
    protected function migrateToken($userId, $jwt)
    {
        $payload = JwtPayloadHelper::decode($jwt);
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return;
        }

        $tokenHash = hash('sha256', $jwt);
        if ($this->revokedTokenRepo->existsForUser($userId, $tokenHash)) {
            return;
        }

        $expiresAt = isset($payload['exp']) ? gmdate('Y-m-d H:i:s', (int) $payload['exp']) : null;
        $this->revokedTokenRepo->insert($userId, $tokenHash, $expiresAt);
    }
}
