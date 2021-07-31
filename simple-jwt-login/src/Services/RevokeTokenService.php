<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

class RevokeTokenService extends AuthenticateService
{
    public function makeAction()
    {
        $this->checkAuthenticationEnabled();
        $this->checkAllowedIPAddress();
        $this->validateAuthenticationAuthKey(ErrorCodes::ERR_INVALID_AUTH_CODE_PROVIDED);

        return $this->revokeToken();
    }

    /**
     * @throws Exception
     */
    private function revokeToken()
    {
        $this->jwt = $this->getJwtFromRequestHeaderOrCookie();
        if (empty($this->jwt)) {
            throw new Exception(
                __('The `jwt` parameter is missing.', 'simple-jwt-login'),
                ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE
            );
        }

        $loginParameter = $this->validateJWTAndGetUserValueFromPayload(
            $this->jwtSettings->getLoginSettings()->getJwtLoginByParameter()
        );
        $user           = $this->getUserDetails($loginParameter);
        if ($user === null) {
            throw new Exception(
                __('User not found.', 'simple-jwt-login'),
                ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND
            );
        }

        $userRevokedTokens = $this->getUserRevokedTokensFromDatabase(
            $this->wordPressData->getUserProperty($user, 'id')
        );
        $this->cleanUpUserExpiredTokens(
            $userRevokedTokens,
            $this->wordPressData->getUserProperty($user, 'id')
        );
        $this->checkIfTokenIsAlreadyRevoked($userRevokedTokens);

        $this->wordPressData->addUserMeta(
            $this->wordPressData->getUserProperty($user, 'id'),
            SimpleJWTLoginSettings::REVOKE_TOKEN_KEY,
            $this->jwt
        );

        return $this->wordPressData->createResponse(
            [
                'success' => true,
                'message' => __('Token was revoked.', 'simple-jwt-login'),
                'data'    => [
                    'jwt' => [
                        $this->jwt
                    ]
                ]
            ]
        );
    }

    /**
     * @param array $revokedTokens
     * @param int $userId
     */
    private function cleanUpUserExpiredTokens($revokedTokens, $userId)
    {
        if (empty($revokedTokens)) {
            return;
        }
        $currentTime = time();
        foreach ($revokedTokens as $token) {
            $payload = $this->getPayloadFromJWT($token);
            if (isset($payload['exp']) && $payload['exp'] < $currentTime) {
                $this->wordPressData->deleteUserMeta($userId, SimpleJWTLoginSettings::REVOKE_TOKEN_KEY, $token);
            }
        }
    }

    /**
     * @param array $userRevokedTokens
     *
     * @return bool
     * @throws Exception
     */
    private function checkIfTokenIsAlreadyRevoked($userRevokedTokens)
    {
        if (empty($userRevokedTokens)) {
            return false;
        }
        foreach ($userRevokedTokens as $token) {
            if ($token === $this->jwt) {
                throw new Exception('Token was already revoked.');
            }
        }

        return false;
    }

    /**
     * @param int $userId
     *
     * @return mixed
     */
    private function getUserRevokedTokensFromDatabase($userId)
    {
        return $this->wordPressData->getUserMeta(
            $userId,
            SimpleJWTLoginSettings::REVOKE_TOKEN_KEY
        );
    }
}
