<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Exceptions\ValidationException;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
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
        $this->checkValidateTokenEnabled();
        $this->checkAllowedIPAddress();
        $this->validateAuthenticationAuthKey(
            $this->jwtSettings->getAuthenticationSettings()->isValidateAuthKeyRequired()
        );

        return $this->validateAuth();
    }

    /**
     * @throws Exception
     */
    private function checkValidateTokenEnabled()
    {
        if (!$this->jwtSettings->getAuthenticationSettings()->isValidateTokenEnabled()) {
            throw new Exception(
                esc_html(__('Validate Token endpoint is not enabled.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_VALIDATE_TOKEN_NOT_ENABLED)
            );
        }
    }

    /**
     * @return WP_REST_Response
     * @throws Exception
     */
    private function validateAuth()
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

        $user = $this->getUserDetails($loginParameter);
        if ($user === null) {
            throw new Exception(
                esc_html(__('User not found.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND)
            );
        }

        $this->validateJwtRevoked(
            $this->wordPressData->getUserProperty($user, 'ID'),
            $this->jwt
        );

        $userArray = $this->wordPressData->wordpressUserToArray($user);
        unset($userArray['user_pass']);
        $jwtData                  = $this->extractJwtData($this->jwt);
        $jwtParameters            = [];
        $jwtParameters['token']   = $this->jwt;
        $jwtParameters['header']  = $jwtData['header'];
        $jwtParameters['payload'] = $jwtData['payload'];
        if (isset($jwtParameters['payload']['exp'])) {
            $jwtParameters['expire_in'] = $jwtParameters['payload']['exp'] - time();
        }

        $response = [
            'success' => true,
            'data'    => [
                'user' => $userArray,
                'roles' => $this->wordPressData->getUserRoles($user),
                'jwt'  => [
                    $jwtParameters
                ]
            ]
        ];

        if ($this->jwtSettings->getHooksSettings()
            ->isHookEnabled(SimpleJWTLoginHooks::HOOK_RESPONSE_VALIDATE_TOKEN)
        ) {
            $response = $this->wordPressData
                ->triggerFilter(
                    SimpleJWTLoginHooks::HOOK_RESPONSE_VALIDATE_TOKEN,
                    $response,
                    $user
                );
        }

        return $this->wordPressData->createResponse($response);
    }
}
