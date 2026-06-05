<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Exceptions\ValidationException;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use WP_REST_Response;

class RefreshTokenService extends AuthenticateService
{
    public function makeAction()
    {
        try {
            $this->checkAuthenticationEnabled();
            $this->checkJwtNotRevoked();
            $this->checkRefreshTokenEnabled();
            $this->checkAllowedIPAddress();
            $this->validateAuthenticationAuthKey(
                $this->jwtSettings->getAuthenticationSettings()->isRefreshAuthKeyRequired()
            );

            return $this->refreshJwt();
        } catch (Exception $exception) {
            if ($this->jwtSettings->getAuditLogSettings()->isAuditEventEnabled(AuditEvents::AUTH_REFRESH_TOKEN_FAILED)) {
                $this->wordPressData->doAction(
                    SimpleJWTLoginHooks::AUDIT_AUTH_REFRESH_TOKEN_FAILED,
                    null,
                    null,
                    $exception->getMessage()
                );
            }
            throw $exception;
        }
    }

    /**
     * If a JWT is present in the request, validate it and throw if it has been revoked.
     * This ensures revoked JWTs cannot be used to obtain new tokens even on the refresh endpoint.
     *
     * @throws Exception
     */
    private function checkJwtNotRevoked()
    {
        $jwt = $this->getJwtFromRequestHeaderOrCookie();
        if (empty($jwt)) {
            return;
        }

        try {
            $this->jwt = $jwt;
            $userValue = $this->validateJWTAndGetUserValueFromPayload(
                $this->jwtSettings->getLoginSettings()->getJwtLoginByParameter()
            );
            $user = $this->getUserDetails($userValue);
            if ($user !== null) {
                $this->validateJwtRevoked(
                    $this->wordPressData->getUserProperty($user, 'ID'),
                    $jwt
                );
            }
        } catch (Exception $exception) {
            if ($exception->getCode() === ErrorCodes::ERR_REVOKED_TOKEN) {
                throw $exception;
            }
            // Ignore other JWT errors - the refresh endpoint uses opaque tokens
        }
    }

    /**
     * @throws Exception
     */
    private function checkRefreshTokenEnabled()
    {
        if (!$this->jwtSettings->getAuthenticationSettings()->isRefreshTokenEnabled()) {
            throw new Exception(
                esc_html(__('Refresh Token endpoint is not enabled.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_REFRESH_TOKEN_NOT_ENABLED)
            );
        }
    }

    /**
     * @return WP_REST_Response
     * @throws Exception
     */
    private function refreshJwt()
    {
        $refreshToken = isset($this->request['refresh_token']) ? $this->request['refresh_token'] : null;
        if (empty($refreshToken)) {
            throw new ValidationException(
                esc_html(__('Refresh token is missing.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH)
            );
        }

        // Validate refresh token against database
        $encryptedToken = $this->encryptRefreshToken($refreshToken);
        $tokenData = $this->tokenRepository->getByToken($encryptedToken);
        
        if ($tokenData === null) {
            throw new Exception(
                esc_html(__('Invalid refresh token.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH)
            );
        }

        $user = $this->wordPressData->getUserDetailsById($tokenData->user_id);
        if (!$this->wordPressData->isInstanceOfuser($user)) {
            throw new Exception(esc_html(__('User not found.', 'simple-jwt-login')), absint(ErrorCodes::ERR_REVOKED_TOKEN));
        }

        // Generate new JWT payload for the user
        $newPayload = isset($this->request['payload'])
            ? json_decode(
                stripslashes(
                    $this->wordPressData->sanitizeTextField($this->request['payload'])
                ),
                true
            )
            : [];

        $newPayload = AuthenticateService::generatePayload(
            $newPayload,
            $this->wordPressData,
            $this->jwtSettings,
            $user
        );

        if ($this->jwtSettings->getHooksSettings()->isHookEnabled(SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME)) {
            $newPayload = $this->wordPressData->applyFilters(
                SimpleJWTLoginHooks::JWT_PAYLOAD_ACTION_NAME,
                $newPayload,
                $this->request
            );
        }

        // Generate new refresh token
        $newRefreshToken = $this->generateRefreshToken();
        $newTokenExpiresAt = time() + ($this->jwtSettings->getAuthenticationSettings()->getAuthJwtRefreshTtl() * 60);
        
        // Rotate: delete old token, persist new one
        $this->tokenRepository->deleteByToken($encryptedToken);
        $this->tokenRepository->insert(
            $tokenData->user_id,
            $this->encryptRefreshToken($newRefreshToken),
            $newTokenExpiresAt
        );

        $userId    = (int) $this->wordPressData->getUserProperty($user, 'ID');
        $userEmail = (string) $this->wordPressData->getUserProperty($user, 'user_email');

        if ($this->jwtSettings->getAuditLogSettings()->isAuditEventEnabled(AuditEvents::AUTH_REFRESH_TOKEN_SUCCESS)) {
            $this->wordPressData->doAction(
                SimpleJWTLoginHooks::AUDIT_AUTH_REFRESH_TOKEN_SUCCESS,
                $userId,
                $userEmail
            );
        }

        $response = [
            'success' => true,
            'data'    => [
                'jwt' => $this->getJwtWrapper()->encode(
                    $newPayload,
                    JwtKeyFactory::getFactory($this->jwtSettings)->getPrivateKey(),
                    $this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm()
                ),
                'refresh_token' => $newRefreshToken
            ]
        ];

        if ($this->jwtSettings->getHooksSettings()
            ->isHookEnabled(SimpleJWTLoginHooks::HOOK_RESPONSE_REFRESH_TOKEN)
        ) {
            $response = $this->wordPressData
                ->applyFilters(
                    SimpleJWTLoginHooks::HOOK_RESPONSE_REFRESH_TOKEN,
                    $response,
                    $user
                );
        }

        return $this->wordPressData->createResponse($response);
    }
}
