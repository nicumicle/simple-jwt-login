<?php

namespace SimpleJWTLogin\Services\ApiKeys;

use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Services\ServiceInterface;

interface ApiKeyServiceInterface extends ServiceInterface
{
    /**
     * @param ApiKeyRepositoryInterface $repository
     * @return $this
     */
    public function withApiKeyRepository(ApiKeyRepositoryInterface $repository);
}
