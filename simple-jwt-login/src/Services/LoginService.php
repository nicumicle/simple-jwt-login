<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\WebhooksSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;
use WP_REST_Response;
use WP_User;

class LoginService extends BaseService implements ServiceInterface
{
    public function makeAction()
    {
        try {
            return $this->makeActionInternal();
        } catch (Exception $exception) {
            $redirectOnFail = $this->jwtSettings->getLoginSettings()->getAutologinRedirectOnFail();
            if (!empty($redirectOnFail)) {
                $redirectOnFail = $this->includeRequestParameters($redirectOnFail);
                $redirectOnFail .= (strpos($redirectOnFail, '?') !== false ? '&' : '?')
                    . http_build_query([
                        'error_message' => $exception->getMessage(),
                        'error_code' => $exception->getCode()
                    ]);

                if ($this->jwtSettings->getGeneralSettings()->isSafeRedirectEnabled()) {
                    return $this->wordPressData->redirectSafe($redirectOnFail);
                }

                return $this->wordPressData->redirect($redirectOnFail);
            }
            throw new Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @return WP_REST_Response|null
     * @throws Exception
     */
    public function makeActionInternal()
    {
        try {
            return $this->doLogin();
        } catch (Exception $exception) {
            $this->wordPressData->triggerAction(
                SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_SESSION_FAILED,
                null,
                null,
                $exception->getMessage()
            );
            throw $exception;
        }
    }

    /**
     * @return WP_REST_Response|null
     * @throws Exception
     */
    private function doLogin()
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

        $this->wordPressData->triggerAction(
            SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_SESSION_SUCCESS,
            $this->wordPressData->getUserProperty($user, 'ID'),
            $this->wordPressData->getUserProperty($user, 'user_email')
        );

        (new WebhooksService($this->jwtSettings, $this->webhookLogRepository))->dispatch(
            WebhooksSettings::EVENT_LOGIN,
            [
                'user_id'    => $this->wordPressData->getUserProperty($user, 'ID'),
                'user_email' => $this->wordPressData->getUserProperty($user, 'user_email'),
            ]
        );

        if ($this->jwtSettings->getHooksSettings()->isHookEnabled(SimpleJWTLoginHooks::LOGIN_ACTION_NAME)) {
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
        if (!$this->jwtSettings->getLoginSettings()->isAutologinEnabled()) {
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

        if ($this->jwtSettings->getLoginSettings()->isAuthKeyRequiredOnLogin() && !$this->validateAuthKey()) {
            throw new Exception(
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
            if ($payload === null ||
                !isset($payload['iss']) ||
                !in_array($payload['iss'], array_map('trim', explode(',', $allowedIss)), true)) {
                throw new Exception(
                    __('The JWT issuer(iss) is not allowed to auto-login.', 'simple-jwt-login'),
                    ErrorCodes::ERR_INVALID_IIS_LOGIN
                );
            }
        }
    }
}
