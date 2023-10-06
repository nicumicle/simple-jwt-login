<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\ArrayHelper;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use WP_REST_Response;
use WP_User;

class LoginService extends BaseService implements ServiceInterface
{
    public function makeAction()
    {
        try {
            return $this->makeActionInternal();
        } catch (Exception $e) {
            $redirectOnFail = $this->jwtSettings->getLoginSettings()->getAutologinRedirectOnFail();
            if (!empty($redirectOnFail)) {
                $redirectOnFail = $this->includeRequestParameters($redirectOnFail);
                $redirectOnFail .= (strpos($redirectOnFail, '?') !== false ? '&' : '?')
                    . http_build_query([
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode()
                    ]);

                return $this->wordPressData->redirect($redirectOnFail);
            }
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return WP_REST_Response|null
     * @throws Exception
     */
    public function makeActionInternal()
    {
        $this->validateDoLogin();
        $loginParameter = $this->validateJWTAndGetUserValueFromPayload(
            $this->jwtSettings->getLoginSettings()->getJwtLoginByParameter()
        );

        /** @var WP_User|null $user */
        $user = $this->getUserDetails($loginParameter);
        if ($user === null) {
            throw new Exception(
                __('User not found.', 'simple-jwt-login'),
                ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND
            );
        }

        $this->validateJwtRevoked(
            $this->wordPressData->getUserProperty($user, 'ID'),
            $this->jwt
        );
        $this->wordPressData->loginUser($user);
        if ($this->jwtSettings->getHooksSettings()->isHookEnable(SimpleJWTLoginHooks::LOGIN_ACTION_NAME)) {
            $this->wordPressData->triggerAction(SimpleJWTLoginHooks::LOGIN_ACTION_NAME, $user);
        }

        return (new RedirectService())
            ->withSettings($this->jwtSettings)
            ->withSession($this->session)
            ->withCookies($this->cookie)
            ->withRequest($this->request)
            ->withUser($user)
            ->withServerHelper($this->serverHelper)
            ->makeAction();
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @throws Exception
     */
    private function validateDoLogin()
    {
        // Validate Autologin is enabled
        $this->jwt = $this->getJwtFromRequestHeaderOrCookie();
        if ($this->jwtSettings->getLoginSettings()->isAutologinEnabled() === false) {
            throw new Exception(
                __('Auto-login is not enabled on this website.', 'simple-jwt-login'),
                ErrorCodes::ERR_AUTO_LOGIN_NOT_ENABLED
            );
        }

        // Check if JWT is present
        if (empty($this->jwt)) {
            throw new Exception(
                __('Wrong Request.', 'simple-jwt-login'),
                ErrorCodes::ERR_VALIDATE_LOGIN_WRONG_REQUEST
            );
        }

        // Validate AUTH KEY
        if ($this->jwtSettings->getLoginSettings()->isAuthKeyRequiredOnLogin() && $this->validateAuthKey() === false) {
            throw  new Exception(
                sprintf(
                    __('Invalid Auth Code ( %s ) provided.', 'simple-jwt-login'),
                    $this->jwtSettings->getAuthCodesSettings()->getAuthCodeKey()
                ),
                ErrorCodes::ERR_INVALID_AUTH_CODE_PROVIDED
            );
        }

        // Validate IP
        $allowedIPs = $this->jwtSettings->getLoginSettings()->getAllowedLoginIps();
        if (!empty($allowedIPs) && !$this->serverHelper->isClientIpInList($allowedIPs)) {
            throw new Exception(
                sprintf(
                    __('This IP[ %s ] is not allowed to auto-login.', 'simple-jwt-login'),
                    $this->serverHelper->getClientIP()
                ),
                ErrorCodes::ERR_IP_IS_NOT_ALLOWED_TO_LOGIN
            );
        }

        // Validate ISS
        $allowedIss = $this->jwtSettings->getLoginSettings()->getAllowedLoginIss();
        if (!empty($allowedIss)) {
            $payload = $this->getPayloadFromJWT($this->jwt);
            if ($payload == null  ||
                !isset($payload['iss']) ||
                !in_array($payload['iss'], ArrayHelper::convertStringToArray($allowedIss))) {
                throw new Exception(
                    __('The JWT issuer(iss) is not allowed to auto-login.', 'simple-jwt-login'),
                    ErrorCodes::ERR_INVALID_IIS_LOGIN
                );
            }
        }
    }
}
