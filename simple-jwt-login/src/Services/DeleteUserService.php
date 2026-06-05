<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\Exceptions\ValidationException;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\AuditEvents;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\Settings\WebhooksSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;

class DeleteUserService extends BaseService implements ServiceInterface
{
    /**
     * @return mixed|\WP_REST_Response
     * @throws Exception
     * @throws \Throwable
     */
    public function makeAction()
    {
        try {
            return $this->deleteUser();
        } catch (\Throwable $exception) {
            if ($this->jwtSettings->getAuditLogSettings()->isAuditEventEnabled(AuditEvents::AUTH_DELETE_USER_FAILED)) {
                $this->wordPressData->doAction(
                    SimpleJWTLoginHooks::AUDIT_AUTH_DELETE_USER_FAILED,
                    null,
                    null,
                    $exception->getMessage()
                );
            }
            throw $exception;
        }
    }

    /**
     * Main Function for Delete user route
     * @throws Exception
     */
    public function deleteUser()
    {
        if (!$this->jwtSettings->getDeleteUserSettings()->isDeleteAllowed()) {
            throw new Exception(
                esc_html(__('Delete is not enabled.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_DELETE_IS_NOT_ENABLED)
            );
        }

        $this->jwt = $this->getJwtFromRequestHeaderOrCookie();
        if (empty($this->jwt)) {
            throw new ValidationException(
                esc_html(__('The `jwt` parameter is missing.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_DELETE_MISSING_JWT)
            );
        }

        if ($this->jwtSettings->getDeleteUserSettings()->isAuthKeyRequiredOnDelete()) {
            $this->validateAuthKey();
        }

        $allowedIpsString = trim($this->jwtSettings->getDeleteUserSettings()->getAllowedDeleteIps());
        if (!empty($allowedIpsString) && !$this->serverHelper->isClientIpInList($allowedIpsString)) {
            throw new Exception(
                esc_html(
                    sprintf(
                        /* translators: %s: client IP address */
                        __('You are not allowed to delete users from this IP: %s', 'simple-jwt-login'),
                        $this->serverHelper->getClientIP()
                    )
                ),
                absint(ErrorCodes::ERR_DELETE_INVALID_CLIENT_IP)
            );
        }

        $jwtParts = $this->extractJwtData($this->jwt);
        $ruleConfig = $this->jwtSettings->getJwtRulesSettings()->findMatchingRuleConfig($jwtParts);

        $getUserBy = $this->jwtSettings->getLoginSettings()->getJWTLoginBy();
        $loginByParameter = $this->jwtSettings->getLoginSettings()->getJwtLoginByParameter();
        if ($ruleConfig !== null) {
            $getUserBy = isset($ruleConfig['login_by']) ? (int)$ruleConfig['login_by'] : LoginSettings::JWT_LOGIN_BY_EMAIL;
            $loginByParameter = isset($ruleConfig['login_by_parameter']) ? $ruleConfig['login_by_parameter'] : '';
        }

        $registerParameter = $this->validateJWTAndGetUserValueFromPayload($loginByParameter);

        switch ($getUserBy) {
            case LoginSettings::JWT_LOGIN_BY_EMAIL:
                $user = $this->wordPressData->getUserDetailsByEmail($registerParameter);
                break;
            case LoginSettings::JWT_LOGIN_BY_WORDPRESS_USER_ID:
                $user = $this->wordPressData->getUserDetailsById($registerParameter);
                break;
            case LoginSettings::JWT_LOGIN_BY_USER_LOGIN:
                $user = $this->wordPressData->getUserByUserLogin($registerParameter);
                break;
            default:
                $user = false;
        }

        if ($user === false) {
            throw new Exception(
                esc_html(__('User not found.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND)
            );
        }

        $userId    = $this->wordPressData->getUserProperty($user, 'ID');
        $userEmail = (string) $this->wordPressData->getUserProperty($user, 'user_email');

        $this->validateJwtRevoked($userId, $this->jwt);

        $this->tokenRepository->deleteByUserId($userId);

        $result = $this->wordPressData->deleteUser($user);

        if ($result === false) {
            throw new Exception(
                esc_html(__('User not found.', 'simple-jwt-login')),
                absint(ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND)
            );
        }

        if ($this->jwtSettings->getAuditLogSettings()->isAuditEventEnabled(AuditEvents::AUTH_DELETE_USER_SUCCESS)) {
            $this->wordPressData->doAction(
                SimpleJWTLoginHooks::AUDIT_AUTH_DELETE_USER_SUCCESS,
                $userId,
                $userEmail
            );
        }

        (new WebhooksService($this->jwtSettings, $this->webhookLogRepository))->dispatch(
            WebhooksSettings::EVENT_DELETE_USER,
            [
                'user_id'    => $userId,
                'user_email' => $userEmail,
            ]
        );

        if ($this->jwtSettings->getHooksSettings()->isHookEnabled(SimpleJWTLoginHooks::DELETE_USER_ACTION_NAME)) {
            $this->wordPressData->doAction(SimpleJWTLoginHooks::DELETE_USER_ACTION_NAME, $user);
        }

        $response = [
            'message' => __('User was successfully deleted.', 'simple-jwt-login'),
            'id' => $result
        ];
        if ($this->jwtSettings->getHooksSettings()
            ->isHookEnabled(SimpleJWTLoginHooks::HOOK_RESPONSE_DELETE_USER)
        ) {
            $response = $this->wordPressData
                ->applyFilters(
                    SimpleJWTLoginHooks::HOOK_RESPONSE_DELETE_USER,
                    $response,
                    $user
                );
        }

        return $this->wordPressData->createResponse($response);
    }
}
