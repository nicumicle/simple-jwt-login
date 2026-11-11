<?php

namespace SimpleJWTLogin\Modules\Abilities\ApiKeys;

use Exception;
use SimpleJWTLogin\Modules\Abilities\AbstractAbility;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Services\ApiKeys\BaseApiKeyService;
use WP_REST_Response;

/**
 * Runs the existing API key REST services so abilities get the same
 * validation, ownership and capability checks as the REST endpoints.
 */
abstract class AbstractApiKeyAbility extends AbstractAbility
{
    /**
     * @var ApiKeyRepositoryInterface
     */
    protected $apiKeyRepository;

    /**
     * @param SimpleJWTLoginSettings    $jwtSettings
     * @param ApiKeyRepositoryInterface $apiKeyRepository
     */
    public function __construct(SimpleJWTLoginSettings $jwtSettings, ApiKeyRepositoryInterface $apiKeyRepository)
    {
        parent::__construct($jwtSettings);
        $this->apiKeyRepository = $apiKeyRepository;
    }

    /**
     * @param BaseApiKeyService $service
     * @param array             $request
     * @return array The response "data" payload (or the whole body when there is none).
     * @throws Exception
     */
    protected function runService(BaseApiKeyService $service, array $request)
    {
        $response = $service
            ->withRequest($request)
            ->withSettings($this->jwtSettings)
            ->withApiKeyRepository($this->apiKeyRepository)
            ->makeAction();

        $body = $response instanceof WP_REST_Response ? $response->get_data() : $response;
        if (!is_array($body)) {
            return [];
        }

        return isset($body['data']) && is_array($body['data']) ? $body['data'] : $body;
    }
}
