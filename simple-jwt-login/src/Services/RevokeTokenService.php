<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Exceptions\ValidationException;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;

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
            if ($this->jwtSettings->getAuditLogSettings()->isAuditEventEnabled(AuditEvents::AUTH_LOGOUT_FAILED)) {
                $this->wordPressData->doAction(
                    SimpleJWTLoginHooks::AUDIT_AUTH_LOGOUT_FAILED,
                    null,
                    null,
                    $exception->getMessage()
                );
            }
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
                esc_html(__('Revoke Token endpoint is not enabled.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_REVOKE_TOKEN_NOT_ENABLED)
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
                esc_html(__('The `jwt` parameter is missing.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_MISSING_JWT_AUTH_VALIDATE)
            );
        }

        $loginParameter = $this->validateJWTAndGetUserValueFromPayload(
            $this->jwtSettings->getLoginSettings()->getJwtLoginByParameter()
        );
        $user           = $this->getUserDetails($loginParameter);
        if ($user === null) {
            throw new Exception(
                esc_html(__('User not found.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND)
            );
        }

        $userId    = (int) $this->wordPressData->getUserProperty($user, 'ID');
        $userEmail = (string) $this->wordPressData->getUserProperty($user, 'user_email');

        $tokenHash = hash('sha256', $this->jwt);
        if ($this->revokedTokenRepo->existsForUser($userId, $tokenHash)) {
            throw new Exception(
                esc_html(__('Token was already revoked.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_REVOKED_TOKEN)
            );
        }

        $payload   = $this->getPayloadFromJWT($this->jwt);
        $expiresAt = isset($payload['exp']) ? gmdate('Y-m-d H:i:s', (int) $payload['exp']) : null;
        $this->revokedTokenRepo->insert($userId, $tokenHash, $expiresAt);

        $this->tokenRepository->deleteByUserId($userId);

        if ($this->jwtSettings->getAuditLogSettings()->isAuditEventEnabled(AuditEvents::AUTH_LOGOUT_SUCCESS)) {
            $this->wordPressData->doAction(
                SimpleJWTLoginHooks::AUDIT_AUTH_LOGOUT_SUCCESS,
                $userId,
                $userEmail
            );
        }

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
                ->applyFilters(
                    SimpleJWTLoginHooks::HOOK_RESPONSE_REVOKE_TOKEN,
                    $response,
                    $user
                );
        }

        return $this->wordPressData->createResponse($response);
    }
}
