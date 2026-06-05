<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Exceptions\ValidationException;
use SimpleJWTLogin\Modules\AuditEvents;
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

            throw $exception;
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
            if ($this->jwtSettings->getAuditLogSettings()->isAuditEventEnabled(AuditEvents::AUTH_LOGIN_SESSION_FAILED)) {
                $this->wordPressData->doAction(
                    SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_SESSION_FAILED,
                    null,
                    null,
                    $exception->getMessage()
                );
            }
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
                esc_html(__('User not found.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND)
            );
        }

        $userId    = (int) $this->wordPressData->getUserProperty($user, 'ID');
        $userEmail = (string) $this->wordPressData->getUserProperty($user, 'user_email');

        $this->validateJwtRevoked($userId, $this->jwt);
        $this->wordPressData->loginUser($user);

        if ($this->jwtSettings->getAuditLogSettings()->isAuditEventEnabled(AuditEvents::AUTH_LOGIN_SESSION_SUCCESS)) {
            $this->wordPressData->doAction(
                SimpleJWTLoginHooks::AUDIT_AUTH_LOGIN_SESSION_SUCCESS,
                $userId,
                $userEmail
            );
        }

        (new WebhooksService($this->jwtSettings, $this->webhookLogRepository))->dispatch(
            WebhooksSettings::EVENT_LOGIN,
            [
                'user_id'    => $userId,
                'user_email' => $userEmail,
            ]
        );

        if ($this->jwtSettings->getHooksSettings()->isHookEnabled(SimpleJWTLoginHooks::LOGIN_ACTION_NAME)) {
            $this->wordPressData->doAction(SimpleJWTLoginHooks::LOGIN_ACTION_NAME, $user);
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
        if (!$this->jwtSettings->getLoginSettings()->isAutologinEnabled()) {
            throw new Exception(
                esc_html(__('Auto-login is not enabled on this website.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_AUTO_LOGIN_NOT_ENABLED)
            );
        }
                
        $this->jwt = $this->getJwtFromRequestHeaderOrCookie();
        if (empty($this->jwt)) {
            throw new ValidationException(
                esc_html(__('JWT is missing.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_JWT_IS_MISSING)
            );
        }

        $interimPayload = $this->getPayloadFromJWT($this->jwt);
        if (!empty($interimPayload[AuthenticateService::TFA_PENDING_CLAIM])) {
            throw new Exception(
                esc_html(__('This JWT requires two-factor verification before it can be used for login.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_TWO_FACTOR_INTERIM_JWT_REJECTED)
            );
        }

        if ($this->jwtSettings->getLoginSettings()->isAuthKeyRequiredOnLogin()) {
            $this->validateAuthKey();
        }

        // Validate IP
        $allowedIPs = $this->jwtSettings->getLoginSettings()->getAllowedLoginIps();
        if (!empty($allowedIPs) && !$this->serverHelper->isClientIpInList($allowedIPs)) {
            throw new Exception(
                esc_html(
                    sprintf(
                        /* translators: %s: client IP address */
                        __('This IP[ %s ] is not allowed to auto-login.', 'simple-jwt-login'),
                        $this->serverHelper->getClientIP()
                    )
                ),
                absint(ErrorCodes::ERR_IP_IS_NOT_ALLOWED_TO_LOGIN)
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
                    esc_html(__('The JWT issuer(iss) is not allowed to auto-login.', 'simple-jwt-login')),
                    absint(ErrorCodes::ERR_INVALID_IIS_LOGIN)
                );
            }
        }
    }
}
