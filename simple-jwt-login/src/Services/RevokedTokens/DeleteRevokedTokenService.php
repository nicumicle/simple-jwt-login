<?php

namespace SimpleJWTLogin\Services\RevokedTokens;

use Exception;
use SimpleJWTLogin\ErrorCodes;

class DeleteRevokedTokenService extends BaseRevokedTokenService
{
    /**
     * @return mixed
     * @throws Exception
     */
    public function makeAction()
    {
        $this->requireAdmin();
        $revokedTokenId = $this->requireValidId();

        if (!$this->revokedTokenRepo->existsById($revokedTokenId)) {
            throw new Exception(
                esc_html(__('Invalid revoked token ID.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_REVOKED_TOKEN_NOT_FOUND)
            );
        }

        if (!$this->revokedTokenRepo->deleteById($revokedTokenId)) {
            throw new Exception(
                esc_html(__('Failed to delete revoked token entry.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_REVOKED_TOKEN_DELETE_FAILED)
            );
        }

        return $this->wordPressData->createResponse([
            'success' => true,
            'message' => __(
                'Revoked token entry deleted. If the token has not expired, it is valid again.',
                'simple-jwt-login'
            ),
        ]);
    }
}
