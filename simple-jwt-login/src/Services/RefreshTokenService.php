<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Libraries\JWT\JWT;
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
                ErrorCodes::ERR_INVALID_AUTH_CODE_PROVIDED,
                $this->jwtSettings->getAuthenticationSettings()->isRefreshAuthKeyRequired()
            );

            return $this->refreshJwt();
        } catch (Exception $exception) {
            $this->wordPressData->triggerAction(
                SimpleJWTLoginHooks::AUDIT_AUTH_REFRESH_TOKEN_FAILED,
                null,
                null,
                $exception->getMessage()
            );
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
                __('Refresh Token endpoint is not enabled.', 'simple-jwt-login'),
                ErrorCodes::ERR_REFRESH_TOKEN_NOT_ENABLED
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
            throw new Exception(
                __('Refresh token is missing.', 'simple-jwt-login'),
                ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH
            );
        }

        // Validate refresh token against database
        $encryptedToken = $this->encryptRefreshToken($refreshToken);
        $tokenData = $this->tokenRepository->getByToken($encryptedToken);
        
        if ($tokenData === null) {
            throw new Exception(
                __('Invalid refresh token.', 'simple-jwt-login'),
                ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH
            );
        }

        $user = $this->wordPressData->getUserDetailsById($tokenData->user_id);
        if (!$this->wordPressData->isInstanceOfuser($user)) {
            throw new Exception(__('User not found.', 'simple-jwt-login'), ErrorCodes::ERR_REVOKED_TOKEN);
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
            $newPayload = $this->wordPressData->triggerFilter(
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

        $this->wordPressData->triggerAction(
            SimpleJWTLoginHooks::AUDIT_AUTH_REFRESH_TOKEN_SUCCESS,
            $this->wordPressData->getUserProperty($user, 'ID'),
            $this->wordPressData->getUserProperty($user, 'user_email')
        );

        $response = [
            'success' => true,
            'data'    => [
                'jwt' => JWT::encode(
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
                ->triggerFilter(
                    SimpleJWTLoginHooks::HOOK_RESPONSE_REFRESH_TOKEN,
                    $response,
                    $user
                );
        }

        return $this->wordPressData->createResponse($response);
    }
}
