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
        $keyId = $this->requireValidKeyId();
        $name = $this->requireName();

        $permissions = $this->normalizeAndValidatePermissions(
            isset($this->request['permissions']) ? $this->request['permissions'] : []
        );
        $this->requireCapabilityForPermissions($permissions);

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
                esc_html(__('Failed to update API key.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_API_KEY_UPDATE_FAILED)
            );
        }

        return $this->wordPressData->createResponse([
            'success' => true,
            'message' => __('API key updated successfully.', 'simple-jwt-login'),
        ]);
    }
}
