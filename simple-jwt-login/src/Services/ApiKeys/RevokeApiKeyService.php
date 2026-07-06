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
        $keyId = $this->requireValidKeyId();

        $revoked = $this->apiKeyRepository->revokeById($keyId, gmdate('Y-m-d H:i:s'));

        if (!$revoked) {
            throw new Exception(
                esc_html(__('Failed to revoke API key.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_API_KEY_REVOKE_FAILED)
            );
        }

        return $this->wordPressData->createResponse([
            'success' => true,
            'message' => __('API key revoked successfully.', 'simple-jwt-login'),
        ]);
    }
}
