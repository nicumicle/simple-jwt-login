<?php

namespace SimpleJWTLogin\Services\ApiKeys;

use Exception;
use SimpleJWTLogin\ErrorCodes;

class RevokeApiKeyService extends BaseApiKeyService
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

        $revoked = $this->apiKeyRepository->revokeById($keyId, gmdate('Y-m-d H:i:s'));

        if (!$revoked) {
            throw new Exception(
                __('Failed to revoke API key.', 'simple-jwt-login'),
                ErrorCodes::ERR_API_KEY_REVOKE_FAILED
            );
        }

        return $this->wordPressData->createResponse([
            'success' => true,
            'message' => __('API key revoked successfully.', 'simple-jwt-login'),
        ]);
    }
}
