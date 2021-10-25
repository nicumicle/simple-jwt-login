<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Libraries\JWT;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use WP_REST_Response;
use WP_User;

class LoginService extends BaseService implements ServiceInterface
{
    /**
     * @return WP_REST_Response|null
     * @throws Exception
     */
    public function makeAction()
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
            $this->wordPressData->getUserProperty($user, 'id'),
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
     * @throws Exception
     */
    private function validateDoLogin()
    {
        $this->jwt = $this->getJwtFromRequestHeaderOrCookie();
        if ($this->jwtSettings->getLoginSettings()->isAutologinEnabled() === false) {
            throw new Exception(
                __('Auto-login is not enabled on this website.', 'simple-jwt-login'),
                ErrorCodes::ERR_AUTO_LOGIN_NOT_ENABLED
            );
        }

        if (empty($this->jwt)) {
            throw new Exception(
                __('Wrong Request.', 'simple-jwt-login'),
                ErrorCodes::ERR_VALIDATE_LOGIN_WRONG_REQUEST
            );
        }

        if ($this->jwtSettings->getLoginSettings()->isAuthKeyRequiredOnLogin() && $this->validateAuthKey() === false) {
            throw  new Exception(
                sprintf(
                    __('Invalid Auth Code ( %s ) provided.', 'simple-jwt-login'),
                    $this->jwtSettings->getAuthCodesSettings()->getAuthCodeKey()
                ),
                ErrorCodes::ERR_INVALID_AUTH_CODE_PROVIDED
            );
        }
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
    }
}
