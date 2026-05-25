<?php

namespace SimpleJWTLogin\Services\ApiKeys;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\ApiKeyPermissions;
use SimpleJWTLogin\Repositories\ApiKey\ApiKeyRepositoryInterface;
use SimpleJWTLogin\Services\BaseService;

abstract class BaseApiKeyService extends BaseService implements ApiKeyServiceInterface
{
    /**
     * @var ApiKeyRepositoryInterface
     */
    protected $apiKeyRepository;

    /**
     * @param ApiKeyRepositoryInterface $repository
     * @return $this
     */
    public function withApiKeyRepository(ApiKeyRepositoryInterface $repository)
    {
        $this->apiKeyRepository = $repository;

        return $this;
    }

    /**
     * @throws Exception
     */
    protected function requireLoggedIn()
    {
        if (!$this->wordPressData->isUserLoggedIn()) {
            throw new Exception(
                __('You must be logged in to manage API keys.', 'simple-jwt-login'),
                ErrorCodes::ERR_API_KEY_UNAUTHORIZED
            );
        }
    }

    /**
     * @param int $keyId
     * @throws Exception
     */
    protected function requireKeyOwnership($keyId)
    {
        if ($this->wordPressData->currentUserCan('manage_options')) {
            return;
        }

        $key = $this->apiKeyRepository->findById($keyId);
        if ($key === null || (int) $key->user_id !== $this->wordPressData->getCurrentUserId()) {
            throw new Exception(
                __('You are not allowed to manage this API key.', 'simple-jwt-login'),
                ErrorCodes::ERR_API_KEY_UNAUTHORIZED
            );
        }
    }

    /**
     * Verify the current user holds the WordPress capability required by each
     * permission they are trying to assign. Admins (manage_options) bypass all
     * checks. Throws ERR_API_KEY_UNAUTHORIZED (→ HTTP 403) on failure.
     *
     * @param array $permissions Already-normalised permission strings
     * @throws Exception
     */
    protected function requireCapabilityForPermissions(array $permissions)
    {
        if ($this->wordPressData->currentUserCan('manage_options')) {
            return;
        }

        foreach ($permissions as $permission) {
            $cap = ApiKeyPermissions::permissionToCapability((string) $permission);
            if ($cap !== null && !$this->wordPressData->currentUserCan($cap)) {
                throw new Exception(
                    sprintf(
                        __('You do not have permission to assign the "%s" permission to an API key.', 'simple-jwt-login'),
                        $permission
                    ),
                    ErrorCodes::ERR_API_KEY_UNAUTHORIZED
                );
            }
        }
    }

    /**
     * @param mixed $permissions
     * @return array
     * @throws Exception
     */
    /**
     * @return int
     * @throws Exception
     */
    protected function requireValidKeyId()
    {
        $keyId = (int) (isset($this->request['id']) ? $this->request['id'] : 0);
        if ($keyId <= 0) {
            throw new Exception(
                __('Invalid API key ID.', 'simple-jwt-login'),
                ErrorCodes::ERR_API_KEY_NOT_FOUND
            );
        }

        $this->requireKeyOwnership($keyId);

        return $keyId;
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function requireName()
    {
        $name = trim((string) (isset($this->request['name']) ? $this->request['name'] : ''));
        if ($name === '') {
            throw new Exception(
                __('API key name is required.', 'simple-jwt-login'),
                ErrorCodes::ERR_API_KEY_MISSING_NAME
            );
        }

        return $name;
    }

    protected function normalizeAndValidatePermissions($permissions)
    {
        if (empty($permissions)) {
            throw new Exception(
                __('At least one permission is required.', 'simple-jwt-login'),
                ErrorCodes::ERR_API_KEY_MISSING_PERMISSIONS
            );
        }

        if (is_string($permissions)) {
            $permissions = array_filter(array_map('trim', explode(',', $permissions)));
        }

        foreach ((array) $permissions as $permission) {
            if (!ApiKeyPermissions::isValid((string) $permission)) {
                throw new Exception(
                    sprintf(__('Invalid permission: %s', 'simple-jwt-login'), $permission),
                    ErrorCodes::ERR_API_KEY_INVALID_PERMISSION
                );
            }
        }

        return array_values((array) $permissions);
    }
}
