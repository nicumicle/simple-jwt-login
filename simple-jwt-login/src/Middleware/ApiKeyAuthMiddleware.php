<?php

namespace SimpleJWTLogin\Middleware;

use SimpleJWTLogin\Helpers\ApiKeyPermissions;
use SimpleJWTLogin\Helpers\ServerHelper;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;

class ApiKeyAuthMiddleware
{
    /**
     * @var ApiKeyRepositoryInterface
     */
    private $repository;

    /**
     * @param ApiKeyRepositoryInterface $repository
     */
    public function __construct(ApiKeyRepositoryInterface $repository)
    {
        $this->repository = $repository;
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

        $this->repository->touchLastUsed((int) $key->id, gmdate('Y-m-d H:i:s'));

        return (array) $key;
    }
}
