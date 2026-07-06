<?php

namespace SimpleJWTLogin\Services\RevokedTokens;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Services\BaseService;
use SimpleJWTLogin\Services\ServiceInterface;

abstract class BaseRevokedTokenService extends BaseService implements ServiceInterface
{
    /**
     * @throws Exception
     */
    protected function requireAdmin()
    {
        if (!$this->wordPressData->currentUserCan('manage_options')) {
            throw new Exception(
                esc_html(__('You must be an administrator to manage revoked tokens.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_REVOKED_TOKEN_UNAUTHORIZED)
            );
        }
    }

    /**
     * @return int
     * @throws Exception
     */
    protected function requireValidId()
    {
        $revokedTokenId = (int) (isset($this->request['id']) ? $this->request['id'] : 0);
        if ($revokedTokenId <= 0) {
            throw new Exception(
                esc_html(__('Invalid revoked token ID.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_REVOKED_TOKEN_NOT_FOUND)
            );
        }

        return $revokedTokenId;
    }
}
