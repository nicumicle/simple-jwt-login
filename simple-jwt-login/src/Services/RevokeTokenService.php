<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Exceptions\ValidationException;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;

class RevokeTokenService extends AuthenticateService
{
    public function makeAction()
    {
        try {
            $this->checkAuthenticationEnabled();
            $this->checkRevokeTokenEnabled();
            $this->checkAllowedIPAddress();
            $this->validateAuthenticationAuthKey(
                $this->jwtSettings->getAuthenticationSettings()->isRevokeAuthKeyRequired()
            );

            return $this->revokeToken();
        } catch (Exception $exception) {
            $this->wordPressData->triggerAction(
                SimpleJWTLoginHooks::AUDIT_AUTH_LOGOUT_FAILED,
                null,
                null,
                $exception->getMessage()
            );
            throw $exception;
        }
    }

    /**
     * @throws Exception
     */
    private function checkRevokeTokenEnabled()
    {
        if (!$this->jwtSettings->getAuthenticationSettings()->isRevokeTokenEnabled()) {
            throw new Exception(
                __('Revoke Token endpoint is not enabled.', 'simple-jwt-login'),
                ErrorCodes::ERR_REVOKE_TOKEN_NOT_ENABLED
            );
        }
    }

    /**
     * @throws Exception
     */
    private function revokeToken()
    {
        $this->jwt = $this->getJwtFromRequestHeaderOrCookie();
        if (empty($this->jwt)) {
            throw new ValidationException(
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

        $userId    = (int) $this->wordPressData->getUserProperty($user, 'ID');
        $userEmail = (string) $this->wordPressData->getUserProperty($user, 'user_email');

        $userRevokedTokens = $this->getUserRevokedTokensFromDatabase($userId);
        $this->cleanUpUserExpiredTokens($userRevokedTokens, $userId);
        $this->checkIfTokenIsAlreadyRevoked($userRevokedTokens);

        $this->wordPressData->addUserMeta(
            $userId,
            SimpleJWTLoginSettings::REVOKE_TOKEN_KEY,
            $this->jwt
        );

        $this->tokenRepository->deleteByUserId($userId);

        $this->wordPressData->triggerAction(
            SimpleJWTLoginHooks::AUDIT_AUTH_LOGOUT_SUCCESS,
            $userId,
            $userEmail
        );

        $response = [
            'success' => true,
            'message' => __('Token was revoked.', 'simple-jwt-login'),
            'data'    => [
                'jwt' => [
                    $this->jwt
                ]
            ]
        ];

        if ($this->jwtSettings->getHooksSettings()
            ->isHookEnabled(SimpleJWTLoginHooks::HOOK_RESPONSE_REVOKE_TOKEN)
        ) {
            $response = $this->wordPressData
                ->triggerFilter(
                    SimpleJWTLoginHooks::HOOK_RESPONSE_REVOKE_TOKEN,
                    $response,
                    $user
                );
        }

        return $this->wordPressData->createResponse($response);
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
                $this->wordPressData->deleteUserMeta(
                    $userId,
                    SimpleJWTLoginSettings::REVOKE_TOKEN_KEY,
                    $this->wordPressData->sanitizeTextField($token)
                );
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
                throw new Exception(
                    __('Token was already revoked.', 'simple-jwt-login'),
                    ErrorCodes::ERR_REVOKED_TOKEN
                );
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
