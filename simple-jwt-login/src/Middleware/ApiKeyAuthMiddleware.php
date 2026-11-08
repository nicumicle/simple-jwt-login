<?php

namespace SimpleJWTLogin\Middleware;

use SimpleJWTLogin\Helpers\ApiKeyPermissions;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Modules\Settings\ApiKeysSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;

class ApiKeyAuthMiddleware
{
    /**
     * Minimum number of seconds between two last_used_at updates of the same key.
     */
    const LAST_USED_THROTTLE_SECONDS = 60;

    /**
     * @var ApiKeyRepositoryInterface
     */
    private $repository;

    /**
     * @var ApiKeysSettings|null
     */
    private $apiKeysSettings;

    /**
     * @param ApiKeyRepositoryInterface $repository
     * @param ApiKeysSettings|null      $apiKeysSettings Null keeps the default (update at most once per minute)
     */
    public function __construct(ApiKeyRepositoryInterface $repository, $apiKeysSettings = null)
    {
        $this->repository = $repository;
        $this->apiKeysSettings = $apiKeysSettings;
    }

    /**
     * Validate the configured API key header against the required permission.
     *
     * Returns the key row as an array on success, null on any failure.
     *
     * @param ServerHelper $serverHelper
     * @param string       $requiredPermission
     * @param string       $headerName         Header to read the raw key from (default: x-api-key)
     * @return array|null
     */
    public function validate(ServerHelper $serverHelper, $requiredPermission, $headerName = 'x-api-key')
    {
        if (!ApiKeyPermissions::isValid($requiredPermission)) {
            return null;
        }

        $headers    = array_change_key_case($serverHelper->getHeaders(), CASE_LOWER);
        $headerKey  = strtolower($headerName);
        $rawKey     = isset($headers[$headerKey]) ? trim((string) $headers[$headerKey]) : '';

        if ($rawKey === '') {
            return null;
        }

        $keyHash = hash('sha256', $rawKey);
        $key     = $this->repository->getByKeyHash($keyHash);

        if ($key === null) {
            return null;
        }

        $permissions = json_decode($key->permissions, true);
        if (!is_array($permissions) || !in_array($requiredPermission, $permissions, true)) {
            return null;
        }

        if ($this->shouldTouchLastUsed($key)) {
            $this->repository->touchLastUsed((int) $key->id, gmdate('Y-m-d H:i:s'));
        }

        return (array) $key;
    }

    /**
     * Skip the DB write when the key was already marked as used recently,
     * so busy keys don't write to the database on every request.
     *
     * @param object $key
     * @return bool
     */
    protected function shouldTouchLastUsed($key)
    {
        $everyRequest = $this->apiKeysSettings !== null
            && $this->apiKeysSettings->isLastUsedUpdatedOnEveryRequest();
        if ($everyRequest || empty($key->last_used_at)) {
            return true;
        }

        $lastUsedAt = strtotime($key->last_used_at . ' UTC');
        if ($lastUsedAt === false) {
            return true;
        }

        return time() - $lastUsedAt >= self::LAST_USED_THROTTLE_SECONDS;
    }
}
