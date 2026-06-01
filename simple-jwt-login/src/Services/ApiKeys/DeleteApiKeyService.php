<?php

namespace SimpleJWTLogin\Services\ApiKeys;

use Exception;
use SimpleJWTLogin\ErrorCodes;

class DeleteApiKeyService extends BaseApiKeyService
{
    /**
     * @return mixed
     * @throws Exception
     */
    public function makeAction()
    {
        $this->requireLoggedIn();
        $keyId = $this->requireValidKeyId();

        $deleted = $this->apiKeyRepository->deleteById($keyId);

        if (!$deleted) {
            throw new Exception(
                esc_html(__('Failed to delete API key.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_API_KEY_DELETE_FAILED)
            );
        }

        return $this->wordPressData->createResponse([
            'success' => true,
            'message' => __('API key deleted successfully.', 'simple-jwt-login'),
        ]);
    }
}
