<?php

namespace SimpleJWTLogin\Services\ApiKeys;

use Exception;
use SimpleJWTLogin\ErrorCodes;

class UpdateApiKeyService extends BaseApiKeyService
{
    /**
     * @return mixed
     * @throws Exception
     */
    public function makeAction()
    {
        $this->requireLoggedIn();

        $keyId = (int) ($this->request['id'] ?? 0);
        if ($keyId <= 0) {
            throw new Exception(
                __('Invalid API key ID.', 'simple-jwt-login'),
                ErrorCodes::ERR_API_KEY_NOT_FOUND
            );
        }

        $this->requireKeyOwnership($keyId);

        $name = trim((string) ($this->request['name'] ?? ''));
        if ($name === '') {
            throw new Exception(
                __('API key name is required.', 'simple-jwt-login'),
                ErrorCodes::ERR_API_KEY_MISSING_NAME
            );
        }

        $permissions = $this->normalizeAndValidatePermissions($this->request['permissions'] ?? []);

        $expiresAt = !empty($this->request['expires_at'])
            ? (string) $this->request['expires_at']
            : null;

        $updated = $this->apiKeyRepository->updateById(
            $keyId,
            $name,
            (string) json_encode($permissions),
            $expiresAt
        );

        if (!$updated) {
            throw new Exception(
                __('Failed to update API key.', 'simple-jwt-login'),
                ErrorCodes::ERR_API_KEY_UPDATE_FAILED
            );
        }

        return $this->wordPressData->createResponse([
            'success' => true,
            'message' => __('API key updated successfully.', 'simple-jwt-login'),
        ]);
    }
}
