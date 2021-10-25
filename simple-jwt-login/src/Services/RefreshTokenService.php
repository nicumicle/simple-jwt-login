<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Libraries\JWT;
use SimpleJWTLogin\Modules\Settings\AuthenticationSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use WP_REST_Response;

class RefreshTokenService extends AuthenticateService
{
    public function makeAction()
    {
        $this->checkAuthenticationEnabled();
        $this->checkAllowedIPAddress();
        $this->validateAuthenticationAuthKey(ErrorCodes::ERR_INVALID_AUTH_CODE_PROVIDED);

        return $this->refreshJwt();
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @return WP_REST_Response
     * @throws Exception
     */
    private function refreshJwt()
    {
        $this->jwt = $this->getJwtFromRequestHeaderOrCookie();
        if (empty($this->jwt)) {
            throw new Exception(
                __('JWT is missing.', 'simple-jwt-login'),
                ErrorCodes::ERR_JWT_NOT_FOUND_ON_AUTH_REFRESH
            );
        }

        try {
            JWT::$leeway = self::JWT_LEEVAY;
            JWT::decode(
                $this->jwt,
                JwtKeyFactory::getFactory($this->jwtSettings)->getPublicKey(),
                [$this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm()]
            );
        } catch (Exception $e) {
            if ($e->getCode() !== ErrorCodes::ERR_TOKEN_EXPIRED) {
                throw new Exception($e->getMessage(), $e->getCode());
            }
        }

        $payload = $this->getPayloadFromJWT($this->jwt);

        if ($payload === null) {
            throw new Exception(
                __('There was an error with your JWT and we can not refresh it.', 'simple-jwt-login'),
                ErrorCodes::ERR_JWT_REFRESH_NULL_PAYLOAD
            );
        }

        $result = $this->getUserParameterValueFromPayload(
            $payload,
            $this->jwtSettings->getLoginSettings()->getJwtLoginByParameter()
        );


        $user = $this->getUserDetails($result);
        if ($user !== null) {
            $userMeta = $this->wordPressData
                ->getUserMeta(
                    $this->wordPressData->getUserProperty($user, 'id'),
                    SimpleJWTLoginSettings::REVOKE_TOKEN_KEY
                );
            foreach ($userMeta as $key) {
                if ($key === $this->jwt) {
                    throw new Exception(__('Jwt is invalid.', 'simple-jwt-login'), ErrorCodes::ERR_REVOKED_TOKEN);
                }
            }
        }

        if (isset($payload[AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP])) {
            $refreshTimeToLive =
                $payload[AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP]
                + $this->jwtSettings->getAuthenticationSettings()->getAuthJwtRefreshTtl() * 60;

            if (time() > $refreshTimeToLive) {
                throw new Exception(
                    __('JWT is too old to be refreshed.', 'simple-jwt-login'),
                    ErrorCodes::ERR_JWT_REFRESH_JWT_TOO_OLD
                );
            }

            $expValue = time() + ($this->jwtSettings->getAuthenticationSettings()->getAuthJwtTtl() * 60);
            $payload[AuthenticationSettings::JWT_PAYLOAD_PARAM_EXP] = $expValue;
        }

        //Display result
        return $this->wordPressData->createResponse(
            [
                'success' => true,
                'data'    => [
                    'jwt' => JWT::encode(
                        $payload,
                        JwtKeyFactory::getFactory($this->jwtSettings)->getPrivateKey(),
                        $this->jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm()
                    )
                ]
            ]
        );
    }
}
