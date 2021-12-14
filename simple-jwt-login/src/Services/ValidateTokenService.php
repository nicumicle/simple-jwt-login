<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use WP_REST_Response;

class ValidateTokenService extends AuthenticateService
{
    /**
     * @return WP_REST_Response
     * @throws Exception
     */
    public function makeAction()
    {
        $this->checkAuthenticationEnabled();
        $this->checkAllowedIPAddress();

        return $this->validateAuth();
    }

    /**
     * @return  WP_REST_Response
     * @throws Exception
     */
    private function validateAuth()
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

        $user = $this->getUserDetails($loginParameter);
        if ($user === null) {
            throw new Exception(
                __('User not found.', 'simple-jwt-login'),
                ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND
            );
        }

        $this->validateJwtRevoked(
            $this->wordPressData->getUserProperty($user, 'id'),
            $this->jwt
        );

        $userArray = $this->wordPressData->convertUserToArray($user);
        if (isset($userArray['user_pass'])) {
            unset($userArray['user_pass']);
        }
        $jwtParameters          = [];
        $jwtParameters['token'] = $this->jwt;
        list($header, $payload) = explode('.', $this->jwt);
        $jwtParameters['header']  = json_decode(base64_decode($header), true);
        $jwtParameters['payload'] = json_decode(base64_decode($payload), true);
        if (isset($jwtParameters['payload']['exp'])) {
            $jwtParameters['expire_in'] = $jwtParameters['payload']['exp'] - time();
        }

        return $this->wordPressData->createResponse(
            [
                'success' => true,
                'data'    => [
                    'user' => $userArray,
                    'roles' => $this->wordPressData->getUserRoles($user),
                    'jwt'  => [
                        $jwtParameters
                    ]
                ]
            ]
        );
    }
}
