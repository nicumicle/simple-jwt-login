<?php

namespace SimpleJWTLogin\Services;

use Exception;
use SimpleJWTLogin\ErrorCodes;
use SimpleJWTLogin\Modules\Settings\LoginSettings;
use SimpleJWTLogin\Modules\Settings\WebhooksSettings;
use SimpleJWTLogin\Modules\SimpleJWTLoginHooks;

class DeleteUserService extends BaseService implements ServiceInterface
{
    /**
     * @return mixed|\WP_REST_Response
     * @throws Exception
     */
    public function makeAction()
    {
        try {
            return $this->deleteUser();
        } catch (Exception $e) {
            $this->wordPressData->triggerAction(
                SimpleJWTLoginHooks::AUDIT_AUTH_DELETE_USER_FAILED,
                null,
                null,
                $e->getMessage()
            );
            throw $e;
        }
    }

    /**
     * Main Function for Delete user route
     * @throws Exception
     */
    public function deleteUser()
    {
        if ($this->jwtSettings->getDeleteUserSettings()->isDeleteAllowed() === false) {
            throw  new Exception(
                __('Delete is not enabled.', 'simple-jwt-login'),
                ErrorCodes::ERR_DELETE_IS_NOT_ENABLED
            );
        }

        $this->jwt = $this->getJwtFromRequestHeaderOrCookie();
        if (empty($this->jwt)) {
            throw new Exception(
                __('The `jwt` parameter is missing.', 'simple-jwt-login'),
                ErrorCodes::ERR_DELETE_MISSING_JWT
            );
        }

        if ($this->jwtSettings->getDeleteUserSettings()->isAuthKeyRequiredOnDelete()
            && $this->validateAuthKey() === false
        ) {
            throw new Exception(
                sprintf(
                    __('Missing AUTH KEY ( %s ).', 'simple-jwt-login'),
                    $this->jwtSettings->getAuthCodesSettings()->getAuthCodeKey()
                ),
                ErrorCodes::ERR_DELETE_MISSING_AUTH_KEY
            );
        }

        $allowedIpsString = trim($this->jwtSettings->getDeleteUserSettings()->getAllowedDeleteIps());
        if (!empty($allowedIpsString) && !$this->serverHelper->isClientIpInList($allowedIpsString)) {
            throw new Exception(
                sprintf(
                    __('You are not allowed to delete users from this IP: %s', 'simple-jwt-login'),
                    $this->serverHelper->getClientIP()
                ),
                ErrorCodes::ERR_DELETE_INVALID_CLIENT_IP
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
                __('User not found.', 'simple-jwt-login'),
                ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND
            );
        }

        $userId = $this->wordPressData->getUserProperty($user, 'ID');

        $this->validateJwtRevoked($userId, $this->jwt);

        $this->tokenRepository->deleteByUserId($userId);

        $result = $this->wordPressData->deleteUser($user);

        if ($result === false) {
            throw new Exception(
                __('User not found.', 'simple-jwt-login'),
                ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND
            );
        }

        $this->wordPressData->triggerAction(
            SimpleJWTLoginHooks::AUDIT_AUTH_DELETE_USER_SUCCESS,
            $this->wordPressData->getUserProperty($user, 'ID'),
            $this->wordPressData->getUserProperty($user, 'user_email')
        );

        (new WebhooksService($this->jwtSettings, $this->webhookLogRepository))->dispatch(
            WebhooksSettings::EVENT_DELETE_USER,
            [
                'user_id'    => $this->wordPressData->getUserProperty($user, 'ID'),
                'user_email' => $this->wordPressData->getUserProperty($user, 'user_email'),
            ]
        );

        if ($this->jwtSettings->getHooksSettings()->isHookEnable(SimpleJWTLoginHooks::DELETE_USER_ACTION_NAME)) {
            $this->wordPressData->triggerAction(SimpleJWTLoginHooks::DELETE_USER_ACTION_NAME, $user);
        }

        $response = [
            'message' => __('User was successfully deleted.', 'simple-jwt-login'),
            'id' => $result
        ];
        if ($this->jwtSettings->getHooksSettings()
            ->isHookEnable(SimpleJWTLoginHooks::HOOK_RESPONSE_DELETE_USER)
        ) {
            $response = $this->wordPressData
                ->triggerFilter(
                    SimpleJWTLoginHooks::HOOK_RESPONSE_DELETE_USER,
                    $response,
                    $user
                );
        }

        return $this->wordPressData->createResponse($response);
    }
}
