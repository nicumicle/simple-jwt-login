<?php

namespace SimpleJWTLogin\Services\ApiKeys;

use Exception;
use SimpleJWTLogin\ErrorCodes;

class CreateApiKeyService extends BaseApiKeyService
{
    /**
     * @return mixed
     * @throws Exception
     */
    public function makeAction()
    {
        $this->requireLoggedIn();
        $name = $this->requireName();

        $permissions = $this->normalizeAndValidatePermissions(
            isset($this->request['permissions']) ? $this->request['permissions'] : []
        );
        $this->requireCapabilityForPermissions($permissions);

        $expiresAt = !empty($this->request['expires_at'])
            ? (string) $this->request['expires_at']
            : null;

        $rawKey    = 'sjl_' . bin2hex(openssl_random_pseudo_bytes(16));
        $keyHash   = hash('sha256', $rawKey);
        $keyPrefix = substr($rawKey, 0, 8);
        $createdAt = gmdate('Y-m-d H:i:s');
        $userId    = $this->wordPressData->getCurrentUserId();

        $keyId = $this->apiKeyRepository->insert(
            $userId,
            $name,
            $keyHash,
            $keyPrefix,
            (string) json_encode($permissions),
            $expiresAt,
            $createdAt
        );

        if ($keyId === false) {
            throw new Exception(
                esc_html(__('Failed to create API key.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_API_KEY_CREATE_FAILED)
            );
        }

        return $this->wordPressData->createResponse([
            'success' => true,
            'data'    => [
                'id'          => $keyId,
                'key'         => $rawKey,
                'name'        => $name,
                'key_prefix'  => $keyPrefix,
                'permissions' => $permissions,
                'expires_at'  => $expiresAt,
            ],
        ]);
    }
}
